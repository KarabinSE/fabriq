<?php

use App\Models\User;
use Illuminate\Support\Str;
use Karabin\Fabriq\Jobs\GenerateResponsiveImagesJob;
use Karabin\Fabriq\Models\Article;
use Karabin\Fabriq\Models\BlockType;
use Karabin\Fabriq\Models\Comment;
use Karabin\Fabriq\Models\Contact;
use Karabin\Fabriq\Models\Event;
use Karabin\Fabriq\Models\File;
use Karabin\Fabriq\Models\I18nDefinition;
use Karabin\Fabriq\Models\Image;
use Karabin\Fabriq\Models\Locale;
use Karabin\Fabriq\Models\Media;
use Karabin\Fabriq\Models\Menu;
use Karabin\Fabriq\Models\MenuItem;
use Karabin\Fabriq\Models\Notification;
use Karabin\Fabriq\Models\Page;
use Karabin\Fabriq\Models\Role;
use Karabin\Fabriq\Models\Slug;
use Karabin\Fabriq\Models\SmartBlock;
use Karabin\Fabriq\Models\Tag;
use Karabin\Fabriq\Models\Video;
use Karabin\Fabriq\Services\MediaPathGenerator;
use Laravel\Fortify\Features;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;

return [
    /**
     * Modules
     * These are menu items in the sidebar menu
     */
    'modules' => [
        [
            'title' => 'Dashboard',
            'enabled' => env('FABRIQ_ANALYTICS', true),
            'roles' => ['admin'],
            'icon' => 'DashboardIcon',
            'route' => 'home.index',
        ],
        [
            'title' => 'Sidor',
            'enabled' => env('FABRIQ_PAGES', true),
            'roles' => ['admin'],
            'icon' => 'BrowsersIcon',
            'route' => 'pages.index',
        ],
        [
            'title' => 'Smarta block',
            'route' => 'smartBlocks.index',
            'enabled' => env('FABRIQ_SMART_BLOCKS', true),
            'icon' => 'BrushFineIcon',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Nyheter',
            'route' => 'articles.index',
            'enabled' => env('FABRIQ_ARTICLES', false),
            'icon' => 'NewspaperIcon',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Kontakter',
            'route' => 'contacts.index',
            'enabled' => env('FABRIQ_CONTACTS', true),
            'icon' => 'UsersCrownIcon',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Kalender',
            'route' => 'calendar.index',
            'enabled' => env('FABRIQ_EVENTS', false),
            'icon' => 'CalendarIcon',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Användare',
            'route' => 'users.index',
            'enabled' => env('FABRIQ_USERS', true),
            'icon' => 'UsersGearIcon',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Menyer',
            'route' => 'menus.index',
            'enabled' => env('FABRIQ_MENUS', true),
            'icon' => 'ListTreeIcon',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Bilder',
            'route' => 'images.index',
            'enabled' => env('FABRIQ_IMAGES', true),
            'icon' => 'ImagesIcon',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Videos',
            'route' => 'videos.index',
            'enabled' => env('FABRIQ_VIDEOS', true),
            'icon' => 'CameraMovieIcon',
            'roles' => ['admin'],
        ],
        [
            'title' => 'Filer',
            'route' => 'files.index',
            'enabled' => env('FABRIQ_FILES', true),
            'icon' => 'FilesIcon',
            'roles' => ['admin'],
        ],
    ],
    'front_end_domain' => env('FABRIQ_FRONT_END_DOMAIN', 'http://localhost:3000'),
    'redirect_to_admin' => env('FABRIQ_REDIRECT_TO_ADMIN', true),
    'bucket_prefix' => env('BUCKET_PREFIX', 'fabriq-dev'),
    'enable_webp' => env('FABRIQ_ENABLE_WEBP', false),
    'enable_remote_image_processing' => env('FABRIQ_REMOTE_IMAGE_PROCESSING', false),
    'remote_image_processing_url' => env('FABRIQ_REMOTE_IMAGE_PROCESSING_URL', 'https://media-cruncher.ikoncept.io'),
    'remote_image_processing_api_key' => env('FABRIQ_REMOTE_IMAGE_PROCESSING_KEY', ''),
    'aws_lambda_access_key' => env('AWS_LAMBDA_ACCESS_KEY_ID'),
    'aws_lambda_secret_key' => env('AWS_LAMBDA_SECRET_ACCESS_KEY'),

    'ws_prefix' => env('IKONCEPT_WS_IDENTIFIER', Str::slug(env('APP_NAME', 'laravel'), '_').'_ws'),

    /**
     * Model mapping
     */
    'models' => [
        'article' => Article::class,
        'blockType' => BlockType::class,
        'comment' => Comment::class,
        'contact' => Contact::class,
        'event' => Event::class,
        'file' => File::class,
        'i18nDefinition' => I18nDefinition::class,
        'image' => Image::class,
        'locale' => Locale::class,
        'media' => Media::class,
        'menu' => Menu::class,
        'menuItem' => MenuItem::class,
        'notification' => Notification::class,
        'page' => Page::class,
        'role' => Role::class,
        'slug' => Slug::class,
        'smartBlock' => SmartBlock::class,
        'tag' => Tag::class,
        'user' => User::class,
        'video' => Video::class,
    ],
    'media-library' => [
        'max_file_size' => 1024 * 1024 * 500, // 500 MB,
        'jobs' => [
            'perform_conversions' => PerformConversionsJob::class,
            'generate_responsive_images' => GenerateResponsiveImagesJob::class,
        ],
        'path_generator' => MediaPathGenerator::class,
        'remote' => [
            'extra_headers' => [
                'CacheControl' => 'max-age=604800',
                'ACL' => 'public-read',
            ],
        ],
    ],

    'fortify' => [
        'features' => [
            Features::resetPasswords(),
            Features::updateProfileInformation(),
            Features::updatePasswords(),
        ],
    ],
    'ui' => [
        'large_block_picker' => false,
    ],

    'webhooks' => [
        'enabled' => env('FABRIQ_WEBHOOK_ENABLED', true),
        'secret' => env('FABRIQ_WEBHOOK_SECRET', 'very_secret'),
        'endpoint' => env('FABRIQ_WEBHOOK_ENDPOINT'),
    ],
];
