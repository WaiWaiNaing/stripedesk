<template>
  <a-tag :color="color">{{ status }}</a-tag>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({ status: { type: String, default: '' } });
const color = computed(() => {
  const s = (props.status || '').toLowerCase();
  if (s === 'paid') return 'green';
  if (s === 'pending') return 'orange';
  if (s === 'cancelled' || s === 'failed' || s === 'void') return 'red';
  return 'default';
});
</script>

<style scoped></style>

