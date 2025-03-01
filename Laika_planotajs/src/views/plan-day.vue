<template>
  <div>
    <div class="calendar">
      <div class="calendar-header">
        <button @click="prevMonth">Previous</button>
        <span>{{ monthNames[currentMonth] }} {{ currentYear }}</span>
        <button @click="nextMonth">Next</button>
      </div>
      <div class="calendar-body">
        <div class="calendar-weekdays">
          <div v-for="day in weekDays" :key="day">{{ day }}</div>
        </div>
        <div class="calendar-days">
          <button v-for="day in daysInMonth" :key="day.date" :class="{ 'current-day': isCurrentDay(day.date) }" @click="openSidebar(day.date)">
            {{ day.date ? day.date.getDate() : '' }}
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
      events: []
    };
  },
  computed: {
    daysInMonth() {
      const days = [];
      const firstDay = new Date(this.currentYear, this.currentMonth, 1).getDay();
      const lastDate = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();

      for (let i = 0; i < firstDay; i++) {
        days.push({ date: null });
      }

      for (let date = 1; date <= lastDate; date++) {
        days.push({ date: new Date(this.currentYear, this.currentMonth, date) });
      }

      return days;
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
      const today = new Date();
      return date && date.getDate() === today.getDate() &&
             date.getMonth() === today.getMonth() &&
             date.getFullYear() === today.getFullYear();
    },
    async openSidebar(date) {
      if (date) {
        // Format with proper leading zeros
        const year = date.getFullYear();
        const month = (date.getMonth() + 1).toString().padStart(2, '0'); 
        const day = date.getDate().toString().padStart(2, '0');
        
        this.selectedDate = `${year}-${month}-${day}`;
        console.log("Opening sidebar with date:", this.selectedDate);
        await this.fetchEvents(date);
        this.isSidebarOpen = true;
      }
    },
    closeSidebar() {
      this.isSidebarOpen = false;
    },
    async fetchEvents(date) {
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }

      // Format date consistently
      const year = date.getFullYear();
      const month = (date.getMonth() + 1).toString().padStart(2, '0');
      const day = date.getDate().toString().padStart(2, '0');
      const formattedDate = `${year}-${month}-${day}`;
      console.log("Fetching events for date:", formattedDate);
      
      try {
        const response = await axios.get(`http://localhost:8000/api/events?date=${formattedDate}`, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        this.events = response.data;
        console.log('Fetched Events:', this.events);
      } catch (error) {
        console.error('Error fetching events:', error);
      }
    },
    addEvent(event) {
      const token = localStorage.getItem('token');
      const user = JSON.parse(localStorage.getItem('user'));
      if (!token || !user) {
        console.error('No token or user found');
        return;
      }

      const formattedDate = this.selectedDate;
      console.log("Using date for new event:", formattedDate);
      
      const eventData = {
        user_id: user.id,
        title: event.title,
        description: event.description || '',
        start_time: `${formattedDate} ${event.start_time}`,
        end_time: `${formattedDate} ${event.end_time}`
      };
      
      console.log("Sending new event with data:", eventData);
      
      axios.post('http://localhost:8000/api/events', eventData, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })
      .then(response => {
        console.log('Event Added:', response.data);
        this.events.push(response.data);
      })
      .catch(error => {
        console.error('Error adding event:', error);
        if (error.response) {
          console.error('Server response:', error.response.data);
        }
      });
    },
    deleteEvent(event) {
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      // Remove from local array
      this.events = this.events.filter(e => e.id !== event.id);
      
      // Delete from API/backend with proper URL and auth headers
      axios.delete(`http://localhost:8000/api/events/${event.id}`, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })
      .then(() => {
        console.log('Event deleted successfully');
      })
      .catch(error => {
        console.error('Error deleting event:', error);
        // Reload events in case of error to restore state
        this.fetchEvents(new Date(this.selectedDate));
      });
    },
    updateEvent(updatedEvent) {
      const token = localStorage.getItem('token');
      if (!token) {
        console.error('No token found');
        return;
      }
      
      // Extract the original date from the event's start_time
      // Use today's date as default fallback
      const today = new Date();
      const year = today.getFullYear();
      const month = (today.getMonth() + 1).toString().padStart(2, '0');
      const day = today.getDate().toString().padStart(2, '0');
      let originalDatePart = `${year}-${month}-${day}`; // Today's date as default
      
      // Get the date part from the original event start_time
      if (updatedEvent.start_time && updatedEvent.start_time.includes(' ')) {
        originalDatePart = updatedEvent.start_time.split(' ')[0];
      }

      // Create event data using the ORIGINAL date (not the selected date)
      const eventData = {
        id: updatedEvent.id,
        title: updatedEvent.title,
        description: updatedEvent.description || '',
        // Use the original date with the updated time
        start_time: `${originalDatePart} ${updatedEvent.start_time.includes(' ') ? 
                      updatedEvent.start_time.split(' ')[1] : updatedEvent.start_time}`,
        end_time: `${originalDatePart} ${updatedEvent.end_time.includes(' ') ? 
                    updatedEvent.end_time.split(' ')[1] : updatedEvent.end_time}`,
        user_id: updatedEvent.user_id
      };

      console.log('Sending update with data:', eventData);
      
      axios.put(`http://localhost:8000/api/events/${updatedEvent.id}`, eventData, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      })
      .then(response => {
        console.log('Event Updated:', response.data);
        // Update the local events array
        const index = this.events.findIndex(event => event.id === updatedEvent.id);
        if (index !== -1) {
          this.events.splice(index, 1, response.data);
        }
        
        // Make sure the updated event appears in the current view
        if (!this.events.some(e => e.id === response.data.id)) {
          this.events.push(response.data);
        }
      })
      .catch(error => {
        console.error('Error updating event:', error);
        if (error.response) {
          console.error('Server response:', error.response.data);
        }
      });
    }
  }
};
</script>

<style scoped>
@import '../assets/planday.css';
</style>