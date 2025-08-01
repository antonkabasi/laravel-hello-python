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
        Schema::create('stream_data', function (Blueprint $table) {
            $table->id();                           // auto-incrementing primary key
            $table->timestamp('timestamp');         // the time the Python script generated
            $table->double('value');                // the random value
            $table->timestamps();                   // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stream_data');
    }
};
