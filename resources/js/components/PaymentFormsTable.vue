<template>
  <div class="ffpay-table-header">
    <h3 class="ffpay-dashboard-heading"> Payment Analytics for <span> Fluent Forms</span> </h3>
    <button @click="refreshForms" class="refresh-button">
      <span v-if="!loading">Refresh</span>
      <span v-else class="loading-spinner"></span>
    </button>
  </div>
  <div class="ffpay-table-wrapper">

    <div v-if="loading" class="ffpay-summary-cards">
      <div class="summary-card shimmer-card">
        <div class="shimmer-line shimmer-title"></div>
        <div class="shimmer-line shimmer-value"></div>
      </div>
      <div class="summary-card shimmer-card">
        <div class="shimmer-line shimmer-title"></div>
        <div class="shimmer-line shimmer-value"></div>
      </div>
      <div class="summary-card shimmer-card">
        <div class="shimmer-line shimmer-title"></div>
        <div class="shimmer-line shimmer-value"></div>
      </div>
    </div>
    <div class="ffpay-summary-cards" v-else>
      <div class="summary-card">
        <div class="summary-title"> Total Payment Forms </div>
        <div class="summary-value">{{ totals.total_forms }}</div>
      </div>
      <div class="summary-card">
        <div class="summary-title"> Total Payments Entries </div>
        <div class="summary-value">{{ totals.total_payments }}</div>
      </div>
      <div class="summary-card">
        <div class="summary-title">Total Payments Received </div>
        <div class="summary-value">${{ totals.total_amount }}</div>
      </div>
    </div>

    <div class="search-wrapper" v-if="!loading">
      <div class="search-container">
        <input
            type="text"
            v-model="searchQuery"
            placeholder="Search Forms"
            class="search-input"
            @input="onSearch"
        >
        <div class="search-icon">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"/>
            <path d="m21 21-4.35-4.35"/>
          </svg>
        </div>
      </div>
      <div class="search-results-info" v-if="searchQuery">
        Found {{ filteredForms.length }} of {{ forms.length }} forms
      </div>
    </div>

    <div class="responsive-table">
      <table>
        <thead>
        <tr>
          <th v-for="column in columns" :key="column.key">{{ column.label }}</th>
        </tr>
        </thead>
        <tbody>
        <template v-if="loading">
          <tr v-for="i in 5" :key="'shimmer-' + i" class="shimmer-row">
            <td v-for="column in columns" :key="column.key" :data-label="column.label">
              <div class="shimmer-line" :class="getShimmerClass(column.key)"></div>
            </td>
          </tr>
        </template>

        <template v-else>
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
          <tr v-if="!forms.length">
            <td :colspan="columns.length" class="no-data">No payment forms found</td>
          </tr>
        </template>

        </tbody>
      </table>
    </div>

    <div class="ffpay-pagination" v-if="totalPages > 1 && !loading">
      <div class="pagination-container">
        <div class="pagination-controls">
          <span class="total-text">Total {{ filteredForms.length }}</span>
          <div class="per-page-selector">
            <select v-model="perPage" @change="onPerPageChange" class="per-page-select">
              <option value="10">10/page</option>
              <option value="25">25/page</option>
              <option value="50">50/page</option>
              <option value="100">100/page</option>
            </select>
          </div>
        </div>

        <div class="pagination-navigation">
          <button
              @click="goToPage(currentPage - 1)"
              :disabled="currentPage === 1"
              class="pagination-button pagination-prev"
          >
            &lt;
          </button>
          <div class="pagination-numbers">
            <button
                v-if="showFirstPage"
                @click="goToPage(1)"
                :class="['pagination-number', { active: currentPage === 1 }]"
            >
              1
            </button>
            <span v-if="showLeftEllipsis" class="pagination-ellipsis">...</span>
            <button
                v-for="page in visiblePages"
                :key="page"
                @click="goToPage(page)"
                :class="['pagination-number', { active: currentPage === page }]"
            >
              {{ page }}
            </button>
            <span v-if="showRightEllipsis" class="pagination-ellipsis">...</span>
            <button
                v-if="showLastPage"
                @click="goToPage(totalPages)"
                :class="['pagination-number', { active: currentPage === totalPages }]"
            >
              {{ totalPages }}
            </button>
          </div>
          <button
              @click="goToPage(currentPage + 1)"
              :disabled="currentPage === totalPages"
              class="pagination-button pagination-next"
          >
            &gt;
          </button>
        </div>
        <div class="go-to-page">
          <span class="go-to-text">Go to</span>
          <input
              type="number"
              v-model="goToPageInput"
              @keyup.enter="goToInputPage"
              @blur="goToInputPage"
              :min="1"
              :max="totalPages"
              class="go-to-input"
          >
        </div>
      </div>
    </div>

    <div v-if="error" class="error-notice">{{ error }}</div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';

const loading = ref(false);
const error = ref(null);
const forms = ref([]);
const totals = ref({
  total_forms: 0,
  total_payments: 0,
  total_amount: '0.00'
});

const currentPage = ref(1);
const perPage = ref(10);
const searchQuery = ref('');
const filteredForms = ref([]);
const goToPageInput = ref(1);

const columns = ref([
  { key: 'id', label: 'ID' },
  { key: 'title', label: 'Title' },
  { key: 'status', label: 'Status' },
  { key: 'creator', label: 'Creator' },
  { key: 'total_paid', label: 'Total Payment Received (USD)' },
  { key: 'actions', label: 'Actions' }
]);

const totalPages = computed(() => Math.ceil(filteredForms.value.length / perPage.value));
const paginatedForms = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  const end = start + perPage.value;
  return filteredForms.value.slice(start, end);
});

const visiblePages = computed(() => {
  const pages = [];
  const total = totalPages.value;
  const current = currentPage.value;
  const delta = 2; // Number of pages to show on each side of current page

  let start = Math.max(2, current - delta);
  let end = Math.min(total - 1, current + delta);

  // Adjust start and end if we're near the beginning or end
  if (current - delta <= 2) {
    end = Math.min(total - 1, 2 + delta * 2);
  }
  if (current + delta >= total - 1) {
    start = Math.max(2, total - 1 - delta * 2);
  }

  for (let i = start; i <= end; i++) {
    pages.push(i);
  }

  return pages;
});

const showFirstPage = computed(() => {
  return totalPages.value > 1 && (currentPage.value > 3 || totalPages.value <= 7);
});

const showLastPage = computed(() => {
  return totalPages.value > 1 && (currentPage.value < totalPages.value - 2 || totalPages.value <= 7);
});

const showLeftEllipsis = computed(() => {
  return currentPage.value > 4 && totalPages.value > 7;
});

const showRightEllipsis = computed(() => {
  return currentPage.value < totalPages.value - 3 && totalPages.value > 7;
});

const startRecord = computed(() => {
  return filteredForms.value.length === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1;
});
const onSearch = () => {
  currentPage.value = 1;
  goToPageInput.value = 1;
  filterForms();
};
const goToInputPage = () => {
  const page = parseInt(goToPageInput.value);
  if (page && page >= 1 && page <= totalPages.value) {
    goToPage(page);
  } else {
    goToPageInput.value = currentPage.value;
  }
};
const onPerPageChange = () => {
  currentPage.value = 1;
  goToPageInput.value = 1;
};

const filterForms = () => {
  if (!searchQuery.value.trim()) {
    filteredForms.value = forms.value;
    return;
  }

  const query = searchQuery.value.toLowerCase().trim();
  filteredForms.value = forms.value.filter(form => {
    return (
        form.title.toLowerCase().includes(query) ||
        form.creator.toLowerCase().includes(query) ||
        form.status.toLowerCase().includes(query)
    );
  });
};
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
    goToPageInput.value = page;
  }
};

const endRecord = computed(() => {
  const end = currentPage.value * perPage.value;
  return Math.min(end, filteredForms.value.length);
});

const getShimmerClass = (columnKey) => {
  const classes = {
    'id': 'shimmer-id',
    'title': 'shimmer-title-long',
    'status': 'shimmer-status',
    'creator': 'shimmer-creator',
    'total_paid': 'shimmer-amount',
    'actions': 'shimmer-actions'
  };
  return classes[columnKey] || 'shimmer-default';
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
    filteredForms.value = forms.value;
    goToPageInput.value = 1;
    totals.value = response.data.totals || {
      total_forms: 0,
      total_payments: 0,
      total_amount: '0.00'
    };
  } catch (e) {
    error.value = 'Failed to load forms. Please try again.';
  } finally {
    loading.value = false;
  }
};
const refreshForms = () => {
  searchQuery.value = ''; // Clear search on refresh
  fetchForms();
};

onMounted(() => fetchForms());
</script>


<style scoped>
.ffpay-table-wrapper {
  padding: 0;
  max-width: 100%;
  overflow-x: auto;
}

.ffpay-table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 1.5rem;
  background: white;
  margin-bottom: 2rem;
  position: sticky;
  top: 32px;
  z-index: 9999;
}

h3.ffpay-dashboard-heading {
  font-size: 1rem;
  color: #666;
}

h3.ffpay-dashboard-heading span {
  background: #1c7efc;
  color: #ffffff;
  padding: .2rem;
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

.responsive-table {
  background: white;
}

.responsive-table table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
  padding: 1rem;
}

.responsive-table table tr:hover {
  background: #1a7efb08;
  cursor: pointer;
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

.ffpay-summary-cards {
  display: flex;
  gap: 20px;
  margin-bottom: 30px;
}

.summary-card {
  flex: 1;
  background: #ffffff;
  padding: 1.8rem 1.5rem;
}

.summary-card:hover .summary-value {
  background: var(--primary-color-ffpay);
  transition: all .5s ease;
  transform: skewY(5deg);
  color: white;
  padding: .2rem;
  width: max-content;
}

.summary-card:hover .summary-title {
  color: var(--primary-color-ffpay);
  transition: all 2s ease;
  transform: skewY(5deg);
}

.summary-title {
  font-size: 18px;
  color: #666;
  margin-bottom: 10px;
}

.summary-value {
  font-size: 24px;
  font-weight: bold;
  color: #333;
  padding: .2rem;
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

.search-wrapper {
  margin-bottom: 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.search-container {
  position: relative;
  width: 400px;
  max-width: 400px;
  flex-shrink: 0;
  margin-right: 0.1rem;
}

.search-input {
  width: 100%;
  padding: 10px 40px 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s ease;
  margin-right: 0.5rem;
}

.search-input:focus {
  outline: none;
  border: 1px solid #2563eb;
}

.search-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
  pointer-events: none;
}

.search-results-info {
  margin-top: 8px;
  font-size: 14px;
  color: #6b7280;
  text-align: right;
}

.ffpay-pagination {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: flex-end;
}

.pagination-container {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  margin-top: 1.5rem;
  padding: 1rem;
  background: white;
  border-radius: 4px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.total-text {
  font-size: 14px;
  color: #374151;
  font-weight: 500;
}

.per-page-selector {
  position: relative;
}

.per-page-select {
  padding: 6px 24px 6px 8px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  background: #fff;
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
  background-position: right 8px center;
  background-repeat: no-repeat;
  background-size: 16px;
}

.per-page-select:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.pagination-navigation {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.pagination-numbers {
  display: flex;
  gap: 0.25rem;
  align-items: center;
}

.pagination-button {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  color: #374151;
  transition: all 0.2s ease;
}

.pagination-button:hover:not(:disabled) {
  background: #f3f4f6;
  border-color: #9ca3af;
}

.pagination-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-number {
  min-width: 32px;
  height: 32px;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  transition: all 0.2s ease;
}

.pagination-number:hover {
  background: #f3f4f6;
  border-color: #9ca3af;
}

.pagination-number.active {
  background: #2563eb;
  border-color: #2563eb;
  color: #fff;
}

.pagination-ellipsis {
  padding: 0 8px;
  color: #6b7280;
  font-weight: 500;
}

.go-to-page {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.go-to-text {
  font-size: 14px;
  color: #374151;
}

.go-to-input {
  width: 50px;
  padding: 6px 8px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  text-align: center;
}

.go-to-input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

/* Shimmer Animation */
@keyframes shimmer {
  0% {
    background-position: -200px 0;
  }
  100% {
    background-position: calc(200px + 100%) 0;
  }
}

.shimmer-line {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200px 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
  height: 16px;
  margin: 4px 0;
}

/* Shimmer Card Styles */
.shimmer-card {
  background: #fafafa;
  border: 1px solid #e1e5e9;
  padding: 1.5rem;
  border-radius: 8px;
}

.shimmer-title {
  width: 60%;
  height: 14px;
  margin-bottom: 12px;
}

.shimmer-value {
  width: 40%;
  height: 24px;
}

/* Shimmer Table Row Styles */
.shimmer-row td {
  padding: 12px 8px;
  border-bottom: 1px solid #e1e5e9;
}

.shimmer-id {
  width: 30px;
  height: 16px;
}

.shimmer-title-long {
  width: 80%;
  height: 16px;
}

.shimmer-status {
  width: 60px;
  height: 20px;
  border-radius: 12px;
}

.shimmer-creator {
  width: 70%;
  height: 16px;
}

.shimmer-amount {
  width: 50px;
  height: 16px;
}

.shimmer-actions {
  width: 90px;
  height: 16px;
}

.shimmer-default {
  width: 60%;
  height: 16px;
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
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
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

  .search-wrapper {
    align-items: stretch;
  }

  .search-container {
    max-width: 100%;
  }

  .search-results-info {
    text-align: left;
  }

  .ffpay-pagination {
    align-items: stretch;
    gap: 0.75rem;
  }

  .pagination-container {
    justify-content: center;
    gap: 0.25rem;
  }

  .pagination-controls {
    order: 1;
  }

  .pagination-navigation {
    order: 2;
  }

  .go-to-page {
    order: 3;
  }

  .shimmer-line {
    height: 14px;
  }

  .shimmer-title {
    width: 70%;
  }

  .shimmer-value {
    width: 50%;
    height: 20px;
  }
}

@media (max-width: 480px) {
  .pagination-container {
    flex-direction: column;
  }

  .pagination-navigation {
    flex-wrap: wrap;
    justify-content: center;
  }

  .pagination-numbers {
    flex-wrap: wrap;
    justify-content: center;
  }

  .pagination-button {
    width: 28px;
    height: 28px;
    font-size: 12px;
  }

  .pagination-number {
    min-width: 28px;
    height: 28px;
    font-size: 12px;
  }
}
</style>
