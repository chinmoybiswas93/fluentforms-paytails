<template>
  <div class="ff-chart-card">
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({
  chartData: { type: Object, required: false, default: () => ({}) },
  loading: { type: Boolean, default: false }
});

const chartCanvas = ref(null);
let chartInstance = null;

onMounted(() => {
  if (chartCanvas.value) {
    chartInstance = new Chart(chartCanvas.value, {
      type: 'bar',
      data: props.chartData || {
        labels: ['A', 'B', 'C'],
        datasets: [{
          label: 'Payments',
          data: [12, 19, 3],
          backgroundColor: '#1a7efb'
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
        },
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true }
        }
      }
    });
  }
});

watch(() => props.chartData, (newData) => {
  if (chartInstance && newData) {
    chartInstance.data = newData;
    chartInstance.update();
  }
});
</script>

<style scoped>
.ff-chart-card {
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  padding: 1.5rem 2rem;
  margin-bottom: 2rem;
  min-height: 260px;
  display: flex;
  align-items: center;
  justify-content: center;
}
canvas {
  max-width: 100%;
  max-height: 220px;
}
</style>
