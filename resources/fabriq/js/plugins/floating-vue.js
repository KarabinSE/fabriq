import FloatingVue, { Dropdown } from 'floating-vue'
import 'floating-vue/dist/style.css'
import '../../css/floating-vue.css'

export default {
    install (app) {
        app.use(FloatingVue, {
            themes: {
                tooltip: {
                    delay: {
                        show: 300,
                        hide: 100,
                    }
                }
            }
        })
        app.component('UiPopover', Dropdown)
    }
}
