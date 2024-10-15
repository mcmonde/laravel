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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string("last_name")->nullable();
            $table->string('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->boolean('status')->default(true)->nullable();
            $table->boolean('allow_login')->default(true)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('first_name');
            $table->dropColumn('middle_name');
            $table->dropColumn('last_name');
            $table->dropColumn('address');
            $table->dropColumn('contact_number');
            $table->dropColumn('status');
            $table->dropColumn('allow_login');
            $table->dropColumn('created_by');

            $table->string('name')->nullable();
        });
    }
};
