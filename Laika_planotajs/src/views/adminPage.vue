<template>
  <div class="admin-page">
    <h1>Admin Dashboard</h1>

    <div v-if="loading" class="loading">Loading...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    
    <div v-else>
      <!-- Statistics Section -->
      <div class="stats-section">
        <h2>System Statistics</h2>
        <div class="stats-grid">
          <div class="stat-card">
            <h3>Total Users</h3>
            <p class="stat-value">{{ stats.total_users }}</p>
          </div>
          <div class="stat-card">
            <h3>Total Events</h3>
            <p class="stat-value">{{ stats.total_events }}</p>
          </div>
          <div class="stat-card">
            <h3>Total Projects</h3>
            <p class="stat-value">{{ stats.total_projects }}</p>
          </div>
          <div class="stat-card">
            <h3>New Users Today</h3>
            <p class="stat-value">{{ stats.new_users_today }}</p>
          </div>
        </div>
      </div>

      <!-- User Activity Summary -->
      <div class="user-activity-section">
        <h2>User Activity Summary</h2>
        <div class="search-filter-container">
          <div class="search-box">
            <input 
              type="text" 
              v-model="activitySearchTerm" 
              placeholder="Search users..." 
              @input="debounceActivitySearch"
            >
          </div>
          <div class="filter-options">
            <select v-model="activityFilter">
              <option value="all">All Activities</option>
              <option value="with_events">With Events</option>
              <option value="with_projects">With Projects</option>
              <option value="recent">Recent Activity (7 days)</option>
            </select>
          </div>
        </div>

        <div class="table-container">
          <div class="table-wrapper">
            <table class="activity-table">
              <thead>
                <tr>
                  <th @click="sortUserActivity('name')">
                    User 
                    <span v-if="activitySortField === 'name'" class="sort-indicator">{{ activitySortDirection === 'asc' ? '▲' : '▼' }}</span>
                  </th>
                  <th @click="sortUserActivity('event_count')" class="mobile-hide">
                    Events 
                    <span v-if="activitySortField === 'event_count'" class="sort-indicator">{{ activitySortDirection === 'asc' ? '▲' : '▼' }}</span>
                  </th>
                  <th @click="sortUserActivity('project_count')" class="mobile-hide">
                    Projects 
                    <span v-if="activitySortField === 'project_count'" class="sort-indicator">{{ activitySortDirection === 'asc' ? '▲' : '▼' }}</span>
                  </th>
                  <th @click="sortUserActivity('last_activity')" class="mobile-hide">
                    Last Activity 
                    <span v-if="activitySortField === 'last_activity'" class="sort-indicator">{{ activitySortDirection === 'asc' ? '▲' : '▼' }}</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="activity in filteredAndSortedUserActivity" :key="activity.user_id">
                  <td>
                    <div class="mobile-user-info">
                      <div class="user-name">{{ activity.name }}</div>
                      <div class="mobile-details">
                        <span class="mobile-stat">Events: {{ activity.event_count }}</span>
                        <span class="mobile-stat">Projects: {{ activity.project_count }}</span>
                        <span class="mobile-stat">Last: {{ activity.last_activity ? new Date(activity.last_activity).toLocaleDateString() : 'No activity' }}</span>
                      </div>
                    </div>
                  </td>
                  <td class="mobile-hide">{{ activity.event_count }}</td>
                  <td class="mobile-hide">{{ activity.project_count }}</td>
                  <td class="mobile-hide">{{ activity.last_activity ? new Date(activity.last_activity).toLocaleString() : 'No activity' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="filteredAndSortedUserActivity.length === 0" class="no-results">
            No matching results found
          </div>
        </div>
      </div>

      <!-- User Management -->
      <div class="user-section">
        <h2>User Management</h2>
        <div class="search-filter-container">
          <div class="search-box">
            <input 
              type="text" 
              v-model="userSearchTerm" 
              placeholder="Search by name, email or ID..." 
              @input="debounceUserSearch"
            >
          </div>
          <div class="filter-options">
            <select v-model="userFilter" @change="onUserFilterChange">
              <option value="all">All Users</option>
              <option value="admin">Admins Only</option>
              <option value="regular">Regular Users</option>
              <option value="recent">New Users (30 days)</option>
            </select>
          </div>
        </div>

        <div class="table-container">
          <div class="table-wrapper">
            <table class="user-table">
              <thead>
                <tr>
                  <th @click="sortUsers('name')">
                    User
                    <span v-if="userSortField === 'name'" class="sort-indicator">{{ userSortDirection === 'asc' ? '▲' : '▼' }}</span>
                  </th>
                  <th @click="sortUsers('email')" class="mobile-hide">
                    Email
                    <span v-if="userSortField === 'email'" class="sort-indicator">{{ userSortDirection === 'asc' ? '▲' : '▼' }}</span>
                  </th>
                  <th @click="sortUsers('is_admin')" class="admin-column">
                    Admin
                    <span v-if="userSortField === 'is_admin'" class="sort-indicator">{{ userSortDirection === 'asc' ? '▲' : '▼' }}</span>
                  </th>
                  <th class="actions-column">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in filteredAndSortedUsers" :key="user.id">
                  <td>
                    <div class="mobile-user-info">
                      <div class="user-name">{{ user.name }}</div>
                      <div class="mobile-details">
                        <span class="mobile-stat">ID: {{ user.id }}</span>
                        <span class="mobile-stat">{{ user.email }}</span>
                        <span class="mobile-stat" v-if="user.telephone">{{ user.telephone }}</span>
                        <span class="mobile-stat">{{ new Date(user.created_at).toLocaleDateString() }}</span>
                      </div>
                    </div>
                  </td>
                  <td class="mobile-hide">{{ user.email }}</td>
                  <td class="admin-column">
                    <input 
                      type="checkbox" 
                      :checked="isUserAdmin(user)" 
                      @change="toggleAdmin(user)"
                      :disabled="user.id === currentUser.id"
                      class="admin-checkbox"
                    >
                  </td>
                  <td class="actions-column">
                    <div class="action-buttons">
                      <button @click="editUser(user)" class="action-btn edit">Edit</button>
                      <button 
                        @click="confirmDeleteUser(user)" 
                        class="action-btn delete"
                        :disabled="user.id === currentUser.id"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="filteredAndSortedUsers.length === 0" class="no-results">
            No matching results found
          </div>
        </div>
      </div>
    </div>

    <!-- Edit User Modal -->
    <div v-if="showEditModal" class="modal">
      <div class="modal-content">
        <h3>Edit User</h3>
        <form @submit.prevent="saveUserChanges">
          <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" v-model="editingUser.name" required>
          </div>
          <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" v-model="editingUser.email" required>
          </div>
          <div class="form-group">
            <label for="telephone">Telephone:</label>
            <input type="text" id="telephone" v-model="editingUser.telephone">
          </div>
          <div class="form-group" v-if="editingUser.id !== currentUser.id">
            <label for="is_admin">Admin:</label>
            <input type="checkbox" id="is_admin" v-model="editingUser.is_admin">
          </div>
          <div class="modal-buttons">
            <button type="button" @click="showEditModal = false" class="cancel-btn">Cancel</button>
            <button type="submit" class="save-btn">Save Changes</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal">
      <div class="modal-content">
        <h3>Confirm Delete</h3>
        <p>Are you sure you want to delete user "{{ userToDelete?.name }}"?</p>
        <p class="warning">This action cannot be undone.</p>
        <div class="modal-buttons">
          <button @click="showDeleteModal = false" class="cancel-btn">Cancel</button>
          <button @click="deleteUser" class="delete-btn">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import axios from 'axios';

export default {
  name: 'AdminPage',
  setup() {
    const loading = ref(true);
    const error = ref(null);
    const users = ref([]);
    const userActivity = ref([]);
    const stats = ref({
      total_users: 0,
      total_events: 0,
      total_projects: 0,
      new_users_today: 0
    });
    
    // Search and filter state
    const userSearchTerm = ref('');
    const userFilter = ref('all');
    const activitySearchTerm = ref('');
    const activityFilter = ref('all');
    
    // Separate sorting state for each table
    const userSortField = ref('id');
    const userSortDirection = ref('asc');
    const activitySortField = ref('name');
    const activitySortDirection = ref('asc');
    
    const currentUser = ref(JSON.parse(localStorage.getItem('user')) || {});
    const showEditModal = ref(false);
    const showDeleteModal = ref(false);
    const editingUser = ref({});
    const userToDelete = ref(null);
    const showDebugInfo = ref(false);

    // Helper function to check if user is admin
    const isUserAdmin = (user) => {
      return user.is_admin === true || user.is_admin === 1 || user.is_admin === '1';
    };

    // Debounce search to avoid excessive filtering on each keystroke
    let userSearchTimeout = null;
    let activitySearchTimeout = null;

    const debounceUserSearch = () => {
      clearTimeout(userSearchTimeout);
      userSearchTimeout = setTimeout(() => {
        // The search happens automatically via the computed property
      }, 300);
    };

    const debounceActivitySearch = () => {
      clearTimeout(activitySearchTimeout);
      activitySearchTimeout = setTimeout(() => {
        // The search happens automatically via the computed property
      }, 300);
    };

    // Handle filter change
    const onUserFilterChange = () => {
      console.log('Filter changed to:', userFilter.value);
      console.log('Total users:', users.value.length);
      console.log('Admin users:', users.value.filter(u => isUserAdmin(u)).length);
    };

    // Sorting function
    const sortTable = (data, field, direction) => {
      return [...data].sort((a, b) => {
        let aValue = a[field];
        let bValue = b[field];
        
        // Handle special cases
        if (field === 'created_at' || field === 'last_activity') {
          aValue = new Date(aValue || 0).getTime();
          bValue = new Date(bValue || 0).getTime();
        }
        
        // Handle boolean values
        if (typeof aValue === 'boolean' || field === 'is_admin') {
          aValue = isUserAdmin(a) ? 1 : 0;
          bValue = isUserAdmin(b) ? 1 : 0;
        }
        
        // Handle null values
        if (aValue === null || aValue === undefined) aValue = '';
        if (bValue === null || bValue === undefined) bValue = '';
        
        // Regular comparison
        if (direction === 'asc') {
          return aValue > bValue ? 1 : (aValue < bValue ? -1 : 0);
        } else {
          return aValue < bValue ? 1 : (aValue > bValue ? -1 : 0);
        }
      });
    };
    
    // Sort users
    const sortUsers = (field) => {
      if (userSortField.value === field) {
        userSortDirection.value = userSortDirection.value === 'asc' ? 'desc' : 'asc';
      } else {
        userSortField.value = field;
        userSortDirection.value = 'asc';
      }
    };
    
    // Sort user activity
    const sortUserActivity = (field) => {
      if (activitySortField.value === field) {
        activitySortDirection.value = activitySortDirection.value === 'asc' ? 'desc' : 'asc';
      } else {
        activitySortField.value = field;
        activitySortDirection.value = 'asc';
      }
    };
    
    // Filter users based on search term and filter option
    const filteredUsers = computed(() => {
      let filtered = users.value;
      
      // Apply search term filter
      if (userSearchTerm.value) {
        const searchTerm = userSearchTerm.value.toLowerCase();
        filtered = filtered.filter(user => 
          user.name.toLowerCase().includes(searchTerm) ||
          user.email.toLowerCase().includes(searchTerm) ||
          user.id.toString().includes(searchTerm)
        );
      }
      
      // Apply dropdown filter
      if (userFilter.value !== 'all') {
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        
        filtered = filtered.filter(user => {
          switch (userFilter.value) {
            case 'admin':
              return isUserAdmin(user);
            case 'regular':
              return !isUserAdmin(user);
            case 'recent':
              const createdDate = new Date(user.created_at);
              return createdDate >= thirtyDaysAgo;
            default:
              return true;
          }
        });
      }
      
      return filtered;
    });
    
    // Filter activity data based on search term and filter option
    const filteredUserActivity = computed(() => {
      let filtered = userActivity.value;
      
      // Apply search term filter
      if (activitySearchTerm.value) {
        const searchTerm = activitySearchTerm.value.toLowerCase();
        filtered = filtered.filter(activity => 
          activity.name.toLowerCase().includes(searchTerm)
        );
      }
      
      // Apply dropdown filter
      if (activityFilter.value !== 'all') {
        const sevenDaysAgo = new Date();
        sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
        
        filtered = filtered.filter(activity => {
          switch (activityFilter.value) {
            case 'with_events':
              return activity.event_count > 0;
            case 'with_projects':
              return activity.project_count > 0;
            case 'recent':
              if (activity.last_activity) {
                const lastActivityDate = new Date(activity.last_activity);
                return lastActivityDate >= sevenDaysAgo;
              }
              return false;
            default:
              return true;
          }
        });
      }
      
      return filtered;
    });
    
    // Computed sorted and filtered users
    const filteredAndSortedUsers = computed(() => {
      return sortTable(filteredUsers.value, userSortField.value, userSortDirection.value);
    });
    
    // Computed sorted and filtered user activity
    const filteredAndSortedUserActivity = computed(() => {
      return sortTable(filteredUserActivity.value, activitySortField.value, activitySortDirection.value);
    });

    const fetchUsers = async () => {
      try {
        const token = localStorage.getItem('token');
        if (!token) throw new Error('Not authenticated');

        const response = await axios.get('http://localhost:8000/api/admin/users', {
          headers: { Authorization: `Bearer ${token}` }
        });
        users.value = response.data;
        console.log('Fetched users:', users.value);
      } catch (err) {
        error.value = err.response?.data?.message || 'Failed to fetch users';
        console.error('Error fetching users:', err);
      }
    };

    const fetchUserActivity = async () => {
      try {
        const token = localStorage.getItem('token');
        if (!token) throw new Error('Not authenticated');

        const response = await axios.get('http://localhost:8000/api/admin/user-activity', {
          headers: { Authorization: `Bearer ${token}` }
        });
        userActivity.value = response.data;
      } catch (err) {
        error.value = err.response?.data?.message || 'Failed to fetch user activity';
        console.error('Error fetching user activity:', err);
      }
    };

    const fetchStats = async () => {
      try {
        const token = localStorage.getItem('token');
        if (!token) throw new Error('Not authenticated');

        const response = await axios.get('http://localhost:8000/api/admin/stats', {
          headers: { Authorization: `Bearer ${token}` }
        });
        stats.value = response.data;
      } catch (err) {
        console.error('Error fetching stats:', err);
      } finally {
        loading.value = false;
      }
    };

    const toggleAdmin = async (user) => {
      try {
        const token = localStorage.getItem('token');
        if (!token) throw new Error('Not authenticated');

        const newAdminStatus = !isUserAdmin(user);
        
        await axios.put(`http://localhost:8000/api/admin/users/${user.id}`, 
          { is_admin: newAdminStatus },
          { headers: { Authorization: `Bearer ${token}` }}
        );
        
        // Update local data
        user.is_admin = newAdminStatus;
      } catch (err) {
        error.value = err.response?.data?.message || 'Failed to update user';
        console.error('Error updating user:', err);
      }
    };

    const editUser = (user) => {
      editingUser.value = { ...user };
      showEditModal.value = true;
    };

    const saveUserChanges = async () => {
        try {
            const token = localStorage.getItem('token');
            if (!token) throw new Error('Not authenticated');

            await axios.put(
            `http://localhost:8000/api/admin/users/${editingUser.value.id}`, 
            editingUser.value,
            { headers: { Authorization: `Bearer ${token}` }}
            );
            
            // Update local data in this component
            const index = users.value.findIndex(u => u.id === editingUser.value.id);
            if (index !== -1) {
            users.value[index] = { ...editingUser.value };
            }
            
            // If the edited user is the current logged-in user
            if (editingUser.value.id === currentUser.value.id) {
            // Update localStorage
            const updatedUser = {...currentUser.value, ...editingUser.value};
            localStorage.setItem('user', JSON.stringify(updatedUser));
            
            // Update current user reference
            currentUser.value = updatedUser;
            
            // Dispatch event to notify App.vue and other components
            window.dispatchEvent(new CustomEvent('user-updated'));
            }
            
            showEditModal.value = false;
        } catch (err) {
            error.value = err.response?.data?.message || 'Failed to save changes';
            console.error('Error saving user changes:', err);
        }
    };

    const confirmDeleteUser = (user) => {
      userToDelete.value = user;
      showDeleteModal.value = true;
    };

    const deleteUser = async () => {
      if (!userToDelete.value) return;
      
      try {
        const token = localStorage.getItem('token');
        if (!token) throw new Error('Not authenticated');

        await axios.delete(
          `http://localhost:8000/api/admin/users/${userToDelete.value.id}`,
          { headers: { Authorization: `Bearer ${token}` }}
        );
        
        // Remove from local data
        users.value = users.value.filter(u => u.id !== userToDelete.value.id);
        showDeleteModal.value = false;
        userToDelete.value = null;
        
        // Refresh stats and activity data
        await fetchStats();
        await fetchUserActivity();
      } catch (err) {
        error.value = err.response?.data?.message || 'Failed to delete user';
        console.error('Error deleting user:', err);
      }
    };

    onMounted(async () => {
      await Promise.all([
        fetchUsers(),
        fetchStats(),
        fetchUserActivity()
      ]);
    });

    return {
      loading,
      error,
      users,
      stats,
      userActivity,
      userSearchTerm,
      userFilter,
      activitySearchTerm,
      activityFilter,
      userSortField,
      userSortDirection,
      activitySortField,
      activitySortDirection,
      sortUsers,
      sortUserActivity,
      filteredUsers,
      filteredUserActivity,
      filteredAndSortedUsers,
      filteredAndSortedUserActivity,
      debounceUserSearch,
      debounceActivitySearch,
      onUserFilterChange,
      currentUser,
      showEditModal,
      showDeleteModal,
      editingUser,
      userToDelete,
      showDebugInfo,
      isUserAdmin,
      toggleAdmin,
      editUser,
      saveUserChanges,
      confirmDeleteUser,
      deleteUser
    };
  }
};
</script>

<style scoped>
.admin-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
  padding-top: 1000px;
  color: #fff;
  min-height: 100vh;
}

h1 {
  font-size: 2em;
  margin-bottom: 20px;
  color: #fff;
  text-align: center;
}

h2 {
  font-size: 1.5em;
  margin: 20px 0;
  color: #fff;
}

h3 {
  color: #fff;
  margin-bottom: 10px;
}

.loading, .error {
  text-align: center;
  padding: 20px;
  font-size: 1.2em;
}

.error {
  color: #ff5555;
  background-color: rgba(255, 85, 85, 0.1);
  border-radius: 4px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background-color: #424242;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  text-align: center;
}

.stat-value {
  font-size: 2em;
  font-weight: bold;
  margin-top: 10px;
  color: #4caf50;
}

.search-filter-container {
  display: flex;
  justify-content: space-between;
  margin-bottom: 15px;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.search-box {
  flex: 1;
  max-width: 400px;
  min-width: 250px;
}

.search-box input {
  width: 100%;
  padding: 12px;
  border-radius: 6px;
  border: 1px solid #555;
  background-color: #363636;
  color: #fff;
  box-sizing: border-box;
  font-size: 16px;
}

.filter-options {
  flex-shrink: 0;
}

.filter-options select {
  padding: 12px;
  border-radius: 6px;
  border: 1px solid #555;
  background-color: #363636;
  color: #fff;
  cursor: pointer;
  min-width: 180px;
  font-size: 16px;
}

.table-container {
  position: relative;
  margin-bottom: 30px;
}

.table-wrapper {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.user-table, .activity-table {
  width: 100%;
  border-collapse: collapse;
  background-color: #363636;
  border-radius: 8px;
  overflow: hidden;
}

.user-table th, .user-table td,
.activity-table th, .activity-table td {
  padding: 15px 12px;
  text-align: left;
  border-bottom: 1px solid #424242;
  word-wrap: break-word;
}

.user-table th, .activity-table th {
  background-color: #424242;
  font-weight: bold;
  cursor: pointer;
  position: sticky;
  top: 0;
  z-index: 1;
  user-select: none;
}

.mobile-user-info {
  min-width: 0;
}

.user-name {
  font-weight: bold;
  margin-bottom: 5px;
  color: #fff;
}

.mobile-details {
  display: none;
  flex-direction: column;
  gap: 3px;
}

.mobile-stat {
  font-size: 12px;
  color: #ccc;
  background-color: rgba(255, 255, 255, 0.1);
  padding: 2px 6px;
  border-radius: 3px;
  display: inline-block;
}

.admin-column {
  text-align: center !important;
  width: 80px;
}

.actions-column {
  width: 120px;
}

.action-buttons {
  display: flex;
  gap: 5px;
  flex-wrap: wrap;
}

.admin-checkbox {
  margin: 0;
  transform: scale(1.3);
  cursor: pointer;
}

.admin-checkbox:disabled {
  cursor: not-allowed;
}

.sort-indicator {
  margin-left: 5px;
  display: inline-block;
  color: #fff;
}

.user-table tr:hover, .activity-table tr:hover {
  background-color: #424242;
}

.action-btn {
  padding: 8px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
  font-size: 12px;
  white-space: nowrap;
  min-width: 50px;
}

.edit {
  background-color: #2196f3;
  color: white;
}

.delete {
  background-color: #f44336;
  color: white;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 2000;
  padding: 20px;
}

.modal-content {
  background-color: #424242;
  padding: 30px;
  border-radius: 8px;
  width: 100%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  color: #fff;
  font-weight: bold;
}

.form-group input[type="text"],
.form-group input[type="email"] {
  width: 100%;
  padding: 12px;
  border: 1px solid #555;
  border-radius: 4px;
  background-color: #363636;
  color: #fff;
  box-sizing: border-box;
  font-size: 16px;
}

.modal-buttons {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
  flex-wrap: wrap;
}

.cancel-btn, .save-btn, .delete-btn {
  padding: 12px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: bold;
  font-size: 14px;
  min-width: 80px;
}

.cancel-btn {
  background-color: #616161;
  color: white;
}

.save-btn {
  background-color: #4caf50;
  color: white;
}

.delete-btn {
  background-color: #f44336;
  color: white;
}

.warning {
  color: #f44336;
  font-style: italic;
}

.no-results {
  text-align: center;
  padding: 40px 20px;
  font-style: italic;
  color: #999;
  background-color: #363636;
  border-radius: 8px;
  margin-top: 10px;
}

/* Mobile Styles */
@media (max-width: 768px) {
  .admin-page {
    padding: 25px 10px;
    padding-top: 80px;
  }
  
  h1 {
    font-size: 1.5em;
    margin-bottom: 15px;
  }
  
  h2 {
    font-size: 1.3em;
    margin: 15px 0;
  }
  
  .stats-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
  }
  
  .stat-card {
    padding: 15px 10px;
  }
  
  .stat-value {
    font-size: 1.5em;
  }
  
  .search-filter-container {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }
  
  .search-box {
    max-width: none;
    min-width: auto;
  }
  
  .filter-options select {
    width: 100%;
    min-width: auto;
  }
  
  .mobile-hide {
    display: none !important;
  }
  
  .mobile-details {
    display: flex !important;
    margin-top: 5px;
  }
  
  .user-table th, .user-table td,
  .activity-table th, .activity-table td {
    padding: 12px 8px;
  }
  
  .action-buttons {
    flex-direction: column;
    gap: 5px;
  }
  
  .action-btn {
    width: 100%;
    padding: 10px;
    font-size: 14px;
  }
  
  .admin-checkbox {
    transform: scale(1.5);
  }
  
  .modal-content {
    padding: 20px;
    margin: 10px;
  }
  
  .modal-buttons {
    flex-direction: column;
    gap: 10px;
  }
  
  .cancel-btn, .save-btn, .delete-btn {
    width: 100%;
    padding: 15px;
  }
}

@media (max-width: 480px) {
  .admin-page {
    padding: 10px 5px;
    padding-top: 70px;
  }
  
  h1 {
    font-size: 1.3em;
  }
  
  h2 {
    font-size: 1.2em;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }
  
  .stat-card {
    padding: 12px 8px;
  }
  
  .stat-card h3 {
    font-size: 12px;
    margin-bottom: 5px;
  }
  
  .stat-value {
    font-size: 1.3em;
  }
  
  .search-box input,
  .filter-options select {
    padding: 10px;
    font-size: 14px;
  }
  
  .user-table th, .user-table td,
  .activity-table th, .activity-table td {
    padding: 10px 6px;
    font-size: 14px;
  }
  
  .mobile-stat {
    font-size: 11px;
    padding: 1px 4px;
  }
  
  .modal-content {
    padding: 15px;
    margin: 5px;
  }
  
  .form-group input[type="text"],
  .form-group input[type="email"] {
    padding: 10px;
    font-size: 14px;
  }
}

/* Very small screens */
@media (max-width: 320px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .action-btn {
    font-size: 12px;
    padding: 8px;
  }
  
  .user-table th, .user-table td,
  .activity-table th, .activity-table td {
    padding: 8px 4px;
    font-size: 12px;
  }
}
</style>