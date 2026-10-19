<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $table = 'webhook_logs';

    protected $primaryKey = 'id_webhook_log';

    public $incrementing = false;

    protected $keyType = 'string';

    const CREATED_AT = 'dibuat_pada';

    const UPDATED_AT = 'diperbarui_pada';

    protected $fillable = [
        'id_webhook_log',
        'provider',
        'provider_transaction_id',
        'id_order',
        'endpoint',
        'method',
        'payload',
        'signature',
        'status',
        'http_status',
        'alamat_ip',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
        ];
    }
}
