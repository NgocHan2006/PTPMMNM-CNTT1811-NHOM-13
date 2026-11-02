<?php $siteFooterBase = $siteFooterBase ?? ''; ?>
<footer class="site-footer">
    <div class="footer-intro">
        Formly giúp bạn tạo biểu mẫu, thu thập câu trả lời và theo dõi dữ liệu
        thuận tiện trong cùng một không gian làm việc.
    </div>
    <div class="footer-main">
        <a class="footer-brand" href="<?= htmlspecialchars($siteFooterBase . 'index.php', ENT_QUOTES, 'UTF-8') ?>" aria-label="Formly - Trang tổng quan">
            <span class="footer-brand-mark">F</span>
            <span>
                <strong>Formly</strong>
                <small>Công cụ tạo biểu mẫu</small>
            </span>
        </a>
        <div class="footer-note">
            Dữ liệu biểu mẫu của bạn được quản lý trong hệ thống.
            <span>© <?= date('Y') ?> Formly</span>
        </div>
        <a class="footer-contact" href="<?= htmlspecialchars($siteFooterBase . 'forms.php', ENT_QUOTES, 'UTF-8') ?>">Quản lý biểu mẫu <span>→</span></a>
    </div>
    <div class="footer-bottom">
        <span>Đơn giản hóa việc thu thập thông tin</span>
        <nav aria-label="Liên kết nhanh">
            <a href="<?= htmlspecialchars($siteFooterBase . 'index.php', ENT_QUOTES, 'UTF-8') ?>">Tổng quan</a>
            <a href="<?= htmlspecialchars($siteFooterBase . 'forms.php', ENT_QUOTES, 'UTF-8') ?>">Biểu mẫu</a>
            <a href="<?= htmlspecialchars($siteFooterBase . 'responses.php', ENT_QUOTES, 'UTF-8') ?>">Câu trả lời</a>
        </nav>
    </div>
</footer>
