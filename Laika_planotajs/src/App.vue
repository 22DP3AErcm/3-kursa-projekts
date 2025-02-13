<template>
  <div id="app">
    <header>
      <div class="header-right">
        <RouterLink to="/login" class="nav-button">Login</RouterLink>
      </div>
      <nav>
        <RouterLink to="/" class="nav-button">Home</RouterLink>
        <RouterLink to="/about" class="nav-button">About</RouterLink>
      </nav>
    </header>
    <main>
      <RouterView />
    </main>
  </div>
</template>

<script setup>
import { RouterLink, RouterView } from 'vue-router'
import '@/assets/styles.css'
</script>

<script>
export default {
  metaInfo: {
    meta: [
      { name: 'csrf-token', content: '{{ csrf_token() }}' }
    ]
  }
}
</script>