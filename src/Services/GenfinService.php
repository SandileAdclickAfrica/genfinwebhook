<?php

namespace App\Services;

// sandile

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

class GenfinService
{
    protected LoggerInterface $logger;
    protected Client $client;
    protected string $baseUrl;
    protected string $username;
    protected string $password;

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

    private function authentication()
    {
        try {
            $url = 'authentication?username=' . $this->username . '&password=' . $this->password;

            $response = $this->client->request('GET', $url);
            $result = json_decode($response->getBody(), true);

            $this->logger->info('Genfin API Authentication:', $result);
            return $result;

        } catch (RequestException $e) {
            $error = [
                'error' => true,
                'message' => $e->getMessage(),
                'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'body' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ];

            $this->logger->error('Genfin API Authentication Response:', $error);
            return $error;

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error during authentication', [
                'message' => $e->getMessage()
            ]);
            return [
                'error' => true,
                'message' => 'Unexpected error: ' . $e->getMessage()
            ];
        }
    }

    public function lead()
    {
        try {
            $auth = $this->authentication();

            // Fail early if authentication failed
            if (!empty($auth['error'])) {
                return $auth;
            }

            $response = $this->client->request('POST', 'lead', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $auth['authenticationGUID']
                ],
                'json' => [
                    "ipAddress" => "154.117.178.10",
                    "guid" => $auth['apiGUID'],
                    "loanAmount" => 120000,
                    "tradeHistory" => true,
                    "turnoverHistory" => true,
                    "companyTradingName" => "CompName",
                    "natureOfBusiness" => "Nature",
                    "loanPurpose" => "Purpose",
                    "premises" => "Owned",
                    "numberEmployees" => "10-20",
                    "websiteAddress" => "test.co.za",
                    "hearAboutUs" => "Lead Provider",
                    "firstName" => "First",
                    "lastName" => "Last",
                    "emailAddress" => "test@test.co.za",
                    "primaryContactNumber" => "0123456789",
                    "genfinRepresentative" => "GFRep",
                    "comments" => "comments",
                    "productSelection" => "OBL",
                    "additionalContactNumber" => "0123456789",
                    "source" => "WEB",
                    "companyRegNumber" => "1234/123456/12",
                    "affiliateNumber" => "Test0905",
                    "autoEmail" => false,
                    "confirmConsent" => true,
                    "utmSource" => "utmS",
                    "utmCampaign" => "utmC",
                    "utmMedium" => "utmM",
                    "utmContent" => "utmCon",
                    "gcLid" => "glc",
                    "extLinkID" => "extLink"
                ],
            ]);

            $body = json_decode($response->getBody(), true);
            $this->logger->info('Lead API Response:', $body);
            return $body;

        } catch (RequestException $e) {
            $error = [
                'error' => true,
                'message' => $e->getMessage(),
                'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'body' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ];

            $this->logger->error('Lead API request failed', $error);
            return $error;

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in lead()', [
                'message' => $e->getMessage()
            ]);
            return [
                'error' => true,
                'message' => 'Unexpected error: ' . $e->getMessage(),
            ];
        }
    }


    public function logWebhookPayload(  $payloadData )
    {
        try {
            $auth = $this->authentication();

            // Fail early if authentication failed
            if (!empty($auth['error'])) {
                return $auth;
            }

            // Prepare and clean the payload
            $payload = [
                "ipAddress"=> $payloadData['ipAddress'],
                "guid"=> $auth['apiGUID'],
                "loanAmount"=> 120000,
                "tradeHistory"=> true,
                "turnoverHistory"=> true,
                "companyTradingName"=> "CompName",
                "natureOfBusiness"=> "Nature",
                "loanPurpose"=> "Purpose",
                "premises" => "Owned",
                "numberEmployees" => "10-20",
                "websiteAddress"=> "test.co.za",
                "hearAboutUs" => "Lead Provider",
                "firstName" => "First",
                "lastName" => "Last",
                "emailAddress" => "test@test.co.za",
                "primaryContactNumber" => "0123456789",
                "genfinRepresentative" => "GFRep",
                "comments" => "comments",
                "productSelection" => "OBL",
                "additionalContactNumber" => "0123456789",
                "source" => "WEB",
                "companyRegNumber" => "1234/123456/12",
                "affiliateNumber" => "Test0905",
                "autoEmail" => false,
                "confirmConsent" => true,
                "utmSource" => "utmS",
                "utmCampaign" => "utmC",
                "utmMedium" => "utmM",
                "utmContent" => "utmCon",
                "gcLid" => "glc",
                "extLinkID" => "extLink",
                //'leadValue' => $payloadData['leadValue'] ?? null,
                //'tradeHistory' => filter_var($payloadData['tradeHistory'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];

            $response = $this->client->request('POST', 'lead', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $auth['authenticationGUID'],
                    'Accept' => 'application/json',
                ],
                'timeout' => 15,
                'body' => json_encode($payload),
            ]);

            $body = json_decode($response->getBody(), true);
            $this->logger->info('Genfin API Lead Response:', $body);
            return $body;

        } catch (RequestException $e) {
            $error = [
                'error' => true,
                'message' => $e->getMessage(),
                'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : null,
                'body' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ];

            $this->logger->error('Genfin API Lead Response:', $error);
            return $error;

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error in lead()', [
                'message' => $e->getMessage()
            ]);
            return [
                'error' => true,
                'message' => 'Unexpected error: ' . $e->getMessage(),
            ];
        }
    }
}
