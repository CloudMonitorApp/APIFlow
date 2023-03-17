class Resource {
    /**
     * @var {Integer}
     */
    #id = null

    /**
     * @var {Route}
     */
    #route = null

    /**
     * @var {Object}
     */
    #data = {}

    /**
     * @var {API}
     */
    #api = null

    constructor(id, route, data, api) {
        this.#id = id
        this.#route = route
        this.#data = data?.data || {}
        this.#api = api
    }

    /**
     * 
     * @returns 
     */
    data() {
        return this.#data
    }

    /**
     * 
     * @param {*} property 
     * @param {*} value 
     */
    set(property, value) {
        this.#data[property] = value
    }

    /**
     * 
     * @param {*} property 
     * @returns 
     */
    get(property) {
        return this.#data[property]
    }

    /**
     * 
     */
    async refresh() {
        return this.#api.get(this.#route.show({id: this.#id})).then(response => {
            this.#data = response?.data?.data
        })
    }

    async delete(after = null, parameters = {}) {
        this.#api.delete(this.#route.destroy(Object.assign({id: this.#id}, parameters))).then(response => {
            if (after !== null)
                after(response?.data)
        })
    }

    async save(after = null) {
        if (this.#id === null)
            return this.#create(after)

        return this.#update(after)
    }

    async #create(after = null) {
        let data = { ...this.#data }

        if (data.id === null)
            delete data.id

        return this.#api.post(this.#route.store(), data).then(response => {
            let resource = new Resource(response.data.data.id, this.#route, response?.data, this.#api)

            if (after !== null)
                after(resource)

            return resource
        })
    }

    async #update(after = null) {
        return this.#api.put(this.#route.update({id: this.#id}), this.#data).then(response => {
            let resource = new Resource(response.data.data.id, this.#route, response?.data, this.#api)

            if (after !== null)
                after(resource)

            return resource
        })
    }
}

module.exports = Resource