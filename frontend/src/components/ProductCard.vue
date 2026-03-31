<template>
  <a-card size="small">
    <a-row justify="space-between" align="top">
      <a-col>
        <div style="font-weight: 700">{{ product.name }}</div>
        <a-typography-text type="secondary">{{ product.description }}</a-typography-text>
      </a-col>
      <a-col style="text-align: right">
        <div style="font-weight: 700">{{ product.price }}</div>
        <a-typography-text type="secondary">{{ product.currency_code }}</a-typography-text>
      </a-col>
    </a-row>
    <div style="margin-top: 12px">
      <a-button type="primary" :loading="disabled" @click="$emit('buy', product.id)">Buy</a-button>
    </div>
  </a-card>
</template>

<script setup>
defineProps({
  product: { type: Object, required: true },
  disabled: { type: Boolean, default: false },
});
defineEmits(['buy']);
</script>

<style scoped></style>

