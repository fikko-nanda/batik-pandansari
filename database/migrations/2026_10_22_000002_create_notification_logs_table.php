<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_logs')) {
            return;
        }

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->bigIncrements('id_notification_log');
            $table->char('id_pengguna', 36)->nullable();
            $table->string('channel', 20);
            $table->string('provider', 50);
            $table->string('notification_type', 100);
            $table->string('recipient_masked', 255)->nullable();
            $table->string('reference_type', 50)->nullable();
            $table->string('reference_id', 100)->nullable();
            $table->string('provider_message_id', 150)->nullable();
            $table->string('delivery_status', 30)->default('queued');
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('id_pengguna', 'idx_notification_logs_pengguna');
            $table->index('channel', 'idx_notification_logs_channel');
            $table->index('provider', 'idx_notification_logs_provider');
            $table->index('notification_type', 'idx_notification_logs_type');
            $table->index('delivery_status', 'idx_notification_logs_status');
            $table->index('created_at', 'idx_notification_logs_created');
            $table->index(
                ['reference_type', 'reference_id'],
                'idx_notification_logs_reference'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
