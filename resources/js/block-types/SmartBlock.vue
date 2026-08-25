<template>
    <div>
        <div class="grid grid-cols-3 gap-x-6">
            <FInput
                v-model="localContent.name"
                name="name"
                label="Namn"
                rules="required"
                help-text="Visas endast internt"
            />
            <FSelect
                v-model="localContent.smartBlock"
                label="Smart block"
                name="smartBlock"
                :reduce-fn="item => item"
                option-label="name"
                :clearable="false"
                value-key="id"
                rules="required"
                placeholder="Välj smart block"
                :options="smartBlocks"
            />
            <div v-if="localContent.smartBlock">
                <FSwitch
                    v-if="localContent.smartBlock.id == 2"
                    v-model="localContent.limitBlocks"
                    name="limitBlocks"
                >
                    Visa två slumpmässiga?
                </FSwitch>
            </div>
        </div>
        <div class="mt-8 info-placeholder">
            <div class="flex items-center justify-center border border-dashed rounded-md min-h-36 border-royal-300">
                <div class="text-center">
                    <div class="text-xl font-light">
                        Autogenererat innehåll
                    </div>
                    <RouterLink
                        v-if="localContent.smartBlock"
                        :to="{ name: 'smartBlocks.edit', params: { id: localContent.smartBlock.id } }"
                        class="text-sm"
                    >
                        Klicka här för att gå till det smarta blocket
                    </RouterLink>
                    <RouterLink
                        v-else
                        :to="{ name: 'smartBlocks.index' }"
                        class="text-sm"
                    >
                        Klicka här för att gå till Smarta block
                    </RouterLink>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import SmartBlock from '@/models/SmartBlock'
export default {
    name: 'SmartBlock',
    props: {
        index: {
            type: Number,
            default: 0
        },
        value: {
            type: [String, Number, Object, Array],
            default: () => {
                return {
                    name: '',
                    header: ''
                }
            }
        },
        content: {
            type: [Array, Object],
            required: true,
            default: () => {
                return {
                    name: '',
                    headerType: {
                        name: ''
                    }
                    // repeaters: []
                }
            }
        }
    },
    data () {
        return {

            // repeaters: this.content.repeaters,
            localContent: this.content,
            smartBlocks: []
        }
    },
    activated () {
        this.id = this.$route.params.id
        this.fetchSmartBlocks()
    },
    methods: {
        async fetchSmartBlocks () {
            try {
                const { data } = await SmartBlock.index()
                this.smartBlocks = data
            } catch (error) {
                console.error(error)
            }
        }
    }

}
</script>
