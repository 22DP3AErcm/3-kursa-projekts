<template>
  <div>
    <vue-cal
      class="fixed-size-calendar"
      :events="events"
    />
  </div>
</template>

<script>
import VueCal from 'vue-cal'
import 'vue-cal/dist/vuecal.css'
import '../assets/planday.css'

export default {
  components: {
    VueCal
  },
  data() {
    return {
      
    }
  }
}
</script>
