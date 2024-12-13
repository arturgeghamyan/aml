import { createRouter, createWebHistory } from "vue-router";

import HomeView from "../views/HomeView.vue";
import RegisterView from "../views/Auth/RegisterView.vue";
import VerificationEmailSent from "../views/Auth/VerificationEmailSent.vue";
import VerifyEmail from "../views/Auth/VerifyEmail.vue";
import LoginView from "../views/Auth/LoginView.vue";
import CreateView from "@/views/Blogs/CreateView.vue";
import ShowView from "@/views/Blogs/ShowView.vue";
import UpdateView from "@/views/Blogs/UpdateView.vue";
import { useAuthStore } from "@/stores/auth";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/",
      name: "home",
      component: HomeView,
    },
    {
      path: "/register",
      name: "register",
      component: RegisterView,
      meta: { guest: true },
    },
    {
      path: "/verify-email-notification",
      name: "verify-email-notification",
      component: VerificationEmailSent,
      meta: { auth: true, emailVerified: true },
    },
    {
      path: "/verify-email",
      name: "verify-email",
      component: VerifyEmail,
      meta: { auth: true, emailVerified: true },
    },
    {
      path: "/login",
      name: "login",
      component: LoginView,
      meta: { guest: true },
    },
    {
      path: "/create",
      name: "create",
      component: CreateView,
      meta: { auth: true, requiresEmailVerified: true },
      },
    {
      path: "/blogs/:id",
      name: "show",
      component: ShowView,
    },
    {
      path: "/blogs/update/:id",
      name: "update",
      component: UpdateView,
      meta: { auth: true, requiresEmailVerified: true },
    },
  ],
});

router.beforeEach(async (to, from) => {
  const authStore = useAuthStore();
  
  if (!authStore.user) {
    await authStore.getUser();
  }

  // if (to.meta.requiresEmailVerified && authStore.user && !authStore.user.email_verified_at) {
  //   return { name: 'verify-email-notification' };
  // }

  // if (to.meta.emailVerified && authStore.user && authStore.user.email_verified_at) {
  //   return { name: 'home' };
  // }
  
  if (authStore.user && to.meta.guest) {
    return { name: "home" };
  }

  if (!authStore.user && to.meta.auth) {
    return { name: "login" };
  }
});


export default router;
