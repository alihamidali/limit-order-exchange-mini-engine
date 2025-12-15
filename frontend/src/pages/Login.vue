<template>
  <div class="max-w-md mx-auto mt-20">
    <h1 class="text-2xl font-bold mb-4">Login</h1>
    <form @submit.prevent="submit">
      <input v-model="email" type="email" placeholder="Email" class="border p-2 w-full mb-2"/>
      <input v-model="password" type="password" placeholder="Password" class="border p-2 w-full mb-2"/>
      <button type="submit" class="bg-blue-500 text-white p-2 w-full">Login</button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'
import { reactive } from 'vue'

const email = ref('')
const password = ref('')
const auth = useAuthStore()

const toast = reactive({
  show: false,
  type: 'success', // 'success' or 'error'
  title: '',
  message: ''
})

let toastTimeout = null

const showToast = (type, title, message, duration = 4000) => {
  if (toastTimeout) clearTimeout(toastTimeout)
  toast.show = true
  toast.type = type
  toast.title = title
  toast.message = message
  toastTimeout = setTimeout(() => {
    toast.show = false
  }, duration)
}

const submit = async () => {
  try {
    await auth.login({ email: email.value, password: password.value })
    showToast('success', 'Login Successful', `Welcome back, ${email.value}!`)
  } catch (e) {
    showToast('error', 'Login Failed', e?.response?.data?.message || 'Invalid credentials')
  }
}
</script>
