            </div> <!-- /.main-content -->
        </div> <!-- /.row -->
    </div> <!-- /.container-fluid -->

    <!-- ============================================================ -->
    <!-- LIBRARY JAVASCRIPT -->
    <!-- ============================================================ -->
    
    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Datepicker -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- ============================================================ -->
    <!-- KONFIGURASI GLOBAL & CUSTOM JS -->
    <!-- ============================================================ -->

    <script>
        // Konfigurasi global (dipakai JS di assets/js/script.js juga)
        window.APP = {
            url: <?= json_encode(APP_URL) ?>,
            name: <?= json_encode(APP_NAME) ?>
        };
    </script>

    <!-- Custom JS -->
    <script src="<?= e(APP_URL) ?>/assets/js/script.js?v=2.0"></script>

    <!-- ============================================================ -->
    <!-- CUSTOM SCRIPT (Sweetalert Logout) -->
    <!-- ============================================================ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logoutLinks = document.querySelectorAll('a[href*="action=logout"]');
            
            logoutLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault(); // Tahan redirect bawaan link
                    
                    Swal.fire({
                        title: 'Konfirmasi Logout',
                        text: "Apakah Anda yakin ingin keluar dari sistem?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#4F46E5', // Warna Indigo sesuai tema
                        cancelButtonColor: '#EF4444', // Warna Merah
                        confirmButtonText: '<i class="fas fa-sign-out-alt me-1"></i> Ya, Keluar',
                        cancelButtonText: 'Batal',
                        reverseButtons: true,
                        customClass: {
                            popup: 'border-radius-16'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = this.href; // Lanjutkan logout
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>