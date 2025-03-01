<template>
  <div class="auth-container">
    <h1>Login</h1>
    <form @submit.prevent="login">
      <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" v-model="email" required>
      </div>
      <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" v-model="password" required>
      </div>
      <button type="submit" class="btn">Login</button>
    </form>
    <p class="register-link">
      Don't have an account? <RouterLink to="/register">Register here</RouterLink>
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import axios from '@/axios';
import '@/assets/auth.css';
import { user } from '@/store';

const email = ref('');
const password = ref('');
const router = useRouter();

const login = async () => {
  try {
    const response = await axios.post('/api/login', { 
      email: email.value,
      password: password.value,
    });
    
    alert(response.data.message);
    user.value = response.data.user;
    localStorage.setItem('user', JSON.stringify(response.data.user));
    localStorage.setItem('token', response.data.token);
    await router.push('/');
    window.location.reload();
  } catch (error) {
    alert(error.response.data.message);
  }
};
</script>