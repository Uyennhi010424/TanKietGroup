<?php
/**
 * Generic Admin CRUD Helper
 * Cung cấp các phương thức tái sử dụng cho tất cả admin pages,
 * loại bỏ code trùng lặp.
 *
 * Cách dùng:
 *   $crud = new AdminCRUD('services', $db, $adminRoutes['services']);
 *   $crud->handlePost();  // Xử lý POST (save/delete)
 *   $rows = $crud->getAll();
 *   $editing = $crud->getEditRow();
 */

require_once __DIR__ . '/admin_helpers.php';

class AdminCRUD
{
    protected PDO $db;
    protected string $table;
    protected string $route;
    protected string $idPrefix;
    protected array $columns = [];
    protected string $flashMessage = '';
    protected string $errorMessage = '';

    /**
     * @param string $table  Tên bảng trong database
     * @param PDO    $db     Kết nối database
     * @param string $route  URL route cho redirect sau save/delete
     * @param string $idPrefix  Prefix hiển thị cho ID (VD: 'SVC', 'BLG')
     */
    public function __construct(string $table, PDO $db, string $route, string $idPrefix = '')
    {
        $this->table = $table;
        $this->db = $db;
        $this->route = $route;
        $this->idPrefix = $idPrefix !== '' ? $idPrefix : strtoupper(substr($table, 0, 3));
    }

    /**
     * Lấy kết nối DB an toàn (trả về null nếu lỗi)
     */
    public static function connect(): ?PDO
    {
        try {
            return get_db_connection();
        } catch (Throwable $e) {
            error_log('AdminCRUD DB error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Xử lý POST request (save/delete). Gọi ở đầu trang admin.
     *
     * @param callable|null $onSave   Callback khi save: function($id, $db) nhận $id (0 = insert, >0 = update)
     * @param callable|null $onDelete Callback khi delete: function($id, $db)
     * @return void
     */
    public function handlePost(?callable $onSave = null, ?callable $onDelete = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        try {
            if (!csrf_validate((string)($_POST['csrf_token'] ?? ''))) {
                throw new RuntimeException('CSRF token không hợp lệ');
            }

            $action = $_POST['action'] ?? '';

            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id > 0) {
                    if ($onDelete) {
                        $onDelete($id, $this->db);
                    } else {
                        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
                        $stmt->execute(['id' => $id]);
                    }
                }
                header('Location: ' . with_query($this->route, ['msg' => 'Đã xóa thành công']));
                exit;
            }

            if ($action === 'save') {
                $id = (int)($_POST['id'] ?? 0);
                if ($onSave) {
                    $onSave($id, $this->db);
                }
                $msg = $id > 0 ? 'Đã cập nhật thành công' : 'Đã thêm thành công';
                header('Location: ' . with_query($this->route, ['msg' => $msg]));
                exit;
            }
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    /**
     * Kiểm tra slug trùng lặp
     *
     * @param string $slug    Slug cần kiểm tra
     * @param int    $excludeId  ID cần loại trừ (khi edit)
     * @param string $slugColumn Tên cột slug (mặc định: 'slug')
     * @return bool true nếu slug đã tồn tại
     */
    public function slugExists(string $slug, int $excludeId = 0, string $slugColumn = 'slug'): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE {$slugColumn} = :slug AND id <> :id"
        );
        $stmt->execute(['slug' => $slug, 'id' => $excludeId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Tạo slug tự động từ text, đảm bảo không trùng
     */
    public function uniqueSlug(string $text, int $excludeId = 0): string
    {
        $slug = make_slug($text);
        $original = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $excludeId)) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Lấy tất cả rows (có thể override query)
     */
    public function getAll(string $orderBy = 'id DESC'): array
    {
        return $this->db->query(
            "SELECT * FROM {$this->table} ORDER BY {$orderBy}"
        )->fetchAll();
    }

    /**
     * Lấy row theo ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /**
     * Lấy row đang edit (từ $_GET['edit'])
     */
    public function getEditRow(array $default = []): array
    {
        if (!isset($_GET['edit'])) {
            return $default;
        }

        $editId = (int)$_GET['edit'];
        if ($editId <= 0) {
            return $default;
        }

        $row = $this->getById($editId);
        return $row ?? $default;
    }

    /**
     * Xử lý upload ảnh
     *
     * @param string $fieldName   Tên field upload
     * @param string $subDir      Thư mục con trong uploads/
     * @param string $currentPath Ảnh hiện tại (nếu có)
     * @return string|null  Path ảnh mới hoặc null nếu không upload
     */
    public function handleImageUpload(string $fieldName, string $subDir, string $currentPath = ''): ?string
    {
        $uploaded = store_uploaded_image($fieldName, 'uploads/' . $subDir);
        return $uploaded !== null ? $uploaded : null;
    }

    /**
     * Render flash message và error message
     */
    public function renderMessages(): string
    {
        $flash = $_GET['msg'] ?? '';
        $html = '';

        if ($flash !== '') {
            $html .= '<div class="card" style="margin-bottom:16px;background:rgba(146,221,214,0.12);padding:12px 16px;color:#92ddd6;">';
            $html .= h($flash);
            $html .= '</div>';
        }

        if ($this->errorMessage !== '') {
            $html .= '<div class="card" style="margin-bottom:16px;background:rgba(255,120,120,0.12);padding:12px 16px;color:#ffb0b0;">';
            $html .= 'Lỗi: ' . h($this->errorMessage);
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Format ID hiển thị
     */
    public function formatId(int $id): string
    {
        return '#' . $this->idPrefix . '-' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Render nút edit/delete cho bảng
     */
    public function renderActionButtons(int $id, string $confirmMessage = 'Bạn có chắc muốn xóa?'): string
    {
        $csrfToken = csrf_token();
        $editUrl = h(with_query($this->route, ['edit' => $id]));

        return <<<HTML
<td style="text-align:right;display:flex;gap:8px;justify-content:flex-end;">
    <a class="btn-admin" style="text-decoration:none;" href="{$editUrl}">Sửa</a>
    <form method="post" onsubmit="return confirm('{$confirmMessage}');" style="margin:0;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="csrf_token" value="{$csrfToken}">
        <input type="hidden" name="id" value="{$id}">
        <button class="btn-admin" style="background:#ff8c8c;color:#3d1111;" type="submit">Xóa</button>
    </form>
</td>
HTML;
    }

    /**
     * Lấy error message hiện tại
     */
    public function getError(): string
    {
        return $this->errorMessage;
    }

    /**
     * Set error message
     */
    public function setError(string $message): void
    {
        $this->errorMessage = $message;
    }
}
