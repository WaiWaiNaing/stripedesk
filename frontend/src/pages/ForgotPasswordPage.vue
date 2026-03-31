<template>
  <a-card style="max-width: 420px; margin: 0 auto">
    <a-typography-title :level="3" style="margin-bottom: 0">Forgot password</a-typography-title>
    <a-typography-paragraph type="secondary" style="margin-top: 8px">
      Enter your email and we'll send a one-time code (OTP).
    </a-typography-paragraph>
    <a-form layout="vertical" @submit.prevent="submit">
      <a-form-item label="Email">
        <a-input v-model:value="email" type="email" />
      </a-form-item>
      <a-button type="primary" html-type="submit" :loading="loading" block>Send OTP</a-button>
      <a-alert v-if="info" style="margin-top: 12px" type="success" :message="info" show-icon />
      <a-alert v-if="error" style="margin-top: 12px" type="error" :message="error" show-icon />
    </a-form>
  </a-card>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiFetch } from '../services/apiClient';

const router = useRouter();
const email = ref('');
const loading = ref(false);
const error = ref('');
const info = ref('');

async function submit() {
  error.value = '';
  info.value = '';
  loading.value = true;
  try {
    const res = await apiFetch('/api/v1/auth/forgot', { method: 'POST', body: { email: email.value } });
    info.value = 'OTP sent (check your email / dev logs).';
    const otp = res?.data?.otp;
    router.push({
      name: 'verify-otp',
      query: { email: email.value, otp: otp || '', intent: 'password_reset' },
    });
  } catch (e) {
    error.value = e.body?.error?.message || e.message || 'Failed to send OTP';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped></style>
