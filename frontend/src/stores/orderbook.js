import { defineStore } from 'pinia'
import api from '../api/api'
import Pusher from 'pusher-js'

export const useOrderBookStore = defineStore('orderBook', {
    state: () => ({
        buy: [],
        sell: [],
        trades: [],
        pusher: null,
        channel: null
    }),
    actions: {
        // Fetch initial order book
        async fetchOrderBook(symbol = 'BTC') {
            try {
                const res = await api.get(`/orderbook?symbol=${symbol}`)
                if (res.data.success) {
                    this.buy = res.data.buy.map(o => ({ ...o, status: o.status === 'filled' ? 2 : 1 }))
                    this.sell = res.data.sell.map(o => ({ ...o, status: o.status === 'filled' ? 2 : 1 }))
                    console.log("📊 Initial OrderBook fetched:", { buy: this.buy, sell: this.sell })
                }
            } catch (e) {
                console.error("Failed to fetch order book:", e)
            }
        },

        // Initialize Pusher for real-time updates
        initPusher(symbol = 'BTC') {
            if (!localStorage.getItem('token')) {
                console.warn("⚠️ No token found for Pusher auth")
                return
            }

            this.pusher = new Pusher(import.meta.env.VITE_PUSHER_KEY, {
                cluster: import.meta.env.VITE_PUSHER_CLUSTER,
                forceTLS: true,
                authEndpoint: "http://127.0.0.1:8000/broadcasting/auth",
                auth: { headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } }
            })

            const channelName = `orderbook.${symbol}`
            this.channel = this.pusher.subscribe(channelName)
            console.log("🔔 Pusher initialized for symbol:", symbol)
            console.log("Subscribed to channel:", channelName)

            this.channel.bind("App\\Events\\OrderMatched", (data) => {
                console.log("📬 OrderMatched event received in store:", data)

                // Update Buy Orders
                this.buy = this.buy.map(o => {
                    if (o.id === data.buy_order_id) {
                        console.log(`💚 Updating Buy Order ID ${o.id} to status ${data.buy_order_status}`)
                        return { ...o, status: data.buy_order_status === 'filled' ? 2 : o.status }
                    }
                    return o
                })

                // Update Sell Orders
                this.sell = this.sell.map(o => {
                    if (o.id === data.sell_order_id) {
                        console.log(`❤️ Updating Sell Order ID ${o.id} to status ${data.sell_order_status}`)
                        return { ...o, status: data.sell_order_status === 'filled' ? 2 : o.status }
                    }
                    return o
                })

                // Add trade to Recent Trades
                this.trades.unshift(data)
                console.log("📈 Trades array updated:", this.trades)
            })

            this.pusher.connection.bind('connected', () => {
                console.log("✅ Pusher connected successfully")
            })
        }
    }
})
