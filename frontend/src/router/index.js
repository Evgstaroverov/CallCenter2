import { createRouter, createWebHistory } from 'vue-router'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import Chats from '../views/Chats.vue'

const routes = [
  { path: '/', redirect: '/chats' },
  { path: '/login', component: Login },
  { path: '/register', component: Register },
  { path: '/chats', component: Chats, meta: { requiresAuth: true } }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  if (to.meta.requiresAuth && !token) {
    next('/login')
  } else {
    next()
  }
})

export default router