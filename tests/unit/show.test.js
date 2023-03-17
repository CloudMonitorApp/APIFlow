/**
 * @jest-environment jsdom
 */

const API = require('../../src/resources/js/api')
const Resource = require('../../src/resources/js/resource')
const axios = require('axios')
const MockAdapter = require('axios-mock-adapter')

let mock

beforeAll(() => {
    mock = new MockAdapter(axios)
    jest.useFakeTimers()

    window.route = function(name, parameters = {}) {
        let routes = {
            'api.users.index': '/api/users',
            'api.users.show': '/api/users/{id}',
            'api.users.destroy': '/api/users/{id}',
            'api.users.store': '/api/users',
            'api.users.update': '/api/users/{id}',
        }

        return (routes[name] + (
            Object.values(parameters).length > 0
                ? '?' + new URLSearchParams(parameters).toString()
                : ''
        )).replace('{id}', parameters.hasOwnProperty('id') ? parameters.id : '').replace('?id=1', '')
    }
})

afterEach(() => {
    mock.reset()
})

test('Can show user 1', async () => {
    let response = {
        data: {id: 1, name: 'John'}
    }

    mock.onGet(`/api/users/1`).reply(200, {data: response.data})

    let api = new API('api.users', [])
    let user = await api.show(1)
    expect(user).toBeInstanceOf(Resource)
    await api.show(1)
    expect(user.data()).toEqual(response.data)
    api.show(1, user => {
        expect(user.data()).toEqual(response.data)
    })
})

test('Can change data', async () => {
    let response = {
        data: {id: 1, name: 'John'}
    }

    mock.onGet(`/api/users/1`).reply(200, {data: response.data})

    let api = new API('api.users', [])
    let user = await api.show(1)

    user.set('name', 'James')

    expect(user.data().name).toEqual('James')
})

test('Can refresh data', async () => {
    let response = {
        data: {id: 1, name: 'John'}
    }

    mock.onGet(`/api/users/1`).reply(200, {data: response.data})

    let api = new API('api.users', [])
    let user = await api.show(1)

    user.set('name', 'James')

    expect(user.data().name).toEqual('James')

    await user.refresh()

    expect(user.get('name')).toEqual('John')
})

test('Can delete', async () => {
    let response = {data: {id: 1, name: 'John'}}

    mock.onGet(`/api/users/1`).reply(200, {data: response.data})
    mock.onDelete(`/api/users/1`).reply(200, {})

    let api = new API('api.users', [])
    let user = await api.show(1)

    user.delete(response => {
        // deleted
    })
})

test('Can create', async () => {
    let response = {data: {id: 1, name: 'John'}}

    mock.onPost(`/api/users`).reply(200, {data: response.data})

    let api = new API('api.users', [])
    let user = api.create()
    user.set('name', 'John')
    user.save(created => {
        expect(created).toBeInstanceOf(Resource)
        expect(created.get('name')).toEqual('John')
        expect(created.get('id')).toEqual(1)
    })
})

test('Can update', async () => {
    let response = {data: {id: 1, name: 'John'}}
    let response_updated = {data: {id: 1, name: 'James'}}

    mock.onGet(`/api/users/1`).reply(200, {data: response.data})
    mock.onPut(`/api/users/1`).reply(200, {data: response_updated.data})

    let api = new API('api.users', [])
    let user = await api.show(1)
    expect(user.get('name')).toEqual('John')
    user.set('name', 'James')
    user.save(updated => {
        expect(updated.get('name')).toEqual('James')
    })
})
