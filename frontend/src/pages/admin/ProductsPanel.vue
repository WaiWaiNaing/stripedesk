<template>
  <a-space direction="vertical" style="width: 100%" size="middle">
    <a-card title="Create product">
      <a-form layout="vertical" @submit.prevent="submit">
        <a-row :gutter="16">
          <a-col :xs="24" :md="12">
            <a-form-item label="Name">
              <a-input v-model:value="form.name" />
            </a-form-item>
          </a-col>
          <a-col :xs="24" :md="12">
            <a-form-item label="Description">
              <a-input v-model:value="form.description" />
            </a-form-item>
          </a-col>
          <a-col :xs="24" :md="12">
            <a-form-item label="Price">
              <a-input-number v-model:value="form.price" :min="0" :step="0.01" style="width: 100%" />
            </a-form-item>
          </a-col>
          <a-col :xs="24" :md="12">
            <a-form-item label="Currency">
              <a-select v-model:value="form.currency_code" placeholder="Select currency">
                <a-select-option v-for="c in currencies" :key="c.code" :value="c.code">
                  {{ c.code }} — {{ c.name }}
                </a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
        </a-row>
        <a-button type="primary" :loading="loading" @click="submit">Create product</a-button>
        <a-alert v-if="msg" style="margin-top: 12px" type="info" :message="msg" show-icon />
      </a-form>
    </a-card>

    <a-card title="Products">
      <a-input-search
        v-model:value="searchText"
        allow-clear
        placeholder="Search name, description, currency, Stripe price id"
        style="max-width: 360px; margin-bottom: 12px"
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
          title="Price"
          data-index="price"
          key="price"
          width="110"
          :sorter="(a, b) => Number(a.price) - Number(b.price)"
          :sort-directions="['ascend', 'descend']"
        />
        <a-table-column
          title="Currency"
          data-index="currency_code"
          key="currency_code"
          width="120"
          :filters="currencyFilters"
          :filter-multiple="true"
          :on-filter="(value, record) => record.currency_code === value"
          :sorter="(a, b) => String(a.currency_code || '').localeCompare(String(b.currency_code || ''))"
          :sort-directions="['ascend', 'descend']"
        />
        <a-table-column
          title="Stripe price"
          data-index="stripe_price_id"
          key="stripe_price_id"
          ellipsis
          :sorter="(a, b) => String(a.stripe_price_id || '').localeCompare(String(b.stripe_price_id || ''))"
          :sort-directions="['ascend', 'descend']"
        />
        <a-table-column title="Actions" key="actions" width="120">
          <template #default="{ record }">
            <a-button size="small" @click="openEdit(record)">Edit</a-button>
          </template>
        </a-table-column>
      </a-table>
    </a-card>

    <a-modal v-model:open="editOpen" title="Edit product" @ok="saveEdit" :confirm-loading="editSaving">
      <a-form layout="vertical">
        <a-form-item label="Name">
          <a-input v-model:value="editForm.name" />
        </a-form-item>
        <a-form-item label="Description">
          <a-input v-model:value="editForm.description" />
        </a-form-item>
        <a-form-item label="Price">
          <a-input-number v-model:value="editForm.price" :min="0" :step="0.01" style="width: 100%" />
        </a-form-item>
        <a-form-item label="Currency">
          <a-select v-model:value="editForm.currency_code" placeholder="Select currency">
            <a-select-option v-for="c in currencies" :key="c.code" :value="c.code">
              {{ c.code }} — {{ c.name }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="Stripe price id">
          <a-input v-model:value="editForm.stripe_price_id" placeholder="optional" />
        </a-form-item>
      </a-form>
      <a-alert v-if="editMsg" style="margin-top: 12px" type="error" :message="editMsg" show-icon />
    </a-modal>
  </a-space>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiFetch } from '../../services/apiClient';
import { adminTablePagination, useAdminTableSearch } from '../../composables/useAdminTable';

const form = ref({ name: '', description: '', price: 9.99, currency_code: 'USD' });
const msg = ref('');
const loading = ref(false);
const items = ref([]);
const listLoading = ref(false);
const currencies = ref([]);
const editOpen = ref(false);
const editSaving = ref(false);
const editMsg = ref('');
const editId = ref(null);
const editForm = ref({ name: '', description: '', price: 0, currency_code: '', stripe_price_id: '' });

const { searchText, filteredItems } = useAdminTableSearch(items, (row, q) => {
  const blob = [
    row.name,
    row.description,
    row.currency_code,
    row.stripe_price_id,
    row.price,
  ]
    .map((f) => String(f ?? ''))
    .join(' ')
    .toLowerCase();
  return blob.includes(q);
});

const currencyFilters = computed(() => {
  const codes = [...new Set((items.value || []).map((r) => r.currency_code).filter(Boolean))];
  return codes
    .sort()
    .map((c) => ({ text: c, value: c }));
});

async function submit() {
  msg.value = '';
  loading.value = true;
  try {
    const res = await apiFetch('/api/v1/admin/products', { method: 'POST', body: form.value });
    msg.value = `Created product #${res.data.id}`;
    form.value = { name: '', description: '', price: 9.99, currency_code: 'USD' };
    await load();
  } catch (e) {
    msg.value = e.body?.error?.message || e.message || 'Create product failed';
  } finally {
    loading.value = false;
  }
}

async function load() {
  listLoading.value = true;
  try {
    const res = await apiFetch('/api/v1/admin/products');
    items.value = res.data || [];
  } finally {
    listLoading.value = false;
  }
}

async function loadCurrencies() {
  const res = await apiFetch('/api/v1/admin/currencies');
  currencies.value = res.data || [];
  if (!form.value.currency_code && currencies.value.length) {
    const def = currencies.value.find((c) => c.is_default) || currencies.value[0];
    form.value.currency_code = def.code;
  }
}

function openEdit(record) {
  editMsg.value = '';
  editId.value = record.id;
  editForm.value = {
    name: record.name || '',
    description: record.description || '',
    price: Number(record.price) || 0,
    currency_code: record.currency_code || '',
    stripe_price_id: record.stripe_price_id || '',
  };
  editOpen.value = true;
}

async function saveEdit() {
  editMsg.value = '';
  editSaving.value = true;
  try {
    await apiFetch(`/api/v1/admin/products/${editId.value}`, {
      method: 'PUT',
      body: {
        name: editForm.value.name,
        description: editForm.value.description,
        price: editForm.value.price,
        currency_code: editForm.value.currency_code,
        stripe_price_id: editForm.value.stripe_price_id || null,
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

onMounted(async () => {
  await Promise.all([loadCurrencies(), load()]);
});
</script>

<style scoped></style>
