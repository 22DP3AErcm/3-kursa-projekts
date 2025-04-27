<template>
    <div class="finance-container">
      <!-- Finance Projects Section -->
      <div class="finance-section">
        <h2>Your Finance Projects</h2>
        
        <div class="finance-grid" v-if="projects.length > 0">
          <div v-for="project in projects" :key="project.id" class="finance-card">
            <h3 class="finance-title">{{ project.name }}</h3>
            <p class="finance-description">{{ project.description || 'No description provided' }}</p>
            
            <!-- Summary info if available -->
            <div class="finance-summary" v-if="projectSummaries[project.id]">
              <div class="summary-item income">
                <span class="label">Income</span>
                <span class="amount">€{{ projectSummaries[project.id].income.toFixed(2) }}</span>
              </div>
              <div class="summary-item expense">
                <span class="label">Expenses</span>
                <span class="amount">€{{ projectSummaries[project.id].expense.toFixed(2) }}</span>
              </div>
              <div class="summary-item balance" :class="{ 'negative': projectSummaries[project.id].balance < 0 }">
                <span class="label">Balance</span>
                <span class="amount">€{{ projectSummaries[project.id].balance.toFixed(2) }}</span>
              </div>
            </div>
            
            <div class="finance-actions">
              <button class="view-btn" @click="viewProject(project.id)">View Transactions</button>
              <button class="edit-btn" @click="openEditProjectModal(project)">Edit</button>
              <button class="delete-btn" @click="confirmDeleteProject(project)">Delete</button>
            </div>
          </div>
        </div>
        
        <div class="no-projects" v-else>
          <p>You don't have any finance projects yet. Create your first project to start tracking your finances!</p>
        </div>
      </div>
      
      <!-- Create New Project Button -->
      <div class="create-project-section">
        <button class="create-btn" @click="showCreateProjectModal = true">Create New Finance Project</button>
      </div>
      
      <!-- Create Project Modal -->
      <div class="modal" v-if="showCreateProjectModal">
        <div class="modal-content">
          <span class="close-btn" @click="showCreateProjectModal = false">&times;</span>
          <h2>Create New Finance Project</h2>
          <form @submit.prevent="createProject">
            <div class="form-group">
              <label for="name">Project Name:</label>
              <input type="text" id="name" v-model="newProject.name" required>
            </div>
            <div class="form-group">
              <label for="description">Description:</label>
              <textarea id="description" v-model="newProject.description" rows="4"></textarea>
            </div>
            <div class="form-actions">
              <button type="button" class="cancel-btn" @click="showCreateProjectModal = false">Cancel</button>
              <button type="submit" class="submit-btn">Create Project</button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Edit Project Modal -->
      <div class="modal" v-if="showEditProjectModal">
        <div class="modal-content">
          <span class="close-btn" @click="showEditProjectModal = false">&times;</span>
          <h2>Edit Finance Project</h2>
          <form @submit.prevent="updateProject">
            <div class="form-group">
              <label for="edit-name">Project Name:</label>
              <input type="text" id="edit-name" v-model="editedProject.name" required>
            </div>
            <div class="form-group">
              <label for="edit-description">Description:</label>
              <textarea id="edit-description" v-model="editedProject.description" rows="4"></textarea>
            </div>
            <div class="form-actions">
              <button type="button" class="cancel-btn" @click="showEditProjectModal = false">Cancel</button>
              <button type="submit" class="submit-btn">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    data() {
      return {
        projects: [],
        projectSummaries: {},
        showCreateProjectModal: false,
        showEditProjectModal: false,
        newProject: {
          name: '',
          description: ''
        },
        editedProject: {
          id: null,
          name: '',
          description: ''
        }
      };
    },
    
    mounted() {
      this.fetchProjects();
    },
    
    methods: {
      async fetchProjects() {
        const token = localStorage.getItem('token');
        if (!token) {
          console.error('No token found');
          return;
        }
        
        try {
          const response = await axios.get('http://localhost:8000/api/finance/projects', {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });
          
          this.projects = response.data;
          console.log('Fetched Finance Projects:', this.projects);
          
          // Fetch summary data for each project
          this.projects.forEach(project => {
            this.fetchProjectSummary(project.id);
          });
        } catch (error) {
          console.error('Error fetching finance projects:', error);
        }
      },
      
      async fetchProjectSummary(projectId) {
        const token = localStorage.getItem('token');
        if (!token) {
            console.error('No token found');
            return;
        }
        
        try {
            const response = await axios.get(`http://localhost:8000/api/finance/summary?project_id=${projectId}`, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
            });
            
            // Vue 3 way: Direct assignment works for reactivity
            this.projectSummaries[projectId] = {
            income: response.data.overview.income,
            expense: response.data.overview.expense,
            balance: response.data.overview.balance
            };
        } catch (error) {
            console.error(`Error fetching summary for project ${projectId}:`, error);
        }
      },
      
      async createProject() {
        const token = localStorage.getItem('token');
        if (!token) {
          console.error('No token found');
          return;
        }
        
        try {
          const projectData = {
            name: this.newProject.name,
            description: this.newProject.description
          };
          
          const response = await axios.post('http://localhost:8000/api/finance/projects', projectData, {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });
          
          console.log('Finance project created:', response.data);
          this.projects.push(response.data);
          this.showCreateProjectModal = false;
          this.newProject = { name: '', description: '' };
        } catch (error) {
          console.error('Error creating finance project:', error);
        }
      },
      
      openEditProjectModal(project) {
        this.editedProject = {
          id: project.id,
          name: project.name,
          description: project.description || ''
        };
        
        this.showEditProjectModal = true;
      },
      
      async updateProject() {
        const token = localStorage.getItem('token');
        if (!token) {
          console.error('No token found');
          return;
        }
        
        try {
          const projectData = {
            name: this.editedProject.name,
            description: this.editedProject.description
          };
          
          const response = await axios.put(`http://localhost:8000/api/finance/projects/${this.editedProject.id}`, projectData, {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });
          
          // Update the project in the local array
          const index = this.projects.findIndex(p => p.id === this.editedProject.id);
          if (index !== -1) {
            this.projects[index] = response.data;
          }
          
          this.showEditProjectModal = false;
          console.log('Finance project updated successfully');
        } catch (error) {
          console.error('Error updating finance project:', error);
        }
      },
      
      confirmDeleteProject(project) {
        if (confirm(`Are you sure you want to delete the finance project "${project.name}"? This will permanently delete all related transactions.`)) {
          this.deleteProject(project.id);
        }
      },
      
      async deleteProject(projectId) {
        const token = localStorage.getItem('token');
        if (!token) {
          console.error('No token found');
          return;
        }
        
        try {
          await axios.delete(`http://localhost:8000/api/finance/projects/${projectId}`, {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });
          
          // Remove the project from the local array
          this.projects = this.projects.filter(p => p.id !== projectId);
          // Remove the summary data
          delete this.projectSummaries[projectId];
          
          console.log('Finance project deleted successfully');
        } catch (error) {
          console.error('Error deleting finance project:', error);
        }
      },
      
      viewProject(projectId) {
        this.$router.push(`/finance/project/${projectId}`);
      }
    }
  };
  </script>
  
  <style scoped>
  @import '../assets/finances.css';
  </style>