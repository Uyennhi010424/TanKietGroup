<?php
require_once __DIR__ . '/../includes/site.php';
require_once __DIR__ . '/../includes/security.php';

$site = site_settings();
$rows = [];
try {
    $rows = site_fetch_all('SELECT id, title, slug, location, salary, deadline, description FROM recruitments WHERE status = 1 ORDER BY created_at DESC');
} catch (Throwable $e) {
    $rows = [];
}

?>
<section class="vintage-hero" style="--hero-banner: url('/img/hero.jpg');">
    <div class="container reveal">
        <span class="vintage-hero__category">Tuyển dụng</span>
        <h1 class="vintage-hero__title">Cơ hội nghề nghiệp</h1>
        <p class="vintage-hero__lead">Gia nhập đội ngũ TanKiet Group — nơi bạn được phát triển và tỏa sáng.</p>
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
                <?php foreach ($rows as $r): ?>
                    <article class="card recruitment-card reveal" id="job-<?php echo (int)$r['id']; ?>">
                        <div class="recruitment-header">
                            <h3><?php echo htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="recruitment-meta">
                                <?php if ($r['location']): ?>
                                    <span class="recruitment-tag"><?php echo htmlspecialchars($r['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                                <?php if ($r['salary']): ?>
                                    <span class="recruitment-tag"><?php echo htmlspecialchars($r['salary'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                                <?php if ($r['deadline']): ?>
                                    <span class="recruitment-tag">Hạn nộp: <?php echo htmlspecialchars($r['deadline'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
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

                        <form class="apply-form" id="apply-form-<?php echo (int)$r['id']; ?>" style="display:none;" enctype="multipart/form-data" onsubmit="submitApply(event, <?php echo (int)$r['id']; ?>)">
                            <input type="hidden" name="job_id" value="<?php echo (int)$r['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="form-grid">
                                <div>
                                    <label for="apply-name-<?php echo (int)$r['id']; ?>" class="sr-only">Họ và tên</label>
                                    <input class="input" id="apply-name-<?php echo (int)$r['id']; ?>" type="text" name="apply_name" placeholder="Họ và tên *" required>
                                </div>
                                <div>
                                    <label for="apply-email-<?php echo (int)$r['id']; ?>" class="sr-only">Email</label>
                                    <input class="input" id="apply-email-<?php echo (int)$r['id']; ?>" type="email" name="apply_email" placeholder="Email *" required>
                                </div>
                            </div>
                            <div class="form-grid" style="margin-top:12px;">
                                <div>
                                    <label for="apply-phone-<?php echo (int)$r['id']; ?>" class="sr-only">Số điện thoại</label>
                                    <input class="input" id="apply-phone-<?php echo (int)$r['id']; ?>" type="tel" name="apply_phone" placeholder="Số điện thoại *" required pattern="[0-9]{10,11}" title="Vui lòng nhập 10-11 chữ số">
                                </div>
                                <div>
                                    <label for="apply-position-<?php echo (int)$r['id']; ?>" class="sr-only">Vị trí ứng tuyển</label>
                                    <input class="input" id="apply-position-<?php echo (int)$r['id']; ?>" type="text" name="apply_position" placeholder="Vị trí ứng tuyển" value="<?php echo htmlspecialchars($r['title'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div style="margin-top:12px;">
                                <label for="apply-message-<?php echo (int)$r['id']; ?>" class="sr-only">Giới thiệu</label>
                                <textarea class="textarea" id="apply-message-<?php echo (int)$r['id']; ?>" name="apply_message" placeholder="Giới thiệu ngắn về bản thân và lý do bạn quan tâm" rows="3"></textarea>
                            </div>
                            <div style="margin-top:12px;">
                                <label for="apply-cv-<?php echo (int)$r['id']; ?>" style="display:block;margin-bottom:6px;font-size:0.9rem;color:var(--muted);">Đính kèm CV (PDF, DOC, DOCX, JPG, PNG - tối đa 10MB)</label>
                                <input class="input" id="apply-cv-<?php echo (int)$r['id']; ?>" type="file" name="cv_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" style="padding:8px;">
                            </div>
                            <button class="btn btn-primary" type="submit" style="margin-top:16px;">Gửi đơn ứng tuyển</button>
                            <div class="apply-message" id="apply-msg-<?php echo (int)$r['id']; ?>" style="display:none;"></div>
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

    // Client-side phone validation
    var phoneInput = form.querySelector('input[name="apply_phone"]');
    var phoneDigits = phoneInput.value.replace(/[^0-9]/g, '');
    if (phoneDigits.length < 10) {
        msgDiv.style.display = 'block';
        msgDiv.style.background = 'rgba(231,76,60,0.15)';
        msgDiv.style.color = '#e74c3c';
        msgDiv.textContent = 'Số điện thoại phải có ít nhất 10 chữ số';
        phoneInput.focus();
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Đang gửi...';

    var formData = new FormData(form);

    fetch('/api/apply_job.php', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        msgDiv.style.display = 'block';
        if (result.success) {
            msgDiv.style.background = 'rgba(46,204,113,0.15)';
            msgDiv.style.color = '#2ecc71';
            msgDiv.textContent = result.message;
            form.reset();
        } else {
            msgDiv.style.background = 'rgba(231,76,60,0.15)';
            msgDiv.style.color = '#e74c3c';
            msgDiv.textContent = result.message;
        }
        btn.disabled = false;
        btn.textContent = 'Gửi đơn ứng tuyển';
        setTimeout(function() { msgDiv.style.display = 'none'; }, 5000);
    })
    .catch(function(error) {
        msgDiv.style.display = 'block';
        msgDiv.style.background = 'rgba(231,76,60,0.15)';
        msgDiv.style.color = '#e74c3c';
        msgDiv.textContent = 'Đã xảy ra lỗi, vui lòng thử lại sau';
        btn.disabled = false;
        btn.textContent = 'Gửi đơn ứng tuyển';
    });
}
</script>
