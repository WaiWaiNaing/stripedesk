<template>
  <div>
    <a-typography-title :level="3">Shop</a-typography-title>
    <a-spin :spinning="loading">
      <a-alert v-if="error" style="margin-bottom: 12px" type="error" :message="error" show-icon />

      <a-row v-if="!error" :gutter="[16, 16]">
        <a-col v-for="p in products" :key="p.id" :xs="24" :md="12">
          <ProductCard :product="p" :disabled="buyingId === p.id" @buy="buy" />
        </a-col>
      </a-row>
      <a-empty v-if="!error && !loading && !products.length" description="No products." />
    </a-spin>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { apiFetch } from '../services/apiClient';
import ProductCard from '../components/ProductCard.vue';

const loading = ref(true);
const error = ref('');
const products = ref([]);
const buyingId = ref(0);

onMounted(async () => {
  try {
    const res = await apiFetch('/api/v1/products');
    products.value = res.data || [];
  } catch (e) {
    error.value = e.body?.error?.message || e.message || 'Failed to load products';
  } finally {
    loading.value = false;
  }
});

async function buy(productId) {
  buyingId.value = productId;
  try {
    const res = await apiFetch('/api/v1/checkout/session', {
      method: 'POST',
      body: { product_id: productId, quantity: 1 },
    });
    const url = res.data?.checkout_url;
    if (!url) throw new Error('Missing checkout URL');
    window.location.href = url;
  } catch (e) {
    error.value = e.body?.error?.message || e.message || 'Checkout failed';
  } finally {
    buyingId.value = 0;
  }
}
</script>

<style scoped>
/* AntD-driven layout */
</style>

