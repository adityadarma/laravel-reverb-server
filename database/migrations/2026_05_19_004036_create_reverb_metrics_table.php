<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reverb_metrics', function (Blueprint $table) {
            $table->id();
            $table->uuid('app_id')->index();
            $table->unsignedInteger('connections')->default(0);
            $table->unsignedInteger('channels')->default(0);
            $table->timestamp('recorded_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reverb_metrics');
    }
};
