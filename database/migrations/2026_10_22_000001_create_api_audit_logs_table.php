<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('api_audit_logs')) {
            return;
        }

        Schema::create('api_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id_audit_log');
            $table->string('correlation_id', 100)->nullable();
            // users.id_user bertipe char(36) pada database proyek ini.
            $table->char('id_pengguna', 36)->nullable();
            $table->string('service_name', 50);
            $table->string('http_method', 10);
            $table->string('endpoint', 500);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('request_summary')->nullable();
            $table->json('response_summary')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('correlation_id', 'idx_api_audit_logs_correlation');
            $table->index('id_pengguna', 'idx_api_audit_logs_pengguna');
            $table->index('service_name', 'idx_api_audit_logs_service');
            $table->index('http_status', 'idx_api_audit_logs_status');
            $table->index('created_at', 'idx_api_audit_logs_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_audit_logs');
    }
};
