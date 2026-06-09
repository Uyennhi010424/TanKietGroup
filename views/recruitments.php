<?php
require_once __DIR__ . '/../includes/site.php';

$site = site_settings();
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
        <h1>Cơ hội nghề nghiệp</h1>
        <p class="lead">Gia nhập đội ngũ TanKiet Group — nơi bạn được phát triển và tỏa sáng.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (!$rows): ?>
            <div class="card" style="text-align:center;padding:40px;">
                <h3>Hiện chưa có vị trí tuyển dụng</h3>
                <p class="muted">Vui lòng quay lại sau hoặc gửi CV trực tiếp qua email.</p>
                <a class="btn btn-primary" href="/lien-he" style="margin-top:16px;">Liên hệ với chúng tôi</a>
            </div>
        <?php else: ?>
            <div class="recruitment-list">
                <?php foreach ($rows as $i => $r): ?>
                    <article class="card recruitment-card reveal" id="job-<?php echo (int)$r['id']; ?>">
                        <div class="recruitment-header">
                            <div>
                                <h3><?php echo htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <ul class="contact-list" style="margin-top:8px;">
                                    <li>📍 <?php echo htmlspecialchars($r['location'] ?: 'Không xác định', ENT_QUOTES, 'UTF-8'); ?></li>
                                    <li>💰 <?php echo htmlspecialchars($r['salary'] ?: 'Thỏa thuận', ENT_QUOTES, 'UTF-8'); ?></li>
                                    <?php if ($r['deadline']): ?>
                                        <li>📅 Hạn nộp: <?php echo htmlspecialchars($r['deadline'], ENT_QUOTES, 'UTF-8'); ?></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <?php if ($r['description']): ?>
                            <div class="recruitment-desc">
                                <?php echo sanitize_html($r['description']); ?>
                            </div>
                        <?php endif; ?>

                        <button class="btn btn-primary" type="button" onclick="toggleApplyForm(<?php echo (int)$r['id']; ?>)">
                            Ứng tuyển ngay
                        </button>

                        <form class="apply-form" id="apply-form-<?php echo (int)$r['id']; ?>" style="display:none;margin-top:16px;" onsubmit="submitApply(event, <?php echo (int)$r['id']; ?>)">
                            <input type="hidden" name="job_id" value="<?php echo (int)$r['id']; ?>">
                            <input type="hidden" name="job_title" value="<?php echo htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="form-grid">
                                <input class="input" type="text" name="apply_name" placeholder="Họ và tên" required>
                                <input class="input" type="email" name="apply_email" placeholder="Email" required>
                            </div>
                            <p></p>
                            <div class="form-grid">
                                <input class="input" type="tel" name="apply_phone" placeholder="Số điện thoại" required>
                                <input class="input" type="text" name="apply_position" placeholder="Vị trí ứng tuyển" value="<?php echo htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <p></p>
                            <textarea class="textarea" name="apply_message" placeholder="Giới thiệu ngắn về bản thân và lý do bạn quan tâm đến vị trí này" rows="4"></textarea>
                            <p></p>
                            <button class="btn btn-primary" type="submit">Gửi đơn ứng tuyển</button>
                            <div class="apply-message" id="apply-msg-<?php echo (int)$r['id']; ?>" style="margin-top:12px;display:none;padding:12px;border-radius:8px;"></div>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
function toggleApplyForm(id) {
    var form = document.getElementById('apply-form-' + id);
    if (form) {
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
        if (form.style.display === 'block') {
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
}

function submitApply(e, id) {
    e.preventDefault();
    var form = document.getElementById('apply-form-' + id);
    var msgDiv = document.getElementById('apply-msg-' + id);
    var btn = form.querySelector('button[type="submit"]');

    btn.disabled = true;
    btn.textContent = 'Đang gửi...';

    // Show success message (no backend endpoint for this yet)
    setTimeout(function() {
        msgDiv.style.display = 'block';
        msgDiv.style.background = 'rgba(46,204,113,0.15)';
        msgDiv.style.color = '#2ecc71';
        msgDiv.textContent = 'Đã gửi đơn ứng tuyển thành công! Chúng tôi sẽ liên hệ với bạn sớm.';
        form.reset();
        btn.disabled = false;
        btn.textContent = 'Gửi đơn ứng tuyển';
        setTimeout(function() { msgDiv.style.display = 'none'; }, 5000);
    }, 800);
}
</script>
