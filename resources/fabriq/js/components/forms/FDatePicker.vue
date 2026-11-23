<template>
    <DatePicker
        v-model="model"
        :popover="{
            visibility: 'focus',
            placement: popoverPlacement,
            hideDelay: 30
        }"
        :select-attribute="selectAttribute"
        :is-range="isRange"
        timezone="utc"
        :disabled-dates="disabledDates"
        :mode
        :attributes="attributes"
        :rules="rules"
        :model-config="modelConfig"
        is24hr
    >
        <template
            v-if="isRange"
            #default="{ inputValue, inputEvents, isDragging }"
        >
            <div class="flex justify-between">
                <FLabel
                    v-if="label"
                    class="mb-1"
                    :name="name"
                    :required="hasRuleRequired"
                >
                    {{ label }}
                </FLabel>
                <button
                    class="text-xs link"
                    @click="clearDates"
                >
                    Rensa
                </button>
            </div>
            <div class="flex flex-col items-center justify-start sm:flex-row">
                <div class="relative grow">
                    <FInput
                        :class="isDragging ? 'text-gray-600' : 'text-gray-900'"
                        :value="inputValue.start"
                        :placeholder="placeholder"
                        v-on="inputEvents.start"
                    >
                        <template #icon>
                            <CalendarIcon class="w-4 h-4" />
                        </template>
                    </FInput>
                </div>
                <span class="shrink-0 m-2">
                    <MinusIcon
                        thin
                        class="w-6 h-6 text-gray-800"
                    />
                </span>
                <div class="relative grow">
                    <FInput
                        :class="isDragging ? 'text-gray-600' : 'text-gray-900'"
                        :value="inputValue.end"
                        v-on="inputEvents.end"
                    >
                        <template #icon>
                            <CalendarIcon class="w-4 h-4" />
                        </template>
                    </FInput>
                </div>
            </div>
            <slot
                name="date"
                v-bind="togglePopover"
            />
        </template>
        <template
            v-else
            #default="{ inputValue, inputEvents }"
        >
            <span class="flex justify-between">

                <FLabel
                    v-if="label"
                    class="mb-1"
                    :name="name"
                    :required="hasRuleRequired"
                >
                    {{ label }}
                </FLabel>
                <button
                    v-if="clearable"
                    class="text-xs link"
                    type="button"
                    @click="clearDates"
                >
                    Rensa
                </button>
            </span>
            <FInput
                class=""
                :value="inputValue"
                :placeholder="placeholder"
                v-on="inputEvents"
            >
                <template #icon>
                    <CalendarIcon class="w-4 h-4" />
                </template>
            </FInput>
        </template>
    </DatePicker>
</template>

<script setup>
import { DatePicker } from 'v-calendar'
import { computed, ref } from 'vue'
import { isPast, isToday } from 'date-fns'
import { useUserStore } from '@fabriq/stores'

const userStore = useUserStore()

defineOptions({
    name: 'FDatePicker',
})
const model = defineModel({
    type: [Object, Array, String, Date, Number],
    default: '',
    set(value) {
        if(props.warnOnPastDate){
            if(isToday(value)) {
                return value
            }

            if(isPast(value)){
                alert('Du har valt ett passerat datum')
            }
        }

        return value
    },
})

const props = defineProps({
    isRange: {
        type: Boolean,
        default: false,
    },
    label: {
        type: String,
        default: '',
    },
    popoverPlacement: {
        type: String,
        default: 'bottom-start',
    },
    disabledDates: {
        type: Array,
        default: () => ([]),
    },
    formatFn: {
        type: Function,
        default: (date) => format(date, 'yyyy-MM-dd HH:mm'),
    },
    clearable: {
        type: Boolean,
        default: false,
    },
    mode: {
        type: String,
        default: 'date',
    },

    placeholder: {
        type: String,
        default: '',
    },
    validationMode: {
        type: String,
        default: 'eager',
    },
    absolutePosErrors: {
        type: Boolean,
        default: false,
    },
    hideErrors: {
        type: Boolean,
        default: false,
    },
    rules: {
        type: [String, Object],
        default: '',
    },
    name: {
        type: String,
        default: '',
    },
    inline: {
        type: Boolean,
        default: false,
    },
    modelConfig: {
        type: Object,
        default: () => {
            return {
                type: 'string',
                mask: 'iso',
            }
        },
    },
    masks: {
        type: Object,
        default: () => {
            return {
                input: 'YYYY-ax-DD',
                output: 'YYYY-MM-DD',

            }
        },
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    warnOnPastDate: {
        type: Boolean,
        default: false,
    },
    timezone: {
        type: String,
        default: 'UTC',
    },
})

const attributes = computed(() => {
    const today = new Date().toDateString()
    let selected = null

    if (model.value) {
        selected = new Date(model.value).toDateString()
    }

    return [
        {
            key: 'today',
            dates: new Date(),
            highlight: today === selected
                ? {
                    fillMode: 'outline',
                    contentClass: '!text-gray-800',
                    style: {
                        'border-color': '#7697E5 !important',
                        backgroundColor: '#DDE5F8 !important',
                    },
                }
                : {
                    fillMode: 'outline',
                    contentClass: '!text-gray-800',
                    style: {
                        'border-color': '#BACBF2',
                    },
                },
        },
    ]
})

const selectAttribute = ref({
    highlight: {
        fillMode: 'solid',
        contentClass: '!text-slate-800',
        style: {
            backgroundColor: '#DDE5F8 !important',
        },
    },
})

const hasRuleRequired = computed(() => {
    return props.rules.includes('required')
})

const hasDateSlot = computed(() => {
    return !!$slots.date
})


function clearDates() {
    let reset = ''

    if (props.isRange) {
        reset = {
            start: null,
            end: null,
        }
    }

    model.value = reset
}

</script>

<style>
@reference '@fabriq/../css/fabriq.css';

.vc-time-month, .vc-time-day {
    @apply text-royal-300;
}

.vc-container .vc-weekday-1, .vc-container .vc-weekday-7 {
    @apply text-royal-300;
}
.vc-container .vc-base-icon {
    @apply text-royal-300;
}
.vc-attr {
    --vc-accent-200: var('--color-royal-200');
    --vc-accent-600: var('--color-royal-300');
    --vc-accent-700: var('--color-royal-400');
    --vc-accent-900: var('--color-royal-500');
}
</style>
