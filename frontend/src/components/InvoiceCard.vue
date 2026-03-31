<template>
  <a-card size="small">
    <a-row justify="space-between" align="top">
      <a-col>
        <div style="font-weight: 700">{{ invoice.invoice_number }}</div>
        <a-typography-text type="secondary">Order #{{ invoice.order_id }}</a-typography-text>
      </a-col>
      <a-col style="text-align: right">
        <div style="font-weight: 700">{{ invoice.total_amount }}</div>
        <StatusBadge :status="invoice.status" />
      </a-col>
    </a-row>
    <div style="margin-top: 12px">
      <a-button type="primary" @click="$emit('open', invoice.id)">View</a-button>
    </div>
  </a-card>
</template>

<script setup>
import StatusBadge from './StatusBadge.vue';

defineProps({
  invoice: { type: Object, required: true },
});
defineEmits(['open']);
</script>

<style scoped></style>

