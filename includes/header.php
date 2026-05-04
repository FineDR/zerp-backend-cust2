<?php

// echo the html header and page title

// Variables which should be defined in the page this file is included with, before the inclusion of this header.php:
// $Language
// $Title
// various $_SESSION items: Theme, DefaultDateFormat, Timeout, ShowPageHelp, ShowFieldHelp, FontSize, UsersRealName, etc...

/// @todo there are any more global variables use in this script than those 3... are we sure it would work if
///       called within a function?
global $Language;
global $Title;
global $LanguagesArray;
global $RootPath;

//if (!isset($RootPath)) {
//	$RootPath = dirname(htmlspecialchars(basename(__FILE__)));
//	if ($RootPath == '/' or $RootPath == "\\") {
//		$RootPath = '';
//	}
//}

$ScriptName = basename($_SERVER['SCRIPT_NAME']);

if (!isset($ViewTopic)) {$ViewTopic = 'Contents';}
if (!isset($BookMark)) {$BookMark = '';}

/// @todo should we move this to session.php?
if (isset($_GET['Theme'])) {
	$_SESSION['Theme'] = $_GET['Theme'];
	$SQL = "UPDATE www_users SET theme='" . $_GET['Theme'] . "' WHERE userid='" . $_SESSION['UserID'] . "'";
	$Result = DB_query($SQL);
}

if (isset($_SESSION['Language']) && isset($LanguagesArray[$_SESSION['Language']]) && $LanguagesArray[$_SESSION['Language']]['Direction'] == 'rtl' and mb_substr($_SESSION['Theme'], -4) != '-rtl') {
	$_SESSION['Theme'] = $_SESSION['Theme'] . '-rtl';
}

if (!headers_sent()) {
	header('cache-control: no-cache, no-store, must-revalidate');
	header('Pragma: no-cache');
} else {
	trigger_error('Page output started before header file was included, this should not happen');
}

echo "<!DOCTYPE html>\n";

/// @todo handle better the case where $Language is not in xx-YY format (full spec is at https://www.rfc-editor.org/rfc/rfc5646.html)
echo '<html lang="' , str_replace('_', '-', substr($Language ?? 'en_GB', 0, 5)) , '">
<head>
	<meta http-equiv="Content-Type" content="application/html; charset=utf-8; cache-control: no-cache, no-store, must-revalidate; Pragma: no-cache" />
	<title>', __('webERP'), ' - ', $Title, '</title>
	<link rel="icon" href="', $RootPath, '/favicon.ico" type="image/x-icon" />
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link href="', $RootPath, '/css/', $_SESSION['Theme'], '/styles.css?version=1.2" rel="stylesheet" type="text/css" media="screen" />
	<link href="', $RootPath, '/css/print.css" rel="stylesheet" type="text/css" media="print" />
	<meta name="viewport" content="width=device-width, initial-scale=1">';
echo '	<script async src="', $RootPath, '/javascripts/MiscFunctions.js?version=1.0"></script>' , "\n";
echo '	<script async src="', $RootPath, '/javascripts/manual.js"></script>' , "\n";
echo '	<script>
		localStorage.setItem("DateFormat", "', $_SESSION['DefaultDateFormat'], '");
		localStorage.setItem("Theme", "', $_SESSION['Theme'], '");
	</script>' , "\n";

if (isset($_SESSION['Timeout'])) {
	echo '	<meta http-equiv="refresh" content="' . (60 * $_SESSION['Timeout']) . ';url=' . $RootPath . '/Logout.php" />', "\n";
}

if ($_SESSION['ShowPageHelp'] == 0) {
	echo '	<link href="', $RootPath, '/css/', $_SESSION['Theme'], '/page_help_off.css" rel="stylesheet" type="text/css" media="screen" />' , "\n";
} else {
	echo '	<link href="', $RootPath, '/css/', $_SESSION['Theme'], '/page_help_on.css" rel="stylesheet" type="text/css" media="screen" />' , "\n";
}

if ($_SESSION['ShowFieldHelp'] == 0) {
	echo '	<link href="', $RootPath, '/css/', $_SESSION['Theme'], '/field_help_off.css" rel="stylesheet" type="text/css" media="screen" />' , "\n";
} else {
	echo '	<link href="', $RootPath, '/css/', $_SESSION['Theme'], '/field_help_on.css" rel="stylesheet" type="text/css" media="screen" />' , "\n";
}

/// @todo should we move this to index.php?
if (isset($_GET['FontSize'])) {
	$SQL = "UPDATE www_users
				SET fontsize='" . $_GET['FontSize'] . "'
				WHERE userid = '" . $_SESSION['UserID'] . "'";
	$Result = DB_query($SQL);
	switch ($_GET['FontSize']) {
		case 0:
			$_SESSION['ScreenFontSize'] = '0';
			$_SESSION['FontSize'] = '0.667rem';
		break;
		case 1:
			$_SESSION['ScreenFontSize'] = '1';
			$_SESSION['FontSize'] = '0.833rem';
		break;
		case 2:
			$_SESSION['ScreenFontSize'] = '2';
			$_SESSION['FontSize'] = '1rem';
		break;
		default:
			$_SESSION['ScreenFontSize'] = '1';
			$_SESSION['FontSize'] = '0.833rem';
	}
}

echo '	<style>
		body {
			font-size: ', $_SESSION['FontSize'], ';
		}
	</style>';

if (isset($ExtraHeadContent)) {
	echo "\n" . $ExtraHeadContent;
}

echo "\n</head>\n";

echo '<body onload="initial();' . ($BodyOnLoad ?? '') . '">' . "\n";

echo '<div class="help-bubble" id="help-bubble">
		<link rel="stylesheet" type="text/css" href="'. $RootPath . '/doc/Manual/css/manual.css" />
		<div class="help-header" id="help-header">
			<div id="help_exit" class="close_button" onclick="CloseHelp()" title="', __('Close this window'), '">X</div>
		</div>
		<div class="help-content" id="help-content"></div>
	</div>';

echo '<div class="dashboard-container">
		<div id="SidebarMask" class="sidebar-mask"></div>';

// Icon map for sidebar modules
$moduleIcons = [
    'Dashboard'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>',
    'Sales'           => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>',
    'Receivables'     => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
    'Purchases'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>',
    'Payables'        => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>',
    'Inventory'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>',
    'Manufacturing'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>',
    'General Ledger'  => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>',
    'Asset Manager'   => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>',
    'Petty Cash'      => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>',
    'Setup'           => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>',
    'Utilities'       => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>',
];

echo '<nav class="ModuleList">
		<ul>';

$dashboardActiveClass = (!isset($_GET['Application']) || $_GET['Application'] == 'Dashboard') ? 'class="ModuleSelected"' : '';
$dashIcon = $moduleIcons['Dashboard'];
echo '<li ' . $dashboardActiveClass . '><a href="' . $RootPath . '/index.php?Application=Dashboard"><span class="nav-icon">' . $dashIcon . '</span><span class="nav-label">' . __('Dashboard') . '</span></a></li>';

if (isset($ModuleLink)) {
	for ($i=0; $i < count($ModuleLink); $i++) {
		if (in_array($ModuleLink[$i], $_SESSION['ModulesEnabled']) OR (isset($_SESSION['ModulesEnabled'][$i]) AND $_SESSION['ModulesEnabled'][$i] == '1')) {
			$activeClass = (isset($_SESSION['Module']) AND $ModuleLink[$i] == $_SESSION['Module']) ? 'class="ModuleSelected"' : '';
			$icon = $moduleIcons[$ModuleList[$i]] ?? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>';
			echo '<li ' . $activeClass . '><a href="' . $RootPath . '/index.php?Application=' . urlencode($ModuleLink[$i]) . '"><span class="nav-icon">' . $icon . '</span><span class="nav-label">' . $ModuleList[$i] . '</span></a></li>';
		}
	}
}

echo '	</ul>
	</nav>';


echo '<div class="dashboard-content">';

echo '<header class="noPrint">
		<button id="SidebarToggle" class="SidebarToggle" aria-label="Toggle Navigation">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
		</button>';

$CompanyLogo = '';
if (file_exists('companies/' . $_SESSION['DatabaseName'] . '/logo.png')) {
	$CompanyLogo = $RootPath . '/companies/' . $_SESSION['DatabaseName'] . '/logo.png';
} elseif (file_exists('companies/' . $_SESSION['DatabaseName'] . '/logo.jpeg')) {
	$CompanyLogo = $RootPath . '/companies/' . $_SESSION['DatabaseName'] . '/logo.jpeg';
} elseif (file_exists('companies/' . $_SESSION['DatabaseName'] . '/logo.jpg')) {
	$CompanyLogo = $RootPath . '/companies/' . $_SESSION['DatabaseName'] . '/logo.jpg';
} elseif (file_exists('companies/' . $_SESSION['DatabaseName'] . '/logo.gif')) {
	$CompanyLogo = $RootPath . '/companies/' . $_SESSION['DatabaseName'] . '/logo.gif';
}

echo '<div id="AppIcon"><a href="' . $RootPath . '/index.php"></a></div>';

if (isset($_SESSION['AllowedPageSecurityTokens']) && is_array($_SESSION['AllowedPageSecurityTokens']) && count($_SESSION['AllowedPageSecurityTokens']) > 1) {
	echo '<div id="ActionIcon">
			<select name="Favourites" id="favourites" onchange="window.open (this.value,\'_self\',false)">';
	echo '<option value=""><i>', __('Commonly used scripts'), '</i></option>';
	if (isset($_SESSION['Favourites'])) {
		foreach ($_SESSION['Favourites'] as $Url => $Caption) {
			echo '<option value="', $Url, '">', __($Caption), '</option>';
		}
	}
	echo '</select>
		</div>';
}

echo '<div id="Info">
		<a class="FontSize" data-title="', __('Change the settings for'), ' ', $_SESSION['UsersRealName'], '" href="', $RootPath, '/UserSettings.php">
			<span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></span> ', $_SESSION['UsersRealName'], '
		</a>
	</div>';

echo '<div id="ExitIcon">
		<a data-title="', __('Logout'), '" href="#" id="logoutLink">
			<span class="icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></span>
		</a>
	</div>';

echo '</header>';

echo '<section class="MainBody">';

if ($ScriptName != 'index.php' && $ScriptName != 'Dashboard.php') {
	if (isset($_SESSION['Module'])) {
		$SQL = "SELECT modulename FROM modules WHERE modulelink='" . $_SESSION['Module'] . "'";
		$Result = DB_query($SQL);
		if (DB_num_rows($Result) > 0) {
			$MyRow = DB_fetch_array($Result);
			echo '<div class="ScriptTitle"><a href="index.php?Application=' . $_SESSION['Module'] . '">', $MyRow['modulename'] . '</a> -> '. $Title, '</div>';
		} else {
			echo '<div class="ScriptTitle">', $Title, '</div>';
		}
	} else {
		echo '<div class="ScriptTitle">', $Title, '</div>';
	}
} elseif ($ScriptName == 'Dashboard.php') {
    echo '<div class="ScriptTitle">', __('Dashboard'), '</div>';
}

echo '<div id="MessageContainerHead"></div>';

echo '<div id="mask"></div>
	<dialog id="logoutDialog">
		<div id="DialogContainer">
			<h3 id="LogoutDialogHeader">', __('Confirm Logout'), '</h3>
			<p id="LogoutDialogText">', __('Are you sure you wish to logout?'), '</p>
			<div id="DialogButtonContainer">
				<button id="cancelLogout">', __('Cancel'), '</button>
				<button id="confirmLogout">', __('Logout'), '</button>
			</div>
		</div>
	</dialog>
	<script async src="', $RootPath, '/javascripts/dialogs.js?version=1.0"></script>
	<script>
		const sidebarToggle = document.getElementById("SidebarToggle");
		const sidebarMask = document.getElementById("SidebarMask");

		function toggleSidebar() {
			if (window.innerWidth > 1024) {
				document.body.classList.toggle("sidebar-collapsed");
			} else {
				document.body.classList.toggle("sidebar-active");
			}
		}

		sidebarToggle.onclick = toggleSidebar;
		sidebarMask.onclick = function() {
			document.body.classList.remove("sidebar-active");
		};
	</script>';
