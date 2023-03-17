/**
 * @jest-environment jsdom
 */

const API = require('../../src/resources/js/api')
const axios = require('axios')
const MockAdapter = require('axios-mock-adapter')

let mock

beforeAll(() => {
    mock = new MockAdapter(axios)
    jest.useFakeTimers()
    window.route = function(name, parameters = {}) {
        let routes = {
            'api.users.index': '/api/users'
        }

        return routes[name] + (
            Object.values(parameters).length > 0
                ? '?' + new URLSearchParams(parameters).toString()
                : ''
        )
    }
})

afterEach(() => {
    mock.reset()
})

test('Can create instance', () => {
    mock.onGet(`/api/users`).reply(200, {data: []})
    expect(new API('api.users')).toBeInstanceOf(API)
})

test('Can get list of users', async () => {
    let response = {
        data: [
            {id: 1, name: 'John'}
        ]
    }

    mock.onGet(`/api/users`).reply(200, response)
    jest.advanceTimersByTime(1000)

    let api = new API('api.users')

    await api.index(users => {
        expect(users).toEqual(response.data)
    })
})

test('Can get list of users with parameters', async () => {
    let response = {
        data: [
            {id: 1, name: 'John'}
        ]
    }

    mock.onGet(`/api/users?active=true`).reply(200, response)
    jest.advanceTimersByTime(1000)

    let api = new API('api.users', [])

    await api.index(users => {
        expect(users).toEqual(response.data)
    }, {
        active: true
    })
})

test('Non standard index result', async () => {
    let response = {
        id: 1, name: 'John'
    }

    mock.onGet(`/api/users`).reply(200, response)
    jest.advanceTimersByTime(1000)

    let api = new API('api.users', [])

    await api.index(users => {
        expect(users).toEqual(undefined)
    })
})

test('Change page', async () => {
    let response_page_1 = {
        data: [
            {id: 1, name: 'John'}
        ],
        meta: {
            current_page: 1,
            from: 1,
            to: 15,
            per_page: 15,
            total: 30,
            last_page: 2
        }
    }

    let response_page_2 = {
        data: [
            {id: 2, name: 'James'}
        ],
        meta: {
            current_page: 2,
            from: 16,
            to: 30,
            per_page: 15,
            total: 30,
            last_page: 2
        }
    }

    mock.onGet(`/api/users`).reply(200, response_page_1)
    mock.onGet(`/api/users?page=1`).reply(200, response_page_1)
    mock.onGet(`/api/users?page=2`).reply(200, response_page_2)
    jest.advanceTimersByTime(1000)

    let api = new API('api.users')

    await api.index(users => {
        expect(users).toEqual(response_page_1.data)
    })

    await api.goTo(2, users => {
        expect(users).toEqual(response_page_2.data)
    })

    await api.goTo(1)
    expect(api.data()).toEqual(response_page_1.data)
})

test('Is in loading state', async () => {
    let response = {
        data: [
            {id: 1, name: 'John'}
        ]
    }

    mock.onGet(`/api/users`).reply(200, response)
    jest.advanceTimersByTime(1000)

    let api = new API('api.users', [])
    expect(api.loading()).toEqual(false)
    api.index()
    expect(api.loading()).toEqual(true)
    await api.index()
    expect(api.loading()).toEqual(false)
})

test('Can query', async () => {
    let response = {
        data: [
            {id: 1, name: 'John'}
        ]
    }

    mock.onGet(`/api/users?query=john`).reply(200, response)
    jest.advanceTimersByTime(1000)

    let api = new API('api.users', [])
    await api.query('john')
    expect(api.data()).toEqual(response.data)
    await api.query('john', result => {
        expect(result).toEqual(response.data)
    })
})

test('Can exclude ids', async () => {
    let response = {
        data: [
            {id: 1, name: 'John'}
        ]
    }

    mock.onGet(`/api/users?exclude=2%2C3`).reply(200, response)
    jest.advanceTimersByTime(1000)

    let api = new API('api.users', [])
    await api.exclude([2, 3])
    expect(api.data()).toEqual(response.data)
    await api.exclude([2, 3], result => {
        expect(result).toEqual(response.data)
    })
})

test('Can include ids only', async () => {
    let response = {
        data: [
            {id: 1, name: 'John'},
            {id: 2, name: 'James'},
        ]
    }

    mock.onGet(`/api/users?only=1%2C2`).reply(200, response)
    jest.advanceTimersByTime(1000)

    let api = new API('api.users', [])
    await api.only([1, 2])
    expect(api.data()).toEqual(response.data)
    await api.only([1, 2], result => {
        expect(result).toEqual(response.data)
    })
})