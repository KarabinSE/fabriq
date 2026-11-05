<template>
    <FModal
        v-model="show"
        name="menu-item-modal"
        width="max-w-2xl"
        :click-to-close="false"
        @closed="resetModal"
        @before-open="initModal"
    >
        <template #title>
            <span
                class="text-gray-700"
                v-text="isCreating ? 'Skapa menypunkt' : 'Redigera menypunkt'"
            />
        </template>

        <template #actions>
            <div class="flex justify-end space-x-4">
                <button
                    class="px-8 py-2.5 leading-none fabriq-btn btn-link"
                    @click="show = false"
                >
                    Stäng
                </button>
                <FButton
                    v-if="! isCreating"
                    :click="updateMenuItem"
                    class="px-8 py-2.5 leading-none fabriq-btn btn-royal"
                >
                    Spara
                </FButton>
                <FButton
                    v-else
                    :click="createMenuItem"
                    class="px-8 py-2.5 leading-none fabriq-btn btn-royal"
                >
                    Skapa menypunkt
                </FButton>
            </div>
        </template>

        <div class="relative py-2 duration-300">
            <!-- Menu item type -->
            <div class="flex flex-col gap-6">
                <div>
                    <FLabel>Länktyp</FLabel>
                    <div class="grid grid-cols-3 gap-4">
                        <div
                            :data-checked="mItem.type === 'internal'"
                            @click="mItem.type = 'internal'"
                            class="group rounded-md ring ring-gray-300 data-checked:ring-royal-500 border-b border-transparent data-checked:border-royal-500 p-3 data-checked:shadow transition duration-200"
                        >
                            <div class="flex gap-2 items-center">
                                <div class="p-1 size-4 border ring-gray-300 flex justify-center items-center rounded-full">
                                    <span class="rounded-full size-full group-data-checked:bg-royal-500 aspect-square duration-200"  />
                                </div>
                                Intern
                            </div>
                            <div class="text-gray-500 text-sm mt-2">
                                Välj en sida att länka till
                            </div>
                        </div>
                        <div
                            :data-checked="mItem.type === 'external'"
                            @click="mItem.type = 'external'"
                            class="group rounded-md ring ring-gray-300 data-checked:ring-royal-500 border-b border-transparent data-checked:border-royal-500 p-3 data-checked:shadow transition duration-200"
                        >
                            <div class="flex gap-2 items-center">
                                <div class="p-1 size-4 border ring-gray-300 flex justify-center items-center rounded-full">
                                    <span class="rounded-full size-full group-data-checked:bg-royal-500 aspect-square duration-200"  />
                                </div>
                                Extern
                            </div>
                            <div class="text-gray-500 text-sm mt-2">
                                Ange en relativ URL eller en extern webbadress
                            </div>
                        </div>
                        <div
                            :data-checked="mItem.type === 'file'"
                            @click="mItem.type = 'file'"
                            class="group rounded-md ring ring-gray-300 data-checked:ring-royal-500 border-b border-transparent data-checked:border-royal-500 p-3 data-checked:shadow transition duration-200"
                        >
                            <div class="flex gap-2 items-center">
                                <div class="p-1 size-4 border ring-gray-300 flex justify-center items-center rounded-full">
                                    <span class="rounded-full size-full group-data-checked:bg-gray-800 aspect-square duration-200"  />
                                </div>
                                Fil
                            </div>
                            <div class="text-gray-500 text-sm mt-2">
                                Välj en fil som besökare kan ladda ner
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conditional inputs -->
                <div class="h-22">
                    <ValidationObserver
                        ref="validator"
                    >
                        <FSelect
                            v-if="mItem.type === 'internal'"
                            v-model="mItem.page_id"
                            value-key="id"
                            :reduce-fn="page => page.id"
                            label="Länkas till sidan"
                            :options="pages"
                            option-label="name"
                            rules="required"
                            name="pageSelect"
                            @input="onPageChange"
                        >
                            <template #prefix="option">
                                <div
                                    v-for="index in option.depth"
                                    :key="index"
                                >
                                    <div class="mr-3" />
                                </div>
                            </template>
                        </FSelect>

                        <FInput
                            v-if="mItem.type === 'external'"
                            v-model="content.external_url"
                            label="URL"
                            placeholder="https://exempel.se"
                            help-text="Ange en relativ URL för att länka till en sida på webbplatsen."
                            name="URL"
                            :rules="isCreating ? '' : 'required'"
                            type="url"
                            class="w-full"
                        />

                        <FFileInput
                            v-if="mItem.type === 'file'"
                            label="Fil"
                            name="Fil"
                            rules="required"
                            v-model="content.file"
                        />
                    </ValidationObserver>
                </div>

                <div>
                    <FInput
                        v-model="content.title"
                        :placeholder="placeholder"
                        help-text="Texten visas i menyer"
                        label="Titel"
                        disabled
                        name="title"
                        :rules="isCreating || mItem.type === 'internal' ? '' : 'required'"
                    />
                </div>

                <FEditor
                    :key="'html' + mItem.Id"
                    v-model="content.body"
                    label="Menytext"
                />

                <Transition name="menu-item-spinner">
                    <div
                        v-if="!ready"
                        key="spinner"
                        class="absolute z-10 inset-0 flex justify-center items-center bg-white"
                    >
                        <SpinIcon class="animate-spin size-10" />
                    </div>
                </Transition>
            </div>
        </div>
    </FModal>
</template>
<script>
import MenuItem from '@fabriq/models/MenuItem.js'
import PageTree from '@fabriq/models/PageTree.js'
function defaultCreationObject () {
    return {
        id: 0,
        type: 'internal',
        content: {
            title: ''
        }
    }
}

export default {
    name: 'MenuItemModal',
    props: {
        isCreating: {
            type: Boolean,
            default: false
        }
    },
    data () {
        return {
            show: false,
            pages: [],
            content: {},
            ready: false,
            placeholder: null,
            useCustomTitle: false,
            mItem: {
                id: 0,
                type: 'internal',
                content: {
                    title: ''
                }
            }
        }
    },
    methods: {
        async initModal (parameters) {
            if (this.isCreating) {
                this.fetchPages()
                this.$set(this, 'content', {
                    title: null,
                    url: null,
                    external_url: null,
                    body: null
                })
            } else {
                const id = parameters.ref.params
                this.mItem.id = id
                const promises = [this.fetchPages(), this.fetchMenuItem()]
                await Promise.all(promises)
            }

            this.ready = true
        },
        async validate() {
            let valid = false

            if (!this.mItem.type) {
                return
            }

            valid = await this.$refs.validator.validate()
            this.$refs.validator.reset()

            if(!valid) {
                throw new Error("Validation failed");
            }
        },
        async fetchPages () {
            try {
                const payload = {
                    params: {
                        // field: 'id,name',
                        selectOptions: true
                    }
                }
                const { data } = await PageTree.index(payload)
                this.pages = data
            } catch (error) {
                console.error(error)
            }
        },
        async createMenuItem () {
            try {
                await this.validate()

                const payload = {
                    item: this.mItem,
                    has_custom_title: this.useCustomTitle,
                    content: {
                        ...this.content,
                        title: this.content.title ?? this.placeholder
                    }
                }
                await MenuItem.store(this.$route.params.id, payload)
                this.$toast.success({ title: 'Menypunkten har skapats!' })
                this.$emit('created')
                this.show = false
            } catch (error) {
                console.error(error)
            }
        },
        async updateMenuItem () {
            try {
                await this.validate()
            } catch (error) {
                this.$toast.warning({ title: 'Kontrollera uppgifterna', message: 'Fyll i alla obligatoriska fält korrekt.' })
                return
            }

            try {
                const payload = {
                    item: this.mItem,
                    content: {
                        ...this.content,
                        title: this.content.title ?? this.placeholder,
                    }
                }
                await MenuItem.update(this.mItem.id, payload)
                this.$emit('updated')
                this.show = false
            } catch (error) {
                console.error(error)
            }
        },
        async fetchMenuItem () {
            try {
                const payload = {
                    params: {
                        include: 'content'
                    }
                }
                const { data } = await MenuItem.show(this.mItem.id, payload)
                this.mItem = { ...data }
                if(!Array.isArray(data.content.data)) {
                    this.content = data.content.data
                }
                if(!this.content.title) {
                    this.$set(this, 'placeholder', this.pages.find(page => page.id === this.mItem.page_id)?.name ?? '')
                }
            } catch (error) {
                console.error(error)
            }
        },
        resetModal () {
            this.ready = false
            this.mItem = { ...defaultCreationObject() }
            this.content = {}
            this.$nextTick(() => {
                this.$refs.validator.reset()
            })
        },
        onPageChange(pageId) {
            const pageTitle = this.pages.find(page => page.id === pageId)?.name ?? ''
            this.$set(this, 'placeholder', pageTitle)
        }
    }
}
</script>

<style>
.menu-item-spinner-enter-active {
    transition: all 150ms ease;
}
.menu-item-spinner-leave-active {
    transition: all 150ms cubic-bezier(1, 0.5, 0.8, 1);
}
.menu-item-spinner-enter
/* .menu-item-spinner-leave-active below version 2.1.8 */ {
    opacity: 0;
}
.menu-item-spinner-leave-to
/* .menu-item-spinner-leave-active below version 2.1.8 */ {
    opacity: 0;
}
</style>
