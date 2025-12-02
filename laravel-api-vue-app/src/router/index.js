import { createRouter, createWebHistory } from "vue-router";

import HomeView from "../views/HomeView.vue";
import RegisterView from "../views/Auth/RegisterView.vue";
import VerificationEmailSent from "../views/Auth/VerificationEmailSent.vue";
import VerifyEmail from "../views/Auth/VerifyEmail.vue";
import LoginView from "../views/Auth/LoginView.vue";
import CreateView from "@/views/Blogs/CreateView.vue";
import ShowView from "@/views/Blogs/ShowView.vue";
import UpdateView from "@/views/Blogs/UpdateView.vue";
import ProductCreateView from "@/views/Products/ProductCreateView.vue";
import ProductShowView from "@/views/Products/ProductShowView.vue";
import MyOrdersView from "@/views/Orders/MyOrdersView.vue";
import ReturnRequestsView from "@/views/Returns/ReturnRequestsView.vue";
import FulfillmentView from "@/views/Fulfillment/FulfillmentView.vue";
import EmployeeStatsView from "@/views/Stats/EmployeeStatsView.vue";
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
    {
      path: "/products/create",
      name: "product-create",
      component: ProductCreateView,
      meta: { auth: true, requiresEmailVerified: true, role: "seller" },
    },
    {
      path: "/products/:id",
      name: "product-show",
      component: ProductShowView,
    },
    {
      path: "/orders",
      name: "orders",
      component: MyOrdersView,
      meta: { auth: true, role: "customer" },
    },
    {
      path: "/returns",
      name: "returns",
      component: ReturnRequestsView,
      meta: { auth: true, role: "employee" },
    },
    {
      path: "/fulfillment",
      name: "fulfillment",
      component: FulfillmentView,
      meta: { auth: true, role: "employee" },
    },
    {
      path: "/stats",
      name: "stats",
      component: EmployeeStatsView,
      meta: { auth: true, role: "employee" },
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

  if (to.meta.role && authStore.user && authStore.user.role !== to.meta.role) {
    return { name: "home" };
  }
});


export default router;
