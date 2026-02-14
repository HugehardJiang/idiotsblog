<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

$pageTitle = 'Archives - Idiots';
$filterType = '';
$filterValue = '';
$filterName = '';

// Determine Filter
if (!empty($_GET['category'])) {
    $filterType = 'category';
    $slug = $_GET['category'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
    $stmt->execute([$slug]);
    $cat = $stmt->fetch();
    if ($cat) {
        $filterValue = $cat['id'];
        $filterName = $cat['name'];
        $pageTitle = "Category: " . htmlspecialchars($cat['name']) . " - Idiots";
    }
} elseif (!empty($_GET['tag'])) {
    $filterType = 'tag';
    $slug = $_GET['tag'];
    $stmt = $pdo->prepare("SELECT * FROM tags WHERE slug = ?");
    $stmt->execute([$slug]);
    $tag = $stmt->fetch();
    if ($tag) {
        $filterValue = $tag['id'];
        $filterName = $tag['name'];
        $pageTitle = "Tag: " . htmlspecialchars($tag['name']) . " - Idiots";
    }
}

require_once 'includes/header.php';

// Pagination
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Query Logic
$params = [];
$sql = "SELECT SQL_CALC_FOUND_ROWS a.*, u.username as author_name, u.avatar as author_avatar 
        FROM articles a 
        LEFT JOIN users u ON a.author_id = u.id ";

if ($filterType === 'category') {
    $sql .= " WHERE a.category_id = :cat_id AND a.is_published = 1";
    $params['cat_id'] = $filterValue;
} elseif ($filterType === 'tag') {
    $sql .= " JOIN article_tags at ON a.id = at.article_id 
              WHERE at.tag_id = :tag_id AND a.is_published = 1";
    $params['tag_id'] = $filterValue;
} else {
    $sql .= " WHERE a.is_published = 1";
}

$sql .= " ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
if ($filterType === 'category') {
    $stmt->bindValue(':cat_id', $filterValue, PDO::PARAM_INT);
} elseif ($filterType === 'tag') {
    $stmt->bindValue(':tag_id', $filterValue, PDO::PARAM_INT);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

$totalStmt = $pdo->query("SELECT FOUND_ROWS()");
$totalArticles = $totalStmt->fetchColumn();
$totalPages = ceil($totalArticles / $limit);
?>

<main class="container" style="min-height: 600px; padding-top: 40px;">
    <div style="margin-bottom: 40px; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 20px;">
        <h5 style="text-transform: uppercase; letter-spacing: 2px; color: var(--text-secondary); font-size: 0.9rem;">
            Archive</h5>
        <h1 style="font-family: var(--font-serif); font-size: 2.5rem; margin-top: 10px;">
            <?php if ($filterName): ?>
                <?php echo htmlspecialchars($filterName); ?>
            <?php else: ?>
                All Articles
            <?php endif; ?>
        </h1>
        <p style="color: var(--text-secondary); margin-top: 10px;">
            <?php echo $totalArticles; ?> articles found.
        </p>
    </div>

    <section class="latest-articles">
        <?php if (empty($articles)): ?>
            <p>No articles found in this section.</p>
        <?php else: ?>
            <div class="article-grid">
                <?php foreach ($articles as $article): ?>
                    <article class="article-card">
                        <?php if (!empty($article['cover_image'])): ?>
                            <a href="<?php echo SITE_URL; ?>/article/<?php echo $article['id']; ?>"
                                class="article-card-img-wrapper">
                                <img src="<?php echo strpos($article['cover_image'], 'http') === 0 ? htmlspecialchars($article['cover_image']) : SITE_URL . '/' . htmlspecialchars($article['cover_image']); ?>"
                                    loading="lazy" alt="Cover" class="article-card-img-lazy">
                            </a>
                        <?php endif; ?>
                        <div class="article-card-content">
                            <div class="article-meta">
                                <?php if (!empty($article['author_avatar'])): ?>
                                    <img src="<?php echo strpos($article['author_avatar'], 'http') === 0 ? htmlspecialchars($article['author_avatar']) : SITE_URL . '/' . htmlspecialchars($article['author_avatar']); ?>"
                                        alt="<?php echo htmlspecialchars($article['author_name']); ?>"
                                        style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                                <?php endif; ?>
                                <span>
                                    <?php echo htmlspecialchars($article['author_name'] ?? 'Unknown'); ?>
                                </span>
                                <span>&bull;</span>
                                <span>
                                    <?php echo date('Y-m-d', strtotime($article['created_at'])); ?>
                                </span>
                            </div>
                            <h3><a href="<?php echo SITE_URL; ?>/article/<?php echo $article['id']; ?>">
                                    <?php echo htmlspecialchars($article['title']); ?>
                                </a></h3>
                            <div class="article-summary">
                                <?php echo htmlspecialchars(truncate($article['summary'], 100)); ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php
                    $baseUrl = SITE_URL . '/archive';
                    if ($filterType === 'category') {
                        $stmt = $pdo->prepare("SELECT slug FROM categories WHERE id = ?");
                        $stmt->execute([$filterValue]);
                        $slugPromise = $stmt->fetchColumn(); 
                        // Note: We already have slug from $_GET['category'] usually? 
                        // In line 13: $slug = $_GET['category'];
                        // Let's use that if available, or re-fetch.
                        // Actually line 13 logic: $slug = $_GET['category'];
                        $baseUrl = SITE_URL . '/category/' . (isset($_GET['category']) ? $_GET['category'] : $slugPromise);
                    } elseif ($filterType === 'tag') {
                        $baseUrl = SITE_URL . '/tag/' . (isset($_GET['tag']) ? $_GET['tag'] : '');
                    }
                    ?>

                    <?php if ($page > 1): ?>
                        <a href="<?php echo $baseUrl; ?>?page=<?php echo $page - 1; ?>" class="page-link">&laquo; Prev</a>
                    <?php else: ?>
                        <span class="page-link disabled">&laquo; Prev</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="<?php echo $baseUrl; ?>?page=<?php echo $i; ?>"
                            class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo $baseUrl; ?>?page=<?php echo $page + 1; ?>" class="page-link">Next &raquo;</a>
                    <?php else: ?>
                        <span class="page-link disabled">Next &raquo;</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>