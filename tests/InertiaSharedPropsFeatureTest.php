<?php

namespace Karabin\Fabriq\Tests;

use Karabin\Fabriq\Models\Image;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InertiaSharedPropsFeatureTest extends AdminUserTestCase
{
    public function test_admin_pages_share_runtime_auth_locale_and_flash_props(): void
    {
        config([
            'app.name' => 'Fabriq Package Test',
            'fabriq.ws_prefix' => 'fabriq_test_ws',
            'session.driver' => 'array',
            'fabriq.modules' => [
                [
                    'title' => 'Dashboard',
                    'enabled' => true,
                    'roles' => ['admin'],
                    'icon' => 'DashboardIcon',
                    'route' => 'home.index',
                ],
                [
                    'title' => 'Kontakter',
                    'enabled' => true,
                    'roles' => ['editor'],
                    'icon' => 'UsersCrownIcon',
                    'route' => 'contacts.index',
                ],
                [
                    'title' => 'Videos',
                    'enabled' => false,
                    'roles' => ['admin'],
                    'icon' => 'CameraMovieIcon',
                    'route' => 'videos.index',
                ],
            ],
        ]);

        DB::table('i18n_locales')->insert([
            [
                'name' => 'Swedish',
                'native' => 'Svenska',
                'regional' => 'sv_SE',
                'iso_code' => 'sv',
                'enabled' => true,
                'sort_index' => 1,
            ],
            [
                'name' => 'English',
                'native' => 'English',
                'regional' => 'en_US',
                'iso_code' => 'en',
                'enabled' => true,
                'sort_index' => 2,
            ],
        ]);

        Cache::forget('locales');

        $this->user->assignRole('admin');

        $image = Image::factory()->create();
        $image->addMediaFromString('avatar image')->toMediaCollection('profile_image');

        $this->user->forceFill([
            'image_id' => $image->id,
        ])->save();

        $response = $this->actingAs($this->user)
            ->withSession([
                'status' => 'Sidan sparades.',
                'status_action_label' => 'Öppna sidan',
                'status_action_href' => '/admin/pages/1/edit',
            ])
            ->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('page.props.auth.user.id', $this->user->id);
        $response->assertViewHas('page.props.auth.user.email', $this->user->email);
        $response->assertViewHas('page.props.auth.user.roles', fn (array $roles) => is_array($roles));
        $response->assertViewHas('page.props.auth.user.unread_notifications_count', 0);
        $response->assertViewHas('page.props.auth.user.image.id', $image->id);
        $response->assertViewHas('page.props.auth.user.image.thumbSrc', fn (?string $thumbSrc) => is_string($thumbSrc) && $thumbSrc !== '');
        $response->assertViewHas('page.props.fabriq.appName', 'Fabriq Package Test');
        $response->assertViewHas('page.props.fabriq.modules', [
            [
                'label' => 'Dashboard',
                'href' => '/admin/dashboard',
                'icon' => 'DashboardIcon',
            ],
        ]);
        $response->assertViewHas('page.props.fabriq.wsPrefix', 'fabriq_test_ws');
        $response->assertViewHas('page.props.fabriq.supportedLocales');
        $response->assertViewHas('page.props.flash.status', 'Sidan sparades.');
        $response->assertViewHas('page.props.flash.status_action_label', 'Öppna sidan');
        $response->assertViewHas('page.props.flash.status_action_href', '/admin/pages/1/edit');
    }
}
