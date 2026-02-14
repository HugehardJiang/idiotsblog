<?php
// Default values if not set
if (!isset($pageTitle)) {
    $pageTitle = 'Idiots - Academic Blog';
}
if (!isset($pathPrefix)) {
    $pathPrefix = ''; // Default to current directory
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo htmlspecialchars($pageTitle); ?>
    </title>
    <?php if (isset($metaDescription)): ?>
        <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <?php endif; ?>
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>/favicon.ico">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">

    <?php if (isset($extraHead))
        echo $extraHead; ?>

    <style>
        @media (max-width: 768px) {
            .site-header {
                transition: transform 0.3s ease-in-out;
            }

            .site-header.hidden {
                transform: translateY(-100%);
            }
        }
    </style>
</head>

<body>
    <header class="site-header">
        <div class="container header-inner">
            <div class="logo">
                <a href="<?php echo SITE_URL; ?>/index.php"><img src="<?php echo SITE_URL; ?>/logos.png"
                        alt="Idiots Logo" style="height: 50px;"></a>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>/index.php">首页</a></li>
                    <?php if (!defined('ENABLE_ABOUT_PAGE') || ENABLE_ABOUT_PAGE): ?>
                        <li><a href="<?php echo SITE_URL; ?>/about">关于</a></li>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="user-menu-item">
                            <a href="#" class="user-toggle">
                                <!-- <?php if (!empty($_SESSION['avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars($_SESSION['avatar']); ?>" class="user-avatar">
                                <?php endif; ?> -->
                                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9"></polyline>
                                </svg>
                            </a>
                            <ul class="dropdown-menu">
                                <!-- <li><a href="<?php echo SITE_URL; ?>/my_articles.php">我的文章</a></li> -->
                                <li><a href="<?php echo SITE_URL; ?>/settings.php">个人设置</a></li>
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <li><a href="<?php echo SITE_URL; ?>/admin/index.php">管理后台</a></li>
                                <?php endif; ?>
                                <li class="dropdown-divider"><a href="<?php echo SITE_URL; ?>/logout.php">退出</a></li>
                            </ul>

                        </li>
                    <?php else: ?>
                        <li><a href="<?php echo SITE_URL; ?>/login.php">登录 / 注册</a></li>
                    <?php endif; ?>
                    <li><!-- Search -->
                        <div class="search-container">
                            <form action="<?php echo SITE_URL; ?>/index.php" method="GET"
                                style="display: flex; align-items: center;">
                                <input type="text" name="q" class="search-input" id="search-input"
                                    placeholder="Search...">
                                <button type="button" class="search-btn" id="search-toggle">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Search Toggle Logic
            const searchToggle = document.getElementById('search-toggle');
            const searchInput = document.getElementById('search-input');

            if (searchToggle && searchInput) {
                searchToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (searchInput.classList.contains('active')) {
                        if (searchInput.value.trim() !== '') {
                            searchInput.parentElement.submit();
                        } else {
                            searchInput.classList.remove('active');
                            searchInput.blur();
                        }
                    } else {
                        searchInput.classList.add('active');
                        searchInput.focus();
                    }
                });

                document.addEventListener('click', function (e) {
                    if (!searchInput.contains(e.target) && !searchToggle.contains(e.target) && searchInput.value === '') {
                        searchInput.classList.remove('active');
                    }
                });

                searchInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        searchInput.parentElement.submit();
                    }
                });
            }

            // Auto-hide Header on Scroll (Mobile Only Logic via CSS class, but JS runs always)
            let lastScrollTop = 0;
            const header = document.querySelector('.site-header');
            const scrollThreshold = 50;

            window.addEventListener('scroll', function () {
                let scrollTop = window.pageYOffset || document.documentElement.scrollTop;

                // Toggle scrolled class for glass effect enhancement
                if (scrollTop > 10) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }

                if (Math.abs(lastScrollTop - scrollTop) <= 5) return;

                if (scrollTop > lastScrollTop && scrollTop > scrollThreshold) {
                    // Scroll Down
                    header.classList.add('hidden');
                } else {
                    // Scroll Up
                    header.classList.remove('hidden');
                }
                lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
            }, false);
        });
    </script>