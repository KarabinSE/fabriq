<template>
    <div>
        <div>
            <RouterLink
                :to="{name:'home.index'}"
                class="inline-block px-6 py-3 mt-8 font-sans text-xl leading-none shadow hover:shadow-lg fabriq-btn btn-gold"
            >
                Gå hem
            </RouterLink>
            <span v-tooltip="'You have ' + 50 + ' new messages.'">
                <button>hej</button>
            </span>
            <div class="bg-gray-200">
                lolboll
                <UiPopover
                    id="poppy"
                    :distance="16"
                    :shown="true"
                    :triggers="[]"
                >
                    <!-- This will be the popover target (for the events and position) -->

                    <!-- This will be the content of the popover -->
                    <template #popper>
                        <input
                            v-model="msg"
                            class="tooltip-content"
                            placeholder="Tooltip content"
                        >
                        <p>
                            ett meddelande {{ msg }}
                        </p>

                    <!-- You can put other components too -->
                    <!-- <ExampleComponent char="=" /> -->
                    </template>
                </UiPopover>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    name: 'HomeIndex',
    data () {
        return {
            message: 'Some charts here perhaps 📈'
        }
    }
}
</script>
