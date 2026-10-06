import mitt from 'mitt'

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
    $on: (eventName, handler) => emitter.on(eventName, handler),
    $off: (eventName, handler) => emitter.off(eventName, handler),
    $emit: (eventName, payload) => emitter.emit(eventName, payload),
    $once: (eventName, handler) => {
        const wrappedHandler = (payload) => {
            emitter.off(eventName, wrappedHandler)
            handler(payload)
        }
        emitter.on(eventName, wrappedHandler)
    },
}

export default eventBus
