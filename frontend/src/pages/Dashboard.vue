<template>
  <div class="p-6 max-w-7xl mx-auto">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold">Dashboard</h1>
      <button
          @click="logoutAndRedirect"
          class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition"
      >
        Logout
      </button>
    </div>

    <!-- Pusher Status -->
    <div v-if="userStore.connectionError" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
      ⚠️ Real-time connection error: {{ userStore.connectionError }}
      <button @click="userStore.reconnectPusher()" class="ml-2 underline">Reconnect</button>
    </div>
    <div v-else-if="userStore.isPusherConnected" class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
      ✅ Real-time updates active
    </div>

    <!-- Wallet Overview -->
    <section class="mb-6 p-4 border rounded bg-white shadow">
      <h2 class="text-xl font-semibold mb-4">Wallet</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-gray-50 rounded shadow-sm">
          <p class="text-sm text-gray-600 mb-1">USD Balance</p>
          <p v-if="user" class="text-2xl font-bold">${{ user.balance.toFixed(2) }}</p>
          <p v-else class="text-gray-400">Loading...</p>
        </div>
        <div class="p-4 bg-gray-50 rounded shadow-sm">
          <p class="text-sm text-gray-600 mb-2">Crypto Assets</p>
          <div v-if="user?.assets?.length" class="space-y-2">
            <div v-for="asset in user.assets" :key="asset.symbol" class="text-sm">
              <p class="font-semibold">{{ asset.symbol }}</p>
              <p class="text-gray-600">
                Total: <span class="font-medium">{{ formatAmount(asset.amount) }}</span> |
                Locked: <span class="font-medium">{{ formatAmount(asset.locked_amount) }}</span> |
                Available: <span class="font-medium text-green-600">{{ formatAmount(asset.amount - asset.locked_amount) }}</span>
              </p>
            </div>
          </div>
          <div v-else class="text-gray-400 text-sm">No assets yet</div>
        </div>
      </div>
    </section>

    <!-- Limit Order Form -->
    <section class="mb-6 p-4 border rounded bg-white shadow">
      <h2 class="text-xl font-semibold mb-4">Place Limit Order</h2>
      <form @submit.prevent="submitOrder" class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
        <div>
          <label class="block text-sm font-medium mb-1">Symbol</label>
          <select v-model="orderForm.symbol" class="w-full border p-2 rounded">
            <option value="BTC">BTC</option>
            <option value="ETH">ETH</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Side</label>
          <select v-model="orderForm.side" class="w-full border p-2 rounded">
            <option value="buy">BUY</option>
            <option value="sell">SELL</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Price (USD)</label>
          <input
              v-model.number="orderForm.price"
              type="number"
              step="0.01"
              min="0.01"
              placeholder="0.00"
              class="w-full border p-2 rounded"
              required
          />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Amount</label>
          <input
              v-model.number="orderForm.amount"
              type="number"
              step="0.00000001"
              min="0.00000001"
              placeholder="0.00000000"
              class="w-full border p-2 rounded"
              required
          />
        </div>
        <div class="md:col-span-2">
          <p v-if="orderForm.price && orderForm.amount" class="text-sm text-gray-600 mb-2">
            Total: ${{ (orderForm.price * orderForm.amount).toFixed(2) }}
            <span class="text-xs">(+ 1.5% commission)</span>
          </p>
          <p v-if="error" class="text-red-500 text-sm mb-2">{{ error }}</p>
          <button
              type="submit"
              :disabled="isSubmitting"
              class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
          >
            {{ isSubmitting ? 'Placing Order...' : 'Place Order' }}
          </button>
        </div>
      </form>
    </section>

    <!-- Orders Table with Pagination -->
    <section class="p-4 border rounded bg-white shadow mb-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold">Your Orders</h2>
        <button
            @click="refreshOrders"
            class="text-sm bg-gray-200 px-3 py-1 rounded hover:bg-gray-300 transition"
            :disabled="isRefreshing"
        >
          {{ isRefreshing ? 'Refreshing...' : '🔄 Refresh' }}
        </button>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full table-auto border-collapse">
          <thead class="bg-gray-50 sticky top-0">
          <tr class="border-b">
            <th class="p-3 text-left text-sm font-semibold">ID</th>
            <th class="p-3 text-left text-sm font-semibold">Symbol</th>
            <th class="p-3 text-left text-sm font-semibold">Side</th>
            <th class="p-3 text-left text-sm font-semibold">Price</th>
            <th class="p-3 text-left text-sm font-semibold">Amount</th>
            <th class="p-3 text-left text-sm font-semibold">Total</th>
            <th class="p-3 text-left text-sm font-semibold">Status</th>
            <th class="p-3 text-left text-sm font-semibold">Actions</th>
          </tr>
          </thead>
          <tbody>
          <tr
              v-for="order in paginatedOrders"
              :key="order.id"
              class="border-b hover:bg-gray-50 transition"
              :class="getOrderRowClass(order)"
          >
            <td class="p-3 text-sm">{{ order.id }}</td>
            <td class="p-3 text-sm font-medium">{{ order.symbol }}</td>
            <td class="p-3 text-sm">
                <span :class="order.side === 'buy' ? 'text-green-600' : 'text-red-600'" class="font-semibold uppercase">
                  {{ order.side }}
                </span>
            </td>
            <td class="p-3 text-sm">${{ parseFloat(order.price).toFixed(2) }}</td>
            <td class="p-3 text-sm">{{ formatAmount(order.amount) }}</td>
            <td class="p-3 text-sm font-medium">${{ (parseFloat(order.price) * parseFloat(order.amount)).toFixed(2) }}</td>
            <td class="p-3 text-sm">
                <span :class="getStatusBadgeClass(order.status)" class="px-2 py-1 rounded text-xs font-medium">
                  {{ getStatusText(order.status) }}
                </span>
            </td>
            <td class="p-3 text-sm">
              <button
                  v-if="order.status === 1"
                  @click="cancelOrder(order.id)"
                  :disabled="cancellingOrders.has(order.id)"
                  class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition text-xs disabled:bg-gray-400"
              >
                {{ cancellingOrders.has(order.id) ? 'Cancelling...' : 'Cancel' }}
              </button>
              <span v-else class="text-gray-400 text-xs">—</span>
            </td>
          </tr>
          <tr v-if="orders.length === 0">
            <td colspan="8" class="text-center p-6 text-gray-400">No orders found</td>
          </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Controls -->
      <div class="flex justify-between items-center mt-4">
        <div>
          <button
              @click="prevPage"
              :disabled="currentPage === 1"
              class="px-3 py-1 border rounded hover:bg-gray-100 transition disabled:opacity-50"
          >
            Previous
          </button>
          <button
              @click="nextPage"
              :disabled="currentPage === totalPages"
              class="ml-2 px-3 py-1 border rounded hover:bg-gray-100 transition disabled:opacity-50"
          >
            Next
          </button>
        </div>
        <div class="text-sm text-gray-600">
          Page {{ currentPage }} of {{ totalPages }}
        </div>
      </div>
    </section>

    <!-- Embedded OrderBook Section -->
    <section class="p-4 border rounded bg-white shadow mb-6">
      <h2 class="text-xl font-semibold mb-4">OrderBook - {{ orderForm.symbol }}</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Buy Orders -->
        <div class="bg-green-50 p-2 rounded border border-green-200">
          <h3 class="font-semibold text-green-700 mb-2">Buy Orders</h3>
          <ul>
            <li v-for="order in orderBookStore.buy" :key="order.id" class="flex justify-between px-2 py-1 border-b">
              <span>{{ order.amount }}</span>
              <span>{{ order.price }}</span>
              <span :class="order.status === 2 ? 'text-green-600' : 'text-gray-600'">
                {{ order.status === 2 ? 'Filled' : 'Open' }}
              </span>
            </li>
          </ul>
        </div>

        <!-- Sell Orders -->
        <div class="bg-red-50 p-2 rounded border border-red-200">
          <h3 class="font-semibold text-red-700 mb-2">Sell Orders</h3>
          <ul>
            <li v-for="order in orderBookStore.sell" :key="order.id" class="flex justify-between px-2 py-1 border-b">
              <span>{{ order.amount }}</span>
              <span>{{ order.price }}</span>
              <span :class="order.status === 2 ? 'text-red-600' : 'text-gray-600'">
                {{ order.status === 2 ? 'Filled' : 'Open' }}
              </span>
            </li>
          </ul>
        </div>

        <!-- Recent Trades -->
        <div class="bg-blue-50 p-2 rounded border border-blue-200">
          <h3 class="font-semibold text-blue-700 mb-2">Recent Trades</h3>
          <ul>
            <li v-for="trade in orderBookStore.trades" :key="trade.trade_id" class="flex justify-between px-2 py-1 border-b">
              <span>{{ trade.amount }}</span>
              <span>{{ trade.price }}</span>
              <span :class="trade.buyer_id === userStore.user.id ? 'text-green-600' : 'text-red-600'">
                {{ trade.buyer_id === userStore.user.id ? 'Buy' : 'Sell' }}
              </span>
            </li>
          </ul>
        </div>

      </div>
    </section>

    <!-- Toast Notifications -->
    <Transition name="toast">
      <div
          v-if="toast.show"
          class="fixed bottom-4 right-4 px-6 py-4 rounded-lg shadow-lg text-white max-w-md"
          :class="toast.type === 'success' ? 'bg-green-500' : 'bg-red-500'"
      >
        <div class="flex items-start">
          <span class="text-xl mr-3">{{ toast.type === 'success' ? '✅' : '❌' }}</span>
          <div>
            <p class="font-semibold">{{ toast.title }}</p>
            <p class="text-sm opacity-90">{{ toast.message }}</p>
          </div>
        </div>
      </div>
    </Transition>

  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeUnmount, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useUserStore } from '../stores/user'
import { useOrdersStore } from '../stores/orders'
import { useOrderBookStore } from '../stores/orderbook'

const userStore = useUserStore()
const ordersStore = useOrdersStore()
const orderBookStore = useOrderBookStore()
const { user } = storeToRefs(userStore)
const { orders } = storeToRefs(ordersStore)

const orderForm = reactive({ symbol:'BTC', side:'buy', price:0, amount:0 })
const error = ref(''), isSubmitting=ref(false), isRefreshing=ref(false)
const cancellingOrders = ref(new Set())
const toast = reactive({ show:false, type:'success', title:'', message:'' })
let toastTimeout = null, eventHandler = null

// Pagination
const currentPage = ref(1)
const perPage = 10
const totalPages = computed(()=>Math.ceil(orders.value.length/perPage))
const paginatedOrders = computed(()=>orders.value.slice((currentPage.value-1)*perPage, currentPage.value*perPage))
const nextPage = ()=>{ if(currentPage.value<totalPages.value) currentPage.value++ }
const prevPage = ()=>{ if(currentPage.value>1) currentPage.value-- }

// Helpers
const formatAmount = amount=>parseFloat(amount).toFixed(8)
const getStatusText=status=>({1:'Open',2:'Filled',3:'Cancelled'}[status]||'Unknown')
const getStatusBadgeClass=status=>({1:'bg-blue-100 text-blue-800',2:'bg-green-100 text-green-800',3:'bg-gray-100 text-gray-800'}[status]||'bg-gray-100 text-gray-800')
const getOrderRowClass=order=>order.status===2?'bg-green-50':order.status===3?'bg-gray-50':''

const showToast=(type,title,message,duration=4000)=>{
  if(toastTimeout) clearTimeout(toastTimeout)
  toast.show=true; toast.type=type; toast.title=title; toast.message=message
  toastTimeout=setTimeout(()=>toast.show=false,duration)
}

// Actions
const submitOrder=async()=>{
  error.value=''; isSubmitting.value=true
  try{
    await ordersStore.placeOrder({ symbol:orderForm.symbol, side:orderForm.side, price:Number(orderForm.price), amount:Number(orderForm.amount) })
    showToast('success','Order Placed',`${orderForm.side.toUpperCase()} order for ${orderForm.symbol} placed`)
    orderForm.price=0; orderForm.amount=0
  }catch(e){ showToast('error','Order Failed', e?.response?.data?.message||'Failed'); error.value=e?.response?.data?.message||'Failed' }
  finally{ isSubmitting.value=false }
}

const cancelOrder=async(id)=>{
  cancellingOrders.value.add(id)
  try{ await ordersStore.cancelOrder(id); showToast('success','Order Cancelled',`Order #${id} cancelled`) }
  catch(e){ showToast('error','Cancellation Failed', e?.response?.data?.message||'Failed') }
  finally{ cancellingOrders.value.delete(id) }
}

const refreshOrders=async()=>{
  isRefreshing.value=true
  try{ await ordersStore.fetchOrders(orderForm.symbol); await userStore.fetchProfile() } catch(e){console.error(e)}
  finally{isRefreshing.value=false}
}

const logoutAndRedirect=async()=>{ await userStore.logout() }

onMounted(async()=>{
  await userStore.fetchProfile()
  await ordersStore.fetchOrders(orderForm.symbol)
  await orderBookStore.fetchOrderBook(orderForm.symbol)
  orderBookStore.initPusher(orderForm.symbol)

  if(userStore.channel){
    eventHandler=data=>{
      if(user.value && (user.value.id===data.buyer_id || user.value.id===data.seller_id)){
        const isBuyer=user.value.id===data.buyer_id
        showToast('success','🎉 Trade Executed!',
            `Your ${isBuyer?'BUY':'SELL'} order #${isBuyer?data.buy_order_id:data.sell_order_id} matched at $${parseFloat(data.price).toFixed(2)}`
        )
      }
    }
    userStore.channel.bind('App\\Events\\OrderMatched', eventHandler)
  }
})

onBeforeUnmount(()=>{
  if(userStore.channel && eventHandler) userStore.channel.unbind('App\\Events\\OrderMatched', eventHandler)
  if(toastTimeout) clearTimeout(toastTimeout)
})
</script>

<style scoped>
.toast-enter-active,.toast-leave-active{transition:all 0.3s ease}
.toast-enter-from{transform:translateX(100%);opacity:0}
.toast-leave-to{transform:translateX(100%);opacity:0}
</style>
