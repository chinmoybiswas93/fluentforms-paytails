<template>
  <div class="ff-summary-cards">
    <div class="summary-card" v-for="(item, idx) in summaryItems" :key="item.title">
      <div class="summary-title">{{ item.title }}</div>
      <div class="summary-value">
        <span v-if="loading">
          <span :class="item.loaderClass"></span>
        </span>
        <span v-else>
          {{ item.prefix }}{{ totals[item.key] }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue';
const props = defineProps({
  totals: { type: Object, required: true },
  loading: { type: Boolean, default: false }
});

const summaryItems = [
  { title: 'Total Forms', key: 'total_forms', loaderClass: 'pulse-loader loader-1', prefix: '' },
  { title: 'Total Payments', key: 'total_payments', loaderClass: 'bounce-loader loader-2', prefix: '' },
  { title: 'Total Amount', key: 'total_amount', loaderClass: 'fade-loader loader-3', prefix: '$' }
];
</script>

<style scoped>
.ff-summary-cards {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 2rem;
}
.summary-card {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  padding: 1.2rem 2rem;
  min-width: 180px;
  flex: 1 1 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.summary-title {
  font-size: 1rem;
  color: #888;
  margin-bottom: 0.5rem;
}
.summary-value {
  font-size: 1.5rem;
  font-weight: 600;
  min-height: 32px;
}
.pulse-loader.loader-1 {
  display: inline-block;
  width: 60px;
  height: 20px;
  background: #f0f0f0;
  border-radius: 4px;
  animation: pulse 1.2s ease-in-out infinite;
}
@keyframes pulse {
  0% { opacity: 0.6; }
  50% { opacity: 1; }
  100% { opacity: 0.6; }
}
.bounce-loader.loader-2 {
  display: inline-block;
  width: 60px;
  height: 20px;
  background: #f0f0f0;
  border-radius: 4px;
  position: relative;
}
.bounce-loader.loader-2::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 4px;
  height: 100%;
  background: #1a7efb;
  animation: bounce 1.5s ease-in-out infinite;
}
@keyframes bounce {
  0% { left: 0; width: 4px; }
  50% { left: calc(100% - 4px); width: 4px; }
  100% { left: 0; width: 4px; }
}
.fade-loader.loader-3 {
  display: inline-block;
  width: 60px;
  height: 20px;
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  border-radius: 4px;
  animation: shimmer 1.8s infinite;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
</style>
