<template>
    <FSelect
        v-model="color"
        :label="label"
        :name="name"
        value-key="value"
        option-label="label"
        :clearable="false"
        :options="getColorOptions()"
        :disabled="disabled"
        default-value="primary"
        class="w-44"
        @input="emitValue"
    >
        <template #fop="option">
            <div
                class="w-5 h-5 mr-4 border-gray-200 rounded-full"
                :class="option.color"
            />
            <span>
                {{ option.label }}
            </span>
        </template>
        <template #prefix="option">
            <div>
                <div
                    class="w-5 h-5 mr-4 border border-gray-200 rounded-full"
                    :class="option.color"
                />
            </div>
        </template>
    </FSelect>
</template>
<script>
export const colors = {
    buttonColors: [
        { label: 'Primär', color: 'bg-btn-primary border-btn-primary', value: 'primary' },
        { label: 'Primär kontur', color: 'border-btn-primary', value: 'primary-outline' },
        { label: 'Sekundär', color: 'bg-btn-secondary border-btn-secondary', value: 'secondary' },
        { label: 'Sekundär kontur', color: 'border-btn-secondary', value: 'secondary-outline' },
        { label: 'Tertiär', color: 'bg-btn-tertiary border-btn-tertiary', value: 'tertiary' },
        { label: 'Tertiär kontur', color: 'border-btn-tertiary', value: 'tertiary-outline' },
    ],
    backgroundColors: [
        { label: 'Primär', color: 'bg-primary', value: 'primary' },
        { label: 'Sekundär', color: 'bg-secondary', value: 'secondary' },
        { label: 'Tertiär', color: 'bg-tertiary', value: 'tertiary' },
    ]
}

export default {
    name: 'FColorPicker',
    props: {
        value: {
            type: String,
            default: null
        },
        label: {
            type: String,
            default: 'Färg'
        },
        collection: {
            type: String,
            default: 'buttons'
        },
        name: {
            type: String,
            default: ''
        },
        disabled: {
            type: Boolean,
            default: false
        }
    },
    data () {
        return {
            color: 'primary',
        }
    },
    mounted () {
        if (!this.value) {
            this.$emit('input', this.color)
        } else {
            this.color = this.value
        }
    },
    methods: {
        emitValue (value) {
            this.color = value
            this.$emit('input', value)
        },
        getColorOptions() {
            if(!['buttons', 'backgrounds'].includes(this.collection)) {
                console.error(`expected buttons|backgrounds got:${this.collection}`)
            }
            if(this.collection === 'backgrounds'){
                return colors.backgroundColors
            }

            return colors.buttonColors
        }
    }
}
</script>
