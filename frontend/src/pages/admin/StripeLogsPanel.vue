<template>
  <a-card title="Stripe logs">
    <a-input-search
      v-model:value="searchText"
      allow-clear
      placeholder="Search event type, time, or payload"
      style="max-width: 420px; margin-bottom: 12px"
    />
    <a-table
      :data-source="filteredItems"
      :loading="loading"
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
        title="Event"
        data-index="event_type"
        key="event_type"
        :filters="eventTypeFilters"
        :filter-multiple="true"
        :on-filter="(value, record) => record.event_type === value"
        :sorter="(a, b) => String(a.event_type || '').localeCompare(String(b.event_type || ''))"
        :sort-directions="['ascend', 'descend']"
      />
      <a-table-column
        title="Created"
        data-index="created_at"
        key="created_at"
        width="200"
        :sorter="(a, b) => new Date(a.created_at || 0) - new Date(b.created_at || 0)"
        :sort-directions="['ascend', 'descend']"
      />
      <a-table-column title="Payload" key="payload" ellipsis>
        <template #default="{ record }">
          <span :title="payloadPreview(record.payload)">{{ payloadPreview(record.payload) }}</span>
        </template>
      </a-table-column>
    </a-table>
  </a-card>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { apiFetch } from '../../services/apiClient';
import { adminTablePagination, useAdminTableSearch } from '../../composables/useAdminTable';

const logs = ref([]);
const loading = ref(false);

const { searchText, filteredItems } = useAdminTableSearch(logs, (row, q) => {
  const payloadStr =
    typeof row.payload === 'string'
      ? row.payload
      : JSON.stringify(row.payload ?? '');
  const blob = [row.event_type, row.created_at, payloadStr].join(' ').toLowerCase();
  return blob.includes(q);
});

const eventTypeFilters = computed(() => {
  const types = [...new Set((logs.value || []).map((r) => r.event_type).filter(Boolean))];
  return types
    .sort()
    .map((t) => ({ text: t, value: t }));
});

function payloadPreview(payload) {
  if (payload === null || payload === undefined) return '';
  if (typeof payload === 'string') return payload.length > 120 ? `${payload.slice(0, 120)}…` : payload;
  try {
    const s = JSON.stringify(payload);
    return s.length > 120 ? `${s.slice(0, 120)}…` : s;
  } catch {
    return String(payload);
  }
}

async function load() {
  loading.value = true;
  try {
    const res = await apiFetch('/api/v1/admin/stripe-logs', { params: { limit: 500 } });
    logs.value = res.data || [];
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>

<style scoped></style>
