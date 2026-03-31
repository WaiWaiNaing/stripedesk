<template>
  <div>
    <a-typography-title :level="3">Receipts</a-typography-title>
    <a-typography-paragraph type="secondary">
      Open a receipt by ID (from invoice detail or admin view).
    </a-typography-paragraph>
    <a-card>
      <a-form layout="inline" @submit.prevent="load">
        <a-form-item label="Receipt ID">
          <a-input-number v-model:value="rid" :min="1" />
        </a-form-item>
        <a-form-item>
          <a-button type="primary" @click="load">Load</a-button>
        </a-form-item>
      </a-form>
      <a-alert v-if="error" style="margin-top: 12px" type="error" :message="error" show-icon />
      <a-textarea v-if="receipt" style="margin-top: 12px" :value="receipt" :rows="18" readonly />
    </a-card>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { apiFetch } from '../services/apiClient';

const rid = ref(1);
const receipt = ref('');
const error = ref('');

async function load() {
  error.value = '';
  receipt.value = '';
  try {
    const res = await apiFetch(`/api/v1/receipts/${rid.value}`);
    receipt.value = JSON.stringify(res.data, null, 2);
  } catch (e) {
    error.value = e.body?.error?.message || e.message || 'Failed to load receipt';
  }
}
</script>

<style scoped>
/* AntD-driven layout */
</style>

