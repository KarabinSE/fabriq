<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\RevisionTemplate;
use Karabin\Fabriq\Models\RevisionTemplateField;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class ContentMigrationFeatureTest extends AdminUserTestCase
{
    public function test_it_migrates_latest_revision_content_and_shared_meta_to_translations(): void
    {
        $template = RevisionTemplate::factory()->create();
        RevisionTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'page_title',
            'translated' => true,
        ]);
        RevisionTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'cards',
            'translated' => true,
            'repeater' => true,
        ]);
        RevisionTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'shared_label',
            'translated' => false,
        ]);

        $page = Page::factory()->create(['template_id' => $template->id]);

        Schema::table('pages', function (Blueprint $table) {
            $table->integer('revision')->default(2);
            $table->integer('published_version')->nullable();
        });

        $this->createLegacyI18nTables();
        Schema::create('revision_meta', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->integer('model_version')->nullable();
            $table->string('meta_key');
            $table->text('meta_value')->nullable();
            $table->timestamps();
        });

        $addTerm = function (string $key, int $version, array $translations) use ($page): void {
            $termId = DB::table('i18n_terms')->insertGetId([
                'key' => $key,
                'model_type' => $page->getMorphClass(),
                'model_id' => $page->id,
                'model_version' => $version,
                'field_key' => null,
                'description' => $key,
                'group' => 'main_content',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($translations as $locale => $content) {
                DB::table('i18n_definitions')->insert([
                    'term_id' => $termId,
                    'locale' => $locale,
                    'content' => json_encode($content, JSON_THROW_ON_ERROR),
                    'approved' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        };

        $addTerm('pages_'.$page->id.'_1_page_title', 1, [
            'sv' => 'Outdated title',
            'en' => 'Outdated title',
        ]);
        $addTerm('pages_'.$page->id.'_2_page_title', 2, [
            'sv' => 'Aktuell titel',
            'en' => 'Current title',
        ]);
        $addTerm('pages_'.$page->id.'_2_cards', 2, [
            'sv' => [['title' => 'Kort']],
            'en' => [['title' => 'Card']],
        ]);

        DB::table('revision_meta')->insert([
            'model_type' => $page->getMorphClass(),
            'model_id' => $page->id,
            'model_version' => 2,
            'meta_key' => 'shared_label',
            'meta_value' => json_encode('Shared label', JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('slugs')->where('model_id', $page->id)->update([
            'source_key' => 'pages_'.$page->id.'_2_page_title',
        ]);

        $migration = require __DIR__.'/../database/migrations/2026_10_21_090000_replace_revision_content_with_translations.php';
        $migration->up();
        $dropLegacyTermColumns = require __DIR__.'/../database/migrations/2026_10_21_100000_drop_legacy_i18n_term_columns.php';
        $dropLegacyTermColumns->up();

        $migratedPage = Page::findOrFail($page->id);

        $this->assertSame('Aktuell titel', $migratedPage->getSimpleFieldContent('sv')->get('page_title'));
        $this->assertSame('Current title', $migratedPage->getSimpleFieldContent('en')->get('page_title'));
        $this->assertSame('Shared label', $migratedPage->getSimpleFieldContent('en')->get('shared_label'));
        $this->assertSame([['title' => 'Kort']], $migratedPage->getFieldContent('sv')->get('cards'));
        $this->assertSame([['title' => 'Card']], $migratedPage->getFieldContent('en')->get('cards'));
        $this->assertDatabaseHas('slugs', [
            'model_id' => $page->id,
            'source_key' => 'page_title',
        ]);
        $migratedPage->updateContent(['page_title' => 'Updated title'], 'en');
        $this->assertSame('Updated title', $migratedPage->getSimpleFieldContent('en')->get('page_title'));
        $this->assertFalse(Schema::hasColumn('i18n_terms', 'key'));
        $this->assertFalse(Schema::hasColumn('i18n_terms', 'description'));
        $this->assertFalse(Schema::hasColumn('i18n_terms', 'group'));
        $this->assertFalse(Schema::hasColumn('pages', 'revision'));
        $this->assertFalse(Schema::hasColumn('pages', 'published_version'));
        $this->assertFalse(Schema::hasTable('revision_meta'));
    }

    private function createLegacyI18nTables(): void
    {
        Schema::dropIfExists('i18n_definitions');
        Schema::dropIfExists('i18n_terms');

        Schema::create('i18n_terms', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->integer('model_version')->nullable();
            $table->string('field_key')->nullable();
            $table->string('description');
            $table->string('group')->default('ungrouped');
            $table->timestamps();
            $table->index(['model_type', 'model_id', 'model_version', 'field_key'], 'i18n_terms_lookup_idx');
            $table->index('key');
        });

        Schema::create('i18n_definitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('term_id');
            $table->string('locale');
            $table->longText('content')->nullable();
            $table->boolean('approved')->default(false);
            $table->timestamps();
            $table->index(['term_id', 'locale']);
            $table->unique(['term_id', 'locale']);
        });
    }
}
