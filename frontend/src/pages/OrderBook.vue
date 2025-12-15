<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Header with Symbol and Dashboard Button -->
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-3xl font-bold">OrderBook - {{ symbol }}</h2>
      <button
          @click="goToDashboard"
          class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
      >
        Dashboard
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

      <!-- Buy Orders Table -->
      <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-green-200">
        <div class="px-4 py-2 bg-green-100 border-b">
          <h3 class="font-semibold text-green-800">Buy Orders</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-green-50 sticky top-0">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-green-700 uppercase">Amount (BTC)</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-green-700 uppercase">Price (USD)</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-green-700 uppercase">Status</th>
          </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
          <tr v-for="order in buyOrders" :key="order.id" class="hover:bg-green-50 transition-colors">
            <td class="px-4 py-2 font-medium">{{ order.amount }}</td>
            <td class="px-4 py-2 font-medium">{{ order.price }}</td>
            <td class="px-4 py-2">
              <span v-if="order.status === 2" class="text-green-600 font-semibold">Filled</span>
              <span v-else class="text-gray-600">Open</span>
            </td>
          </tr>
          <tr v-if="buyOrders.length === 0">
            <td colspan="3" class="text-center p-4 text-gray-400">No Buy Orders</td>
          </tr>
          </tbody>
        </table>
      </div>

      <!-- Sell Orders Table -->
      <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-red-200">
        <div class="px-4 py-2 bg-red-100 border-b">
          <h3 class="font-semibold text-red-800">Sell Orders</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-red-50 sticky top-0">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-red-700 uppercase">Amount (BTC)</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-red-700 uppercase">Price (USD)</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-red-700 uppercase">Status</th>
          </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
          <tr v-for="order in sellOrders" :key="order.id" class="hover:bg-red-50 transition-colors">
            <td class="px-4 py-2 font-medium">{{ order.amount }}</td>
            <td class="px-4 py-2 font-medium">{{ order.price }}</td>
            <td class="px-4 py-2">
              <span v-if="order.status === 2" class="text-red-600 font-semibold">Filled</span>
              <span v-else class="text-gray-600">Open</span>
            </td>
          </tr>
          <tr v-if="sellOrders.length === 0">
            <td colspan="3" class="text-center p-4 text-gray-400">No Sell Orders</td>
          </tr>
          </tbody>
        </table>
      </div>

      <!-- Recent Trades Table -->
      <div class="bg-white shadow-lg rounded-lg overflow-hidden border border-blue-200">
        <div class="px-4 py-2 bg-blue-100 border-b">
          <h3 class="font-semibold text-blue-800">Recent Trades</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-blue-50 sticky top-0">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-blue-700 uppercase">Amount (BTC)</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-blue-700 uppercase">Price (USD)</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-blue-700 uppercase">Type</th>
          </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
          <tr v-for="trade in trades" :key="trade.trade_id" class="hover:bg-blue-50 transition-colors">
            <td class="px-4 py-2 font-medium">{{ trade.amount }}</td>
            <td class="px-4 py-2 font-medium">{{ trade.price }}</td>
            <td class="px-4 py-2">
                <span :class="trade.buyer_id === userId ? 'text-green-600 font-semibold' : 'text-red-600 font-semibold'">
                  {{ trade.buyer_id === userId ? 'Buy' : 'Sell' }}
                </span>
            </td>
          </tr>
          <tr v-if="trades.length === 0">
            <td colspan="3" class="text-center p-4 text-gray-400">No Trades Yet</td>
          </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useOrderBookStore } from '../stores/orderbook'
import { useUserStore } from '../stores/user'
import { useRouter } from 'vue-router'

const props = defineProps({
  symbol: { type: String, default: 'BTC' }
})

const router = useRouter()
const orderBookStore = useOrderBookStore()
const userStore = useUserStore()

const buyOrders = ref([])
const sellOrders = ref([])
const trades = ref([])
const userId = userStore.user?.id || null

onMounted(async () => {
  console.log("🌐 Fetching order book for", props.symbol)
  await orderBookStore.fetchOrderBook(props.symbol)
  orderBookStore.initPusher(props.symbol)
})

// Watch store changes
watch(() => orderBookStore.buy, (val) => buyOrders.value = val, { deep: true })
watch(() => orderBookStore.sell, (val) => sellOrders.value = val, { deep: true })
watch(() => orderBookStore.trades, (val) => trades.value = val, { deep: true })

// Navigate back to dashboard
const goToDashboard = () => {
  router.push({ path: '/' })
}
</script>

<style scoped>
tbody tr:nth-child(even) {
  background-color: #f9fafb;
}
tbody tr:hover {
  cursor: pointer;
  background-color: #f1f5f9;
  transition: background-color 0.2s;
}
</style>
