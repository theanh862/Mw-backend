<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('return_status')->nullable()->after('payment_status'); // requested|approved|rejected
            $table->text('return_reason')->nullable()->after('return_status');
            $table->text('admin_note')->nullable()->after('return_reason');
            $table->string('refund_status')->nullable()->after('admin_note');    // processing|refunded
            $table->decimal('refund_amount', 15, 0)->nullable()->after('refund_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['return_status', 'return_reason', 'admin_note', 'refund_status', 'refund_amount']);
        });
    }
};
