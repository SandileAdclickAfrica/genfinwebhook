<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLogModel extends Model
{
    protected $table = 'webhook_logs'; // Your table name
    protected $fillable = [
        'payload', 'response', 'status_code', 'ip_address'
    ];
}
