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
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();

            // Polymorphic relation (model that "owns" the file)
            $table->morphs('uploadable'); // creates uploadable_id and uploadable_type

            // Upload categorization
            $table->string('type')->nullable();
            // e.g. avatar, document, attachment, banner, gallery, etc.

            // File details
            $table->string('original_name');    // e.g. user_uploaded_file.pdf
            $table->string('stored_name');      // hashed or UUID filename
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('size')->nullable(); // in bytes
            $table->string('mime_type')->nullable();

            // Disk and path
            $table->string('disk')->default('local'); // e.g. local, s3, do_spaces, azure
            $table->string('path'); // path relative to the disk
            $table->text('url')->nullable(); // cached URL (optional, for performance)

            // Optional metadata
            $table->json('meta')->nullable(); // e.g. thumbnails, processing info

            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
