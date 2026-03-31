<template>
  <a-space direction="vertical" style="width: 100%" size="middle">
    <a-card title="Create currency">
      <a-form layout="vertical" @submit.prevent="submit">
        <a-row :gutter="16">
          <a-col :xs="24" :md="12">
            <a-form-item label="Code (ISO 4217)">
              <a-input v-model:value="form.code" maxlength="3" placeholder="USD" />
            </a-form-item>
          </a-col>
          <a-col :xs="24" :md="12">
            <a-form-item label="Numeric code">
              <a-input v-model:value="form.numeric_code" placeholder="840" />
            </a-form-item>
          </a-col>
          <a-col :xs="24" :md="12">
            <a-form-item label="Name">
              <a-input v-model:value="form.name" placeholder="US Dollar" />
            </a-form-item>
          </a-col>
          <a-col :xs="24" :md="12">
            <a-form-item label="Symbol">
              <a-input v-model:value="form.symbol" placeholder="$" />
            </a-form-item>
          </a-col>
          <a-col :xs="24" :md="12">
            <a-form-item label="Minor unit">
              <a-input-number v-model:value="form.minor_unit" :min="0" :max="6" style="width: 100%" />
            </a-form-item>
          </a-col>
          <a-col :xs="24" :md="12">
            <a-form-item>
              <a-checkbox v-model:checked="form.is_default">Set as default</a-checkbox>
            </a-form-item>
          </a-col>
        </a-row>
        <a-button type="primary" :loading="loading" @click="submit">Create currency</a-button>
        <a-alert v-if="msg" style="margin-top: 12px" type="info" :message="msg" show-icon />
      </a-form>
    </a-card>

    <a-card title="Currencies">
      <a-input-search
        v-model:value="searchText"
        allow-clear
        placeholder="Search code, name, symbol"
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
          title="Code"
          data-index="code"
          key="code"
          width="100"
          :sorter="(a, b) => String(a.code || '').localeCompare(String(b.code || ''))"
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
          title="Symbol"
          data-index="symbol"
          key="symbol"
          width="100"
          :sorter="(a, b) => String(a.symbol || '').localeCompare(String(b.symbol || ''))"
          :sort-directions="['ascend', 'descend']"
        />
        <a-table-column
          title="Minor"
          data-index="minor_unit"
          key="minor_unit"
          width="90"
          :sorter="(a, b) => (a.minor_unit ?? 0) - (b.minor_unit ?? 0)"
          :sort-directions="['ascend', 'descend']"
        />
        <a-table-column
          title="Default"
          key="is_default"
          width="120"
          :filters="defaultFilters"
          :filter-multiple="false"
          :on-filter="(value, record) => record.is_default === value"
          :sorter="(a, b) => Number(a.is_default) - Number(b.is_default)"
          :sort-directions="['ascend', 'descend']"
        >
          <template #default="{ record }">
            <a-tag v-if="record.is_default" color="green">Default</a-tag>
          </template>
        </a-table-column>
        <a-table-column title="Actions" key="actions" width="120">
          <template #default="{ record }">
            <a-button size="small" @click="openEdit(record)">Edit</a-button>
          </template>
        </a-table-column>
      </a-table>
    </a-card>

    <a-modal v-model:open="editOpen" title="Edit currency" @ok="saveEdit" :confirm-loading="editSaving">
      <a-form layout="vertical">
        <a-form-item label="Code">
          <a-input :value="editForm.code" disabled />
        </a-form-item>
        <a-form-item label="Name">
          <a-input v-model:value="editForm.name" />
        </a-form-item>
        <a-form-item label="Numeric code">
          <a-input v-model:value="editForm.numeric_code" />
        </a-form-item>
        <a-form-item label="Symbol">
          <a-input v-model:value="editForm.symbol" />
        </a-form-item>
        <a-form-item label="Minor unit">
          <a-input-number v-model:value="editForm.minor_unit" :min="0" :max="6" style="width: 100%" />
        </a-form-item>
        <a-form-item>
          <a-checkbox v-model:checked="editForm.is_default">Set as default</a-checkbox>
        </a-form-item>
      </a-form>
      <a-alert v-if="editMsg" style="margin-top: 12px" type="error" :message="editMsg" show-icon />
    </a-modal>
  </a-space>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { apiFetch } from '../../services/apiClient';
import { adminTablePagination, useAdminTableSearch } from '../../composables/useAdminTable';

const defaultFilters = [
  { text: 'Default', value: true },
  { text: 'Not default', value: false },
];

const form = ref({
  code: '',
  numeric_code: '',
  name: '',
  minor_unit: 2,
  symbol: '',
  is_default: false,
});
const msg = ref('');
const loading = ref(false);
const items = ref([]);
const listLoading = ref(false);
const editOpen = ref(false);
const editSaving = ref(false);
const editMsg = ref('');
const editId = ref(null);
const editForm = ref({
  code: '',
  numeric_code: '',
  name: '',
  minor_unit: 2,
  symbol: '',
  is_default: false,
});

const { searchText, filteredItems } = useAdminTableSearch(items, (row, q) => {
  return [row.code, row.name, row.symbol, row.numeric_code]
    .map((f) => String(f ?? ''))
    .join(' ')
    .toLowerCase()
    .includes(q);
});

async function load() {
  listLoading.value = true;
  try {
    const res = await apiFetch('/api/v1/admin/currencies');
    items.value = res.data || [];
  } finally {
    listLoading.value = false;
  }
}

async function submit() {
  msg.value = '';
  loading.value = true;
  try {
    const payload = {
      code: form.value.code,
      numeric_code: form.value.numeric_code || null,
      name: form.value.name,
      minor_unit: form.value.minor_unit,
      symbol: form.value.symbol || null,
      is_default: !!form.value.is_default,
    };
    const res = await apiFetch('/api/v1/admin/currencies', { method: 'POST', body: payload });
    msg.value = `Created currency #${res.data.id}`;
    form.value = { code: '', numeric_code: '', name: '', minor_unit: 2, symbol: '', is_default: false };
    await load();
  } catch (e) {
    msg.value = e.body?.error?.message || e.message || 'Create currency failed';
  } finally {
    loading.value = false;
  }
}

function openEdit(record) {
  editMsg.value = '';
  editId.value = record.id;
  editForm.value = {
    code: record.code,
    numeric_code: record.numeric_code || '',
    name: record.name || '',
    minor_unit: record.minor_unit ?? 2,
    symbol: record.symbol || '',
    is_default: !!record.is_default,
  };
  editOpen.value = true;
}

async function saveEdit() {
  editMsg.value = '';
  editSaving.value = true;
  try {
    await apiFetch(`/api/v1/admin/currencies/${editId.value}`, {
      method: 'PUT',
      body: {
        name: editForm.value.name,
        numeric_code: editForm.value.numeric_code || null,
        minor_unit: editForm.value.minor_unit,
        symbol: editForm.value.symbol || null,
        is_default: !!editForm.value.is_default,
      },
    });
    editOpen.value = false;
    await load();
  } catch (e) {
    editMsg.value = e.body?.error?.message || e.message || 'Update failed';
  } finally {
    editSaving.value = false;
  }
}

onMounted(load);
</script>

<style scoped></style>
