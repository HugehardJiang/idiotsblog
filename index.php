<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Initial fetch moved to inside main for pagination logic
?>
<?php
$pageTitle = 'idiots - 学术垃圾的独白';
require_once 'includes/header.php';
?>

<?php
// Parameters
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = 9; // Articles per page
$offset = ($page - 1) * $limit;

// Build Query
$sql = "SELECT SQL_CALC_FOUND_ROWS a.*, u.username as author_name, u.avatar as author_avatar 
        FROM articles a 
        LEFT JOIN users u ON a.author_id = u.id 
        WHERE a.is_published = 1";
$params = [];

if ($searchQuery) {
    $sql .= " AND (a.title LIKE :q OR a.content LIKE :q)";
    $params['q'] = '%' . $searchQuery . '%';
}

$sql .= " ORDER BY a.created_at DESC LIMIT :limit OFFSET :offset";

// Prepare & Execute
$stmt = $pdo->prepare($sql);
if ($searchQuery) {
    $stmt->bindValue(':q', $params['q']);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

// Get Total Count
$totalStmt = $pdo->query("SELECT FOUND_ROWS()");
$totalArticles = $totalStmt->fetchColumn();
$totalPages = ceil($totalArticles / $limit);

// Hero Logic: Only on Page 1 and No Search
$heroArticle = null;
$displayArticles = $articles;

if ($page === 1 && empty($searchQuery) && !empty($articles)) {
    // Check if any featured article exists in the fetched batch
    // Or strictly find a featured article from DB for Hero? 
    // Logic change: Let's extract the first featured article from the current page 
    // OR separate query for Hero safely?
    // To keep pagination consistent, let's just create a separate Hero query if Page 1
    // But that messes up the count.
    // Let's stick to the previous logic: Filter from the fetched list

    $featuredKey = null;
    foreach ($displayArticles as $k => $a) {
        if (!empty($a['is_featured'])) {
            $heroArticle = $a;
            $featuredKey = $k;
            break;
        }
    }

    if ($heroArticle) {
        unset($displayArticles[$featuredKey]);
        // Re-index not strictly needed for foreach but good for cleanliness
        // $displayArticles = array_values($displayArticles);
    } else {
        // No featured in this batch, maybe pick first as hero? 
        // Or just no hero.
        // Let's picking the first one as Hero if no featured found, to keep layout consistent?
        // "If has featured, pick first one as Hero" was previous logic.
        // If we strictly want Hero on homepage, we can pick the first one.
        if (!empty($displayArticles)) {
            $heroArticle = array_shift($displayArticles);
        }
    }
}
?>

<?php if ($heroArticle): ?>
    <section class="hero-section">
        <div class="hero-container">
            <div class="hero-text-col">
                <span class="hero-tag">Featured</span>
                <h2 class="hero-title"><a
                        href="<?php echo SITE_URL; ?>/article/<?php echo $heroArticle['id']; ?>"><?php echo htmlspecialchars($heroArticle['title']); ?></a>
                </h2>
                <p class="hero-summary"><?php echo htmlspecialchars(truncate($heroArticle['summary'], 200)); ?></p>
                <a href="<?php echo SITE_URL; ?>/article/<?php echo $heroArticle['id']; ?>" class="hero-btn">Read More
                    &rarr;</a>
            </div>
            <div class="hero-image-col"
                style="background-image: url('<?php echo htmlspecialchars($heroArticle['cover_image'] ? (strpos($heroArticle['cover_image'], 'http') === 0 ? $heroArticle['cover_image'] : SITE_URL . '/' . $heroArticle['cover_image']) : 'https://picsum.photos/1200/800?grayscale'); ?>');">
            </div>
        </div>
    </section>
<?php endif; ?>

<main class="container">
    <?php if ($searchQuery): ?>
        <div style="margin-bottom: 20px;">
            <h2>Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h2>
            <p><?php echo $totalArticles; ?> articles found.</p>
        </div>
    <?php endif; ?>

    <section class="latest-articles">
        <?php if (empty($displayArticles) && !$heroArticle): ?>
            <p>No articles found.</p>
        <?php else: ?>
            <div class="article-grid">
                <?php foreach ($displayArticles as $article): ?>
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
                                <span><?php echo htmlspecialchars($article['author_name'] ?? 'Unknown'); ?></span>
                                <span>&bull;</span>
                                <span><?php echo date('Y-m-d', strtotime($article['created_at'])); ?></span>
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
                    $qs = $searchQuery ? '&q=' . urlencode($searchQuery) : '';
                    ?>

                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $qs; ?>" class="page-link">&laquo; Prev</a>
                    <?php else: ?>
                        <span class="page-link disabled">&laquo; Prev</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $qs; ?>"
                            class="page-link <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $qs; ?>" class="page-link">Next &raquo;</a>
                    <?php else: ?>
                        <span class="page-link disabled">Next &raquo;</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>