<template>
  <div class="ff-chart-card">
    <canvas ref="barChartCanvas"></canvas>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';

Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const props = defineProps({
  chartData: { type: Object, required: true },
  loading: { type: Boolean, default: false }
});

const barChartCanvas = ref(null);
let chartInstance = null;

onMounted(() => {
  if (barChartCanvas.value) {
    chartInstance = new Chart(barChartCanvas.value, {
      type: 'bar',
      data: props.chartData,
      options: {
        indexAxis: 'y',
        responsive: true,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              title: (items) => {
                // Use full label from chartData.fullLabels
                const idx = items[0].dataIndex;
                return props.chartData.fullLabels
                  ? props.chartData.fullLabels[idx]
                  : items[0].label;
              }
            }
          }
        },
        scales: {
          x: { beginAtZero: true },
          y: { grid: { display: false } }
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
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
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
