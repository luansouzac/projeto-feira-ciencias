import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/home',
      name: 'home',
      component: () => import('../views/HomeView.vue'),
    },
    {
      path: '/about',
      name: 'about',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('../views/AboutView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      // route level code-splitting
      // this generates a separate chunk (About.[hash].js) for this route
      // which is lazy-loaded when the route is visited.
      component: () => import('../views/LoginView.vue'),
    },
    {
      path: '/registrar',
      name: 'registrar',

      component: () => import('../views/RegisterView.vue'),
    },
    {
    path: '/projetos/:id', 
    name: 'project-details',
    component: () => import('../views/ProjectDetails.vue'),
    },
    {
      path: '/projetos',
      name: 'projetos',
      component: () => import('../views/ProjectsView.vue'),
    }
  ],
})

export default router
