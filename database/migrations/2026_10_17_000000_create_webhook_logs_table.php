<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->char('id_webhook_log', 36)->primary();
            $table->string('provider', 50);
            $table->string('provider_transaction_id', 150)->nullable();
            $table->char('id_order', 36)->nullable();
            $table->string('endpoint', 255);
            $table->string('method', 10);
            $table->longText('payload');
            $table->string('signature', 255)->nullable();
            $table->string('status', 30)->default('diterima');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('alamat_ip', 45)->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('dibuat_pada')->nullable();
            $table->timestamp('diperbarui_pada')->nullable();

            $table->index('id_order', 'idx_webhook_logs_order');
            $table->index('provider_transaction_id', 'idx_webhook_logs_transaction');
            $table->index('status', 'idx_webhook_logs_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
