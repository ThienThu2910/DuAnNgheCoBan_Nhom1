<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isAdmin = !empty($_SESSION['admin_id']);
$isUser = !empty($_SESSION['user_id']);

if (!isset($baseUrl)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $baseUrl = preg_replace('#/(admin|api|includes).*$#', '', $scriptDir);
    $baseUrl = rtrim($baseUrl, '/');
}
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top py-2 shadow-sm" style="background-color: #1a0d0f !important; z-index: 1040;">
    <div class="container">
        <!-- Logo thương hiệu -->
        <a class="navbar-brand fw-bold text-white fs-5 me-3" href="<?= $baseUrl ?>/index.php">
            Đặc sản Cà Mau
        </a>

        <!-- Nút bật menu mobile -->
        <button class="navbar-toggler py-1 px-2 border-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Ô TÌM KIẾM NỔI BẬT & LIVE SEARCH -->
            <div class="position-relative my-2 my-lg-0 me-auto">
                <form class="d-flex" action="<?= $baseUrl ?>/dac-san.php" method="get" id="navSearchForm">
                    <div class="input-group input-group-sm nav-search-box">
                        <span class="input-group-text bg-white border-0 text-muted ps-3">
                            <i class="bi bi-search"></i>
                        </span>
                        <input 
                            class="form-control border-0 bg-white text-dark ps-2 pe-3" 
                            type="search" 
                            name="q" 
                            id="navSearchInput"
                            placeholder="Tìm đặc sản, cơ sở..." 
                            value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                            autocomplete="off"
                            style="width: 220px; font-size: 0.88rem; border-radius: 0 20px 20px 0;"
                        >
                    </div>
                </form>

                <!-- Khung gợi ý kết quả Live Search -->
                <div id="liveSearchResult" class="live-search-dropdown shadow-lg"></div>
            </div>

            <!-- Menu chính -->
            <ul class="navbar-nav mb-2 mb-lg-0 align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link px-2 py-1 <?= ($currentPage ?? '') === 'trang-chu' ? 'active text-warning fw-bold' : 'text-white-50' ?>" href="<?= $baseUrl ?>/index.php">Trang chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2 py-1 <?= ($currentPage ?? '') === 'dac-san' ? 'active text-warning fw-bold' : 'text-white-50' ?>" href="<?= $baseUrl ?>/dac-san.php">Đặc sản</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2 py-1 <?= ($currentPage ?? '') === 'co-so' ? 'active text-warning fw-bold' : 'text-white-50' ?>" href="<?= $baseUrl ?>/co-so-san-xuat.php">Cơ sở sản xuất</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2 py-1 <?= ($currentPage ?? '') === 'ban-do' ? 'active text-warning fw-bold' : 'text-white-50' ?>" href="<?= $baseUrl ?>/ban-do.php">Bản đồ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2 py-1 <?= in_array($currentPage ?? '', ['cau-chuyen', 'bai-viet'], true) ? 'active text-warning fw-bold' : 'text-white-50' ?>" href="<?= $baseUrl ?>/bai-viet.php">Câu chuyện</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-2 py-1 <?= ($currentPage ?? '') === 'lien-he' ? 'active text-warning fw-bold' : 'text-white-50' ?>" href="<?= $baseUrl ?>/lien-he.php">Liên hệ</a>
                </li>
            </ul>

            <!-- Nhóm nút Quản trị / Tài khoản / Theme toggle -->
            <div class="d-flex align-items-center gap-2 ms-lg-3 mt-2 mt-lg-0">
                <?php if ($isAdmin): ?>
                    <a href="<?= $baseUrl ?>/admin/index.php" class="btn btn-sm btn-danger py-1 px-2" style="font-size: 0.8rem;">
                        <i class="bi bi-speedometer2 me-1"></i> Quản trị
                    </a>
                    <a href="<?= $baseUrl ?>/admin/logout.php" class="btn btn-sm btn-outline-light py-1 px-2" style="font-size: 0.8rem;">
                        Đăng xuất
                    </a>
                <?php elseif ($isUser): ?>
                    <a href="<?= $baseUrl ?>/tai-khoan.php" class="btn btn-sm btn-outline-warning py-1 px-2" style="font-size: 0.8rem;">
                        <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars((string)$_SESSION['user_name']) ?>
                    </a>
                    <a href="<?= $baseUrl ?>/logout.php" class="btn btn-sm btn-outline-light py-1 px-2" style="font-size: 0.8rem;">
                        Đăng xuất
                    </a>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>/login.php" class="btn btn-sm btn-outline-light py-1 px-2" style="font-size: 0.8rem;">
                        Đăng nhập
                    </a>
                <?php endif; ?>

                <button class="btn btn-sm btn-outline-secondary text-white-50 p-1 px-2 border-secondary" type="button" id="themeToggleBtn" onclick="toggleTheme()" title="Đổi giao diện Sáng/Tối">
                    <i class="bi bi-moon-stars" id="themeIcon"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

<script>
function toggleTheme() {
    var html = document.documentElement;
    var current = html.getAttribute('data-theme') || 'light';
    var next = current === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    try { localStorage.setItem('cm-theme', next); } catch(e) {}
    updateThemeIcon(next);
}

function updateThemeIcon(theme) {
    var icon = document.getElementById('themeIcon');
    if (icon) {
        icon.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars';
    }
}

document.addEventListener("DOMContentLoaded", function() {
    var theme = document.documentElement.getAttribute('data-theme') || 'light';
    updateThemeIcon(theme);

    // XỬ LÝ LIVE SEARCH GỢI Ý TỨC THÌ
    const searchInput = document.getElementById('navSearchInput');
    const resultBox = document.getElementById('liveSearchResult');
    let debounceTimer;

    if (searchInput && resultBox) {
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();

            if (query.length < 2) {
                resultBox.innerHTML = '';
                resultBox.style.display = 'none';
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch('<?= $baseUrl ?>/api/live-search.php?q=' + encodeURIComponent(query))
                    .then(res => res.json())
                    .then(data => {
                        if (!data || data.length === 0) {
                            resultBox.innerHTML = '<div class="p-3 text-muted small text-center">Không tìm thấy kết quả nào.</div>';
                            resultBox.style.display = 'block';
                            return;
                        }

                        let html = '<div class="list-group list-group-flush rounded-3">';
                        data.forEach(item => {
                            const link = item.loai === 'dac-san' 
                                ? '<?= $baseUrl ?>/chi-tiet-dac-san.php?id=' + item.id 
                                : '<?= $baseUrl ?>/co-so-san-xuat.php?q=' + encodeURIComponent(item.ten);
                            
                            const badge = item.loai === 'dac-san' 
                                ? '<span class="badge bg-danger ms-auto" style="font-size: 10px;">Đặc sản</span>'
                                : '<span class="badge bg-primary ms-auto" style="font-size: 10px;">Cơ sở</span>';

                            const imgUrl = item.hinh_anh 
                                ? '<?= $baseUrl ?>/assets/uploads/' + (item.loai === 'dac-san' ? 'dac-san/' : 'co-so/') + item.hinh_anh
                                : '';

                            html += `
                                <a href="${link}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 p-2">
                                    ${imgUrl ? `<img src="${imgUrl}" class="rounded" style="width: 38px; height: 38px; object-fit: cover;">` : '<div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width: 38px; height: 38px;"><i class="bi bi-image"></i></div>'}
                                    <div class="small fw-semibold text-dark text-truncate" style="max-width: 170px;">${item.ten}</div>
                                    ${badge}
                                </a>
                            `;
                        });
                        html += '</div>';

                        resultBox.innerHTML = html;
                        resultBox.style.display = 'block';
                    })
                    .catch(() => {
                        resultBox.style.display = 'none';
                    });
            }, 250);
        });

        // Đóng dropdown khi bấm ra ngoài
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultBox.contains(e.target)) {
                resultBox.style.display = 'none';
            }
        });
    }
});
</script>