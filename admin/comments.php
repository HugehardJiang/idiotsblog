<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Handle Batch Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['selected_ids'])) {
    $ids = array_map('intval', $_POST['selected_ids']);
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        header("Location: comments.php");
        exit;
    }
}

// Handle Single Deletion
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = :id");
    $stmt->execute(['id' => $id]);
    header("Location: comments.php");
    exit;
}

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Fetch comments with article info
$sql = "SELECT c.id, c.author_name, c.content, c.created_at, a.title AS article_title 
            FROM comments c 
            JOIN articles a ON c.article_id = a.id";

$params = [];
if ($searchQuery) {
    $sql .= " WHERE c.content LIKE :q OR c.author_name LIKE :q";
    $params['q'] = '%' . $searchQuery . '%';
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$comments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments - Idiots Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
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
            background: #f4f4f4;
        }

        .action-del {
            color: red;
        }

        .btn {
            padding: 5px 10px;
            background: #333;
            color: white;
            text-decoration: none;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <h1>Comments Management</h1>
    <div
        style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <a href="index.php">&larr; Back to Dashboard</a>

        <form method="GET" style="display: flex;">
            <input type="text" name="q" placeholder="Search content/author..."
                value="<?php echo htmlspecialchars($searchQuery); ?>"
                style="padding: 5px; border: 1px solid #ddd; border-radius: 4px 0 0 4px;">
            <button type="submit" class="btn" style="border-radius: 0 4px 4px 0;">Search</button>
            <?php if ($searchQuery): ?>
                <a href="comments.php" class="btn" style="margin-left: 5px; background: #999;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <form method="POST" id="batch-form">
        <div style="margin-bottom: 10px; display: none;" id="batch-actions">
            <button type="submit" name="action" value="delete" class="btn" style="background: red;"
                onclick="return confirm('Delete selected comments?')">Delete Selected</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 30px;"><input type="checkbox" id="select-all"></th>
                    <th>Author</th>
                    <th>Comment</th>
                    <th>Article</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td><input type="checkbox" name="selected_ids[]" value="<?php echo $comment['id']; ?>"
                                class="item-checkbox"></td>
                        <td>
                            <?php echo htmlspecialchars($comment['author_name']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars(substr($comment['content'], 0, 50)) . '...'; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($comment['article_title']); ?>
                        </td>
                        <td>
                            <?php echo $comment['created_at']; ?>
                        </td>
                        <td>
                            <a href="?delete=<?php echo $comment['id']; ?>" class="action-del"
                                onclick="return confirm('Delete this comment?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($comments)): ?>
                    <tr>
                        <td colspan="6">No comments found.</td>
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