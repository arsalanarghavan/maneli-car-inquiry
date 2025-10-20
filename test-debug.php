<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تست دیباگ آیکون‌های Line Awesome</title>
    
    <?php
    // شبیه‌سازی متغیر پلاگین
    if (!defined('MANELI_INQUIRY_PLUGIN_URL')) {
        // مسیر نسبی از روت سرور
        $plugin_url = rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/';
        define('MANELI_INQUIRY_PLUGIN_URL', $plugin_url);
    }
    ?>
    
    <!-- Bootstrap -->
    <link href="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/libs/bootstrap/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <!-- Styles -->
    <link href="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/css/styles.css" rel="stylesheet">
    
    <!-- Line Awesome - اصلی -->
    <link href="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/icon-fonts/line-awesome/1.3.0/css/line-awesome.css" rel="stylesheet">
    
    <!-- Line Awesome Fix - فیکس -->
    <link href="<?php echo MANELI_INQUIRY_PLUGIN_URL; ?>assets/css/line-awesome-fix.css" rel="stylesheet">
    
    <style>
        body {
            padding: 40px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .icon-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        .icon-item {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            min-width: 100px;
        }
        .icon-item i {
            font-size: 32px;
            display: block;
            margin-bottom: 10px;
            color: #667eea;
        }
        .icon-item code {
            font-size: 11px;
            background: #e9ecef;
            padding: 4px 8px;
            border-radius: 4px;
            display: block;
        }
        .debug-info {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 8px;
            border-right: 4px solid #2196F3;
            margin-bottom: 20px;
        }
        .debug-info h3 {
            margin-top: 0;
            color: #1976D2;
        }
        .debug-info pre {
            background: #fff;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 تست دیباگ Line Awesome</h1>
        
        <div class="debug-info">
            <h3>📍 مسیر پلاگین:</h3>
            <pre><?php echo MANELI_INQUIRY_PLUGIN_URL; ?></pre>
            
            <h3>📁 فایل‌های CSS لود شده:</h3>
            <ol>
                <li><code>assets/libs/bootstrap/css/bootstrap.rtl.min.css</code></li>
                <li><code>assets/css/styles.css</code></li>
                <li><strong style="color: #667eea;">assets/icon-fonts/line-awesome/1.3.0/css/line-awesome.css</strong></li>
                <li><strong style="color: #d63384;">assets/css/line-awesome-fix.css</strong></li>
            </ol>
        </div>
        
        <div id="font-check-result" class="alert alert-danger" style="display:none;">
            در حال بررسی...
        </div>
        
        <div class="test-section">
            <h2>🎨 آیکون‌های تست</h2>
            <div class="icon-row">
                <div class="icon-item">
                    <i class="la la-home"></i>
                    <code>la-home</code>
                </div>
                <div class="icon-item">
                    <i class="la la-user"></i>
                    <code>la-user</code>
                </div>
                <div class="icon-item">
                    <i class="la la-users"></i>
                    <code>la-users</code>
                </div>
                <div class="icon-item">
                    <i class="la la-car"></i>
                    <code>la-car</code>
                </div>
                <div class="icon-item">
                    <i class="la la-dollar-sign"></i>
                    <code>la-dollar-sign</code>
                </div>
                <div class="icon-item">
                    <i class="la la-chart-bar"></i>
                    <code>la-chart-bar</code>
                </div>
                <div class="icon-item">
                    <i class="la la-cog"></i>
                    <code>la-cog</code>
                </div>
                <div class="icon-item">
                    <i class="la la-calendar"></i>
                    <code>la-calendar</code>
                </div>
                <div class="icon-item">
                    <i class="la la-search"></i>
                    <code>la-search</code>
                </div>
                <div class="icon-item">
                    <i class="la la-lock"></i>
                    <code>la-lock</code>
                </div>
                <div class="icon-item">
                    <i class="la la-eye"></i>
                    <code>la-eye</code>
                </div>
                <div class="icon-item">
                    <i class="la la-edit"></i>
                    <code>la-edit</code>
                </div>
            </div>
        </div>
        
        <div class="test-section">
            <h2>🔧 اطلاعات دیباگ</h2>
            <div id="debug-output"></div>
        </div>
    </div>
    
    <script>
        setTimeout(function() {
            var icon = document.querySelector('.la-home');
            var computed = window.getComputedStyle(icon, ':before');
            var fontFamily = window.getComputedStyle(icon).fontFamily;
            var content = computed.content;
            
            var resultDiv = document.getElementById('font-check-result');
            var debugDiv = document.getElementById('debug-output');
            
            resultDiv.style.display = 'block';
            
            var debugInfo = '<table class="table table-bordered" style="margin-top: 20px;">';
            debugInfo += '<tr><th>Property</th><th>Value</th></tr>';
            debugInfo += '<tr><td>font-family</td><td>' + fontFamily + '</td></tr>';
            debugInfo += '<tr><td>content (:before)</td><td>' + content + '</td></tr>';
            debugInfo += '<tr><td>font-weight</td><td>' + window.getComputedStyle(icon).fontWeight + '</td></tr>';
            debugInfo += '<tr><td>font-style</td><td>' + window.getComputedStyle(icon).fontStyle + '</td></tr>';
            debugInfo += '<tr><td>display</td><td>' + window.getComputedStyle(icon).display + '</td></tr>';
            debugInfo += '</table>';
            
            debugDiv.innerHTML = debugInfo;
            
            if (fontFamily.toLowerCase().includes('line awesome')) {
                resultDiv.className = 'alert alert-success';
                resultDiv.innerHTML = '✅ موفقیت! فونت Line Awesome به درستی لود شده است!<br><small>Font: ' + fontFamily + '</small>';
            } else {
                resultDiv.className = 'alert alert-danger';
                resultDiv.innerHTML = '❌ خطا! فونت Line Awesome لود نشده است!<br><small>Font فعلی: ' + fontFamily + '</small><br><br>' +
                    '<strong>راهکار:</strong><br>' +
                    '1. Cache مرورگر را پاک کنید (Ctrl+Shift+Delete)<br>' +
                    '2. Hard Refresh کنید (Ctrl+F5)<br>' +
                    '3. Console را چک کنید (F12) برای خطاهای 404';
            }
            
            // بررسی لود شدن فایل‌های CSS
            var sheets = document.styleSheets;
            var cssFiles = '<h4 style="margin-top: 20px;">فایل‌های CSS لود شده:</h4><ul>';
            for (var i = 0; i < sheets.length; i++) {
                try {
                    var href = sheets[i].href || 'Inline Style';
                    cssFiles += '<li>' + href + '</li>';
                } catch(e) {
                    cssFiles += '<li>Cross-origin CSS</li>';
                }
            }
            cssFiles += '</ul>';
            debugDiv.innerHTML += cssFiles;
            
        }, 1000);
    </script>
</body>
</html>

