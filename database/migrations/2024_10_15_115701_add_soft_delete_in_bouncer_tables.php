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
        Schema::table('roles', function (Blueprint $table){
            $table->softDeletes();
        });
        Schema::table('assigned_roles', function (Blueprint $table){
            $table->softDeletes();
        });
        Schema::table('abilities', function (Blueprint $table){
            $table->softDeletes();
        });
        Schema::table('permissions', function (Blueprint $table){
            $table->softDeletes();
        });
//        Schema::table('settings', function (Blueprint $table){
//            $table->softDeletes();
//        });
        Schema::table('logs', function (Blueprint $table){
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table){
            $table->dropSoftDeletes();
        });
        Schema::table('assigned_roles', function (Blueprint $table){
            $table->dropSoftDeletes();
        });
        Schema::table('abilities', function (Blueprint $table){
            $table->dropSoftDeletes();
        });
        Schema::table('permissions', function (Blueprint $table){
            $table->dropSoftDeletes();
        });
//        Schema::table('settings', function (Blueprint $table){
//            $table->dropSoftDeletes();
//        });
        Schema::table('logs', function (Blueprint $table){
            $table->dropSoftDeletes();
        });
    }
};
