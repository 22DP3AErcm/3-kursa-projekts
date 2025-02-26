<template>
    <div :class="['sidebar', { open: isOpen }]">
      <h2>Events for {{ selectedDate }}</h2>
      <div class="timeline">
        <div v-for="hour in 24" :key="hour" class="timeline-hour">
          <span>{{ hour }}:00</span>
          <div class="timeline-events">
            <div v-for="event in events" :key="event.id" v-if="event && new Date(event.time).getHours() === hour">
              <input v-model="event.description" @change="updateEvent(event)" />
            </div>
          </div>
        </div>
        <div class="current-time-line" :style="{ top: currentTimePosition + '%' }"></div>
      </div>
      <div class="add-event">
        <input type="time" v-model="newEventTime" />
        <input type="text" v-model="newEventDescription" placeholder="Event description" />
        <button @click="addEvent">Add Event</button>
      </div>
      <button @click="closeSidebar">Close</button>
    </div>
  </template>
  
  <script>
  export default {
    props: {
      isOpen: {
        type: Boolean,
        required: true
      },
      selectedDate: {
        type: String,
        required: true
      },
      events: {
        type: Array,
        required: true
      }
    },
    data() {
      return {
        newEventTime: '',
        newEventDescription: '',
        currentTimePosition: this.calculateCurrentTimePosition()
      };
    },
    methods: {
      closeSidebar() {
        this.$emit('close');
      },
      addEvent() {
        if (this.newEventTime && this.newEventDescription) {
          const newEvent = {
            id: Date.now(),
            time: `${this.selectedDate}T${this.newEventTime}`,
            description: this.newEventDescription
          };
          this.$emit('add-event', newEvent);
          this.newEventTime = '';
          this.newEventDescription = '';
        }
      },
      updateEvent(event) {
        this.$emit('update-event', event);
      },
      calculateCurrentTimePosition() {
        const now = new Date();
        const hours = now.getHours();
        const minutes = now.getMinutes();
        return (hours * 60 + minutes) / (24 * 60) * 100;
      }
    },
    mounted() {
      setInterval(() => {
        this.currentTimePosition = this.calculateCurrentTimePosition();
      }, 60000);
    }
  };
  </script>
  
  <style scoped>
  .sidebar {
    position: fixed;
    right: 0;
    top: 0;
    width: 300px;
    height: 100%;
    background-color: #ffffff;
    box-shadow: -2px 0 5px rgba(0, 0, 0, 0.1);
    padding: 20px;
    overflow-y: auto;
    transition: transform 0.3s ease-in-out;
    transform: translateX(100%);
  }
  .sidebar.open {
    transform: translateX(0);
  }
  .timeline {
    position: relative;
  }
  .timeline-hour {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
  }
  .timeline-hour span {
    width: 50px;
  }
  .timeline-events {
    flex: 1;
    display: flex;
    flex-direction: column;
  }
  .current-time-line {
    position: absolute;
    left: 0;
    right: 0;
    height: 2px;
    background-color: red;
  }
  .add-event {
    margin-top: 20px;
  }
  </style>