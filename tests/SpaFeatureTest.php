<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Karabin\Fabriq\ContentGetters\FileGetter;
use Karabin\Fabriq\ContentGetters\ImageGetter;
use Karabin\Fabriq\ContentGetters\VideoGetter;
use Karabin\Fabriq\Mail\AccountInvitation;
use Karabin\Fabriq\Models\Article;
use Karabin\Fabriq\Models\BlockType;
use Karabin\Fabriq\Models\Contact;
use Karabin\Fabriq\Models\Event;
use Karabin\Fabriq\Models\File;
use Karabin\Fabriq\Models\Image;
use Karabin\Fabriq\Models\Menu;
use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\Models\Notification;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\SmartBlock;
use Karabin\Fabriq\Models\User;
use Karabin\Fabriq\Models\Video;
use Karabin\Fabriq\Tests\AdminUserTestCase;
use Karabin\TranslatableRevisions\Models\RevisionMeta;
use Karabin\TranslatableRevisions\Models\RevisionTemplate;
use Karabin\TranslatableRevisions\Models\RevisionTemplateField;

class SpaFeatureTest extends AdminUserTestCase
{
    /** @test **/
    public function it_will_return_a_message_if_request_wants_json()
    {
        $response = $this->actingAs($this->user)->json('GET', '/admin/dashboard');

        $response->assertStatus(404);
        $this->assertSame('"Get outta here!"', $response->getContent());
    }

    /** @test **/
    public function it_returns_not_found_for_unknown_admin_routes()
    {
        $response = $this->actingAs($this->user)->get('/admin/this-route-does-not-exist');

        $response->assertNotFound();
    }

    /** @test **/
    public function it_keeps_the_json_not_found_message_for_unknown_admin_routes()
    {
        $response = $this->actingAs($this->user)->json('GET', '/admin/this-route-does-not-exist');

        $response->assertStatus(404);
        $this->assertSame('"Get outta here!"', $response->getContent());
    }

    /** @test **/
    public function it_renders_the_dashboard_through_inertia()
    {
        $response = $this->actingAs($this->user)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Dashboard/Index');
        $response->assertViewHas('page.props.pageTitle', 'Dashboard');
    }

    /** @test **/
    public function it_renders_profile_settings_through_inertia()
    {
        $response = $this->actingAs($this->user)->get('/admin/profile/settings');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Profile/Settings');
        $response->assertViewHas('page.props.profile.name', $this->user->name);
    }

    /** @test **/
    public function it_renders_notifications_through_inertia()
    {
        $page = \Karabin\Fabriq\Models\Page::factory()->create();
        $comment = $page->commentAs(
            $this->user,
            '<p>Hej på dig <span data-mention="" class="mention" data-email="'.$this->user->email.'">@'.$this->user->name.'</span></p>'
        );

        $response = $this->actingAs($this->user)->get('/admin/notifications');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Notifications/Index');
        $response->assertViewHas('page.props.unseen.data', fn (array $items) => count($items) >= 1
            && $items[0]['pageName'] === $page->name
            && $items[0]['id'] === Notification::where('notifiable_id', $comment->id)->firstOrFail()->id);
    }

    /** @test **/
    public function it_can_clear_notifications_through_admin_web_routes()
    {
        $page = \Karabin\Fabriq\Models\Page::factory()->create();
        $comment = $page->commentAs(
            $this->user,
            '<p>Hej på dig <span data-mention="" class="mention" data-email="'.$this->user->email.'">@'.$this->user->name.'</span></p>'
        );
        $notification = Notification::where('notifiable_id', $comment->id)->firstOrFail();

        $response = $this->actingAs($this->user)->post('/admin/notifications/'.$notification->id.'/clear');

        $response->assertRedirect('/admin/notifications');
        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
            'cleared_at' => null,
        ]);
    }

    /** @test **/
    public function it_renders_users_index_through_inertia()
    {
        \Karabin\Fabriq\Models\User::factory()->create([
            'name' => 'Zebra Green',
            'email' => 'zebra@example.test',
        ]);

        $response = $this->actingAs($this->user)->get('/admin/users?search=Zebra');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Users/Index');
        $response->assertViewHas('page.props.filters.search', 'Zebra');
        $response->assertViewHas('page.props.users.data', fn (array $items) => count($items) === 1
            && $items[0]['name'] === 'Zebra Green');
    }

    /** @test **/
    public function it_renders_users_edit_through_inertia()
    {
        $otherUser = User::factory()->create([
            'name' => 'Edit Me',
            'email' => 'edit-me@example.test',
        ]);

        $response = $this->actingAs($this->user)->get('/admin/users/'.$otherUser->id.'/edit');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Users/Edit');
        $response->assertViewHas('page.props.user.name', 'Edit Me');
        $response->assertViewHas('page.props.roles', fn (array $items) => count($items) >= 1);
    }

    /** @test **/
    public function it_can_create_a_user_through_admin_web_routes()
    {
        $response = $this->actingAs($this->user)->post('/admin/users', [
            'name' => 'Ralf Edstrom',
            'email' => 'ralf@example.test',
            'role_list' => ['admin'],
            'send_activation' => false,
        ]);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('users', [
            'name' => 'Ralf Edstrom',
            'email' => 'ralf@example.test',
        ]);
    }

    /** @test **/
    public function it_can_update_a_user_through_admin_web_routes()
    {
        $otherUser = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old-name@example.test',
        ]);

        $response = $this->actingAs($this->user)->patch('/admin/users/'.$otherUser->id, [
            'id' => $otherUser->id,
            'name' => 'New Name',
            'email' => 'new-name@example.test',
            'role_list' => ['admin'],
        ]);

        $response->assertRedirect('/admin/users/'.$otherUser->id.'/edit');
        $this->assertDatabaseHas('users', [
            'id' => $otherUser->id,
            'name' => 'New Name',
            'email' => 'new-name@example.test',
        ]);
        $this->assertSame(['admin'], $otherUser->fresh()->roles->pluck('name')->values()->all());
    }

    /** @test **/
    public function it_can_send_an_invitation_through_admin_web_routes()
    {
        Mail::fake(AccountInvitation::class);

        $invitedUser = User::factory()->create([
            'email' => 'invite-me@example.test',
        ]);

        $response = $this->actingAs($this->user)->post('/admin/users/'.$invitedUser->id.'/invitation');

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseHas('invitations', [
            'user_id' => $invitedUser->id,
        ]);
        Mail::assertQueued(AccountInvitation::class);
    }

    /** @test **/
    public function it_can_cancel_an_invitation_through_admin_web_routes()
    {
        $invitedUser = User::factory()->create();
        $invitedUser->createInvitation($this->user->id);

        $response = $this->actingAs($this->user)->delete('/admin/users/'.$invitedUser->id.'/invitation');

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseMissing('invitations', [
            'user_id' => $invitedUser->id,
        ]);
    }

    /** @test **/
    public function it_can_delete_a_user_through_admin_web_routes()
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($this->user)->delete('/admin/users/'.$otherUser->id);

        $response->assertRedirect('/admin/users');
        $this->assertDatabaseMissing('users', [
            'id' => $otherUser->id,
        ]);
    }

    /** @test **/
    public function it_renders_menus_index_through_inertia()
    {
        \Karabin\Fabriq\Models\Menu::factory()->create([
            'name' => 'Footer meny',
            'slug' => 'footer-menu',
        ]);

        $response = $this->actingAs($this->user)->get('/admin/menus');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Menus/Index');
        $response->assertViewHas('page.props.menus', fn (array $items) => count($items) >= 1
            && collect($items)->contains(fn (array $item) => $item['name'] === 'Footer meny'));
    }

    /** @test **/
    public function it_renders_menus_edit_through_inertia()
    {
        $menu = Menu::factory()->create([
            'name' => 'Huvudmeny',
        ]);
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $page = Page::factory()->create([
            'name' => 'Start',
        ]);
        $item = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
            'type' => 'internal',
            'page_id' => $page->id,
        ]);

        $response = $this->actingAs($this->user)->get('/admin/menus/'.$menu->id.'/edit');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Menus/Edit');
        $response->assertViewHas('page.props.menu.name', 'Huvudmeny');
        $response->assertViewHas('page.props.menuTree', fn (array $items) => count($items) === 1
            && $items[0]['id'] === $item->id
            && $items[0]['page']['name'] === 'Start');
    }

    /** @test **/
    public function it_renders_images_index_through_inertia()
    {
        Image::factory()->create([
            'alt_text' => 'Budget hero image',
        ]);

        $response = $this->actingAs($this->user)->get('/admin/media/images?search=Budget&sort=alt_text');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Images/Index');
        $response->assertViewHas('page.props.filters.search', 'Budget');
        $response->assertViewHas('page.props.filters.sort', 'alt_text');
        $response->assertViewHas('page.props.images.data', fn (array $items) => count($items) === 1
            && $items[0]['altText'] === 'Budget hero image');
    }

    /** @test **/
    public function it_renders_files_index_through_inertia()
    {
        File::factory()->create([
            'readable_name' => 'Budgetunderlag',
        ]);

        $response = $this->actingAs($this->user)->get('/admin/media/files?search=Budget&sort=file_name');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Files/Index');
        $response->assertViewHas('page.props.filters.search', 'Budget');
        $response->assertViewHas('page.props.filters.sort', 'file_name');
        $response->assertViewHas('page.props.files.data', fn (array $items) => count($items) === 1
            && $items[0]['name'] === 'Budgetunderlag');
    }

    /** @test **/
    public function it_renders_videos_index_through_inertia()
    {
        Video::factory()->create([
            'alt_text' => 'Budget launch teaser',
        ]);

        $response = $this->actingAs($this->user)->get('/admin/media/videos?search=Budget&sort=alt_text');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Videos/Index');
        $response->assertViewHas('page.props.filters.search', 'Budget');
        $response->assertViewHas('page.props.filters.sort', 'alt_text');
        $response->assertViewHas('page.props.videos.data', fn (array $items) => count($items) === 1
            && $items[0]['altText'] === 'Budget launch teaser');
    }

    /** @test **/
    public function it_renders_pages_index_through_inertia()
    {
        [$root, $template] = $this->ensurePageRootAndTemplate();

        $page = Page::factory()->create([
            'name' => 'Om oss',
            'template_id' => $template->id,
            'parent_id' => $root->id,
        ]);

        $response = $this->actingAs($this->user)->get('/admin/pages');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Pages/Index');
        $response->assertViewHas('page.props.pageTree', fn (array $items) => collect($items)->contains(
            fn (array $item) => $item['name'] === $page->name
        ));
    }

    /** @test **/
    public function it_renders_pages_edit_through_inertia()
    {
        [$root, $template] = $this->ensurePageRootAndTemplate();
        $blockType = BlockType::factory()->create([
            'name' => 'Textblock',
            'component_name' => 'TextBlock',
            'active' => true,
            'options' => [
                'visible_for' => [],
                'hidden_for' => [],
                'recommended_for' => [$template->slug],
            ],
        ]);
        RevisionTemplateField::factory()->create([
            'template_id' => $template->id,
            'name' => 'Box',
            'key' => 'boxes',
            'group' => 'boxes',
            'type' => 'repeater',
            'repeater' => true,
            'translated' => true,
        ]);

        $page = Page::factory()->create([
            'name' => 'Landningssida',
            'template_id' => $template->id,
            'parent_id' => $root->id,
        ]);
        $page->localizedContent = [
            'sv' => [
                'boxes' => [
                    [
                        'id' => 'box-1',
                        'name' => 'Ingress',
                        'hidden' => false,
                        'block_type' => [
                            'id' => $blockType->id,
                            'name' => $blockType->name,
                            'component_name' => $blockType->component_name,
                            'has_children' => false,
                            'options' => [
                                'visible_for' => [],
                                'hidden_for' => [],
                                'recommended_for' => [$template->slug],
                            ],
                        ],
                        'header' => 'Rubrik',
                        'columns' => 2,
                        'body' => '<p>Hej</p>',
                        'buttons' => [],
                    ],
                ],
            ],
        ];
        $page->save();

        $response = $this->actingAs($this->user)->get('/admin/pages/'.$page->id.'/edit?openComments=1&commentId=42');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Pages/Edit');
        $response->assertViewHas('page.props.page.name', 'Landningssida');
        $response->assertViewHas('page.props.page.template.name', $template->name);
        $response->assertViewHas('page.props.page.template.slug', $template->slug);
        $response->assertViewHas('page.props.page.fieldGroups', fn (array $items) => collect($items)->contains(
            fn (array $group) => collect($group['fields'] ?? [])->contains(
                fn (array $field) => $field['key'] === 'boxes' && $field['repeater'] === true
            )
        ));
        $response->assertViewHas('page.props.blockTypes', fn (array $items) => collect($items)->contains(
            fn (array $item) => $item['id'] === $blockType->id
                && $item['componentName'] === 'TextBlock'
        ));
        $response->assertViewHas('page.props.commentContext.openComments', true);
        $response->assertViewHas('page.props.commentContext.commentId', 42);
    }

    /** @test **/
    public function it_can_update_a_page_through_admin_web_routes()
    {
        [$root, $template] = $this->ensurePageRootAndTemplate();
        $blockType = BlockType::factory()->create([
            'name' => 'Textblock',
            'component_name' => 'TextBlock',
            'active' => true,
        ]);

        RevisionTemplateField::factory()->create([
            'template_id' => $template->id,
            'name' => 'Titel',
            'key' => 'page_title',
            'group' => 'main_content',
            'type' => 'text',
            'translated' => true,
        ]);
        RevisionTemplateField::factory()->create([
            'template_id' => $template->id,
            'name' => 'Box',
            'key' => 'boxes',
            'group' => 'boxes',
            'type' => 'repeater',
            'repeater' => true,
            'translated' => true,
        ]);

        $page = Page::factory()->create([
            'name' => 'Gammal sida',
            'template_id' => $template->id,
            'parent_id' => $root->id,
        ]);

        $response = $this->actingAs($this->user)->patch('/admin/pages/'.$page->id, [
            'name' => 'Ny sida',
            'localizedContent' => [
                'sv' => [
                    'page_title' => 'Ny titel',
                    'boxes' => [
                        [
                            'id' => 'box-1',
                            'name' => 'Ingress',
                            'hidden' => false,
                            'block_type' => [
                                'id' => $blockType->id,
                                'name' => $blockType->name,
                                'component_name' => $blockType->component_name,
                                'has_children' => false,
                                'options' => [
                                    'visible_for' => [],
                                    'hidden_for' => [],
                                    'recommended_for' => [],
                                ],
                            ],
                            'header' => 'Rubrik',
                            'columns' => 2,
                            'body' => '<p>Hej</p>',
                            'buttons' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect('/admin/pages/'.$page->id.'/edit');
        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'name' => 'Ny sida',
            'updated_by' => $this->user->id,
        ]);
        $this->assertDatabaseHas('i18n_definitions', [
            'locale' => 'sv',
            'content' => json_encode('Ny titel'),
        ]);

        $rendered = $this->actingAs($this->user)->get('/admin/pages/'.$page->id.'/edit');

        $rendered->assertOk();
        $rendered->assertViewHas('page.props.page.localizedContent.sv.boxes', fn (array $items) => count($items) === 1
            && $items[0]['name'] === 'Ingress'
            && $items[0]['header'] === 'Rubrik');
    }

    /** @test **/
    public function it_can_publish_a_page_through_admin_web_routes()
    {
        [$root, $template] = $this->ensurePageRootAndTemplate();

        RevisionTemplateField::factory()->create([
            'template_id' => $template->id,
            'name' => 'Titel',
            'key' => 'page_title',
            'group' => 'main_content',
            'type' => 'text',
            'translated' => true,
        ]);

        $page = Page::factory()->create([
            'name' => 'Publicera mig',
            'template_id' => $template->id,
            'parent_id' => $root->id,
            'published_version' => null,
        ]);

        $response = $this->actingAs($this->user)->post('/admin/pages/'.$page->id.'/publish', [
            'name' => 'Publicerad sida',
            'localizedContent' => [
                'sv' => [
                    'page_title' => 'Publicerad titel',
                ],
            ],
        ]);

        $response->assertRedirect('/admin/pages/'.$page->id.'/edit');
        $this->assertNotNull($page->fresh()->published_version);
    }

    /** @test **/
    public function it_renders_articles_index_through_inertia()
    {
        Article::factory()->create([
            'name' => 'Lansering',
        ]);

        $response = $this->actingAs($this->user)->get('/admin/articles?search=Lanser');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Articles/Index');
        $response->assertViewHas('page.props.filters.search', 'Lanser');
        $response->assertViewHas('page.props.articles.data', fn (array $items) => count($items) === 1
            && $items[0]['name'] === 'Lansering');
    }

    /** @test **/
    public function it_renders_smart_blocks_index_through_inertia()
    {
        SmartBlock::factory()->create([
            'name' => 'Find me block',
        ]);

        $response = $this->actingAs($this->user)->get('/admin/smart-blocks?search=Find&sort=-updated_at');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/SmartBlocks/Index');
        $response->assertViewHas('page.props.filters.search', 'Find');
        $response->assertViewHas('page.props.filters.sort', '-updated_at');
        $response->assertViewHas('page.props.smartBlocks.data', fn (array $items) => count($items) === 1
            && $items[0]['name'] === 'Find me block');
    }

    /** @test **/
    public function it_renders_smart_blocks_edit_through_inertia()
    {
        $blockType = BlockType::factory()->create([
            'name' => 'Text block',
            'component_name' => 'TextBlock',
            'active' => true,
        ]);
        $smartBlock = SmartBlock::factory()->create([
            'name' => 'Edit smart block',
        ]);
        $smartBlock->localizedContent = [
            'sv' => [
                'boxes' => [
                    [
                        'id' => 'box-1',
                        'name' => 'Ingress',
                        'hidden' => false,
                        'block_type' => [
                            'id' => $blockType->id,
                            'name' => $blockType->name,
                            'component_name' => $blockType->component_name,
                            'has_children' => false,
                            'options' => [
                                'visible_for' => [],
                                'hidden_for' => [],
                                'recommended_for' => [],
                            ],
                        ],
                        'header' => 'Rubrik',
                        'subheader' => 'Underrubrik',
                        'columns' => 2,
                        'body' => '<p>Hej</p>',
                        'buttons' => [],
                    ],
                ],
            ],
            'en' => [
                'boxes' => [],
            ],
        ];
        $smartBlock->save();

        $response = $this->actingAs($this->user)->get('/admin/smart-blocks/'.$smartBlock->id.'/edit');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/SmartBlocks/Edit');
        $response->assertViewHas('page.props.smartBlock.name', 'Edit smart block');
        $response->assertViewHas('page.props.smartBlock.localizedContent.sv.boxes', fn (array $items) => count($items) === 1
            && $items[0]['name'] === 'Ingress'
            && $items[0]['header'] === 'Rubrik');
        $response->assertViewHas('page.props.blockTypes', fn (array $items) => collect($items)->contains(
            fn (array $item) => $item['componentName'] === 'TextBlock'
        ));
    }

    /** @test **/
    public function it_renders_block_types_index_through_inertia()
    {
        BlockType::factory()->create([
            'name' => 'Hero block',
            'component_name' => 'HeroBlock',
            'active' => true,
        ]);

        $response = $this->actingAs($this->user)->get('/admin/block-types?sort=name');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/BlockTypes/Index');
        $response->assertViewHas('page.props.filters.sort', 'name');
        $response->assertViewHas('page.props.blockTypes', fn (array $items) => count($items) >= 1
            && collect($items)->contains(fn (array $item) => $item['name'] === 'Hero block'));
        $response->assertViewHas('page.props.templates', fn (array $items) => is_array($items));
    }

    /** @test **/
    public function it_renders_block_types_edit_through_inertia()
    {
        $blockType = BlockType::factory()->create([
            'name' => 'Promo block',
            'component_name' => 'PromoBlock',
            'active' => true,
            'has_children' => true,
            'options' => [
                'visible_for' => ['landing-page'],
                'hidden_for' => [],
                'recommended_for' => ['article'],
            ],
        ]);

        $response = $this->actingAs($this->user)->get('/admin/block-types/'.$blockType->id.'/edit');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/BlockTypes/Index');
        $response->assertViewHas('page.props.editingBlockType.name', 'Promo block');
        $response->assertViewHas('page.props.editingBlockType.componentName', 'PromoBlock');
        $response->assertViewHas('page.props.editingBlockType.hasChildren', true);
    }

    /** @test **/
    public function it_renders_articles_edit_through_inertia()
    {
        $template = $this->ensureArticleTemplate();
        $article = Article::factory()->create([
            'name' => 'Edit Article',
            'template_id' => $template->id,
            'publishes_at' => '2043-02-02 15:00:00',
            'has_unpublished_time' => true,
            'unpublishes_at' => '2055-02-03 13:00:00',
        ]);

        $this->actingAs($this->user)->patch('/admin/articles/'.$article->id, [
            'name' => 'Edit Article',
            'publishes_at' => '2043-02-02T15:00:00.000Z',
            'has_unpublished_time' => true,
            'unpublishes_at' => '2055-02-03T13:00:00.000Z',
            'content' => [
                'title' => 'Rubrik',
                'preamble' => 'Ingress',
                'body' => 'Brodtext',
                'image' => null,
            ],
        ]);

        $response = $this->actingAs($this->user)->get('/admin/articles/'.$article->id.'/edit');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Articles/Edit');
        $response->assertViewHas('page.props.article.name', 'Edit Article');
        $response->assertViewHas('page.props.article.content.title', 'Rubrik');
        $response->assertViewHas('page.props.article.publishesAt', '2043-02-02T16:00');
        $response->assertViewHas('page.props.article.unpublishesAt', '2055-02-03T14:00');
        $response->assertViewHas('page.props.article.hasUnpublishedTime', true);
    }

    /** @test **/
    public function it_renders_contacts_index_through_inertia()
    {
        Contact::factory()->create([
            'name' => 'Britta Budget',
        ]);

        $response = $this->actingAs($this->user)->get('/admin/contacts?search=Budget&sort=name');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Contacts/Index');
        $response->assertViewHas('page.props.filters.search', 'Budget');
        $response->assertViewHas('page.props.filters.sort', 'name');
        $response->assertViewHas('page.props.contacts.data', fn (array $items) => count($items) === 1
            && $items[0]['name'] === 'Britta Budget');
    }

    /** @test **/
    public function it_renders_contacts_edit_through_inertia()
    {
        $contact = Contact::factory()->create([
            'name' => 'Edit Contact',
        ]);

        $response = $this->actingAs($this->user)->get('/admin/contacts/'.$contact->id.'/edit');

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Contacts/Edit');
        $response->assertViewHas('page.props.contact.name', 'Edit Contact');
    }

    /** @test **/
    public function it_renders_calendar_index_through_inertia()
    {
        $this->ensureEventTemplate();

        $event = Event::factory()->create([
            'start' => now()->startOfMonth()->addDays(4)->startOfDay(),
            'end' => now()->startOfMonth()->addDays(4)->startOfDay(),
        ]);
        $event->updateContent([
            'title' => 'Budgetmote',
            'description' => 'Mote om kvartalet',
            'location' => 'Konferensrum',
        ], 'sv');

        $response = $this->actingAs($this->user)->get('/admin/calendar?month='.now()->format('Y-m'));

        $response->assertOk();
        $response->assertViewHas('page.component', 'Admin/Calendar/Index');
        $response->assertViewHas('page.props.events', fn (array $items) => count($items) >= 1
            && collect($items)->contains(fn (array $item) => $item['id'] === $event->id && $item['title'] === 'Budgetmote'));
    }

    /** @test **/
    public function it_can_create_an_article_through_admin_web_routes()
    {
        $response = $this->actingAs($this->user)->post('/admin/articles', [
            'name' => 'Ny release',
        ]);

        $response->assertRedirect('/admin/articles');
        $response->assertSessionHas('status_action_label', 'Gå till nyheten');
        $response->assertSessionHas('status_action_href');
        $this->assertDatabaseHas('articles', [
            'name' => 'Ny release',
        ]);
    }

    /** @test **/
    public function it_can_create_a_smart_block_through_admin_web_routes()
    {
        $response = $this->actingAs($this->user)->post('/admin/smart-blocks', [
            'name' => 'Hero reusable block',
        ]);

        $response->assertRedirect('/admin/smart-blocks');
        $response->assertSessionHas('status_action_label', 'Gå till blocket');
        $response->assertSessionHas('status_action_href');
        $this->assertDatabaseHas('smart_blocks', [
            'name' => 'Hero reusable block',
        ]);
    }

    /** @test **/
    public function it_can_create_a_block_type_through_admin_web_routes()
    {
        $response = $this->actingAs($this->user)->post('/admin/block-types', [
            'name' => 'Call to action',
            'component_name' => 'CallToActionBlock',
            'has_children' => true,
        ]);

        $blockType = BlockType::query()->where('component_name', 'CallToActionBlock')->firstOrFail();

        $response->assertRedirect('/admin/block-types/'.$blockType->id.'/edit');
        $this->assertDatabaseHas('block_types', [
            'id' => $blockType->id,
            'name' => 'Call to action',
            'component_name' => 'CallToActionBlock',
            'type' => 'block',
            'has_children' => true,
            'active' => true,
        ]);
    }

    /** @test **/
    public function it_can_create_a_contact_through_admin_web_routes()
    {
        $response = $this->actingAs($this->user)->post('/admin/contacts', [
            'name' => 'Anna Admin',
        ]);

        $response->assertRedirect('/admin/contacts');
        $this->assertDatabaseHas('contacts', [
            'name' => 'Anna Admin',
        ]);
    }

    /** @test **/
    public function it_can_create_an_event_through_admin_web_routes()
    {
        $this->ensureEventTemplate();

        $response = $this->actingAs($this->user)->post('/admin/events', [
            'month' => now()->format('Y-m'),
            'date' => [
                'start' => now()->startOfDay()->toDateTimeString(),
                'end' => now()->addDay()->startOfDay()->toDateTimeString(),
            ],
            'start_time' => '08:00',
            'end_time' => '10:00',
            'full_day' => false,
            'daily_interval' => 7,
            'localizedContent' => [
                'sv' => [
                    'title' => 'Lansering',
                    'description' => 'Produktlansering',
                    'location' => 'Stockholm',
                ],
                'en' => [
                    'title' => 'Launch',
                    'description' => 'Product launch',
                    'location' => 'Stockholm',
                ],
            ],
        ]);

        $response->assertRedirect('/admin/calendar?month='.now()->format('Y-m'));
        $this->assertDatabaseHas('events', [
            'start_time' => '08:00',
            'end_time' => '10:00',
            'daily_interval' => 7,
        ]);
    }

    /** @test **/
    public function it_can_create_a_menu_item_through_admin_web_routes()
    {
        $menu = Menu::factory()->create();
        $page = Page::factory()->create();

        $response = $this->actingAs($this->user)->post('/admin/menus/'.$menu->id.'/items', [
            'item' => [
                'page_id' => $page->id,
                'type' => 'internal',
            ],
            'content' => [],
        ]);

        $response->assertRedirect('/admin/menus/'.$menu->id.'/edit');
        $this->assertDatabaseHas('menu_items', [
            'menu_id' => $menu->id,
            'page_id' => $page->id,
            'type' => 'internal',
        ]);
    }

    /** @test **/
    public function it_can_delete_an_article_through_admin_web_routes()
    {
        $article = Article::factory()->create();

        $response = $this->actingAs($this->user)->delete('/admin/articles/'.$article->id);

        $response->assertRedirect('/admin/articles');
        $this->assertDatabaseMissing('articles', [
            'id' => $article->id,
        ]);
    }

    /** @test **/
    public function it_can_delete_a_smart_block_through_admin_web_routes()
    {
        $smartBlock = SmartBlock::factory()->create();

        $response = $this->actingAs($this->user)->delete('/admin/smart-blocks/'.$smartBlock->id);

        $response->assertRedirect('/admin/smart-blocks');
        $this->assertDatabaseMissing('smart_blocks', [
            'id' => $smartBlock->id,
        ]);
    }

    /** @test **/
    public function it_can_delete_a_block_type_through_admin_web_routes()
    {
        $blockType = BlockType::factory()->create([
            'active' => true,
        ]);

        $response = $this->actingAs($this->user)->delete('/admin/block-types/'.$blockType->id);

        $response->assertRedirect('/admin/block-types');
        $this->assertDatabaseMissing('block_types', [
            'id' => $blockType->id,
        ]);
    }

    /** @test **/
    public function it_can_update_a_contact_through_admin_web_routes()
    {
        $contact = Contact::factory()->create([
            'name' => 'Old Contact',
        ]);

        $response = $this->actingAs($this->user)->patch('/admin/contacts/'.$contact->id, [
            'name' => 'New Contact',
            'email' => 'contact@example.test',
            'phone' => '0701234567',
            'mobile' => '0707654321',
            'published' => true,
            'content' => [
                'enabled_locales' => ['sv'],
            ],
            'localizedContent' => [
                'sv' => [
                    'position' => 'VD',
                    'body' => '<p>Hej</p>',
                ],
            ],
            'tags' => ['Ledning'],
        ]);

        $response->assertRedirect('/admin/contacts/'.$contact->id.'/edit');
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'New Contact',
            'email' => 'contact@example.test',
            'phone' => '0701234567',
            'mobile' => '0707654321',
            'published' => true,
        ]);
        $this->assertDatabaseHas('tags', [
            'name' => json_encode(['en' => 'Ledning']),
            'type' => 'contacts',
        ]);
    }

    /** @test **/
    public function it_can_update_an_article_through_admin_web_routes()
    {
        $template = $this->ensureArticleTemplate();
        $article = Article::factory()->create([
            'name' => 'Old Article',
            'template_id' => $template->id,
        ]);

        $response = $this->actingAs($this->user)->patch('/admin/articles/'.$article->id, [
            'name' => 'New Article',
            'publishes_at' => '2043-02-02T15:00:00.000Z',
            'has_unpublished_time' => true,
            'unpublishes_at' => '2055-02-03T13:00:00.000Z',
            'content' => [
                'title' => 'Ny titel',
                'preamble' => 'Ny ingress',
                'body' => 'Ny brodtext',
                'image' => null,
            ],
        ]);

        $response->assertRedirect('/admin/articles/'.$article->id.'/edit');
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'name' => 'New Article',
            'publishes_at' => '2043-02-02 15:00:00',
            'has_unpublished_time' => true,
            'unpublishes_at' => '2055-02-03 13:00:00',
        ]);
        $this->assertSame(
            'Ny titel',
            data_get($article->fresh()->getFieldContent($article->fresh()->revision)->toArray(), 'title')
        );
        $this->assertSame(
            'Ny ingress',
            data_get($article->fresh()->getFieldContent($article->fresh()->revision)->toArray(), 'preamble')
        );
        $this->assertSame(
            'Ny brodtext',
            data_get($article->fresh()->getFieldContent($article->fresh()->revision)->toArray(), 'body')
        );
    }

    /** @test **/
    public function it_can_update_a_smart_block_through_admin_web_routes()
    {
        $cardBlockType = BlockType::factory()->create([
            'name' => 'Cards',
            'component_name' => 'CardBlock',
            'active' => true,
            'has_children' => true,
        ]);
        $textBlockType = BlockType::factory()->create([
            'name' => 'Text',
            'component_name' => 'TextBlock',
            'active' => true,
            'has_children' => false,
        ]);
        $smartBlock = SmartBlock::factory()->create([
            'name' => 'Old smart block',
        ]);
        $image = Image::factory()->create([
            'alt_text' => 'Kortbild',
        ]);
        $video = Video::factory()->create([
            'alt_text' => 'Kortvideo',
        ]);
        $file = File::factory()->create([
            'caption' => 'Kortfil',
        ]);

        $response = $this->actingAs($this->user)->patch('/admin/smart-blocks/'.$smartBlock->id, [
            'name' => 'New smart block',
            'localizedContent' => [
                'sv' => [
                    'boxes' => [
                        [
                            'id' => 'card-1',
                            'name' => 'Kortblock',
                            'hidden' => false,
                            'block_type' => [
                                'id' => $cardBlockType->id,
                                'name' => $cardBlockType->name,
                                'component_name' => $cardBlockType->component_name,
                                'has_children' => true,
                                'options' => [
                                    'visible_for' => [],
                                    'hidden_for' => [],
                                    'recommended_for' => [],
                                ],
                            ],
                            'size' => 'large',
                            'header' => 'Blockrubrik',
                            'subheader' => 'Blockunderrubrik',
                            'hasButton' => true,
                            'button' => [
                                'text' => 'Ladda ner',
                                'linkType' => 'file',
                                'page_id' => null,
                                'url' => '',
                                'file' => $this->parsedFile($file),
                            ],
                            'children' => [
                                [
                                    'id' => 'child-1',
                                    'name' => 'Kort 1',
                                    'header' => 'Barnrubrik',
                                    'subheader' => 'Barnunderrubrik',
                                    'body' => '<p>Body</p>',
                                    'hidden' => false,
                                    'hasImage' => true,
                                    'image' => $this->parsedImage($image),
                                    'hasVideo' => true,
                                    'video' => $this->parsedVideo($video),
                                    'buttons' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                'en' => [
                    'boxes' => [
                        [
                            'id' => 'text-1',
                            'name' => 'English block',
                            'hidden' => false,
                            'block_type' => [
                                'id' => $textBlockType->id,
                                'name' => $textBlockType->name,
                                'component_name' => $textBlockType->component_name,
                                'has_children' => false,
                                'options' => [
                                    'visible_for' => [],
                                    'hidden_for' => [],
                                    'recommended_for' => [],
                                ],
                            ],
                            'header' => 'English heading',
                            'subheader' => '',
                            'columns' => 1,
                            'body' => '<p>Hello</p>',
                            'buttons' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect('/admin/smart-blocks/'.$smartBlock->id.'/edit');
        $this->assertDatabaseHas('smart_blocks', [
            'id' => $smartBlock->id,
            'name' => 'New smart block',
        ]);

        $rendered = $this->actingAs($this->user)->get('/admin/smart-blocks/'.$smartBlock->id.'/edit');

        $rendered->assertOk();
        $rendered->assertViewHas('page.component', 'Admin/SmartBlocks/Edit');
        $rendered->assertViewHas('page.props.smartBlock.localizedContent.sv.boxes', fn (array $items) => count($items) === 1
            && data_get($items, '0.children.0.image.id') === $image->id
            && data_get($items, '0.children.0.video.id') === $video->id
            && data_get($items, '0.button.file.id') === $file->id);
        $rendered->assertViewHas('page.props.smartBlock.localizedContent.en.boxes', fn (array $items) => count($items) === 1
            && $items[0]['name'] === 'English block'
            && $items[0]['header'] === 'English heading');
    }

    /** @test **/
    public function it_can_update_a_block_type_through_admin_web_routes()
    {
        $blockType = BlockType::factory()->create([
            'name' => 'Old block',
            'component_name' => 'OldBlock',
            'active' => true,
        ]);

        $response = $this->actingAs($this->user)->patch('/admin/block-types/'.$blockType->id, [
            'name' => 'Updated block',
            'component_name' => 'UpdatedBlock',
            'base_64_svg' => 'PHN2Zz48L3N2Zz4=',
            'has_children' => true,
            'options' => [
                'visible_for' => ['landing-page'],
                'recommended_for' => ['article'],
                'hidden_for' => ['standard-page'],
            ],
        ]);

        $response->assertRedirect('/admin/block-types/'.$blockType->id.'/edit');
        $this->assertDatabaseHas('block_types', [
            'id' => $blockType->id,
            'name' => 'Updated block',
            'component_name' => 'UpdatedBlock',
            'base_64_svg' => 'PHN2Zz48L3N2Zz4=',
            'has_children' => true,
        ]);
    }

    /** @test **/
    public function it_can_update_an_event_through_admin_web_routes()
    {
        $this->ensureEventTemplate();

        $event = Event::factory()->create([
            'start' => now()->startOfDay(),
            'end' => now()->startOfDay(),
        ]);
        $event->updateContent([
            'title' => 'Gammal titel',
            'description' => 'Gammal text',
            'location' => 'Gammal plats',
        ], 'sv');

        $response = $this->actingAs($this->user)->patch('/admin/events/'.$event->id, [
            'month' => now()->format('Y-m'),
            'date' => [
                'start' => now()->addDay()->startOfDay()->toDateTimeString(),
                'end' => now()->addDays(2)->startOfDay()->toDateTimeString(),
            ],
            'start_time' => '09:00',
            'end_time' => '11:00',
            'full_day' => false,
            'daily_interval' => 14,
            'localizedContent' => [
                'sv' => [
                    'title' => 'Ny titel',
                    'description' => 'Ny text',
                    'location' => 'Ny plats',
                ],
                'en' => [
                    'title' => 'New title',
                    'description' => 'New text',
                    'location' => 'New location',
                ],
            ],
        ]);

        $response->assertRedirect('/admin/calendar?month='.now()->format('Y-m'));
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'daily_interval' => 14,
        ]);
        $this->assertSame('Ny titel', data_get($event->fresh()->getSimpleFieldContent($event->fresh()->revision, 'sv')->toArray(), 'title'));
    }

    /** @test **/
    public function it_can_update_a_menu_item_through_admin_web_routes()
    {
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);

        $template = RevisionTemplate::factory()->create([
            'slug' => 'menu-item',
        ]);
        RevisionTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'title',
            'type' => 'text',
            'translated' => false,
        ]);
        RevisionTemplateField::factory()->create([
            'template_id' => $template->id,
            'key' => 'external_url',
            'type' => 'text',
            'translated' => false,
        ]);

        $item = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
            'type' => 'external',
        ]);

        $response = $this->actingAs($this->user)->patch('/admin/menu-items/'.$item->id, [
            'item' => [
                'type' => 'external',
            ],
            'content' => [
                'title' => 'Ny extern länk',
                'external_url' => 'https://example.test',
            ],
        ]);

        $response->assertRedirect('/admin/menus/'.$menu->id.'/edit');
        $this->assertSame('external', $item->fresh()->type);
        $this->assertSame(
            'Ny extern länk',
            data_get($item->fresh()->getFieldContent($item->fresh()->revision)->toArray(), 'title')
        );
    }

    /** @test **/
    public function it_can_delete_a_contact_through_admin_web_routes()
    {
        $contact = Contact::factory()->create();

        $response = $this->actingAs($this->user)->delete('/admin/contacts/'.$contact->id);

        $response->assertRedirect('/admin/contacts');
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    /** @test **/
    public function it_can_delete_an_event_through_admin_web_routes()
    {
        $this->ensureEventTemplate();

        $event = Event::factory()->create();

        $response = $this->actingAs($this->user)->delete('/admin/events/'.$event->id, [
            'month' => now()->format('Y-m'),
        ]);

        $response->assertRedirect('/admin/calendar?month='.now()->format('Y-m'));
        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    /** @test **/
    public function it_can_delete_a_menu_item_through_admin_web_routes()
    {
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $item = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
        ]);

        $response = $this->actingAs($this->user)->delete('/admin/menu-items/'.$item->id);

        $response->assertRedirect('/admin/menus/'.$menu->id.'/edit');
        $this->assertDatabaseMissing('menu_items', [
            'id' => $item->id,
        ]);
    }

    /** @test **/
    public function it_can_create_a_page_through_admin_web_routes()
    {
        [$root, $template] = $this->ensurePageRootAndTemplate();

        $response = $this->actingAs($this->user)->post('/admin/pages', [
            'name' => 'Kontakt',
            'template_id' => $template->id,
        ]);

        $response->assertRedirect('/admin/pages');
        $this->assertDatabaseHas('pages', [
            'name' => 'Kontakt',
            'template_id' => $template->id,
            'parent_id' => $root->id,
        ]);
    }

    /** @test **/
    public function it_can_clone_a_page_through_admin_web_routes()
    {
        [$root, $template] = $this->ensurePageRootAndTemplate();

        $page = Page::factory()->create([
            'name' => 'Tjanster',
            'template_id' => $template->id,
            'parent_id' => $root->id,
        ]);

        $response = $this->actingAs($this->user)->post('/admin/pages/'.$page->id.'/clone');

        $response->assertRedirect('/admin/pages');
        $this->assertDatabaseHas('pages', [
            'name' => 'Kopia av Tjanster',
            'template_id' => $template->id,
            'parent_id' => $root->id,
        ]);
    }

    /** @test **/
    public function it_can_delete_a_leaf_page_through_admin_web_routes()
    {
        [$root, $template] = $this->ensurePageRootAndTemplate();

        $page = Page::factory()->create([
            'template_id' => $template->id,
            'parent_id' => $root->id,
        ]);

        $response = $this->actingAs($this->user)->delete('/admin/pages/'.$page->id);

        $response->assertRedirect('/admin/pages');
        $this->assertDatabaseMissing('pages', [
            'id' => $page->id,
        ]);
    }

    /** @test **/
    public function it_can_update_the_page_tree_through_admin_web_routes()
    {
        [$root, $template] = $this->ensurePageRootAndTemplate();

        $first = Page::factory()->create([
            'name' => 'Forst',
            'template_id' => $template->id,
            'parent_id' => $root->id,
            'sortindex' => 0,
        ]);

        $second = Page::factory()->create([
            'name' => 'Andra',
            'template_id' => $template->id,
            'parent_id' => $root->id,
            'sortindex' => 10,
        ]);

        $response = $this->actingAs($this->user)->patch('/admin/pages-tree', [
            'tree' => [
                [
                    'id' => $second->id,
                    'children' => [],
                ],
                [
                    'id' => $first->id,
                    'children' => [],
                ],
            ],
        ]);

        $response->assertRedirect('/admin/pages');
        $this->assertLessThan($first->fresh()->_lft, $second->fresh()->_lft);
    }

    /** @test **/
    public function it_can_update_the_menu_tree_through_admin_web_routes()
    {
        $menu = Menu::factory()->create();
        $root = MenuItem::factory()->create([
            'menu_id' => $menu->id,
        ]);
        $first = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
            'sortindex' => 0,
        ]);
        $second = MenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
            'sortindex' => 10,
        ]);

        $response = $this->actingAs($this->user)->patch('/admin/menus/'.$menu->id.'/items/tree', [
            'tree' => [
                [
                    'id' => $second->id,
                    'children' => [
                        [
                            'id' => $first->id,
                            'children' => [],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect('/admin/menus/'.$menu->id.'/edit');
        $this->assertSame($second->id, $first->fresh()->parent_id);
    }

    private function ensurePageRootAndTemplate(): array
    {
        $template = new RevisionTemplate();
        $template->forceFill([
            'name' => 'Standardsida '.uniqid(),
            'slug' => 'standard-page-'.uniqid(),
            'type' => 'page',
        ])->save();

        $root = Page::query()
            ->where('name', 'root')
            ->whereNull('parent_id')
            ->first();

        if ($root === null) {
            $root = new Page();
            $root->forceFill([
                'name' => 'root',
                'parent_id' => null,
                'template_id' => $template->id,
            ])->save();
            $root = Page::query()->where('name', 'root')->whereNull('parent_id')->firstOrFail();
        }

        if ((int) $root->template_id !== (int) $template->id) {
            $root->template_id = $template->id;
            $root->save();
        }

        return [$root, $template];
    }

    private function ensureArticleTemplate(): RevisionTemplate
    {
        $template = RevisionTemplate::query()
            ->where('slug', 'article')
            ->first();

        if ($template === null) {
            $template = new RevisionTemplate();
            $template->forceFill([
                'name' => 'Nyhet',
                'slug' => 'article',
                'type' => 'article',
            ])->save();

            $template = RevisionTemplate::query()
                ->where('slug', 'article')
                ->firstOrFail();
        }

        foreach ([
            ['key' => 'title', 'name' => 'Titel', 'type' => 'text', 'translated' => true, 'group' => 'article', 'sort_index' => 10],
            ['key' => 'image', 'name' => 'image', 'type' => 'image', 'translated' => false, 'group' => 'article', 'sort_index' => 20],
            ['key' => 'preamble', 'name' => 'Ingress', 'type' => 'textarea', 'translated' => true, 'group' => 'article', 'sort_index' => 30],
            ['key' => 'body', 'name' => 'Innehåll', 'type' => 'html', 'translated' => true, 'group' => 'article', 'sort_index' => 40],
        ] as $field) {
            $existingField = RevisionTemplateField::query()
                ->where('template_id', $template->id)
                ->where('key', $field['key'])
                ->first();

            if ($existingField !== null) {
                continue;
            }

            $templateField = new RevisionTemplateField();
            $templateField->forceFill([
                'template_id' => $template->id,
                'name' => $field['name'],
                'key' => $field['key'],
                'type' => $field['type'],
                'translated' => $field['translated'],
                'group' => $field['group'],
                'repeater' => false,
                'sort_index' => $field['sort_index'],
            ])->save();
        }

        return $template;
    }

    private function ensureEventTemplate(): void
    {
        $template = RevisionTemplate::query()
            ->where('slug', 'event-item')
            ->first();

        if ($template === null) {
            $template = new RevisionTemplate();
            $template->forceFill([
                'name' => 'Event item',
                'slug' => 'event-item',
                'type' => 'default',
            ])->save();

            $template = RevisionTemplate::query()
                ->where('slug', 'event-item')
                ->firstOrFail();
        }

        foreach ([
            ['key' => 'title', 'translated' => true],
            ['key' => 'description', 'translated' => true],
            ['key' => 'location', 'translated' => true],
        ] as $field) {
            $existingField = RevisionTemplateField::query()
                ->where('template_id', $template->id)
                ->where('key', $field['key'])
                ->first();

            if ($existingField !== null) {
                continue;
            }

            $templateField = new RevisionTemplateField();
            $templateField->forceFill([
                'template_id' => $template->id,
                'name' => $field['key'],
                'key' => $field['key'],
                'type' => 'text',
                'translated' => $field['translated'],
            ])->save();
        }
    }

    private function parsedImage(Image $image): array
    {
        $metaImage = RevisionMeta::make([
            'meta_value' => [$image->id],
        ]);

        return ImageGetter::get($metaImage);
    }

    private function parsedVideo(Video $video): array
    {
        $metaVideo = RevisionMeta::make([
            'meta_value' => [$video->id],
        ]);

        return VideoGetter::get($metaVideo);
    }

    private function parsedFile(File $file): array
    {
        $metaFile = RevisionMeta::make([
            'meta_value' => [$file->id],
        ]);

        return FileGetter::get($metaFile);
    }
}
