import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import AboutView from '../views/AboutView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import planday from '../views/plan-day.vue'
import PlanProject from '../views/plan-project.vue'
import ProjectDetail from '../views/ProjectDetail.vue'
import MailView from '../views/MailView.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView,
  },
  {
    path: '/about',
    name: 'about',
    component: AboutView,
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
  },
  {
    path: '/plan-day',
    name: 'plan-day',
    component: planday,
  },
  {
    path: '/plan-project',
    name: 'plan-project',
    component: PlanProject,
  },
  {
    path: '/projects/:id',
    name: 'project-detail',
    component: ProjectDetail,
  },
  {
    path: '/mail',
    name: 'mail',
    component: MailView,
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

export default router