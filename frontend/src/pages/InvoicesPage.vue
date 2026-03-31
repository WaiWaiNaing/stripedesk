<template>
  <div>
    <a-typography-title :level="3">Invoices</a-typography-title>
    <a-spin :spinning="loading">
      <a-alert v-if="error" style="margin-bottom: 12px" type="error" :message="error" show-icon />

      <a-row v-if="!error" :gutter="[16, 16]">
        <a-col v-for="inv in invoices" :key="inv.id" :xs="24" :md="12">
          <InvoiceCard :invoice="inv" @open="open" />
        </a-col>
      </a-row>

      <a-empty v-if="!error && !loading && !invoices.length" description="No invoices." />

      <a-drawer v-model:open="drawerOpen" title="Invoice detail" width="720">
        <a-typography-paragraph type="secondary" style="margin-top: 0">
          Raw payload (for now)
        </a-typography-paragraph>
        <a-textarea :value="selected" :rows="18" readonly />
      </a-drawer>
    </a-spin>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { apiFetch } from '../services/apiClient';
import InvoiceCard from '../components/InvoiceCard.vue';

const loading = ref(true);
const error = ref('');
const invoices = ref([]);
const selected = ref(null);
const drawerOpen = ref(false);

onMounted(async () => {
  try {
    const res = await apiFetch('/api/v1/invoices');
    invoices.value = res.data || [];
  } catch (e) {
    error.value = e.body?.error?.message || e.message || 'Failed to load invoices';
  } finally {
    loading.value = false;
  }
});

async function open(id) {
  try {
    const res = await apiFetch(`/api/v1/invoices/${id}`);
    selected.value = JSON.stringify(res.data, null, 2);
    drawerOpen.value = true;
  } catch (e) {
    error.value = e.body?.error?.message || e.message || 'Failed to load invoice detail';
  }
}
</script>

<style scoped>
/* AntD-driven layout */
</style>

