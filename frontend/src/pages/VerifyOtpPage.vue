<template>
  <a-card style="max-width: 420px; margin: 0 auto">
    <a-typography-title :level="3" style="margin-bottom: 0">Verify OTP</a-typography-title>
    <a-typography-paragraph v-if="intent === 'registration'" type="secondary" style="margin-top: 8px">
      Enter the code we sent to finish creating your account. You’ll be signed in next.
    </a-typography-paragraph>
    <a-typography-paragraph v-else-if="intent === 'account_activation'" type="secondary" style="margin-top: 8px">
      Enter the code we sent to activate this administrator account. You’ll be signed in next.
    </a-typography-paragraph>
    <a-typography-paragraph v-else type="secondary" style="margin-top: 8px">
      Enter the code to continue resetting your password.
    </a-typography-paragraph>
    <a-form layout="vertical" @submit.prevent="submit">
      <a-form-item label="Email">
        <a-input v-model:value="email" type="email" />
      </a-form-item>
      <a-form-item label="OTP">
        <a-input v-model:value="otp" inputmode="numeric" autocomplete="one-time-code" />
      </a-form-item>
      <a-button type="primary" html-type="submit" :loading="loading" block>Verify</a-button>
      <a-alert v-if="error" style="margin-top: 12px" type="error" :message="error" show-icon />
    </a-form>
  </a-card>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiFetch } from '../services/apiClient';
import { loginWithTokenPayload } from '../app/auth';

const router = useRouter();
const route = useRoute();

const intent = computed(() => {
  const q = route.query.intent;
  if (q === 'registration') return 'registration';
  if (q === 'account_activation') return 'account_activation';
  return 'password_reset';
});

const email = ref(typeof route.query.email === 'string' ? route.query.email : '');
const otp = ref(typeof route.query.otp === 'string' ? route.query.otp : '');
const loading = ref(false);
const error = ref('');

async function submit() {
  error.value = '';
  loading.value = true;
  try {
    const res = await apiFetch('/api/v1/auth/verify-otp', {
      method: 'POST',
      body: { email: email.value, otp: otp.value, intent: intent.value },
    });
    if (intent.value === 'registration' || intent.value === 'account_activation') {
      await loginWithTokenPayload(res.data);
      router.push(intent.value === 'account_activation' ? { name: 'admin' } : { name: 'dashboard' });
    } else {
      router.push({
        name: 'reset-password',
        query: { email: email.value, reset_token: res.data.reset_token },
      });
    }
  } catch (e) {
    error.value = e.body?.error?.message || e.message || 'OTP verification failed';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped></style>
