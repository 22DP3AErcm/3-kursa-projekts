import './assets/main.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { user } from './store' // Import the centralized user store

const app = createApp(App)

// Make the user state globally available to all components
app.config.globalProperties.$user = user

// Add navigation guard to check authentication on protected routes
router.beforeEach((to, from, next) => {
  // Check if the route requires authentication
  if (to.meta.requiresAuth && !user.value) {
    next({ name: 'login' })
  } 
  // Check if the route requires admin privileges
  else if (to.meta.requiresAdmin && (!user.value || !user.value.is_admin)) {
    next({ name: 'home' })
  }
  else {
    next()
  }
})

// Use the router
app.use(router)

// Mount the application
app.mount('#app')

// For debugging in development
if (process.env.NODE_ENV === 'development') {
  window.appUser = user
}