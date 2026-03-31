<template>
  <a-card style="max-width: 560px; margin: 0 auto">
    <a-result status="warning" title="Payment cancelled" sub-title="No charge was made. You can try again anytime.">
      <template #extra>
        <router-link to="/shop"><a-button type="primary">Back to shop</a-button></router-link>
      </template>
    </a-result>
  </a-card>
</template>

<style scoped></style>

