

export class Media {
    type = 'image'
    image = {}
    video = {}
    fullscreen = false

    constructor(data = {}) {
        this.type = data.type ?? 'image'
        this.image = data.image ?? {}
        this.video = data.video ?? {}
        this.fullscreen = data.fullscreen ?? false
    }
}