export class Button {
    isButton = true

    text = ''
    linkType = 'internal'
    page_id = null
    file = {}

    constructor(data = {}) {
        Object.entries(data).forEach(([k,v]) => this[k] = v)
    }
}
