<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Initial Logic handled in main body now for search support

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM articles WHERE id = :id");
    $stmt->execute(['id' => $id]);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Idiots Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Admin specific styles override or addition */
        body {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #d00;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
        }

        .btn {
            padding: 5px 10px;
            background: #333;
            color: white;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .btn-new {
            background: #d00;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f9f9f9;
        }

        .actions a {
            margin-right: 10px;
        }

        .status-draft {
            color: orange;
            font-weight: bold;
        }

        .status-pending {
            color: blue;
            font-weight: bold;
        }

        .status-published {
            color: green;
            font-weight: bold;
        }

        .alert-box {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <header>
        <h1>Idiots Dashboard</h1>
        <nav>
            <a href="../index.php" target="_blank">View Site</a> |
            <a href="comments.php">Comments</a> |
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <?php
    // Handle Batch Actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['selected_ids'])) {
        $ids = array_map('intval', $_POST['selected_ids']);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $action = $_POST['action'];

            if ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM articles WHERE id IN ($placeholders)");
                $stmt->execute($ids);
            } elseif ($action === 'publish') {
                $stmt = $pdo->prepare("UPDATE articles SET status = 'published', is_published = 1 WHERE id IN ($placeholders)");
                $stmt->execute($ids);
            } elseif ($action === 'draft') {
                $stmt = $pdo->prepare("UPDATE articles SET status = 'draft', is_published = 0 WHERE id IN ($placeholders)");
                $stmt->execute($ids);
            }
            header("Location: index.php");
            exit;
        }
    }

    $searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

    // Fetch articles with search
    $sql = "SELECT id, title, created_at, status, is_published FROM articles";
    $params = [];
    if ($searchQuery) {
        $sql .= " WHERE title LIKE :q";
        $params['q'] = '%' . $searchQuery . '%';
    }
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $articles = $stmt->fetchAll();

    // Filter Pending
    // Note: Pending filter is separate from main article list display logic if we want to keep it always visible at top?
    // For now, let's keep the pending alert based on ALL pending articles, not just searched ones.
    $pendingStmt = $pdo->query("SELECT count(*) FROM articles WHERE status = 'pending'");
    $pendingCount = $pendingStmt->fetchColumn();
    ?>

    <?php if ($pendingCount > 0): ?>
        <div class="alert-box">
            <strong>🔔 You have <?php echo $pendingCount; ?> pending articles to review!</strong>
        </div>
    <?php endif; ?>

    <div
        style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; gap: 10px; align-items: center;">
            <h2>Articles</h2>
            <form method="GET" style="display: flex;">
                <input type="text" name="q" placeholder="Search titles..."
                    value="<?php echo htmlspecialchars($searchQuery); ?>"
                    style="padding: 5px; border: 1px solid #ddd; border-radius: 4px 0 0 4px;">
                <button type="submit" class="btn" style="border-radius: 0 4px 4px 0;">Search</button>
                <?php if ($searchQuery): ?>
                    <a href="index.php" class="btn" style="margin-left: 5px; background: #999;">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <a href="editor.php" class="btn btn-new">+ New Article</a>
    </div>

    <form method="POST" id="batch-form">
        <div style="margin-bottom: 10px; display: none;" id="batch-actions">
            Actions:
            <button type="submit" name="action" value="publish" class="btn" style="background: green;">Publish
                Selected</button>
            <button type="submit" name="action" value="draft" class="btn" style="background: orange;">Revert to
                Draft</button>
            <button type="submit" name="action" value="delete" class="btn" style="background: red;"
                onclick="return confirm('Are you sure you want to delete selected items?')">Delete Selected</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 30px;"><input type="checkbox" id="select-all"></th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($articles as $article):
                    // Fallback for status if migration not run
                    $status = $article['status'] ?? ($article['is_published'] ? 'published' : 'draft');
                    ?>
                    <tr>
                        <td><input type="checkbox" name="selected_ids[]" value="<?php echo $article['id']; ?>"
                                class="item-checkbox"></td>
                        <td>
                            <?php echo htmlspecialchars($article['title']); ?>
                        </td>
                        <td>
                            <span class="status-<?php echo $status; ?>">
                                <?php echo ucfirst($status); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('Y-m-d', strtotime($article['created_at'])); ?>
                        </td>
                        <td class="actions">
                            <a href="editor.php?id=<?php echo $article['id']; ?>">Edit</a>
                            <a href="?delete=<?php echo $article['id']; ?>"
                                onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($articles)): ?>
                    <tr>
                        <td colspan="5">No articles found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selectAll = document.getElementById('select-all');
            const checkboxes = document.querySelectorAll('.item-checkbox');
            const batchActions = document.getElementById('batch-actions');

            function toggleBatchActions() {
                const anyChecked = Array.from(checkboxes).some(cb => cb.checked);
                batchActions.style.display = anyChecked ? 'block' : 'none';
            }

            selectAll.addEventListener('change', function () {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
                toggleBatchActions();
            });

            checkboxes.forEach(cb => {
                cb.addEventListener('change', toggleBatchActions);
            });
        });
    </script>
</body>

</html>