<?php
declare(strict_types=1);

function safePreviewPath(string $raw): ?string
{
    $raw = trim($raw);
    if ($raw === '' || $raw[0] !== '/') {
        return null;
    }
    if (strpos($raw, '//') !== false || strpos($raw, "\0") !== false) {
        return null;
    }
    if (!preg_match('#^/[a-zA-Z0-9/_\.\-]+$#', $raw)) {
        return null;
    }
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?: dirname(__DIR__));
    if ($docRoot === false) {
        return null;
    }
    $fsPath = realpath($docRoot . $raw);
    if ($fsPath === false || !is_file($fsPath)) {
        return null;
    }
    if (strpos($fsPath, $docRoot) !== 0) {
        return null;
    }
    return $raw;
}

$devices = [
    'iphone-se' => ['w' => 375, 'h' => 667, 'label' => 'iPhone SE'],
    'iphone-14' => ['w' => 390, 'h' => 844, 'label' => 'iPhone 14'],
    'iphone-14-max' => ['w' => 430, 'h' => 932, 'label' => 'iPhone 14 Pro Max'],
    'pixel-7' => ['w' => 412, 'h' => 915, 'label' => 'Pixel 7'],
    'galaxy-s20' => ['w' => 360, 'h' => 800, 'label' => 'Galaxy S20'],
];

$url = safePreviewPath((string)($_GET['url'] ?? ''));
$checkout = safePreviewPath((string)($_GET['checkout'] ?? ''));
$deviceKey = (string)($_GET['device'] ?? 'iphone-14');
if (!isset($devices[$deviceKey])) {
    $deviceKey = 'iphone-14';
}
$device = $devices[$deviceKey];
$label = trim((string)($_GET['label'] ?? 'Mobile preview'));
if ($label === '') {
    $label = 'Mobile preview';
}
$page = ($_GET['page'] ?? 'sales') === 'checkout' && $checkout ? 'checkout' : 'sales';
$activeUrl = $page === 'checkout' ? $checkout : $url;
$hasCheckout = $checkout !== null;
$error = $activeUrl === null ? 'Invalid or missing preview URL.' : '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($label, ENT_QUOTES) ?> · Mobile Preview</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #0b0d12;
  --surface: #12151c;
  --border: #252a36;
  --text: #e8eaef;
  --muted: #8b93a7;
}
*, *::before, *::after { box-sizing: border-box; }
body {
  margin: 0;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  font-family: Inter, system-ui, sans-serif;
  background: var(--bg);
  color: var(--text);
}
.toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.65rem;
  padding: 0.85rem 1.25rem;
  border-bottom: 1px solid var(--border);
  background: var(--surface);
}
.toolbar h1 {
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  flex: 1;
  min-width: 140px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.controls { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.controls label {
  font-size: 0.72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--muted);
}
.controls select, .controls a, .controls button {
  padding: 0.4rem 0.75rem;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--bg);
  color: var(--text);
  font: inherit;
  font-size: 0.82rem;
  text-decoration: none;
  cursor: pointer;
}
.page-tabs {
  display: flex;
  border: 1px solid var(--border);
  border-radius: 8px;
  overflow: hidden;
}
.page-tabs a {
  padding: 0.4rem 0.75rem;
  border: none;
  border-radius: 0;
  background: var(--surface);
  color: var(--muted);
}
.page-tabs a.active {
  background: #181c26;
  color: var(--text);
  font-weight: 600;
}
.stage {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  overflow: auto;
}
.error {
  max-width: 480px;
  padding: 1rem 1.25rem;
  border: 1px solid rgba(248, 113, 113, 0.4);
  border-radius: 12px;
  color: #f87171;
  background: rgba(248, 113, 113, 0.08);
}
.mobile-device {
  position: relative;
  background: #111;
  border-radius: 36px;
  padding: 12px;
  box-shadow:
    0 0 0 2px #2a2a2a,
    0 0 0 4px #1a1a1a,
    0 24px 80px rgba(0, 0, 0, 0.55);
}
.mobile-device::before {
  content: "";
  position: absolute;
  top: 10px;
  left: 50%;
  transform: translateX(-50%);
  width: 96px;
  height: 24px;
  background: #111;
  border-radius: 0 0 14px 14px;
  z-index: 2;
  pointer-events: none;
}
.mobile-device-screen {
  overflow: hidden;
  border-radius: 26px;
  background: #fff;
  line-height: 0;
}
.mobile-device-screen iframe {
  display: block;
  border: none;
  width: <?= (int)$device['w'] ?>px;
  height: <?= (int)$device['h'] ?>px;
  background: #fff;
}
</style>
</head>
<body>
<div class="toolbar">
  <h1><?= htmlspecialchars($label, ENT_QUOTES) ?></h1>
  <div class="controls">
    <?php if ($hasCheckout && $url): ?>
    <div class="page-tabs">
      <a href="?<?= htmlspecialchars(http_build_query(['url' => $url, 'checkout' => $checkout, 'device' => $deviceKey, 'label' => $label, 'page' => 'sales']), ENT_QUOTES) ?>" class="<?= $page === 'sales' ? 'active' : '' ?>">Sales page</a>
      <a href="?<?= htmlspecialchars(http_build_query(['url' => $url, 'checkout' => $checkout, 'device' => $deviceKey, 'label' => $label, 'page' => 'checkout']), ENT_QUOTES) ?>" class="<?= $page === 'checkout' ? 'active' : '' ?>">Checkout</a>
    </div>
    <?php endif; ?>
    <form method="get" style="display:flex;align-items:center;gap:0.5rem;margin:0;">
      <?php if ($url): ?><input type="hidden" name="url" value="<?= htmlspecialchars($url, ENT_QUOTES) ?>"><?php endif; ?>
      <?php if ($checkout): ?><input type="hidden" name="checkout" value="<?= htmlspecialchars($checkout, ENT_QUOTES) ?>"><?php endif; ?>
      <?php if ($hasCheckout): ?><input type="hidden" name="page" value="<?= htmlspecialchars($page, ENT_QUOTES) ?>"><?php endif; ?>
      <input type="hidden" name="label" value="<?= htmlspecialchars($label, ENT_QUOTES) ?>">
      <label for="device">Device</label>
      <select id="device" name="device" onchange="this.form.submit()">
        <?php foreach ($devices as $key => $dev): ?>
        <option value="<?= htmlspecialchars($key, ENT_QUOTES) ?>"<?= $key === $deviceKey ? ' selected' : '' ?>><?= htmlspecialchars($dev['label'], ENT_QUOTES) ?> (<?= (int)$dev['w'] ?>)</option>
        <?php endforeach; ?>
      </select>
    </form>
    <a href="index.php">← Back to catalog</a>
  </div>
</div>
<div class="stage">
  <?php if ($error !== ''): ?>
  <div class="error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
  <?php else: ?>
  <div class="mobile-device">
    <div class="mobile-device-screen">
      <iframe src="<?= htmlspecialchars($activeUrl, ENT_QUOTES) ?>" title="<?= htmlspecialchars($label, ENT_QUOTES) ?>"></iframe>
    </div>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
