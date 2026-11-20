<template>
    <UiImagePresenter
        thumbnail
        :image="imageObject"
    />
</template>
<script>
export default {
    name: 'UiAvatar',
    props: {
        user: {
            type: Object,
            default: () => {
                return {
                    name: '',
                    email: '',
                    image: {
                        data: {
                            mime_type: 'image/avif'
                        }
                    }
                }
            },
            required: true
        }
    },
    computed: {
        imageObject () {
            return {
                thumb_src: this.src,
                alt_text: `Profilbild för ${this.user.name}`,
                mime_type: 'image/avif'
            }
        },
        fallbackUrl () {
            return encodeURIComponent(`https://eu.ui-avatars.com/api?name=${this.user.name}&format=svg&bold=true&background=e2d3bb&color=0b3b5b`)
        },
        src () {
            return this.user.image.data.src ?? `https://unavatar.io/${this.user.email}?fallback=${this.fallbackUrl}`
        }
    }
}
</script>
