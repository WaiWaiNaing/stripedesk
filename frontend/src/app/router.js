import { createRouter, createWebHistory } from 'vue-router';
import { auth, ensureProfile } from './auth';

import LoginPage from '../pages/LoginPage.vue';
import DashboardPage from '../pages/DashboardPage.vue';
import ShopPage from '../pages/ShopPage.vue';
import InvoicesPage from '../pages/InvoicesPage.vue';
import ReceiptsPage from '../pages/ReceiptsPage.vue';
import PurchaseSuccessPage from '../pages/PurchaseSuccessPage.vue';
import PurchaseCancelPage from '../pages/PurchaseCancelPage.vue';
import AdminPage from '../pages/AdminPage.vue';
import RegisterPage from '../pages/RegisterPage.vue';
import ForgotPasswordPage from '../pages/ForgotPasswordPage.vue';
import VerifyOtpPage from '../pages/VerifyOtpPage.vue';
import ResetPasswordPage from '../pages/ResetPasswordPage.vue';

/** Logged-in users are redirected away from these (OTP / reset stay reachable while signed in). */
const USER_ONLY_PUBLIC_ROUTES = new Set(['register', 'forgot']);

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/login', name: 'login', component: LoginPage, meta: { public: true } },
    { path: '/register', name: 'register', component: RegisterPage, meta: { public: true } },
    { path: '/forgot', name: 'forgot', component: ForgotPasswordPage, meta: { public: true } },
    { path: '/verify-otp', name: 'verify-otp', component: VerifyOtpPage, meta: { public: true } },
    { path: '/reset-password', name: 'reset-password', component: ResetPasswordPage, meta: { public: true } },
    { path: '/', name: 'dashboard', component: DashboardPage },
    { path: '/shop', name: 'shop', component: ShopPage },
    { path: '/invoices', name: 'invoices', component: InvoicesPage },
    { path: '/receipts', name: 'receipts', component: ReceiptsPage },
    { path: '/purchase/success', name: 'purchase-success', component: PurchaseSuccessPage, meta: { public: true } },
    { path: '/purchase/cancel', name: 'purchase-cancel', component: PurchaseCancelPage, meta: { public: true } },
    { path: '/admin', name: 'admin', component: AdminPage },
  ],
});

router.beforeEach(async (to) => {
  if (to.meta.public) {
    if (auth.token) {
      try {
        await ensureProfile();
      } catch {
        return true;
      }
      const role = auth.profile?.role;
      if (USER_ONLY_PUBLIC_ROUTES.has(to.name)) {
        if (role === 'admin') {
          return { name: 'admin' };
        }
        if (role === 'user') {
          return { name: 'dashboard' };
        }
      }
      if (to.name === 'login') {
        return role === 'admin' ? { name: 'admin' } : { name: 'dashboard' };
      }
    }
    return true;
  }
  if (!auth.token) return { name: 'login', query: { redirect: to.fullPath } };

  try {
    await ensureProfile();
  } catch {
    return { name: 'login', query: { redirect: to.fullPath } };
  }

  const role = auth.profile?.role;
  const isAdminRoute = to.path.startsWith('/admin');
  const isUserRoute =
    to.path === '/' ||
    to.path.startsWith('/shop') ||
    to.path.startsWith('/invoices') ||
    to.path.startsWith('/receipts');

  if (role === 'admin') {
    if (!isAdminRoute) return { name: 'admin' };
    return true;
  }
  if (role === 'user') {
    if (isAdminRoute) return { name: 'dashboard' };
    if (isUserRoute) return true;
    return { name: 'dashboard' };
  }

  return true;
});

export default router;
