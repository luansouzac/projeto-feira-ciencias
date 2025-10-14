import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/home',
      name: 'home',
      component: () => import('../views/HomeView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/',
      redirect: '/login',
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
    },
    {
      path: '/registrar',
      name: 'registrar',
      component: () => import('../views/RegisterView.vue'),
    },
    {
      path: '/recuperar-senha',
      name: 'recuperar',
      component: () => import('../views/RecuperarSenhaView.vue'),
    },
    {
      path: '/projetos',
      name: 'projetos',
      component: () => import('../views/ProjectsView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/projetos/inscritos',
      name: 'projetos-inscritos',
      component: () => import('../views/ApprovedProjectsView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/projetos/orientados/:id',
      name: 'project-avaliacao',
      component: () => import('../views/OrientadorProjectView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/projetos/:id',
      name: 'project-details',
      component: () => import('../views/ProjectDetails.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/banco-projetos',
      name: 'banco-projects',
      component: () => import('../views/BancoProjetosView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/gerenciar-projeto/:id',
      name: 'gerenciar-projeto',
      component: () => import('../views/GerenciarProjetoView.vue'),
      meta: { requiresAuth: true },
    },
    {
      path: '/eventos',
      name: 'eventos',
      component: () => import('../views/EventsView.vue'),
      meta: {
        requiresAuth: true,
        requiredTypeId: [1, 3, 4],
      },
    },
    {
      path: '/avaliacoes',
      name: 'avaliacoes',
      component: () => import('../views/AvaliacoesView.vue'),
      meta: {
        requiresAuth: true,
        requiredTypeId: [1, 3, 4],
      },
    },
    {
      path: '/projetos/orientados',
      name: 'orientados',
      component: () => import('../views/ProjetosOrientados.vue'),
      meta: {
        requiresAuth: true,
        requiredTypeId: [1, 3, 4],
      },
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: { requiresAuth: true },
    },
    {
    path: '/public/projeto/:id',
    name: 'PublicProject',
    component: () => import('../views/PublicProjectView.vue'),
  },
  {
    // ✅ ROTA ADICIONADA PARA A PÁGINA DE LISTAGEM
    path: '/public/evento/:id/projetos',
    name: 'PublicEventProjects',
    component: () => import('../views/PublicEventView.vue'),
  },
  ],
})

router.beforeEach((to, from, next) => {
  const userDataString = sessionStorage.getItem('user_data')
  const isAuthenticated = !!userDataString
  let userTypeId = null

  if (isAuthenticated) {
    const userData = JSON.parse(userDataString)

    if (userData.user && userData.user.id_tipo_usuario) { 
      userTypeId = userData.user.id_tipo_usuario
    }
  }

  if (isAuthenticated && (to.name === 'login' || to.name === 'registrar')) {
    return next({ name: 'home' })
  }

  if (to.meta.requiresAuth && !isAuthenticated) {
    return next({ name: 'login' })
  }

  if (to.meta.requiredTypeId) {
    if (!userTypeId || !to.meta.requiredTypeId.includes(userTypeId)) {
      return next({ name: 'home' })
    }
  }

  next()
})

export default router
