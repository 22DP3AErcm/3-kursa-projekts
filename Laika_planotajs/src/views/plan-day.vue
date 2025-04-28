<template>
  <div class="plan-day-container">
    <div class="calendar">
      <div class="calendar-header">
        <button class="nav-button" @click="prevMonth">&laquo; Previous</button>
        <span class="month-display">{{ monthNames[currentMonth] }} {{ currentYear }}</span>
        <button class="nav-button" @click="nextMonth">Next &raquo;</button>
      </div>
      <div class="calendar-body">
        <div class="calendar-weekdays">
          <div v-for="day in weekDays" :key="day" class="weekday">{{ day }}</div>
        </div>
        <div class="calendar-days">
          <button 
            v-for="day in daysInMonth" 
            :key="day.date" 
            :class="{ 
              'calendar-day': true,
              'current-day': isCurrentDay(day.date),
              'has-events': hasEvents(day.date),
              'has-reminders': hasReminders(day.date),
              'empty-day': !day.date
            }" 
            @click="openSidebar(day.date)"
            :disabled="!day.date"
          >
            <span class="day-number">{{ day.date ? day.date.getDate() : '' }}</span>
            <div v-if="hasEvents(day.date)" class="event-indicator"></div>
            <div v-if="hasReminders(day.date)" class="reminder-indicator"></div>
          </button>
        </div>
      </div>
    </div>
    
    <Sidebar 
      :isOpen="isSidebarOpen" 
      :selectedDate="selectedDate" 
      :events="events" 
      @close="closeSidebar" 
      @add-event="addEvent" 
      @update-event="updateEvent"
      @delete-event="deleteEvent" 
    />
  </div>
</template>

<script>
import Sidebar from '../components/Sidebar.vue';
import axios from 'axios';

export default {
  components: {
    Sidebar
  },
  data() {
    return {
      currentYear: new Date().getFullYear(),
      currentMonth: new Date().getMonth(),
      weekDays: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
      monthNames: [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
      ],
      isSidebarOpen: false,
      selectedDate: '',
      events: [],
      // Store all month events for indicators
      monthEvents: {},
      loading: false,
      error: null
    };
  },
  computed: {
    daysInMonth() {
      const days = [];
      const firstDay = new Date(this.currentYear, this.currentMonth, 1).getDay();
      const lastDate = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();

      // Add empty slots for days before the first day of month
      for (let i = 0; i < firstDay; i++) {
        days.push({ date: null });
      }

      // Add the actual days of the month
      for (let date = 1; date <= lastDate; date++) {
        days.push({ date: new Date(this.currentYear, this.currentMonth, date) });
      }

      return days;
    }
  },
  watch: {
    // Watch for month/year changes to reload calendar data
    currentMonth() {
      this.fetchMonthEvents();
    },
    currentYear() {
      this.fetchMonthEvents();
    }
  },
  mounted() {
    // Fetch events for the current month when component mounts
    this.fetchMonthEvents();
    
    // Check URL for date parameter to open calendar at specific date
    const urlParams = new URLSearchParams(window.location.search);
    const dateParam = urlParams.get('date');
    if (dateParam) {
      try {
        const date = new Date(dateParam);
        if (!isNaN(date.getTime())) {
          this.currentYear = date.getFullYear();
          this.currentMonth = date.getMonth();
          // Open sidebar for this date after events are loaded
          setTimeout(() => {
            this.openSidebar(date);
          }, 500);
        }
      } catch (e) {
        console.error("Invalid date in URL parameter:", e);
      }
    }
  },
  methods: {
    prevMonth() {
      if (this.currentMonth === 0) {
        this.currentMonth = 11;
        this.currentYear--;
      } else {
        this.currentMonth--;
      }
    },
    nextMonth() {
      if (this.currentMonth === 11) {
        this.currentMonth = 0;
        this.currentYear++;
      } else {
        this.currentMonth++;
      }
    },
    isCurrentDay(date) {
      if (!date) return false;
      const today = new Date();
      return date.getDate() === today.getDate() &&
             date.getMonth() === today.getMonth() &&
             date.getFullYear() === today.getFullYear();
    },
    formatDateForAPI(date) {
      if (!date) return '';
      const year = date.getFullYear();
      const month = (date.getMonth() + 1).toString().padStart(2, '0');
      const day = date.getDate().toString().padStart(2, '0');
      return `${year}-${month}-${day}`;
    },
    async openSidebar(date) {
      if (!date) return;
      
      // Format with proper leading zeros
      this.selectedDate = this.formatDateForAPI(date);
      console.log("Opening sidebar with date:", this.selectedDate);
      
      await this.fetchEvents(date);
      this.isSidebarOpen = true;
    },
    closeSidebar() {
      this.isSidebarOpen = false;
      // Clear selected events when closing
      this.events = [];
    },
    // Check if a day has any events
    hasEvents(date) {
      if (!date) return false;
      const formattedDate = this.formatDateForAPI(date);
      return this.monthEvents[formattedDate] && this.monthEvents[formattedDate].length > 0;
    },
    // Check if a day has any reminders
    hasReminders(date) {
      if (!date) return false;
      const formattedDate = this.formatDateForAPI(date);
      if (!this.monthEvents[formattedDate]) return false;
      
      // Check if any event on this day has reminders
      return this.monthEvents[formattedDate].some(event => 
        event.reminders && event.reminders.length > 0
      );
    },
    async fetchMonthEvents() {
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }

      this.loading = true;
      this.error = null;
      this.monthEvents = {};
      
      try {
        // Get first and last day of the month
        const firstDay = new Date(this.currentYear, this.currentMonth, 1);
        const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
        
        const startDate = this.formatDateForAPI(firstDay);
        const endDate = this.formatDateForAPI(lastDay);
        
        const response = await axios.get(
          `http://localhost:8000/api/events/month?start_date=${startDate}&end_date=${endDate}`, 
          {
            headers: { 'Authorization': `Bearer ${token}` }
          }
        );
        
        // Group events by date for easy lookup
        if (response.data && Array.isArray(response.data)) {
          response.data.forEach(event => {
            // Extract date part from start_time
            const eventDate = event.start_time.split(' ')[0];
            if (!this.monthEvents[eventDate]) {
              this.monthEvents[eventDate] = [];
            }
            this.monthEvents[eventDate].push(event);
          });
        }
        
        console.log('Fetched month events:', this.monthEvents);
      } catch (error) {
        console.error('Error fetching month events:', error);
        this.error = 'Failed to load calendar data. Please try again.';
      } finally {
        this.loading = false;
      }
    },
    async fetchEvents(date) {
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }

      this.loading = true;
      const formattedDate = this.formatDateForAPI(date);
      console.log("Fetching events for date:", formattedDate);
      
      try {
        const response = await axios.get(`http://localhost:8000/api/events?date=${formattedDate}`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        this.events = response.data;
        console.log('Fetched Events with reminders:', this.events);
      } catch (error) {
        console.error('Error fetching events:', error);
        this.error = 'Failed to load events. Please try again.';
      } finally {
        this.loading = false;
      }
    },
    async addEvent(event) {
      const token = localStorage.getItem('token');
      const user = JSON.parse(localStorage.getItem('user'));
      if (!token || !user) {
        console.error('No token or user found');
        return;
      }

      this.loading = true;
      
      try {
        // Prepare event data - extracting just the time portion if needed
        let startTime = event.start_time;
        let endTime = event.end_time;
        
        // Check if times already include date portion
        if (!startTime.includes(' ')) {
          startTime = `${this.selectedDate} ${startTime}`;
        }
        
        if (!endTime.includes(' ')) {
          endTime = `${this.selectedDate} ${endTime}`;
        }
        
        const eventData = {
          user_id: user.id,
          title: event.title,
          description: event.description || '',
          start_time: startTime,
          end_time: endTime
        };
        
        console.log("Sending new event with data:", eventData);
        
        // Create the event
        const response = await axios.post(
          'http://localhost:8000/api/events', 
          eventData,
          {
            headers: { 'Authorization': `Bearer ${token}` }
          }
        );
        
        const newEvent = response.data;
        console.log('Event Added:', newEvent);
        
        // If event has reminders, save them
        if (event.reminders && event.reminders.length > 0) {
          for (const reminder of event.reminders) {
            await axios.post(
              `http://localhost:8000/api/events/${newEvent.id}/reminders`,
              {
                type: reminder.type,
                minutes_before: reminder.minutes_before
              },
              {
                headers: { 'Authorization': `Bearer ${token}` }
              }
            );
          }
          
          // Fetch the complete event with reminders
          const updatedEventResponse = await axios.get(
            `http://localhost:8000/api/events/${newEvent.id}`,
            { headers: { 'Authorization': `Bearer ${token}` } }
          );
          
          // Update local event list with the complete event
          this.events.push(updatedEventResponse.data);
          
          // Update month events cache
          const eventDate = this.selectedDate;
          if (!this.monthEvents[eventDate]) {
            this.monthEvents[eventDate] = [];
          }
          this.monthEvents[eventDate].push(updatedEventResponse.data);
        } else {
          // No reminders to add, just update local lists
          this.events.push(newEvent);
          
          // Update month events cache
          const eventDate = this.selectedDate;
          if (!this.monthEvents[eventDate]) {
            this.monthEvents[eventDate] = [];
          }
          this.monthEvents[eventDate].push(newEvent);
        }
      } catch (error) {
        console.error('Error adding event:', error);
        if (error.response) {
          console.error('Server response:', error.response.data);
        }
        alert('Failed to create event. Please try again.');
      } finally {
        this.loading = false;
      }
    },
    async updateEvent(updatedEvent) {
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      this.loading = true;
      
      try {
        // Extract the original date from the event's start_time
        let originalDatePart = this.selectedDate; // Default
        
        // Get the date part from the original event start_time
        if (updatedEvent.start_time && updatedEvent.start_time.includes(' ')) {
          originalDatePart = updatedEvent.start_time.split(' ')[0];
        }

        // Extract time portions
        let startTime = updatedEvent.start_time;
        let endTime = updatedEvent.end_time;
        
        if (startTime.includes(' ')) {
          startTime = startTime.split(' ')[1];
        }
        
        if (endTime.includes(' ')) {
          endTime = endTime.split(' ')[1];
        }
        
        // Create event data using the original date
        const eventData = {
          id: updatedEvent.id,
          title: updatedEvent.title,
          description: updatedEvent.description || '',
          start_time: `${originalDatePart} ${startTime}`,
          end_time: `${originalDatePart} ${endTime}`,
          user_id: updatedEvent.user_id
        };

        console.log('Sending update with data:', eventData);
        
        // Update the event
        const response = await axios.put(
          `http://localhost:8000/api/events/${updatedEvent.id}`, 
          eventData,
          {
            headers: { 'Authorization': `Bearer ${token}` }
          }
        );
        
        // Handle reminders separately - first delete existing ones
        if (updatedEvent.original_reminders) {
          for (const reminder of updatedEvent.original_reminders) {
            await axios.delete(
              `http://localhost:8000/api/reminders/${reminder.id}`,
              { headers: { 'Authorization': `Bearer ${token}` } }
            );
          }
        }
        
        // Add new reminders
        if (updatedEvent.reminders && updatedEvent.reminders.length > 0) {
          for (const reminder of updatedEvent.reminders) {
            // Skip reminders that already exist and haven't changed
            if (reminder.id) continue;
            
            await axios.post(
              `http://localhost:8000/api/events/${updatedEvent.id}/reminders`,
              {
                type: reminder.type,
                minutes_before: reminder.minutes_before
              },
              { headers: { 'Authorization': `Bearer ${token}` } }
            );
          }
        }
        
        // Fetch the complete updated event with reminders
        const updatedEventResponse = await axios.get(
          `http://localhost:8000/api/events/${updatedEvent.id}`,
          { headers: { 'Authorization': `Bearer ${token}` } }
        );
        
        const updatedEventWithReminders = updatedEventResponse.data;
        console.log('Event Updated with reminders:', updatedEventWithReminders);
        
        // Update the local events array
        const index = this.events.findIndex(event => event.id === updatedEvent.id);
        if (index !== -1) {
          this.events.splice(index, 1, updatedEventWithReminders);
        } else {
          // Add to events if not found (could happen if date changed)
          this.events.push(updatedEventWithReminders);
        }
        
        // Update month events cache
        this.fetchMonthEvents(); // Refresh the entire month view
      } catch (error) {
        console.error('Error updating event:', error);
        if (error.response) {
          console.error('Server response:', error.response.data);
        }
        alert('Failed to update event. Please try again.');
      } finally {
        this.loading = false;
      }
    },
    async deleteEvent(event) {
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      this.loading = true;
      
      try {
        // First, delete associated reminders
        if (event.reminders && event.reminders.length > 0) {
          for (const reminder of event.reminders) {
            await axios.delete(
              `http://localhost:8000/api/reminders/${reminder.id}`,
              { headers: { 'Authorization': `Bearer ${token}` } }
            );
          }
        }
        
        // Then delete the event
        await axios.delete(
          `http://localhost:8000/api/events/${event.id}`,
          { headers: { 'Authorization': `Bearer ${token}` } }
        );
        
        // Remove from local array
        this.events = this.events.filter(e => e.id !== event.id);
        
        // Update month events cache
        this.fetchMonthEvents();
        
        console.log('Event deleted successfully');
      } catch (error) {
        console.error('Error deleting event:', error);
        alert('Failed to delete event. Please try again.');
        // Reload events in case of error to restore state
        if (this.selectedDate) {
          this.fetchEvents(new Date(this.selectedDate));
        }
      } finally {
        this.loading = false;
      }
    }
  }
};
</script>

<style scoped>
@import '../assets/planday.css';
</style>