<template>
  <div class="auth-wrapper">
    <div class="auth-card">
      <h2>Регистрация</h2>
      <form @submit.prevent="register">
        <input v-model="name" type="text" placeholder="Имя" required />
        <input v-model="email" type="email" placeholder="Email" required />
        <input v-model="password" type="password" placeholder="Пароль" required />
        <input v-model="passwordConfirmation" type="password" placeholder="Повторите пароль" required />
        <button type="submit">Зарегистрироваться</button>
      </form>
      <p v-if="error" class="error">{{ error }}</p>
      <p class="link">
        <router-link to="/login">Войти</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const name = ref('')
const email = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const error = ref('')

const register = async () => {
  try {
    await axios.post('/api/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value
    })
    
    // После регистрации сразу логинимся
    const loginRes = await axios.post('/api/login', {
      email: email.value,
      password: password.value
    })
    
    localStorage.setItem('token', loginRes.data.access_token)
    localStorage.setItem('user', JSON.stringify(loginRes.data.user))
    router.push('/chats')
  } catch (err) {
    error.value = err.response?.data?.message || 'Ошибка регистрации'
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
