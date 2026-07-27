<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('found_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('management_no', 20)->unique()->notNull();
            $table->enum('status', ['保管中', '返還済', '警察提出済', '期間満了処分'])->default('保管中');
            $table->enum('category', ['財布・カバン類', '衣類', '電子機器', '傘', 'その他'])->nullable();
            $table->string('sub_category', 255)->nullable();
            $table->text('features');
            $table->dateTime('found_datetime');
            $table->string('found_location', 255)->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('storage_location', 255)->nullable();
            $table->string('finder_name', 255)->nullable();
            $table->string('finder_contact', 255)->nullable();
            $table->boolean('rights_waived')->default(false);
            $table->dateTime('returned_at')->nullable();
            $table->string('returned_to', 255)->nullable();
            $table->string('returned_by', 255)->nullable();
            $table->boolean('identity_verified')->default(false);
            $table->boolean('receipt_signed')->default(false);
            $table->timestamps();

            $table->index(['status', 'found_datetime']);
            $table->index('management_no');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('found_items');
    }
};
