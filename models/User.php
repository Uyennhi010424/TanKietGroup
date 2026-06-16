<?php
/**
 * User Model
 * Truy vấn bảng users
 */
class User
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Lấy tất cả users */
    public function all(string $orderBy = 'id DESC'): array
    {
        return $this->db->query("SELECT * FROM users ORDER BY {$orderBy}")->fetchAll();
    }

    /** Lấy user theo ID */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** Lấy user theo username hoặc email */
    public function findByLogin(string $login): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE (LOWER(username) = LOWER(:u1) OR LOWER(email) = LOWER(:u2)) LIMIT 1'
        );
        $stmt->execute(['u1' => $login, 'u2' => $login]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** Tạo user mới */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, password, full_name, email, phone, role, status)
             VALUES (:username, :password, :full_name, :email, :phone, :role, :status)'
        );
        $stmt->execute([
            'username'  => $data['username'],
            'password'  => password_hash($data['password'], PASSWORD_DEFAULT),
            'full_name' => $data['full_name'] ?? '',
            'email'     => $data['email'],
            'phone'     => $data['phone'] ?? '',
            'role'      => $data['role'] ?? 'user',
            'status'    => $data['status'] ?? 1,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /** Cập nhật user */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        $allowed = ['username', 'full_name', 'email', 'phone', 'role', 'status'];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $params[$field] = $data[$field];
            }
        }

        if (isset($data['password']) && $data['password'] !== '') {
            $fields[] = 'password = :password';
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /** Xóa user */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /** Đếm tổng users */
    public function count(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    /** Đếm users theo role */
    public function countByRole(string $role): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
        $stmt->execute(['role' => $role]);
        return (int)$stmt->fetchColumn();
    }
}
