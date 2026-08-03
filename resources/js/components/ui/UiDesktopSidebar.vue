<template>
    <!-- Static sidebar for desktop -->
    <div class="hidden lg:flex md:flex-shrink-0">
        <div class="flex flex-col w-64">
            <!-- Sidebar component, swap this element with another sidebar if you like -->
            <div
                class="flex flex-col flex-grow pt-5 pb-0 overflow-y-auto bg-white border-r border-gray-200"
            >
                <div class="flex items-center flex-shrink-0 px-4">
                    <img
                        alt="Fabriq CMS Logotyp"
                        class="h-12 px-2"
                        src="@/../fabriq/images/fabriq-cms-logo.svg"
                    >
                </div>
                <div class="flex flex-col flex-grow mt-5">
                    <!-- <pre>{{ isDev }}</pre> -->
                    <nav class="flex-1 px-2 space-y-1 bg-white">
                        <RouterLink
                            v-for="(item, index) in menuItems"
                            :key="`ds${index}`"
                            :to="{ name: item.route }"
                            class="sidebar-item"
                        >
                            <Component
                                :is="item.icon"
                                :thin="true"
                                class="w-6 h-6 mr-3 text-gray-800 group-hover:text-gray-500"
                            />
                            {{ item.title }}
                        </RouterLink>
                    </nav>
                    <div
                        v-if="isDev"
                        class="px-4 mb-4 text-sm"
                    >
                        <div v-if="devMode">
                            <FButton
                                class="w-full py-2 my-4 fabriq-btn btn-outline-red"
                                :click="bustCache"
                            >
                                💥  &nbsp; Nuke cache
                            </FButton>
                        </div>
                        <FSwitch v-model="devMode">
                            Dev mode?
                        </FSwitch>
                    </div>
                    <div class="flex items-center justify-between flex-shrink-0 p-4 border-t border-gray-200 bg-gray-50">
                        <RouterLink
                            :to="{name: 'profile.settings'}"
                            class="flex"
                        >
                            <GearIcon
                                class="w-7"
                                thin
                            />
                        </RouterLink>
                        <RouterLink
                            class="relative block"
                            :to="{name: 'notifications.index'}"
                        >
                            <div
                                v-if="notifications.length > 0"
                                class="absolute top-0 right-0 w-3 h-3 -mt-1 -mr-1 bg-red-400 rounded-full dot"
                            />
                            <UiAvatar
                                :user="user"
                                class="inline-block object-cover rounded-lg h-9 w-9"
                            />
                        </RouterLink>
                        <div>
                            <LogoutForm />
                        </div>
                    </div>
                    <!-- <a class="flex-shrink-0 block w-full group" href="#">
                            <div class="flex items-center">
                                <div>
                                    <img
                                        alt
                                        class="inline-block rounded-full h-9 w-9"
                                        :src="`https://unavatar.io/${user.email}`"
                                    >
                                </div>
                                <div class="ml-3">
                                    <p
                                        class="text-sm font-medium text-gray-700 group-hover:text-gray-900"
                                    >{{ user.name }}</p>
                                    <p
                                        class="text-xs font-medium text-gray-500 group-hover:text-gray-700"
                                    >View profile</p>
                                </div>
                            </div>
                        </a> -->
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import UiLogo from '@/components/Logo.vue'
import LogoutForm from '@/components/LogoutForm.vue'
import Developer from '@/models/Developer.js'
import { useConfigStore, useUserStore, useRouteHistoryStore, useMenuStore } from '@/stores';

export default {
    name: 'UiDesktopSidebar',
    components: { UiLogo, LogoutForm },
    setup() {
        const configStore = useConfigStore();

        const userStore = useUserStore();

        const menuStore = useMenuStore();

        const routeHistoryStore = useRouteHistoryStore();

        return { 
            configStore, 
            userStore, 
            menuStore, 
            routeHistoryStore 
        }
    },
    computed: {
        devMode: {
            get () {
                return this.configStore.devMode;
            },
            set (value) {
                this.configStore.setDevMode(value);
            }
        },
        isDev () {
            return this.userStore.isDev;
        },
        lastRoute () {
            return this.routeHistoryStore.lastRoute;
        },
        menuItems () {
            return this.menuStore.fullMenuItems;
        },
        user () {
            return this.userStore.user;
        },
        notifications () {
            return this.userStore.notifications
        }
    },
    methods: {
        async bustCache () {
            try {
                await Developer.bustCache()
                this.$toast.success({ title: 'Cache was nuked successfully! 😵' })
            } catch (error) {
                console.error(error)
            }
        }
    }
}
</script>
