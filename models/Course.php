<?php
/**
 * Course Model
 * Truy vấn bảng courses + course_enrollments
 */
class Course
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Lấy tất cả khóa học */
    public function all(string $orderBy = 'id DESC'): array
    {
        return $this->db->query("SELECT * FROM courses ORDER BY {$orderBy}")->fetchAll();
    }

    /** Lấy khóa học đang active */
    public function active(int $limit = 0): array
    {
        $sql = "SELECT * FROM courses WHERE status = 1 ORDER BY sort_order ASC, id DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . $limit;
        }
        return $this->db->query($sql)->fetchAll();
    }

    /** Lấy khóa học theo slug */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM courses WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** Lấy khóa học theo ID */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM courses WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** Tăng lượt xem */
    public function incrementViews(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE courses SET views = views + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** Đăng ký khóa học */
    public function enroll(int $courseId, string $fullName, string $phone, string $email): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO course_enrollments (course_id, full_name, phone, email) VALUES (:cid, :name, :phone, :email)'
        );
        return $stmt->execute([
            'cid' => $courseId,
            'name' => $fullName,
            'phone' => $phone,
            'email' => $email,
        ]);
    }

    /** Lấy danh sách đăng ký của một khóa học */
    public function enrollments(int $courseId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM course_enrollments WHERE course_id = :cid ORDER BY created_at DESC'
        );
        $stmt->execute(['cid' => $courseId]);
        return $stmt->fetchAll();
    }

    /** Đếm tổng khóa học */
    public function count(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM courses')->fetchColumn();
    }
}
