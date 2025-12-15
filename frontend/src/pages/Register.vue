<template>
  <div class="max-w-md mx-auto mt-20">
    <h1 class="text-2xl font-bold mb-4">Register</h1>
    <form @submit.prevent="submit">
      <input v-model="name" placeholder="Name" class="border p-2 w-full mb-2"/>
      <input v-model="email" type="email" placeholder="Email" class="border p-2 w-full mb-2"/>
      <input v-model="password" type="password" placeholder="Password" class="border p-2 w-full mb-2"/>
      <input v-model="passwordConfirmation" type="password" placeholder="Confirm Password" class="border p-2 w-full mb-2"/>
      <button type="submit" class="bg-green-500 text-white p-2 w-full">Register</button>
    </form>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '../stores/auth'

const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')  // ✅ Add confirmation
const auth = useAuthStore()

const submit = async () => {
  try {
    await auth.register({
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value  // ✅ send confirmation
    })
  } catch (e) {
    alert('Registration failed: ' + (e.response?.data?.message || e.message))
  }
}
</script>
