const axios = require('axios')
const Route = require('./Route')
const Resource = require('./Resource')
// import Collection from './Collection.js'
import APIException from './APIException.js'
// import AxiosException from './AxiosException.js'

class API {
    /**
     * Last output data.
     * 
     * @var {Object}
     */
    #data = null

    /**
     * 
     */
    #meta = null

    /**
     * 
     */
    #parameters = {}

    /**
    * Current loading state.
    * 
    * @var {Boolean}
    */
    #loading = false

    /**
     * @var {Route}
     */
    #route = null

    /**
     * 
     */
    #debounce = null

    /**
     * 
     */
    // #mapBefore = null

    /**
     * 
     */
    // #saving = false

    /**
     * Default endpoints.
     * 
     * @var {Object}
     */
    #endpoints = {
        index: 'index',
        destroy: 'destroy',
        show: 'show',
        store: 'store',
        update: 'update',
    }

    // #callback = () => {}

    /**
     * 
     */
    constructor(route, data = null, parameters = {}, meta = {}) {
        this.#meta = meta
        this.#data = data
        this.#parameters = parameters

        this.#route = new Route(route)

        if (this.#data === null) {
            this.index()
        }
    }

    /**
     * Data returned from last execution.
     * 
     * @return {Object}
     */
    data() {
        return this.#data
    }

    /**
     * 
     */
    meta() {
        return this.#meta
    }

    /**
     * 
     */
    parameters(parameters) {
        this.#parameters = parameters
    }

    /**
     * Call API on index entrypoint.
     * 
     * @param {Function} after 
     * @param {Object} parameters 
     * @returns {Promise}
     */
    async index(after = null, parameters = {}) {
        return this.get(this.#route.index(Object.assign(parameters, this.#parameters))).then(response => {
            this.#meta = response.data?.meta
            this.#data = response.data?.data

            if (after !== null) {
                after(this.#data)
            }
        })
    }

    /**
     * 
     */
    async show(id, after = null, parameters = {}) {
        parameters = Object.assign(parameters, {id: id})

        return this.get(this.#route.show(Object.assign(parameters, this.#parameters))).then(response => {
            let resource = new Resource(id, this.#route, response?.data, this)

            if (after !== null)
                after(resource)

            return resource
        })
    }

    create() {
        return new Resource(null, this.#route, {}, this)
    }

    /**
     * 
     */
    /*create(paramaters = {}, data = {}, callback = null) {
        // return this.#call('post', 'store', paramaters, data, callback)
    }*/

    /**
     * 
     */
    /*update(parameters = {}, data = {}, callback = null) {
        // return this.#call('put', 'update', parameters, data, callback)
    }*/

    /**
     * 
     */
    /*delete(id, parameters = {}, callback = null) {
        // return this.#call('delete', 'destroy', Object.assign(parameters, {id: id}), null, callback)
    }*/

    /**
     * 
     */
    /*#call(method, endpoint, parameters = {}, data = null, callback = null) {
        let _parameters = this.#parameters
        clearTimeout(this.#debounce)
        this.#loading = true
        Object.assign(this.#parameters, parameters)

        this.#debounce = setTimeout(() => {
            this.#axios(method, this.#route(endpoint, this.#parameters)).then(response => {
                if (this.#mapBefore) {
                    response.data.data = this.#mapBefore(response.data.data)
                }

                this.#data = response.data.data
                this.#meta = response.data.meta
                this.#loading = false

                if (callback) {
                    callback(response)
                }

                return response.data
            })
        }, this.#debounce ? 1000 : 0)

        this.setParameters(_parameters)
        // return this.data()
    }*/

    /**
     * Return current loading state.
     * 
     * @returns {Boolean}
     */
    loading() {
        return this.#loading
    }

    /**
     * 
     */
    /*saving() {
        return this.#saving
    }*/

    /**
     * 
     */
    async query(keyword = '', after = null, parameters = {}) {
        await this.index(response => {
            if (after !== null)
                after(response)
        }, Object.assign({query: keyword}, parameters))
    }

    /**
     * 
     * @param {*} ids 
     * @returns 
     */
    async exclude(ids = [], after = null, parameters = {}) {
        await this.index(response => {
            if (after !== null)
            after(response)
        }, Object.assign({exclude: ids.join(',')}, parameters))
    }

    /**
     * 
     * @param {*} ids 
     * @returns 
     */
    async only(ids = [], after = null, parameters = {}) {
        await this.index(response => {
            if (after !== null)
            after(response)
        }, Object.assign({only: ids.join(',')}, parameters))
    }

    /**
     * 
     * @param {*} pageNumber 
     */
    async goTo(pageNumber, after = null, parameters = {}) {
        await this.index(response => {
            if (after !== null)
                after(response)
        }, Object.assign({page: pageNumber}, parameters))
    }

    async goToLast(after = null, parameters = {}) {
        await this.index(response => {
            if (after !== null)
                after(response)
        }, Object.assign({page: this.#meta.last_page}, parameters))
    }

    async goToFirst(after = null, parameters = {}) {
        await this.index(response => {
            if (after !== null)
                after(response)
        }, Object.assign({page: 1}, parameters))
    }

    /**
     * 
     * @param {*} data 
     * @param {*} update 
     * @param {*} id 
     * @param {*} params 
     * @returns 
     */
    /*save(data = {}, update = false, id = {}, params = {}) {
        update ? this.update(Object.assign(id, params), data) : this.post(params, data)
        return this
    }*/

    /**
     * 
     */
    /*onSuccess(callback) {
        this.#callback = callback
        return this
    }*/

    /**
     * 
     * @param {*} callback 
     * @returns 
     */
    /*mapBefore(callback) {
        this.#mapBefore = callback
        return this
    }*/

    async get(url) {
        return this.#axios('GET', url)
    }

    async delete(url) {
        return this.#axios('DELETE', url)
    }

    async post(url, data) {
        return this.#axios('POST', url, data)
    }

    async put(url, data) {
        return this.#axios('PUT', url, data)
    }

    /**
     * 
     */
    async #axios(method, url, data = null) {
        /*let axios = window.axios ?? axios.create({
            httpsAgent: new https.Agent(this.config)
        })*/
        this.#loading = true

        return axios({
            method: method,
            url: url,
            data: data,
        }).then(response => {
            return response
        }).catch(error => {
            throw new APIException(error)
        }).finally(() => {
            this.#loading = false
        })
    }
}

module.exports = API
