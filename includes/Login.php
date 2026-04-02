<?php

/// @todo should we include these two here? This file is included from session.php, which already includes them...
include($PathPrefix . 'includes/LanguageSetup.php');
include(__DIR__ . '/LanguagesArray.php');

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
	echo '<link rel="stylesheet" href="css/login.css?v=', filemtime(__DIR__ . '/../css/login.css'), '" type="text/css" />';
}
echo '</head>';

$_SESSION['FormID'] = sha1(uniqid(mt_rand(), true));

$DefaultCompany = $_COOKIE['Login'] ?? $DefaultDatabase;

// Read companies directory once and collect all company information
$CompanyList = array();

// If demo mode is enabled, only show weberpdemo company
if ((isset($AllowDemoMode)) and ($AllowDemoMode == true)) {
	$CompanyEntry = 'weberpdemo';
	if (is_dir('companies/' . $CompanyEntry)) {
		if (file_exists('companies/' . $CompanyEntry . '/Companies.php')) {
			include('companies/' . $CompanyEntry . '/Companies.php');
		} else {
			$CompanyName[$CompanyEntry] = $CompanyEntry;
		}

		// Store company information for later use
		$CompanyList[$CompanyEntry] = array(
			'name' => $CompanyName[$CompanyEntry],
			'has_png_logo' => file_exists('companies/' . $CompanyEntry . '/logo.png'),
			'has_jpg_logo' => file_exists('companies/' . $CompanyEntry . '/logo.jpg')
		);
	}
} else {
	// Normal mode - show all companies
	$DirHandle = dir($PathPrefix . 'companies/');

	while (false !== ($CompanyEntry = $DirHandle->read())) {
		if (is_dir('companies/' . $CompanyEntry) and $CompanyEntry != '..' and $CompanyEntry != '' and $CompanyEntry != '.' and $CompanyEntry != 'weberpdemo') {
			if (file_exists('companies/' . $CompanyEntry . '/Companies.php')) {
				include('companies/' . $CompanyEntry . '/Companies.php');
			} else {
				$CompanyName[$CompanyEntry] = $CompanyEntry;
			}

			// Store company information for later use
			$CompanyList[$CompanyEntry] = array(
				'name' => $CompanyName[$CompanyEntry],
				'has_png_logo' => file_exists('companies/' . $CompanyEntry . '/logo.png'),
				'has_jpg_logo' => file_exists('companies/' . $CompanyEntry . '/logo.jpg')
			);
		}
	}
	$DirHandle->close();
}

if ($AllowDemoMode == true) {
	$DefaultCompany = 'weberpdemo';
}

$DefaultCompanyInfo = $CompanyList[$DefaultCompany] ?? null;
$LoginLogo = '';
if (!empty($DefaultCompanyInfo['has_png_logo'])) {
	$LoginLogo = 'companies/' . $DefaultCompany . '/logo.png';
} elseif (!empty($DefaultCompanyInfo['has_jpg_logo'])) {
	$LoginLogo = 'companies/' . $DefaultCompany . '/logo.jpg';
}

echo '<body>
	<div id="container">
		<div class="login-shell">
			<div id="login_box">
				<form action="' . $RootPath . '/index.php" name="LogIn" method="post" class="noPrint">
				<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
				<input type="hidden" name="NewLogin" value="Yes" />
				<div class="login-branding">';

if ($LoginLogo != '') {
	echo '<div class="login-brand-mark">
			<img src="' . $LoginLogo . '" alt="' . htmlspecialchars($DefaultCompanyName ?? $DefaultCompany, ENT_QUOTES, 'UTF-8') . '" />
		</div>';
} else {
	echo '<div id="login_logo">
			<div class="logo logo-left">web</div><div class="logo logo-right">ERP</div>
		</div>';
}

echo '<div class="login-card-header">
			<p class="login-card-kicker">' . __('Welcome back') . '</p>
			<h2>' . __('Sign in to your account') . '</h2>
			<p class="login-card-copy">' . __('Use your credentials to continue to the system dashboard.') . '</p>
		</div>
	</div>';
// Generate appropriate company selection UI based on configuration
if ($AllowCompanySelectionBox === 'Hide') {
	// do not show input or selection box
	echo '<input type="hidden" name="CompanyNameField"  value="' . $DefaultCompany . '" />';
} elseif ($AllowCompanySelectionBox === 'ShowInputBox') {
	// show input box
	echo '<input type="text" required="required" autofocus="autofocus" name="CompanyNameField"  value="' . $DefaultCompany . '" />';
} else {
	// Show selection box ($AllowCompanySelectionBox == 'ShowSelectionBox')
	echo '<select name="CompanyNameField" id="CompanyNameField">';

	if (!isset($_COOKIE["Company"])) {
		$Company = $DefaultCompany;
	} else {
		$Company = $_COOKIE["Company"];
	}

	foreach ($CompanyList as $CompanyEntry => $CompanyInfo) {
		if ($Company == $CompanyEntry) {
			echo '<option selected="selected" value="' . $CompanyEntry . '">' . $CompanyInfo['name'] . '</option>';
		} else {
			echo '<option value="' . $CompanyEntry . '">' . $CompanyInfo['name'] . '</option>';
		}
	}

	echo '</select>';
}

if ($AllowCompanySelectionBox != 'Hide') {
	echo '<div class="field-group company-group">
			<label for="CompanySelect">', __('Company'), '</label>';
	$DefaultCompanyName = $CompanyName[$DefaultCompany] ?? $DefaultCompany;
	echo '<input type="text" id="CompanySelect" readonly value="' . $DefaultCompanyName . '" />';
	if (!isset($ShowLogoAtLogin) OR ($ShowLogoAtLogin == true)) {
		echo '<ol id="dropdownlist" class="dropdownlist" style="padding-bottom:10px;">';
	} else {
		echo '<ol id="dropdownlist" class="dropdownlist" style="padding-bottom:15px;">';
	}

	// Generate company list with logos
	foreach ($CompanyList as $CompanyEntry => $CompanyInfo) {
		if (!isset($ShowLogoAtLogin) OR ($ShowLogoAtLogin == true)) {
			if ($CompanyInfo['has_png_logo']) {
				echo '<li class="option" id="' . $CompanyEntry . '" ><img id="optionlogo" src="companies/' . $CompanyEntry . '/logo.png" /><span id="optionlabel">', $CompanyInfo['name'], '</span></li>';
			} elseif ($CompanyInfo['has_jpg_logo']) {
				echo '<li class="option" id="' . $CompanyEntry . '" ><img id="optionlogo" src="companies/' . $CompanyEntry . '/logo.jpg" /><span id="optionlabel">', $CompanyInfo['name'], '</span></li>';
			}
		} else {
			echo '<li class="option" id="' . $CompanyEntry . '" ><span style="top:0px" id="optionlabel">', $CompanyInfo['name'], '</span></li>';
		}
	}

	echo '</ol>
		</div>';
}

echo '<div class="field-group">
		<label for="username">', __('User name'), '</label>
		<div class="input-shell">
			<span class="input-icon input-user"></span>
			<input type="text" id="username" autocomplete="username" autofocus="autofocus" required="required" name="UserNameEntryField" placeholder="', __('Enter your username'), '" maxlength="20" />
		</div>
	</div>
	<div class="field-group">
		<label for="password">', __('Password'), '</label>
		<div class="password-field">
			<span class="input-icon input-lock"></span>
			<input type="password" autocomplete="current-password" id="password" required="required" name="Password" placeholder="', __('Enter your password'), '" />
			<input type="text" id="eye" readonly title="', __('Show Password'), '" data-show-title="', __('Show Password'), '" data-hide-title="', __('Hide Password'), '" />
		</div>
	</div>';

if (!empty($DemoText)) {
	echo '<div id="demo_text">';
	echo $DemoText;
	echo '</div>';
}

echo '<label class="remember-row" for="remember_me">
		<input type="checkbox" id="remember_me" name="RememberMe" value="1" />
		<span>' . __('Remember me') . '</span>
	</label>
	<div class="login-actions">
		<button class="button" type="submit" value="', __('Login'), '" name="SubmitUser" onclick="ShowSpinner()">
			<img id="waiting_show" class="waiting_show" src="css/waiting.gif" />', __('Sign In'), ' ', '<img src="css/tick.png" title="', __('Login'), '" alt="" class="ButtonIcon" />
		</button>
		<p class="login-copyright">&copy; ', date('Y'), ' ', __('All rights reserved.'), '</p>
	</div>';

echo '</form>
			</div>
		</div>
	</div>';

echo '</body>
	</html>';
