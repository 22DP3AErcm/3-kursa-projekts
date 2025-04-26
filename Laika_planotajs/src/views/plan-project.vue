<template>
  <div class="project-planner-container">
    
    <!-- Projects List Section -->
    <div class="projects-section">
      <h2>Your Projects</h2>
      
      <div class="projects-grid" v-if="projects.length > 0">
        <div v-for="project in projects" :key="project.id" class="project-card">
          <h3 class="project-title">{{ project.title }}</h3>
          <p class="project-description">{{ project.description }}</p>
          <div class="project-actions">
            <button class="view-btn" @click="viewProject(project.id)">Open</button>
            <button class="edit-btn" @click="openEditProjectModal(project)">Edit</button>
            <button class="delete-btn" @click="confirmDeleteProject(project)">Delete</button>
          </div>
        </div>
      </div>
      
      <div class="no-projects" v-else>
        <p>You don't have any projects yet. Create your first project to get started!</p>
      </div>
    </div>
    
    <!-- Create New Project Button -->
    <div class="create-project-section">
      <button class="create-btn" @click="showCreateProjectModal = true">Create New Project</button>
    </div>
    
    <!-- Create Project Modal -->
    <div class="modal" v-if="showCreateProjectModal">
      <div class="modal-content">
        <span class="close-btn" @click="showCreateProjectModal = false">&times;</span>
        <h2>Create New Project</h2>
        <form @submit.prevent="createProject">
          <div class="form-group">
            <label for="title">Project Title:</label>
            <input type="text" id="title" v-model="newProject.title" required>
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
        <h2>Edit Project</h2>
        
        <!-- Project Details Tab Navigation -->
        <div class="tab-navigation">
          <button 
            :class="['tab-btn', { active: activeTab === 'details' }]" 
            @click="activeTab = 'details'"
          >
            Project Details
          </button>
          <button 
            :class="['tab-btn', { active: activeTab === 'users' }]" 
            @click="activeTab = 'users'"
          >
            Team Members
          </button>
        </div>

        <!-- Project Details Tab -->
        <div v-if="activeTab === 'details'" class="tab-content">
          <form @submit.prevent="updateProject">
            <div class="form-group">
              <label for="edit-title">Project Title:</label>
              <input type="text" id="edit-title" v-model="editedProject.title" required>
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

        <!-- Team Members Tab -->
        <div v-if="activeTab === 'users'" class="tab-content">
          <div class="current-members" v-if="projectMembers.length > 0">
            <h3>Current Team Members</h3>
            <div class="members-list">
              <div v-for="member in projectMembers" :key="member.id" class="member-item">
                <div class="member-info">
                  <span class="member-name">{{ member.name }}</span>
                  <span class="member-email">{{ member.email }}</span>
                  <span class="member-role">{{ member.pivot.role }}</span>
                </div>
                <div class="member-actions">
                  <!-- Owner can remove anyone except themselves -->
                  <button 
                    v-if="isProjectOwner && member.id !== currentUserId" 
                    class="remove-member-btn" 
                    @click="removeMember(member.id)"
                  >
                    Remove
                  </button>
                  
                  <!-- Co-owner can remove regular members -->
                  <button 
                    v-else-if="isCoOwner && member.id !== currentUserId && member.pivot.role !== 'owner' && member.pivot.role !== 'co-owner'" 
                    class="remove-member-btn" 
                    @click="removeMember(member.id)"
                  >
                    Remove
                  </button>
                  
                  <!-- Members can leave the project themselves -->
                  <button 
                    v-else-if="member.id === currentUserId && !isProjectOwner" 
                    class="leave-project-btn" 
                    @click="leaveProject"
                  >
                    Leave Project
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <div class="invite-section" v-if="isProjectOwner || isCoOwner">
            <h3>Invite New Member</h3>
            <form @submit.prevent="inviteUser">
              <div class="form-group">
                <label for="invite-email">Email:</label>
                <input 
                  type="email" 
                  id="invite-email" 
                  v-model="invitation.email" 
                  placeholder="Enter user email" 
                  required
                >
              </div>
              <div class="form-group">
                <label for="invite-role">Role:</label>
                <select id="invite-role" v-model="invitation.role" required>
                  <option value="member">Member (can edit)</option>
                  <option value="viewer">Viewer (read-only)</option>
                  <option value="co-owner" v-if="isProjectOwner">Co-Owner (can invite members)</option>
                </select>
              </div>
              <div class="form-actions">
                <button type="submit" class="invite-btn">Send Invitation</button>
              </div>
            </form>
          </div>

          <div class="invitation-message" v-if="invitationMessage">
            {{ invitationMessage }}
          </div>
        </div>
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
      showCreateProjectModal: false,
      showEditProjectModal: false,
      newProject: {
        title: '',
        description: ''
      },
      editedProject: {
        id: null,
        title: '',
        description: ''
      },
      activeTab: 'details',
      projectMembers: [],
      currentUserId: null,
      invitation: {
        email: '',
        role: 'member'
      },
      invitationMessage: ''
    };
  },
  
  computed: {
    isProjectOwner() {
      return this.editedProject && this.currentUserId === this.projects.find(p => p.id === this.editedProject.id)?.user_id;
    },
    
    isCoOwner() {
      if (!this.projectMembers || !this.currentUserId) return false;
      const currentMember = this.projectMembers.find(m => m.id === this.currentUserId);
      return currentMember && currentMember.pivot && currentMember.pivot.role === 'co-owner';
    }
  },
  
  mounted() {
    this.fetchProjects();
    
    // Set current user ID on mount
    const user = JSON.parse(localStorage.getItem('user'));
    if (user) {
      this.currentUserId = user.id;
    }
  },
  
  methods: {
    async fetchProjects() {
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      try {
        const response = await axios.get('http://localhost:8000/api/projects', {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        this.projects = response.data;
        console.log('Fetched Projects:', this.projects);
      } catch (error) {
        console.error('Error fetching projects:', error);
      }
    },
    
    async createProject() {
      const token = localStorage.getItem('token');
      const user = JSON.parse(localStorage.getItem('user'));
      
      if (!token || !user) {
        console.error('No token or user found');
        return;
      }
      
      try {
        const projectData = {
          title: this.newProject.title,
          description: this.newProject.description
        };
        
        const response = await axios.post('http://localhost:8000/api/projects', projectData, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        console.log('Project created:', response.data);
        this.projects.push(response.data);
        this.showCreateProjectModal = false;
        this.newProject = { title: '', description: '' };
      } catch (error) {
        console.error('Error creating project:', error);
      }
    },
    
    openEditProjectModal(project) {
      this.editedProject = {
        id: project.id,
        title: project.title,
        description: project.description || ''
      };
      
      // Reset data
      this.activeTab = 'details';
      this.invitationMessage = '';
      this.invitation = { email: '', role: 'member' };
      
      // Load project members
      this.fetchProjectMembers(project.id);
      
      // Set current user ID for permission checks
      const user = JSON.parse(localStorage.getItem('user'));
      if (user) {
        this.currentUserId = user.id;
      }
      
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
          title: this.editedProject.title,
          description: this.editedProject.description
        };
        
        const response = await axios.put(`http://localhost:8000/api/projects/${this.editedProject.id}`, projectData, {
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
        console.log('Project updated successfully');
      } catch (error) {
        console.error('Error updating project:', error);
      }
    },

    async fetchProjectMembers(projectId) {
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      try {
        const response = await axios.get(`http://localhost:8000/api/projects/${projectId}/members`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        this.projectMembers = response.data;
        console.log('Project members:', this.projectMembers);
      } catch (error) {
        console.error('Error fetching project members:', error);
        // Initialize with empty array to prevent UI errors
        this.projectMembers = [];
        
        // Show user-friendly error message
        this.invitationMessage = 'Unable to load team members. Please try again later.';
      }
    },
    
    async inviteUser() {
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      try {
        await axios.post(`http://localhost:8000/api/projects/${this.editedProject.id}/invite`, {
          email: this.invitation.email,
          role: this.invitation.role
        }, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        // Success message and reset form
        this.invitationMessage = `Invitation sent to ${this.invitation.email}`;
        this.invitation.email = '';
        
        // Refresh the members list
        this.fetchProjectMembers(this.editedProject.id);
      } catch (error) {
        console.error('Error inviting user:', error);
        if (error.response && error.response.data && error.response.data.message) {
          this.invitationMessage = `Error: ${error.response.data.message}`;
        } else {
          this.invitationMessage = 'Error sending invitation. Please try again.';
        }
      }
    },
    
    async removeMember(userId) {
      if (!confirm('Are you sure you want to remove this member from the project?')) {
        return;
      }
      
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      try {
        await axios.delete(`http://localhost:8000/api/projects/${this.editedProject.id}/members/${userId}`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        // Remove the member from the local array
        this.projectMembers = this.projectMembers.filter(m => m.id !== userId);
      } catch (error) {
        console.error('Error removing member:', error);
        if (error.response && error.response.data && error.response.data.message) {
          alert(`Error: ${error.response.data.message}`);
        } else {
          alert('Error removing member. Please try again.');
        }
      }
    },
    
    async leaveProject() {
      if (!confirm('Are you sure you want to leave this project?')) {
        return;
      }
      
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      try {
        await axios.post(`http://localhost:8000/api/projects/${this.editedProject.id}/leave`, {}, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        // Close the modal
        this.showEditProjectModal = false;
        
        // Remove this project from the list if the user leaves
        this.projects = this.projects.filter(p => p.id !== this.editedProject.id);
        
        alert('You have left the project');
      } catch (error) {
        console.error('Error leaving project:', error);
        if (error.response && error.response.data && error.response.data.message) {
          alert(`Error: ${error.response.data.message}`);
        } else {
          alert('Error leaving project. Please try again.');
        }
      }
    },
    
    confirmDeleteProject(project) {
      if (confirm(`Are you sure you want to delete the project "${project.title}"? This will permanently delete all columns and cards within this project.`)) {
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
        await axios.delete(`http://localhost:8000/api/projects/${projectId}`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        // Remove the project from the local array
        this.projects = this.projects.filter(p => p.id !== projectId);
        console.log('Project deleted successfully');
      } catch (error) {
        console.error('Error deleting project:', error);
        if (error.response && error.response.data && error.response.data.message) {
          alert(`Error: ${error.response.data.message}`);
        } else {
          alert('Error deleting project. Please try again.');
        }
      }
    },
    
    viewProject(projectId) {
      this.$router.push(`/projects/${projectId}`);
    }
  }
};
</script>

<style scoped>
@import '../assets/planproject.css';
</style>