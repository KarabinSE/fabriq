<script>
export default {
    name: 'SlideUpDown',

    props: {
        active: Boolean,
        duration: {
            type: Number,
            default: 500,
        },
        tag: {
            type: String,
            default: 'div',
        },
        useHidden: {
            type: Boolean,
            default: true,
        },
    },

    data() {
        return {
            hidden: !this.active,
            transitionStyle: {},
            transitionFrame: null,
            transitionStartedAt: null,
            transitionDuration: 0,
            transitionId: 0,
            transitioningTo: null,
        }
    },

    computed: {
        attributes() {
            const attributes = {
                'aria-hidden': !this.active,
                'aria-expanded': this.active,
            }

            if (this.useHidden) {
                attributes.hidden = this.hidden
            }

            return attributes
        },
    },

    watch: {
        active(active) {
            this.transition(active)
        },
    },

    beforeDestroy() {
        cancelAnimationFrame(this.transitionFrame)
    },

    methods: {
        transition(opening) {
            const element = this.$refs.container
            if (!element) return

            cancelAnimationFrame(this.transitionFrame)
            this.transitionFrame = null
            this.transitionStartedAt = null
            this.transitionDuration = Math.max(0, this.duration)
            const transitionId = ++this.transitionId
            this.transitioningTo = opening
            this.hidden = false
            this.$emit(opening ? 'open-start' : 'close-start')

            this.transitionStyle = {
                height: `${element.getBoundingClientRect().height}px`,
                overflow: 'hidden',
                'transition-property': 'none',
            }

            this.$nextTick(() => {
                if (transitionId !== this.transitionId) return

                void element.offsetHeight

                this.transitionStyle = {
                    height: opening ? `${element.scrollHeight}px` : '0px',
                    overflow: 'hidden',
                    'transition-property': 'height',
                    'transition-duration': `${this.transitionDuration}ms`,
                }

                this.scheduleTransitionFrame(transitionId)
            })
        },

        scheduleTransitionFrame(transitionId) {
            this.transitionFrame = requestAnimationFrame((timestamp) => {
                if (transitionId !== this.transitionId || this.transitioningTo === null) return

                this.transitionStartedAt ??= timestamp
                if (timestamp - this.transitionStartedAt >= this.transitionDuration) {
                    this.finishTransition(transitionId)

                    return
                }

                this.scheduleTransitionFrame(transitionId)
            })
        },

        finishTransition(transitionId) {
            if (transitionId !== this.transitionId || this.transitioningTo === null) return

            cancelAnimationFrame(this.transitionFrame)
            this.transitionFrame = null
            const opening = this.transitioningTo
            this.transitioningTo = null

            if (opening) {
                this.transitionStyle = { height: 'auto' }
                this.$emit('open-end')
            } else {
                this.transitionStyle = {
                    height: '0px',
                    overflow: 'hidden',
                }
                this.hidden = true
                this.$emit('close-end')
            }
        },

        onTransitionEnd(event) {
            if (event.target !== this.$refs.container || event.propertyName !== 'height') return

            this.finishTransition(this.transitionId)
        },
    },

    render(h) {
        return h(
            this.tag,
            {
                attrs: this.attributes,
                ref: 'container',
                style: this.transitionStyle,
                on: { transitionend: this.onTransitionEnd },
            },
            this.$slots.default
        )
    },
}
</script>
