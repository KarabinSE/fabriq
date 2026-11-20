import mitt from 'mitt'
import { hasInjectionContext, inject } from 'vue'

const emitter = mitt()

const eventBus = {
    on: (eventName, handler) => emitter.on(eventName, handler),
    off: (eventName, handler) => emitter.off(eventName, handler),
    emit: (eventName, payload) => emitter.emit(eventName, payload),
    once: (eventName, handler) => {
        const wrappedHandler = (payload) => {
            emitter.off(eventName, wrappedHandler)
            handler(payload)
        }
        emitter.on(eventName, wrappedHandler)
    },
}

const eventBusPlugin = {
    install(app) {
        app.config.globalProperties.$eventBus = eventBus

        app.provide('eventBus', eventBus)
    }
}

export function useEventBus() {
    if(!hasInjectionContext()) {
        throw new Error("Must be called within setup");

    }
    return inject('eventBus', {})
}


export default eventBusPlugin
