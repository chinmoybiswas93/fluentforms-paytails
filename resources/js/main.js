import { createApp } from 'vue'
import AdminApp from "./AdminApp.vue";

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('ffpay-admin-dashboard')) {
        createApp(AdminApp).mount('#ffpay-admin-dashboard')
    } else{
        createApp(AdminApp).mount('#ffpay-admin-dashboard')
    }
})
