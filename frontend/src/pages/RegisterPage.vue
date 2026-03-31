<template>
  <a-card style="max-width: 420px; margin: 0 auto">
    <a-typography-title :level="3" style="margin-bottom: 0">Create account</a-typography-title>
    <a-typography-paragraph type="secondary" style="margin-top: 8px">
      After sign-up, enter the OTP sent to your email, then you’ll be signed in.
    </a-typography-paragraph>
    <a-form layout="vertical" :model="form" :rules="rules" @finish="submit">
      <a-form-item name="name" label="Name">
        <a-input v-model:value="form.name" autocomplete="name" />
      </a-form-item>
      <a-form-item name="email" label="Email">
        <a-input v-model:value="form.email" type="email" autocomplete="email" />
      </a-form-item>
      <a-form-item name="password" label="Password" :extra="PASSWORD_HINT">
        <a-input-password v-model:value="form.password" autocomplete="new-password" />
      </a-form-item>
      <a-button type="primary" html-type="submit" :loading="loading" block>Create</a-button>
      <a-alert v-if="error" style="margin-top: 12px" type="error" :message="error" show-icon />
      <a-typography-paragraph type="secondary" style="margin-top: 12px">
        Already have an account? <router-link to="/login">Sign in</router-link>
      </a-typography-paragraph>
    </a-form>
  </a-card>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { apiFetch } from '../services/apiClient';
import { PASSWORD_HINT, passwordFormRules } from '../utils/passwordRules';

const router = useRouter();
const form = reactive({ name: '', email: '', password: '' });
const loading = ref(false);
const error = ref('');

const rules = {
  name: [{ required: true, message: 'Name is required' }],
  email: [
    { required: true, message: 'Email is required' },
    { type: 'email', message: 'Please enter a valid email' },
  ],
  password: passwordFormRules(),
};

async function submit() {
  error.value = '';
  loading.value = true;
  try {
    await apiFetch('/api/v1/auth/register', {
      method: 'POST',
      body: { ...form },
    });
    router.push({
      name: 'verify-otp',
      query: { email: form.email, intent: 'registration' },
    });
  } catch (e) {
    error.value = e.body?.error?.message || e.message || 'Registration failed';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped></style>
