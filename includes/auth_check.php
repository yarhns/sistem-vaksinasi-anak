<?php
/**
 * @deprecated File ini dipertahankan untuk kompatibilitas.
 * Gunakan includes/auth.php dengan requireLogin() / requireRole().
 */

require_once __DIR__ . '/auth.php';

// Perilaku lama: cek login
requireLogin();