<template>
  <div class="calls-container">
    <h1>Звонки</h1>
    <button @click="logout">Выйти</button>

    <div class="add-call">
      <h3>Добавить звонок</h3>
      <form @submit.prevent="addCall">
        <input v-model="newCall.phone" placeholder="Телефон" required>
        <input v-model="newCall.description" placeholder="Описание">
        <button type="submit">Создать</button>
      </form>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Телефон</th>
          <th>Статус</th>
          <th>Описание</th>
          <th>Действия</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="call in calls" :key="call.id">
          <td>{{ call.id }}</td>
          <td>{{ call.phone }}</td>
          <td>{{ call.status }}</td>
          <td>{{ call.description }}</td>
          <td>
            <button @click="deleteCall(call.id)">Удалить</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const calls = ref([])
const newCall = ref({ phone: '', description: '' })

const token = localStorage.getItem('token')
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`

onMounted(async () => {
  const response = await axios.get('/api/calls')
  calls.value = response.data
})

const addCall = async () => {
  const response = await axios.post('/api/calls', {
    phone: newCall.value.phone,
    description: newCall.value.description
  })
  calls.value.push(response.data)
  newCall.value = { phone: '', description: '' }
}

const deleteCall = async (id) => {
  await axios.delete(`/api/calls/${id}`)
  calls.value = calls.value.filter(c => c.id !== id)
}

const logout = () => {
  localStorage.removeItem('token')
  router.push('/login')
}
</script>

<style scoped>
.calls-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}
.add-call {
  background: white;
  padding: 15px;
  margin: 20px 0;
  border-radius: 8px;
}
table {
  width: 100%;
  background: white;
  border-collapse: collapse;
  border-radius: 8px;
  overflow: hidden;
}
th, td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #ddd;
}
th {
  background: #4CAF50;
  color: white;
}
button {
  padding: 8px 16px;
  background: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
</style>