<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PayloadModel extends Model
{
    protected $table = 'payload'; // Your table name
    protected $fillable = [
        'webhook_log_id', 'ipAddress', 'loanAmount', 'tradeHistory', 'turnoverHistory', 'companyTradingName', 'natureOfBusiness', 'loanPurpose',
        'premises', 'numberEmployees', 'websiteAddress', 'hearAboutUs', 'firstName', 'lastName', 'emailAddress', 'primaryContactNumber',
        'productSelection', 'source', 'companyRegNumber', 'affiliateNumber', 'autoEmail', 'confirmConsent', 'extLinkID'
    ];
}