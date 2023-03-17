export default class Collection {
    #baseRoute = null
    #parameters = null
    #collection = []
    #meta = null
    #endpoints = {
        index: 'index',
        destroy: 'destroy',
        show: 'show',
        store: 'store',
        update: 'update',
    }

    constructor(route, parameters = {}, collection = [], meta = {}) {
        this.#baseRoute = route
        this.#parameters = parameters
        this.#collection = collection
        this.#meta = meta

        if (collection === null) {}
    }

    first() {}
    find() {}

    /**
     * 
     * @return Resource
     */
    find(id) {
        let handler = {
            get(target, prop) {
                if (prop in target && prop[0] !== '#') {
                    if (typeof target[prop] === 'function') {
                        return target[prop].bind(target)
                    } else {
                        return target[prop]
                    }
                } else {
                    return target.data()[prop]
                }
            }
        }

        return new Proxy(
            new Resource(this.#find(id)),
            handler
        )
    }

    #find(id) {
        item = this.#collection.includes(item => {
            return item.id === id
        })

        if (item) {
            return item
        }

        this.#axios('get', this.#route('show', {id: id}))
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
    #route(endpoint) {
        return window.route(this.#baseRoute + this.#endpoint(endpoint))
    }

    #axios(method, url, data = null) {
        return window.axios({
            method: method,
            url: url,
            data: data,
        }).then(response => {
            // do something
        }).catch(error => {
            throw APIException(error)
        })
    }
}