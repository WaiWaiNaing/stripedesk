<template>
  <a-card style="max-width: 560px; margin: 0 auto">
    <a-result status="success" title="Payment successful">
      <template #subTitle>
        Stripe redirected you back. Your invoice and receipt will appear once the webhook is processed.
        <div v-if="sessionId" style="margin-top: 8px">Session: <code>{{ sessionId }}</code></div>
      </template>
      <template #extra>
        <router-link to="/invoices"><a-button type="primary">Go to invoices</a-button></router-link>
      </template>
    </a-result>
  </a-card>
</template>

<script setup>
import { useRoute } from 'vue-router';

const route = useRoute();
const sessionId = typeof route.query.session_id === 'string' ? route.query.session_id : '';
</script>

<style scoped></style>

