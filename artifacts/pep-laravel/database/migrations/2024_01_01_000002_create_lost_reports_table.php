<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lost_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('status', ['探索中', '解決済', 'キャンセル'])->default('探索中');
            $table->string('owner_name', 255);
            $table->string('owner_contact', 255);
            $table->dateTime('lost_datetime_from')->nullable();
            $table->dateTime('lost_datetime_to')->nullable();
            $table->string('lost_location_estimated', 255)->nullable();
            $table->enum('category', ['財布・カバン類', '衣類', '電子機器', '傘', 'その他']);
            $table->text('features');
            $table->timestamps();

            $table->index('status');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lost_reports');
    }
};
