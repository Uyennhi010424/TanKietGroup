<?php
/**
 * Blog Model
 * Truy vấn bảng blog_posts + blog_categories
 */
class Blog
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /** Lấy tất cả bài viết (có join category, author) */
    public function all(string $orderBy = 'bp.id DESC'): array
    {
        return $this->db->query(
            "SELECT bp.*, COALESCE(bc.name, '-') AS category_name,
                    COALESCE(u.full_name, 'Admin') AS author_name
             FROM blog_posts bp
             LEFT JOIN blog_categories bc ON bc.id = bp.category_id
             LEFT JOIN users u ON u.id = bp.author_id
             ORDER BY {$orderBy}"
        )->fetchAll();
    }

    /** Lấy bài viết đã published */
    public function published(int $limit = 0): array
    {
        $sql = "SELECT bp.*, COALESCE(bc.name, '-') AS category_name
                FROM blog_posts bp
                LEFT JOIN blog_categories bc ON bc.id = bp.category_id
                WHERE bp.status = 'published'
                ORDER BY bp.published_at DESC, bp.id DESC";
        if ($limit > 0) {
            $sql .= " LIMIT " . $limit;
        }
        return $this->db->query($sql)->fetchAll();
    }

    /** Lấy bài viết nổi bật */
    public function featured(int $limit = 3): array
    {
        $stmt = $this->db->prepare(
            "SELECT bp.*, bc.name AS category_name
             FROM blog_posts bp LEFT JOIN blog_categories bc ON bc.id = bp.category_id
             WHERE bp.is_featured = 1 AND bp.status = 'published'
             ORDER BY bp.published_at DESC LIMIT :limit"
        );
        $stmt->execute(['limit' => $limit]);
        return $stmt->fetchAll();
    }

    /** Lấy bài viết theo slug */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT bp.*, bc.name AS category_name, u.full_name AS author_name
             FROM blog_posts bp
             LEFT JOIN blog_categories bc ON bc.id = bp.category_id
             LEFT JOIN users u ON u.id = bp.author_id
             WHERE bp.slug = :slug LIMIT 1"
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** Lấy bài viết theo ID */
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM blog_posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** Tăng lượt xem */
    public function incrementViews(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE blog_posts SET views = views + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /** Lấy danh sách categories */
    public function categories(): array
    {
        return $this->db->query(
            'SELECT * FROM blog_categories ORDER BY sort_order ASC, name ASC'
        )->fetchAll();
    }

    /** Đếm tổng bài viết */
    public function count(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
    }

    /** Đếm bài published */
    public function countPublished(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn();
    }
}
