<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiAuditLog extends Model
{
    protected $table = 'api_audit_logs';

    protected $primaryKey = 'id_audit_log';

    public $incrementing = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'correlation_id',
        'id_pengguna',
        'service_name',
        'http_method',
        'endpoint',
        'http_status',
        'request_summary',
        'response_summary',
        'latency_ms',
        'ip_address',
        'error_code',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'latency_ms' => 'integer',
            'request_summary' => 'array',
            'response_summary' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
