<?php
/**
 * Service Model
 * Truy vấn bảng services + industries
 */
class Service
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Lấy tất cả services (có join industry) */
    public function all(string $orderBy = 's.id DESC'): array
    {
        return $this->db->query(
            "SELECT s.*, COALESCE(i.name, '-') AS industry_name
             FROM services s LEFT JOIN industries i ON i.id = s.industry_id
             ORDER BY {$orderBy}"
        )->fetchAll();
    }

    /** Lấy services đang active */
    public function active(): array
    {
        return $this->db->query(
            "SELECT s.*, COALESCE(i.name, '-') AS industry_name
             FROM services s LEFT JOIN industries i ON i.id = s.industry_id
             WHERE s.status = 1 ORDER BY s.sort_order ASC, s.id DESC"
        )->fetchAll();
    }

    /** Lấy service theo slug */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, i.name AS industry_name, i.slug AS industry_slug
             FROM services s LEFT JOIN industries i ON i.id = s.industry_id
             WHERE s.slug = :slug LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** Lấy service theo ID */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM services WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** Lấy services theo industry slug */
    public function byIndustry(string $industrySlug): array
    {
        $stmt = $this->db->prepare(
            "SELECT s.*, i.name AS industry_name
             FROM services s JOIN industries i ON i.id = s.industry_id
             WHERE i.slug = :slug AND s.status = 1
             ORDER BY s.sort_order ASC"
        );
        $stmt->execute(['slug' => $industrySlug]);
        return $stmt->fetchAll();
    }

    /** Lấy services theo service_type */
    public function byType(string $type): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM services WHERE service_type = :type AND status = 1
             ORDER BY sort_order ASC"
        );
        $stmt->execute(['type' => $type]);
        return $stmt->fetchAll();
    }

    /** Đếm tổng services */
    public function count(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM services')->fetchColumn();
    }
}
