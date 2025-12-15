import { defineStore } from 'pinia'
import api from '../api/api'
import router from '../router'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('token') || null,
        user: null,
    }),
    actions: {
        async login(data) {
            const res = await api.post('/login', data)
            this.token = res.data.token
            console.log(this.token);
            localStorage.setItem('token', this.token)
            await this.fetchUser()
            console.log(this.fetchUser,"here");
            router.push('/')
        },
        async register(data) {
            await api.post('/register', data)
            await this.login({ email: data.email, password: data.password })
        },
        async fetchUser() {
            const res = await api.get('/profile')
            this.user = res.data
        },
        logout() {
            this.token = null
            this.user = null
            localStorage.removeItem('token')
            router.push('/login')
        }
    }
})
