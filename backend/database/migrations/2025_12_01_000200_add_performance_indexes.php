<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Licenses table indexes
        Schema::table('licenses', function (Blueprint $table) {
            $table->index('status');
            $table->index('product_id');
            $table->index('customer_id');
            $table->index('expires_at');
            $table->index(['status', 'expires_at']);
            $table->index('purchased_at');
        });

        // License activations indexes
        Schema::table('license_activations', function (Blueprint $table) {
            $table->index('status');
            $table->index('activation_type');
            $table->index('activated_at');
            $table->index('last_check');
            $table->index(['license_id', 'status']);
        });

        // Support tickets indexes
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->index('status');
            $table->index('priority');
            $table->index('category');
            $table->index('customer_id');
            $table->index('assigned_to');
            $table->index('created_at');
            $table->index(['status', 'priority']);
            $table->index(['customer_id', 'status']);
        });

        // Ticket replies indexes
        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->index('ticket_id');
            $table->index('user_id');
            $table->index('created_at');
        });

        // Payments indexes
        Schema::table('payments', function (Blueprint $table) {
            $table->index('customer_id');
            $table->index('status');
            $table->index('transaction_id');
            $table->index('paid_at');
            $table->index('created_at');
            $table->index(['customer_id', 'status']);
        });

        // API keys indexes
        Schema::table('api_keys', function (Blueprint $table) {
            $table->index('status');
            $table->index('customer_id');
            $table->index('product_id');
            $table->index('expires_at');
            $table->index('last_used_at');
        });

        // Customers indexes
        Schema::table('customers', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
        });

        // Products indexes
        Schema::table('products', function (Blueprint $table) {
            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['product_id']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['status', 'expires_at']);
            $table->dropIndex(['purchased_at']);
        });

        Schema::table('license_activations', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['activation_type']);
            $table->dropIndex(['activated_at']);
            $table->dropIndex(['last_check']);
            $table->dropIndex(['license_id', 'status']);
        });

        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['category']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['assigned_to']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status', 'priority']);
            $table->dropIndex(['customer_id', 'status']);
        });

        Schema::table('ticket_replies', function (Blueprint $table) {
            $table->dropIndex(['ticket_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['transaction_id']);
            $table->dropIndex(['paid_at']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['customer_id', 'status']);
        });

        Schema::table('api_keys', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['customer_id']);
            $table->dropIndex(['product_id']);
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['last_used_at']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['type']);
        });
    }
};

