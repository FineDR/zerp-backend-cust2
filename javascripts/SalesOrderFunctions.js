/**
 * Sales Order Search Modern Logic
 */

const SalesOrderSearch = {
    debounceTimer: null,
    formId: '',

    init() {
        console.log('SalesOrderSearch Module Initialized');
        this.bindEvents();
        this.formId = document.querySelector('input[name="FormID"]')?.value || '';

        // Load initial data if none exists
        if (document.querySelector('.so-table tbody').children.length === 0) {
            this.fetchResults();
        }
    },

    bindEvents() {
        // Smart Search input
        const searchInput = document.getElementById('SmartSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.debounce(() => this.fetchResults(), 500);
            });
        }

        // Advanced filter changes
        document.querySelectorAll('.filter-input').forEach(input => {
            input.addEventListener('change', () => this.fetchResults());
        });

        // Toggle Advanced Filters
        const toggleBtn = document.getElementById('ToggleFilters');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                const panel = document.getElementById('AdvancedFiltersPanel');
                panel.style.display = panel.style.display === 'none' ? 'grid' : 'none';
            });
        }

        // Bulk selection
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('so-check-all')) {
                document.querySelectorAll('.so-row-check').forEach(cb => cb.checked = e.target.checked);
                this.updateBulkBar();
            }
            if (e.target.classList.contains('so-row-check')) {
                this.updateBulkBar();
            }
        });
    },

    debounce(func, delay) {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(func, delay);
    },

    async fetchResults() {
        const tableBody = document.querySelector('.so-table tbody');
        const container = document.querySelector('.so-table-container');

        container.classList.add('table-loading-overlay');

        const formData = new FormData(document.getElementById('SalesOrderForm'));
        formData.append('AjaxSearch', '1');

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error('Search failed');

            const html = await response.text();
            tableBody.innerHTML = html;

        } catch (error) {
            console.error('Search error:', error);
            tableBody.innerHTML = '<tr><td colspan="100%" class="text-center p-8 text-red-500">Error loading results. Please try again.</td></tr>';
        } finally {
            container.classList.remove('table-loading-overlay');
            this.updateBulkBar();
        }
    },

    updateBulkBar() {
        const selected = document.querySelectorAll('.so-row-check:checked');
        const bar = document.getElementById('BulkActionsBar');
        const countSpan = document.getElementById('SelectedCount');

        if (selected.length > 0) {
            bar.style.display = 'flex';
            countSpan.textContent = `${selected.length} items selected`;
        } else {
            bar.style.display = 'none';
        }
    }
};

document.addEventListener('DOMContentLoaded', () => SalesOrderSearch.init());
