<?php
$pageTitle = '关于我 - idiots';
require_once 'includes/db.php';
require_once 'includes/header.php';
?>

<style>
    /* About Page Specific Styles */
    .about-header {
        margin-bottom: 50px;
        text-align: center;
    }

    .about-intro {
        margin-bottom: 60px;
        font-size: 1.1rem;
        line-height: 1.8;
        color: var(--text-secondary);
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Hawchi Card */
    .project-card-hawchi {
        background-color: #1e293b;
        /* Dark slate */
        color: #fff;
        border-radius: 12px;
        padding: 40px;
        margin-bottom: 40px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .hawchi-label {
        color: #60a5fa;
        /* Light blue */
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .hawchi-title {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 20px;
        color: #fff;
    }

    .hawchi-desc {
        color: #94a3b8;
        /* Slate 400 */
        margin-bottom: 30px;
        max-width: 600px;
        font-size: 1.1rem;
    }

    .hawchi-tags {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 40px;
    }

    .hawchi-tag {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.9rem;
        color: #e2e8f0;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .hawchi-btn {
        background: #fff;
        color: #0f172a;
        padding: 12px 28px;
        border-radius: 30px;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: transform 0.2s;
    }

    .hawchi-btn:hover {
        transform: translateY(-2px);
        color: #0f172a;
        /* Override link hover */
    }

    /* EnGo Card */
    .project-card-engo {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        display: flex;
        margin-bottom: 60px;
    }

    .engo-content {
        flex: 1;
        padding: 50px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .engo-image {
        flex: 1;
        background: #b1d8c5;
        /* Soft Green */
        background-image: url('https://images.unsplash.com/photo-1513475382585-d06e58bcb0e0?auto=format&fit=crop&q=80&w=1000');
        background-size: cover;
        background-position: center;
        position: relative;
    }

    .engo-image::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(85, 139, 114, 0.3);
        /* Green tint */
    }

    .engo-label {
        color: #3d8c65;
        /* Dark Green */
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .engo-title {
        font-size: 2.5rem;
        color: #1a1a1a;
        margin-bottom: 20px;
        font-weight: 700;
    }

    .engo-desc {
        color: #555;
        font-size: 1.1rem;
        margin-bottom: 30px;
        line-height: 1.7;
    }

    .engo-tags {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 35px;
    }

    .engo-tag {
        background: #eef7f2;
        color: #2f6f4e;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .engo-btn {
        background: #3d8c65;
        color: #fff;
        padding: 12px 28px;
        border-radius: 30px;
        font-weight: 600;
        text-decoration: none;
        align-self: flex-start;
        transition: background 0.2s;
    }

    .engo-btn:hover {
        background: #2f6f4e;
        color: #fff;
    }

    /* Wildhens Section */
    .wildhens-section {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 50px;
        text-align: center;
        margin-bottom: 40px;
    }

    .wildhens-logo {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 15px;
        display: inline-block;
        background: -webkit-linear-gradient(45deg, #090979, #00d4ff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .sso-badge {
        display: inline-block;
        background: #e0e7ff;
        color: #4338ca;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: 10px;
        vertical-align: middle;
    }

    @media (max-width: 768px) {
        .project-card-engo {
            flex-direction: column;
        }

        .engo-image {
            min-height: 250px;
        }
    }
</style>

<main class="container">

    <div class="about-header">
        <h1 style="font-size: 3rem; margin-bottom: 20px;">关于我</h1>
        <div class="about-intro">
            <p>你好！我是一个来自东北大学信息学院的大一学生。</p>
            <p>平时喜欢研究一些没用的小东西，也喜欢用 AI 写一些小网站来帮助自己和同学们。</p>
            <p>在这个信息爆炸的时代，我希望能做一些真正有温度、有价值的产品。</p>
            <p style="margin-top: 20px;">
                关于 <strong>idiots</strong> 这个名字，我希望自己能始终保持一颗“愚者”的好奇心，去探索未知，分享新知。<br>
                网站 Logo 的灵感则来自于成语“方枘圆凿”，寓意着即使我们与身边的世界显得有些格格不入，但依然努力前行。
            </p>
        </div>
    </div>

    <!-- Hawchi Project -->
    <div class="project-card-hawchi">
        <div class="hawchi-label">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
            </svg>
            Online Exam Simulator
        </div>
        <h2 class="hawchi-title">Hawchi 自主刷题系统</h2>
        <p class="hawchi-desc">
            专为被机考折磨的东大学生打造。<br>
            1:1 还原真实机考环境，这里有你需要的题库，也有和你一样在深夜奋斗的战友。
        </p>
        <div class="hawchi-tags">
            <div class="hawchi-tag">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                大学化学
            </div>
            <div class="hawchi-tag">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                健康教育
            </div>
            <div class="hawchi-tag">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                毛概题库
            </div>
            <div class="hawchi-tag">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                </svg>
                评论吐槽
            </div>
        </div>
        <a href="https://www.hawchi.com/" target="_blank" class="hawchi-btn">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"
                    clip-rule="evenodd" />
            </svg>
            进入系统刷题
        </a>
    </div>

    <!-- EnGo Project -->
    <div class="project-card-engo">
        <div class="engo-content">
            <div class="engo-label">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                </svg>
                English Booster
            </div>
            <h2 class="engo-title">EnGo 英语突击系统</h2>
            <p class="engo-desc">
                不要假装背单词，结果不会陪你演戏。<br>
                针对东大英语教材定制，精准解决期末考试与四六级“三进翻译”痛点。
            </p>
            <div class="engo-tags">
                <span class="engo-tag">📖 最新教科书词表</span>
                <span class="engo-tag">🖋️ 三进翻译专项</span>
                <span class="engo-tag">☁️ 自传词表</span>
            </div>
            <a href="https://eng.hawchi.com/" target="_blank" class="engo-btn">开始背单词 &rarr;</a>
        </div>
        <div class="engo-image">
            <!-- Background Image via CSS -->
        </div>
    </div>

    <!-- Wildhens Section -->
    <div class="wildhens-section">
        <h2 style="font-size: 2rem; margin-bottom: 20px;">
            学习与生活方式主站点
            <span class="wildhens-logo">Wildhens</span>
        </h2>
        <p style="margin-bottom: 30px; font-size: 1.1rem; color: #555; line-height: 1.8;">
            名字灵感来源于“野鸡大学”的梗。<br>
            我希望通过我这种“民办大学”的举动，帮到更多的人提高自习效率。<br>
            同时，我开发的所有网站都可以用 <strong>Wildhens 统一身份认证</strong> (SSO) 登录。
        </p>
        <div style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
            <a href="https://www.wildhens.com/" target="_blank"
                style="padding: 12px 30px; background: #2c3e50; color: #fff; border-radius: 6px; font-weight: 600;">Visit
                Wildhens</a>
            <a href="https://www.wildhens.com/post.php?id=1"
                style="padding: 12px 30px; border: 2px solid #2c3e50; color: #2c3e50; border-radius: 6px; font-weight: 600;">接入
                SSO 文档</a>
        </div>
        <div style="margin-top: 30px; font-size: 0.9rem; color: #888;">
            欢迎其他小伙伴和站长接入我的 SSO 登录系统，共同构建开放生态。
        </div>
    </div>

</main>

<?php require_once 'includes/footer.php'; ?>