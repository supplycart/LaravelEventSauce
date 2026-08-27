<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_snapshots', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('aggregate_root_type');
            $table->string('event_stream', 36);
            $table->unsignedBigInteger('aggregate_root_version');
            $table->longText('state');
            $table->timestamps();
            $table->unique(['aggregate_root_type', 'event_stream']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_snapshots');
    }
};
