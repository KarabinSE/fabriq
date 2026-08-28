type RouteParameters = {
login: never,
'login.store': never,
logout: never,
'password.request': never,
'password.email': never,
'password.reset': {
token: string | number,
},
'password.update': never,
'user-profile-information.update': never,
'user-password.update': never,
'password.confirm': never,
'password.confirm.store': never,
'password.confirmation': never,
'sanctum.csrf-cookie': never,
'pages.show': {
slug: string | number,
},
'menus.show': {
slug: string | number,
},
'news.index': never,
'news.show': {
slug: string | number,
},
'published-contacts.index': never,
'bust-cache.store': never,
'templates.index': never,
'menus.public.show': {
slug: string | number,
},
'menus.items.tree.index': {
id: string | number,
},
'menus.items.tree.update': {
id: string | number,
},
'model.count.show': {
model: string | number,
},
'uploads.images.store': never,
'uploads.files.store': never,
'uploads.videos.store': never,
'articles.index': never,
'articles.create': never,
'articles.store': never,
'articles.show': {
article: string | number,
},
'articles.edit': {
article: string | number,
},
'articles.update': {
article: string | number,
},
'articles.destroy': {
article: string | number,
},
'contacts.sort': never,
'contacts.index': never,
'contacts.create': never,
'contacts.store': never,
'contacts.show': {
contact: string | number,
},
'contacts.edit': {
contact: string | number,
},
'contacts.update': {
contact: string | number,
},
'contacts.destroy': {
contact: string | number,
},
'block-types.index': never,
'block-types.create': never,
'block-types.store': never,
'block-types.show': {
block_type: string | number,
},
'block-types.edit': {
block_type: string | number,
},
'block-types.update': {
block_type: string | number,
},
'block-types.destroy': {
block_type: string | number,
},
'comments.commentable.index': {
model: string | number,
id: string | number,
},
'comments.commentable.store': {
model: string | number,
id: string | number,
},
'comments.update': {
id: string | number,
},
'comments.destroy': {
id: string | number,
},
'events.index': never,
'events.create': never,
'events.store': never,
'events.show': {
event: string | number,
},
'events.edit': {
event: string | number,
},
'events.update': {
event: string | number,
},
'events.destroy': {
event: string | number,
},
'files.index': never,
'files.show': {
id: string | number,
},
'files.update': {
id: string | number,
},
'files.destroy': {
id: string | number,
},
'images.src-set.show': {
id: string | number,
},
'images.show.src-set': {
id: string | number,
},
'images.imageable.index': {
model: string | number,
id: string | number,
},
'images.imageable.store': {
id: string | number,
model: string | number,
},
'images.index': never,
'images.show': {
id: string | number,
},
'images.update': {
id: string | number,
},
'images.destroy': {
id: string | number,
},
'media.downloads.show': {
uuid: string | number,
},
'downloads.index': never,
'downloads.show': {
id: string | number,
},
'menus.index': never,
'menus.store': never,
'menus.show': {
id: string | number,
},
'menus.update': {
id: string | number,
},
'menus.destroy': {
id: string | number,
},
'menus.items.store': {
id: string | number,
},
'menu-items.show': {
id: string | number,
},
'menu-items.update': {
id: string | number,
},
'menu-items.destroy': {
id: string | number,
},
'pages-tree.index': never,
'pages-tree.update': never,
'pages.live.show': {
slug: string | number,
},
'pages.index': never,
'pages.store': never,
'pages.show': {
id: string | number,
},
'pages.update': {
id: string | number,
},
'pages.destroy': {
id: string | number,
},
'pages.clone.store': {
id: string | number,
},
'pages.publish.store': {
id: string | number,
},
'roles.index': never,
'smart-blocks.index': never,
'smart-blocks.create': never,
'smart-blocks.store': never,
'smart-blocks.show': {
smart_block: string | number,
},
'smart-blocks.edit': {
smart_block: string | number,
},
'smart-blocks.update': {
smart_block: string | number,
},
'smart-blocks.destroy': {
smart_block: string | number,
},
'tags.index': never,
'tags.store': never,
'users.index': never,
'users.create': never,
'users.store': never,
'users.show': {
user: string | number,
},
'users.edit': {
user: string | number,
},
'users.update': {
user: string | number,
},
'users.destroy': {
user: string | number,
},
'videos.index': never,
'videos.show': {
id: string | number,
},
'videos.update': {
id: string | number,
},
'videos.destroy': {
id: string | number,
},
'pages.paths.index': {
id: string | number,
},
'invitations.store': {
userId: string | number,
},
'invitations.destroy': {
userId: string | number,
},
'previews.store': never,
'previews.update': {
id: string | number,
},
'previews.show': {
uuid: string | number,
},
'notifications.ask-to-leave': {
userId: string | number,
},
'notifications.decline-to-leave': {
userId: string | number,
},
'user.notifications.index': never,
'user.notifications.update': {
id: string | number,
},
'user.index': never,
'user.update': never,
'user.self': never,
'user.image.store': never,
'user.image.destroy': never,
'user.send-email-verification.store': never,
'config.index': never,
'pages.show.preview': {
slug: string | number,
},
'permalink.redirect': {
hash: string | number,
locale?: string | number,
},
'invitation.accept': {
token: string | number,
},
'invitation.accept.store': {
token: string | number,
},
'admin.index': never,
'admin.any.index': {
any: string | number,
},
'verification.notice': never,
'verification.verify': {
id: string | number,
hash: string | number,
},
'verification.send': never,
'storage.local.upload': {
path: string | number,
},
};
export function hasRoute(name: string): name is keyof RouteParameters {
return Object.prototype.hasOwnProperty.call(routes, name);
}
export function route<T extends keyof RouteParameters>(name: T, parameters?: [RouteParameters[T]] extends [never] ? Record<string, never> : RouteParameters[T], absolute: boolean = true): string {
if (! Object.prototype.hasOwnProperty.call(routes, name)) {
    throw new Error(`Route "${name}" not found.`);
}

let url: string = '/' + routes[name];

if (parameters) {
    for (const [key, value] of Object.entries(parameters)) {
        url = url.replace(`{${key}}`, String(value));
    }
}

if (absolute) {
    url = window.location.origin + url;
}

return url;
}
const routes = {
    "login": "login",
    "login.store": "login",
    "logout": "logout",
    "password.request": "forgot-password",
    "password.email": "forgot-password",
    "password.reset": "reset-password/{token}",
    "password.update": "reset-password",
    "user-profile-information.update": "user/profile-information",
    "user-password.update": "user/password",
    "password.confirm": "user/confirm-password",
    "password.confirm.store": "user/confirm-password",
    "password.confirmation": "user/confirmed-password-status",
    "sanctum.csrf-cookie": "sanctum/csrf-cookie",
    "pages.show": "api/admin/pages/{id}",
    "menus.show": "api/admin/menus/{id}",
    "news.index": "api/news",
    "news.show": "api/news/{slug}",
    "published-contacts.index": "api/contacts",
    "bust-cache.store": "api/dev/bust-cache",
    "templates.index": "api/admin/templates",
    "menus.public.show": "api/admin/menus/{slug}/public",
    "menus.items.tree.index": "api/admin/menus/{id}/items/tree",
    "menus.items.tree.update": "api/admin/menus/{id}/items/tree",
    "model.count.show": "api/admin/{model}/count",
    "uploads.images.store": "api/admin/uploads/images",
    "uploads.files.store": "api/admin/uploads/files",
    "uploads.videos.store": "api/admin/uploads/videos",
    "articles.index": "api/admin/articles",
    "articles.create": "api/admin/articles/create",
    "articles.store": "api/admin/articles",
    "articles.show": "api/admin/articles/{article}",
    "articles.edit": "api/admin/articles/{article}/edit",
    "articles.update": "api/admin/articles/{article}",
    "articles.destroy": "api/admin/articles/{article}",
    "contacts.sort": "api/admin/contacts/sort-contacts",
    "contacts.index": "api/admin/contacts",
    "contacts.create": "api/admin/contacts/create",
    "contacts.store": "api/admin/contacts",
    "contacts.show": "api/admin/contacts/{contact}",
    "contacts.edit": "api/admin/contacts/{contact}/edit",
    "contacts.update": "api/admin/contacts/{contact}",
    "contacts.destroy": "api/admin/contacts/{contact}",
    "block-types.index": "api/admin/block-types",
    "block-types.create": "api/admin/block-types/create",
    "block-types.store": "api/admin/block-types",
    "block-types.show": "api/admin/block-types/{block_type}",
    "block-types.edit": "api/admin/block-types/{block_type}/edit",
    "block-types.update": "api/admin/block-types/{block_type}",
    "block-types.destroy": "api/admin/block-types/{block_type}",
    "comments.commentable.index": "api/admin/{model}/{id}/comments",
    "comments.commentable.store": "api/admin/{model}/{id}/comments",
    "comments.update": "api/admin/comments/{id}",
    "comments.destroy": "api/admin/comments/{id}",
    "events.index": "api/admin/events",
    "events.create": "api/admin/events/create",
    "events.store": "api/admin/events",
    "events.show": "api/admin/events/{event}",
    "events.edit": "api/admin/events/{event}/edit",
    "events.update": "api/admin/events/{event}",
    "events.destroy": "api/admin/events/{event}",
    "files.index": "api/admin/files",
    "files.show": "api/admin/files/{id}",
    "files.update": "api/admin/files/{id}",
    "files.destroy": "api/admin/files/{id}",
    "images.src-set.show": "api/admin/images/{id}/src-set",
    "images.show.src-set": "api/images/{id}/src-set",
    "images.imageable.index": "api/admin/{model}/{id}/images",
    "images.imageable.store": "api/admin/images/{id}/{model}",
    "images.index": "api/admin/images",
    "images.show": "api/admin/images/{id}",
    "images.update": "api/admin/images/{id}",
    "images.destroy": "api/admin/images/{id}",
    "media.downloads.show": "api/admin/media/downloads/{uuid}",
    "downloads.index": "api/admin/downloads",
    "downloads.show": "api/admin/downloads/{id}",
    "menus.index": "api/admin/menus",
    "menus.store": "api/admin/menus",
    "menus.update": "api/admin/menus/{id}",
    "menus.destroy": "api/admin/menus/{id}",
    "menus.items.store": "api/admin/menus/{id}/items",
    "menu-items.show": "api/admin/menu-items/{id}",
    "menu-items.update": "api/admin/menu-items/{id}",
    "menu-items.destroy": "api/admin/menu-items/{id}",
    "pages-tree.index": "api/admin/pages-tree",
    "pages-tree.update": "api/admin/pages-tree",
    "pages.live.show": "api/admin/pages/{slug}/live",
    "pages.index": "api/admin/pages",
    "pages.store": "api/admin/pages",
    "pages.update": "api/admin/pages/{id}",
    "pages.destroy": "api/admin/pages/{id}",
    "pages.clone.store": "api/admin/pages/{id}/clone",
    "pages.publish.store": "api/admin/pages/{id}/publish",
    "roles.index": "api/admin/roles",
    "smart-blocks.index": "api/admin/smart-blocks",
    "smart-blocks.create": "api/admin/smart-blocks/create",
    "smart-blocks.store": "api/admin/smart-blocks",
    "smart-blocks.show": "api/admin/smart-blocks/{smart_block}",
    "smart-blocks.edit": "api/admin/smart-blocks/{smart_block}/edit",
    "smart-blocks.update": "api/admin/smart-blocks/{smart_block}",
    "smart-blocks.destroy": "api/admin/smart-blocks/{smart_block}",
    "tags.index": "api/admin/tags",
    "tags.store": "api/admin/tags",
    "users.index": "api/admin/users",
    "users.create": "api/admin/users/create",
    "users.store": "api/admin/users",
    "users.show": "api/admin/users/{user}",
    "users.edit": "api/admin/users/{user}/edit",
    "users.update": "api/admin/users/{user}",
    "users.destroy": "api/admin/users/{user}",
    "videos.index": "api/admin/videos",
    "videos.show": "api/admin/videos/{id}",
    "videos.update": "api/admin/videos/{id}",
    "videos.destroy": "api/admin/videos/{id}",
    "pages.paths.index": "api/admin/pages/{id}/paths",
    "invitations.store": "api/admin/invitations/{userId}",
    "invitations.destroy": "api/admin/invitations/{userId}",
    "previews.store": "api/admin/previews",
    "previews.update": "api/admin/previews/{id}",
    "previews.show": "api/previews/{uuid}",
    "notifications.ask-to-leave": "api/notifications/ask-to-leave/{userId}",
    "notifications.decline-to-leave": "api/notifications/decline-to-leave/{userId}",
    "user.notifications.index": "api/user/notifications",
    "user.notifications.update": "api/user/notifications/{id}",
    "user.index": "api/user",
    "user.update": "api/user",
    "user.self": "api/user/self",
    "user.image.store": "api/user/image",
    "user.image.destroy": "api/user/image",
    "user.send-email-verification.store": "api/user/send-email-verification",
    "config.index": "api/config",
    "pages.show.preview": "api/pages/{slug}/preview",
    "permalink.redirect": "permalink/{hash}/{locale}",
    "invitation.accept": "invitations/accept/{token}",
    "invitation.accept.store": "invitations/accept/{token}",
    "admin.index": "admin",
    "admin.any.index": "admin/{any}",
    "verification.notice": "admin/email/verify",
    "verification.verify": "admin/email/verify/{id}/{hash}",
    "verification.send": "admin/email/verification-notification",
    "storage.local.upload": "storage/{path}"
}
