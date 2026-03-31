<template>
  <div>
    <a-typography-title :level="3">Dashboard</a-typography-title>
    <a-spin :spinning="loading">
      <a-alert v-if="loadError" style="margin-bottom: 12px" type="error" :message="loadError" show-icon />

      <a-card v-if="!loadError" title="Profile" style="margin-bottom: 16px">
        <a-space v-if="profile">
          <span><strong>{{ profile.name }}</strong> — {{ profile.email }}</span>
          <a-tag>{{ profile.role }}</a-tag>
        </a-space>
      </a-card>

      <a-card v-if="!loadError" title="Products" style="margin-bottom: 16px">
        <a-table :data-source="products" :pagination="false" row-key="id" size="small">
          <a-table-column title="Name" data-index="name" key="name" />
          <a-table-column title="Price" data-index="price" key="price" />
          <a-table-column title="Currency" data-index="currency_code" key="currency_code" />
        </a-table>
        <a-empty v-if="!products.length" description="No products." />
      </a-card>

      <a-card v-if="!loadError" title="Invoices">
        <a-table :data-source="invoices" :pagination="false" row-key="id" size="small">
          <a-table-column title="#" data-index="invoice_number" key="invoice_number" />
          <a-table-column title="Order" data-index="order_id" key="order_id" />
          <a-table-column title="Amount" data-index="total_amount" key="total_amount" />
          <a-table-column title="Status" data-index="status" key="status" />
        </a-table>
        <a-empty v-if="!invoices.length" description="No invoices." />
      </a-card>
    </a-spin>
  </div>
</template>

<script setup>
import { inject, onMounted, ref } from 'vue';
import { apiFetch } from '../services/apiClient';

const auth = inject('auth');

const loading = ref(true);
const loadError = ref('');
const profile = ref(null);
const products = ref([]);
const invoices = ref([]);

onMounted(async () => {
  try {
    const [me, pr, inv] = await Promise.all([
      apiFetch('/api/v1/auth/me'),
      apiFetch('/api/v1/products'),
      apiFetch('/api/v1/invoices'),
    ]);
    if (me.success) {
      profile.value = me.data;
      auth.profile = me.data;
    }
    if (pr.success) products.value = pr.data || [];
    if (inv.success) invoices.value = inv.data || [];
  } catch (e) {
    loadError.value = e.body?.error?.message || e.message || 'Failed to load';
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
/* AntD-driven layout */
</style>

