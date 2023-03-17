class Route {
    #base = null

    #endpoints = {
        index: 'index',
        destroy: 'destroy',
        show: 'show',
        store: 'store',
        update: 'update',
    }

    constructor(base) {
        this.#base = base
    }

    index(parameters = {}) {
        return this.#route('index', parameters)
    }

    destroy(parameters = {}) {
        return this.#route('destroy', parameters)
    }

    show(parameters = {}) {
        return this.#route('show', parameters)
    }

    store(parameters = {}) {
        return this.#route('store', parameters)
    }

    update(parameters = {}) {
        return this.#route('update', parameters)
    }

    /**
     * 
     */
    #endpoint(type) {
        return '.' + this.#endpoints[type]
    }

    /**
     * 
     */
    #route(endpoint, parameters = {}) {
        return window.route(
            this.#base + this.#endpoint(endpoint),
            parameters
        )
    }
}

module.exports = Route