<template>
    <div>
        <FLabel
            v-if="label"
            :name="name"
            :required="hasRuleRequired"
        >
            {{ label }}
        </FLabel>
        <Multiselect
            :id="name"
            v-model="model"
            :options="options"
            :value-prop="valueKey"
            :object="object"
            :class="{'bg-gray-100 text-gray-500 cursor-not-allowed' : disabled}"
            :disabled="disabled"
            :searchable="(searchable === null && mode === 'tags') ? true : !!searchable"
            :label="optionLabel"
            :name="name"
            :mode="mode"
            :can-clear="canClear"
            :can-deselect="clearable"
            :placeholder="placeholder"
            :create-option="mode === 'tags'"
            :on-create="createOption"
            :append-new-option="pushTags"
            @open="emit('open')"
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
                    class="inline-flex items-center gap-2.5 pr-6"
                >
                    <div
                        :class="[
                            'z-10 size-2 rounded-full bg-gold-500 duration-100',
                            isSelected(option) ? '' : 'opacity-0 scale-0'
                        ]"
                    />
                    <span class="flex items-center">
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

<script setup>
import Multiselect from '@vueform/multiselect'
import { computed, watchEffect } from 'vue'

defineOptions({
    name: 'FSelect',
})

const model = defineModel({
    type: [String, Number, Object, Array, Boolean],
})


const emit = defineEmits(['open', 'update:model-value'])
const props = defineProps({
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
        default: null,
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
})
watchEffect(() =>{
    if(model.value === undefined) {
        emit('update:model-value', props.mode === 'single' ? null : [], {} )
    }
})

const hasRuleRequired = computed(() => props.rules.includes('required'))
</script>
