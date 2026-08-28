<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Karabin\Fabriq\Fabriq;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->boolean('published')->after('name')->default(false);
        });

        $pages = Fabriq::getFqnModel('page')::whereNotNull('published_version')
            ->get()
            ->each(function ($page) {
                $page->published = true;
                $page->save();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('published');
        });
    }
};
