<template>
  <div class="ff-chart-card">
    <canvas ref="lineChartCanvas"></canvas>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { Chart, LineController, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({
  chartData: { type: Object, required: true },
  loading: { type: Boolean, default: false }
});

const lineChartCanvas = ref(null);
let chartInstance = null;

onMounted(() => {
  if (lineChartCanvas.value) {
    chartInstance = new Chart(lineChartCanvas.value, {
      type: 'line',
      data: props.chartData,
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
