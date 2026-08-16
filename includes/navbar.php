<?php 
 
if (session_status() !== PHP_SESSION_ACTIVE) { 
    session_start(); 
} 
 
$currentPage = $currentPage ?? ''; 
$baseUrl = $baseUrl ?? '/DuAnNgheCoBan_Nhom1'; 
 
?> 
 
<nav class="navbar navbar-expand-lg navbar-custom sticky-top"> 
 
    <div class="container navbar-inner"> 
 
        <!-- LOGO --> 
        <a 
            class="navbar-brand" 
            href="<?= $baseUrl ?>/" 
        > 
            <span>Đặc sản Cà Mau</span> 
        </a> 
 
 
        <!-- MOBILE MENU --> 
        <button 
            class="navbar-toggler" 
            type="button" 
            data-bs-toggle="collapse" 
            data-bs-target="#mainNavbar" 
            aria-controls="mainNavbar" 
            aria-expanded="false" 
            aria-label="Mở menu" 
        > 
            <span class="navbar-toggler-icon"></span> 
        </button> 
 
 
        <!-- MENU --> 
        <div 
            class="collapse navbar-collapse" 
            id="mainNavbar" 
        > 
 
            <!-- SEARCH --> 
            <div class="search-box-container position-relative"> 
 
                <div class="search-box"> 
 
                    <i class="bi bi-search"></i> 
 
                    <input 
                        type="text" 
                        id="live-search-input" 
                        placeholder="Tìm đặc sản, cơ sở..." 
                        autocomplete="off" 
                    > 
 
                </div> 
 
                <div 
                    id="live-search-results" 
                    class="list-group position-absolute w-100 d-none" 
                ></div> 
 
            </div> 
 
 
            <!-- MAIN MENU --> 
            <ul class="navbar-nav ms-auto align-items-lg-center main-menu"> 
 
                <li class="nav-item"> 
 
                    <a 
                        class="nav-link <?= $currentPage === 'trang-chu' ? 'active' : '' ?>" 
                        href="<?= $baseUrl ?>/" 
                    > 
                        Trang chủ 
                    </a> 
 
                </li> 
 
 
                <li class="nav-item"> 
 
                    <a 
                        class="nav-link <?= $currentPage === 'dac-san' ? 'active' : '' ?>" 
                        href="<?= $baseUrl ?>/dac-san.php" 
                    > 
                        Đặc sản 
                    </a> 
 
                </li> 
 
 
                <li class="nav-item"> 
 
                    <a 
                        class="nav-link <?= $currentPage === 'co-so' ? 'active' : '' ?>" 
                        href="<?= $baseUrl ?>/co-so-san-xuat.php" 
                    > 
                        Cơ sở sản xuất 
                    </a> 
 
                </li> 
 
 
                <li class="nav-item"> 
 
                    <a 
                        class="nav-link <?= $currentPage === 'ban-do' ? 'active' : '' ?>" 
                        href="<?= $baseUrl ?>/ban-do.php" 
                    > 
                        Bản đồ 
                    </a> 
 
                </li> 
 
 
                <li class="nav-item"> 
 
                    <a 
                        class="nav-link <?= $currentPage === 'bai-viet' ? 'active' : '' ?>" 
                        href="<?= $baseUrl ?>/bai-viet.php" 
                    > 
                        Câu chuyện 
                    </a> 
 
                </li> 
 
 
                <li class="nav-item"> 
 
                    <a 
                        class="nav-link <?= $currentPage === 'lien-he' ? 'active' : '' ?>" 
                        href="<?= $baseUrl ?>/lien-he.php" 
                    > 
                        Liên hệ 
                    </a> 
 
                </li> 
 
            </ul> 
 
 
            <!-- ACCOUNT --> 
            <div class="account-actions"> 
 
                <?php if (!empty($_SESSION['admin_id'])): ?> 
 
                    <a 
                        class="nav-action nav-action-primary" 
                        href="<?= $baseUrl ?>/admin/index.php" 
                    > 
                        Quản trị 
                    </a> 
 
                    <a 
                        class="nav-action nav-action-outline" 
                        href="<?= $baseUrl ?>/logout.php" 
                    > 
                        Đăng xuất 
                    </a> 
 
 
                <?php elseif (!empty($_SESSION['user_id'])): ?> 
 
                    <a 
                        class="nav-action nav-action-primary" 
                        href="<?= $baseUrl ?>/tai-khoan.php" 
                    > 
                        Tài khoản 
                    </a> 
 
                    <a 
                        class="nav-action nav-action-outline" 
                        href="<?= $baseUrl ?>/logout.php" 
                    > 
                        Đăng xuất 
                    </a> 
 
 
                <?php else: ?> 
 
                    <a 
                        class="nav-action nav-action-primary" 
                        href="<?= $baseUrl ?>/login.php" 
                    > 
                        Đăng nhập 
                    </a> 
 
                <?php endif; ?> 
 
            </div> 
 
 
            <!-- THEME TOGGLE --> 
            <button 
                type="button" 
                class="theme-toggle" 
                id="themeToggle" 
                aria-label="Chuyển chế độ sáng tối" 
                title="Chuyển chế độ sáng tối" 
            > 
                ☾ 
            </button> 
 
        </div> 
 
    </div> 
 
</nav> 
 
 
<script> 
 
document.addEventListener('DOMContentLoaded', function () { 
 
    /* ===================================================== 
       LIVE SEARCH 
    ===================================================== */ 
 
    const input = 
        document.getElementById('live-search-input'); 
 
    const resultsBox = 
        document.getElementById('live-search-results'); 
 
    const baseUrl = 
        <?= json_encode( 
            $baseUrl, 
            JSON_UNESCAPED_SLASHES | 
            JSON_UNESCAPED_UNICODE 
        ) ?>; 
 
    let timeout = null; 
 
 
    if (input && resultsBox) { 
 
        input.addEventListener('input', function () { 
 
            clearTimeout(timeout); 
 
            const query = this.value.trim(); 
 
 
            if (query.length < 2) { 
 
                resultsBox.classList.add('d-none'); 
 
                resultsBox.innerHTML = ''; 
 
                return; 
            } 
 
 
            timeout = setTimeout(() => { 
 
                fetch( 
                    baseUrl + 
                    '/api/live-search.php?q=' + 
                    encodeURIComponent(query) 
                ) 
 
                .then(res => { 
 
                    if (!res.ok) { 
                        throw new Error('API error'); 
                    } 
 
                    return res.json(); 
 
                }) 
 
                .then(data => { 
 
                    resultsBox.innerHTML = ''; 
 
 
                    if ( 
                        !Array.isArray(data) || 
                        data.length === 0 
                    ) { 
 
                        resultsBox.innerHTML = ` 
                            <div class="list-group-item small text-muted"> 
                                Không tìm thấy kết quả 
                            </div> 
                        `; 
 
                    } else { 
 
                        data.forEach(item => { 
 
                            const isDacSan = 
                                item.loai === 'dac-san'; 
 
 
                            const url = isDacSan 
 
                                ? baseUrl + 
                                  '/chi-tiet-dac-san.php?id=' + 
                                  encodeURIComponent(item.id) 
 
                                : baseUrl + 
                                  '/co-so-san-xuat.php?id=' + 
                                  encodeURIComponent(item.id); 
 
 
                            const folder = 
                                isDacSan 
                                    ? 'dac-san' 
                                    : 'co-so'; 
 
 
                            const image = item.hinh_anh 
 
                                ? baseUrl + 
                                  '/assets/uploads/' + 
                                  folder + 
                                  '/' + 
                                  item.hinh_anh 
 
                                : baseUrl + 
                                  '/assets/images/banner-ca-mau.jpg'; 
 
 
                            resultsBox.insertAdjacentHTML( 
                                'beforeend', 
                                ` 
                                <a 
                                    href="${url}" 
                                    class="list-group-item list-group-item-action d-flex align-items-center gap-2 p-2" 
                                > 
 
                                    <img 
                                        src="${image}" 
                                        alt="" 
                                        class="search-result-image" 
                                        onerror="this.src='${baseUrl}/assets/images/banner-ca-mau.jpg'" 
                                    > 
 
                                    <div class="small overflow-hidden"> 
 
                                        <div class="fw-bold text-truncate"> 
                                            ${item.ten ?? ''} 
                                        </div> 
 
                                        <span class="search-type"> 
                                            ${ 
                                                isDacSan 
                                                    ? 'Đặc sản' 
                                                    : 'Cơ sở' 
                                            } 
                                        </span> 
 
                                    </div> 
 
                                </a> 
                                ` 
                            ); 
 
                        }); 
 
                    } 
 
 
                    resultsBox.classList.remove('d-none'); 
 
                }) 
 
                .catch(() => { 
 
                    resultsBox.classList.add('d-none'); 
 
                }); 
 
            }, 300); 
 
        }); 
 
 
        document.addEventListener('click', function (e) { 
 
            if ( 
                !input.contains(e.target) && 
                !resultsBox.contains(e.target) 
            ) { 
 
                resultsBox.classList.add('d-none'); 
 
            } 
 
        }); 
 
    } 
 
 
    /* ===================================================== 
       LIGHT / DARK MODE 
    ===================================================== */ 
 
    const html = 
        document.documentElement; 
 
    const themeToggle = 
        document.getElementById('themeToggle'); 
 
 
    if (themeToggle) { 
 
        const savedTheme = 
            localStorage.getItem('cm-theme'); 
 
 
        if (savedTheme === 'dark') { 
 
            html.setAttribute( 
                'data-theme', 
                'dark' 
            ); 
 
            themeToggle.textContent = '☀'; 
 
        } else { 
 
            html.setAttribute( 
                'data-theme', 
                'light' 
            ); 
 
            themeToggle.textContent = '☾'; 
 
        } 
 
 
        themeToggle.addEventListener( 
            'click', 
            function () { 
 
                const isDark = 
                    html.getAttribute('data-theme') === 'dark'; 
 
 
                if (isDark) { 
 
                    html.setAttribute( 
                        'data-theme', 
                        'light' 
                    ); 
 
                    localStorage.setItem( 
                        'cm-theme', 
                        'light' 
                    ); 
 
                    themeToggle.textContent = '☾'; 
 
                } else { 
 
                    html.setAttribute( 
                        'data-theme', 
                        'dark' 
                    ); 
 
                    localStorage.setItem( 
                        'cm-theme', 
                        'dark' 
                    ); 
 
                    themeToggle.textContent = '☀'; 
 
                } 
 
            } 
        ); 
 
    } 
 
}); 
 
</script> 