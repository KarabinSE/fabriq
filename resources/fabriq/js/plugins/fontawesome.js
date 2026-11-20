import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'



function registerIcons(){
    library.add(
        // add icons here
    )
}

export default {
    install(app) {
        registerIcons()

        app.component('Fa', FontAwesomeIcon)
    },
}
