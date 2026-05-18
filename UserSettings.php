<?php

// Allows the user to change system-wide defaults for the theme - appearance, the number of records to show in searches and the language to display messages in.

require(__DIR__ . '/includes/session.php');

$Title = __('User Settings');
$ViewTopic = 'GettingStarted';
$BookMark = 'UserSettings';
include(__DIR__ . '/includes/header.php');



$PDFLanguages = array(
	__('Latin Western Languages - Times'),
	__('Eastern European Russian Japanese Korean Hebrew Arabic Thai'),
	__('Chinese'),
	__('Free Serif')
);

if (isset($_POST['Modify'])) {
	// no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible
	if ($_POST['DisplayRecordsMax'] <= 0) {
		$InputError = 1;
		prnMsg(__('The Maximum Number of Records on Display entered must not be negative') . '. ' . __('0 will default to system setting'),'error');
	}

	//!!!for the demo only - enable this check so password is not changed
	if ($AllowDemoMode AND $_POST['Password'] != '') {
		$InputError = 1;
		prnMsg(__('Cannot change password in the demo or others would be locked out!'),'warn');
	}

 	$UpdatePassword = 'N';

	if ($_POST['PasswordCheck'] != '') {
		if (mb_strlen($_POST['Password']) < 5) {
			$InputError = 1;
			prnMsg(__('The password entered must be at least 5 characters long'),'error');
		} elseif (mb_strstr($_POST['Password'],$_SESSION['UserID'])!= false) {
			$InputError = 1;
			prnMsg(__('The password cannot contain the user id'), 'error');
		}
		if ($_POST['Password'] != $_POST['PasswordCheck']) {
			$InputError = 1;
			prnMsg(__('The password and password confirmation fields entered do not match'), 'error');
		} else {
			$UpdatePassword = 'Y';
		}
	}

	if ($InputError != 1) {
		// no errors
		if (isset($_POST['Language']) && !checkLanguageChoice($_POST['Language'])) {
			$_POST['Language'] = $DefaultLanguage;
		}

		if ($UpdatePassword != 'Y') {
			$SQL = "UPDATE www_users
					SET displayrecordsmax='" . $_POST['DisplayRecordsMax'] . "',
						theme='" . $_POST['Theme'] . "',
						language='" . $_POST['Language'] . "',
						email='" . $_POST['email'] . "',
						showpagehelp='" . $_POST['ShowPageHelp'] . "',
						showfieldhelp='" . $_POST['ShowFieldHelp'] . "',
						pdflanguage='" . $_POST['PDFLanguage'] . "'
					WHERE userid = '" . $_SESSION['UserID'] . "'";
			$ErrMsg = __('The user alterations could not be processed because');
			$Result = DB_query($SQL, $ErrMsg);
			prnMsg( __('The user settings have been updated') . '. ' . __('Be sure to remember your password for the next time you login'),'success');
		} else {
			$SQL = "UPDATE www_users
					SET displayrecordsmax='" . $_POST['DisplayRecordsMax'] . "',
						theme='" . $_POST['Theme'] . "',
						language='" . $_POST['Language'] . "',
						email='" . $_POST['email'] ."',
						showpagehelp='" . $_POST['ShowPageHelp'] . "',
						showfieldhelp='" . $_POST['ShowFieldHelp'] . "',
						pdflanguage='" . $_POST['PDFLanguage'] . "',
						password='" . CryptPass($_POST['Password']) . "'
					WHERE userid = '" . $_SESSION['UserID'] . "'";
			$ErrMsg = __('The user alterations could not be processed because');
			$Result = DB_query($SQL, $ErrMsg);
			prnMsg(__('The user settings have been updated'),'success');
		}
		// Update the session variables to reflect user changes on-the-fly:
		$_SESSION['DisplayRecordsMax'] = $_POST['DisplayRecordsMax'];
		$_SESSION['Theme'] = trim($_POST['Theme']); /*already set by session.php but for completeness */
		$Theme = $_SESSION['Theme'];
		$_SESSION['Language'] = trim($_POST['Language']);
		$_SESSION['ShowPageHelp'] = $_POST['ShowPageHelp'];
		$_SESSION['ShowFieldHelp'] = $_POST['ShowFieldHelp'];
		$_SESSION['PDFLanguage'] = $_POST['PDFLanguage'];
		include($PathPrefix . 'includes/LanguageSetup.php'); // After last changes in LanguageSetup.php, is it required to update?
	}
}

$SQL = "SELECT
			email,
			showpagehelp,
			showfieldhelp,
			language
		from www_users WHERE userid = '" . $_SESSION['UserID'] . "'";
$Result = DB_query($SQL);
$MyRow = DB_fetch_array($Result);

if (!isset($_POST['email'])) {
	$_POST['email'] = $MyRow['email'];
}
$_POST['ShowPageHelp'] = $MyRow['showpagehelp'];
$_POST['ShowFieldHelp'] = $MyRow['showfieldhelp'];
$_POST['Language'] = $MyRow['language'];

echo '<style>
    /* Super Modern ERP Settings Styles */
    :root { --settings-bg: #ffffff; --settings-border: #e2e8f0; --settings-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01); }
    .modern-page-header { text-align: center; margin-top: 2rem; margin-bottom: 2.5rem; }
    .modern-page-header h1 { font-size: 2rem; font-weight: 800; color: #1e293b; margin: 0 0 0.5rem 0; letter-spacing: -0.025em; }
    .modern-page-header p { font-size: 1.05rem; color: #64748b; margin: 0 auto; max-width: 600px; }
    
    .modern-card { background: white; border-radius: 16px; box-shadow: var(--settings-shadow); padding: 2.5rem; margin: 1.5rem auto 3rem; max-width: 900px; border: 1px solid var(--settings-border); }
    .modern-card * { box-sizing: border-box; }
    .modern-card-title { color: #0f172a; font-weight: 800; margin-bottom: 2rem; font-size: 1.25rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 1rem; display: flex; align-items: center; gap: 10px; }
    
    .modern-form-grid { display: grid; grid-template-columns: 1fr; gap: 1.5rem 2.5rem; margin-bottom: 1rem; }
    @media (min-width: 768px) { .modern-form-grid { grid-template-columns: 1fr 1fr; } }
    
    .modern-field { display: flex; flex-direction: column; gap: 0.5rem; position: relative; }
    .modern-label { font-weight: 700; font-size: 0.8rem; color: #64748b; display: block; text-transform: uppercase; letter-spacing: 0.05em; }
    .modern-input, .modern-select { padding: 0.75rem 1rem; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 1rem; transition: all 0.2s; background: #f8fafc; width: 100%; color: #1e293b; font-weight: 500; appearance: none; -webkit-appearance: none; }
    .modern-select { background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394a3b8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E"); background-repeat: no-repeat; background-position: right 1rem center; background-size: 10px auto; padding-right: 2.5rem; }
    .modern-input:focus, .modern-select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-soft); background: white; }
    
    .modern-fieldhelp { font-size: 0.75rem; color: #94a3b8; font-weight: 400; margin-top: 0.2rem; display: block; }
    
    .modern-fieldtext { padding: 0.75rem 1rem; background: #f1f5f9; border-radius: 10px; font-weight: 600; color: #475569; font-size: 1rem; border: 1px dashed #cbd5e1; }
    
    .modern-form-actions { clear: both; display: flex; justify-content: flex-end; margin-top: 3rem; padding-top: 1.5rem; border-top: 2px solid #f1f5f9; }
    .modern-btn { background: var(--primary); color: white; padding: 1rem 2.5rem; border: none; border-radius: 50px; font-weight: 700; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 0.75rem; font-size: 1.05rem; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
    .modern-btn:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15); }
    .modern-btn svg { width: 20px; height: 20px; }
</style>';

echo '<div class="modern-page-header">
		<h1>' . __('User Settings') . '</h1>
        <p>' . __('Manage your account preferences, theme, and application behavior.') . '</p>
	</div>';

echo '<form action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" method="post">',
	'<input name="FormID" value="', $_SESSION['FormID'] ?? '', '" type="hidden" />';

echo '<div class="modern-card">';
echo '<div class="modern-card-title">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
        ' . __('Edit User Settings') . '
      </div>';

echo '<div class="modern-form-grid">';

echo '<div class="modern-field">
			<label class="modern-label" for="UserID">', __('User ID'), '</label>
			<div class="modern-fieldtext">', $_SESSION['UserID'], '</div>
		</div>';

echo '<div class="modern-field">
		<label class="modern-label" for="UsersRealName">', __('User Name'), '</label>
		<div class="modern-fieldtext">', $_SESSION['UsersRealName'] ?? '', '<input name="RealName" type="hidden" value="', $_SESSION['UsersRealName'] ?? '', '" /></div>
	</div>';

echo '<div class="modern-field">
		<label class="modern-label" for="email">', __('Email'), '</label>
		<input class="modern-input" name="email" id="email" type="email" value="', $_POST['email'], '" />
	</div>';

echo '<div class="modern-field">
		<label class="modern-label" for="DisplayRecordsMax">', __('Max Records to Display'), '</label>
		<input class="modern-input integer" id="DisplayRecordsMax" maxlength="3" name="DisplayRecordsMax" required="required" title="', __('The input must be positive integer'), '" type="text" value="', $_SESSION['DisplayRecordsMax'] ?? '', '" />
	</div>';

// Select language:
echo '<div class="modern-field">
		<label class="modern-label" for="Language">', __('Language'), '</label>
		<select class="modern-select" id="Language" name="Language">';
if (!isset($_POST['Language'])) {
	$_POST['Language'] = $_SESSION['Language'];
}
foreach($LanguagesArray as $LanguageEntry => $LanguageName) {
	echo '<option ';
	if (isset($_POST['Language']) AND $_POST['Language'] == $LanguageEntry) {
		echo 'selected="selected" ';
	}
	echo 'value="', $LanguageEntry, '">', $LanguageName['LanguageName'], '</option>';
}
echo '</select>
	</div>';

// Select theme:
echo '<div class="modern-field">
		<label class="modern-label" for="Theme">' . __('Theme') . '</label>
		<select class="modern-select" id="Theme" name="Theme">';

$ThemeDirectories = scandir($PathPrefix . 'css/');
foreach ($ThemeDirectories as $ThemeName) {
	if (is_dir('css/' . $ThemeName) AND $ThemeName != '.' AND $ThemeName != '..' AND $ThemeName != '.svn') {
		if ($_SESSION['Theme'] == $ThemeName) {
			echo '<option selected="selected" value="' . $ThemeName . '">' . $ThemeName . '</option>';
		} else {
			echo '<option value="' . $ThemeName . '">' . $ThemeName . '</option>';
		}
	}
}
if (!isset($_POST['PasswordCheck'])) {
	$_POST['PasswordCheck']='';
}
if (!isset($_POST['Password'])) {
	$_POST['Password']='';
}
echo '</select>
	</div>';

echo '<div class="modern-field">
		<label class="modern-label" for="Password">', __('New Password'), '</label>
		<input class="modern-input" id="Password" name="Password" pattern="(?!^', $_SESSION['UserID'], '$).{5,}" placeholder="', __('More than 5 characters'), '" title="', __('Must be more than 5 characters and cannot be as same as userid'), '" type="password" value="', $_POST['Password'], '" />
		<span class="modern-fieldhelp">', __('Leave empty to keep current password'), '</span>
	</div>';

echo '<div class="modern-field">
		<label class="modern-label" for="PasswordCheck">', __('Confirm Password'), '</label>
		<input class="modern-input" id="PasswordCheck" name="PasswordCheck" pattern="(?!^', $_SESSION['UserID'], '$).{5,}" placeholder="', __('More than 5 characters'), '" title="', __('Must be more than 5 characters and cannot be as same as userid'), '" type="password" value="', $_POST['PasswordCheck'], '" />
		<span class="modern-fieldhelp">', __('Confirm the new password'), '</span>
	</div>';

// Turn off/on page help:
echo '<div class="modern-field">
		<label class="modern-label" for="ShowPageHelp">', __('Display page help'), '</label>
		<select class="modern-select" id="ShowPageHelp" name="ShowPageHelp">';
if ($_POST['ShowPageHelp']==0) {
	echo '<option selected="selected" value="0">', __('No'), '</option>',
		 '<option value="1">', __('Yes'), '</option>';
} else {
	echo '<option value="0">', __('No'), '</option>',
 		 '<option selected="selected" value="1">', __('Yes'), '</option>';
}
echo '</select>
	<span class="modern-fieldhelp">', __('Show page help when available'), '</span>
</div>';

// Turn off/on field help:
echo '<div class="modern-field">
		<label class="modern-label" for="ShowFieldHelp">', __('Display field help'), '</label>
		<select class="modern-select" id="ShowFieldHelp" name="ShowFieldHelp">';
if ($_POST['ShowFieldHelp']==0) {
	echo '<option selected="selected" value="0">', __('No'), '</option>',
		 '<option value="1">', __('Yes'), '</option>';
} else {
	echo '<option value="0">', __('No'), '</option>',
 		 '<option selected="selected" value="1">', __('Yes'), '</option>';
}
echo '</select>
	<span class="modern-fieldhelp">', __('Show field help when available'), '</span>
</div>';

// PDF Language Support:
if (!isset($_POST['PDFLanguage'])) {
	$_POST['PDFLanguage']=$_SESSION['PDFLanguage'];
}
echo '<div class="modern-field">
		<label class="modern-label" for="PDFLanguage">', __('PDF Language Support'), '</label>
		<select class="modern-select" id="PDFLanguage" name="PDFLanguage">';
for($i=0; $i<count($PDFLanguages); $i++) {
	if ($_POST['PDFLanguage'] == $i) {
		echo '<option selected="selected" value="', $i, '">', $PDFLanguages[$i], '</option>';
	} else {
		echo '<option value="', $i, '">', $PDFLanguages[$i], '</option>';
	}
}
echo '</select>
	</div>';

echo '</div>'; // End of grid

echo '<div class="modern-form-actions">
		<button class="modern-btn" name="Modify" type="submit">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
            ', __('Save Settings'), '
        </button>
      </div>';
echo '</div>'; // End of card
echo '</form>';

include(__DIR__ . '/includes/footer.php');
