<template>
  <div class="chats-layout">
    <!-- Левая панель: список чатов -->
    <div class="sidebar">
    <div class="sidebar-header">
       <h2 class="sidebar-title">Чаты</h2>
       <button @click="logout" class="btn-logout">Выход</button>
      </div>
      <div 
        v-for="chat in chats" 
        :key="chat.chat_id"
        @click="selectChat(chat)"
        :class="['chat-card', { active: selectedChat && selectedChat.chat_id === chat.chat_id }]"
      >
        <div class="chat-card-name">
          {{ chat.first_name || chat.username || 'Пользователь' }}
          <span v-if="chat.assigned_user" class="tag">👤 {{ chat.assigned_user }}</span>
        </div>
        <div class="chat-card-meta">
          <span v-if="chat.unread_count" class="badge">{{ chat.unread_count }}</span>
          <small>{{ formatDate(chat.last_message_at) }}</small>
        </div>
      </div>
    </div>

    <!-- Правая часть: переписка -->
    <div class="main" v-if="selectedChat">
      <!-- Заголовок чата -->
      <div class="topbar">
        <h3>{{ selectedChat.first_name || selectedChat.username }}</h3>
        <div class="topbar-actions">
          <span v-if="assignedUser" class="assigned-label">Взял: {{ assignedUser }}</span>
          <button v-if="!assignedTo" @click="assignToMe" class="btn btn-blue">Взять в работу</button>
          <button v-if="assignedTo" @click="releaseChat" class="btn btn-red">Освободить</button>
        </div>
      </div>

      <!-- Баннер только для чтения -->
      <div v-if="!canReply && assignedTo" class="banner">
        🔒 Чат уже взят. Вы можете только просматривать.
      </div>

      <!-- Сообщения -->
      <div class="messages" ref="messagesContainer">
        <div 
          v-for="msg in messages" 
          :key="msg.id"
          :class="['msg', msg.is_replied ? 'out' : 'in']"
        >
          <div class="bubble">{{ msg.text }}</div>
          <div class="time">{{ formatTime(msg.received_at) }}</div>
        </div>
      </div>

      <!-- Поле ввода -->
      <div class="input-area" v-if="canReply">
        <input 
          v-model="replyText" 
          @keyup.enter="sendReply" 
          placeholder="Написать ответ..."
          class="input"
        />
        <button @click="sendReply" class="btn btn-green">Отправить</button>
      </div>
      <div class="input-area disabled" v-else>
        <input disabled placeholder="Нет доступа" class="input" />
        <button disabled class="btn btn-gray">Отправить</button>
      </div>
    </div>

    <!-- Пустое состояние -->
    <div class="main empty" v-else>
      <p>← Выберите чат слева</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const chats = ref([])
const selectedChat = ref(null)
const messages = ref([])
const replyText = ref('')
const messagesContainer = ref(null)
const assignedTo = ref(null)
const assignedUser = ref(null)
const canReply = ref(true)
let pollTimer = null

const token = localStorage.getItem('token')
axios.defaults.headers.common['Authorization'] = `Bearer ${token}`


const logout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
}

// === Загрузка данных ===
const fetchChats = async () => {
  try {
    const res = await axios.get('/api/chats')
    chats.value = res.data || []
  } catch (e) {
    console.error(e)
  }
}

const fetchMessages = async (chatId) => {
  if (!chatId) return
  try {
    const res = await axios.get(`/api/chats/${chatId}/messages`)
    const data = res.data

    if (data && Array.isArray(data.messages)) {
      messages.value = [...data.messages].reverse()
      assignedTo.value = data.assigned_to || null
      assignedUser.value = data.assigned_user || null
      canReply.value = data.can_reply !== undefined ? data.can_reply : true
    } else if (Array.isArray(data)) {
      messages.value = [...data].reverse()
      assignedTo.value = null
      assignedUser.value = null
      canReply.value = true
    } else {
      messages.value = []
    }
    await nextTick()
    scrollDown()
  } catch (e) {
    console.error(e)
    messages.value = []
  }
}

// === Действия ===
const selectChat = (chat) => {
  selectedChat.value = chat
  fetchMessages(chat.chat_id)
}

const assignToMe = async () => {
  await axios.post(`/api/chats/${selectedChat.value.chat_id}/assign`)
  fetchMessages(selectedChat.value.chat_id)
  fetchChats()
}

const releaseChat = async () => {
  await axios.post(`/api/chats/${selectedChat.value.chat_id}/release`)
  fetchMessages(selectedChat.value.chat_id)
  fetchChats()
}

const sendReply = async () => {
  const text = replyText.value.trim()
  if (!text) return

  const last = messages.value[messages.value.length - 1]
  try {
    await axios.post(`/api/chats/${selectedChat.value.chat_id}/reply`, {
      text: text,
      reply_to_message_id: last?.message_id
    })
    replyText.value = ''
    fetchMessages(selectedChat.value.chat_id)
    fetchChats()
  } catch (e) {
    if (e.response?.status === 403) {
      alert(e.response.data.error || 'Нет доступа')
      fetchMessages(selectedChat.value.chat_id)
    }
  }
}

const scrollDown = () => {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

// === Периодический опрос ===
onMounted(() => {
  fetchChats()
  pollTimer = setInterval(() => {
    fetchChats()
    if (selectedChat.value) {
      fetchMessages(selectedChat.value.chat_id)
    }
  }, 3000)
})

onUnmounted(() => {
  clearInterval(pollTimer)
})

// === Форматирование ===
const formatDate = (d) => new Date(d).toLocaleString('ru-RU')
const formatTime = (d) => new Date(d).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
</script>

<style>
/* Глобальные сбросы */
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
</style>

<style scoped>
.chats-layout {
  display: flex;
  height: 100vh;
  background: #e5ddd5;
}

/* === Сайдбар === */
.sidebar {
  width: 320px;
  background: #fff;
  border-right: 1px solid #ddd;
  overflow-y: auto;
}
.sidebar-title {
  padding: 16px;
  font-size: 18px;
  border-bottom: 1px solid #eee;
}
.chat-card {
  padding: 14px 16px;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
}
.chat-card:hover, .chat-card.active {
  background: #e3f2fd;
}
.chat-card-name {
  font-weight: 600;
  display: flex;
  justify-content: space-between;
}
.tag {
  font-size: 11px;
  color: #e67e22;
}
.chat-card-meta {
  display: flex;
  justify-content: space-between;
  margin-top: 4px;
}
.badge {
  background: #4caf50;
  color: #fff;
  border-radius: 10px;
  padding: 1px 8px;
  font-size: 11px;
}

/* === Основная область === */
.main {
  flex: 1;
  display: flex;
  flex-direction: column;
}
.main.empty {
  justify-content: center;
  align-items: center;
  color: #888;
  font-size: 16px;
}

.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 20px;
  background: #f0f0f0;
  border-bottom: 1px solid #ddd;
}
.topbar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}
.assigned-label {
  font-size: 13px;
  color: #555;
}

/* === Баннер === */
.banner {
  padding: 8px 20px;
  background: #fff3cd;
  border-bottom: 1px solid #ffc107;
  font-size: 13px;
  text-align: center;
}

/* === Сообщения === */
.messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  display: flex;
  flex-direction: column;
}
.msg {
  max-width: 70%;
  margin-bottom: 6px;
}
.msg.in {
  align-self: flex-start;
}
.msg.out {
  align-self: flex-end;
}
.bubble {
  padding: 10px 14px;
  border-radius: 12px;
  font-size: 15px;
  line-height: 1.4;
}
.in .bubble {
  background: #fff;
  border-bottom-left-radius: 4px;
}
.out .bubble {
  background: #dcf8c6;
  border-bottom-right-radius: 4px;
}
.time {
  font-size: 11px;
  color: #999;
  margin-top: 2px;
  padding: 0 4px;
}
.out .time {
  text-align: right;
}

/* === Поле ввода === */
.input-area {
  display: flex;
  padding: 12px 16px;
  background: #f0f0f0;
  border-top: 1px solid #ddd;
}
.input-area.disabled {
  opacity: 0.7;
}
.input {
  flex: 1;
  padding: 10px 14px;
  border: 1px solid #ccc;
  border-radius: 20px;
  font-size: 14px;
  outline: none;
}
.btn {
  padding: 8px 18px;
  border: none;
  border-radius: 20px;
  color: #fff;
  cursor: pointer;
  font-size: 13px;
  margin-left: 8px;
  white-space: nowrap;
}

.sidebar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #eee;
}
.sidebar-title {
  margin: 0;
  font-size: 18px;
}
.btn-logout {
  padding: 6px 14px;
  background: #e74c3c;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 13px;
}
.btn-logout:hover {
  background: #c0392b;
}

.btn-green { background: #4caf50; }
.btn-blue { background: #3498db; }
.btn-red { background: #e74c3c; }
.btn-gray { background: #adb5bd; }
</style>