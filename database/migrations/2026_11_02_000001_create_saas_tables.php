<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->string('description', 500)->nullable();
            $table->unsignedBigInteger('monthly_price')->default(0);
            $table->unsignedBigInteger('yearly_price')->default(0);
            $table->unsignedInteger('max_monthly_runs')->nullable();
            $table->unsignedInteger('max_active_workflows')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('billing_cycle', 20)->default('monthly');
            $table->string('status', 20)->default('active');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'ends_at']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 32)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->string('billing_cycle', 20);
            $table->unsignedBigInteger('amount');
            $table->string('gateway', 30)->default('zarinpal');
            $table->string('authority', 64)->nullable()->unique();
            $table->string('ref_id', 64)->nullable()->index();
            $table->string('card_pan', 32)->nullable();
            $table->string('card_hash', 128)->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_response')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        $now = now();
        $plans = config('saas.plans', []);

        foreach ($plans as $plan) {
            DB::table('plans')->insert([
                'slug' => $plan['slug'],
                'name' => $plan['name'],
                'description' => $plan['description'] ?? null,
                'monthly_price' => $plan['monthly_price'],
                'yearly_price' => $plan['yearly_price'],
                'max_monthly_runs' => $plan['max_monthly_runs'],
                'max_active_workflows' => $plan['max_active_workflows'],
                'features' => json_encode($plan['features'] ?? [], JSON_UNESCAPED_UNICODE),
                'is_active' => true,
                'sort_order' => $plan['sort_order'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
