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
        Schema::create('apps', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('key')->unique();
            $table->string('secret');

            $table->string('host')->nullable();
            $table->unsignedSmallInteger('port')->default(443);
            $table->enum('scheme', ['https', 'http'])->default('https');

            $table->json('allowed_origins')->default('["*"]');

            $table->unsignedSmallInteger('ping_interval')->default(60);
            $table->unsignedSmallInteger('activity_timeout')->default(30);

            $table->unsignedInteger('max_connections')->nullable();
            $table->unsignedInteger('max_message_size')->default(10000);

            $table->string('accept_client_events_from')->default('members');

            $table->boolean('rate_limiting_enabled')->default(false);
            $table->unsignedSmallInteger('rate_limit_max_attempts')->default(60);
            $table->unsignedSmallInteger('rate_limit_decay_seconds')->default(60);
            $table->boolean('rate_limit_terminate_on_limit')->default(false);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apps');
    }
};
