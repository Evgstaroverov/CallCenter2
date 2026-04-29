<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <h2>Вход в систему</h2>
      <form @submit.prevent="login">
        <input v-model="email" type="email" placeholder="Email" required />
        <input v-model="password" type="password" placeholder="Пароль" required />
        <button type="submit">Войти</button>
      </form>
      <p v-if="error" class="error">{{ error }}</p>
      <p class="link">
        <router-link to="/register">Зарегистрироваться</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const email = ref('')
const password = ref('')
const error = ref('')

const login = async () => {
  try {
    const response = await axios.post('/api/login', {
      email: email.value,
      password: password.value
    })
    
    localStorage.setItem('token', response.data.access_token)
    localStorage.setItem('user', JSON.stringify(response.data.user))
    router.push('/chats') // ← После логина сразу на чаты
  } catch (err) {
    error.value = 'Ошибка входа'
  }
}
</script>

<style scoped>
.auth-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background: #f0f2f5;
}
.auth-card {
  background: #fff;
  padding: 30px;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  width: 360px;
}
h2 {
  text-align: center;
  margin-bottom: 20px;
}
input {
  width: 100%;
  padding: 10px;
  margin: 8px 0;
  border: 1px solid #ddd;
  border-radius: 4px;
}
button {
  width: 100%;
  padding: 10px;
  background: #4CAF50;
  color: #fff;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 10px;
}
.link {
  text-align: center;
  margin-top: 15px;
}
.error {
  color: red;
  margin-top: 10px;
}
</style>
