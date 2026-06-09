<?php
require_once __DIR__ . '/../includes/site.php';

$rows = [];
try {
    $rows = site_fetch_all('SELECT id, title, slug, location, salary, deadline, description FROM recruitments WHERE status = 1 ORDER BY created_at DESC');
} catch (Throwable $e) {
    $rows = [];
}

?>
<section class="hero">
    <div class="container reveal">
        <span class="tag">Tuyển dụng</span>
        <h1>Tin tuyển dụng</h1>
        <p class="lead">Xem các vị trí đang tuyển và ứng tuyển ngay.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (!$rows): ?>
            <div class="card">
                <h3>Hiện chưa có tin tuyển dụng</h3>
                <p class="muted">Vui lòng quay lại sau hoặc liên hệ qua trang Liên hệ.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-2">
                <?php foreach ($rows as $r): ?>
                    <article class="card">
                        <h3><?php echo htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <ul class="contact-list">
                            <li><strong>Địa điểm:</strong> <?php echo htmlspecialchars($r['location'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Lương:</strong> <?php echo htmlspecialchars($r['salary'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></li>
                            <li><strong>Hạn nộp:</strong> <?php echo htmlspecialchars($r['deadline'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></li>
                        </ul>
                        <div><?php echo $r['description'] ? sanitize_html($r['description']) : '<p>Chưa có mô tả.</p>'; ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
