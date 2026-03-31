<template>
  <a-space direction="vertical" style="width: 100%" size="middle">
    <a-card title="Create admin account">
      <a-form layout="vertical" :model="form" :rules="createRules" @finish="submit">
        <a-row :gutter="16">
          <a-col :xs="24" :md="12">
            <a-form-item name="name" label="Name">
              <a-input v-model:value="form.name" autocomplete="name" />
            </a-form-item>
          </a-col>
          <a-col :xs="24" :md="12">
            <a-form-item name="email" label="Email">
              <a-input v-model:value="form.email" type="email" autocomplete="email" />
            </a-form-item>
          </a-col>
          <a-col :xs="24" :md="12">
            <a-form-item name="password" label="Password" :extra="PASSWORD_HINT">
              <a-input-password v-model:value="form.password" autocomplete="new-password" />
            </a-form-item>
          </a-col>
        </a-row>
        <a-button type="primary" html-type="submit" :loading="loading">Create admin</a-button>
        <a-alert v-if="msg" style="margin-top: 12px" type="info" :message="msg" show-icon />
      </a-form>
    </a-card>

    <a-card title="Users">
      <a-input-search
        v-model:value="searchText"
        allow-clear
        placeholder="Search name, email, or role"
        style="max-width: 320px; margin-bottom: 12px"
      />
      <a-table
        :data-source="filteredItems"
        :loading="listLoading"
        :pagination="adminTablePagination"
        row-key="id"
        size="small"
      >
        <a-table-column
          title="ID"
          data-index="id"
          key="id"
          width="90"
          :sorter="(a, b) => a.id - b.id"
          :sort-directions="['ascend', 'descend']"
        />
        <a-table-column
          title="Name"
          data-index="name"
          key="name"
          :sorter="(a, b) => String(a.name || '').localeCompare(String(b.name || ''))"
          :sort-directions="['ascend', 'descend']"
        />
        <a-table-column
          title="Email"
          data-index="email"
          key="email"
          :sorter="(a, b) => String(a.email || '').localeCompare(String(b.email || ''))"
          :sort-directions="['ascend', 'descend']"
        />
        <a-table-column
          title="Role"
          data-index="role"
          key="role"
          width="110"
          :filters="roleFilters"
          :filter-multiple="false"
          :on-filter="(value, record) => record.role === value"
          :sorter="(a, b) => String(a.role || '').localeCompare(String(b.role || ''))"
          :sort-directions="['ascend', 'descend']"
        />
        <a-table-column
          title="Created"
          data-index="created_at"
          key="created_at"
          width="190"
          :sorter="(a, b) => new Date(a.created_at || 0) - new Date(b.created_at || 0)"
          :sort-directions="['ascend', 'descend']"
        />
        <a-table-column title="Actions" key="actions" width="120" align="right">
          <template #default="{ record }">
            <a-button
              type="link"
              danger
              size="small"
              :disabled="record.id === currentUserId"
              @click="confirmDelete(record)"
            >
              Delete
            </a-button>
          </template>
        </a-table-column>
      </a-table>
    </a-card>
  </a-space>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Modal } from 'ant-design-vue';
import { apiFetch } from '../../services/apiClient';
import { auth, ensureProfile } from '../../app/auth';
import { adminTablePagination, useAdminTableSearch } from '../../composables/useAdminTable';
import { PASSWORD_HINT, passwordFormRules } from '../../utils/passwordRules';

const roleFilters = [
  { text: 'Admin', value: 'admin' },
  { text: 'User', value: 'user' },
];

const form = reactive({ name: '', email: '', password: '' });
const createRules = {
  name: [{ required: true, message: 'Name is required' }],
  email: [
    { required: true, message: 'Email is required' },
    { type: 'email', message: 'Please enter a valid email' },
  ],
  password: passwordFormRules(),
};
const msg = ref('');
const loading = ref(false);
const items = ref([]);
const listLoading = ref(false);

const { searchText, filteredItems } = useAdminTableSearch(items, (row, q) => {
  return [row.name, row.email, row.role]
    .some((f) => String(f || '')
      .toLowerCase()
      .includes(q));
});

const currentUserId = computed(() => auth.profile?.id ?? null);

async function submit() {
  msg.value = '';
  loading.value = true;
  try {
    const res = await apiFetch('/api/v1/admin/users', { method: 'POST', body: { ...form } });
    msg.value = `Created admin #${res.data.id}. They must verify the OTP sent to ${res.data.email} before signing in (use Verify OTP with intent account activation).`;
    form.name = '';
    form.email = '';
    form.password = '';
    await load();
  } catch (e) {
    msg.value = e.body?.error?.message || e.message || 'Create failed';
  } finally {
    loading.value = false;
  }
}

async function load() {
  listLoading.value = true;
  try {
    await ensureProfile();
    const res = await apiFetch('/api/v1/admin/users');
    items.value = res.data || [];
  } finally {
    listLoading.value = false;
  }
}

function confirmDelete(record) {
  Modal.confirm({
    title: 'Soft delete this user?',
    content: `${record.email} will be deactivated and cannot sign in.`,
    okText: 'Delete',
    okType: 'danger',
    cancelText: 'Cancel',
    async onOk() {
      await apiFetch(`/api/v1/admin/users/${record.id}`, { method: 'DELETE' });
      await load();
    },
  });
}

onMounted(load);
</script>

<style scoped></style>
