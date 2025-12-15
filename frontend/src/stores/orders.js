import { defineStore } from 'pinia'
import api from '../api/api'

export const useOrdersStore = defineStore('orders', {
    state: () => ({
        orders: []
    }),

    actions: {
        async fetchOrders(symbol = 'BTC') {
            try {
                const res = await api.get(`/orders?symbol=${symbol}`)
                if (res.data.success) {
                    this.orders = (res.data.orders || []).map(o => ({
                        ...o,
                        status:
                            o.status === 'open' || o.status == 1 ? 1 :
                                o.status === 'filled' || o.status == 2 ? 2 :
                                    o.status === 'cancelled' || o.status == 3 ? 3 :
                                        0
                    }))
                }
            } catch (e) {
                console.error('Failed to fetch orders:', e)
                this.orders = []
            }
        },

        async placeOrder({ symbol, side, price, amount }) {
            try {
                const res = await api.post('/my-orders', {
                    symbol,
                    side,
                    price: Number(price),
                    amount: Number(amount)
                })

                // Update local store immediately
                if (res.data.success && res.data.order) {
                    this.orders.push({
                        ...res.data.order,
                        status: res.data.order.status === 'filled' ? 2 : 1
                    })
                }

                // Fetch latest orders to sync
                await this.fetchOrders(symbol)

                return res.data
            } catch (e) {
                throw e
            }
        },

        async cancelOrder(orderId) {
            try {
                const res = await api.post(`/orders/${orderId}/cancel`)
                await this.fetchOrders()
                return res.data
            } catch (e) {
                throw e
            }
        }
    }
})
