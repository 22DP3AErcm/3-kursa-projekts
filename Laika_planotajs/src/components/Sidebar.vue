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
          
          <!-- REMINDERS SECTION -->
          <div class="reminders-section">
            <h4>Reminders</h4>
            
            <!-- Existing reminders list -->
            <div v-if="eventReminders.length > 0" class="reminders-list">
              <div v-for="(reminder, index) in eventReminders" :key="index" class="reminder-item">
                <span>{{ getReminderText(reminder) }}</span>
                <button type="button" class="delete-reminder-btn" @click="removeReminder(index)">×</button>
              </div>
            </div>

            <!-- Apply preset dropdown -->
            <div class="presets-dropdown">
              <label>Apply Preset:</label>
              <select v-model="selectedPreset" @change="applyPreset">
                <option value="">Select a preset</option>
                <option v-for="(preset, name) in savedPresets" :key="name" :value="name">{{ name }}</option>
              </select>
            </div>
            
            <!-- Add reminder form -->
            <div class="add-reminder-form">
              <div class="reminder-inputs">
                <select v-model="newReminder.type" class="reminder-input">
                  <option value="email">Email</option>
                  <option value="sms">SMS</option>
                </select>
                
                <select v-model="newReminder.timeValue" class="reminder-input">
                  <option value="5">5</option>
                  <option value="10">10</option>
                  <option value="15">15</option>
                  <option value="30">30</option>
                  <option value="45">45</option>
                  <option value="60">60</option>
                  <option value="120">120</option>
                  <option value="180">180</option>
                  <option value="1440">1440</option>
                  <option value="2880">2880</option>
                  <option value="4320">4320</option>
                  <option value="7200">7200</option>
                  <option value="10080">10080</option>
                </select>
                
                <select v-model="newReminder.timeUnit" class="reminder-input">
                  <option value="minutes">minutes</option>
                  <option value="hours">hours</option>
                  <option value="days">days</option>
                  <option value="weeks">weeks</option>
                </select>
                
                <button type="button" class="add-btn" @click="addReminder">
                  +
                </button>
              </div>
            </div>
            
            <!-- Save preset form -->
            <div class="save-preset-form" v-if="eventReminders.length > 0">
              <div class="preset-inputs">
                <input 
                  type="text" 
                  v-model="presetName" 
                  placeholder="Preset name" 
                  class="preset-input">
                <button type="button" class="save-preset-btn" @click="savePreset">
                  Save as Preset
                </button>
              </div>
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
import axios from 'axios';

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
      isProcessingEvent: false,
      eventForm: {
        title: '',
        description: '',
        startTime: '',
        endTime: ''
      },
      timeInterval: null,
      
      // Reminder data
      eventReminders: [],
      newReminder: {
        type: 'email',
        timeValue: '30',
        timeUnit: 'minutes'
      },
      savedPresets: {},
      selectedPreset: '',
      presetName: ''
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
    
    // Load saved presets from localStorage
    const savedPresets = localStorage.getItem('reminderPresets');
    if (savedPresets) {
      this.savedPresets = JSON.parse(savedPresets);
    }
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
      
      // Load existing reminders if any
      this.eventReminders = event.reminders ? [...event.reminders] : [];
      
      // Fix: Log loaded reminders for debugging
      console.log('Loaded existing reminders:', JSON.stringify(this.eventReminders));
    },

    formatForDatabase(dateStr, timeStr) {
      // Ensure time has seconds
      if (timeStr.split(':').length === 2) {
        timeStr = `${timeStr}:00`;
      }
      return `${dateStr} ${timeStr}`;
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
      this.eventReminders = [];
      this.selectedPreset = '';
      this.presetName = '';
    },
    
    async saveEvent() {
      // Add processing flag to prevent duplicate submissions
      if (this.isProcessingEvent) {
        console.log('Event submission already in progress, preventing duplicate');
        return;
      }
      
      this.isProcessingEvent = true;
      
      try {
        const token = localStorage.getItem('token');
        if (!token) {
          alert('You need to be logged in to save events');
          return;
        }
    
        // FIX: Check for empty event reminders and prepare them properly
        const cleanReminders = this.prepareReminders();
        console.log('Prepared reminders:', cleanReminders);
    
        // Validate that end time is after start time
        const startTime = new Date(`2000-01-01T${this.eventForm.startTime}`);
        const endTime = new Date(`2000-01-01T${this.eventForm.endTime}`);
        if (endTime <= startTime) {
          alert('End time must be after start time');
          return;
        }
    
        // Get user information
        const user = JSON.parse(localStorage.getItem('user'));
        if (!user) {
          alert('User information not found. Please log in again.');
          return;
        }
    
        if (this.editingEvent) {
          try {
            // Extract date part from original event
            let originalDatePart = this.selectedDate;
            if (this.editingEvent.start_time && this.editingEvent.start_time.includes(' ')) {
              originalDatePart = this.editingEvent.start_time.split(' ')[0];
            }
            
            // FIXED: Include user_id in the event payload
            const updatedEvent = {
              user_id: user.id,
              title: this.eventForm.title,
              description: this.eventForm.description,
              start_time: `${originalDatePart} ${this.eventForm.startTime}:00`,
              end_time: `${originalDatePart} ${this.eventForm.endTime}:00`
            };
            
            console.log('Sending update with data:', updatedEvent);
            
            // First delete existing reminders
            if (this.editingEvent.reminders && this.editingEvent.reminders.length > 0) {
              for (const reminder of this.editingEvent.reminders) {
                await axios.delete(
                  `http://localhost:8000/api/reminders/${reminder.id}`,
                  { headers: { 'Authorization': `Bearer ${token}` } }
                );
              }
            }
            
            // Update the event with proper error handling
            const response = await axios.put(
              `http://localhost:8000/api/events/${this.editingEvent.id}`,
              updatedEvent,
              { headers: { 'Authorization': `Bearer ${token}` } }
            );
            
            // Get the updated event with its new times
            const updatedEventData = response.data;
            
            // Debug reminders array before processing
            console.log('Reminders to be added:', JSON.stringify(cleanReminders));
            
            // Now add the new reminders for the updated event
            const newReminders = [];
            
            // Process each reminder with improved validation
            for (const reminder of cleanReminders) {
              // Skip invalid reminders
              if (!reminder.minutes_before || isNaN(reminder.minutes_before) || reminder.minutes_before <= 0) {
                console.warn('Skipping invalid reminder:', reminder);
                continue;
              }
              
              console.log('Creating reminder with data:', { 
                type: reminder.type, 
                minutes_before: reminder.minutes_before 
              });
              
              try {
                const reminderResponse = await axios.post(
                  `http://localhost:8000/api/events/${this.editingEvent.id}/reminders`,
                  {
                    type: reminder.type,
                    minutes_before: reminder.minutes_before
                  },
                  { headers: { 'Authorization': `Bearer ${token}` } }
                );
                
                console.log('Reminder created successfully:', reminderResponse.data);
                newReminders.push(reminderResponse.data);
              } catch (reminderError) {
                console.error('Failed to create reminder:', reminderError);
                if (reminderError.response && reminderError.response.data) {
                  console.error('Reminder validation error:', reminderError.response.data);
                }
                // Continue with other reminders
              }
            }
            
            // Create complete event with new reminders
            const updatedEventWithReminders = {
              ...updatedEventData,
              reminders: newReminders
            };
            
            console.log('Updated event with reminders:', updatedEventWithReminders);
            
            // FIX: Only emit one update event to avoid duplication
            this.$emit('update-event', updatedEventWithReminders);
            
          } catch (error) {
            console.error('Error updating event:', error);
            if (error.response && error.response.data) {
              console.error('Validation errors:', error.response.data);
              if (error.response.data.errors) {
                const errorMessages = Object.values(error.response.data.errors).flat().join('\n');
                alert(`Update failed: ${errorMessages}`);
              } else {
                alert(`Update failed: ${error.response.data.message || 'Unknown error'}`);
              }
            } else {
              alert('Error updating event. Please check your connection and try again.');
            }
          }
        } else {
          // Fixed: Create new event implementation
          try {
            // Create new event
            const newEvent = {
              user_id: user.id,
              title: this.eventForm.title,
              description: this.eventForm.description,
              start_time: `${this.selectedDate} ${this.eventForm.startTime}:00`,
              end_time: `${this.selectedDate} ${this.eventForm.endTime}:00`
            };
            
            console.log('Creating new event:', newEvent);
            
            // Create the event
            const response = await axios.post(
              'http://localhost:8000/api/events',
              newEvent,
              { headers: { 'Authorization': `Bearer ${token}` } }
            );
            
            const createdEvent = response.data;
            console.log('Event created:', createdEvent);
            
            // Now add reminders
            const newReminders = [];
            
            for (const reminder of cleanReminders) {
              // Skip invalid reminders
              if (!reminder.minutes_before || isNaN(reminder.minutes_before)) {
                console.warn('Skipping invalid reminder:', reminder);
                continue;
              }
              
              try {
                const reminderResponse = await axios.post(
                  `http://localhost:8000/api/events/${createdEvent.id}/reminders`,
                  {
                    type: reminder.type,
                    minutes_before: reminder.minutes_before
                  },
                  { headers: { 'Authorization': `Bearer ${token}` } }
                );
                
                newReminders.push(reminderResponse.data);
              } catch (error) {
                console.error('Failed to create reminder:', error);
              }
            }
            
            // Create complete event with reminders
            const eventWithReminders = {
              ...createdEvent,
              reminders: newReminders
            };
            
            console.log('Event created with reminders:', eventWithReminders);
            
            // FIX: Use a one-time flag to prevent duplicate emission
            this.$emit('add-event', eventWithReminders);
            
          } catch (error) {
            console.error('Error creating event:', error);
            
            if (error.response && error.response.data) {
              console.error('Validation errors:', error.response.data);
              if (error.response.data.errors) {
                const errorMessages = Object.values(error.response.data.errors).flat().join('\n');
                alert(`Create failed: ${errorMessages}`);
              } else {
                alert(`Create failed: ${error.response.data.message || 'Unknown error'}`);
              }
            } else {
              alert('Error creating event. Please check your connection and try again.');
            }
          }
        }
      } catch (error) {
        console.error('Error in event processing:', error);
      } finally {
        // Reset the processing flag regardless of success or failure
        this.isProcessingEvent = false;
        this.cancelEdit();
      }
    },
    
    deleteEvent() {
      if (this.editingEvent) {
        if (confirm("Are you sure you want to delete this event?")) {
          this.$emit('delete-event', this.editingEvent);
          this.cancelEdit();
        }
      }
    },
    
    // FIX: New method to prepare reminders for backend
    prepareReminders() {
      return this.eventReminders.map(reminder => {
        let minutes_before;
        
        // Extract minutes_before from existing reminder or calculate from timeValue/timeUnit
        if (reminder.minutes_before !== undefined) {
          minutes_before = parseInt(reminder.minutes_before);
        } else if (reminder.timeValue !== undefined) {
          let minutes = parseInt(reminder.timeValue);
          
          if (reminder.timeUnit === 'hours') {
            minutes *= 60;
          } else if (reminder.timeUnit === 'days') {
            minutes *= 1440; // 24 * 60
          } else if (reminder.timeUnit === 'weeks') {
            minutes *= 10080; // 7 * 24 * 60
          }
          
          minutes_before = minutes;
        }
        
        return {
          type: reminder.type || 'email',
          minutes_before: minutes_before
        };
      }).filter(r => r.minutes_before && !isNaN(r.minutes_before));
    },
    
    // IMPROVED: Reminder methods
    calculateMinutesBefore(reminder) {
      // Handle backend reminders with minutes_before
      if (reminder.minutes_before !== undefined) {
        return parseInt(reminder.minutes_before);
      }
      
      // Handle frontend reminders with timeValue/timeUnit
      if (!reminder || !reminder.timeValue) {
        return null;
      }
      
      let minutes = parseInt(reminder.timeValue);
      if (isNaN(minutes)) {
        console.error('Invalid reminder time value:', reminder.timeValue);
        return null;
      }
      
      // Convert to minutes based on time unit
      if (reminder.timeUnit === 'hours') {
        minutes *= 60;
      } else if (reminder.timeUnit === 'days') {
        minutes *= 1440; // 24 * 60
      } else if (reminder.timeUnit === 'weeks') {
        minutes *= 10080; // 7 * 24 * 60
      }
      
      return minutes;
    },
    
    getReminderText(reminder) {
      const type = reminder.type || 'unknown';
      let timeText = '';
      
      if (reminder.minutes_before) {
        // For backend reminders with just minutes_before
        const mins = reminder.minutes_before;
        if (mins < 60) {
          timeText = `${mins} minutes before`;
        } else if (mins === 60) {
          timeText = '1 hour before';
        } else if (mins < 1440) {
          timeText = `${mins / 60} hours before`;
        } else if (mins === 1440) {
          timeText = '1 day before';
        } else if (mins < 10080) {
          timeText = `${mins / 1440} days before`;
        } else {
          timeText = `${mins / 10080} weeks before`;
        }
      } else if (reminder.timeValue && reminder.timeUnit) {
        // For frontend reminders with timeValue and timeUnit
        timeText = `${reminder.timeValue} ${reminder.timeUnit} before`;
      }
      
      return `${type.charAt(0).toUpperCase() + type.slice(1)} reminder: ${timeText}`;
    },
    
    addReminder() {
      // Calculate minutes_before for new reminder
      const minutesBefore = this.calculateMinutesBefore(this.newReminder);
      
      if (!minutesBefore || minutesBefore <= 0) {
        alert('Please set a valid reminder time');
        return;
      }
      
      // Add the current reminder to the list
      this.eventReminders.push({
        type: this.newReminder.type,
        timeValue: this.newReminder.timeValue,
        timeUnit: this.newReminder.timeUnit,
        minutes_before: minutesBefore
      });
      
      console.log('Added reminder with minutes_before:', minutesBefore);
      
      // Reset the form to default values
      this.newReminder = {
        type: 'email',
        timeValue: '30',
        timeUnit: 'minutes'
      };
    },
    
    removeReminder(index) {
      this.eventReminders.splice(index, 1);
    },
    
    savePreset() {
      if (!this.presetName.trim()) {
        alert('Please enter a name for your preset');
        return;
      }
      
      if (this.eventReminders.length === 0) {
        alert('Please add at least one reminder to save as a preset');
        return;
      }
      
      // Create a copy of the current reminders
      const presetData = this.eventReminders.map(reminder => ({
        type: reminder.type,
        timeValue: reminder.timeValue || (reminder.minutes_before.toString()),
        timeUnit: reminder.timeUnit || this.determineTimeUnit(reminder.minutes_before),
        minutes_before: reminder.minutes_before
      }));
      
      // Save to local state
      this.savedPresets = {
        ...this.savedPresets,
        [this.presetName]: presetData
      };
      
      // Save to localStorage
      localStorage.setItem('reminderPresets', JSON.stringify(this.savedPresets));
      
      alert(`Preset "${this.presetName}" saved successfully`);
      this.presetName = '';
    },
    
    applyPreset() {
      if (!this.selectedPreset || !this.savedPresets[this.selectedPreset]) {
        return;
      }
      
      // Load the preset reminders
      this.eventReminders = [...this.savedPresets[this.selectedPreset]];
      this.selectedPreset = '';
    },
    
    determineTimeUnit(minutes) {
      if (minutes < 60) {
        return 'minutes';
      } else if (minutes < 1440) {
        return 'hours';
      } else if (minutes < 10080) {
        return 'days';
      } else {
        return 'weeks';
      }
    }
  }
};
</script>

<style>
@import '../assets/sidebar.css';
</style>