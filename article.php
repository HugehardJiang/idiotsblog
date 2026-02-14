<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$articleId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
// Basic check
if (!$articleId) {
    header("HTTP/1.0 404 Not Found");
    die("Article not found.");
}

// Fetch article with loose restrictions first
$stmt = $pdo->prepare("SELECT a.*, 
                       u.username as author_name, 
                       c.name as category_name,
                       c.slug as category_slug
                       FROM articles a 
                       LEFT JOIN users u ON a.author_id = u.id 
                       LEFT JOIN categories c ON a.category_id = c.id
                       WHERE a.id = :id");
$stmt->execute(['id' => $articleId]);
$article = $stmt->fetch();

if (!$article) {
    header("HTTP/1.0 404 Not Found");
    die("Article not found.");
}

// Check visibility
$isPublished = (isset($article['status']) && $article['status'] === 'published') || (!isset($article['status']) && $article['is_published'] == 1);
$canView = $isPublished;

if (!$isPublished) {
    // start session if not started to check user
    if (session_status() === PHP_SESSION_NONE)
        session_start();

    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['role'] === 'admin' || $_SESSION['user_id'] == $article['author_id']) {
            $canView = true;
        }
    }
}

if (!$canView) {
    header("HTTP/1.0 404 Not Found");
    die("Article not found or not published.");
}

// Fetch Tags
$tags = [];
try {
    $stmtTags = $pdo->prepare("SELECT t.name, t.slug 
                               FROM tags t 
                               JOIN article_tags at ON t.id = at.tag_id 
                               WHERE at.article_id = ?");
    $stmtTags->execute([$articleId]);
    $tags = $stmtTags->fetchAll();
} catch (Exception $e) { /* Ignore */
}

// Track views
if (!isset($_COOKIE['viewed_' . $articleId])) {
    $pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?")->execute([$articleId]);
    setcookie('viewed_' . $articleId, '1', time() + 3600, '/');
    // Update local variable to reflect new count
    $article['views'] = ($article['views'] ?? 0) + 1;
}

// Handle Comment Submission
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $author = trim($_POST['author_name']);
    $content = trim($_POST['content']);
    $parentId = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;

    // Simple anti-spam
    if (empty($author) || empty($content)) {
        $msg = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO comments (article_id, parent_id, author_name, content) VALUES (:aid, :pid, :author, :content)");
        $stmt->execute([
            'aid' => $articleId,
            'pid' => $parentId,
            'author' => $author,
            'content' => $content
        ]);
        // Refresh to avoid resubmission
        header("Location: article.php?id=" . $articleId . "#comments");
        exit;
    }
}

// Fetch comments
$stmt = $pdo->prepare("SELECT * FROM comments WHERE article_id = :id ORDER BY created_at ASC");
$stmt->execute(['id' => $articleId]);
$allComments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$commentTree = buildCommentTree($allComments);

function renderComments($comments)
{
    echo '<div class="comments-list">';
    foreach ($comments as $comment) {
        echo '<div class="comment" id="comment-' . $comment['id'] . '">';
        echo '<div class="comment-author">' . htmlspecialchars($comment['author_name']) . '</div>';
        echo '<div class="comment-meta">' . $comment['created_at'] . '</div>';
        echo '<div class="comment-body">' . nl2br(htmlspecialchars($comment['content'])) . '</div>';
        echo '<button class="reply-btn" data-id="' . $comment['id'] . '">Reply</button>';

        if (!empty($comment['children'])) {
            echo '<div class="nested">';
            renderComments($comment['children']);
            echo '</div>';
        }
        echo '</div>';
    }
    echo '</div>';
}
?>
<?php
$pageTitle = htmlspecialchars($article['title']) . ' - Idiots';
$metaDescription = htmlspecialchars(truncate($article['summary'], 160));
$extraHead = '
    <!-- Marked for Markdown -->
    <script src="' . SITE_URL . '/assets/js/marked.min.js"></script>
    
    <!-- MathJax for LaTeX -->
    <script>
    MathJax = {
      tex: {
        inlineMath: [[\'$\', \'$\'], [\'\\\\(\', \'\\\\)\']]
      }
    };
    </script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
';

require_once 'includes/header.php';
?>

<div class="article-hero"
    style="<?php echo !empty($article['cover_image']) ? 'background-image: url(' . (strpos($article['cover_image'], 'http') === 0 ? htmlspecialchars($article['cover_image']) : SITE_URL . '/' . htmlspecialchars($article['cover_image'])) . ');' : ''; ?>">
    <div class="article-hero-overlay"></div>
    <div class="article-hero-content container">
        <div class="article-tags">
            <?php if (!empty($article['category_name'])): ?>
                <a href="<?php echo SITE_URL; ?>/category/<?php echo $article['category_slug']; ?>" class="hero-tag"
                    style="text-decoration:none;">
                    # <?php echo htmlspecialchars($article['category_name']); ?>
                </a>
            <?php else: ?>
                <span class="hero-tag"># Article</span>
            <?php endif; ?>

            <?php foreach ($tags as $tag): ?>
                <a href="<?php echo SITE_URL; ?>/tag/<?php echo $tag['slug']; ?>" class="hero-tag"
                    style="text-decoration:none; margin-left: 5px;">
                    # <?php echo htmlspecialchars($tag['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <h1 class="article-hero-title"><?php echo htmlspecialchars($article['title']); ?></h1>

        <div class="article-hero-meta">
            <div class="hero-author">
                <!-- <?php if (!empty($article['author_avatar'])): ?>
                    <img src="<?php echo strpos($article['author_avatar'], 'http') === 0 ? htmlspecialchars($article['author_avatar']) : SITE_URL . '/' . htmlspecialchars($article['author_avatar']); ?>"
                        alt="<?php echo htmlspecialchars($article['author_name']); ?>" class="hero-avatar">
                <?php endif; ?> -->
                <span
                    class="hero-author-name"><?php echo htmlspecialchars($article['author_name'] ?? 'Unknown'); ?></span>
            </div>
            <span class="hero-meta-sep">&bull;</span>
            <span class="hero-date">发布于 <?php echo date('Y-m-d H:i', strtotime($article['created_at'])); ?></span>

            <span class="hero-meta-sep">&bull;</span>
            <span class="hero-views"> <?php echo $article['views'] ?? 0; ?> 阅读</span>
        </div>
    </div>
</div>

<main class="container article-container">
    <div class="article-layout">
        <div class="article-main-column">
            <article class="article-detail-card">
                <div class="article-header">
                    <h1 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h1>
                    <div class="article-meta">
                        <!-- <?php if (!empty($article['author_avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($article['author_avatar']); ?>" alt="Author"
                                class="author-avatar">
                        <?php endif; ?> -->
                        <span
                            class="author-name"><?php echo htmlspecialchars($article['author_name'] ?? 'Unknown'); ?></span>
                        <span class="separator">&bull;</span>
                        <span
                            class="publish-date"><?php echo date('Y-m-d', strtotime($article['created_at'])); ?></span>
                        <span class="separator">&bull;</span>
                        <span class="views"><?php echo $article['views']; ?> views</span>
                    </div>
                </div>

                <div class="article-summary-box">
                    <strong>摘要：</strong> <?php echo htmlspecialchars($article['summary']); ?>
                </div>

                <!-- Hidden inputs for JS processing -->
                <div id="raw-content" style="display:none;"><?php echo htmlspecialchars($article['content']); ?></div>

                <!-- Rendered Content -->
                <div id="article-rendered" class="article-content">
                    <!-- JS will populate this -->
                </div>

                <section class="comments-section" id="comments">
                    <h3>评论</h3>
                    <?php if ($msg): ?>
                        <p style="color:red"><?php echo $msg; ?></p><?php endif; ?>

                    <?php renderComments($commentTree); ?>

                    <div id="comment-form-wrapper" style="margin-top: 30px;">
                        <h4>发表评论</h4>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div id="comment-form-container">
                                <form method="POST" class="comment-form">
                                    <input type="hidden" name="parent_id" id="parent_id" value="">
                                    <input type="hidden" name="author_name"
                                        value="<?php echo htmlspecialchars($_SESSION['username']); ?>">
                                    <div style="margin-bottom: 10px;">当前用户:
                                        <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                                    </div>
                                    <textarea name="content" rows="4" placeholder="写下你的评论..." required></textarea>
                                    <button type="submit" class="btn-submit">提交评论</button>
                                    <button id="cancel-reply" style="display:none; margin-left:10px;">取消回复</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <p>请 <a href="<?php echo SITE_URL; ?>/login.php">登录</a> 后发表评论。</p>
                        <?php endif; ?>
                    </div>
                </section>
            </article>
        </div>

        <aside class="article-sidebar">
            <div class="toc-card" id="toc-card">
                <div class="toc-header">
                    <h3>目录</h3>
                    <span class="toc-progress">0%</span>
                    <button class="toc-close-btn" id="toc-close-btn">&times;</button>
                </div>
                <nav id="toc-content" class="toc-content"></nav>
            </div>
        </aside>
    </div>

    <!-- Mobile TOC Toggle -->
    <button id="toc-mobile-toggle" class="toc-mobile-toggle">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="8" y1="6" x2="21" y2="6"></line>
            <line x1="8" y1="12" x2="21" y2="12"></line>
            <line x1="8" y1="18" x2="21" y2="18"></line>
            <line x1="3" y1="6" x2="3.01" y2="6"></line>
            <line x1="3" y1="12" x2="3.01" y2="12"></line>
            <line x1="3" y1="18" x2="3.01" y2="18"></line>
        </svg>
        <span>目录</span>
    </button>
</main>

<?php
$siteUrl = SITE_URL;
$time = time();
$extraScripts = <<<HTML
    <script src="{$siteUrl}/assets/js/marked.min.js?v={$time}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Render Markdown
            const rawContentDiv = document.getElementById("raw-content");
            if (!rawContentDiv) return;
            
            const rawContent = rawContentDiv.textContent;
            const articleContainer = document.getElementById("article-rendered");
            
            if (typeof marked === 'undefined') {
                console.error("Marked.js failed to load.");
                articleContainer.innerHTML = "<p style='color:red'>Error: Content renderer failed to load.</p>";
                return;
            }

            try {
                // Configure marked options if needed (e.g. unique IDs)
                // But we can do it post-render to be safe
                articleContainer.innerHTML = marked.parse(rawContent);

                // Open links in new tab
                articleContainer.querySelectorAll('a').forEach(link => {
                    const href = link.getAttribute('href');
                    if (href && !href.startsWith('#')) {
                        link.setAttribute('target', '_blank');
                        link.setAttribute('rel', 'noopener noreferrer');
                    }
                });
            } catch (e) {
                console.error("Error parsing markdown:", e);
                articleContainer.innerHTML = "<p style='color:red'>Error rendering content.</p>";
            }

            // 2. Generate TOC
            const tocContainer = document.getElementById('toc-content');
            const tocCard = document.getElementById('toc-card');
            const headers = articleContainer.querySelectorAll('h2, h3, h4');
            const tocList = document.createElement('ul');
            tocList.className = 'toc-list';

            if (headers.length === 0) {
                // Hide TOC if no headers
                if(tocCard) tocCard.style.display = 'none';
            } else {
                headers.forEach((header, index) => {
                    // Create ID if missing
                    if (!header.id) {
                        header.id = 'heading-' + index;
                    }

                    const li = document.createElement('li');
                    const link = document.createElement('a');
                    
                    link.href = '#' + header.id;
                    link.textContent = header.textContent;
                    link.className = 'toc-link';
                    
                    // Add indentation class based on tag name
                    const tagName = header.tagName.toLowerCase();
                    li.className = 'toc-item-' + tagName;

                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        header.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        // Update active state manually
                        document.querySelectorAll('.toc-link').forEach(l => l.classList.remove('active'));
                        link.classList.add('active');
                        // Update history
                        history.pushState(null, null, '#' + header.id);
                    });

                    li.appendChild(link);
                    tocList.appendChild(li);
                });

                tocContainer.appendChild(tocList);
            }

            // 3. Scroll Spy & Progress
            const progressSpan = document.querySelector('.toc-progress');
            const tocLinks = document.querySelectorAll('.toc-link');
            
            window.addEventListener('scroll', () => {
                // Progress Bar
                const scrollTop = window.scrollY;
                const docHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                const scrollPercent = (scrollTop / docHeight) * 100;
                if(progressSpan) progressSpan.textContent = Math.round(scrollPercent) + '%';

                // Active Link Highlighting
                let currentid = '';
                
                headers.forEach(header => {
                    const headerTop = header.getBoundingClientRect().top;
                    if (headerTop < 150) { // Offset for fixed header
                         currentid = header.id;
                    }
                });

                if (currentid) {
                    tocLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === '#' + currentid) {
                            link.classList.add('active');
                             // Optional: Scroll TOC container to keep active link in view
                             // const linkTop = link.offsetTop;
                             // tocContainer.parentElement.scrollTop = linkTop - 50; 
                        }
                    });
                }
            });
            
            // 4. Mobile TOC Toggle
            const mobileToggle = document.getElementById('toc-mobile-toggle');
           // const tocCard = document.getElementById('toc-card'); // Removed duplicate
            const closeBtn = document.getElementById('toc-close-btn');
            const sidebar = document.querySelector('.article-sidebar'); // Select the sidebar wrapper

            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    sidebar.classList.add('active'); // Add active class to sidebar
                    document.body.style.overflow = 'hidden'; // Prevent background scrolling
                });
            }

            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

            // Close on link click (mobile)
            tocContainer.addEventListener('click', function(e) {
                if (e.target.tagName === 'A' && window.innerWidth < 1024) {
                    sidebar.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
            
            // Close when clicking outside (optional, but good UX)
            sidebar.addEventListener('click', function(e) {
                if (e.target === sidebar) {
                    sidebar.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });

            // Initial trigger
            window.dispatchEvent(new Event('scroll'));
        });
    </script>
HTML;
require_once 'includes/footer.php';
?>