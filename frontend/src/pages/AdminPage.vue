<template>
  <div>
    <a-typography-title :level="3">Admin</a-typography-title>
    <a-spin :spinning="loading">
      <a-alert v-if="error" style="margin-bottom: 12px" type="error" :message="error" show-icon />

      <a-card v-if="!error && profile?.role !== 'admin'">
        <a-result status="403" title="Admin access required" />
      </a-card>

      <a-card v-if="!error && profile?.role === 'admin'">
        <a-tabs v-model:activeKey="activeKey">
          <a-tab-pane key="users" tab="Users">
            <UsersPanel />
          </a-tab-pane>
          <a-tab-pane key="products" tab="Products">
            <ProductsPanel />
          </a-tab-pane>
          <a-tab-pane key="currencies" tab="Currencies">
            <CurrenciesPanel />
          </a-tab-pane>
          <a-tab-pane key="stripeLogs" tab="Stripe logs">
            <StripeLogsPanel />
          </a-tab-pane>
        </a-tabs>
      </a-card>
    </a-spin>
  </div>
</template>

<script setup>
import { inject, onMounted, ref } from 'vue';
import { apiFetch } from '../services/apiClient';
import UsersPanel from './admin/UsersPanel.vue';
import ProductsPanel from './admin/ProductsPanel.vue';
import StripeLogsPanel from './admin/StripeLogsPanel.vue';
import CurrenciesPanel from './admin/CurrenciesPanel.vue';

const auth = inject('auth');
const loading = ref(true);
const error = ref('');
const profile = ref(null);
const activeKey = ref('users');

onMounted(async () => {
  try {
    const me = await apiFetch('/api/v1/auth/me');
    profile.value = me.data;
    auth.profile = me.data;
  } catch (e) {
    error.value = e.body?.error?.message || e.message || 'Failed to load profile';
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped></style>

