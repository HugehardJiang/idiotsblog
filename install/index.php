<?php
// Check if already installed
if (file_exists(__DIR__ . '/install.lock')) {
    die('System is already installed. Defaulting to security mode. To reinstall, delete install/install.lock');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>安装向导 - Idiots Website</title>
    <link rel="stylesheet" href="install.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;700&family=PingFang+SC:wght@400;500;600&display=swap"
        rel="stylesheet">
</head>

<body>

    <div class="installer-container">
        <div class="installer-header">
            <h1>安装向导</h1>
            <p>欢迎使用 Idiots Website 系统。只需几步即可完成配置。</p>
        </div>

        <div id="alert-box" class="alert hidden"></div>

        <form id="install-form">
            <div class="form-section-title">数据库配置</div>

            <div class="form-group">
                <label for="db_host">数据库地址 (Host)</label>
                <input type="text" id="db_host" name="db_host" value="localhost" required>
            </div>

            <div class="form-group">
                <label for="db_name">数据库名 (Database Name)</label>
                <input type="text" id="db_name" name="db_name" value="idiots" required>
            </div>

            <div class="form-group">
                <label for="db_user">数据库用户 (User)</label>
                <input type="text" id="db_user" name="db_user" value="root" required>
            </div>

            <div class="form-group">
                <label for="db_pass">数据库密码 (Password)</label>
                <input type="password" id="db_pass" name="db_pass" placeholder="留空则为空">
            </div>

            <div class="form-section-title">站点配置</div>

            <div class="form-group">
                <label for="site_name">站点名称 (Site Name)</label>
                <input type="text" id="site_name" name="site_name" value="My Website" required>
            </div>

            <div class="form-group">
                <label for="site_url">站点地址 (URL)</label>
                <input type="url" id="site_url" name="site_url" placeholder="https://example.com" required
                    value="<?php echo (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]"; ?>">
            </div>

            <div class="form-section-title">管理员账户</div>

            <div class="form-group">
                <label for="admin_user">管理员用户名</label>
                <input type="text" id="admin_user" name="admin_user" value="admin" required>
            </div>

            <div class="form-group">
                <label for="admin_pass">管理员密码</label>
                <input type="password" id="admin_pass" name="admin_pass" required>
            </div>

            <button type="submit" class="btn-submit" id="submit-btn">开始安装</button>
        </form>
    </div>

    <script>
        document.getElementById('install-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const btn = document.getElementById('submit-btn');
            const alertBox = document.getElementById('alert-box');
            const formData = new FormData(this);

            // UI State
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> 安装中...';
            alertBox.className = 'alert hidden';

            fetch('install_process.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alertBox.textContent = '安装成功！正在跳转...';
                        alertBox.className = 'alert alert-success';
                        alertBox.classList.remove('hidden');
                        setTimeout(() => {
                            window.location.href = '../index.php';
                        }, 2000);
                    } else {
                        alertBox.textContent = '错误: ' + data.message;
                        alertBox.className = 'alert alert-error';
                        alertBox.classList.remove('hidden');
                        btn.disabled = false;
                        btn.innerHTML = '重试安装';
                    }
                })
                .catch(error => {
                    alertBox.textContent = '请求失败: ' + error.message;
                    alertBox.className = 'alert alert-error';
                    alertBox.classList.remove('hidden');
                    btn.disabled = false;
                    btn.innerHTML = '重试安装';
                });
        });
    </script>

</body>

</html>