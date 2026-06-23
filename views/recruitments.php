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

function rc_h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<!-- Hero -->
<section class="hero">
	<div class="container reveal">
		<span class="tag">Tuyển dụng</span>
		<h1>Cơ hội nghề nghiệp</h1>
		<p class="lead">Gia nhập đội ngũ TanKiet Group — nơi bạn được phát triển và tỏa sáng.</p>
	</div>
</section>

<!-- Job List -->
<section class="section">
	<div class="container">
		<?php if (!$rows): ?>
			<div class="rc-empty reveal">
				<h3>Hiện chưa có vị trí tuyển dụng</h3>
				<p>Vui lòng quay lại sau hoặc gửi CV trực tiếp qua email.</p>
				<a class="btn btn-primary" href="/lien-he">Liên hệ với chúng tôi</a>
			</div>
		<?php else: ?>
			<div class="rc-list">
				<?php foreach ($rows as $r): ?>
					<article class="rc-card reveal" id="job-<?php echo (int)$r['id']; ?>">
						<!-- Header: title + tags + toggle button -->
						<div class="rc-card__header" onclick="toggleJob(<?php echo (int)$r['id']; ?>)">
							<div class="rc-card__header-left">
								<h3 class="rc-card__title"><?php echo rc_h($r['title']); ?></h3>
								<div class="rc-card__tags">
									<?php if ($r['location']): ?>
										<span class="rc-tag rc-tag--location"><?php echo rc_h($r['location']); ?></span>
									<?php endif; ?>
									<?php if ($r['salary']): ?>
										<span class="rc-tag rc-tag--salary"><?php echo rc_h($r['salary']); ?></span>
									<?php endif; ?>
									<?php if ($r['deadline']): ?>
										<span class="rc-tag rc-tag--deadline">Hạn: <?php echo rc_h($r['deadline']); ?></span>
									<?php endif; ?>
								</div>
							</div>
							<div class="rc-card__toggle" id="toggle-icon-<?php echo (int)$r['id']; ?>">
								<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
							</div>
						</div>

						<!-- Collapsible content -->
						<div class="rc-card__body" id="job-body-<?php echo (int)$r['id']; ?>" style="display:none;">
							<!-- Description -->
							<?php if ($r['description']): ?>
								<div class="rc-card__desc">
									<?php
										$raw = trim($r['description']);
										$lines = preg_split('/\r\n|\r|\n/', $raw);
										$out = [];
										foreach ($lines as $line) {
											$line = trim($line);
											if ($line === '') continue;
											$isTitle = false;
											if (preg_match('/^[A-ZÀ-ẠẢÃÁÂẦẤẬẨẪĂẰẮẶẲẴÈẸẺẼÉÊỀẾỆỂỄÌÍỊỈĨÒỌỎÕÓÔỒỐỘỔỖƠỜỚỢỞỠÙỤỦŨÚỪỨỰỬỮỲỴỶỸĐ\s\.\&\(\)\-:]+$/u', $line) && mb_strlen($line) > 3) {
												$isTitle = true;
											}
											elseif (preg_match('/^(Mô tả|Yêu cầu|Quyền lợi|Phúc lợi|Thông tin|Chế độ)/ui', $line)) {
												$isTitle = true;
											}
											if ($isTitle) {
												$out[] = '<span class="rc-cap">' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</span>';
											} else {
												$out[] = '<span class="rc-line"><span class="rc-dot"></span>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</span>';
											}
										}
										echo implode("\n", $out);
									?>
								</div>
							<?php endif; ?>

							<!-- Actions -->
							<div class="rc-card__actions">
								<button class="rc-btn" type="button" onclick="toggleApplyForm(<?php echo (int)$r['id']; ?>)">
									Ứng tuyển ngay
								</button>
							</div>

							<!-- Apply Form (hidden) -->
							<form class="rc-form" id="apply-form-<?php echo (int)$r['id']; ?>" style="display:none;" enctype="multipart/form-data" onsubmit="submitApply(event, <?php echo (int)$r['id']; ?>)">
								<input type="hidden" name="job_id" value="<?php echo (int)$r['id']; ?>">
								<input type="hidden" name="csrf_token" value="<?php echo rc_h(csrf_token()); ?>">

								<h4 class="rc-form__title">Đơn ứng tuyển</h4>

								<div class="rc-form__grid">
									<div class="rc-form__field">
										<label>Họ và tên <span class="rc-required">*</span></label>
										<input type="text" name="apply_name" placeholder="Nguyễn Văn A" required>
									</div>
									<div class="rc-form__field">
										<label>Email <span class="rc-required">*</span></label>
										<input type="email" name="apply_email" placeholder="email@example.com" required>
									</div>
									<div class="rc-form__field">
										<label>Số điện thoại <span class="rc-required">*</span></label>
										<input type="tel" name="apply_phone" placeholder="0901234567" required pattern="[0-9]{10,11}">
									</div>
									<div class="rc-form__field">
										<label>Vị trí ứng tuyển</label>
										<input type="text" name="apply_position" value="<?php echo rc_h($r['title']); ?>">
									</div>
								</div>

								<div class="rc-form__field rc-form__field--full">
									<label>Giới thiệu bản thân</label>
									<textarea name="apply_message" rows="3" placeholder="Lý do bạn quan tâm đến vị trí này..."></textarea>
								</div>

								<div class="rc-form__field rc-form__field--full">
									<label>Đính kèm CV <span class="rc-hint">(PDF, DOC, DOCX, JPG, PNG — tối đa 10MB)</span></label>
									<input type="file" name="cv_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
								</div>

								<div class="rc-form__actions">
									<button class="rc-btn rc-btn--submit" type="submit">Gửi đơn ứng tuyển</button>
									<button class="rc-btn rc-btn--cancel" type="button" onclick="toggleApplyForm(<?php echo (int)$r['id']; ?>)">Hủy</button>
								</div>

								<div class="rc-form__msg" id="apply-msg-<?php echo (int)$r['id']; ?>" style="display:none;"></div>
							</form>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<script>
function toggleJob(id) {
    var body = document.getElementById('job-body-' + id);
    var icon = document.getElementById('toggle-icon-' + id);
    if (!body) return;
    var isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : 'block';
    if (icon) icon.classList.toggle('rc-card__toggle--open', !isOpen);
}

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

    var phoneInput = form.querySelector('input[name="apply_phone"]');
    var phoneDigits = phoneInput.value.replace(/[^0-9]/g, '');
    if (phoneDigits.length < 10) {
        msgDiv.style.display = 'block';
        msgDiv.className = 'rc-form__msg rc-form__msg--error';
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
            msgDiv.className = 'rc-form__msg rc-form__msg--success';
            msgDiv.textContent = result.message;
            form.reset();
        } else {
            msgDiv.className = 'rc-form__msg rc-form__msg--error';
            msgDiv.textContent = result.message;
        }
        btn.disabled = false;
        btn.textContent = 'Gửi đơn ứng tuyển';
        setTimeout(function() { msgDiv.style.display = 'none'; }, 5000);
    })
    .catch(function(error) {
        msgDiv.style.display = 'block';
        msgDiv.className = 'rc-form__msg rc-form__msg--error';
        msgDiv.textContent = 'Đã xảy ra lỗi, vui lòng thử lại sau';
        btn.disabled = false;
        btn.textContent = 'Gửi đơn ứng tuyển';
    });
}
</script>
