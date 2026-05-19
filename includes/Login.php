<?php

/// @todo should we include these two here? This file is included from session.php, which already includes them...
include(__DIR__ . '/LanguagesArray.php');

if (!function_exists('__')) {
	function __($Text) {
		return $Text;
	}
}

if (!isset($DefaultLanguage) || !isset($LanguagesArray[$DefaultLanguage])) {
	$DefaultLanguage = 'en_GB.utf8';
}

if (!isset($Language) || $Language == '') {
	$Language = $DefaultLanguage;
}

if (!isset($Theme) || $Theme == '') {
	$Theme = $DefaultTheme ?? 'xenos';
}

// Display demo user name and password within login form if $AllowDemoMode is true
if ((isset($AllowDemoMode)) and ($AllowDemoMode == true) and (!isset($DemoText))) {
	$DemoText = __('Login as user') . ': <i>' . __('admin') . '</i><br />' . __('with password') . ': <i>' . __('weberp') . '</i>';
} elseif (!isset($DemoText)) {
	$DemoText = __('Please login here');
}

echo "<!DOCTYPE html>\n";
/// @todo handle better the case where $Language is not in xx-YY format (full spec is at https://www.rfc-editor.org/rfc/rfc5646.html)
echo '<html lang="' , str_replace('_', '-', substr($Language, 0, 5)) , '">
	<head>
		<title>WebERP ', __('Login screen'), '</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
		<meta http-equiv="Pragma" content="no-cache" />
		<meta http-equiv="Expires" content="0" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="icon" href="favicon.ico" type="image/x-icon" />
		<script async src="', $RootPath, '/javascripts/Login.js?v=', filemtime(__DIR__ . '/../javascripts/Login.js'), '"></script>';

if ($LanguagesArray[$DefaultLanguage]['Direction'] == 'rtl') {
	echo '<link rel="stylesheet" href="css/login_rtl.css" type="text/css" />';
} else {
	// Reusing the central Green System design system
	echo '<link rel="stylesheet" href="css/modern-zerp/login.css?v=', filemtime(__DIR__ . '/../css/modern-zerp/login.css'), '" type="text/css" />';
}
echo '</head>';

$_SESSION['FormID'] = sha1(uniqid(mt_rand(), true));

// Default to the first available company or cookie
$DefaultCompany = $_COOKIE['Login'] ?? $DefaultDatabase;

echo '<body>
	<div class="animation-wrapper">
		<div class="bg-blob blob-1"></div>
		<div class="bg-blob blob-2"></div>
		<div class="bg-blob blob-3"></div>
	</div>
	<div id="container">
		<div class="login-shell">
			<div id="login_box">
				<form action="' . $RootPath . '/index.php" name="LogIn" method="post" class="noPrint">
				<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
				<input type="hidden" name="NewLogin" value="Yes" />
				
				<!-- Force Default Company (Hiding choice as requested) -->
				<input type="hidden" name="CompanyNameField" value="', $DefaultCompany, '" />

				<div class="login-branding">
					<div class="login-brand-mark">
						<img src="images/logo1.png" alt="Zerp ERP" />
					</div>
					<div class="login-card-header">
						<p class="login-card-kicker">' . __('AUTHENTICATION') . '</p>
						<h2>' . __('Welcome back') . '</h2>
						<p class="login-card-copy">' . __('Please sign in to access your business workstation.') . '</p>
					</div>
				</div>';

echo '<div class="field-group">
		<label for="username">', __('User name'), '</label>
		<div class="input-shell">
			<input type="text" id="username" autocomplete="username" autofocus="autofocus" required="required" name="UserNameEntryField" placeholder="', __('Enter your username'), '" maxlength="20" />
		</div>
	</div>
	<div class="field-group">
		<label for="password">', __('Password'), '</label>
		<div class="password-field">
			<input type="password" autocomplete="current-password" id="password" required="required" name="Password" placeholder="', __('Enter your password'), '" />
			<input type="text" id="password_visible" style="display:none" placeholder="', __('Enter your password'), '" />
			<span id="eye" title="', __('Show Password'), '" onclick="togglePassword()">👁️</span>
		</div>
	</div>
	<script>
		function togglePassword() {
			var pwd = document.getElementById("password");
			var pwd_v = document.getElementById("password_visible");
			var eye = document.getElementById("eye");

			if (pwd.style.display !== "none") {
				// Switch to Visible
				pwd_v.value = pwd.value;
				pwd.style.display = "none";
				pwd_v.style.display = "block";
				pwd_v.focus();
				eye.textContent = "🙈";
				eye.title = "', __('Hide Password'), '";
			} else {
				// Switch to Hidden
				pwd.value = pwd_v.value;
				pwd_v.style.display = "none";
				pwd.style.display = "block";
				pwd.focus();
				eye.textContent = "👁️";
				eye.title = "', __('Show Password'), '";
			}
		}
		
		// Synchronize values on every keystroke
		document.getElementById("password").addEventListener("input", function() {
			document.getElementById("password_visible").value = this.value;
		});
		document.getElementById("password_visible").addEventListener("input", function() {
			document.getElementById("password").value = this.value;
		});
	</script>';


echo '<label class="remember-row" for="remember_me">
		<input type="checkbox" id="remember_me" name="RememberMe" value="1" />
		<span>' . __('Remember me on this device') . '</span>
	</label>
	<div class="login-actions">
		<button class="button" type="submit" value="', __('Login'), '" name="SubmitUser" onclick="ShowSpinner()">
			', __('Sign In'), '
		</button>
		<p class="login-copyright">&copy; ', date('Y'), ' ZERP ERP ', __('All rights reserved.'), '</p>
	</div>';

echo '</form>
			</div>
		</div>
	</div>';

echo '</body>
	</html>';
