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
    Schema::create('recordings', function (Blueprint $table) {
        $table->id();
        $table->string('meeting_id')->nullable();
        $table->string('file_name');
        $table->string('drive_file_id');
        $table->string('mime_type')->nullable();
        $table->bigInteger('file_size')->nullable();
        $table->timestamp('recorded_at')->nullable();
        $table->boolean('processed')->default(false);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recordings');
    }
};
