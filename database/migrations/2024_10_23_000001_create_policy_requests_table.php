<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('policy_requests', function (Blueprint $table) {
            $table->id();
            $table->string('agent_id');
            $table->string('client_request_id');
            $table->string('customer_name');
            $table->date('date_of_birth');
            $table->enum('destination', ['DOMESTIC', 'INTERNATIONAL']);
            $table->date('trip_start');
            $table->date('trip_end');
            $table->enum('plan_code', ['BASIC', 'PLUS', 'PREMIUM']);
            $table->unsignedInteger('sum_insured');
            $table->decimal('premium', 12, 2)->nullable();
            $table->enum('state', [
                'RECEIVED',
                'VALIDATED',
                'SUBMITTED',
                'PENDING_CONFIRMATION',
                'ISSUED',
                'FAILED',
                'NEEDS_RECONCILIATION',
            ])->default('RECEIVED');
            $table->string('provider_reference')->nullable();
            $table->string('policy_number')->unique()->nullable();
            $table->timestamps();

            $table->unique(['agent_id', 'client_request_id'], 'policy_requests_agent_client_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_requests');
    }
};
?>
