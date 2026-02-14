<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$article = [
    'id' => '',
    'title' => '',
    'slug' => '',
    'summary' => '',
    'content' => '',
    'status' => 'draft',
    'is_published' => 0, // Keep for backward compatibility check
    'cover_image' => '',
    'category_id' => '',
    'is_featured' => 0
];

$error = '';
$success = '';

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = :id");
    $stmt->execute(['id' => $_GET['id']]);
    $fetched = $stmt->fetch();
    if ($fetched) {
        $article = $fetched;
        // If status column doesn't exist yet (migration pending), fallback to is_published
        if (!isset($article['status'])) {
            $article['status'] = $article['is_published'] ? 'published' : 'draft';
        }
    }
}

// Handle Image Fetch AJAX
if (isset($_POST['action']) && $_POST['action'] === 'fetch_images') {
    header('Content-Type: application/json');
    $content = $_POST['content'];
    $count = 0;

    // Regex to find markdown images ![alt](http...)
    // match: ![alt](url)
    $newContent = preg_replace_callback('/!\[(.*?)\]\((https?:\/\/.*?)\)/i', function ($matches) use (&$count) {
        $alt = $matches[1];
        $url = $matches[2];

        // Skip if already local (optional check, but regex requires http so it skips relative)

        // Download
        $imgContent = @file_get_contents($url);
        if ($imgContent) {
            $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (!$ext)
                $ext = 'jpg'; // default fallback
            // clean ext
            $ext = preg_replace('/[^a-z0-9]/i', '', $ext);
            $ext = substr($ext, 0, 4);

            $filename = uniqid('ext_', true) . '.' . $ext;
            $savePath = '../assets/uploads/' . $filename;

            if (!is_dir('../assets/uploads/'))
                mkdir('../assets/uploads/', 0755, true);

            if (file_put_contents($savePath, $imgContent)) {
                $count++;
                return "![$alt](" . SITE_URL . "/assets/uploads/$filename)";
            }
        }
        return $matches[0]; // return original if failed
    }, $content);

    echo json_encode(['success' => true, 'content' => $newContent, 'count' => $count]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $title = trim($_POST['title']);
    $slug = trim($_POST['slug']);
    if (empty($slug)) {
        $slug = slugify($title);
    }
    $summary = trim($_POST['summary']);
    $content = $_POST['content'];
    $status = $_POST['status'];
    // Sync is_published for backward compatibility
    $is_published = ($status === 'published') ? 1 : 0;

    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // Handle File Upload
    $cover_image = $article['cover_image'] ?? null;
    // ... (Existing upload logic) ...
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/uploads/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);
        $fileExt = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExt, $allowed)) {
            $newFileName = uniqid('cover_', true) . '.' . $fileExt;
            $destPath = $uploadDir . $newFileName;
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $destPath)) {
                $cover_image = 'assets/uploads/' . $newFileName;
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Invalid file type.";
        }
    }

    if (!$error) {
        $category_id = !empty($_POST['category_id']) ? (int) $_POST['category_id'] : null;
        $tagsInput = trim($_POST['tags'] ?? '');

        try {
            $pdo->beginTransaction();

            if ($article['id']) {
                $sql = "UPDATE articles SET title = :title, slug = :slug, summary = :summary, content = :content, status = :status, is_published = :is_published, is_featured = :is_featured, cover_image = :cover_image, category_id = :category_id WHERE id = :id";
                $params = [
                    'title' => $title,
                    'slug' => $slug,
                    'summary' => $summary,
                    'content' => $content,
                    'status' => $status,
                    'is_published' => $is_published,
                    'is_featured' => $is_featured,
                    'cover_image' => $cover_image,
                    'category_id' => $category_id,
                    'id' => $article['id']
                ];
            } else {
                $sql = "INSERT INTO articles (title, slug, summary, content, status, is_published, is_featured, cover_image, author_id, category_id) VALUES (:title, :slug, :summary, :content, :status, :is_published, :is_featured, :cover_image, :author_id, :category_id)";
                $params = [
                    'title' => $title,
                    'slug' => $slug,
                    'summary' => $summary,
                    'content' => $content,
                    'status' => $status,
                    'is_published' => $is_published,
                    'is_featured' => $is_featured,
                    'cover_image' => $cover_image,
                    'author_id' => $_SESSION['user_id'],
                    'category_id' => $category_id
                ];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            if (!$article['id'])
                $article['id'] = $pdo->lastInsertId();

            // Handle Tags
            $pdo->prepare("DELETE FROM article_tags WHERE article_id = ?")->execute([$article['id']]);
            if (!empty($tagsInput)) {
                $tagsArray = array_map('trim', explode(',', $tagsInput));
                $tagIds = [];
                $stmtCheck = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
                $stmtInsert = $pdo->prepare("INSERT INTO tags (name, slug) VALUES (?, ?)");
                foreach ($tagsArray as $tagName) {
                    if (empty($tagName))
                        continue;
                    $stmtCheck->execute([$tagName]);
                    $tagId = $stmtCheck->fetchColumn();
                    if (!$tagId) {
                        try {
                            $stmtInsert->execute([$tagName, slugify($tagName)]);
                            $tagId = $pdo->lastInsertId();
                        } catch (Exception $e) { /* Race condition */
                        }
                    }
                    if ($tagId)
                        $tagIds[] = $tagId;
                }
                $tagIds = array_unique($tagIds);
                $stmtLink = $pdo->prepare("INSERT INTO article_tags (article_id, tag_id) VALUES (?, ?)");
                foreach ($tagIds as $tid) {
                    $stmtLink->execute([$article['id'], $tid]);
                }
            }

            $pdo->commit();
            $success = "Article saved successfully.";

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error saving: " . $e->getMessage();
        }

        $article['title'] = $title;
        $article['slug'] = $slug;
        $article['summary'] = $summary;
        $article['content'] = $content;
        $article['status'] = $status;
        $article['is_featured'] = $is_featured;
        $article['cover_image'] = $cover_image;
        $article['category_id'] = $category_id;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Article - Idiots Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        body {
            padding: 20px;
            max-width: 1600px;
            margin: 0 auto;
            background-color: var(--bg-color);
        }

        .editor-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            height: calc(100vh - 140px);
            margin-top: 20px;
        }

        .editor-pane,
        .preview-pane {
            background: var(--bg-card);
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .pane-header {
            padding: 10px 15px;
            background: rgba(0, 0, 0, 0.02);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-secondary);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        textarea.content-editor {
            flex: 1;
            width: 100%;
            padding: 20px;
            border: none;
            resize: none;
            font-family: 'Fira Code', 'Consolas', monospace;
            font-size: 15px;
            line-height: 1.6;
            outline: none;
            background: transparent;
        }

        .preview-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            font-family: var(--font-serif);
            line-height: 1.8;
            color: var(--text-primary);
        }

        .preview-content h1,
        .preview-content h2,
        .preview-content h3 {
            margin-top: 1.5em;
            margin-bottom: 0.5em;
            font-weight: 700;
            color: #111;
        }

        .preview-content img {
            max-width: 100%;
            border-radius: 4px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 15px;
            flex: 1;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        input[type="text"],
        input[type="file"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: var(--radius);
            background: #fff;
            font-family: var(--font-sans);
        }

        .btn {
            padding: 10px 24px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 600;
        }

        .btn-secondary {
            background: #fff;
            color: var(--text-primary);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .upload-tool {
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--text-secondary);
            padding: 4px 8px;
            border-radius: 4px;
        }

        .upload-tool:hover {
            background: rgba(0, 0, 0, 0.05);
            color: var(--primary-color);
        }
    </style>
</head>

<body>
    <form method="POST" enctype="multipart/form-data" id="article-form">
        <div class="top-bar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <a href="index.php" style="color: var(--text-secondary);">&larr; Dashboard</a>
                <h1 style="font-size: 1.5rem; margin: 0;"><?php echo $article['id'] ? 'Edit Article' : 'New Article'; ?>
                </h1>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <label style="margin:0; font-weight:400; font-size:0.9rem; margin-right:10px;">Status:
                    <select name="status" style="padding:5px; width:auto; border-color:#ccc;">
                        <option value="draft" <?php echo $article['status'] == 'draft' ? 'selected' : ''; ?>>Draft
                        </option>
                        <option value="pending" <?php echo $article['status'] == 'pending' ? 'selected' : ''; ?>>Pending
                        </option>
                        <option value="published" <?php echo $article['status'] == 'published' ? 'selected' : ''; ?>>
                            Published
                        </option>
                    </select>
                </label>
                <label
                    style="margin:0; font-weight: 400; font-size: 0.9rem; display: flex; align-items: center; gap: 5px;">
                    <input type="checkbox" name="is_featured" <?php echo !empty($article['is_featured']) ? 'checked' : ''; ?>>
                    Featured
                </label>
                <button type="submit" class="btn">Save Changes</button>
            </div>
        </div>

        <?php if ($error): ?>
            <div style="background:#ffebee; color:#c62828; padding:10px; border-radius:var(--radius); margin-bottom:15px;">
                <?php echo htmlspecialchars($error); ?>
            </div><?php endif; ?>
        <?php if ($success): ?>
            <div style="background:#e8f5e9; color:#2e7d32; padding:10px; border-radius:var(--radius); margin-bottom:15px;">
                <?php echo htmlspecialchars($success); ?>
            </div><?php endif; ?>

        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <label>Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($article['title']); ?>" required
                    placeholder="Article Title">
            </div>
            <div class="form-group">
                <label>Slug (Optional)</label>
                <input type="text" name="slug" value="<?php echo htmlspecialchars($article['slug']); ?>"
                    placeholder="Auto-generated if empty">
            </div>
        </div>

        <div class="form-group">
            <label>Cover Image & Summary</label>
            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <?php if (!empty($article['cover_image'])): ?>
                        <div style="margin-bottom:10px;">
                            <img src="../<?php echo htmlspecialchars($article['cover_image']); ?>"
                                style="height: 60px; border-radius: 4px; border: 1px solid #eee; object-fit: cover;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="cover_image" accept="image/*" style="font-size: 0.9rem;">
                </div>
                <div style="flex: 2;">
                    <input type="text" name="summary" value="<?php echo htmlspecialchars($article['summary']); ?>"
                        placeholder="Short summary/abstract" style="width: 100%;">
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Category</label>
                <div style="display: flex; gap: 10px;">
                    <select name="category_id" id="category-select"
                        style="padding: 12px; border: 1px solid rgba(0,0,0,0.1); border-radius: var(--radius); background: #fff; flex: 1;">
                        <option value="">Select Category...</option>
                        <?php
                        try {
                            $cats = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
                            foreach ($cats as $cat) {
                                $selected = ($article['category_id'] ?? '') == $cat['id'] ? 'selected' : '';
                                echo '<option value="' . $cat['id'] . '" ' . $selected . '>' . htmlspecialchars($cat['name']) . '</option>';
                            }
                        } catch (Exception $e) {
                        }
                        ?>
                    </select>
                    <button type="button" id="quick-add-category" class="btn btn-secondary"
                        title="Quick Add Category">+</button>
                </div>
            </div>
            <div class="form-group">
                <label>Tags (Comma separated)</label>
                <?php
                $tagStr = '';
                if ($article['id']) {
                    try {
                        $stmt = $pdo->prepare("SELECT t.name FROM tags t JOIN article_tags at ON t.id = at.tag_id WHERE at.article_id = ?");
                        $stmt->execute([$article['id']]);
                        $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        $tagStr = implode(', ', $tags);
                    } catch (Exception $e) {
                    }
                }
                ?>
                <input type="text" name="tags" value="<?php echo htmlspecialchars($tagStr); ?>"
                    placeholder="e.g. Technology, Life, Coding">
            </div>
        </div>

        <div class="editor-container">
            <!-- Left: Editor -->
            <div class="editor-pane">
                <div class="pane-header">
                    <span>MARKDOWN</span>
                    <div style="display:flex; gap:15px; align-items:center;">
                        <span class="upload-tool" id="fetch-img-btn" style="color:var(--primary-color);">⬇ Fetch
                            External Images</span>
                        <div class="upload-tool" id="upload-btn">
                            <span>📷 Upload Image</span>
                            <input type="file" id="editor-file-input" style="display: none;" accept="image/*">
                        </div>
                        <span id="upload-status" style="font-size: 11px;"></span>
                    </div>
                </div>
                <textarea name="content" id="content-textarea" class="content-editor" required
                    placeholder="# Start writing..."><?php echo htmlspecialchars($article['content']); ?></textarea>
            </div>

            <!-- Right: Preview -->
            <div class="preview-pane">
                <div class="pane-header">
                    <span>LIVE PREVIEW</span>
                </div>
                <div id="preview-div" class="preview-content"></div>
            </div>
        </div>
    </form>

    <script>
        const textarea = document.getElementById('content-textarea');
        const previewDiv = document.getElementById('preview-div');
        const uploadBtn = document.getElementById('upload-btn');
        const fileInput = document.getElementById('editor-file-input');
        const statusSpan = document.getElementById('upload-status');
        const fetchImgBtn = document.getElementById('fetch-img-btn');

        function updatePreview() {
            previewDiv.innerHTML = marked.parse(textarea.value);
        }
        updatePreview();
        textarea.addEventListener('input', updatePreview);

        // Sync Scrolling (Basic)
        textarea.addEventListener('scroll', () => {
            const percentage = textarea.scrollTop / (textarea.scrollHeight - textarea.clientHeight);
            previewDiv.scrollTop = percentage * (previewDiv.scrollHeight - previewDiv.clientHeight);
        });

        // Upload Logic
        uploadBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (!file) return;
            const formData = new FormData();
            formData.append('file', file);
            statusSpan.textContent = '...';
            fetch('upload_image.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const str = `![Image](${data.url})`;
                        const start = textarea.selectionStart;
                        textarea.value = textarea.value.slice(0, start) + str + textarea.value.slice(textarea.selectionEnd);
                        statusSpan.textContent = '✓';
                        updatePreview();
                        setTimeout(() => statusSpan.textContent = '', 2000);
                    } else {
                        alert(data.message);
                        statusSpan.textContent = '✗';
                    }
                })
                .catch(err => {
                    console.error(err);
                    statusSpan.textContent = 'Err';
                })
                .finally(() => {
                    fileInput.value = '';
                });
        });

        // Fetch External Images Logic
        // Quick Add Category Logic
        document.getElementById('quick-add-category').addEventListener('click', () => {
            const newCat = prompt("Enter new category name:");
            if (newCat && newCat.trim() !== "") {
                const formData = new FormData();
                formData.append('name', newCat.trim());

                fetch('api/category.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const select = document.getElementById('category-select');
                            const option = document.createElement('option');
                            option.value = data.id;
                            option.textContent = data.name; // Already escaped in PHP but textContent is safe
                            option.selected = true;
                            select.appendChild(option);
                            alert("Category added!");
                        } else {
                            alert("Error: " + data.message);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert("Network error.");
                    });
            }
        });

        fetchImgBtn.addEventListener('click', () => {
            if (!confirm("This will download all external images in the article to the local server. Continue?")) return;

            statusSpan.textContent = 'Downloading...';
            const formData = new FormData();
            formData.append('action', 'fetch_images');
            formData.append('content', textarea.value);

            fetch('editor.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        textarea.value = data.content;
                        updatePreview();
                        statusSpan.textContent = `Done (${data.count} images)`;
                        alert(`Successfully downloaded ${data.count} images!`);
                        setTimeout(() => statusSpan.textContent = '', 3000);
                    } else {
                        alert("Error downloading images.");
                        statusSpan.textContent = 'Error';
                    }
                })
                .catch(err => {
                    console.error(err);
                    statusSpan.textContent = 'Error';
                    alert("Network error.");
                });
        });
    </script>
</body>

</html>