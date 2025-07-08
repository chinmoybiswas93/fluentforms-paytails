<template>
  <div style="padding: 0rem 1.5rem 1.5rem 0.2rem;">
    <PaymentFormsTable/>
  </div>
</template>

<script setup>
import PaymentFormsTable from "./components/PaymentFormsTable.vue";
</script>
<style>
:root{
  --primary-color-ffpay: #187efc;
}
.ffpay-dashboard-footer {
  margin: 20px 0;
  padding: 15px;
  border-top: 1px solid #eee;
  color: #666;
  font-size: 13px;
  text-align: center;
}

.ffpay-dashboard-footer a {
  color: #2271b1;
  text-decoration: none;
}

.ffpay-dashboard-footer a:hover {
  color: var(--primary-color-ffpay);
}

</style>
