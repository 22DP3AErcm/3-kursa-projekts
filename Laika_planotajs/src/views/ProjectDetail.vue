<template>
    <div class="project-detail-container" v-if="project">
      <div class="project-header">
        <h1>{{ project.title }}</h1>
        <p class="project-description">{{ project.description }}</p>
      </div>
  
      <div class="board-container">
        <!-- Blocks Section with outer draggable for columns -->
        <draggable 
          v-model="blocks" 
          group="columns"
          item-key="id"
          :animation="300"
          chosen-class="column-chosen"
          drag-class="column-drag"
          ghost-class="column-ghost"
          @end="onColumnDragEnd"
          @change="onColumnChange"
          handle=".block-header"
          class="blocks-wrapper"
          :disabled="isViewer"
        >
          <template #item="{element: block}">
            <div class="block-column">
              <div class="block-header">
                <h3>{{ block.title }}</h3>
                <div class="block-actions">
                  <button class="edit-btn" @click.stop="editBlock(block)">Edit</button>
                  <button class="delete-btn" @click.stop="deleteBlock(block.id)">×</button>
                </div>
              </div>
              
              <div class="items-container">
                <!-- Inner draggable for items within columns -->
                <draggable 
                  v-model="block.items" 
                  group="items"
                  item-key="id"
                  :animation="300"
                  chosen-class="sortable-chosen"
                  drag-class="sortable-drag"
                  @end="onDragEnd"
                  @change="onChange($event, block.id)"
                  @start="onDragStart"
                  class="drag-area"
                  :fallbackOnBody="true"
                  :forceFallback="true"
                  :emptyInsertThreshold="0"
                  :swapThreshold="0.65"
                  :disabled="isViewer"
                >
                  <template #item="{element}">
                    <div 
                      class="block-item"
                      @click.stop="openItemDetails(element)"
                    >
                      <h4>{{ element.title }}</h4>
                      <p v-if="element.description" class="item-description">{{ element.description.substring(0, 100) }}{{ element.description.length > 100 ? '...' : '' }}</p>
                      <div v-if="element.due_date" class="due-date">Due: {{ formatDate(element.due_date) }}</div>
                    </div>
                  </template>
                </draggable>
              </div>
  
              <button class="add-item-btn" @click="addItem(block.id)">+ Add Card</button>
            </div>
          </template>
        </draggable>
        
        <!-- Add New Block Column -->
        <div class="add-block-column">
          <button v-if="!showAddBlock" @click="showAddBlock = true" class="add-block-btn">+ Add Column</button>
          <div v-else class="add-block-form">
            <input type="text" v-model="newBlockTitle" placeholder="Enter column title..." @keyup.enter="createBlock">
            <div class="add-block-actions">
              <button @click="createBlock" class="save-btn">Add</button>
              <button @click="showAddBlock = false" class="cancel-btn">Cancel</button>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Item Details Modal -->
      <div class="modal" v-if="showItemModal">
        <div class="modal-content">
          <span class="close-btn" @click="showItemModal = false">&times;</span>
          <h2>{{ currentItem.title }}</h2>
          <div class="form-group">
            <label for="item-title">Title:</label>
            <input type="text" id="item-title" v-model="currentItem.title">
          </div>
          <div class="form-group">
            <label for="item-description">Description:</label>
            <textarea id="item-description" v-model="currentItem.description" rows="4"></textarea>
          </div>
          <div class="form-group">
            <label for="item-due-date">Due Date:</label>
            <input type="date" id="item-due-date" v-model="currentItem.due_date">
          </div>
          <div class="form-group">
            <label for="item-block">Move to:</label>
            <select id="item-block" v-model="currentItem.project_block_id">
              <option v-for="block in blocks" :key="block.id" :value="block.id">{{ block.title }}</option>
            </select>
          </div>
          <div class="form-actions">
            <button class="delete-btn" @click="deleteItem(currentItem.id)">Delete</button>
            <button class="save-btn" @click="updateItem">Save</button>
          </div>
        </div>
      </div>
  
      <!-- Add New Item Modal -->
      <div class="modal" v-if="showAddItemModal">
        <div class="modal-content">
          <span class="close-btn" @click="showAddItemModal = false">&times;</span>
          <h2>Add New Card</h2>
          <div class="form-group">
            <label for="new-item-title">Title:</label>
            <input type="text" id="new-item-title" v-model="newItem.title" required>
          </div>
          <div class="form-group">
            <label for="new-item-description">Description:</label>
            <textarea id="new-item-description" v-model="newItem.description" rows="4"></textarea>
          </div>
          <div class="form-group">
            <label for="new-item-due-date">Due Date:</label>
            <input type="date" id="new-item-due-date" v-model="newItem.due_date">
          </div>
          <div class="form-actions">
            <button type="button" class="cancel-btn" @click="showAddItemModal = false">Cancel</button>
            <button type="button" class="save-btn" @click="createItem">Add Card</button>
          </div>
        </div>
      </div>
      
      <!-- Cloned card for better drag visualization -->
      <div class="drag-clone" ref="dragClone" v-show="isDragging"></div>
    </div>
  </template>
  
  <script>
import axios from 'axios';
import { defineComponent } from 'vue';
import draggable from 'vuedraggable';

export default defineComponent({
  components: {
    draggable
  },
  data() {
    return {
      project: null,
      blocks: [],
      showAddBlock: false,
      newBlockTitle: '',
      showItemModal: false,
      showAddItemModal: false,
      currentItem: {
        id: null,
        title: '',
        description: '',
        due_date: null,
        project_block_id: null
      },
      newItem: {
        title: '',
        description: '',
        due_date: null
      },
      currentBlockId: null,
      draggedItem: null,
      isDragging: false,
      isColumnDragging: false,
      currentDragElement: null,
      mouseVelocity: { x: 0, y: 0 },
      lastMousePosition: { x: 0, y: 0 },
      dragCloneWidth: 280, // Default width, will be updated when dragging starts
      dragCloneHeight: 0,  // Will be measured when dragging starts
      tiltAngle: 0,
      movingDirection: 'none',
      projectMembers: [],
      currentUserId: null,
      dragEnabled: true  // Flag to control drag functionality
    };
  },
  
  computed: {
    isViewer() {
      // First check if the current user is the project owner
      if (this.currentUserId === this.project?.user_id) {
        return false;
      }
      
      // Then check if they're a viewer member
      const member = this.projectMembers.find(m => m.id === this.currentUserId);
      return member && member.pivot && member.pivot.role === 'viewer';
    }
  },
  
  mounted() {
    const projectId = this.$route.params.id;
    if (projectId) {
      this.fetchProjectDetails(projectId);
    }
    
    // Add global event listeners for drag visualization
    document.addEventListener('mousemove', this.updateDragPosition);
    
    // Fetch user role to determine permissions
    this.fetchProjectMembers();
  },
  
  beforeUnmount() {
    // Clean up event listeners
    document.removeEventListener('mousemove', this.updateDragPosition);
  },
  
  methods: {
    async fetchProjectDetails(projectId) {
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      try {
        const response = await axios.get(`http://localhost:8000/api/projects/${projectId}`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        this.project = response.data;
        
        // Ensure all blocks have an items array
        if (response.data.blocks) {
          this.blocks = response.data.blocks.map(block => ({
            ...block,
            items: block.items || []
          }));
        } else {
          this.blocks = [];
        }
        
        console.log('Fetched Project Details:', this.project);
      } catch (error) {
        console.error('Error fetching project details:', error);
      }
    },
    
    async fetchProjectMembers() {
      const token = localStorage.getItem('token');
      if (!token) return;
      
      try {
        const projectId = this.$route.params.id;
        const response = await axios.get(`http://localhost:8000/api/projects/${projectId}/members`, {
          headers: { 'Authorization': `Bearer ${token}` }
        });
        
        this.projectMembers = response.data;
        
        // Set current user ID
        const user = JSON.parse(localStorage.getItem('user'));
        if (user) {
          this.currentUserId = user.id;
        }
        
        // Update UI to reflect view-only mode if needed
        if (this.isViewer) {
          this.disableEditingForViewer();
        }
      } catch (error) {
        console.error('Error fetching project members:', error);
      }
    },
    
    disableEditingForViewer() {
      // Hide all add/edit/delete buttons
      document.querySelectorAll('.add-block-btn, .edit-btn, .delete-btn, .add-item-btn').forEach(el => {
        el.style.display = 'none';
      });
      
      // Disable drag and drop functionality
      this.dragEnabled = false;
      
      // Add a visual indicator that the project is in view-only mode
      const header = document.querySelector('.project-header');
      if (header) {
        const viewOnlyBadge = document.createElement('div');
        viewOnlyBadge.className = 'view-only-badge';
        viewOnlyBadge.textContent = 'View Only Mode';
        viewOnlyBadge.style.backgroundColor = '#555';
        viewOnlyBadge.style.color = 'white';
        viewOnlyBadge.style.padding = '4px 8px';
        viewOnlyBadge.style.borderRadius = '4px';
        viewOnlyBadge.style.marginTop = '10px';
        viewOnlyBadge.style.fontSize = '0.9em';
        header.appendChild(viewOnlyBadge);
      }
    },
    
    formatDate(dateString) {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString();
    },
    
    async createBlock() {
      if (this.isViewer) return; // Prevent viewers from creating blocks
      if (!this.newBlockTitle.trim()) return;
      
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      try {
        const response = await axios.post(`http://localhost:8000/api/projects/${this.project.id}/blocks`, {
          title: this.newBlockTitle,
          order: this.blocks.length // Set the order to be at the end
        }, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        // Ensure new block has an items array
        this.blocks.push({
          ...response.data,
          items: response.data.items || []
        });
        
        this.newBlockTitle = '';
        this.showAddBlock = false;
      } catch (error) {
        console.error('Error creating block:', error);
        if (error.response?.status === 403) {
          alert('You do not have permission to modify this project.');
        }
      }
    },
    
    async deleteBlock(blockId) {
      if (this.isViewer) return; // Prevent viewers from deleting blocks
      if (!confirm('Are you sure you want to delete this column and all its cards?')) return;
      
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      try {
        await axios.delete(`http://localhost:8000/api/blocks/${blockId}`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        this.blocks = this.blocks.filter(block => block.id !== blockId);
      } catch (error) {
        console.error('Error deleting block:', error);
        if (error.response?.status === 403) {
          alert('You do not have permission to modify this project.');
        }
      }
    },
    
    editBlock(block) {
      if (this.isViewer) return; // Prevent viewers from editing blocks
      const newTitle = prompt('Enter new title for this column:', block.title);
      if (newTitle && newTitle.trim() !== '') {
        this.updateBlockTitle(block.id, newTitle);
      }
    },
    
    async updateBlockTitle(blockId, newTitle) {
      if (this.isViewer) return; // Prevent viewers from updating blocks
      
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      try {
        await axios.put(`http://localhost:8000/api/blocks/${blockId}`, {
          title: newTitle
        }, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        const index = this.blocks.findIndex(block => block.id === blockId);
        if (index !== -1) {
          this.blocks[index].title = newTitle;
        }
      } catch (error) {
        console.error('Error updating block:', error);
        if (error.response?.status === 403) {
          alert('You do not have permission to modify this project.');
        }
      }
    },
    
    // Column reordering methods
    onColumnDragEnd() {
      if (this.isViewer) return;
      this.isColumnDragging = false;
    },
    
    async onColumnChange(evt) {
      if (this.isViewer) return; // Prevent viewers from reordering columns
      console.log('Column change event:', evt);
      
      if (evt.moved) {
        // A column was reordered
        await this.reorderColumns();
      }
    },
    
    async reorderColumns() {
      if (this.isViewer) return; // Prevent viewers from reordering columns
      
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      try {
        // Get all block IDs in current order
        const blockIds = this.blocks.map(block => block.id);
        
        // Call API to update block orders
        await axios.post(`http://localhost:8000/api/projects/${this.project.id}/blocks/reorder`, {
          blocks: blockIds
        }, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        console.log('Column order updated successfully');
      } catch (error) {
        console.error('Error reordering columns:', error);
        if (error.response?.status === 403) {
          alert('You do not have permission to modify this project.');
        }
        // Refresh to ensure UI is in sync with backend
        this.fetchProjectDetails(this.project.id);
      }
    },
    
    addItem(blockId) {
      if (this.isViewer) return; // Prevent viewers from adding items
      
      this.currentBlockId = blockId;
      this.newItem = {
        title: '',
        description: '',
        due_date: null
      };
      this.showAddItemModal = true;
    },
    
    async createItem() {
      if (this.isViewer) return; // Prevent viewers from creating items
      if (!this.newItem.title.trim()) return;
      
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      try {
        const response = await axios.post(`http://localhost:8000/api/blocks/${this.currentBlockId}/items`, {
          title: this.newItem.title,
          description: this.newItem.description,
          due_date: this.newItem.due_date
        }, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        const blockIndex = this.blocks.findIndex(block => block.id === this.currentBlockId);
        if (blockIndex !== -1) {
          if (!this.blocks[blockIndex].items) {
            this.blocks[blockIndex].items = [];
          }
          this.blocks[blockIndex].items.push(response.data);
        }
        
        this.showAddItemModal = false;
      } catch (error) {
        console.error('Error creating item:', error);
        if (error.response?.status === 403) {
          alert('You do not have permission to modify this project.');
        }
      }
    },
    
    openItemDetails(item) {
      this.currentItem = { ...item };
      this.showItemModal = true;
    },
    
    async updateItem() {
      if (this.isViewer) return; // Prevent viewers from updating items
      
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      try {
        // Check if we're moving the item
        const originalBlockId = this.blocks.find(block => 
          block.items && block.items.some(i => i.id === this.currentItem.id)
        )?.id;
        
        if (originalBlockId !== this.currentItem.project_block_id) {
          // Moving item to a different block
          await axios.post(`http://localhost:8000/api/items/${this.currentItem.id}/move`, {
            block_id: this.currentItem.project_block_id
          }, {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });
        } else {
          // Just updating the item
          await axios.put(`http://localhost:8000/api/items/${this.currentItem.id}`, {
            title: this.currentItem.title,
            description: this.currentItem.description,
            due_date: this.currentItem.due_date
          }, {
            headers: {
              'Authorization': `Bearer ${token}`
            }
          });
        }
        
        // Refresh project data to get the updated state
        this.fetchProjectDetails(this.project.id);
        this.showItemModal = false;
      } catch (error) {
        console.error('Error updating item:', error);
        if (error.response?.status === 403) {
          alert('You do not have permission to modify this project.');
        }
      }
    },
    
    async deleteItem(itemId) {
      if (this.isViewer) return; // Prevent viewers from deleting items
      
      if (!confirm('Are you sure you want to delete this card?')) return;
      
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      try {
        await axios.delete(`http://localhost:8000/api/items/${itemId}`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        // Update local data
        this.blocks.forEach(block => {
          if (block.items) {
            block.items = block.items.filter(item => item.id !== itemId);
          }
        });
        
        this.showItemModal = false;
      } catch (error) {
        console.error('Error deleting item:', error);
        if (error.response?.status === 403) {
          alert('You do not have permission to modify this project.');
        }
      }
    },
    
    // Enhanced drag and drop methods
    onDragStart(evt) {
      if (this.isViewer || !this.dragEnabled) return; // Prevent drag for viewers
      
      console.log('Drag started', evt);
      this.isDragging = true;
      
      // Initialize mouse velocity tracking
      if (evt.originalEvent) {
        this.lastMousePosition = {
          x: evt.originalEvent.clientX,
          y: evt.originalEvent.clientY
        };
      }
      
      // Store the original element to get its data
      if (evt.item) {
        this.currentDragElement = evt.item;
        
        // Get dimensions of the dragged element for accurate positioning
        const rect = evt.item.getBoundingClientRect();
        this.dragCloneWidth = rect.width;
        this.dragCloneHeight = rect.height;
        
        this.createCustomDragImage(evt);
      }
      
      // Hide the default drag element
      setTimeout(() => {
        const dragEls = document.querySelectorAll('.sortable-drag');
        dragEls.forEach(dragEl => {
          if (dragEl) {
            dragEl.style.opacity = '0';
            dragEl.style.visibility = 'hidden';
            dragEl.style.pointerEvents = 'none';
          }
        });
      }, 0);
    },
    
    createCustomDragImage(evt) {
      // Get content from the dragged element
      if (!this.currentDragElement) return;
      
      const dragClone = this.$refs.dragClone;
      if (!dragClone) return;
      
      // Clone the content
      dragClone.innerHTML = this.currentDragElement.innerHTML;
      dragClone.className = 'drag-clone';
      
      // Position it initially - center horizontally under cursor
      if (evt.originalEvent) {
        const x = evt.originalEvent.clientX - (this.dragCloneWidth / 2); // Center horizontally
        const y = evt.originalEvent.clientY; // Align top with cursor
        dragClone.style.left = `${x}px`;
        dragClone.style.top = `${y}px`;
        dragClone.style.width = `${this.dragCloneWidth}px`;
        
        // Set the hold position to be at the middle-top of card
        dragClone.style.transformOrigin = '50% 0';
      }
    },
    
    updateDragPosition(e) {
      if (!this.isDragging || this.isViewer || !this.dragEnabled) return; // Don't update for viewers
      
      const dragClone = this.$refs.dragClone;
      if (!dragClone) return;
      
      // Calculate mouse velocity (speed and direction)
      const newMousePosition = { x: e.clientX, y: e.clientY };
      this.mouseVelocity = {
        x: newMousePosition.x - this.lastMousePosition.x,
        y: newMousePosition.y - this.lastMousePosition.y
      };
      
      // Determine the direction of movement
      if (this.mouseVelocity.x > 2) this.movingDirection = 'right';
      else if (this.mouseVelocity.x < -2) this.movingDirection = 'left';
      else this.movingDirection = 'neutral';
      
      this.lastMousePosition = newMousePosition;
      
      // Calculate tilt based on horizontal mouse velocity (now in same direction)
      // Increased responsiveness for more dramatic effect
      const maxTilt = 90; // Increased maximum tilt angle
      const responsiveness = 0.9; // Increased responsiveness
      
      // Target tilt is in the same direction as mouse movement (removed the negative sign)
      const targetTilt = this.mouseVelocity.x * responsiveness;
      
      // Smoothly interpolate current tilt towards target tilt
      this.tiltAngle = this.tiltAngle + (targetTilt - this.tiltAngle) * 0.3;
      
      // Cap maximum tilt angle
      if (this.tiltAngle > maxTilt) this.tiltAngle = maxTilt;
      if (this.tiltAngle < -maxTilt) this.tiltAngle = -maxTilt;
      
      // Position the card with the cursor at the middle-top
      dragClone.style.left = `${e.clientX - (this.dragCloneWidth / 2)}px`;
      dragClone.style.top = `${e.clientY}px`;
      
      // Apply the dynamic rotation based on mouse movement
      dragClone.style.transform = `rotate(${this.tiltAngle}deg)`;
      
      // Add perspective based on tilt to create more 3D effect
      const perspective = 500 - Math.abs(this.tiltAngle) * 10; // Dynamic perspective
      
      // Create a dynamic shadow effect based on tilt direction
      const shadowOffsetX = this.tiltAngle * 0.8; // Shadow follows the tilt
      const shadowBlur = 15 + Math.abs(this.tiltAngle) * 0.5; // More tilt = more blur
      const shadowColor = `rgba(0, 0, 0, ${0.5 + Math.abs(this.tiltAngle) / 100})`;
      
      dragClone.style.boxShadow = `${shadowOffsetX}px 10px ${shadowBlur}px ${shadowColor}`;
      dragClone.style.transform = `perspective(${perspective}px) rotateZ(${this.tiltAngle}deg)`;
    },
    
    onDragEnd(evt) {
      if (this.isViewer || !this.dragEnabled) return; // Don't process for viewers
      
      console.log('Drag ended', evt);
      this.isDragging = false;
      this.currentDragElement = null;
      this.tiltAngle = 0;
      this.movingDirection = 'none';
      
      // Clean up any lingering elements
      const dragElements = document.querySelectorAll('.sortable-drag');
      dragElements.forEach(el => {
        if (el && el.parentNode) {
          el.parentNode.removeChild(el);
        }
      });
      
      // Clear our custom drag clone
      if (this.$refs.dragClone) {
        this.$refs.dragClone.innerHTML = '';
        this.$refs.dragClone.style.transform = 'none';
        this.$refs.dragClone.style.boxShadow = 'none';
      }
    },
    
    async onChange(evt, blockId) {
      if (this.isViewer || !this.dragEnabled) return; // Prevent changes for viewers
      
      console.log('onChange event:', evt, 'for block:', blockId);
      
      if (evt.added) {
        // Item was added to this block from another block
        console.log('Item added to block:', blockId, evt.added);
        const item = evt.added.element;
        const newIndex = evt.added.newIndex;
        
        // Update the item's block association in the backend
        await this.moveItemToBlock(item.id, blockId, newIndex);
      } else if (evt.moved) {
        // Item was reordered within the same block
        console.log('Item reordered within block:', blockId);
        const itemIds = this.blocks.find(b => b.id === blockId).items.map(item => item.id);
        await this.reorderItems(blockId, itemIds);
      } else if (evt.removed) {
        // This is triggered on the source list when an item is moved away
        console.log('Item removed from block:', blockId);
      }
    },
    
    async moveItemToBlock(itemId, blockId, position) {
      if (this.isViewer) return; // Prevent viewers from moving items
      
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      console.log(`Moving item ${itemId} to block ${blockId} at position ${position}`);
      
      try {
        await axios.post(`http://localhost:8000/api/items/${itemId}/move`, {
          target_block_id: blockId, 
          position: position  
        }, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        console.log('Move operation sent to server successfully');
      } catch (error) {
        console.error('Error moving item:', error);
        if (error.response?.data?.errors) {
          // Log validation errors for debugging
          console.error('Validation errors:', error.response.data.errors);
        }
        if (error.response?.status === 403) {
          alert('You do not have permission to modify this project.');
        }
        // Refresh to ensure UI is in sync with backend
        this.fetchProjectDetails(this.project.id);
      }
    },
    
    async reorderItems(blockId, itemIds) {
      if (this.isViewer) return; // Prevent viewers from reordering items
      
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      try {
        // Format the items array with id and order properties
        const items = itemIds.map((id, index) => ({
          id: id,
          order: index
        }));
        
        await axios.post(`http://localhost:8000/api/blocks/${blockId}/items/reorder`, {
          items: items  // Now sending the properly formatted array
        }, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        console.log('Items reordered successfully');
      } catch (error) {
        console.error('Error reordering items:', error);
        if (error.response?.data?.errors) {
          console.error('Validation errors:', error.response.data.errors);
        }
        if (error.response?.status === 403) {
          alert('You do not have permission to modify this project.');
        }
        // Refresh to ensure UI is in sync with backend
        this.fetchProjectDetails(this.project.id);
      }
    }
  }
});
</script>
  
  <style scoped>
  /* Enhanced drag and drop styles */
  .drag-area {
    min-height: 50px;
    padding: 10px;
    display: flex;
    flex-direction: column;
    width: 100%;
    flex: 1;
    gap: 10px;
    position: relative;
    transition: height 0.3s ease;
  }
  
  /* Card styling */
  .block-item {
    cursor: grab;
    user-select: none;
    margin-bottom: 0; /* Remove bottom margin to use gap instead */
    transition: all 0.3s ease; /* Smoother transitions */
    background-color: #363636;
    border-radius: 4px;
    padding: 10px;
    position: relative;
    z-index: 1;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .block-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    background-color: #404040;
  }
  
  .block-item:active {
    cursor: grabbing;
  }
  
  /* Custom drag clone for better visibility */
  .drag-clone {
    position: fixed;
    pointer-events: none;
    z-index: 9999;
    background-color: #555;
    color: white;
    border-radius: 4px;
    padding: 10px;
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
    opacity: 0.95;
    transform-origin: 50% 0; /* Center top as rotation origin */
    transition: transform 0.05s cubic-bezier(0.2, 0, 0.3, 1); /* Faster, more responsive transition */
    border: 1px solid rgba(255, 255, 255, 0.1);
    will-change: transform; /* Optimize for animations */
  }
  
  /* Make the chosen card invisible but still taking up space in original location */
  .sortable-chosen {
    visibility: hidden !important; /* Hide but keep the space */
    opacity: 0 !important;
    position: relative;
    z-index: -1 !important;
  }
  
  /* Hide the library's drag element in favor of our custom one */
  .sortable-drag {
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
    position: absolute !important;
    z-index: -100 !important;
  }
  
  /* Column drag styles */
  .column-chosen {
    opacity: 0.6;
    background-color: #3a3a3a !important;
    box-shadow: 0 0 15px rgba(0, 0, 0, 0.3) !important;
    transform: scale(1.02);
    transition: all 0.2s ease !important;
  }
  
  .column-drag {
    opacity: 0.7 !important;
    background-color: #3a3a3a !important;
    transform: rotate(2deg) scale(1.02) !important;
    box-shadow: 0 15px 25px rgba(0, 0, 0, 0.3) !important;
    transition: all 0.2s ease !important;
    z-index: 100 !important;
  }
  
  .column-ghost {
    opacity: 0.3;
    background-color: #4c4c4c !important;
    border: 2px dashed rgba(255, 255, 255, 0.2) !important;
    border-radius: 10px !important;
    transition: all 0.2s ease;
  }
  
  /* Column header styling to indicate draggability */
  .block-header {
  padding: 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #444;
  cursor: grab !important;
  transition: background-color 0.2s ease;
  background-color: #2c2c2c; /* Ensure header has background */
  position: sticky;
  top: 0;
  z-index: 10;
}
  
  .block-header:hover {
    background-color: #353535;
  }
  
  .block-header:active {
    cursor: grabbing !important;
  }
  
  /* Add a trailing shadow effect to the bottom of our custom drag clone */
  .drag-clone:after {
    content: "";
    position: absolute;
    left: 10%;
    right: 10%;
    bottom: -10px;
    height: 15px;
    background: rgba(0, 0, 0, 0.4);
    filter: blur(6px);
    border-radius: 50%;
    transform: perspective(800px) rotateX(80deg) scale(0.8, 0.2);
    opacity: 0.7;
    z-index: -2;
    transition: all 0.05s ease-out;
  }
  
  /* Add side-specific lighting effects for enhanced 3D appearance */
  .drag-clone:before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
      to right,
      rgba(255, 255, 255, 0.1),
      rgba(255, 255, 255, 0) 30%,
      rgba(0, 0, 0, 0) 70%,
      rgba(0, 0, 0, 0.1)
    );
    pointer-events: none;
    border-radius: 4px;
    opacity: 0.7;
    transition: opacity 0.1s ease;
  }
  
  /* Smooth animation for other cards when making room */
  .sortable-fallback {
    transition: transform 0.3s cubic-bezier(0.2, 0, 0.2, 1) !important;
  }
  
  .block-item:not(.sortable-chosen):not(.sortable-drag) {
    transition: transform 0.3s cubic-bezier(0.2, 0, 0.2, 1);
  }
  
  /* Container styling */
  .items-container {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    min-height: 200px;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
  }
  
  /* Empty drag area styling */
  .drag-area:empty {
    min-height: 150px;
    position: relative;
  }
  
  .drag-area:empty::after {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 4px;
    border: 2px dashed rgba(255, 255, 255, 0.1);
    opacity: 0.5;
  }
  
  /* Project container styles */
  .project-detail-container {
    max-width: 100%;
    padding: 20px;
    color: #fff;
  }
  
  .project-header {
    margin-bottom: 30px;
  }
  
  .project-header h1 {
    font-size: 2em;
    margin-bottom: 10px;
  }
  
  .project-description {
    color: #ccc;
  }
  
  .board-container {
    overflow-x: auto;
    padding-bottom: 20px;
  }
  
  .blocks-wrapper {
    display: flex;
    gap: 20px;
    min-height: 70vh;
    align-items: flex-start;
    padding-bottom: 10px;
  }
  
  .block-column {
    background-color: #2c2c2c;
    border-radius: 8px;
    width: 300px;
    min-width: 300px;
    display: flex;
    flex-direction: column;
    transition: all 0.2s ease;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }
  
  .block-header h3 {
    margin: 0;
    font-size: 1.1em;
  }
  
  .block-actions {
    display: flex;
    gap: 8px;
  }
  
  .edit-btn, .delete-btn {
    background: transparent;
    border: none;
    color: #aaa;
    cursor: pointer;
    font-size: 1em;
    padding: 0 5px;
  }
  
  .delete-btn {
    font-size: 1.5em;
    line-height: 0.8;
  }
  
  .edit-btn:hover, .delete-btn:hover {
    color: #fff;
  }
  
  .block-item h4 {
    margin: 0 0 8px 0;
    font-size: 1em;
  }

  .project-detail-container{
    max-width: 100%;
    padding: 20px;
    color: #fff;
    padding-top: 100px;
  }

  .item-description {
    font-size: 0.9em;
    color: #bbb;
    margin-bottom: 10px;
  }
  
  .due-date {
    font-size: 0.8em;
    color: #f0ad4e;
  }
  
  .add-item-btn {
    background-color: transparent;
    border: none;
    color: #aaa;
    cursor: pointer;
    padding: 10px;
    text-align: left;
    border-top: 1px solid #444;
  }
  
  .add-item-btn:hover {
    background-color: #333;
    color: #fff;
  }
  
  .add-block-column {
    min-width: 300px;
    display: flex;
    align-items: flex-start;
  }
  
  .add-block-btn {
    background-color: rgba(255, 255, 255, 0.1);
    border: none;
    border-radius: 8px;
    color: #fff;
    cursor: pointer;
    padding: 15px;
    width: 100%;
    text-align: left;
    font-size: 1em;
  }
  
  .add-block-btn:hover {
    background-color: rgba(255, 255, 255, 0.2);
  }
  
  .add-block-form {
    background-color: #2c2c2c;
    border-radius: 8px;
    padding: 10px;
    width: 100%;
  }
  
  .add-block-form input {
    width: 100%;
    padding: 8px;
    margin-bottom: 10px;
    background-color: #404040;
    border: 1px solid #555;
    border-radius: 4px;
    color: #fff;
  }
  
  .add-block-actions {
    display: flex;
    gap: 10px;
  }
  
  .save-btn, .cancel-btn {
    padding: 8px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }
  
  .save-btn {
    background-color: #000;
    color: white;
  }
  
  .cancel-btn {
    background-color: #555;
    color: white;
  }
  
  .save-btn:hover {
    background-color: #252525;
  }
  
  .cancel-btn:hover {
    background-color: #444;
  }
  
  /* Modal styles */
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
  
  .form-group {
    margin-bottom: 20px;
  }
  
  .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
  }
  
  .form-group input,
  .form-group textarea,
  .form-group select {
    width: 100%;
    padding: 10px;
    background-color: #2c2c2c;
    border: 1px solid #555;
    border-radius: 5px;
    color: #fff;
  }
  
  .form-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 20px;
  }
  </style>