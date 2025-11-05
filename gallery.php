<?php
// 极简相册：支持 jpg / jpeg / png / gif / webp / avif / heic / heif
// 放在 /mnt/sdcard/www/html 下，访问 /gallery.php 即可。

$baseDir = __DIR__;  // 相册根目录 = 当前脚本所在目录
$allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'heic', 'heif'];

// 当前子目录（相对于 $baseDir）
$relDir = isset($_GET['dir']) ? trim($_GET['dir'], '/') : '';

// 防止越权访问
if (strpos($relDir, '..') !== false) {
    $relDir = '';
}

$curDir = $baseDir . ($relDir !== '' ? '/' . $relDir : '');
if (!is_dir($curDir)) {
    http_response_code(404);
    echo 'Directory not found';
    exit;
}

// 扫描当前目录里的子目录和图片
$entries = scandir($curDir);
$dirs = [];
$images = [];

foreach ($entries as $f) {
    if ($f === '.' || $f === '..') continue;
    $path = $curDir . '/' . $f;

    if (is_dir($path)) {
        $dirs[] = $f;
        continue;
    }

    if (is_file($path)) {
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (in_array($ext, $allowedExt, true)) {
            $images[] = $f;
        }
    }
}

// 排序
natcasesort($dirs);
$dirs = array_values($dirs);

natcasesort($images);
$images = array_values($images);
$count = count($images);

// 是否是“查看单张图片”模式（有 i 参数并且合法）
$hasIndexParam = isset($_GET['i']);
$index = $hasIndexParam ? intval($_GET['i']) : 0;
if ($index < 0) $index = 0;
if ($index >= $count) $index = max(0, $count - 1);

// 构造链接
function buildUrl($dir, $i = null) {
    $params = [];
    if ($i !== null) $params['i'] = $i;
    if ($dir !== '') $params['dir'] = $dir;
    if (empty($params)) return '?';
    $q = [];
    foreach ($params as $k => $v) {
        $q[] = $k . '=' . rawurlencode($v);
    }
    return '?' . implode('&', $q);
}

// 计算上一级目录
$parentDir = '';
if ($relDir !== '') {
    $parts = explode('/', $relDir);
    array_pop($parts);
    $parentDir = implode('/', $parts);
}
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<title>简单相册<?php if ($relDir !== '') echo ' - ' . htmlspecialchars($relDir, ENT_QUOTES, 'UTF-8'); ?></title>
<!-- 去掉 maximum-scale=1，让移动端可以 pinch 放大 -->
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
body {
  font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", system-ui, sans-serif;
  margin: 0;
  padding: 0;
  background: #111;
  color: #eee;
}
a { color: #8cf; text-decoration: none; }
a:hover { text-decoration: underline; }
header {
  padding: 8px 12px;
  background: #222;
  position: sticky;
  top: 0;
  z-index: 10;
}
.path { font-size: 13px; color: #aaa; }
.dirs {
  padding: 8px 12px;
  background: #181818;
  border-bottom: 1px solid #222;
  font-size: 14px;
}
.dirs a { margin-right: 8px; }
.grid {
  display: flex;
  flex-wrap: wrap;
  padding: 8px;
  gap: 8px;
}

/* 缩略图 */
.thumb {
  flex: 0 0 auto;
  width: 160px;
  height: 120px;
  overflow: hidden;
  background: #333;
  display:flex;
  align-items:center;
  justify-content:center;
  border-radius:6px;
}
.thumb img {
  max-width: 100%;
  max-height: 100%;
}

/* 大图区域 */
.viewer {
  text-align: center;
  padding: 8px;
  background: #000;
}
.viewer img {
  max-width: 100vw;
  max-height: calc(100vh - 110px);
  cursor: grab;
  transform-origin: center center; /* 固定一次 */
}
.nav {
  margin: 8px 0;
  display: flex;
  justify-content: center;
  gap: 8px;
  flex-wrap: wrap;
}
.footer {
  padding: 8px 12px;
  font-size: 12px;
  color: #999;
}

/* 按钮 */
.btn {
  padding: 6px 12px;
  border-radius: 16px;
  background: #333;
  border: none;
  font-size: 14px;
  line-height: 1.4;
  display: inline-block;
  color: #8cf;
  cursor: pointer;
}
.btn:hover { background: #444; }
.btn:focus { outline: none; }

/* 全屏控制条（覆盖在图片左右） */
.fs-controls {
  position: fixed;
  top: 50%;
  left: 0;
  right: 0;
  display: none;
  justify-content: space-between;
  align-items: center;
  padding: 0 12px;
  pointer-events: none;
}
.fs-controls a {
  pointer-events: auto;
  text-decoration: none;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.5);
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

/* 中小屏幕优化（含 iPad / 手机） */
@media (max-width: 1024px) {
  body { font-size: 16px; }
  .thumb { width: 45vw; height: 34vw; }
  .dirs { font-size: 15px; }
  .btn { padding: 8px 16px; font-size: 16px; }
  .viewer img { max-height: calc(100vh - 140px); }
}

/* 很小的手机屏幕 */
@media (max-width: 480px) {
  .thumb { width: 48vw; height: 36vw; }
}

/* 全屏时的样式：只保留图片 + 左右箭头 */
#viewer:fullscreen,
#viewer:-webkit-full-screen {
  background: #000;
  padding: 0;
  /* 关键：全屏时让 viewer 充当一个居中的 flex 容器 */
  display: flex;
  align-items: center;     /* 垂直居中（因为下面加了 column） */
  justify-content: center; /* 水平居中 */
  flex-direction: column;
  height: 100vh;
  box-sizing: border-box;
}
#viewer:fullscreen img,
#viewer:-webkit-full-screen img {
  max-width: 100vw;
  max-height: 100vh;
}
#viewer:fullscreen .normal-controls,
#viewer:-webkit-full-screen .normal-controls {
  display: none;
}
#viewer:fullscreen .fs-controls,
#viewer:-webkit-full-screen .fs-controls {
  display: flex;
}
</style>
</head>
<body>
<header>
  <strong>简单相册</strong>
  <div class="path">
    当前位置：
    <a href="?">/</a>
    <?php
    if ($relDir !== '') {
        $parts = explode('/', $relDir);
        $acc = '';
        foreach ($parts as $i => $p) {
            if ($p === '') continue;
            $acc = $acc === '' ? $p : $acc . '/' . $p;
            echo ' / <a href="' . htmlspecialchars(buildUrl($acc), ENT_QUOTES, 'UTF-8') . '">' .
                 htmlspecialchars($p, ENT_QUOTES, 'UTF-8') . '</a>';
        }
    }
    ?>
    <?php if ($count): ?>
      &nbsp;&nbsp;(本目录 <?php echo $count; ?> 张图片)
    <?php endif; ?>
  </div>
</header>

<div class="dirs">
  <?php if ($relDir !== ''): ?>
    <a class="btn" href="<?php echo htmlspecialchars($parentDir === '' ? '?' : buildUrl($parentDir), ENT_QUOTES, 'UTF-8'); ?>">⬆ 上一级</a>
  <?php endif; ?>

  <?php if (!empty($dirs)): ?>
    子目录：
    <?php foreach ($dirs as $d):
      $subRel = $relDir === '' ? $d : $relDir . '/' . $d;
    ?>
      <a href="<?php echo htmlspecialchars(buildUrl($subRel), ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars($d, ENT_QUOTES, 'UTF-8'); ?>/</a>
    <?php endforeach; ?>
  <?php else: ?>
    (无子目录)
  <?php endif; ?>
</div>

<?php
// 单张浏览模式（有 i 参数且至少一张图片）
if ($count > 0 && $hasIndexParam):
    $filename = $images[$index];
    $relPath = ($relDir ? $relDir . '/' : '') . $filename;
    $imgUrl = htmlspecialchars($relPath, ENT_QUOTES, 'UTF-8');
?>
<div class="viewer" id="viewer">
  <!-- 普通模式导航 -->
  <div class="nav normal-controls">
    <a class="btn" id="btn-prev" href="<?php echo htmlspecialchars(buildUrl($relDir, ($index - 1 + $count) % $count), ENT_QUOTES, 'UTF-8'); ?>">« 上一张</a>
    <a class="btn" href="<?php echo htmlspecialchars(buildUrl($relDir), ENT_QUOTES, 'UTF-8'); ?>">缩略图</a>
    <a class="btn" id="btn-next" href="<?php echo htmlspecialchars(buildUrl($relDir, ($index + 1) % $count), ENT_QUOTES, 'UTF-8'); ?>">下一张 »</a>
    <button type="button" class="btn" id="fs-btn">全屏</button>
  </div>

  <!-- 图片本体 -->
  <div>
    <img id="photo" src="<?php echo $imgUrl; ?>" alt="">
  </div>

  <!-- 普通模式下的文字信息 -->
  <div class="nav normal-controls">
    <span id="caption"><?php echo ($index + 1) . ' / ' . $count . ' - ' .
                 htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?></span>
  </div>

  <!-- 全屏时显示的左右控制按钮 -->
  <div class="fs-controls">
    <a id="fs-prev" href="<?php echo htmlspecialchars(buildUrl($relDir, ($index - 1 + $count) % $count), ENT_QUOTES, 'UTF-8'); ?>">‹</a>
    <a id="fs-next" href="<?php echo htmlspecialchars(buildUrl($relDir, ($index + 1) % $count), ENT_QUOTES, 'UTF-8'); ?>">›</a>
  </div>
</div>

<script>
(function() {
  // PHP -> JS 数据
  const galleryList = <?php echo json_encode($images, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  let curIndex = <?php echo $index; ?>;
  const relDir = <?php echo json_encode($relDir, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  const listUrl = <?php echo json_encode(buildUrl($relDir)); ?>;

  const viewerEl = document.getElementById('viewer');
  const fsBtn = document.getElementById('fs-btn');
  const imgEl = document.getElementById('photo');
  const captionEl = document.getElementById('caption');
  const btnPrev = document.getElementById('btn-prev');
  const btnNext = document.getElementById('btn-next');
  const fsPrev = document.getElementById('fs-prev');
  const fsNext = document.getElementById('fs-next');

  // ========= 缩放 & 拖动（PC + 移动），带 requestAnimationFrame 合并 =========
  let scale = 1;
  let offsetX = 0;
  let offsetY = 0;

  let isDragging = false;
  let dragStartX = 0;
  let dragStartY = 0;
  let startOffsetX = 0;
  let startOffsetY = 0;

  let isPinching = false;
  let pinchStartDist = 0;
  let pinchStartScale = 1;

  let transformDirty = false;

  function scheduleTransform() {
    if (transformDirty) return;
    transformDirty = true;
    requestAnimationFrame(function() {
      transformDirty = false;
      if (!imgEl) return;
      imgEl.style.transform = 'translate(' + offsetX + 'px,' + offsetY + 'px) scale(' + scale + ')';
    });
  }

  function resetTransform() {
    scale = 1;
    offsetX = 0;
    offsetY = 0;
    scheduleTransform();
  }

  function zoomByFactor(factor) {
    scale *= factor;
    if (scale < 1) scale = 1;
    if (scale > 6) scale = 6;  // 上限 6 倍，减轻渲染压力
    scheduleTransform();
  }

  // ========= 全屏开关 =========
  function isFullscreen() {
    return document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement;
  }

  function requestFs(el) {
    if (el.requestFullscreen) return el.requestFullscreen();
    if (el.webkitRequestFullscreen) return el.webkitRequestFullscreen();
    if (el.msRequestFullscreen) return el.msRequestFullscreen();
  }

  function exitFs() {
    if (document.exitFullscreen) return document.exitFullscreen();
    if (document.webkitExitFullscreen) return document.webkitExitFullscreen();
    if (document.msExitFullscreen) return document.msExitFullscreen();
  }

  function updateFsBtn() {
    if (!fsBtn) return;
    fsBtn.textContent = isFullscreen() ? '退出全屏' : '全屏';
  }

  if (fsBtn && viewerEl) {
    fsBtn.addEventListener('click', function() {
      if (isFullscreen()) {
        exitFs();
      } else {
        requestFs(viewerEl);
      }
    });

    // ✅ 这里加上退出全屏后自动 resetTransform
    document.addEventListener('fullscreenchange', function() {
      updateFsBtn();
      if (!isFullscreen()) {
        resetTransform();
      }
    });
    document.addEventListener('webkitfullscreenchange', function() {
      updateFsBtn();
      if (!isFullscreen()) {
        resetTransform();
      }
    });
    document.addEventListener('msfullscreenchange', function() {
      updateFsBtn();
      if (!isFullscreen()) {
        resetTransform();
      }
    });

    updateFsBtn();
  }

  // PC 滚轮缩放
  if (viewerEl && imgEl) {
    viewerEl.addEventListener('wheel', function(e) {
      e.preventDefault();
      const factor = e.deltaY < 0 ? 1.1 : 0.9;
      zoomByFactor(factor);
    }, { passive: false });

    // PC 鼠标拖动
    imgEl.addEventListener('mousedown', function(e) {
      if (scale <= 1) return;
      isDragging = true;
      dragStartX = e.clientX;
      dragStartY = e.clientY;
      startOffsetX = offsetX;
      startOffsetY = offsetY;
      imgEl.style.cursor = 'grabbing';
      e.preventDefault();
    });

    window.addEventListener('mousemove', function(e) {
      if (!isDragging) return;
      offsetX = startOffsetX + (e.clientX - dragStartX);
      offsetY = startOffsetY + (e.clientY - dragStartY);
      scheduleTransform();
    });

    window.addEventListener('mouseup', function() {
      if (!isDragging) return;
      isDragging = false;
      imgEl.style.cursor = 'grab';
    });

    imgEl.addEventListener('mouseleave', function() {
      if (!isDragging) return;
      isDragging = false;
      imgEl.style.cursor = 'grab';
    });

    // 移动端：单指拖动 + 双指 pinch 缩放
    viewerEl.addEventListener('touchstart', function(e) {
      if (!e.touches || e.touches.length === 0) return;

      if (e.touches.length === 1 && !isPinching) {
        // 单指：如果已放大，则进入拖动模式
        if (scale > 1) {
          isDragging = true;
          dragStartX = e.touches[0].clientX;
          dragStartY = e.touches[0].clientY;
          startOffsetX = offsetX;
          startOffsetY = offsetY;
        }
      } else if (e.touches.length === 2) {
        // 双指：进入 pinch 模式
        isPinching = true;
        isDragging = false;
        const dx = e.touches[0].clientX - e.touches[1].clientX;
        const dy = e.touches[0].clientY - e.touches[1].clientY;
        pinchStartDist = Math.sqrt(dx * dx + dy * dy);
        pinchStartScale = scale;
      }
    }, { passive: true });

    viewerEl.addEventListener('touchmove', function(e) {
      if (!e.touches || e.touches.length === 0) return;

      if (isPinching && e.touches.length === 2) {
        // 处理 pinch 缩放
        const dx = e.touches[0].clientX - e.touches[1].clientX;
        const dy = e.touches[0].clientY - e.touches[1].clientY;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (pinchStartDist > 0) {
          const factor = dist / pinchStartDist;
          scale = pinchStartScale * factor;
          if (scale < 1) scale = 1;
          if (scale > 6) scale = 6;
          scheduleTransform();
        }
        e.preventDefault();
      } else if (isDragging && e.touches.length === 1 && scale > 1) {
        // 单指拖动
        const tx = e.touches[0].clientX;
        const ty = e.touches[0].clientY;
        offsetX = startOffsetX + (tx - dragStartX);
        offsetY = startOffsetY + (ty - dragStartY);
        scheduleTransform();
        e.preventDefault();
      }
    }, { passive: false });

    viewerEl.addEventListener('touchend', function(e) {
      if (e.touches && e.touches.length === 1 && isPinching) {
        isPinching = false;
      } else if (!e.touches || e.touches.length === 0) {
        isDragging = false;
        isPinching = false;
      }
    }, { passive: true });

    viewerEl.addEventListener('touchcancel', function() {
      isDragging = false;
      isPinching = false;
    }, { passive: true });
  }

  // ========= 显示指定索引的图片（用于全屏 + JS 切图） =========
  function showIndex(idx) {
    if (!imgEl || !galleryList.length) return;
    const len = galleryList.length;
    curIndex = ((idx % len) + len) % len; // 保证在 0..len-1

    const filename = galleryList[curIndex];
    const path = (relDir ? relDir + '/' : '') + filename;
    imgEl.src = path;

    resetTransform();

    if (captionEl) {
      captionEl.textContent = (curIndex + 1) + ' / ' + len + ' - ' + filename;
    }
  }

  function showRelative(delta) {
    showIndex(curIndex + delta);
  }

  // 初次调用（同步 PHP 渲染状态）
  showIndex(curIndex);

  // ========= 按钮点击：统一改用 JS 切图（保持全屏） =========
  function bindNavLink(el, delta) {
    if (!el) return;
    el.addEventListener('click', function(e) {
      e.preventDefault();
      showRelative(delta);
    });
  }

  bindNavLink(btnPrev, -1);
  bindNavLink(btnNext, +1);
  bindNavLink(fsPrev, -1);
  bindNavLink(fsNext, +1);

  // ========= 键盘：左右箭头 / 上下箭头 / Esc =========
  document.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') {
      showRelative(-1);
    } else if (e.key === 'ArrowRight') {
      showRelative(+1);
    } else if (e.key === 'ArrowUp') {
      zoomByFactor(1.1);
    } else if (e.key === 'ArrowDown') {
      zoomByFactor(0.9);
    } else if (e.key === 'Escape') {
      // Esc：优先退出全屏，否则回到缩略图列表
      if (isFullscreen()) {
        exitFs();
      } else {
        location.href = listUrl;
      }
    }
  });

  // ========= 触屏：左右滑动切图（仅在未放大时生效） =========
  let startX = null;
  let startY = null;
  let multiTouchSwipe = false;

  document.addEventListener('touchstart', function(e) {
    if (!e.touches || e.touches.length === 0) return;
    if (scale > 1) return;  // 已放大时，不做左右切图手势

    if (e.touches.length > 1) {
      multiTouchSwipe = true;
      startX = null;
      startY = null;
      return;
    }

    multiTouchSwipe = false;
    startX = e.touches[0].clientX;
    startY = e.touches[0].clientY;
  }, { passive: true });

  document.addEventListener('touchend', function(e) {
    if (scale > 1) {
      startX = null;
      startY = null;
      multiTouchSwipe = false;
      return;
    }
    if (multiTouchSwipe) {
      multiTouchSwipe = false;
      startX = null;
      startY = null;
      return;
    }
    if (startX === null || !e.changedTouches || e.changedTouches.length === 0) return;

    const endX = e.changedTouches[0].clientX;
    const endY = e.changedTouches[0].clientY;
    const dx = endX - startX;
    const dy = endY - startY;

    if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy) * 1.2) {
      if (dx < 0) {
        showRelative(+1);
      } else {
        showRelative(-1);
      }
    }

    startX = null;
    startY = null;
  }, { passive: true });

  document.addEventListener('touchmove', function(e) {
    if (e.touches && e.touches.length > 1) {
      multiTouchSwipe = true;
    }
  }, { passive: true });

})();
</script>

<?php else: ?>

<!-- 缩略图列表模式 -->
<div class="grid">
<?php if ($count === 0): ?>
  <p style="padding:8px;">当前目录没有找到图片文件（支持：jpg/jpeg/png/gif/webp/avif/heic/heif）。</p>
<?php else: ?>
  <?php foreach ($images as $idx => $filename):
      $relPath = ($relDir ? $relDir . '/' : '') . $filename;
      $imgUrl = htmlspecialchars($relPath, ENT_QUOTES, 'UTF-8');
  ?>
    <a class="thumb" href="<?php echo htmlspecialchars(buildUrl($relDir, $idx), ENT_QUOTES, 'UTF-8'); ?>">
      <img loading="lazy" src="<?php echo $imgUrl; ?>" alt="">
    </a>
  <?php endforeach; ?>
<?php endif; ?>
</div>

<?php endif; ?>

<div class="footer">
  根目录：<?php echo htmlspecialchars($baseDir, ENT_QUOTES, 'UTF-8'); ?>
</div>
</body>
</html>
