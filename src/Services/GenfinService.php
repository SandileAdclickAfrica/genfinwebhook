<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use App\Models\WebhookLog;

/**
 * Service class for interacting with the Genfin API.
 *
 * Handles authentication and lead submission to the Genfin system,
 * and logs the webhook payload and response for auditing purposes.
 */
class GenfinService
{
    /**
     * Logger for recording API activity and errors.
     *
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Guzzle HTTP client for making API requests.
     *
     * @var Client
     */
    protected Client $client;

    /**
     * The base URL of the Genfin API.
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * Username for API authentication.
     *
     * @var string
     */
    protected string $username;

    /**
     * Password for API authentication.
     *
     * @var string
     */
    protected string $password;

    /**
     * GenfinService constructor.
     *
     * @param LoggerInterface $logger A PSR-compliant logger instance.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->baseUrl = $_ENV['GENFIN_BASE_URL'];
        $this->username = $_ENV['GENFIN_USERNAME'];
        $this->password = $_ENV['GENFIN_PASSWORD'];

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * Authenticates with the Genfin API using stored credentials.
     *
     * @return array Authentication result with GUIDs or error details.
     */
    private function authenticate(): array
    {
        try {
            $url = $this->buildAuthUrl();
            $response = $this->client->get($url);
            $result = json_decode($response->getBody(), true);

            $this->logger->info('Genfin API Authentication:', $result);
            return $result;
        } catch (RequestException $e) {
            return $this->handleException($e, 'Genfin API Authentication Response');
        } catch (\Exception $e) {
            return $this->handleGenericException($e, 'Unexpected error during authentication');
        }
    }

    /**
     * Submits a lead to the Genfin API and logs both the request and response.
     *
     * @param array $payloadData Data received from the webhook.
     * @return array API response or error details.
     */
    public function logWebhookPayload(array $payloadData): array
    {
        try {
            $auth = $this->authenticate();

            // Return early if authentication fails
            if (!empty($auth['error'])) {
                return $auth;
            }

            // Build payload using auth result and request data
            $payload = $this->preparePayload($payloadData, $auth['apiGUID']);

            // Submit payload to Genfin
            $response = $this->client->post('lead', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $auth['authenticationGUID'],
                ],
                'timeout' => 15,
                'body' => json_encode($payload),
            ]);

            $body = json_decode($response->getBody(), true);

            // Log request/response to database
            WebhookLog::create([
                'payload' => json_encode($payload),
                'response' => json_encode($body),
                'status_code' => $response->getStatusCode(),
                'ip_address' => $payloadData['ipAddress'] ?? null,
            ]);

            $this->logger->info('Genfin API Lead Response:', $body);
            return $body;

        } catch (RequestException $e) {
            return $this->handleException($e, 'Genfin API Lead Response');
        } catch (\Exception $e) {
            return $this->handleGenericException($e, 'Unexpected error in logWebhookPayload()');
        }
    }

    /**
     * Builds the authentication URL with credentials as query parameters.
     *
     * @return string
     */
    private function buildAuthUrl(): string
    {
        return "authentication?username={$this->username}&password={$this->password}";
    }

    /**
     * Prepares the final payload to be submitted to the Genfin API.
     *
     * @param array $data Form input or webhook request data.
     * @param string $apiGuid Unique API GUID from Genfin auth response.
     * @return array Prepared payload.
     */
    private function preparePayload(array $data, string $apiGuid): array
    {
        return [
            "ipAddress"             => $data['ipAddress'],
            "guid"                  => $apiGuid,
            "loanAmount"            => $data['loanAmount'],
            "tradeHistory"          => filter_var($data['tradeHistory'], FILTER_VALIDATE_BOOLEAN),
            "turnoverHistory"       => filter_var($data['turnoverHistory'], FILTER_VALIDATE_BOOLEAN),
            "companyTradingName"    => $data['companyTradingName'],
            "natureOfBusiness"      => $data['natureOfBusiness'],
            "loanPurpose"           => $data['loanPurpose'],
            "premises"              => $data['premises'],
            "numberEmployees"       => $data['numberEmployees'],
            "websiteAddress"        => $data['websiteAddress'],
            "hearAboutUs"           => $data['hearAboutUs'],
            "firstName"             => $data['firstName'],
            "lastName"              => $data['lastName'],
            "emailAddress"          => $data['emailAddress'],
            "primaryContactNumber"  => $data['primaryContactNumber'],
            "productSelection"      => $data['productSelection'],
            "source"                => $data['source'],
            "companyRegNumber"      => $data['companyRegNumber'],
            "affiliateNumber"       => "SMESouthAfrica",
            "autoEmail"             => filter_var($data['autoEmail'], FILTER_VALIDATE_BOOLEAN),
            "confirmConsent"        => filter_var($data['confirmConsent'], FILTER_VALIDATE_BOOLEAN),
            "extLinkID"             => "extLink",
        ];
    }

    /**
     * Handles Guzzle request exceptions and logs the error.
     *
     * @param RequestException $e
     * @param string $context Context message for logging.
     * @return array Formatted error details.
     */
    private function handleException(RequestException $e, string $context): array
    {
        $error = [
            'error' => true,
            'message' => $e->getMessage(),
            'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
            'body' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
        ];

        $this->logger->error($context, $error);
        return $error;
    }

    /**
     * Handles general exceptions and logs the error.
     *
     * @param \Exception $e
     * @param string $context Context message for logging.
     * @return array Formatted error details.
     */
    private function handleGenericException(\Exception $e, string $context): array
    {
        $this->logger->error($context, ['message' => $e->getMessage()]);

        return [
            'error' => true,
            'message' => 'Unexpected error: ' . $e->getMessage(),
        ];
    }
}
