import ArticlesEdit from '@fabriq/articles/Edit.vue'
import ArticlesIndex from '@fabriq/articles/Index.vue'
import BlockTypesIndex from '@fabriq/blockTypes/Index.vue'
import CalendarIndex from '@fabriq/calendar/Index.vue'
import ContactEdit from '@fabriq/contacts/Edit.vue'
import ContactIndex from '@fabriq/contacts/Index.vue'
import Dashboard from '@fabriq/dashboard/Dashboard.vue'
import NotFound from '@fabriq/errors/NotFound.vue'
import FilesIndex from '@fabriq/files/Index.vue'
import Home from '@fabriq/Home.vue'
import ImagesIndex from '@fabriq/images/Index.vue'
import MenusEdit from '@fabriq/menus/Edit.vue'
import MenusIndex from '@fabriq/menus/Index.vue'
import BroadcastMiddleware from '@fabriq/middleware/broadcast-middleware'
import RolesMiddleware from '@fabriq/middleware/roles-middleware.js'
import NotificationIndex from '@fabriq/notifications/Index.vue'
import PagesEdit from '@fabriq/pages/Edit.vue'
import PagesIndex from '@fabriq/pages/Index.vue'
import SmartBlocksEdit from '@fabriq/smart-blocks/Edit.vue'
import SmartBlocksIndex from '@fabriq/smart-blocks/Index.vue'
import ProfileSettings from '@fabriq/user/ProfileSettings.vue'
import UsersEdit from '@fabriq/users/Edit.vue'
import UsersIndex from '@fabriq/users/Index.vue'
import VideosIndex from '@fabriq/videos/Index.vue'

export default [
    {
        path: '/admin',
        redirect: '/admin/dashboard'
    },
    {
        path: '/admin/home',
        redirect: '/admin/dashboard'
    },
    {
        path: '/admin/dashboard',
        name: 'home.index',
        component: Home,
        meta: {
            middleware: RolesMiddleware,
            roles: ['*', 'restaurant']
        }
    },
    {
        path: '/admin/profile/settings',
        name: 'profile.settings',
        component: ProfileSettings,
        meta: {
            middleware: RolesMiddleware,
            roles: ['*']
        }
    },
    {
        path: '/admin/dashboard',
        name: 'dashboard.index',
        component: Dashboard,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/pages',
        name: 'pages.index',
        component: PagesIndex,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/pages/:id/edit',
        name: 'pages.edit',
        component: PagesEdit,
        meta: {
            middleware: [RolesMiddleware, BroadcastMiddleware],
            roles: ['admin'],
            commentable: true,
            broadcastName: 'page'
        }
    },
    {
        path: '/admin/users',
        name: 'users.index',
        component: UsersIndex,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/users/:id/edit',
        name: 'users.edit',
        component: UsersEdit,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/notifications',
        name: 'notifications.index',
        component: NotificationIndex,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/menus',
        name: 'menus.index',
        component: MenusIndex,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/menus/:id/edit',
        name: 'menus.edit',
        component: MenusEdit,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/media/images',
        name: 'images.index',
        component: ImagesIndex,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/articles',
        name: 'articles.index',
        component: ArticlesIndex,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/articles/:id/edit',
        name: 'articles.edit',
        component: ArticlesEdit,
        meta: {
            middleware: [RolesMiddleware],
            roles: ['admin']
        }
    },
    {
        path: '/admin/calendar',
        name: 'calendar.index',
        component: CalendarIndex,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/contacts',
        name: 'contacts.index',
        component: ContactIndex,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/contacts/:id/edit',
        name: 'contacts.edit',
        component: ContactEdit,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/media/files',
        name: 'files.index',
        component: FilesIndex,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/media/videos',
        name: 'videos.index',
        component: VideosIndex,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/smart-blocks',
        name: 'smartBlocks.index',
        component: SmartBlocksIndex,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/smart-blocks/:id/edit',
        name: 'smartBlocks.edit',
        component: SmartBlocksEdit,
        meta: {
            middleware: RolesMiddleware,
            roles: ['admin']
        }
    },
    {
        path: '/admin/block-types',
        name: 'blockTypes.index',
        component: BlockTypesIndex,
        meta: {
            middleware: RolesMiddleware,
            roles: ['dev']
        }
    },
    {
        path: '/admin/block-types/:id/edit',
        name: 'blockTypes.edit',
        component: BlockTypesIndex,
        meta: {
            middleware: RolesMiddleware,
            roles: ['dev']
        }
    },
    {
        name: 'errors.404',
        path: '*',
        component: NotFound
    }
]
