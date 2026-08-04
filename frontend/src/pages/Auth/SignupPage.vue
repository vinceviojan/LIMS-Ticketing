<template>
    <div>
        <h1>Signup</h1>
        <p>{{ message }}</p>
        <q-btn label="Login" @click="login" />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../../boot/axios'

const router = useRouter()

const message = ref('Loading...')

const login = () => {
  router.push('/login')
}

const ping = async () => {
  try {
    const response = await api.get('/ping')
    message.value = response.data.message
  } catch (error) {
    message.value = 'Failed to connect.'
    console.error(error)
  }
}

onMounted(() => {
  ping()
})
</script>