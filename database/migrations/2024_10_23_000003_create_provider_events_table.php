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
        Schema::create('provider_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_request_id')->constrained('policy_requests')->onDelete('cascade');
            $table->string('event_id')->unique();
            $table->enum('type', ['SUCCESS', 'TEMPORARY_FAILURE', 'TIMEOUT_AFTER_ISSUE', 'DELAYED_CALLBACK', 'DUPLICATE_CALLBACK']);
            $table->json('payload');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_events');
    }
};
?>
