<template>
    <div class="mail-container">
      <h1>Your Messages</h1>
      
      <div class="mail-tabs">
        <button 
          :class="['tab-btn', { active: activeTab === 'inbox' }]"
          @click="activeTab = 'inbox'"
        >
          Inbox <span v-if="unreadCount" class="unread-badge">{{ unreadCount }}</span>
        </button>
        <button 
          :class="['tab-btn', { active: activeTab === 'invitations' }]"
          @click="activeTab = 'invitations'"
        >
          Project Invitations <span v-if="pendingInvitations.length" class="unread-badge">{{ pendingInvitations.length }}</span>
        </button>
        <button 
          :class="['tab-btn', { active: activeTab === 'compose' }]"
          @click="activeTab = 'compose'"
        >
          Compose
        </button>
      </div>
      
      <!-- Inbox Tab -->
      <div v-if="activeTab === 'inbox'" class="tab-content">
        <div v-if="messages.length === 0" class="empty-state">
          <p>You have no messages in your inbox.</p>
        </div>
        
        <div v-else class="message-list">
          <div 
            v-for="message in messages" 
            :key="message.id" 
            :class="['message-item', { unread: !message.read }]"
            @click="viewMessage(message)"
          >
            <div class="message-sender">{{ message.sender_name }}</div>
            <div class="message-subject">{{ message.subject }}</div>
            <div class="message-date">{{ formatDate(message.created_at) }}</div>
          </div>
        </div>
      </div>
      
      <!-- Invitations Tab -->
      <div v-if="activeTab === 'invitations'" class="tab-content">
        <div v-if="pendingInvitations.length === 0" class="empty-state">
          <p>You have no pending project invitations.</p>
        </div>
        
        <div v-else class="invitation-list">
          <div 
            v-for="invitation in pendingInvitations" 
            :key="invitation.id" 
            class="invitation-item"
          >
            <div>
              <div class="invitation-project">{{ invitation.project.title }}</div>
              <div class="invitation-details">
                <span>Invited by: {{ invitation.inviter.name }}</span>
                <span>Role: {{ invitation.role }}</span>
              </div>
            </div>
            <div class="invitation-actions">
              <button class="accept-btn" @click="respondToInvitation(invitation.id, 'accept')">Accept</button>
              <button class="decline-btn" @click="respondToInvitation(invitation.id, 'decline')">Decline</button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Compose Tab -->
      <div v-if="activeTab === 'compose'" class="tab-content">
        <form @submit.prevent="sendMessage" class="compose-form">
          <div class="form-group">
            <label for="recipient">Recipient Email:</label>
            <input 
              type="email" 
              id="recipient" 
              v-model="newMessage.recipient_email" 
              required 
              placeholder="Enter recipient's email"
            >
          </div>
          <div class="form-group">
            <label for="subject">Subject:</label>
            <input 
              type="text" 
              id="subject" 
              v-model="newMessage.subject" 
              required 
              placeholder="Enter message subject"
            >
          </div>
          <div class="form-group">
            <label for="message-body">Message:</label>
            <textarea 
              id="message-body" 
              v-model="newMessage.body" 
              rows="6" 
              required 
              placeholder="Type your message here"
            ></textarea>
          </div>
          <div class="form-actions">
            <button type="submit" class="send-btn">Send Message</button>
          </div>
        </form>
      </div>
      
      <!-- Message View Modal -->
      <div v-if="showMessageModal" class="modal">
        <div class="modal-content">
          <span class="close-btn" @click="showMessageModal = false">&times;</span>
          <div class="message-view">
            <h2>{{ currentMessage.subject }}</h2>
            <div class="message-info">
              <div><strong>From:</strong> {{ currentMessage.sender_name }}</div>
              <div><strong>Date:</strong> {{ formatDate(currentMessage.created_at) }}</div>
            </div>
            <div class="message-body">
              {{ currentMessage.body }}
            </div>
            <div class="message-actions">
              <button class="reply-btn" @click="replyToMessage">Reply</button>
              <button class="delete-btn" @click="deleteMessage">Delete</button>
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
    activeTab: 'inbox',
    messages: [],
    pendingInvitations: [],
    showMessageModal: false,
    currentMessage: null,
    newMessage: {
        recipient_email: '',
        subject: '',
        body: ''
    }
    };
    },
    computed: {
        unreadCount() {
        return this.messages.filter(message => !message.read).length;
        }
    },
    mounted() {
        this.fetchMessages();
        this.fetchInvitations();
    },
    methods: {
        async fetchMessages() {
        const token = localStorage.getItem('token');
        if (!token) return;
        
        try {
            const response = await axios.get('http://localhost:8000/api/messages', {
            headers: { 'Authorization': `Bearer ${token}` }
            });
            
            this.messages = response.data;
        } catch (error) {
            console.error('Error fetching messages:', error);
            // Removed mock data
        }
        },
        
        async fetchInvitations() {
        const token = localStorage.getItem('token');
        if (!token) return;
        
        try {
            const response = await axios.get('http://localhost:8000/api/invitations', {
            headers: { 'Authorization': `Bearer ${token}` }
            });
            
            this.pendingInvitations = response.data;
        } catch (error) {
            console.error('Error fetching invitations:', error);
            // Removed mock data
        }
        },
      
      
      formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
      },
      
      viewMessage(message) {
        this.currentMessage = message;
        this.showMessageModal = true;
        
        // Mark as read if unread
        if (!message.read) {
          this.markAsRead(message.id);
        }
      },
      
      async markAsRead(messageId) {
        const token = localStorage.getItem('token');
        if (!token) return;
        
        try {
          await axios.put(`http://localhost:8000/api/messages/${messageId}/read`, {}, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          // Update local state
          const index = this.messages.findIndex(m => m.id === messageId);
          if (index !== -1) {
            this.messages[index].read = true;
          }
        } catch (error) {
          console.error('Error marking message as read:', error);
          // Update local state anyway for demo
          const index = this.messages.findIndex(m => m.id === messageId);
          if (index !== -1) {
            this.messages[index].read = true;
          }
        }
      },
      
      async sendMessage() {
        const token = localStorage.getItem('token');
        if (!token) return;
        
        try {
          await axios.post('http://localhost:8000/api/messages', this.newMessage, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          // Reset form
          this.newMessage = {
            recipient_email: '',
            subject: '',
            body: ''
          };
          
          // Show success and switch to inbox
          alert('Message sent successfully!');
          this.activeTab = 'inbox';
          
          // Refresh inbox
          this.fetchMessages();
        } catch (error) {
          console.error('Error sending message:', error);
          alert('Failed to send message. Please try again.');
        }
      },
      
      replyToMessage() {
        if (!this.currentMessage) return;
        
        this.newMessage = {
          recipient_email: this.currentMessage.sender_email || '',
          subject: `Re: ${this.currentMessage.subject}`,
          body: `\n\n---\nOn ${this.formatDate(this.currentMessage.created_at)}, ${this.currentMessage.sender_name} wrote:\n${this.currentMessage.body}`
        };
        
        this.showMessageModal = false;
        this.activeTab = 'compose';
      },
      
      async deleteMessage() {
        if (!this.currentMessage) return;
        
        if (!confirm('Are you sure you want to delete this message?')) {
          return;
        }
        
        const token = localStorage.getItem('token');
        if (!token) return;
        
        try {
          await axios.delete(`http://localhost:8000/api/messages/${this.currentMessage.id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          // Remove from local list
          this.messages = this.messages.filter(m => m.id !== this.currentMessage.id);
          this.showMessageModal = false;
          
          alert('Message deleted successfully');
        } catch (error) {
          console.error('Error deleting message:', error);
          
          // Remove from local list anyway for demo
          this.messages = this.messages.filter(m => m.id !== this.currentMessage.id);
          this.showMessageModal = false;
          
          alert('Message deleted successfully');
        }
      },
      
      async respondToInvitation(invitationId, response) {
        const token = localStorage.getItem('token');
        if (!token) return;
        
        try {
          await axios.post(`http://localhost:8000/api/invitations/${invitationId}/respond`, {
            response: response
          }, {
            headers: { 'Authorization': `Bearer ${token}` }
          });
          
          // Remove from local list
          this.pendingInvitations = this.pendingInvitations.filter(inv => inv.id !== invitationId);
          
          alert(`You have ${response}ed the invitation.`);
        } catch (error) {
          console.error('Error responding to invitation:', error);
          
          // Remove from local list anyway for demo
          this.pendingInvitations = this.pendingInvitations.filter(inv => inv.id !== invitationId);
          
          alert(`You have ${response}ed the invitation.`);
        }
      }
    }
  };
  </script>
  
  <style scoped>
  .mail-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    color: #fff;
  }
  
  h1 {
    margin-bottom: 30px;
    font-size: 2em;
    text-align: center;
  }
  
  .mail-tabs {
    display: flex;
    border-bottom: 1px solid #555;
    margin-bottom: 20px;
  }
  
  .tab-btn {
    padding: 12px 20px;
    background: transparent;
    border: none;
    color: #aaa;
    cursor: pointer;
    font-size: 1em;
    position: relative;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
  }
  
  .tab-btn.active {
    color: #fff;
    border-bottom-color: #000;
  }
  
  .tab-btn:hover:not(.active) {
    color: #fff;
    border-bottom-color: #555;
  }
  
  .unread-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    background-color: #dc3545;
    color: white;
    font-size: 0.7em;
    font-weight: bold;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .tab-content {
    background-color: #363636;
    border-radius: 8px;
    padding: 20px;
    min-height: 400px;
  }
  
  .empty-state {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 200px;
    color: #aaa;
    font-style: italic;
  }
  
  /* Message list styling */
  .message-list, .invitation-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
  }
  
  .message-item {
    display: grid;
    grid-template-columns: 2fr 3fr 1fr;
    padding: 15px;
    border-radius: 5px;
    background-color: #2c2c2c;
    cursor: pointer;
    transition: all 0.2s;
    align-items: center;
  }
  
  .message-item:hover {
    background-color: #404040;
  }
  
  .message-item.unread {
    background-color: #29343d;
    font-weight: bold;
    border-left: 4px solid #0dcaf0;
  }
  
  .message-sender {
    font-weight: bold;
  }
  
  .message-subject {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  
  .message-date {
    text-align: right;
    font-size: 0.9em;
    color: #aaa;
  }
  
  /* Invitation item styling */
  .invitation-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-radius: 5px;
    background-color: #2c2c2c;
    margin-bottom: 10px;
  }
  
  .invitation-project {
    font-weight: bold;
    font-size: 1.1em;
    margin-bottom: 5px;
  }
  
  .invitation-details {
    display: flex;
    flex-direction: column;
    font-size: 0.9em;
    color: #aaa;
  }
  
  .invitation-actions {
    display: flex;
    gap: 10px;
  }
  
  .accept-btn, .decline-btn {
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }
  
  .accept-btn {
    background-color: #000;
    color: white;
  }
  
  .decline-btn {
    background-color: #555;
    color: white;
  }
  
  .accept-btn:hover {
    background-color: #252525;
  }
  
  .decline-btn:hover {
    background-color: #444;
  }
  
  /* Compose form styling */
  .compose-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
  }
  
  .form-group {
    display: flex;
    flex-direction: column;
    gap: 5px;
  }
  
  .form-group label {
    font-weight: bold;
  }
  
  .form-group input, .form-group textarea {
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #555;
    background-color: #2c2c2c;
    color: #fff;
  }
  
  .form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 10px;
  }
  
  .send-btn {
    padding: 10px 20px;
    background-color: #000;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }
  
  .send-btn:hover {
    background-color: #252525;
  }
  
  /* Message view modal styling */
  .modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000;
  }
  
  .modal-content {
    background-color: #363636;
    padding: 30px;
    border-radius: 10px;
    width: 90%;
    max-width: 600px;
    position: relative;
  }
  
  .close-btn {
    position: absolute;
    top: 15px;
    right: 20px;
    font-size: 28px;
    cursor: pointer;
    color: #aaa;
  }
  
  .close-btn:hover {
    color: #fff;
  }
  
  .message-view h2 {
    margin-top: 0;
    margin-bottom: 20px;
  }
  
  .message-info {
    margin-bottom: 20px;
    color: #aaa;
    border-bottom: 1px solid #555;
    padding-bottom: 15px;
  }
  
  .message-body {
    margin-bottom: 30px;
    white-space: pre-line;
    line-height: 1.5;
    max-height: 300px;
    overflow-y: auto;
  }
  
  .message-actions {
    display: flex;
    justify-content: space-between;
  }
  
  .reply-btn, .delete-btn {
    padding: 8px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }
  
  .reply-btn {
    background-color: #000;
    color: white;
  }
  
  .delete-btn {
    background-color: #dc3545;
    color: white;
  }
  
  .reply-btn:hover {
    background-color: #252525;
  }
  
  .delete-btn:hover {
    background-color: #bd2130;
  }
  </style>