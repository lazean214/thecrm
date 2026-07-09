import Alpine from 'alpinejs'
import collapse from '@alpinejs/collapse'
import intersect from '@alpinejs/intersect'
import flatpickr from 'flatpickr'

Alpine.plugin(collapse)
Alpine.plugin(intersect)

// Initialize flatpickr date range pickers
Alpine.data('dateRangePicker', (initialDateFrom = '', initialDateTo = '') => ({
    dateFrom: initialDateFrom,
    dateTo: initialDateTo,
    picker: null,

    init() {
        this.$nextTick(() => {
            const input = this.$refs.dateInput;
            if (input && !this.picker) {
                this.picker = flatpickr(input, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    defaultDate: [
                        initialDateFrom ? new Date(initialDateFrom) : null,
                        initialDateTo ? new Date(initialDateTo) : null
                    ].filter(Boolean),
                    onChange: (selectedDates, dateStr, instance) => {
                        if (selectedDates.length === 2) {
                            this.dateFrom = instance.formatDate(selectedDates[0], 'Y-m-d');
                            this.dateTo = instance.formatDate(selectedDates[1], 'Y-m-d');
                        } else if (selectedDates.length === 1) {
                            this.dateFrom = instance.formatDate(selectedDates[0], 'Y-m-d');
                            this.dateTo = '';
                        } else {
                            this.dateFrom = '';
                            this.dateTo = '';
                        }
                    }
                });
            }
        });
    }
}));

window.addEventListener('notify', event => {
    Toastify({
        text: event.detail.message,
        duration: 3000
    }).showToast()
})


