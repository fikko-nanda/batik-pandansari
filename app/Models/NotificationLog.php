<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    protected $table = 'notification_logs';

    protected $primaryKey = 'id_notification_log';

    public $incrementing = true;

    protected $fillable = [
        'id_pengguna',
        'channel',
        'provider',
        'notification_type',
        'recipient_masked',
        'reference_type',
        'reference_id',
        'provider_message_id',
        'delivery_status',
        'attempt_count',
        'error_code',
        'error_message',
        'sent_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }
}
