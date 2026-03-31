<template>
  <a-layout style="min-height: 100vh">
    <a-layout-header style="display: flex; align-items: center; justify-content: space-between">
      <div style="color: #fff; font-weight: 700">StripeDesk</div>
      <a-space v-if="auth.token" :size="16">
        <template v-if="auth.profile?.role === 'user'">
          <router-link to="/" style="color: rgba(255,255,255,0.85)">Dashboard</router-link>
          <router-link to="/shop" style="color: rgba(255,255,255,0.85)">Shop</router-link>
          <router-link to="/invoices" style="color: rgba(255,255,255,0.85)">Invoices</router-link>
          <router-link to="/receipts" style="color: rgba(255,255,255,0.85)">Receipts</router-link>
        </template>
        <template v-else-if="auth.profile?.role === 'admin'">
          <router-link to="/admin" style="color: rgba(255,255,255,0.85)">Admin</router-link>
        </template>
        <a-button type="link" @click="logout" style="color: rgba(255,255,255,0.85); padding: 0">Log out</a-button>
      </a-space>
    </a-layout-header>
    <a-layout-content style="padding: 24px">
      <div style="max-width: 960px; margin: 0 auto">
        <router-view />
      </div>
    </a-layout-content>
  </a-layout>
</template>

<script setup>
import { provide } from 'vue';
import { useRouter } from 'vue-router';
import { auth, clearAuth } from './app/auth';

provide('auth', auth);

const router = useRouter();

function logout() {
  clearAuth();
  router.push({ name: 'login' });
}
</script>

<style>
body { margin: 0; }
</style>
