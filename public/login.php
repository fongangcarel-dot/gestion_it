<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/core/bootstrap.php';

if (is_authenticated()) {
	redirect('dashboard.php');
}

$error = null;
$email = '';
$appHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$appHostName = (string) parse_url('http://' . $appHost, PHP_URL_HOST);
if (in_array($appHostName, ['localhost', '127.0.0.1', '::1'], true)) {
	$networkHost = null;
	if (PHP_OS_FAMILY === 'Windows') {
		$ipconfig = (string) shell_exec('ipconfig');
		if (preg_match_all('/(?:IPv4 Address|Adresse IPv4)[^:]*:\s*([0-9.]+)/i', $ipconfig, $matches)) {
			$networkHost = end($matches[1]);
		}
	}
	$networkHost ??= gethostbyname(gethostname());
	if ($networkHost !== gethostname() && !in_array($networkHost, ['127.0.0.1', '::1'], true)) {
		$appHost = $networkHost . (str_contains($appHost, ':') ? ':' . parse_url('http://' . $appHost, PHP_URL_PORT) : '');
	}
}
$appUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $appHost . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/login.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim((string) ($_POST['email'] ?? ''));
	$password = (string) ($_POST['password'] ?? '');

	if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
		$error = 'Enter a valid email address and password.';
	} elseif (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
		$error = 'The form has expired. Please try again.';
	} else {
		$user = (new User($pdo))->authenticate($email, $password);
		if ($user === null) {
			$error = 'Invalid credentials.';
		} else {
			session_regenerate_id(true);
			$_SESSION['user'] = $user;
			redirect('dashboard.php');
		}
	}
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>SAVANA Islamic Finance | IT Help Desk</title>
	<link rel="stylesheet" href="assets/css/app.css?v=20260915">
</head>
<body class="login-page">
	<main class="login-shell">
		<section class="login-intro" aria-label="Savana Islamic Finance introduction">
			<img class="login-logo" src="assets/savana-logo.svg" alt="SAVANA Islamic Finance">
			<p class="eyebrow">SAVANA Islamic Finance</p>
			<h1>IT Help Desk</h1>
			<p class="intro-copy">One calm place for incidents, interventions, and the people who keep your workplace running.</p>
			<div class="intro-footer"><span class="status-dot"></span><span>Service desk online</span></div>
		</section>
		<section class="login-card">
			<div class="login-heading">
				<p class="eyebrow">Welcome back</p>
				<h2>Sign in to your workspace</h2>
				<p class="muted">Use your work email to continue.</p>
			</div>
			<?php if ($error !== null): ?>
				<p class="login-error" role="alert"><?= e($error) ?></p>
			<?php endif; ?>
			<form method="post" action="login.php" class="login-form">
				<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
				<div class="field">
					<label for="email">Email address</label>
					<div class="input-wrap"><span class="field-icon" aria-hidden="true">@</span><input id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" placeholder="you@company.com" required></div>
				</div>
				<div class="field">
					<label for="password">Password</label>
					<div class="input-wrap"><span class="field-icon" aria-hidden="true">*</span><input id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required></div>
				</div>
				<button class="login-button" type="submit"><span>Continue securely</span><span aria-hidden="true">-&gt;</span></button>
			</form>
			<p class="login-note">Access is protected by your account role and session security.</p>
			<div class="qr-panel">
				<p class="eyebrow">Open on your phone</p>
				<div id="app-qrcode" class="app-qrcode" aria-label="QR code for this IT Help Desk"></div>
				<p class="qr-url" id="app-url"></p>
				<p class="qr-help">Scan this code while your phone is connected to the same Wi-Fi network.</p>
			</div>
		</section>
	</main>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
	<script>
		const appUrl = <?= json_encode($appUrl, JSON_UNESCAPED_SLASHES) ?>;
		new QRCode(document.getElementById('app-qrcode'), {
			text: appUrl,
			width: 150,
			height: 150,
			colorDark: '#064b2b',
			colorLight: '#ffffff',
			correctLevel: QRCode.CorrectLevel.M
		});
		document.getElementById('app-url').textContent = appUrl;
	</script>
</body>
</html>