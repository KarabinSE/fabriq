<template>
    <div class="flex h-screen overflow-hidden antialiased bg-gray-50">
        <UiSidebar />
        <UiDesktopSidebar />
        <UiTopbar />
        <ModalsContainer />
        <ToastArea />

        <div class="flex flex-col flex-1 w-0 mt-12 overflow-hidden lg:mt-0">
            <main
                class="relative flex-1 overflow-y-auto focus:outline-none scrollbar-gutter-stable"
                tabindex="0"
            >
                <div class="py-6">
                    <div class="px-4 pb-24 mx-auto max-w-10xl sm:px-6 md:px-8">
                        <RouterView v-slot="{ Component }">
                            <Transition
                                mode="out-in"
                                name="fade"
                            >
                                <KeepAlive>
                                    <component
                                        :is="Component"
                                        :key="$route.fullPath"
                                    />
                                </KeepAlive>
                            </Transition>
                        </RouterView>
                    </div>
                </div>
                <Transition name="fade">
                    <div v-if="$route.meta.commentable">
                        <CommentSection />
                    </div>
                </Transition>
            </main>
        </div>
    </div>
</template>
<script>
import UiSidebar from '@fabriq/components/ui/UiSidebar.vue'
import UiTopbar from '@fabriq/components/ui/UiTopbar.vue'
import UiDesktopSidebar from '@fabriq/components/ui/UiDesktopSidebar.vue'
import CommentSection from '@fabriq/comments/CommentSection.vue'
import { useConfigStore, useUserStore } from '@fabriq/stores'
import { KeepAlive } from 'vue'
import { ModalsContainer } from 'vue-final-modal'
import ToastArea from '@fabriq/components/toast/ToastArea.vue'
import { useEventBus } from '@fabriq/services/eventBus'

export default {
    name: 'App',
    components: {
        UiSidebar,
        UiDesktopSidebar,
        UiTopbar,
        CommentSection,
        ModalsContainer,
        ToastArea
    },
    setup () {
        const userStore = useUserStore();
        const eventBus = useEventBus()
        const configStore = useConfigStore();

        return { userStore, configStore, eventBus }
    },
    data () {
        return {
            pollingNotifications: null,
            countdown: 0,
            cancelReload: false
        }
    },
    computed: {
        user () {
            return this.userStore.user;
        },
        userRoles () {
            return this.userStore.roles;
        },
        activeLocale: {
            get () {
                return this.configStore.activeLocale;
            },
            set (value) {
                this.configStore.setActiveLocale(value);
            },
        },
    },
    async created () {
        this.activeLocale = 'sv'
        await this.userStore.index();
        this.configStore.index();
        this.userStore.fetchNotifications();
        this.startPoll()
    },
    methods: {
        startPoll () {
            if(this.$echo) {
                // Listen to private user events

                this.listenToEchoEvents();
                return
            }
            this.pollingNotifications = setInterval(() => {
                this.userStore.fetchNotifications();
            }, 1000 * 120)
        },
        listenToEchoEvents() {
            const wsPrefix = window.fabriqCms.pusher.ws_prefix
            this.$echo.channel(`${wsPrefix}.comments`)
                .listen(`.comment.posted`, (event) => {
                    if (this.userStore.user.id !== event.comment.user_id) {
                        this.eventBus.emit('comment-posted-echo', event)
                    }
                })
                .listen(`.comment.deleted`, (event) => {
                    if (this.userStore.user.id !== event.comment.user_id) {
                        this.eventBus.emit('comment-posted-echo', event)
                    }
                })

            this.$echo.private(`${wsPrefix}.user.${this.userStore.user.id}`)
                .listen(`.comment.user-mentioned`, (event) => {
                    this.eventBus.emit('user-mentioned-echo', event)
                    this.userStore.fetchNotifications();
                })
                .listen(`.notification.deleted`, (event) => {
                    this.eventBus.emit('user-mentioned-echo', event)
                    this.userStore.fetchNotifications();
                })
                .notification((notification) => {
                    // broadcast.ask-to-leave
                    if(notification.type === 'broadcast.ask-to-leave') {
                        this.eventBus.emit('user-asked-to-leave-echo', notification)
                    }
                    if(notification.type === 'broadcast.leave-declined') {
                        this.eventBus.emit('user-declined-to-leave-echo', notification)
                    }
                })
            this.$echo.channel(`${wsPrefix}.media`)
                .listen(`.media-finished-processing`, (event) => {
                    console.log(event)
                    this.eventBus.emit('media-finished-processing', event)
                })
        }
    }
}
</script>