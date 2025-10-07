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
            $table->foreignId('storage_type_id')->nullable()->constrained('storage_types')->cascadeOnDelete();
            $table->foreignId('upload_category_id')->nullable()->constrained('upload_categories')->cascadeOnDelete();
            // File details
            $table->string('filename');      // hashed or UUID filename
            $table->string('original_name');    // e.g. user_uploaded_file.pdf
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable(); // in bytes
            $table->string('extension')->nullable();
            // Disk and path
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
