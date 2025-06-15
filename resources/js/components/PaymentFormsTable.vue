<template>
  <div>
    <!-- Summary Cards - Outside table wrapper -->
    <div v-if="loading" class="spinner-paytails" style="margin-bottom: 2rem; padding: 0.5rem 1rem">
      <span class="spinner is-active"></span>
      <p> Loading stats, please wait... </p>
    </div>
    <div class="ff-summary-cards" v-else>
      <div class="summary-card">
        <div class="summary-title">Total Forms</div>
        <div class="summary-value">{{ totals.total_forms }}</div>
      </div>
      <div class="summary-card">
        <div class="summary-title">Total Payments</div>
        <div class="summary-value">{{ totals.total_payments }}</div>
      </div>
      <div class="summary-card">
        <div class="summary-title">Total Amount</div>
        <div class="summary-value">${{ totals.total_amount }}</div>
      </div>
    </div>

    <!-- Table wrapper -->
    <div class="ff-table-wrapper">
      <div class="ff-table-header">
        <button @click="refreshForms" class="refresh-button">
          <span v-if="!loading">Refresh</span>
          <span v-else class="loading-spinner"></span>
        </button>
      </div>

      <div class="responsive-table">
        <div class="ff-table-header">
          <h2> Payment Receiver Form Analytics </h2>
          <!-- Pagination Controls Top -->
          <div class="pagination-controls" v-if="!loading && forms.length > 0">
            <select v-model="itemsPerPage" @change="goToPage(1)" class="items-per-page">
              <option value="5">5 per page</option>
              <option value="10">10 per page</option>
              <option value="25">25 per page</option>
              <option value="50">50 per page</option>
            </select>
          </div>
        </div>

        <table>
          <thead>
            <tr>
              <th v-for="column in columns" :key="column.key">{{ column.label }}</th>
            </tr>
            <tr v-if="loading" class="spinner-paytails">
              <td :colspan="columns.length" class="loading-state">
                <span class="spinner is-active"></span>
                <p>Loading forms data, please wait...</p>
              </td>
            </tr>
          </thead>
          <tbody>
            <tr v-for="form in paginatedForms" :key="form.id">
              <td data-label="ID">{{ form.id }}</td>
              <td data-label="Title">
                <a :href="form.edit_url" class="form-title">{{ form.title }}</a>
              </td>
              <td data-label="Status">
                <span class="status-badge" :class="form.status.toLowerCase()">
                  {{ form.status }}
                </span>
              </td>
              <td data-label="Creator">{{ form.creator }}</td>
              <td data-label="Total Paid">${{ form.total_paid }}</td>
              <td data-label="Actions">
                <a :href="form.edit_url" class="table-button">Edit</a>
                <a :href="form.entries_url" class="table-button secondary">Entries</a>
              </td>
            </tr>
            <tr v-if="!forms.length && !loading">
              <td :colspan="columns.length" class="no-data">No payment forms found</td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination Controls Bottom -->
        <div class="pagination-wrapper" v-if="!loading && forms.length > 0">
          <div class="pagination-info">
            Showing {{ startItem }} to {{ endItem }} of {{ forms.length }} entries
          </div>
          <div class="pagination">
            <button @click="goToPage(1)" :disabled="currentPage === 1" class="pagination-btn">
              First
            </button>
            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="pagination-btn">
              Previous
            </button>

            <span v-for="page in visiblePages" :key="page">
              <button v-if="page !== '...'" @click="goToPage(page)"
                :class="['pagination-btn', { active: currentPage === page }]">
                {{ page }}
              </button>
              <span v-else class="pagination-dots">...</span>
            </span>

            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" class="pagination-btn">
              Next
            </button>
            <button @click="goToPage(totalPages)" :disabled="currentPage === totalPages" class="pagination-btn">
              Last
            </button>
          </div>
        </div>
      </div>

      <div v-if="error" class="error-notice">{{ error }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';

const loading = ref(false);
const error = ref(null);
const forms = ref([]);
const currentPage = ref(1);
const itemsPerPage = ref(10);
const totals = ref({
  total_forms: 0,
  total_payments: 0,
  total_amount: '0.00'
});

const columns = ref([
  { key: 'id', label: 'ID' },
  { key: 'title', label: 'Title' },
  { key: 'status', label: 'Status' },
  { key: 'creator', label: 'Creator' },
  { key: 'total_paid', label: 'Total Payment Received (USD)' },
  { key: 'actions', label: 'Actions' }
]);

// Computed properties for pagination
const totalPages = computed(() => {
  return Math.ceil(forms.value.length / itemsPerPage.value);
});

const paginatedForms = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value;
  const end = start + itemsPerPage.value;
  return forms.value.slice(start, end);
});

const startItem = computed(() => {
  return forms.value.length === 0 ? 0 : (currentPage.value - 1) * itemsPerPage.value + 1;
});

const endItem = computed(() => {
  const end = currentPage.value * itemsPerPage.value;
  return end > forms.value.length ? forms.value.length : end;
});

const visiblePages = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  const pages = [];

  if (total <= 7) {
    // Show all pages if total is 7 or less
    for (let i = 1; i <= total; i++) {
      pages.push(i);
    }
  } else {
    // Show pages with ellipsis
    if (current <= 3) {
      pages.push(1, 2, 3, 4, '...', total);
    } else if (current >= total - 2) {
      pages.push(1, '...', total - 3, total - 2, total - 1, total);
    } else {
      pages.push(1, '...', current - 1, current, current + 1, '...', total);
    }
  }

  return pages;
});

// Pagination methods
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

const fetchForms = async () => {
  loading.value = true;
  error.value = null;

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

    // Reset to first page when refreshing
    currentPage.value = 1;
  } catch (e) {
    error.value = 'Failed to load forms. Please try again.';
  } finally {
    loading.value = false;
  }
};

const refreshForms = () => fetchForms();

onMounted(() => fetchForms());
</script>

<style scoped>
.ff-table-wrapper {
  padding: 20px;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  max-width: 100%;
  overflow-x: auto;
}

.ff-table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.ff-table-header h2 {
  font-size: 1.2rem;
  margin: 0;
}

.refresh-button {
  background: #1a7efb;
  color: #fff;
  border: none;
  padding: 0.5rem 1rem;
  font-size: 0.95rem;
  border-radius: 5px;
  cursor: pointer;
  transition: background 0.2s ease-in-out;
  position: absolute;
  top: 1.5rem;
  right: 3.5rem;
}

.refresh-button:hover {
  background: #1a7efb;
}

.loading-spinner {
  width: 18px;
  height: 18px;
  border: 2px solid #fff;
  border-top-color: transparent;
  border-radius: 50%;
  display: inline-block;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.responsive-table table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;

  tr:hover {
    background: #1a7efb08;
    cursor: pointer;
  }
}

.responsive-table th,
.responsive-table td {
  padding: 12px 16px;
  border-bottom: 1px solid #eaeaea;
  text-align: left;
  white-space: nowrap;
}

.responsive-table thead {
  background: #f9fafb;
}

.form-title {
  color: #1a7efb;
  text-decoration: none;
  font-weight: 500;
}

.form-title:hover {
  text-decoration: underline;
}

.status-badge {
  padding: 5px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
}

.status-badge.published {
  background-color: #1a7efb0f;
  color: #1a7efb;
}

.status-badge.unpublished {
  background-color: #d636381a;
  color: red;
  font-weight: 600;
}

.status-badge.draft {
  background-color: #fff3cd;
  color: #856404;
}

.table-button {
  display: inline-block;
  padding: 6px 10px;
  font-size: 13px;
  border-radius: 4px;
  text-decoration: none;
  color: white;
  background-color: #1a7efb;
  margin-right: 5px;
}

.table-button.secondary {
  background-color: #6c757d;
}

.table-button:hover {
  opacity: 0.85;
}

.no-data {
  text-align: center;
  padding: 20px;
  font-style: italic;
  color: #999;
}

.error-notice {
  margin-top: 15px;
  background-color: #ffe5e5;
  color: #b32d2e;
  padding: 12px;
  border-radius: 5px;
  border: 1px solid #f5c6cb;
}

/* Pagination Styles */
.pagination-controls {
  display: flex;
  align-items: center;
  gap: 10px;
}

.items-per-page {
  padding: 6px 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  background: white;
  line-height: 1;
}

.pagination-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
  padding: 15px 0;
  border-top: 1px solid #eaeaea;
}

.pagination-info {
  font-size: 14px;
  color: #666;
}

.pagination {
  display: flex;
  align-items: center;
  gap: 5px;
}

.pagination-btn {
  padding: 8px 12px;
  border: 1px solid #ddd;
  background: white;
  color: #333;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  transition: all 0.2s ease;
}

.pagination-btn:hover:not(:disabled) {
  background: #f5f5f5;
  border-color: #1a7efb;
}

.pagination-btn.active {
  background: #1a7efb;
  color: white;
  border-color: #1a7efb;
}

.pagination-btn:disabled {
  background: #f5f5f5;
  color: #999;
  cursor: not-allowed;
  border-color: #ddd;
}

.pagination-dots {
  padding: 8px 4px;
  color: #666;
}

/* Responsive Design */
@media (max-width: 768px) {

  .responsive-table table,
  .responsive-table thead,
  .responsive-table tbody,
  .responsive-table th,
  .responsive-table td,
  .responsive-table tr {
    display: block;
  }

  .responsive-table thead {
    display: none;
  }

  .responsive-table tr {
    margin-bottom: 15px;
    background: #fff;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    overflow: hidden;
  }

  .responsive-table td {
    display: flex;
    justify-content: space-between;
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;
  }

  .responsive-table td::before {
    content: attr(data-label);
    font-weight: 600;
    color: #666;
    flex: 0 0 120px;
    margin-right: 10px;
  }

  .pagination-wrapper {
    flex-direction: column;
    gap: 15px;
    text-align: center;
  }

  .pagination {
    flex-wrap: wrap;
    justify-content: center;
  }

  .ff-table-header {
    flex-direction: column;
    gap: 10px;
    align-items: stretch;
  }

  .pagination-controls {
    justify-content: center;
  }
}

.ff-summary-cards {
  display: flex;
  gap: 20px;
  margin-bottom: 30px;
}

.summary-card {
  flex: 1;
  background: #ffffff;
  border-radius: 4px;
  padding: 20px;
  box-shadow: 0 2px 3px 0 hsla(0, 0%, 51%, .1);
  /* cursor: pointer; */
}

/* .summary-card:hover {
  padding: 20px;
  box-shadow: 0 1px 3px rgb(255 0 195);
  transition: all 0.5s ease;
} */

.summary-title {
  font-size: 14px;
  color: #666;
  margin-bottom: 10px;
}

.summary-value {
  font-size: 24px;
  font-weight: bold;
  color: #333;
}

.spinner-paytails {
  background: #f7ff1217;
  border: 2px solid #cbc41f40;
}

.spinner-paytails p {
  color: #cbc51f;
  font-size: 18px;
  padding: 0;
  margin: 0.4rem 0;
}
</style>
