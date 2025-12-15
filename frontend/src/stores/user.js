import { defineStore } from 'pinia'
import api from '../api/api'
import Pusher from 'pusher-js'
import { useOrdersStore } from './orders'
import { useAuthStore } from './auth'
import router from '../router'

export const useUserStore = defineStore('user', {
    state: () => ({
        user: null,
        pusher: null,
        channel: null,
        isConnecting: false,
        connectionError: null
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
        isPusherConnected: (state) => !!state.channel,
        userBalance: (state) => state.user?.balance || 0,
        userAssets: (state) => state.user?.assets || [],
        userId: (state) => state.user?.id || null
    },

    actions: {
        // Login
        async login(email, password) {
            try {
                const res = await api.post('/login', { email, password })

                if (res.data.success) {
                    const authStore = useAuthStore()
                    authStore.token = res.data.token

                    this.user = {
                        id: res.data.user.id,
                        balance: parseFloat(res.data.user.balance) || 0,
                        assets: res.data.user.assets || []
                    }

                    this.initPusher()
                }

                return res.data
            } catch (error) {
                console.error('Login failed:', error)
                throw error
            }
        },

        // Fetch profile (balances & assets)
        async fetchProfile() {
            try {
                console.log('🔄 Fetching profile...')
                const res = await api.get('/profile')

                if (res.data.success) {
                    const userId = res.data.user?.id || this.user?.id

                    this.user = {
                        id: userId,
                        balance: parseFloat(res.data.balance) || 0,
                        assets: res.data.assets || []
                    }

                    console.log('✅ Profile updated:', {
                        id: this.user.id,
                        balance: this.user.balance,
                        assets: this.user.assets
                    })

                    // Initialize Pusher if not yet initialized
                    if (!this.pusher && userId) {
                        this.initPusher()
                    }
                }

                return this.user
            } catch (error) {
                console.error('❌ Failed to fetch profile:', error)
                return null
            }
        },

        // Logout
        async logout() {
            try {
                await api.post('/logout')
            } catch (error) {
                console.error('Logout request failed:', error)
            } finally {
                const authStore = useAuthStore()
                authStore.token = null
                this.user = null

                this.disconnectPusher()

                const ordersStore = useOrdersStore()
                ordersStore.orders = []

                router.push({ name: 'Login' })
            }
        },

        // Initialize Pusher for real-time updates
        initPusher() {
            if (this.isConnecting || this.pusher) {
                console.log('⚠️ Pusher already initialized or connecting')
                return
            }

            if (!this.user?.id) {
                console.warn('⚠️ Cannot initialize Pusher: No user ID')
                return
            }

            const authStore = useAuthStore()
            const token = authStore.token

            if (!token) {
                console.warn('⚠️ Cannot initialize Pusher: No auth token')
                return
            }

            this.isConnecting = true
            this.connectionError = null

            try {
                console.log('🔌 Initializing Pusher...')

                this.pusher = new Pusher(import.meta.env.VITE_PUSHER_KEY, {
                    cluster: import.meta.env.VITE_PUSHER_CLUSTER,
                    forceTLS: true,
                    authEndpoint: 'http://127.0.0.1:8000/broadcasting/auth',
                    auth: {
                        headers: {
                            Authorization: `Bearer ${token}`
                        }
                    }
                })

                this.pusher.connection.bind('connected', () => {
                    console.log('✅ Pusher connected')
                })

                this.pusher.connection.bind('error', (err) => {
                    console.error('❌ Pusher connection error:', err)
                    this.connectionError = err
                })

                // CRITICAL FIX: Use correct channel name matching backend
                const channelName = `App.Models.User.${this.user.id}`
                console.log(`📡 Subscribing to channel: ${channelName}`)
                console.log(`👤 Current user ID: ${this.user.id}`)

                this.channel = this.pusher.subscribe(channelName)

                this.channel.bind('pusher:subscription_succeeded', () => {
                    console.log(`✅ Successfully subscribed to ${channelName}`)
                    this.isConnecting = false
                })

                this.channel.bind('pusher:subscription_error', (status) => {
                    console.error('❌ Subscription error:', status)
                    this.connectionError = `Subscription failed with status ${status}`
                    this.isConnecting = false
                })

                // Listen for OrderMatched events
                this.channel.bind('App\\Events\\OrderMatched', this.handleOrderMatched)

            } catch (error) {
                console.error('❌ Failed to initialize Pusher:', error)
                this.connectionError = error.message
                this.isConnecting = false
            }
        },

        // Handle OrderMatched event (centralized)
        async handleOrderMatched(data) {
            console.log('📬 OrderMatched event received:', data)
            console.log('👤 Current user ID:', this.user?.id)
            console.log('🛒 Buyer ID:', data.buyer_id)
            console.log('💰 Seller ID:', data.seller_id)

            const ordersStore = useOrdersStore()

            // Update order statuses
            ordersStore.updateOrderStatus(data.buy_order_id, 2)
            ordersStore.updateOrderStatus(data.sell_order_id, 2)

            // CRITICAL FIX: Use consistent user ID comparison
            const currentUserId = this.user?.id
            const isBuyer = currentUserId === data.buyer_id
            const isSeller = currentUserId === data.seller_id

            console.log('🔍 User involvement check:', {
                currentUserId,
                isBuyer,
                isSeller,
                isInvolved: isBuyer || isSeller
            })

            // If current user is involved in trade, refresh profile
            if (currentUserId && (isBuyer || isSeller)) {
                console.log('🔄 User is involved in trade, refreshing profile...')
                await this.fetchProfile()
                console.log('✅ Profile refresh complete')
            } else {
                console.log('ℹ️ User not involved in this trade')
            }
        },

        // Disconnect Pusher
        disconnectPusher() {
            if (this.channel) {
                this.channel.unbind_all()
                this.pusher.unsubscribe(this.channel.name)
                this.channel = null
            }

            if (this.pusher) {
                this.pusher.disconnect()
                this.pusher = null
            }

            this.isConnecting = false
            this.connectionError = null
            console.log('🔌 Pusher disconnected')
        },

        // Reconnect Pusher
        reconnectPusher() {
            console.log('🔄 Reconnecting Pusher...')
            this.disconnectPusher()
            this.initPusher()
        }
    }
})