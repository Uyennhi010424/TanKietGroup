<?php
/**
 * Project Model
 * Truy vấn bảng projects + industries + services
 */
class Project
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Lấy tất cả projects */
    public function all(string $orderBy = 'p.id DESC'): array
    {
        return $this->db->query(
            "SELECT p.*, COALESCE(i.name, '-') AS industry_name,
                    COALESCE(s.title, '-') AS service_name
             FROM projects p
             LEFT JOIN industries i ON i.id = p.industry_id
             LEFT JOIN services s ON s.id = p.service_id
             ORDER BY {$orderBy}"
        )->fetchAll();
    }

    /** Lấy projects đang active */
    public function active(int $limit = 0): array
    {
        $sql = "SELECT p.*, i.name AS industry_name
                FROM projects p LEFT JOIN industries i ON i.id = p.industry_id
                WHERE p.status = 1
                ORDER BY p.id DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . $limit;
        }
        return $this->db->query($sql)->fetchAll();
    }

    /** Lấy project theo slug */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, i.name AS industry_name, s.title AS service_name
             FROM projects p
             LEFT JOIN industries i ON i.id = p.industry_id
             LEFT JOIN services s ON s.id = p.service_id
             WHERE p.slug = :slug LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** Lấy project theo ID */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM projects WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** Lấy projects theo industry */
    public function byIndustry(int $industryId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM projects WHERE industry_id = :iid AND status = 1 ORDER BY id DESC"
        );
        $stmt->execute(['iid' => $industryId]);
        return $stmt->fetchAll();
    }

    /** Đếm tổng projects */
    public function count(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM projects')->fetchColumn();
    }
}
