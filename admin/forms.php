
<?php
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Form Studio - Quản lý biểu mẫu</title>
<style>
:root {
    --primary: #ff6500;
    --primary-dark: #e85b00;
    --navy: #101b50;
    --bg: #fbfbfe;
    --text: #17224f;
    --muted: #70799a;
    --border: #dfe2f3;
    --sidebar: #f4f4ff;
}
* { box-sizing: border-box; }
html { min-width: 320px; }
body {
    margin: 0;
    font-family: Inter, "Segoe UI", Arial, sans-serif;
    background: var(--bg);
    color: var(--text);
    -webkit-font-smoothing: antialiased;
}
button, input, textarea, select { font: inherit; }
button { cursor: pointer; }
.app { min-height: 100vh; }
.sidebar {
    position: fixed;
    inset: 72px auto 0 0;
    width: 260px;
    background: var(--sidebar);
    border-right: 1px solid var(--border);
    padding: 25px 16px 96px;
    overflow-y: auto;
}
.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 10px 25px;
}
.brand-icon {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    background: var(--primary);
    color: white;
    display: grid; place-items: center;
    font-size: 23px;
    font-weight: 800;
    box-shadow: 0 5px 12px #ff650033;
}
.brand strong { font-size: 19px; }
.brand small {
    display: block;
    color: var(--muted);
    margin-top: 3px;
    font-size: 12px;
}
.create-btn, .primary-btn {
    border: 0;
    border-radius: 7px;
    background: var(--primary);
    color: white;
    padding: 13px 16px;
    font-weight: 700;
    box-shadow: 0 3px 8px #ff650026;
    transition: background .15s ease, box-shadow .15s ease, transform .15s ease;
}
.create-btn {
    width: 100%;
    margin-bottom: 31px;
    padding-left: 9px;
    padding-right: 9px;
    font-size: 14px;
    white-space: nowrap;
}
.create-btn:hover, .primary-btn:hover {
    background: var(--primary-dark);
    box-shadow: 0 5px 12px #ff65003b;
    transform: translateY(-1px);
}
.menu-label {
    color: #858ca9;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    padding: 0 12px;
    margin-bottom: 10px;
}
.nav-item {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 12px;
    margin: 3px 0;
    border-radius: 6px;
    color: #535d7d;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    transition: color .15s ease, background .15s ease;
}
.nav-item:hover, .nav-item.active {
    background: #cdd2ee;
    color: var(--navy);
    font-weight: 650;
}
.nav-icon {
    width: 20px;
    text-align: center;
    font-size: 17px;
}
.sidebar-bottom {
    position: absolute;
    left: 20px; right: 20px; bottom: 22px;
    border-top: 1px solid var(--border);
    padding-top: 17px;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.5;
    background: var(--sidebar);
}
.sidebar-bottom strong { color: var(--navy); }
.main {
    margin-left: 260px;
    padding-top: 72px;
}
.topbar {
    position: fixed;
    inset: 0 0 auto;
    z-index: 5;
    height: 72px;
    background: var(--navy);
    color: white;
    border-bottom: 1px solid #26336d;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30px;
    box-shadow: 0 2px 8px #101b5014;
}
.breadcrumb { color: #bfc8ef; font-size: 13px; }
.breadcrumb strong { color: white; }
.profile {
    display: flex; align-items: center; gap: 12px;
    font-size: 13px;
}
.logout-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 11px;
    border: 1px solid #ffffff3b;
    border-radius: 7px;
    color: white;
    font-weight: 600;
    text-decoration: none;
    transition: background .15s ease, border-color .15s ease;
}
.logout-link:hover {
    background: #ffffff1a;
    border-color: #ffffff80;
}
.avatar {
    width: 40px; height: 40px;
    background: var(--primary); color: white;
    display: grid; place-items: center;
    border-radius: 50%; font-weight: 700;
    box-shadow: 0 0 0 3px #ffffff20;
}
.content {
    padding: 35px 36px 54px;
    max-width: 1500px;
    margin: 0 auto;
}
.heading {
    display: flex; justify-content: space-between;
    align-items: center; gap: 20px;
    flex-wrap: wrap;
    padding-bottom: 4px;
}
h1 {
    color: var(--navy);
    font-size: clamp(24px, 2vw, 29px);
    letter-spacing: -.5px;
    margin: 0 0 9px;
}
.subtitle { color: var(--muted); font-size: 14px; line-height: 1.55; }
.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin: 29px 0 35px;
}
.stat {
    position: relative;
    overflow: hidden;
    background: white;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 21px 22px;
    box-shadow: 0 3px 12px #1a24500a;
    transition: transform .15s ease, box-shadow .15s ease;
}
.stat:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px #1a245014;
}
.stat-top {
    display: flex; justify-content: space-between;
    align-items: center; color: var(--muted);
    font-size: 13px;
}
.stat-icon {
    width: 40px; height: 40px;
    display: grid; place-items: center;
    border-radius: 9px;
    background: #fff0e6;
    color: var(--primary);
    font-size: 20px;
}
.stat:nth-child(2) .stat-icon { background: #e8f7df; color: #65a813; }
.stat:nth-child(3) .stat-icon { background: #f2e9ff; color: #8b43d6; }
.stat-number {
    color: var(--navy);
    font-size: 30px;
    font-weight: 750;
    letter-spacing: -.7px;
    margin-top: 13px;
}
.section-head {
    display: flex; justify-content: space-between;
    align-items: center; flex-wrap: wrap; gap: 15px;
    margin: 34px 0 17px;
}
h2 { color: var(--navy); font-size: 18px; margin: 0; }
.tools { display: flex; gap: 10px; flex-wrap: wrap; }
.search {
    width: 260px; max-width: 100%;
    padding: 11px 14px;
    border: 1px solid var(--border);
    border-radius: 7px; outline: none;
    background: white;
    color: var(--text);
    transition: border-color .15s ease, box-shadow .15s ease;
}
.search:focus, input:focus, textarea:focus, select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px #ff65001c;
    outline: none;
}
.secondary-btn {
    background: white;
    border: 1px solid var(--border);
    padding: 10px 14px;
    border-radius: 7px;
    color: #47516f;
    transition: border-color .15s ease, background .15s ease;
}
.secondary-btn:hover, .icon-btn:hover {
    border-color: #b9c0e0;
    background: #f8f8ff;
}
.templates {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}
.template {
    background: white;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 17px;
    cursor: pointer;
    transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
}
.template:hover {
    border-color: var(--primary);
    transform: translateY(-3px);
    box-shadow: 0 9px 22px #1a245014;
}
.template-art {
    height: 112px;
    border-radius: 8px;
    display: grid; place-items: center;
    margin-bottom: 16px;
    font-size: 38px;
}
.art-1 { background: #fff0e6; color: #ff6500; }
.art-2 { background: #f0eaff; color: #7c4ddb; }
.art-3 { background: #eaf6dc; color: #65a813; }
.template h3 { color: var(--navy); margin: 0 0 8px; font-size: 15px; }
.template p { color: var(--muted); font-size: 13px; line-height: 1.6; margin: 0; }
.template-action {
    color: var(--primary-dark);
    font-size: 13px;
    font-weight: 700;
    margin-top: 16px;
}
.form-list {
    background: white;
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 3px 12px #1a24500a;
}
.form-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 110px 120px;
    align-items: center; gap: 15px;
    padding: 17px 20px;
    border-bottom: 1px solid #eceef7;
    transition: background .15s ease;
}
.form-row:hover { background: #fcfcff; }
.form-row:last-child { border-bottom: 0; }
.form-name { color: var(--navy); font-weight: 650; font-size: 14px; }
.form-meta { color: var(--muted); font-size: 12px; margin-top: 6px; line-height: 1.5; }
.status {
    display: inline-block;
    border-radius: 20px;
    padding: 6px 10px;
    font-size: 12px;
    font-weight: 600;
    background: #fff4d8;
    color: #956100;
    width: fit-content;
}
.status.published { background: #e8f7df; color: #438313; }
.row-actions { display: flex; gap: 8px; justify-content: end; }
.icon-btn {
    background: white;
    border: 1px solid var(--border);
    border-radius: 7px;
    padding: 7px 10px;
    color: #596382;
    transition: border-color .15s ease, background .15s ease, color .15s ease;
}
.empty {
    text-align: center;
    padding: 42px 25px;
    color: var(--muted); font-size: 14px;
}
.modal-backdrop {
    display: none; position: fixed; inset: 0;
    background: rgba(16,27,80,.48);
    align-items: center; justify-content: center;
    padding: 20px; z-index: 10;
    backdrop-filter: blur(3px);
}
.modal-backdrop.open { display: flex; }
.modal {
    width: 100%; max-width: 490px;
    background: white;
    border: 1px solid #e8e9f2;
    border-radius: 13px;
    padding: 27px;
    box-shadow: 0 20px 60px #101b5033;
}
.modal-head {
    display: flex; justify-content: space-between;
    align-items: center; margin-bottom: 22px;
}
.close-btn {
    border: 0;
    background: #f2f3fa;
    color: var(--navy);
    width: 35px; height: 35px;
    border-radius: 8px;
    transition: background .15s ease;
}
.close-btn:hover { background: #e5e7f4; }
label { display: block; font-size: 13px; font-weight: 650; margin: 16px 0 8px; }
.modal input, .modal textarea {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 7px;
    padding: 12px;
    outline: none;
    color: var(--text);
    transition: border-color .15s ease, box-shadow .15s ease;
}
.modal textarea { resize: vertical; }
.modal-actions {
    display: flex; justify-content: end;
    gap: 10px; margin-top: 24px;
}
.notice { color: var(--muted); font-size: 12px; margin-top: 12px; line-height: 1.5; }
@media(max-width: 1100px) {
    .sidebar { width: 230px; }
    .main { margin-left: 230px; }
    .content { padding: 29px 25px 45px; }
    .templates { grid-template-columns: repeat(2, minmax(0,1fr)); }
}
@media(max-width: 760px) {
    .sidebar {
        position: static;
        width: 100%;
        padding: 15px 15px 12px;
        border-right: 0;
        border-bottom: 1px solid var(--border);
        overflow: visible;
    }
    .brand { padding: 0 5px 13px; }
    .sidebar .create-btn { margin-bottom: 12px; }
    .menu-label, .sidebar-bottom { display: none; }
    .sidebar nav {
        display: flex;
        gap: 4px;
        overflow-x: auto;
        padding-bottom: 2px;
    }
    .nav-item { flex: 0 0 auto; white-space: nowrap; padding: 10px; }
    .main { padding-top: 0; }
    .topbar {
        position: static;
        height: 58px;
        padding: 0 17px;
    }
    .main { margin-left: 0; }
    .content { padding: 25px 17px 38px; }
    .stats { grid-template-columns: 1fr; gap: 10px; }
    .stats { margin: 23px 0 29px; }
    .templates { grid-template-columns: 1fr; }
    .section-head { align-items: stretch; }
    .tools { width: 100%; }
    .tools .search, .tools select { flex: 1 1 100%; width: 100%; }
    .form-row { grid-template-columns: minmax(0, 1fr) auto; gap: 10px; padding: 15px; }
    .form-row .status { grid-column: 1; grid-row: 2; }
    .form-row .row-actions { grid-column: 2; grid-row: 1 / span 2; }
}
@media(max-width: 420px) {
    .topbar { padding: 0 13px; }
    .breadcrumb { font-size: 12px; }
    .profile { gap: 8px; font-size: 12px; }
    .avatar { width: 35px; height: 35px; }
    .logout-link { padding: 7px 8px; font-size: 11px; }
    .heading { align-items: flex-start; }
    .heading .primary-btn { width: 100%; }
    .modal { padding: 21px; }
    .modal-actions { flex-wrap: wrap; }
    .modal-actions button { flex: 1; }
}
.site-footer { margin-top: 40px; padding: 0; color: #d7dcf5; background: #0c1749; }
.site-footer .footer-intro { padding: 18px 36px; border-bottom: 1px solid #ffffff12; color: #bfc8ef; font-size: 13px; line-height: 1.65; }
.site-footer .footer-main { display: flex; min-height: 105px; align-items: center; justify-content: space-between; gap: 20px; padding: 20px 36px; background: #101b50; }
.site-footer .footer-brand { display: inline-flex; flex: 0 0 auto; align-items: center; gap: 12px; color: white; text-decoration: none; }
.site-footer .footer-brand-mark { display: grid; width: 42px; height: 42px; place-items: center; border-radius: 12px; background: var(--primary); color: white; font-size: 22px; font-weight: 800; }
.site-footer .footer-brand strong, .site-footer .footer-brand small { display: block; }
.site-footer .footer-brand strong { font-size: 17px; }
.site-footer .footer-brand small, .site-footer .footer-note { color: #bfc8ef; font-size: 12px; }
.site-footer .footer-brand small { margin-top: 3px; }
.site-footer .footer-note { display: grid; gap: 5px; line-height: 1.6; }
.site-footer .footer-note span { color: #8794c4; }
.site-footer .footer-contact { display: inline-flex; flex: 0 0 auto; align-items: center; gap: 8px; padding: 10px 13px; border: 1px solid #ffffff35; border-radius: 8px; color: white; font-size: 12px; font-weight: 650; text-decoration: none; }
.site-footer .footer-contact:hover { border-color: #ffffff75; background: #ffffff12; }
.site-footer .footer-bottom { display: flex; min-height: 62px; align-items: center; justify-content: space-between; gap: 18px; padding: 14px 36px; color: #aab6e3; font-size: 12px; }
.site-footer .footer-bottom nav { display: flex; flex-wrap: wrap; }
.site-footer .footer-bottom nav a { padding: 0 12px; border-right: 1px solid #ffffff40; color: inherit; text-decoration: none; }
.site-footer .footer-bottom nav a:first-child { padding-left: 0; }
.site-footer .footer-bottom nav a:last-child { padding-right: 0; border-right: 0; }
.site-footer .footer-bottom nav a:hover { color: white; }
@media(max-width: 760px) {
    .site-footer .footer-intro, .site-footer .footer-main, .site-footer .footer-bottom { padding-right: 18px; padding-left: 18px; }
    .site-footer .footer-main, .site-footer .footer-bottom { align-items: flex-start; flex-direction: column; gap: 14px; }
}
</style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-icon">F</div>
            <div>
                <strong>Form Studio</strong>
                <small>Smart Form Builder</small>
            </div>
        </div>

        <button class="create-btn" onclick="openModal()">
            ＋ &nbsp; Tạo biểu mẫu mới
        </button>

        <div class="menu-label">KHÔNG GIAN LÀM VIỆC</div>
        <nav>
            <div class="nav-item active" onclick="showSection('overview', this)">
                <span class="nav-icon">▦</span> Tổng quan
            </div>
            <div class="nav-item" onclick="showSection('forms', this)">
                <span class="nav-icon">▤</span> Biểu mẫu của tôi
            </div>
            <a class="nav-item" href="../responses.php">
                <span class="nav-icon">☷</span> Câu trả lời
            </a>
            <div class="nav-item" onclick="showSection('stats', this)">
                <span class="nav-icon">▥</span> Thống kê
            </div>
            <div class="nav-item" onclick="showSection('settings', this)">
                <span class="nav-icon">⚙</span> Cài đặt
            </div>
        </nav>

        <div class="sidebar-bottom">
            <strong>Form Studio</strong><br><br>
            Công cụ tạo biểu mẫu có điều kiện
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div class="breadcrumb">Không gian làm việc / <strong id="crumb">Tổng quan</strong></div>
            <div class="profile">
                <span>Quản trị viên</span>
                <div class="avatar">AD</div>
                <a class="logout-link" href="../logout.php" title="Đăng xuất tài khoản">
                    ↪ <span>Đăng xuất</span>
                </a>
            </div>
        </header>

        <div class="content">
            <section id="overview">
                <div class="heading">
                    <div>
                        <h1>Tổng quan</h1>
                        <div class="subtitle">Xin chào! Quản lý biểu mẫu của bạn tại đây.</div>
                    </div>
                    <button class="primary-btn" onclick="openModal()">＋ Tạo biểu mẫu</button>
                </div>

                <div class="stats">
                    <div class="stat">
                        <div class="stat-top"><span>Tổng biểu mẫu</span><span class="stat-icon">▤</span></div>
                        <div class="stat-number" id="totalForms">3</div>
                    </div>
                    <div class="stat">
                        <div class="stat-top"><span>Đã công bố</span><span class="stat-icon">✓</span></div>
                        <div class="stat-number" id="publishedForms">1</div>
                    </div>
                    <div class="stat">
                        <div class="stat-top"><span>Tổng câu trả lời</span><span class="stat-icon">☷</span></div>
                        <div class="stat-number" id="totalResponses">30</div>
                    </div>
                </div>

                <div class="section-head">
                    <h2>Bắt đầu tạo biểu mẫu</h2>
                </div>
                <div class="templates">
                    <div class="template" onclick="createFromTemplate('Biểu mẫu trống','')">
                        <div class="template-art art-1">＋</div>
                        <h3>Biểu mẫu trống</h3>
                        <p>Bắt đầu từ đầu và tự thiết kế các trường dữ liệu.</p>
                        <div class="template-action">Tạo biểu mẫu →</div>
                    </div>
                    <div class="template" onclick="createFromTemplate('Khảo sát sinh viên','Khảo sát ý kiến sinh viên')">
                        <div class="template-art art-2">☷</div>
                        <h3>Khảo sát sinh viên</h3>
                        <p>Mẫu khảo sát ý kiến với các câu hỏi cơ bản.</p>
                        <div class="template-action">Sử dụng mẫu →</div>
                    </div>
                    <div class="template" onclick="createFromTemplate('Đăng ký sự kiện','Thu thập thông tin đăng ký sự kiện')">
                        <div class="template-art art-3">▣</div>
                        <h3>Đăng ký sự kiện</h3>
                        <p>Thu thập thông tin người đăng ký tham gia.</p>
                        <div class="template-action">Sử dụng mẫu →</div>
                    </div>
                </div>
            </section>

            <section id="forms" style="display:none">
                <div class="heading">
                    <div>
                        <h1>Biểu mẫu của tôi</h1>
                        <div class="subtitle">Tìm kiếm và quản lý các biểu mẫu.</div>
                    </div>
                    <button class="primary-btn" onclick="openModal()">＋ Tạo biểu mẫu</button>
                </div>
            </section>

            <section id="responses" style="display:none">
                <h1>Câu trả lời</h1>
                <p class="subtitle">Quản lý dữ liệu thu thập từ các biểu mẫu.</p>
            </section>
            <section id="stats" style="display:none">
                <h1>Thống kê</h1>
                <p class="subtitle">Tổng hợp số liệu biểu mẫu và câu trả lời.</p>
            </section>
            <section id="settings" style="display:none">
                <h1>Cài đặt</h1>
                <p class="subtitle">Cấu hình không gian làm việc.</p>
            </section>

            <div id="formManager">
                <div class="section-head">
                    <h2>Biểu mẫu gần đây</h2>
                    <div class="tools">
                        <input class="search" id="searchInput"
                               placeholder="⌕  Tìm kiếm biểu mẫu..."
                               oninput="renderForms()">
                        <select class="secondary-btn" id="statusFilter" onchange="renderForms()">
                            <option value="all">Tất cả trạng thái</option>
                            <option value="draft">Bản nháp</option>
                            <option value="published">Đã công bố</option>
                        </select>
                    </div>
                </div>
                <div class="form-list" id="formList"></div>
            </div>
        </div>
        <?php $siteFooterBase = '../'; require __DIR__ . '/../includes/site_footer.php'; ?>
    </main>
</div>

<div class="modal-backdrop" id="createModal" onclick="backdropClose(event)">
    <div class="modal">
        <div class="modal-head">
            <h2>Tạo biểu mẫu mới</h2>
            <button class="close-btn" onclick="closeModal()">✕</button>
        </div>
        <form id="createForm" onsubmit="addForm(event)">
            <label for="formTitle">Tên biểu mẫu *</label>
            <input id="formTitle" maxlength="255" required
                   placeholder="Ví dụ: Khảo sát sinh viên">
            <label for="formDescription">Mô tả</label>
            <textarea id="formDescription" rows="3"
                      placeholder="Mục đích của biểu mẫu..."></textarea>
            <div class="notice">
                Bản giao diện hiện tại dùng dữ liệu minh họa trên trình duyệt.
                Chưa lưu biểu mẫu vào MySQL.
            </div>
            <div class="modal-actions">
                <button type="button" class="secondary-btn" onclick="closeModal()">Hủy</button>
                <button type="submit" class="primary-btn">Tạo biểu mẫu</button>
            </div>
        </form>
    </div>
</div>

<script>
let forms = [
    {id: 1, title: 'Khảo sát sinh viên', description: 'Khảo sát ý kiến sinh viên',
     status: 'draft', fields: 12, responses: 0},
    {id: 2, title: 'Đăng ký sự kiện', description: 'Thu thập thông tin đăng ký',
     status: 'published', fields: 10, responses: 30},
    {id: 3, title: 'Tiếp nhận yêu cầu hỗ trợ', description: 'Tiếp nhận yêu cầu hỗ trợ',
     status: 'draft', fields: 11, responses: 0}
];

let nextId = 4;

function escapeHTML(value) {
    return String(value).replace(/[&<>"']/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;',
        '"': '&quot;', "'": '&#039;'
    })[ch]);
}

function renderForms() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const filtered = forms.filter(f =>
        f.title.toLowerCase().includes(query) &&
        (status === 'all' || f.status === status)
    );

    document.getElementById('totalForms').textContent = forms.length;
    document.getElementById('publishedForms').textContent =
        forms.filter(f => f.status === 'published').length;
    document.getElementById('totalResponses').textContent =
        forms.reduce((sum, f) => sum + f.responses, 0);

    const list = document.getElementById('formList');
    if (!filtered.length) {
        list.innerHTML = '<div class="empty">Không tìm thấy biểu mẫu phù hợp.</div>';
        return;
    }

    list.innerHTML = filtered.map(f => `
        <div class="form-row">
            <div>
                <div class="form-name">${escapeHTML(f.title)}</div>
                <div class="form-meta">${escapeHTML(f.description || 'Chưa có mô tả')}
                    · ${f.fields} trường · ${f.responses} câu trả lời</div>
            </div>
            <span class="status ${f.status === 'published' ? 'published' : ''}">
                ${f.status === 'published' ? 'Đã công bố' : 'Bản nháp'}
            </span>
            <div class="row-actions">
                <button class="icon-btn" title="Thiết kế"
                    onclick="editForm(${f.id})">Thiết kế</button>
                <button class="icon-btn" title="Xóa"
                    onclick="deleteForm(${f.id})">×</button>
            </div>
        </div>
    `).join('');
}

function openModal(title = '', description = '') {
    document.getElementById('createForm').reset();
    document.getElementById('formTitle').value = title;
    document.getElementById('formDescription').value = description;
    document.getElementById('createModal').classList.add('open');
    document.getElementById('formTitle').focus();
}
function closeModal() {
    document.getElementById('createModal').classList.remove('open');
}
function backdropClose(event) {
    if (event.target.id === 'createModal') closeModal();
}
function createFromTemplate(title, description) {
    openModal(title, description);
}
function addForm(event) {
    event.preventDefault();
    const title = document.getElementById('formTitle').value.trim();
    const description = document.getElementById('formDescription').value.trim();
    if (!title) return;

    forms.unshift({
        id: nextId++,
        title,
        description,
        status: 'draft',
        fields: 0,
        responses: 0
    });

    renderForms();
    closeModal();
    showSection('forms', document.querySelectorAll('.nav-item')[1]);
}
function deleteForm(id) {
    if (!confirm('Bạn có chắc muốn xóa biểu mẫu minh họa này khỏi giao diện?')) return;
    forms = forms.filter(f => f.id !== id);
    renderForms();
}
function editForm(id) {
    const form = forms.find(f => f.id === id);
    if (!form) return;
    alert('Biểu mẫu: ' + form.title +
        '\nBước tiếp theo sẽ xây dựng trình thiết kế trường và điều kiện.');
}
function showSection(id, element) {
    document.querySelectorAll(
        '#overview, #forms, #responses, #stats, #settings'
    ).forEach(section => section.style.display = 'none');

    document.getElementById(id).style.display = 'block';
    document.getElementById('crumb').textContent = element.textContent.trim();
    document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
    element.classList.add('active');

    document.getElementById('formManager').style.display =
        ['overview', 'forms'].includes(id) ? 'block' : 'none';
}
renderForms();
</script>
</body>
</html>
