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
        'event_id',
        'event_type',
        'provider_transaction_id',
        'idempotency_key',
        'id_order',
        'endpoint',
        'method',
        'payload',
        'signature',
        'signature_valid',
        'status',
        'processing_status',
        'payload_summary',
        'response_summary',
        'attempt_count',
        'error_message',
        'received_at',
        'processed_at',
        'http_status',
        'alamat_ip',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'signature_valid' => 'boolean',
            'payload_summary' => 'array',
            'response_summary' => 'array',
            'attempt_count' => 'integer',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
