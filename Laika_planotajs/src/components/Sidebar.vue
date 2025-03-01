<template>
  <div class="sidebar" :class="{ 'open': isOpen }">
    <div class="sidebar-header">
      <h2>{{ formattedDate }}</h2>
      <button class="close-button" @click="$emit('close')">×</button>
    </div>
    
    <div class="sidebar-content">
      <div class="timeline">
        <div class="time-column">
          <div v-for="hour in 24" :key="hour" class="hour-label">
            {{ formatHour(hour - 1) }}
          </div>
        </div>
        
        <div class="events-area">
          <div v-for="hour in 24" :key="hour" class="hour-guideline"></div>
          
          <div class="current-time-line" :style="currentTimeStyle" v-if="isToday"></div>
          
          <div 
            v-for="event in processedEvents" 
            :key="event.id" 
            class="event" 
            :style="getEventStyle(event)"
            @click="editEvent(event)"
          >
            <div class="event-title">{{ event.title }}</div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="sidebar-footer">
      <button class="add-event" @click="showAddEventForm = true">+ Add Event</button>
    </div>
    
    <div class="event-modal-overlay" v-if="showAddEventForm || editingEvent" @click="cancelEdit">
      <div class="event-modal" @click.stop>
        <h3>{{ editingEvent ? 'Event Details' : 'Add New Event' }}</h3>
        
        <form @submit.prevent="saveEvent">
          <div class="form-group">
            <label>Title</label>
            <input type="text" v-model="eventForm.title" required>
          </div>
          
          <div class="form-group">
            <label>Description</label>
            <textarea v-model="eventForm.description" rows="3" placeholder="Event description..."></textarea>
          </div>
          
          <div class="form-time">
            <div class="form-group">
              <label>Start Time</label>
              <input type="time" v-model="eventForm.startTime" required>
            </div>
            
            <div class="form-group">
              <label>End Time</label>
              <input type="time" v-model="eventForm.endTime" required>
            </div>
          </div>
          
          <div class="form-buttons">
            <button type="button" @click="cancelEdit" class="btn-cancel">Cancel</button>
            <button type="submit" class="btn-save">Save</button>
            <button 
              type="button" 
              v-if="editingEvent" 
              @click="deleteEvent" 
              class="btn-delete"
            >Delete</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    selectedDate: {
      type: String,
      default: ''
    },
    events: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      currentTime: new Date(),
      showAddEventForm: false,
      editingEvent: null,
      eventForm: {
        title: '',
        description: '',
        startTime: '',
        endTime: ''
      },
      timeInterval: null
    };
  },
  computed: {
    formattedDate() {
      if (!this.selectedDate) return '';
      const date = new Date(this.selectedDate);
      return date.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    },
    isToday() {
      if (!this.selectedDate) return false;
      const today = new Date();
      const selected = new Date(this.selectedDate);
      return today.toDateString() === selected.toDateString();
    },
    currentTimeStyle() {
      const now = this.currentTime;
      const hours = now.getHours();
      const minutes = now.getMinutes();
      const top = (hours * 60 + minutes); 
      return {
        top: `${top}px`
      };
    },
    processedEvents() {
      // Sort events by start time
      const sortedEvents = [...this.events].sort((a, b) => {
        let timeA = a.start_time;
        let timeB = b.start_time;
        
        if (timeA.includes(' ')) timeA = timeA.split(' ')[1];
        if (timeB.includes(' ')) timeB = timeB.split(' ')[1];
        
        return new Date(`2000-01-01T${timeA}`) - new Date(`2000-01-01T${timeB}`);
      });
      
      // Process events to handle overlaps
      const processed = [];
      for (const event of sortedEvents) {
        const processedEvent = { ...event };
        
        // Find overlapping events
        const overlaps = processed.filter(e => this.eventsOverlap(processedEvent, e));
        
        if (overlaps.length > 0) {
          // Calculate positioning for overlapping events
          processedEvent.column = 0;
          while (overlaps.some(e => e.column === processedEvent.column)) {
            processedEvent.column++;
          }
          
          // Find maximum number of columns needed
          const maxColumn = Math.max(...overlaps.map(e => e.column), processedEvent.column);
          
          // Set width for all overlapping events
          const width = 100 / (maxColumn + 1);
          
          processedEvent.width = width;
          overlaps.forEach(e => {
            e.width = width;
          });
        } else {
          processedEvent.column = 0;
          processedEvent.width = 100;
        }
        
        processed.push(processedEvent);
      }
      
      return processed;
    }
  },
  mounted() {
    // Update current time every minute
    this.timeInterval = setInterval(() => {
      this.currentTime = new Date();
    }, 60000);
    
    // Add document click listener to detect outside clicks
    document.addEventListener('click', this.handleOutsideClick);
  },
  
  beforeUnmount() {
    clearInterval(this.timeInterval);
    // Clean up the event listener
    document.removeEventListener('click', this.handleOutsideClick);
  },
  methods: {
    formatHour(hour) {
      const h = hour % 24;
      // Format as 24-hour clock with leading zeros
      return `${h.toString().padStart(2, '0')}:00`;
    },
    formatTime(timeString) {
      if (!timeString) return '';
      
      // Extract just the time portion if it's a datetime string
      let time = timeString;
      if (timeString.includes(' ')) {
        time = timeString.split(' ')[1];
      }
      
      // Extract hours and minutes
      const [hours, minutes] = time.split(':');
      // Return in 24-hour format
      return `${hours.padStart(2, '0')}:${minutes}`;
    },

    handleOutsideClick(event) {
      // Only process if sidebar is open
      if (!this.isOpen) return;
      
      // Get sidebar element
      const sidebar = document.querySelector('.sidebar');
      
      // Check if click is outside sidebar and not on any modal
      if (sidebar && 
          !sidebar.contains(event.target) && 
          !event.target.closest('.event-modal-overlay')) {
        this.$emit('close');
      }
    },
    
    formatEventTime(event) {
      // Format event start and end times
      if (!event || !event.start_time || !event.end_time) return '';
      const start = this.formatTime(event.start_time);
      const end = this.formatTime(event.end_time);
      return `${start} - ${end}`;
    },
    
    getEventStyle(event) {
      // Calculate position and height based on start and end times
      let startTime = event.start_time;
      let endTime = event.end_time;
      
      // Extract just the time portion if it's a datetime string
      if (startTime.includes(' ')) {
        startTime = startTime.split(' ')[1];
      }
      if (endTime.includes(' ')) {
        endTime = endTime.split(' ')[1];
      }
      
      const [startHours, startMinutes] = startTime.split(':').map(Number);
      const [endHours, endMinutes] = endTime.split(':').map(Number);
      
      const startInMinutes = startHours * 60 + startMinutes;
      const endInMinutes = endHours * 60 + endMinutes;
      const durationInMinutes = endInMinutes - startInMinutes;
      
      // 60px per hour = 1px per minute
      const topPosition = startInMinutes;
      const height = durationInMinutes > 0 ? durationInMinutes : 30;
      
      return {
        position: 'absolute',
        top: `${topPosition}px`,
        height: `${height}px`,
        left: `${event.column * event.width}%`,
        width: `${event.width}%`,
        backgroundColor: this.getRandomColor(event.id)
      };
    },
    getRandomColor(id) {
      // Generate a consistent color based on the event id
      const colors = [
        '#FFADAD', '#FFD6A5', '#FDFFB6', '#CAFFBF', 
        '#9BF6FF', '#A0C4FF', '#BDB2FF', '#FFC6FF'
      ];
      return colors[id % colors.length];
    },
    eventsOverlap(event1, event2) {
      // Extract just the time portion for comparison
      let start1 = event1.start_time;
      let end1 = event1.end_time;
      let start2 = event2.start_time;
      let end2 = event2.end_time;
      
      // Extract time portion if it's a datetime string
      if (start1.includes(' ')) start1 = start1.split(' ')[1];
      if (end1.includes(' ')) end1 = end1.split(' ')[1];
      if (start2.includes(' ')) start2 = start2.split(' ')[1];
      if (end2.includes(' ')) end2 = end2.split(' ')[1];
      
      // Compare just the time portions
      const start1Time = new Date(`2000-01-01T${start1}`);
      const end1Time = new Date(`2000-01-01T${end1}`);
      const start2Time = new Date(`2000-01-01T${start2}`);
      const end2Time = new Date(`2000-01-01T${end2}`);
      
      return start1Time < end2Time && start2Time < end1Time;
    },
    editEvent(event) {
      this.editingEvent = event;
      
      // Extract just the time portion for the form inputs
      let startTime = event.start_time;
      let endTime = event.end_time;
      
      if (startTime.includes(' ')) {
        startTime = startTime.split(' ')[1];
      }
      if (endTime.includes(' ')) {
        endTime = endTime.split(' ')[1];
      }

      // Remove seconds if present
      startTime = startTime.split(':').slice(0, 2).join(':');
      endTime = endTime.split(':').slice(0, 2).join(':');
      
      this.eventForm = {
        title: event.title,
        description: event.description || '',
        startTime: startTime,
        endTime: endTime
      };
    },
    cancelEdit() {
      this.showAddEventForm = false;
      this.editingEvent = null;
      this.eventForm = {
        title: '',
        description: '',
        startTime: '',
        endTime: ''
      };
    },
    saveEvent() {
      if (this.editingEvent) {
        const updatedEvent = {
          ...this.editingEvent, 
          title: this.eventForm.title,
          description: this.eventForm.description,
          start_time: this.eventForm.startTime,
          end_time: this.eventForm.endTime
        };
        this.$emit('update-event', updatedEvent);
      } else {
        const newEvent = {
          title: this.eventForm.title,
          description: this.eventForm.description,
          start_time: this.eventForm.startTime,
          end_time: this.eventForm.endTime,
          date: this.selectedDate
        };
        this.$emit('add-event', newEvent);
      }
      this.cancelEdit();
    },
    deleteEvent() {
      if (this.editingEvent) {
        if (confirm("Are you sure you want to delete this event?")) {
          this.$emit('delete-event', this.editingEvent);
          this.cancelEdit();
        }
      }
    }
  }
};
</script>

<style>
@import '../assets/sidebar.css';
</style>