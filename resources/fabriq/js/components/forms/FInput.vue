
<template>
    <div :class="$attrs.class">
        <div class="flex justify-between">
            <FLabel
                v-if="label"
                :name="name"
                :non-white-bg="nonWhiteBg"
                :optional="optional"
                :required="hasRuleRequired"
            >
                {{ label }}
            </FLabel>

            <span
                v-if="characterCount"
                class="text-xs text-gray-500"
            >
                Antal tecken: {{ modelValue?.length ?? 0 }}
            </span>
        </div>

        <div
            v-if="inputType === 'radio'"
            class="flex items-center space-x-4 min-h-10"
        >
            <div
                v-for="(radioOption, index) in options"
                :key="index"
                class="flex items-center"
            >
                <input
                    :id="id + radioOption.value"
                    v-model="modelValue"
                    :name="id + name"
                    type="radio"
                    :checked="radioOption.value == modelValue"
                    :value="radioOption[valueKey] ?? radioOption"
                    :class="inputClasses"
                    :disabled
                    class="size-5 fabriq-radio form-radio focus:outline-none focus:ring-offset-3 focus:border focus:ring-royal-300"
                >

                <label
                    :for="id + radioOption.value"
                    class="cursor-pointer"
                >
                    <slot
                        name="option"
                        v-bind="radioOption"
                    >
                        <span class="block ml-3 text-sm font-medium text-gray-700">
                            {{ radioOption.label }}
                        </span>
                    </slot>
                </label>
            </div>
        </div>

        <textarea
            v-else-if="type === 'textarea'"
            ref="textarea"
            v-model="modelValue"
            :class="$attrs.class"
            :disabled
            :name="name"
            :placeholder="placeholder"
            :readonly="readOnly"
            class="field-sizing-content flex-1 block w-full rounded px-4 py-2.5 text-sm antialiased text-gray-800 transition duration-300 ease-out appearance-none leading-none border focus:outline-none border-gray-300 hover:border-gray-400 focus:!border-gray-800"
            :rows="rows"
            v-bind="$attrs"
        />

        <span
            v-else-if="inputType === 'input'"
            class="relative flex items-center"
        >
            <div
                v-if="prefix"
                class="inline-flex items-center px-3 py-2.5 text-sm font-medium text-gray-500 bg-gray-100 rounded-l border border-gray-300"
                v-text="prefix"
            />

            <span
                class="absolute ml-4"
                style="z-index: 1"
            >
                <slot name="icon" />
            </span>

            <input
                ref="input"
                v-model="modelValue"
                v-maska="mask"
                :class="[
                    $attrs.class,
                    inputClasses,
                    roundedClasses,
                    hasIcon ? 'pl-12' : 'pl-4',
                    disabled
                        ? 'bg-gray-100 text-gray-600 cursor-not-allowed'
                        : 'text-gray-800'
                ]"
                :disabled
                :min="min"
                :name="name"
                :placeholder="placeholder"
                :readonly="readOnly"
                :type="type"
                class="relative flex-1 block w-full min-h-10 px-4 py-2.5 text-sm/none antialiased transition duration-300 ease-out appearance-none border focus:outline-none border-gray-300 hover:border-gray-400 focus:!border-gray-800"
                v-bind="$attrs"
            >

            <div
                v-if="suffix"
                class="inline-flex items-center min-h-10 px-3 py-2.5 text-sm/none border-l-0 font-medium text-gray-500 bg-gray-100 rounded-r border border-gray-300"
                v-text="suffix"
            />

            <slot name="buttonSuffix" />
        </span>

        <HelpText v-if="helpText">
            {{ helpText }}
        </HelpText>
    </div>
</template>

<script setup>
import { computed, onMounted, useSlots, watchEffect } from 'vue'
import { useId } from 'vue'

defineOptions({
    name: 'FInput',
    inheritAttrs: false,
})

const props = defineProps({
    textarea: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    absolutePosErrors: {
        type: Boolean,
        default: false,
    },
    hideErrors: {
        type: Boolean,
        default: false,
    },
    name: {
        type: String,
        default: '',
    },
    label: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'text',
    },
    inputType: {
        type: String,
        default: 'input',
    },
    options: {
        type: Array,
        default: () => [],
    },
    min: {
        type: String,
        default: '',
    },
    rules: {
        type: String,
        default: '',
    },
    question: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: '',
    },
    errorMessage: {
        type: String,
        default: 'Fältet är obligatoriskt',
    },
    readOnly: {
        type: Boolean,
        default: false,
    },
    nonWhiteBg: {
        type: Boolean,
        default: false,
    },
    helpText: {
        type: String,
        default: '',
    },
    mask: {
        type: String,
        default: '',
    },
    inputClasses: {
        type: String,
        default: '',
    },
    inputRef: {
        type: String,
        default: 'input',
    },
    validationMode: {
        type: String,
        default: 'eager',
    },
    suffix: {
        type: String,
        default: '',
    },
    prefix: {
        type: String,
        default: '',
    },
    optional: {
        type: String,
        default: '',
    },
    rows: {
        type: Number,
        default: 2,
    },
    defaultValue: {
        type: [String, Number, Object, Array, Boolean],
        default: '',
    },
    characterCount: {
        type: Boolean,
        default: false,
    },
    valueKey: {
        type: String,
        default: undefined,
    },
})
const modelValue = defineModel({
    type: [String, Number, Object, Array, Boolean],
    required: true,
})

modelValue.value ??= props.defaultValue ?? ''


const id = useId()
const slots = useSlots()

const hasIcon = computed (() =>  !!slots.icon)
const hasRuleRequired = computed (() => props.rules.includes('required'))

const roundedClasses = computed (() => {
    if (props.prefix && props.suffix) return 'rounded-none'
    if (props.prefix) return 'rounded-r'
    if (props.suffix || !!slots.buttonSuffix) return 'rounded-l'

    return 'rounded'
})

</script>

<style scoped>
@reference 'tailwindcss';

.form-radio:disabled {
    @apply  opacity-30 cursor-not-allowed ;
}
.form-radio:checked {
    background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16'  fill='%230b3b5b' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='4.5'/%3e%3c/svg%3e");
    @apply border border-gray-400;
    background-color: #fff;
    background-size: 100% 100%;
    background-position: center;
    background-repeat: no-repeat;
}

.form-radio {
    appearance: none;
    color-adjust: exact;
    user-select: none;
    flex-shrink: 0;
    border-radius: 100%;
    /* color: #0b3b5b; */
    border-color: #d1d5db;
    border-width: 1px;
    background-origin: border-box;
    background-size: 50% 50%;
    transition: background-size .1s ease;
}

</style>
