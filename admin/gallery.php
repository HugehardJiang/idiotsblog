<?php
require_once '../includes/db.php';
// session_start();
// Check admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Image Gallery - Idiots Admin';
$pathPrefix = '../';
require_once '../includes/header.php';

// Get images
$uploadDir = '../assets/uploads/';
$images = [];
$baseUrl = rtrim(SITE_URL, '/');

if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file)) {
            $images[] = [
                'name' => $file,
                'url' => $baseUrl . '/assets/uploads/' . $file, // Full Absolute URL
                'path' => 'assets/uploads/' . $file, // Relative path for internal display
                'time' => filemtime($uploadDir . $file)
            ];
        }
    }
    // Sort by newest first
    usort($images, function ($a, $b) {
        return $b['time'] - $a['time'];
    });
}
?>

<div class="container" style="padding-top: 40px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 30px;">
        <h1 style="font-family: var(--font-serif);">Image Gallery (图床)</h1>
        <a href="index.php" class="hero-btn" style="border: 1px solid #333; color: #333;">&larr; Back to Dashboard</a>
    </div>

    <!-- Upload Area -->
    <div id="drop-zone"
        style="border: 2px dashed #ccc; padding: 40px; text-align: center; margin-bottom: 40px; cursor: pointer; border-radius: 4px; background: #fff; transition: background 0.2s;">
        <p style="font-size: 1.2rem; margin-bottom: 10px;">Drag & Drop images here or click to upload</p>
        <p style="color: #888;">Supports JPG, PNG, GIF, WEBP</p>
        <input type="file" id="file-input" style="display: none;" multiple accept="image/*">
    </div>

    <!-- Gallery Grid -->
    <div class="gallery-grid" id="gallery-grid"
        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px;">
        <?php foreach ($images as $img): ?>
            <div class="gallery-item"
                style="border: 1px solid #eee; padding: 10px; border-radius: 4px; background: #fff; box-shadow: var(--shadow-sm);">
                <div
                    style="height: 120px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 10px; background: #f9f9f9; border-radius: 2px;">
                    <a href="<?php echo $img['url']; ?>" target="_blank">
                        <img src="<?php echo $img['url']; ?>" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                    </a>
                </div>
                <div style="margin-bottom: 5px;">
                    <input type="text" value="<?php echo $img['url']; ?>" readonly onclick="this.select()"
                        style="width: 100%; font-size: 12px; padding: 5px; border: 1px solid #ddd; border-radius: 3px;">
                </div>
                <div style="display: flex; gap: 5px;">
                    <button class="copy-btn" data-url="<?php echo $img['url']; ?>"
                        style="flex: 1; padding: 6px; font-size: 12px; cursor: pointer; background: #f0f0f0; border: 1px solid #ccc; border-radius: 3px;">Copy
                        Link</button>
                    <button class="md-btn" data-url="<?php echo $img['url']; ?>"
                        style="flex: 1; padding: 6px; font-size: 12px; cursor: pointer; background: #f0f0f0; border: 1px solid #ccc; border-radius: 3px;">Copy
                        MD</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const galleryGrid = document.getElementById('gallery-grid');

        // Drag & Drop
        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.background = '#f0f0f0';
            dropZone.style.borderColor = '#999';
        });

        dropZone.addEventListener('dragleave', (e) => {
            e.preventDefault();
            dropZone.style.background = '#fff';
            dropZone.style.borderColor = '#ccc';
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.background = '#fff';
            dropZone.style.borderColor = '#ccc';
            const files = e.dataTransfer.files;
            handleFiles(files);
        });

        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });

        function handleFiles(files) {
            if (!files.length) return;

            [...files].forEach(uploadFile);
        }

        function uploadFile(file) {
            const formData = new FormData();
            formData.append('file', file);

            fetch('upload_image.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Prepend new image to grid
                        const item = document.createElement('div');
                        item.className = 'gallery-item';
                        item.style.cssText = 'border: 1px solid #eee; padding: 10px; border-radius: 4px; background: #fff; box-shadow: var(--shadow-sm); animation: fadeIn 0.5s;';
                        item.innerHTML = `
                    <div style="height: 120px; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 10px; background: #f9f9f9; border-radius: 2px;">
                        <a href="../${data.url}" target="_blank">
                            <img src="../${data.url}" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                        </a>
                    </div>
                    <div style="margin-bottom: 5px;">
                        <input type="text" value="${data.url}" readonly onclick="this.select()" style="width: 100%; font-size: 12px; padding: 5px; border: 1px solid #ddd; border-radius: 3px;">
                    </div>
                    <div style="display: flex; gap: 5px;">
                         <button class="copy-btn" data-url="${data.url}" style="flex: 1; padding: 6px; font-size: 12px; cursor: pointer; background: #f0f0f0; border: 1px solid #ccc; border-radius: 3px;">Copy Link</button>
                         <button class="md-btn" data-url="${data.url}" style="flex: 1; padding: 6px; font-size: 12px; cursor: pointer; background: #f0f0f0; border: 1px solid #ccc; border-radius: 3px;">Copy MD</button>
                    </div>
                `;
                        galleryGrid.insertBefore(item, galleryGrid.firstChild);
                    } else {
                        alert('Upload failed: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Upload error.');
                });
        }

        // Event delegation for dynamically added buttons
        galleryGrid.addEventListener('click', function (e) {
            if (e.target.classList.contains('copy-btn')) {
                const url = e.target.getAttribute('data-url');
                navigator.clipboard.writeText(url).then(() => {
                    const originalText = e.target.innerText;
                    e.target.innerText = 'Copied!';
                    setTimeout(() => e.target.innerText = originalText, 1500);
                });
            }
            if (e.target.classList.contains('md-btn')) {
                const url = e.target.getAttribute('data-url');
                const md = `![Image](${url})`;
                navigator.clipboard.writeText(md).then(() => {
                    const originalText = e.target.innerText;
                    e.target.innerText = 'Copied!';
                    setTimeout(() => e.target.innerText = originalText, 1500);
                });
            }
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>