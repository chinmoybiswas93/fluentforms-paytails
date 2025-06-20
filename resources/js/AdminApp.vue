<template>
  <div class="container">
    <h1 style="margin: 15px 0; font-weight: 600;">Fluent Forms Paytails</h1>
    <PaymentsSummary :totals="totals" :loading="loading" />
    <div style="display: flex; gap: 2rem; align-items: flex-start;">
      <div style="flex: 3 1 60%;">
        <PaymentsLineChart :chartData="lineChartData" :loading="loading" />
      </div>
      <div style="flex: 2 1 40%;">
        <PaymentsTopFormsBar :chartData="barChartData" :loading="loading" />
      </div>
    </div>
    <PaymentFormsTable :loading="loading" :forms="forms" :totals="totals" @refresh="refreshForms" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import PaymentsSummary from "./components/PaymentsSummary.vue";
import PaymentsLineChart from "./components/PaymentsLineChart.vue";
import PaymentsTopFormsBar from "./components/PaymentsTopFormsBar.vue";
import PaymentFormsTable from "./components/PaymentFormsTable.vue";

const loading = ref(false);
const forms = ref([]);
const totals = ref({
  total_forms: 0,
  total_payments: 0,
  total_amount: '0.00'
});
const lineChartData = ref({});
const barChartData = ref({});

const fetchForms = async () => {
  loading.value = true;
  try {
    const response = await jQuery.post(ajaxurl, {
      action: 'ffpay_get_payment_forms',
      nonce: FFPaySettings.nonce
    });
    forms.value = response.data.forms || [];
    totals.value = response.data.totals || {
      total_forms: 0,
      total_payments: 0,
      total_amount: '0.00'
    };

    // Prepare 12 months: previous 11 months + current month
    const monthNames = [
      'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
    ];
    const now = new Date();
    const months = [];
    for (let i = 11; i >= 0; i--) {
      const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
      months.push({
        key: `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`,
        label: `${monthNames[d.getMonth()]} ${d.getFullYear()}`
      });
    }
    const monthly = response.data.monthly_payments || [];
    const monthlyMap = {};
    monthly.forEach(m => {
      monthlyMap[m.month] = m;
    });
    let cumulative = 0;
    const chartData = months.map(m => {
      const entry = monthlyMap[m.key];
      if (entry) {
        cumulative = parseFloat(entry.cumulative_total);
      }
      return {
        label: m.label,
        value: entry ? parseFloat(entry.cumulative_total) : cumulative
      };
    });

    lineChartData.value = {
      labels: chartData.map(m => m.label),
      datasets: [{
        label: 'Cumulative Payments',
        data: chartData.map(m => m.value),
        borderColor: '#1a7efb',
        backgroundColor: 'rgba(26,126,251,0.1)',
        fill: true,
        tension: 0.3
      }]
    };

    // Prepare bar chart data (top 5 forms by total paid)
    const topForms = response.data.top_forms || [];
    const fullLabels = topForms.map(f => f.title || '');
    barChartData.value = {
      labels: topForms.map(f => {
        const title = f.title || '';
        if (title.length > 12) {
          const start = title.slice(0, 5);
          const end = title.slice(-4);
          return `${start}...${end}`;
        }
        return title;
      }),
      datasets: [{
        label: 'Total Paid',
        data: topForms.map(f => parseFloat(f.total_paid)),
        backgroundColor: '#1a7efb'
      }],
      fullLabels // <-- add this for tooltip use
    };
  } catch (e) {
    // handle error
  } finally {
    loading.value = false;
  }
};

const refreshForms = () => fetchForms();

onMounted(() => fetchForms());
</script>
