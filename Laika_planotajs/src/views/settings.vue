<template>
    <div class="settings-container">
      <div class="settings-header">
        <h1>Account Settings</h1>
        <p>Manage your account information</p>
      </div>
      
      <div class="message-container">
        <div class="alert success" v-if="successMessage">
          <i class="fas fa-check-circle"></i> {{ successMessage }}
        </div>
        
        <div class="alert error" v-if="errorMessage">
          <i class="fas fa-exclamation-circle"></i> {{ errorMessage }}
        </div>
      </div>
      
      <div class="settings-content">
        <form @submit.prevent="saveChanges" class="settings-form">
          <h2>User Information</h2>
          
          <!-- Profile Information -->
          <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" v-model="user.name" required>
            <div class="error-text" v-if="errors.name">{{ errors.name }}</div>
          </div>
          
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" v-model="user.email" required>
            <div class="error-text" v-if="errors.email">{{ errors.email }}</div>
          </div>
          
          <div class="form-group">
            <label for="telephone">Phone Number</label>
            <input type="tel" id="telephone" v-model="user.telephone">
            <div class="error-text" v-if="errors.telephone">{{ errors.telephone }}</div>
          </div>
          
          <!-- Notification Preferences -->
          <div class="notification-prefs">
            <div class="form-group checkbox-group">
              <input type="checkbox" id="email_notifications" v-model="user.email_notifications">
              <label for="email_notifications">Receive email notifications</label>
            </div>
            
            <div class="form-group checkbox-group">
              <input type="checkbox" id="phone_notifications" v-model="user.phone_notifications">
              <label for="phone_notifications">Receive phone notifications</label>
            </div>
          </div>
          
          <!-- Password Change -->
          <h3 class="password-heading">Change Password</h3>
          <p class="section-info">Leave password fields empty if you don't want to change your password</p>
          
          <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" v-model="passwordData.current_password">
            <div class="error-text" v-if="errors.current_password">{{ errors.current_password }}</div>
          </div>
          
          <div class="form-group">
            <label for="new_password">New Password</label>
            <input 
              type="password" 
              id="new_password" 
              v-model="passwordData.new_password"
              :disabled="!passwordData.current_password"
            >
            <div class="error-text" v-if="errors.new_password">{{ errors.new_password }}</div>
          </div>
          
          <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input 
              type="password" 
              id="confirm_password" 
              v-model="passwordData.confirm_password"
              :disabled="!passwordData.current_password"
            >
            <div class="error-text" v-if="errors.confirm_password">{{ errors.confirm_password }}</div>
          </div>
          
          <div class="action-bar">
            <button type="button" class="cancel-btn" @click="cancelChanges">Cancel</button>
            <button type="submit" class="save-btn" :disabled="isUpdating">
              <span v-if="isUpdating"><i class="fas fa-spinner fa-spin"></i> Saving...</span>
              <span v-else>Save Changes</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    data() {
      return {
        user: {
          name: '',
          email: '',
          telephone: '',
          email_notifications: true,
          phone_notifications: false
        },
        originalUser: null,
        passwordData: {
          current_password: '',
          new_password: '',
          confirm_password: ''
        },
        errors: {},
        successMessage: '',
        errorMessage: '',
        isUpdating: false
      };
    },
    
    mounted() {
      this.loadUserData();
    },
    
    methods: {
      loadUserData() {
        const token = localStorage.getItem('token');
        if (!token) {
          this.$router.push('/login');
          return;
        }
        
        const userData = JSON.parse(localStorage.getItem('user'));
        if (userData) {
          this.user = {
            name: userData.name || '',
            email: userData.email || '',
            telephone: userData.telephone || '',
            email_notifications: userData.email_notifications !== undefined ? userData.email_notifications : true,
            phone_notifications: userData.phone_notifications !== undefined ? userData.phone_notifications : false
          };
          // Store original values to detect changes
          this.originalUser = JSON.parse(JSON.stringify(this.user));
        } else {
          this.fetchUserData();
        }
      },
      
      async fetchUserData() {
        const token = localStorage.getItem('token');
        if (!token) return;
        
        try {
          const response = await axios.get('http://localhost:8000/api/user', {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });
          
          this.user = {
            name: response.data.name || '',
            email: response.data.email || '',
            telephone: response.data.telephone || '',
            email_notifications: response.data.email_notifications !== undefined ? response.data.email_notifications : true,
            phone_notifications: response.data.phone_notifications !== undefined ? response.data.phone_notifications : false
          };
          
          // Store original values to detect changes
          this.originalUser = JSON.parse(JSON.stringify(this.user));
        } catch (error) {
          console.error('Error fetching user data:', error);
          this.errorMessage = 'Failed to load user data';
        }
      },
      
      async saveChanges() {
        this.clearMessages();
        this.isUpdating = true;
        this.errors = {};
        
        const token = localStorage.getItem('token');
        if (!token) {
          this.errorMessage = 'You must be logged in to update your settings';
          this.isUpdating = false;
          return;
        }
        
        // Check if we need to update profile
        const profileChanged = JSON.stringify(this.user) !== JSON.stringify(this.originalUser);
        
        // Check if we need to update password
        const passwordChanged = !!this.passwordData.current_password && !!this.passwordData.new_password;
        
        // Validate password match if entered
        if (passwordChanged && this.passwordData.new_password !== this.passwordData.confirm_password) {
          this.errors.confirm_password = 'Passwords do not match';
          this.isUpdating = false;
          return;
        }
        
        try {
          // Update profile if changed
          if (profileChanged) {
            await this.updateProfile();
          }
          
          // Update password if entered
          if (passwordChanged) {
            await this.updatePassword();
          }
          
          if (!profileChanged && !passwordChanged) {
            this.successMessage = 'No changes to save';
          }
          
        } catch (error) {
          console.error('Error saving changes:', error);
        } finally {
          this.isUpdating = false;
        }
      },
      
      async updateProfile() {
        try {
            const token = localStorage.getItem('token');
            
            const response = await axios.put('http://localhost:8000/api/user/profile', this.user, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
            });
            
            // Update local storage user data
            const userData = JSON.parse(localStorage.getItem('user')) || {};
            
            // Update user data fields
            Object.keys(this.user).forEach(key => {
            userData[key] = this.user[key];
            });
            
            localStorage.setItem('user', JSON.stringify(userData));
            
            // Update original user data
            this.originalUser = JSON.parse(JSON.stringify(this.user));
            
            this.successMessage = 'Profile updated successfully';
            
            // Dispatch a custom event to notify App.vue
            window.dispatchEvent(new CustomEvent('user-updated'));
            
        } catch (error) {
            if (error.response && error.response.data && error.response.data.errors) {
            this.errors = error.response.data.errors;
            } else {
            this.errorMessage = 'Failed to update profile. Please try again.';
            }
            throw error;
        }
        },
      
      async updatePassword() {
        try {
          const token = localStorage.getItem('token');
          
          await axios.put('http://localhost:8000/api/user/password', {
            current_password: this.passwordData.current_password,
            password: this.passwordData.new_password,
            password_confirmation: this.passwordData.confirm_password
          }, {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });
          
          // Reset password fields
          this.passwordData = {
            current_password: '',
            new_password: '',
            confirm_password: ''
          };
          
          this.successMessage = 'Password changed successfully';
          
        } catch (error) {
          if (error.response && error.response.data && error.response.data.errors) {
            this.errors = error.response.data.errors;
          } else if (error.response && error.response.data && error.response.data.message) {
            this.errorMessage = error.response.data.message;
          } else {
            this.errorMessage = 'Failed to change password. Please try again.';
          }
          throw error;
        }
      },
      
      cancelChanges() {
        // Reset all fields to original values
        if (this.originalUser) {
          this.user = JSON.parse(JSON.stringify(this.originalUser));
        }
        
        this.passwordData = {
          current_password: '',
          new_password: '',
          confirm_password: ''
        };
        
        this.clearMessages();
        this.errors = {};
      },
      
      clearMessages() {
        this.successMessage = '';
        this.errorMessage = '';
      }
    }
  };
  </script>
  
  <style scoped>
  .settings-container {
    width: 100%;
    color: #fff;
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 70px);
    background-color: #222;
    padding-top: 700px;
  }
  
  .settings-header {
    padding: 40px 5% 20px;
    background-color: #2d2d2d;
    border-bottom: 1px solid #333;
  }
  
  .settings-header h1 {
    font-size: 2.5rem;
    margin-bottom: 10px;
    font-weight: 600;
  }
  
  .settings-header p {
    font-size: 1.1rem;
    color: #bbb;
  }
  
  .message-container {
    padding: 0 5%;
    margin-top: 20px;
  }
  
  .settings-content {
    flex: 1;
    padding: 0 5% 40px;
  }
  
  .settings-form {
    background-color: #2d2d2d;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
    max-width: 900px;
    margin: 20px auto;
  }
  
  .settings-form h2 {
    font-size: 1.8rem;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #444;
    font-weight: 600;
  }
  
  .password-heading {
    font-size: 1.4rem;
    margin-top: 40px;
    margin-bottom: 10px;
    font-weight: 600;
  }
  
  .section-info {
    margin-top: 0;
    margin-bottom: 20px;
    color: #aaa;
    font-size: 0.9rem;
  }
  
  .form-group {
    margin-bottom: 20px;
  }
  
  .form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #ddd;
  }
  
  .form-group input[type="text"],
  .form-group input[type="email"],
  .form-group input[type="tel"],
  .form-group input[type="password"] {
    width: 100%;
    padding: 12px 15px;
    background-color: #222;
    border: 1px solid #444;
    border-radius: 6px;
    color: #fff;
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
  }
  
  .form-group input:focus {
    outline: none;
    border-color: #000;
    box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.2);
  }
  
  .form-group input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  .notification-prefs {
    margin: 30px 0;
    padding: 20px;
    background-color: #333;
    border-radius: 6px;
  }
  
  .checkbox-group {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
  }
  
  .checkbox-group:last-child {
    margin-bottom: 0;
  }
  
  .checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #000;
  }
  
  .checkbox-group label {
    margin-bottom: 0;
    cursor: pointer;
  }
  
  .error-text {
    color: #ff6b6b;
    font-size: 0.9rem;
    margin-top: 6px;
  }
  
  .action-bar {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    padding: 20px 0;
    margin-top: 40px;
    border-top: 1px solid #444;
  }
  
  .cancel-btn {
    padding: 12px 25px;
    background-color: transparent;
    color: #ccc;
    border: 1px solid #444;
    border-radius: 6px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.2s;
  }
  
  .cancel-btn:hover {
    background-color: #333;
  }
  
  .save-btn {
    padding: 12px 25px;
    background-color: #000;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 1rem;
    cursor: pointer;
    transition: background-color 0.2s;
    min-width: 150px;
  }
  
  .save-btn:hover:not(:disabled) {
    background-color: #111;
  }
  
  .save-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }
  
  .alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 6px;
    font-size: 1rem;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  
  .alert.success {
    background-color: rgba(40, 167, 69, 0.2);
    border: 1px solid #28a745;
    color: #8fd19e;
  }
  
  .alert.error {
    background-color: rgba(220, 53, 69, 0.2);
    border: 1px solid #dc3545;
    color: #ea868f;
  }
  
  @media (max-width: 768px) {
    .settings-header h1 {
      font-size: 2rem;
    }
    
    .settings-form {
      padding: 20px;
    }
    
    .settings-form h2 {
      font-size: 1.5rem;
    }
  }
  </style>