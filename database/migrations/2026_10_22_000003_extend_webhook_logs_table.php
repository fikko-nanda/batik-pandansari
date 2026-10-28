<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel webhook_logs sudah ada dan dipakai Midtrans. Migration ini hanya
     * menambah kolom observability yang belum tersedia, bukan membuat tabel
     * duplikat, sehingga data dan perilaku lama tetap utuh.
     */
    public function up(): void
    {
        if (! Schema::hasTable('webhook_logs')) {
            return;
        }

        Schema::table('webhook_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('webhook_logs', 'event_id')) {
                $table->string('event_id', 100)->nullable()->after('provider');
            }

            if (! Schema::hasColumn('webhook_logs', 'event_type')) {
                $table->string('event_type', 100)->nullable()->after('event_id');
            }

            if (! Schema::hasColumn('webhook_logs', 'idempotency_key')) {
                $table->string('idempotency_key', 150)->nullable()->after('provider_transaction_id');
            }

            if (! Schema::hasColumn('webhook_logs', 'signature_valid')) {
                $table->boolean('signature_valid')->nullable()->after('signature');
            }

            if (! Schema::hasColumn('webhook_logs', 'processing_status')) {
                $table->string('processing_status', 30)->default('received')->after('status');
            }

            if (! Schema::hasColumn('webhook_logs', 'payload_summary')) {
                $table->json('payload_summary')->nullable()->after('processing_status');
            }

            if (! Schema::hasColumn('webhook_logs', 'response_summary')) {
                $table->json('response_summary')->nullable()->after('payload_summary');
            }

            if (! Schema::hasColumn('webhook_logs', 'attempt_count')) {
                $table->unsignedSmallInteger('attempt_count')->default(1)->after('response_summary');
            }

            if (! Schema::hasColumn('webhook_logs', 'error_message')) {
                $table->text('error_message')->nullable()->after('attempt_count');
            }

            if (! Schema::hasColumn('webhook_logs', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('error_message');
            }

            if (! Schema::hasColumn('webhook_logs', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('received_at');
            }
        });

        Schema::table('webhook_logs', function (Blueprint $table) {
            if (! $this->hasIndex('webhook_logs', 'idx_webhook_logs_idempotency')) {
                $table->index('idempotency_key', 'idx_webhook_logs_idempotency');
            }

            if (! $this->hasIndex('webhook_logs', 'idx_webhook_logs_processing')) {
                $table->index('processing_status', 'idx_webhook_logs_processing');
            }

            if (! $this->hasIndex('webhook_logs', 'idx_webhook_logs_event_type')) {
                $table->index('event_type', 'idx_webhook_logs_event_type');
            }

            if (! $this->hasIndex('webhook_logs', 'idx_webhook_logs_received')) {
                $table->index('received_at', 'idx_webhook_logs_received');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('webhook_logs')) {
            return;
        }

        Schema::table('webhook_logs', function (Blueprint $table) {
            foreach ([
                'event_id',
                'event_type',
                'idempotency_key',
                'signature_valid',
                'processing_status',
                'payload_summary',
                'response_summary',
                'attempt_count',
                'error_message',
                'received_at',
                'processed_at',
            ] as $column) {
                if (Schema::hasColumn('webhook_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $row) {
                if (($row->name ?? null) === $index) {
                    return true;
                }
            }

            return false;
        }

        $database = $connection->getDatabaseName();
        $rows = $connection->select(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS '
            .'WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$database, $table, $index]
        );

        return $rows !== [];
    }
};
