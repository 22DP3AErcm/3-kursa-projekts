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
    <Sidebar :isOpen="isSidebarOpen" :selectedDate="selectedDate" :events="events" @close="closeSidebar" @add-event="addEvent" @update-event="updateEvent" />
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
    openSidebar(date) {
      if (date) {
        this.selectedDate = `${date.getFullYear()}-${date.getMonth() + 1}-${date.getDate()}`;
        this.fetchEvents(date);
        this.isSidebarOpen = true;
      }
    },
    closeSidebar() {
      this.isSidebarOpen = false;
    },
    fetchEvents(date) {
      axios.get('/api/events', {
        params: {
          date: `${date.getFullYear()}-${date.getMonth() + 1}-${date.getDate()}`
        }
      })
      .then(response => {
        this.events = response.data;
      })
      .catch(error => {
        console.error('Error fetching events:', error);
      });
    },
    addEvent(event) {
      axios.post('/api/events', event)
      .then(response => {
        this.events.push(response.data);
      })
      .catch(error => {
        console.error('Error adding event:', error);
      });
    },
    updateEvent(updatedEvent) {
      axios.put(`/api/events/${updatedEvent.id}`, updatedEvent)
      .then(response => {
        const index = this.events.findIndex(event => event.id === updatedEvent.id);
        if (index !== -1) {
          this.events.splice(index, 1, response.data);
        }
      })
      .catch(error => {
        console.error('Error updating event:', error);
      });
    }
  }
};
</script>

<style scoped>
@import '../assets/planday.css';
</style>