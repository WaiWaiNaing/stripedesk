<template>
  <a-card style="max-width: 480px; margin: 0 auto">
    <a-typography-title :level="3" style="margin-bottom: 0">Reset password</a-typography-title>
    <a-typography-paragraph type="secondary" style="margin-top: 8px">
      Choose a new password. You’ll be signed in when it’s saved.
    </a-typography-paragraph>
    <a-form layout="vertical" :model="form" :rules="rules" @finish="submit">
      <a-form-item name="email" label="Email">
        <a-input v-model:value="form.email" type="email" autocomplete="email" />
      </a-form-item>
      <a-form-item name="reset_token" label="Reset token">
        <a-input v-model:value="form.reset_token" autocomplete="one-time-code" />
      </a-form-item>
      <a-form-item name="new_password" label="New password" :extra="PASSWORD_HINT">
        <a-input-password v-model:value="form.new_password" autocomplete="new-password" />
      </a-form-item>
      <a-button type="primary" html-type="submit" :loading="loading" block>Reset</a-button>
      <a-alert v-if="info" style="margin-top: 12px" type="success" :message="info" show-icon />
      <a-alert v-if="error" style="margin-top: 12px" type="error" :message="error" show-icon />
    </a-form>
  </a-card>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { apiFetch } from '../services/apiClient';
import { loginWithTokenPayload } from '../app/auth';
import { PASSWORD_HINT, passwordFormRules } from '../utils/passwordRules';

const router = useRouter();
const route = useRoute();

const form = reactive({
  email: typeof route.query.email === 'string' ? route.query.email : '',
  reset_token: typeof route.query.reset_token === 'string' ? route.query.reset_token : '',
  new_password: '',
});
const loading = ref(false);
const error = ref('');
const info = ref('');

const rules = {
  email: [
    { required: true, message: 'Email is required' },
    { type: 'email', message: 'Please enter a valid email' },
  ],
  reset_token: [{ required: true, message: 'Reset token is required' }],
  new_password: passwordFormRules(),
};

async function submit() {
  error.value = '';
  info.value = '';
  loading.value = true;
  try {
    const res = await apiFetch('/api/v1/auth/reset-password', {
      method: 'POST',
      body: {
        email: form.email,
        reset_token: form.reset_token,
        new_password: form.new_password,
      },
    });
    await loginWithTokenPayload(res.data);
    info.value = 'Password updated. Redirecting…';
    router.push({ name: 'dashboard' });
  } catch (e) {
    error.value = e.body?.error?.message || e.message || 'Reset failed';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped></style>
