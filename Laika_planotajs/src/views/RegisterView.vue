<template>
  <div class="auth-container">
    <h1>Register</h1>
    <form @submit.prevent="register">
      <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" v-model="email" required>
      </div>
      <div class="form-group">
        <label for="name">Name:</label>
        <input type="text" id="name" v-model="name" required>
      </div>
      <div class="form-group">
        <label for="telephone">Telephone:</label>
        <input type="tel" id="telephone" v-model="telephone" required>
      </div>
      <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" id="password" v-model="password" required>
      </div>
      <div class="form-group">
        <label for="confirm-password">Confirm Password:</label>
        <input type="password" id="confirm-password" v-model="confirmPassword" required>
      </div>
      <button type="submit" class="btn">Register</button>
    </form>
    <p class="login-link">
      Already have an account? <RouterLink to="/login">Login here</RouterLink>
    </p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import axios from '@/axios';
import '@/assets/auth.css';

const email = ref('');
const name = ref('');
const telephone = ref('');
const password = ref('');
const confirmPassword = ref('');
const router = useRouter();

const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

const register = async () => {
  if (!csrfToken) {
    alert('CSRF token not found');
    return;
  }

  try {
    const response = await axios.post('/register', {
      email: email.value,
      name: name.value,
      telephone: telephone.value,
      password: password.value,
      password_confirmation: confirmPassword.value,
    }, {
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    });
    alert(response.data.message);
    router.push('/login');
  } catch (error) {
    console.error(error.response?.data || error.message);
    alert(error.response?.data?.errors ? Object.values(error.response.data.errors).flat().join('\n') : error.response?.data?.message || 'An error occurred');
  }
};
</script>