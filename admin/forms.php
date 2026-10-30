
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
    --primary: #16a5a5;
    --primary-dark: #108585;
    --bg: #f6f8fb;
    --text: #172033;
    --muted: #718096;
    --border: #e7ebf1;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: Inter, "Segoe UI", Arial, sans-serif;
    background: var(--bg);
    color: var(--text);
}
button, input, textarea, select { font: inherit; }
button { cursor: pointer; }
.app { min-height: 100vh; }
.sidebar {
    position: fixed;
    inset: 0 auto 0 0;
    width: 248px;
    background: white;
    border-right: 1px solid var(--border);
    padding: 24px 16px;
}
.brand {
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 0 10px 30px;
}
.brand-icon {
    width: 42px; height: 42px;
    border-radius: 12px;
    background: var(--primary);
    color: white;
    display: grid; place-items: center;
    font-size: 23px; font-weight: 800;
}
.brand strong { font-size: 19px; }
.brand small {
    display: block;
    color: var(--muted);
    margin-top: 4px;
}
.create-btn, .primary-btn {
    border: 0;
    border-radius: 9px;
    background: var(--primary);
    color: white;
    padding: 12px 15px;
    font-weight: 650;
}
.create-btn {
    width: 100%;
    margin-bottom: 28px;
}
.create-btn:hover, .primary-btn:hover {
    background: var(--primary-dark);
}
.menu-label {
    color: #a0a8b7;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    padding: 0 12px;
    margin-bottom: 12px;
}
.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 12px;
    margin: 4px 0;
    border-radius: 9px;
    color: #5b6678;
    cursor: pointer;
    font-size: 14px;
}
.nav-item:hover, .nav-item.active {
    background: #e9f8f7;
    color: #078b8b;
    font-weight: 650;
}
.nav-icon { width: 20px; text-align: center; }
.sidebar-bottom {
    position: absolute;
    left: 20px; right: 20px; bottom: 22px;
    border-top: 1px solid var(--border);
    padding-top: 17px;
    color: var(--muted);
    font-size: 13px;
}
.main { margin-left: 248px; }
.topbar {
    height: 72px;
    background: white;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 36px;
}
.breadcrumb { color: var(--muted); font-size: 13px; }
.profile {
    display: flex; align-items: center; gap: 12px;
    font-size: 13px;
}
.avatar {
    width: 36px; height: 36px;
    background: #d8f3f0; color: #087f7f;
    display: grid; place-items: center;
    border-radius: 50%; font-weight: 700;
}
.content { padding: 32px 36px 50px; max-width: 1500px; }
.heading {
    display: flex; justify-content: space-between;
    align-items: center; gap: 20px;
    flex-wrap: wrap;
}
h1 { font-size: 28px; margin: 0 0 9px; }
.subtitle { color: var(--muted); font-size: 14px; }
.stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
    margin: 28px 0;
}
.stat {
    background: white; border: 1px solid var(--border);
    border-radius: 13px; padding: 22px;
}
.stat-top {
    display: flex; justify-content: space-between;
    align-items: center; color: var(--muted);
    font-size: 13px;
}
.stat-icon {
    width: 39px; height: 39px;
    display: grid; place-items: center;
    border-radius: 10px; background: #e7f8f5;
    color: #0d9992; font-size: 20px;
}
.stat-number { font-size: 29px; font-weight: 750; margin-top: 15px; }
.section-head {
    display: flex; justify-content: space-between;
    align-items: center; flex-wrap: wrap; gap: 15px;
    margin: 32px 0 18px;
}
h2 { font-size: 19px; margin: 0; }
.tools { display: flex; gap: 10px; flex-wrap: wrap; }
.search {
    width: 250px; max-width: 100%;
    padding: 11px 13px;
    border: 1px solid var(--border);
    border-radius: 9px; outline: none;
    background: white;
}
.search:focus, input:focus, textarea:focus {
    border-color: var(--primary);
}
.secondary-btn {
    background: white; border: 1px solid var(--border);
    padding: 10px 14px; border-radius: 9px; color: #475569;
}
.templates {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 18px;
}
.template {
    background: white; border: 1px solid var(--border);
    border-radius: 13px; padding: 20px;
    cursor: pointer; transition: .15s;
}
.template:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
}
.template-art {
    height: 125px; border-radius: 9px;
    display: grid; place-items: center;
    margin-bottom: 16px; font-size: 40px;
}
.art-1 { background: #e8f8f5; color: #109c92; }
.art-2 { background: #f0eaff; color: #7c4ddb; }
.art-3 { background: #fff1df; color: #d98420; }
.template h3 { margin: 0 0 8px; font-size: 15px; }
.template p { color: var(--muted); font-size: 13px; line-height: 1.6; margin: 0; }
.template-action { color: var(--primary-dark); font-size: 13px; font-weight: 650; margin-top: 17px; }
.form-list {
    background: white; border: 1px solid var(--border);
    border-radius: 12px; overflow: hidden;
}
.form-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 110px 120px;
    align-items: center; gap: 15px;
    padding: 17px 20px; border-bottom: 1px solid var(--border);
}
.form-row:last-child { border-bottom: 0; }
.form-name { font-weight: 650; font-size: 14px; }
.form-meta { color: var(--muted); font-size: 12px; margin-top: 6px; }
.status {
    display: inline-block; border-radius: 20px;
    padding: 6px 10px; font-size: 12px;
    background: #fff4d8; color: #956100;
    width: fit-content;
}
.status.published { background: #dcfce7; color: #15803d; }
.row-actions { display: flex; gap: 8px; justify-content: end; }
.icon-btn {
    background: white; border: 1px solid var(--border);
    border-radius: 7px; padding: 7px 10px; color: #64748b;
}
.empty {
    text-align: center; padding: 35px;
    color: var(--muted); font-size: 14px;
}
.modal-backdrop {
    display: none; position: fixed; inset: 0;
    background: rgba(15,23,42,.45);
    align-items: center; justify-content: center;
    padding: 20px; z-index: 10;
}
.modal-backdrop.open { display: flex; }
.modal {
    width: 100%; max-width: 490px;
    background: white; border-radius: 15px;
    padding: 26px; box-shadow: 0 20px 60px #0002;
}
.modal-head {
    display: flex; justify-content: space-between;
    align-items: center; margin-bottom: 22px;
}
.close-btn {
    border: 0; background: #f1f5f9;
    width: 34px; height: 34px; border-radius: 8px;
}
label { display: block; font-size: 13px; font-weight: 650; margin: 16px 0 8px; }
.modal input, .modal textarea {
    width: 100%; border: 1px solid #dce2ea;
    border-radius: 8px; padding: 12px; outline: none;
}
.modal textarea { resize: vertical; }
.modal-actions {
    display: flex; justify-content: end;
    gap: 10px; margin-top: 24px;
}
.notice { color: var(--muted); font-size: 12px; margin-top: 12px; line-height: 1.5; }
@media(max-width: 1000px) {
    .sidebar { width: 205px; }
    .main { margin-left: 205px; }
    .content { padding: 25px 20px; }
    .templates { grid-template-columns: repeat(2, minmax(0,1fr)); }
}
@media(max-width: 680px) {
    .sidebar { position: static; width: 100%; padding: 15px; }
    .brand { padding-bottom: 15px; }
    .sidebar .create-btn { margin-bottom: 10px; }
    .menu-label, .sidebar-bottom { display: none; }
    .sidebar nav { display: flex; overflow-x: auto; }
    .nav-item { white-space: nowrap; padding: 10px; }
    .main { margin-left: 0; }
    .topbar { padding: 0 18px; height: 58px; }
    .content { padding: 23px 15px; }
    .stats { grid-template-columns: 1fr; gap: 10px; }
    .templates { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: minmax(0,1fr) auto; }
    .form-row .status { display: none; }
    .search { width: 100%; }
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
            <div class="nav-item" onclick="showSection('responses', this)">
                <span class="nav-icon">☷</span> Câu trả lời
            </div>
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
