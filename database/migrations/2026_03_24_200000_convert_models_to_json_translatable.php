<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ArtisticStyle: label, description
        Schema::table('artistic_styles', function (Blueprint $table) {
            $table->json('label')->nullable()->after('style_key');
            $table->json('description')->nullable()->after('label');
        });
        DB::table('artistic_styles')->get()->each(function ($row) {
            DB::table('artistic_styles')->where('id', $row->id)->update([
                'label' => json_encode(array_filter(['es' => $row->label_es, 'en' => $row->label_en])),
                'description' => json_encode(array_filter(['es' => $row->description_es, 'en' => $row->description_en])),
            ]);
        });

        // Product: name, description (as JSON)
        Schema::table('products', function (Blueprint $table) {
            $table->json('name')->nullable()->after('product_key');
            $table->json('description')->nullable()->after('name');
        });
        DB::table('products')->get()->each(function ($row) {
            DB::table('products')->where('id', $row->id)->update([
                'name' => json_encode(array_filter(['es' => $row->name_es, 'en' => $row->name_en])),
                'description' => json_encode(array_filter(['es' => $row->description_es, 'en' => $row->description_en])),
            ]);
        });

        // Format: label, description (as JSON)
        Schema::table('formats', function (Blueprint $table) {
            $table->json('label')->nullable()->after('format_key');
            $table->json('description')->nullable()->after('label');
        });
        DB::table('formats')->get()->each(function ($row) {
            DB::table('formats')->where('id', $row->id)->update([
                'label' => json_encode(array_filter(['es' => $row->label_es, 'en' => $row->label_en])),
                'description' => json_encode(array_filter(['es' => $row->description_es ?? null, 'en' => $row->description_en ?? null])),
            ]);
        });

        // GalleryExample: title (as JSON)
        Schema::table('gallery_examples', function (Blueprint $table) {
            $table->json('title')->nullable()->after('id');
        });
        DB::table('gallery_examples')->get()->each(function ($row) {
            DB::table('gallery_examples')->where('id', $row->id)->update([
                'title' => json_encode(array_filter(['es' => $row->title_es, 'en' => $row->title_en])),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('artistic_styles', function (Blueprint $table) {
            $table->dropColumn(['label', 'description']);
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['name', 'description']);
        });
        Schema::table('formats', function (Blueprint $table) {
            $table->dropColumn(['label', 'description']);
        });
        Schema::table('gallery_examples', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
