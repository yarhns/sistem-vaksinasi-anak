<?php
/**
 * User Model.
 */

require_once __DIR__ . '/../config/database.php';

class User
{
    private mysqli $conn;
    private string $table = 'users';

    public function __construct()
    {
        global $db;
        $this->conn = $db->getConnection();
    }

    // ============================================================
    // AUTENTIKASI
    // ============================================================

    /**
     * Login: verifikasi username + password.
     * Return array user (tanpa password) jika sukses, false jika gagal.
     *
     * Catatan keamanan:
     * - Hanya pakai password_verify().
     * - Kalau hash lama (MD5) terdeteksi & cocok, otomatis upgrade ke bcrypt.
     * - Tidak ada fallback ke plaintext.
     */
    public function login(string $username, string $password)
    {
        $query = "SELECT * FROM {$this->table} WHERE username = ? AND is_active = 1 LIMIT 1";
        $stmt  = $this->conn->prepare($query);
        if (!$stmt) {
            error_log('User::login prepare failed: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows !== 1) {
            return false;
        }

        $user = $result->fetch_assoc();

        // Cek utama: bcrypt/argon
        if (password_verify($password, $user['password'])) {
            // Kalau hash butuh upgrade (misal cost factor lama), upgrade
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $this->upgradePasswordHash((int) $user['id'], $password);
            }
            $this->touchLastLogin((int) $user['id']);
            unset($user['password']); // jangan bawa hash ke luar model
            return $user;
        }

        // Kompatibilitas legacy: hash MD5 (32 char hex)
        if (preg_match('/^[a-f0-9]{32}$/i', $user['password'])
            && hash_equals(strtolower($user['password']), md5($password))
        ) {
            // Cocok → upgrade ke bcrypt permanen
            $this->upgradePasswordHash((int) $user['id'], $password);
            $this->touchLastLogin((int) $user['id']);
            unset($user['password']);
            return $user;
        }

        return false;
    }

    private function upgradePasswordHash(int $userId, string $plainPassword): void
    {
        $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET password = ? WHERE id = ?");
        $stmt->bind_param('si', $newHash, $userId);
        $stmt->execute();
        error_log("Password hash upgraded to bcrypt for user ID {$userId}");
    }

    private function touchLastLogin(int $userId): void
    {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
    }

    // ============================================================
    // READ
    // ============================================================

    public function getAllUsers(): array
    {
        $query = "SELECT id, username, nama_lengkap, role, email, no_hp, is_active, last_login, created_at
                  FROM {$this->table} ORDER BY created_at DESC";
        $result = $this->conn->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getUserById(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, username, nama_lengkap, role, email, no_hp, is_active, last_login, created_at
             FROM {$this->table} WHERE id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: null;
    }

    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->conn->prepare("SELECT id FROM {$this->table} WHERE username = ? AND id != ? LIMIT 1");
            $stmt->bind_param('si', $username, $excludeId);
        } else {
            $stmt = $this->conn->prepare("SELECT id FROM {$this->table} WHERE username = ? LIMIT 1");
            $stmt->bind_param('s', $username);
        }
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    // ============================================================
    // WRITE
    // ============================================================

    public function createUser(array $data): bool
    {
        $query = "INSERT INTO {$this->table}
                  (username, password, nama_lengkap, role, email, no_hp)
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $email  = $data['email']  ?? '';
        $noHp   = $data['no_hp']  ?? '';

        $stmt->bind_param(
            'ssssss',
            $data['username'],
            $hashedPassword,
            $data['nama_lengkap'],
            $data['role'],
            $email,
            $noHp
        );
        return $stmt->execute();
    }

    public function updateUser(int $id, array $data): bool
    {
        $query = "UPDATE {$this->table}
                  SET nama_lengkap = ?, email = ?, no_hp = ?, role = ?, is_active = ?
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $isActive = isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1;

        $stmt->bind_param(
            'ssssii',
            $data['nama_lengkap'],
            $data['email'],
            $data['no_hp'],
            $data['role'],
            $isActive,
            $id
        );
        return $stmt->execute();
    }

    public function deleteUser(int $id): bool
    {
        // Cegah admin menghapus dirinya sendiri
        if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $id) {
            return false;
        }

        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

        // Tambahkan fungsi ini di dalam class User

    public function verifyPassword(int $id, string $password): bool
    {
        $stmt = $this->conn->prepare("SELECT password FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? password_verify($password, $row['password']) : false;
    }

    public function updatePassword(int $id, string $newPassword): bool
    {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET password = ? WHERE id = ?");
        $stmt->bind_param('si', $newHash, $id);
        return $stmt->execute();
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        if (empty($email)) return false;
        
        if ($excludeId !== null) {
            $stmt = $this->conn->prepare("SELECT id FROM {$this->table} WHERE email = ? AND id != ? LIMIT 1");
            $stmt->bind_param('si', $email, $excludeId);
        } else {
            $stmt = $this->conn->prepare("SELECT id FROM {$this->table} WHERE email = ? LIMIT 1");
            $stmt->bind_param('s', $email);
        }
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    // ============================================================
    // STATISTIK
    // ============================================================

    public function countUsers(): int
    {
        $result = $this->conn->query("SELECT COUNT(*) AS total FROM {$this->table}");
        return $result ? (int) $result->fetch_assoc()['total'] : 0;
    }

    public function countActiveUsers(): int
    {
        // Jumlah petugas yang pernah melakukan vaksinasi
        $result = $this->conn->query(
            "SELECT COUNT(DISTINCT petugas_id) AS total FROM vaksinasi WHERE petugas_id IS NOT NULL"
        );
        return $result ? (int) $result->fetch_assoc()['total'] : 0;
    }
}