<?php

namespace Tests\Feature;

use Karabin\Fabriq\ContentGetters\ContentResourceContext;
use Karabin\Fabriq\Models\Contact;
use Karabin\Fabriq\Models\ContentTemplate;
use Karabin\Fabriq\Models\ContentTemplateField;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Tests\AdminUserTestCase;

class HasContentFeatureTest extends AdminUserTestCase
{
    public function test_it_omits_content_fields_not_in_the_assigned_template(): void
    {
        $page = $this->createPageWithRemovedField();

        $content = $page->getSimpleFieldContent('en');

        $this->assertSame('Valid value', $content->get('title'));
        $this->assertFalse($content->has('boxes'));
    }

    public function test_it_omits_content_fields_not_in_the_assigned_template_in_resource_context(): void
    {
        $page = $this->createPageWithRemovedField();

        $content = ContentResourceContext::withModels(
            [$page],
            fn () => $page->getSimpleFieldContent('en'),
        );

        $this->assertSame('Valid value', $content->get('title'));
        $this->assertFalse($content->has('boxes'));
    }

    public function test_it_omits_content_fields_not_in_the_slug_resolved_template(): void
    {
        $template = ContentTemplate::factory()->create(['slug' => 'contact']);
        ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'title',
        ]);
        $removedField = ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'boxes',
        ]);
        $contact = Contact::factory()->create();
        $contact->updateContent([
            'title' => 'Valid value',
            'boxes' => ['Stale content'],
        ], 'en');
        $removedField->delete();

        $content = $contact->getSimpleFieldContent('en');

        $this->assertSame('Valid value', $content->get('title'));
        $this->assertFalse($content->has('boxes'));
    }

    private function createPageWithRemovedField(): Page
    {
        $template = ContentTemplate::factory()->create();
        ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'page_title',
        ]);
        ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'title',
        ]);
        $removedField = ContentTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'boxes',
        ]);

        $page = Page::factory()->create(['template_id' => $template->id]);
        $page->updateContent([
            'title' => 'Valid value',
            'boxes' => ['Stale content'],
        ], 'en');

        $removedField->delete();

        return $page;
    }
}
