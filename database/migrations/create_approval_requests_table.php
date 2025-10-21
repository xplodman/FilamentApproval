<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('request_type')->default('edit'); // 'create' or 'edit'
            $table->unsignedBigInteger('requester_id');
            $table->string('approvable_type');
            $table->ulid('approvable_id')->nullable();

            // Split data into attributes and relationships
            $table->json('attributes')->nullable(); // Main model attributes
            $table->json('relationships')->nullable(); // Relationship data
            $table->json('original_data')->nullable(); // Original values (for edit requests)
            $table->string('resource_class')->nullable(); // Filament resource class for form rendering

            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->unsignedBigInteger('decided_by_id')->nullable();
            $table->text('decided_reason')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->timestamps();
            $table->softDeletes(); // For archiving approved/rejected requests

            // Indexes for better performance
            $table->index(['approvable_type', 'approvable_id']);
            $table->index(['requester_id', 'status']);
            $table->index(['status', 'created_at']);

            $table->foreign('requester_id')->references('id')->on('users');
            $table->foreign('decided_by_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
