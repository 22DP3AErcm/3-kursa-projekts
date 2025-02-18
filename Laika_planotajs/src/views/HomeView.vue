<template>
  <div class="home">
    <h1 class="typing-text">Welcome! start planning your time<span class="dots"></span></h1>
    <div class="card-container">
      <div class="card">
        <div class="card-header">
          <h1>Plan Your Day</h1>
        </div>
        <p>Organize your daily tasks and stay productive.</p>
        <button class="start-button" @click="handleStartClick('/plan-day')">Start</button>
      </div>
      <div class="card">
        <div class="card-header">
          <h1>Plan Your Project</h1>
        </div>
        <p>Manage your projects efficiently and meet deadlines.</p>
        <button class="start-button" @click="handleStartClick('/plan-project')">Start</button>
      </div>
      <div class="card">
        <div class="card-header">
          <h1>Track Your Finances</h1>
        </div>
        <p>Keep track of your expenses and manage your budget.</p>
        <button class="start-button" @click="handleStartClick('/track-finances')">Start</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import '@/assets/Home.css'
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ref } from 'vue'

const router = useRouter()
const user = ref(JSON.parse(localStorage.getItem('user')) || null)

const handleStartClick = (targetUrl) => {
  if (user.value) {
    router.push(targetUrl)
  } else {
    router.push('/login')
  }
}

onMounted(() => {
  const dots = document.querySelector('.dots')
  let dotCount = 0

  setInterval(() => {
    dotCount = (dotCount + 1) % 4
    dots.textContent = '.'.repeat(dotCount)
  }, 500)
})
</script>