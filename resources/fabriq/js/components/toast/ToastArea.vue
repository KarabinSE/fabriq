<!-- eslint-disable vue/no-v-html -->
<template>
    <NotificationGroup group="toasts">
        <div class="fixed z-10000 inset-0 flex items-start justify-center  p-6 px-4 py-6 pointer-events-none">
            <div class="w-full max-w-sm">
                <Notification
                    v-slot="{ notifications, close }"
                    enter="transform ease-out duration-200 transition"
                    enter-from="translate-y-2 opacity-0 "
                    enter-to="translate-y-0 opacity-100"
                    leave="transition ease-in duration-200"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                    move="transition duration-300"
                    move-delay="delay-100"
                >
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="flex w-full max-w-sm mx-auto mt-4 bg-white border border-gray-100 rounded-lg shadow-md pointer-events-auto group dark:bg-gray-900"
                    >
                        <div
                            :class="[
                                'flex  flex-1 p-4 cursor-pointer relative',
                                (!notification.text && notification.action) && 'items-center',
                                (notification.text || notification.action) && 'items-start',
                                !notification.text && !notification.action && 'items-center'
                            ]"
                        >
                            <div class="shrink-0 text-xl ">
                                <template v-if="!notification.data?.user">
                                    {{ notification.icon }}
                                </template>
                                <template v-else>
                                    <!-- <AvatarItem :user="notification.data?.user" /> -->
                                </template>
                            </div>
                            <div class="flex-1 w-0 ml-3">
                                <p
                                    class="text-sm font-medium text-gray-900 dark:text-gray-100"
                                    v-text="notification.title"
                                />
                                <p
                                    v-if="notification.text"
                                    class="mt-1 text-gray-600 text-tiny dark:text-gray-300"
                                    v-html="notification.text"
                                />
                            </div>
                            <button
                                v-show="!notification.action"
                                class="absolute top-0 right-0 mt-1 mr-3 text-gray-400"
                                @click="close(notification.id)"
                            >
                                <Fa :icon="faTimes" />
                            </button>
                            <button
                                v-show="notification.action"
                                class="absolute top-0 left-0 flex items-center justify-center w-5 h-5 -mt-2 -ml-2 leading-none text-gray-400 transition-all duration-150 ease-out transform scale-0 bg-white border rounded-full group-hover:scale-100"
                                @click="close(notification.id)"
                            >
                                <Fa
                                    :icon="faTimes"
                                    class="fa-sm"
                                />
                            </button>
                        </div>
                        <div
                            v-if="notification.action"
                            class="flex border-l border-gray-200 "
                        >
                            <button
                                v-if="notification.action.path"
                                type="button"
                                class="flex items-center justify-center w-full p-4 text-sm font-medium border border-transparent rounded-none rounded-r-lg text-secondary-500 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600"
                                @click="goToRoute(notification.action?.path); close(notification.id)"
                            >
                                {{ notification.action.text }}
                            </button>
                            <button
                                v-if="notification.action.callback"
                                type="button"
                                class="flex items-center justify-center w-full p-4 text-sm font-medium transition-colors border border-transparent rounded-none rounded-r-lg text-secondary-500 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600"
                                @click="notification.action.callback(notification); close(notification.id)"
                            >
                                {{ notification.action.text }}
                            </button>
                        </div>
                    </div>
                </Notification>
            </div>
        </div>
    </NotificationGroup>
</template>

<script setup>
import { faTimes } from '@fortawesome/free-solid-svg-icons'
import { Notification, NotificationGroup } from 'notiwind'

const goToRoute = (path) => {
    //Inertia go to route
}

</script>
