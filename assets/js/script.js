/**
 * Global JS — Sistem Vaksinasi Anak.
 * Gabungan: init UI (Fase 3) + modul VaccineSystem (utilitas).
 */
(function ($) {
    'use strict';

    // ============================================================
    // HELPERS GLOBAL
    // ============================================================

    window.showAlert = function (type, message) {
        const $alert = $('<div>', {
            class: 'alert alert-' + type + ' alert-dismissible fade show auto-dismiss',
            role: 'alert'
        });
        $alert.append(document.createTextNode(' ' + message + ' '));
        $alert.append(
            $('<button>', {
                type: 'button',
                class: 'btn-close',
                'data-bs-dismiss': 'alert',
                'aria-label': 'Close'
            })
        );
        const $target = $('.main-content').first();
        ($target.length ? $target : $('body')).prepend($alert);
        setTimeout(function () { $alert.alert('close'); }, 5000);
    };

    window.formatDate = function (dateString, format) {
        format = format || 'dd/mm/yyyy';
        const d = new Date(dateString);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        if (format === 'dd/mm/yyyy') return day + '/' + month + '/' + year;
        if (format === 'yyyy-mm-dd') return year + '-' + month + '-' + day;
        return dateString;
    };

    window.formatNumber = function (number) {
        return new Intl.NumberFormat('id-ID').format(number);
    };

    window.showNotification = function (message, type) {
        type = type || 'info';
        const $n = $('<div>', {
            class: 'alert alert-' + type + ' alert-dismissible fade show position-fixed',
            css: { top: 20, right: 20, zIndex: 9999, minWidth: 300 }
        });
        $n.text(message);
        $n.append('<button type="button" class="btn-close" data-bs-dismiss="alert"></button>');
        $('body').append($n);
        setTimeout(function () { $n.remove(); }, 5000);
    };

    window.copyToClipboard = function (text) {
        navigator.clipboard.writeText(text).then(function () {
            showNotification('Teks berhasil disalin', 'success');
        }).catch(function () {
            showNotification('Gagal menyalin teks', 'danger');
        });
    };

    // ============================================================
    // VACCINE SYSTEM (utilitas domain)
    // ============================================================

    window.VaccineSystem = {
        calculateAge: function (birthDate, referenceDate) {
            referenceDate = referenceDate || new Date();
            const birth = new Date(birthDate);
            const reference = new Date(referenceDate);

            let years = reference.getFullYear() - birth.getFullYear();
            let months = reference.getMonth() - birth.getMonth();

            if (months < 0) { years--; months += 12; }

            if (reference.getDate() < birth.getDate()) {
                months--;
                if (months < 0) { years--; months += 12; }
            }

            return {
                years: years,
                months: months,
                totalMonths: years * 12 + months
            };
        },

        formatDateID: function (dateString) {
            const date = new Date(dateString);
            const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            return days[date.getDay()] + ', ' + date.getDate() + ' '
                 + months[date.getMonth()] + ' ' + date.getFullYear();
        },

        formatTime: function (dateString) {
            const d = new Date(dateString);
            return String(d.getHours()).padStart(2, '0') + ':'
                 + String(d.getMinutes()).padStart(2, '0');
        },

        generateVaccineSchedule: function (birthDate) {
            const birth = new Date(birthDate);
            const schedule = [];
            const basicSchedule = [
                { name: 'Hepatitis B', age: 0, code: 'HB0' },
                { name: 'BCG', age: 1, code: 'BCG' },
                { name: 'Polio 1', age: 2, code: 'POL1' },
                { name: 'DPT-HB-Hib 1', age: 2, code: 'DPT1' },
                { name: 'Polio 2', age: 3, code: 'POL2' },
                { name: 'DPT-HB-Hib 2', age: 3, code: 'DPT2' },
                { name: 'Polio 3', age: 4, code: 'POL3' },
                { name: 'DPT-HB-Hib 3', age: 4, code: 'DPT3' },
                { name: 'IPV', age: 4, code: 'IPV' },
                { name: 'Campak', age: 9, code: 'CAM' }
            ];
            basicSchedule.forEach(function (item) {
                const scheduleDate = new Date(birth);
                scheduleDate.setMonth(birth.getMonth() + item.age);
                schedule.push({
                    name: item.name,
                    code: item.code,
                    age: item.age,
                    scheduledDate: scheduleDate.toISOString().split('T')[0],
                    status: 'pending'
                });
            });
            return schedule;
        },

        isVaccineOverdue: function (scheduledDate, currentDate) {
            const scheduled = new Date(scheduledDate);
            const current = currentDate ? new Date(currentDate) : new Date();
            return current > scheduled;
        },

        validateNIK: function (nik) {
            if (!nik) return false;
            return /^\d{16}$/.test(nik.toString());
        },

        validatePhone: function (phone) {
            if (!phone) return true;
            return /^[0-9+\-\s]{10,15}$/.test(phone.toString());
        },

        validateEmail: function (email) {
            if (!email) return true;
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        exportToCSV: function (data, filename) {
            if (!data || !data.length) {
                showAlert('warning', 'Tidak ada data untuk di-export');
                return;
            }
            const headers = Object.keys(data[0]);
            const csvContent = [
                headers.join(','),
                ...data.map(function (row) {
                    return headers.map(function (h) {
                        const cell = row[h];
                        return typeof cell === 'string' && cell.indexOf(',') > -1
                            ? '"' + cell + '"' : cell;
                    }).join(',');
                })
            ].join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', filename || 'export.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },

        printElement: function (elementId) {
            const element = document.getElementById(elementId);
            if (!element) {
                showAlert('warning', 'Element tidak ditemukan');
                return;
            }
            const printWindow = window.open('', '_blank');
            printWindow.document.write(
                '<html><head><title>Print</title>' +
                '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">' +
                '<style>@media print{@page{margin:0}body{margin:1cm}}.no-print{display:none!important}</style>' +
                '</head><body>' + element.innerHTML +
                '<script>window.onload=function(){window.print();window.onafterprint=function(){window.close()}}<\/script>' +
                '</body></html>'
            );
            printWindow.document.close();
        }
    };

    // ============================================================
    // FUNGSI FORM (dipakai di halaman create/edit)
    // ============================================================

    window.validateForm = function (formId) {
        const form = document.getElementById(formId);
        if (!form) return false;
        let isValid = true;
        form.querySelectorAll('[required]').forEach(function (input) {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('is-invalid');
                if (!input.nextElementSibling || !input.nextElementSibling.classList.contains('invalid-feedback')) {
                    const div = document.createElement('div');
                    div.className = 'invalid-feedback';
                    div.textContent = 'Field ini wajib diisi';
                    input.parentNode.appendChild(div);
                }
            } else {
                input.classList.remove('is-invalid');
                const next = input.nextElementSibling;
                if (next && next.classList.contains('invalid-feedback')) next.remove();
            }
        });
        return isValid;
    };

    window.previewImage = function (input, previewId) {
        const preview = document.getElementById(previewId);
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    };

    window.exportTableToExcel = function (tableId, filename) {
        const table = document.getElementById(tableId);
        if (!table) {
            showAlert('warning', 'Tabel tidak ditemukan');
            return;
        }
        const blob = new Blob([table.outerHTML], { type: 'application/vnd.ms-excel' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename || 'data.xls';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    };

    // ============================================================
    // INIT UI (dari Fase 3)
    // ============================================================

    function initSidebar() {
        const $sidebar = $('#sidebar');
        if (!$sidebar.length) return;

        $('#closeSidebar').on('click', function () {
            $sidebar.removeClass('active');
        });

        $sidebar.find('.nav-link').on('click', function () {
            if ($(window).width() <= 992) $sidebar.removeClass('active');
        });
    }

    function initDataTables() {
        if (!$.fn.DataTable) return;
        const $tables = $('.datatable');
        if (!$tables.length) return;
        $tables.DataTable({
            language: { url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/id.json' },
            responsive: true,
            autoWidth: false,
            pageLength: 25,
            order: []
        });
    }

    function initSelect2() {
        if (!$.fn.select2) return;
        const $els = $('.select2');
        if (!$els.length) return;
        $els.select2({ theme: 'bootstrap-5', width: '100%', placeholder: 'Pilih...' });
    }

    function initDatepicker() {
        if (typeof flatpickr === 'undefined') return;
        const $date = $('.datepicker');
        if ($date.length) $date.flatpickr({ dateFormat: 'Y-m-d', locale: 'id' });
        const $dt = $('.datetimepicker');
        if ($dt.length) $dt.flatpickr({ enableTime: true, dateFormat: 'Y-m-d H:i', locale: 'id' });
    }

    function initBootstrapWidgets() {
        if ($.fn.tooltip) $('[data-bs-toggle="tooltip"]').tooltip();
        if ($.fn.popover) $('[data-bs-toggle="popover"]').popover();
    }

    function initAlerts() {
        setTimeout(function () {
            $('.alert.auto-dismiss').alert('close');
        }, 5000);
    }

    function initForms() {
        // Konfirmasi via data-confirm
        $(document).on('click', 'a[data-confirm], button[data-confirm]', function (e) {
            if (!confirm($(this).data('confirm'))) {
                e.preventDefault();
                return false;
            }
        });

        // Konfirmasi logout
        // $(document).on('click', 'a[href*="action=logout"]', function (e) {
        //     if (!confirm('Apakah Anda yakin ingin logout?')) {
        //         e.preventDefault();
        //         return false;
        //     }
        // });

        // Auto-format NIK
        $(document).on('input', '#nik', function () {
            this.value = this.value.replace(/\D/g, '').substring(0, 16);
        });

        // Auto-format phone
        $(document).on('input', '.phone-input', function () {
            const v = this.value.replace(/\D/g, '');
            if (v.length > 0) {
                let f = v;
                if (v.length > 4) f = v.substring(0, 4) + '-' + v.substring(4);
                if (v.length > 8) f = f.substring(0, 9) + '-' + f.substring(9);
                this.value = f.substring(0, 14);
            }
        });

        // Auto-hitung usia saat vaksinasi
        $(document).on('change', '.calculate-age, #tanggal_lahir', function () {
            const birthDate = $('#tanggal_lahir').val();
            const vaxDate = $('#tanggal_vaksinasi').val() || new Date().toISOString().split('T')[0];
            if (birthDate && vaxDate && window.VaccineSystem) {
                const age = window.VaccineSystem.calculateAge(birthDate, vaxDate);
                $('#usia_saat_vaksinasi_bulan').val(age.totalMonths);
                const text = age.years > 0
                    ? age.years + ' tahun ' + age.months + ' bulan'
                    : age.months + ' bulan';
                $('#usia_text').text(text);
            }
        });

        // Generate batch number
        $(document).on('click', '.generate-batch', function () {
            const d = new Date();
            const yy = d.getFullYear().toString().substr(-2);
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const rnd = String(Math.floor(Math.random() * 1000)).padStart(3, '0');
            $('#batch_number').val('B' + yy + mm + rnd);
        });

        // Quick search (client-side)
        $(document).on('keyup', '#searchInput', function () {
            const v = this.value.toLowerCase();
            $('.searchable-row').each(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(v) > -1);
            });
        });
    }

    function initPrint() {
        $(document).on('click', '.btn-print', function () {
            window.print();
        });
    }

    // ============================================================
    // READY
    // ============================================================

    $(function () {
        initSidebar();
        initDataTables();
        initSelect2();
        initDatepicker();
        initBootstrapWidgets();
        initAlerts();
        initForms();
        initPrint();
    });

})(jQuery);