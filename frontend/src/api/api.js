import axios from 'axios'

const api = axios.create({
    baseURL: 'http://127.0.0.1:8000/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
})

// Request interceptor - add auth token
api.interceptors.request.use(
    config => {
        // Get token from localStorage directly to avoid circular dependency
        const token = localStorage.getItem('token')

        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }

        console.log(`🌐 API Request: ${config.method.toUpperCase()} ${config.url}`)
        return config
    },
    error => {
        console.error('❌ API Request Error:', error)
        return Promise.reject(error)
    }
)

// Response interceptor - handle errors
api.interceptors.response.use(
    response => {
        console.log(`✅ API Response: ${response.config.method.toUpperCase()} ${response.config.url}`, response.data)
        return response
    },
    error => {
        console.error('❌ API Response Error:', error.response?.data || error.message)

        // Handle 401 Unauthorized
        if (error.response?.status === 401) {
            localStorage.removeItem('token')
            window.location.href = '/login'
        }

        return Promise.reject(error)
    }
)

export default api