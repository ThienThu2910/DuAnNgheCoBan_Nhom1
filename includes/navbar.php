<?php
if (session_status() !== PHP_SESSION_ACTIVE) {  
    session_start();  
}

$currentPage = $currentPage ?? '';  
?>

<!-- Style tùy chỉnh riêng cho Navbar -->
<style>
    .navbar-custom {
        background-color: #144d36; /* Xanh lá rừng Cà Mau */
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    .navbar-custom .nav-link {
        color: rgba(255, 255, 255, 0.85);
        font-weight: 500;
        padding: 0.5rem 0.8rem;
        transition: all 0.2s ease-in-out;
        border-bottom: 2px solid transparent;
    }
    .navbar-custom .nav-link:hover,
    .navbar-custom .nav-link.active {
        color: #ffc107 !important; /* Vàng mật ong Cà Mau */
        border-bottom-color: #ffc107;
    }
    /* Khung kết quả tìm kiếm thả xuống */
    #live-search-results {
        max-height: 320px;
        overflow-y: auto;
    }
    @media (max-width: 991.98px) {
        .navbar-custom .nav-link {
            border-bottom: none !important;
            padding: 0.6rem 1rem;
        }
        .search-box-container {
            margin: 10px 0;
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">  
    <div class="container">  
        <!-- LOGO BRAND -->
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/dac-san-ca-mau/">  
            <i class="bi bi-geo-alt-fill text-warning fs-4"></i>
            <span>Đặc sản Cà Mau</span>
        </a>

        <!-- NÚT TOGGLE DI ĐỘNG -->
        <button  
            class="navbar-toggler border-0"  
            type="button"  
            data-bs-toggle="collapse"  
            data-bs-target="#mainNavbar"  
            aria-controls="mainNavbar"  
            aria-expanded="false"  
            aria-label="Mở menu"  
        >  
            <span class="navbar-toggler-icon"></span>  
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">  
            <!-- THANH TÌM KIẾM NHANH (LIVE SEARCH) -->
            <div class="search-box-container position-relative mx-lg-auto my-2 my-lg-0" style="min-width: 250px; max-width: 350px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input 
                        type="text" 
                        id="live-search-input" 
                        class="form-control border-0 shadow-none" 
                        placeholder="Tìm đặc sản, cơ sở..." 
                        autocomplete="off"
                    >
                </div>
                <!-- Vùng hiển thị kết quả thả xuống -->
                <div id="live-search-results" class="list-group position-absolute w-100 shadow-lg d-none mt-1" style="z-index: 1050;"></div>
            </div>

            <!-- MENU CHÍNH -->
            <ul class="navbar-nav ms-auto align-items-lg-center">  
                <li class="nav-item">  
                    <a class="nav-link <?= $currentPage === 'trang-chu' ? 'active' : '' ?>" href="/dac-san-ca-mau/">  
                        <i class="bi bi-house-door me-1"></i>Trang chủ  
                    </a>  
                </li>  

                <li class="nav-item">  
                    <a class="nav-link <?= $currentPage === 'dac-san' ? 'active' : '' ?>" href="/dac-san-ca-mau/dac-san.php">  
                        <i class="bi bi-box-seam me-1"></i>Đặc sản  
                    </a>  
                </li>  

                <li class="nav-item">  
                    <a class="nav-link <?= $currentPage === 'co-so' ? 'active' : '' ?>" href="/dac-san-ca-mau/co-so-san-xuat.php">  
                        <i class="bi bi-shop me-1"></i>Cơ sở sản xuất  
                    </a>  
                </li>  

                <li class="nav-item">  
                    <a class="nav-link <?= $currentPage === 'ban-do' ? 'active' : '' ?>" href="/dac-san-ca-mau/ban-do.php">  
                        <i class="bi bi-map me-1"></i>Bản đồ đặc sản  
                    </a>  
                </li>  

                <li class="nav-item">  
                    <a class="nav-link <?= $currentPage === 'bai-viet' ? 'active' : '' ?>" href="/dac-san-ca-mau/bai-viet.php">  
                        <i class="bi bi-journal-text me-1"></i>Câu chuyện  
                    </a>  
                </li>  

                <li class="nav-item me-lg-2">  
                    <a class="nav-link <?= $currentPage === 'lien-he' ? 'active' : '' ?>" href="/dac-san-ca-mau/lien-he.php">  
                        <i class="bi bi-envelope me-1"></i>Liên hệ  
                    </a>  
                </li>  

                <!-- KHU VỰC TÀI KHOẢN / QUẢN TRỊ -->
                <?php if (!empty($_SESSION['admin_id'])): ?>  
                    <li class="nav-item ms-lg-2 my-1 my-lg-0">  
                        <a class="btn btn-warning btn-sm fw-bold px-3 shadow-sm" href="/dac-san-ca-mau/admin/index.php">  
                            <i class="bi bi-speedometer2 me-1"></i>Quản trị  
                        </a>  
                    </li>  
                    <li class="nav-item ms-lg-2 my-1 my-lg-0">  
                        <a class="btn btn-outline-light btn-sm px-3" href="/dac-san-ca-mau/logout.php">  
                            <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất  
                        </a>  
                    </li>  

                <?php elseif (!empty($_SESSION['user_id'])): ?>  
                    <li class="nav-item ms-lg-2 my-1 my-lg-0">  
                        <a class="btn btn-warning btn-sm fw-bold px-3 shadow-sm" href="/dac-san-ca-mau/tai-khoan.php">  
                            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['user_name']) ?>  
                        </a>  
                    </li>  
                    <li class="nav-item ms-lg-2 my-1 my-lg-0">  
                        <a class="btn btn-outline-light btn-sm px-3" href="/dac-san-ca-mau/logout.php">  
                            <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất  
                        </a>  
                    </li>  

                <?php else: ?>  
                    <li class="nav-item ms-lg-2 my-1 my-lg-0">  
                        <a class="btn btn-warning btn-sm fw-bold px-3 shadow-sm" href="/dac-san-ca-mau/login.php">  
                            <i class="bi bi-person-fill-lock me-1"></i>Đăng nhập  
                        </a>  
                    </li>  
                <?php endif; ?>  
            </ul>  
        </div>  
    </div>  
</nav>

<!-- SCRIPT TÌM KIẾM NHANH (LIVE SEARCH) -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById('live-search-input');
    const resultsBox = document.getElementById('live-search-results');
    let timeout = null;

    if (!input || !resultsBox) return;

    input.addEventListener('input', function () {
        clearTimeout(timeout);
        const query = this.value.trim();

        if (query.length < 2) {
            resultsBox.classList.add('d-none');
            resultsBox.innerHTML = '';
            return;
        }

        timeout = setTimeout(() => {
            fetch(`/dac-san-ca-mau/api/live-search.php?q=${encodeURIComponent(query)}`)
                .then(res => {
                    if (!res.ok) throw new Error('Không tìm thấy API');
                    return res.json();
                })
                .then(data => {
                    resultsBox.innerHTML = '';
                    if (!data || data.length === 0) {
                        resultsBox.innerHTML = '<div class="list-group-item small text-muted p-2">Không tìm thấy kết quả</div>';
                    } else {
                        data.forEach(item => {
                            const url = item.loai === 'dac-san' 
                                ? `/dac-san-ca-mau/chi-tiet-dac-san.php?id=${item.id}`
                                : `/dac-san-ca-mau/co-so-san-xuat.php?id=${item.id}`;
                            
                            const folder = item.loai === 'dac-san' ? 'dac-san' : 'co-so';
                            const imgPath = item.hinh_anh 
                                ? `/dac-san-ca-mau/assets/uploads/${folder}/${item.hinh_anh}`
                                : '/dac-san-ca-mau/assets/images/banner-ca-mau.jpg';

                            resultsBox.innerHTML += `
                                <a href="${url}" class="list-group-item list-group-item-action d-flex align-items-center gap-2 p-2">
                                    <img src="${imgPath}" style="width: 36px; height: 38px; object-fit: cover; border-radius: 6px;" onerror="this.src='/dac-san-ca-mau/assets/images/banner-ca-mau.jpg'">
                                    <div class="small lh-sm overflow-hidden">
                                        <div class="fw-bold text-dark text-truncate mb-1">${item.ten}</div>
                                        <span class="badge ${item.loai === 'dac-san' ? 'bg-success' : 'bg-primary'}" style="font-size: 10px;">
                                            ${item.loai === 'dac-san' ? 'Đặc sản' : 'Cơ sở'}
                                        </span>
                                    </div>
                                </a>
                            `;
                        });
                    }
                    resultsBox.classList.remove('d-none');
                })
                .catch(err => {
                    console.error('Lỗi Live Search:', err);
                    resultsBox.classList.add('d-none');
                });
        }, 300);
    });

    document.addEventListener('click', function (e) {
        if (!input.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.classList.add('d-none');
        }
    });
});
</script>