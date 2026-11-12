<template>
    <div>
        <FLabel
            v-if="label"
            :class="classes"
            :name="name"
            :required="hasRuleRequired"
        >
            {{ label }}
        </FLabel>
        <Multiselect
            :id="name"
            :value="value"
            :options="options"
            :value-prop="valueKey"
            :object="object"
            :class="{'bg-gray-100 text-gray-500 cursor-not-allowed' : inputDisabled}"
            :disabled="inputDisabled"
            :searchable="searchable"
            :label="optionLabel"
            :name="name"
            :mode="mode"
            :can-clear="canClear"
            :can-deselect="clearable"
            :placeholder="placeholder"
            :create-option="mode === 'tags'"
            :on-create="createOption"
            :append-new-option="pushTags"
            @open="$emit('open')"
            @input="emitValue"
        >
            <template #singlelabel="{ value: option }">
                <div class="multiselect-single-label">
                    <slot
                        name="fop"
                        v-bind="option"
                    >
                        {{ option[optionLabel] }}
                    </slot>
                </div>
            </template>
            <template #option="{ option, isSelected }">
                <div
                    class="inline-flex items-center"
                >
                    <div
                        v-if="isSelected(option)"
                        class="absolute z-10 w-2 h-2 rounded-full bg-gold-500"
                    />
                    <span class="flex items-center ml-2">
                        <slot
                            name="prefix"
                            v-bind="option"
                        />
                        <span v-text="option[optionLabel]" />
                    </span>
                </div>
            </template>
            <template #nooptions>
                <div class="flex items-center justify-center px-2 space-x-3 cursor-default h-9.5">
                    <span class="text-xl">🧐 </span> <span class="text-xs">Kunde inte hitta några resultat</span>
                </div>
            </template>
            <template #noresults>
                <div class="flex items-center justify-center px-2 space-x-3 cursor-default h-9.5">
                    <span class="text-xl">🧐 </span> <span class="text-xs">Kunde inte hitta några resultat</span>
                </div>
            </template>
            <template #caret="{ handleCaretClick, isOpen }">
                <span
                    :class="{ 'pointer-events-none': !isOpen }"
                    class="fabriq-multiselect-caret"
                    @mousedown.prevent="handleCaretClick"
                >
                    <AngleDownIcon
                        class="w-4 h-4 text-gray-500 transition-[colors,rotate] duration-300"
                        :class="isOpen ? '-rotate-180' : 'rotate-0'"
                    />
                </span>
            </template>
        </Multiselect>
        <HelpText v-if="helpText">
            {{ helpText }}
        </HelpText>
    </div>
</template>

<script>
import Multiselect from '@vueform/multiselect/dist/multiselect.vue2.js'

export default {
    name: 'FSelect',
    components: {
        Multiselect,
    },
    props: {
        value: {
            type: [String, Number, Object, Array, Boolean],
            default: '',
        },

        options: {
            type: Array,
            required: false,
            default: () => [],
        },

        label: {
            type: String,
            default: '',
        },

        optionLabel: {
            type: String,
            default: 'label',
        },

        valueKey: {
            type: String,
            default: 'value',
        },

        mode: {
            type: String,
            default: 'single',
        },

        searchable: {
            type: Boolean,
            default: false,
        },

        object: {
            type: Boolean,
            default: false,
        },

        placeholder: {
            type: String,
            default: 'Välj',
        },

        canClear: {
            type: Boolean,
            default: false,
        },

        clearable: {
            type: Boolean,
            default: true,
        },

        rules: {
            type: String,
            default: '',
        },

        validationMode: {
            type: String,
            default: 'eager',
        },

        name: {
            type: String,
            required: true,
        },

        absolutePosErrors: {
            type: Boolean,
            default: false,
        },

        hideErrors: {
            type: Boolean,
            default: false,
        },

        pushTags: {
            type: Boolean,
            default: false,
        },

        createOption: {
            type: Function,
            default: null,
        },

        disabled: {
            type: Boolean,
            default: false,
        },

        helpText: {
            type: String,
            default: '',
        },

        defaultValue: {
            type: [String, Number, Object, Array],
            default: '',
        },
    },

    emits: ['open', 'input'],

    computed: {
        hasRuleRequired () {
            return this.rules.includes('required')
        },

        inputDisabled() {
            if(this.disabled) {
                return true
            }

            return false
        },
    },

    mounted () {
        if (this.defaultValue && !this.value) {
            this.$emit('input', this.defaultValue)
        }
    },

    methods: {
        emitValue (value) {
            this.$emit('input', value)
        },

        convertErrorMessage (string) {
            if (string.includes('{field}')) {
                return string.replace('{field}', this.label.toLowerCase())
            }

            return string
        },
        classes() {
            return
        }
    },
}
</script>
