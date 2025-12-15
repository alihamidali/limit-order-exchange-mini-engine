import { createRouter, createWebHistory } from 'vue-router'
import Login from '../pages/Login.vue'
import Register from '../pages/Register.vue'
import Dashboard from '../pages/Dashboard.vue'
import OrderBook from '../pages/OrderBook.vue'  // import your new page

const routes = [
  { path: '/login', name: 'Login', component: Login },
  { path: '/register', name: 'Register', component: Register },
  { path: '/', component: Dashboard },
  {
    path: '/orderbook/:symbol?',
    name: 'OrderBook',
    component: OrderBook,
    props: route => ({ symbol: route.params.symbol || 'BTC' }) // optional default symbol
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

export default router
