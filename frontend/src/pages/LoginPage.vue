<template>
  <a-card style="max-width: 420px; margin: 0 auto">
    <a-typography-title :level="3" style="margin-bottom: 0">Sign in</a-typography-title>

    <a-form layout="vertical" @submit.prevent="submit">
      <a-form-item label="Email">
        <a-input v-model:value="email" type="email" autocomplete="username" />
      </a-form-item>
      <a-form-item label="Password">
        <a-input-password v-model:value="password" autocomplete="current-password" />
      </a-form-item>

      <a-button type="primary" html-type="submit" :loading="loading" block>Sign in</a-button>

      <a-alert v-if="error" style="margin-top: 12px" type="error" :message="error" show-icon />

      <a-typography-paragraph type="secondary" style="margin-top: 12px">
        <router-link to="/register">Create account</router-link>
        ·
        <router-link to="/forgot">Forgot password?</router-link>
      </a-typography-paragraph>
    </a-form>
  </a-card>
</template>

<script setup>
import { inject, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { login } from '../app/auth';

const auth = inject('auth');
const router = useRouter();
const route = useRoute();

const email = ref('');
const password = ref('');
const loading = ref(false);
const error = ref('');

async function submit() {
  error.value = '';
  loading.value = true;
  try {
    auth.profile = await login(email.value, password.value);
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/';
    router.push(redirect);
  } catch (e) {
    if (e.status === 403 && e.body?.error?.code === 'email_not_verified') {
      const otpIntent = e.body?.error?.intent || 'registration';
      router.push({
        name: 'verify-otp',
        query: { email: email.value, intent: otpIntent },
      });
      return;
    }
    error.value = e.body?.error?.message || e.message || 'Login failed';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped></style>
