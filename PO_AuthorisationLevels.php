<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Purchase Order Authorisation Maintenance');
$ViewTopic = '';
$BookMark = 'PO_AuthorisationLevels';
include(__DIR__ . '/includes/header.php');

echo '<div class="db-page">
		<div class="db-page-header">
			<div>
				<h1 class="db-page-title">' . $Title . '</h1>
				<p class="db-page-subtitle">' . __('Manage user authority for purchase order approvals') . '</p>
			</div>
		</div>';

/*Note: If CanCreate==0 then this means the user can create orders
 *     Also if OffHold==0 then the user can release purchase invocies
 *     This logic confused me a bit to start with
 */

if (isset($_POST['Submit'])) {
	if (isset($_POST['CanCreate']) AND $_POST['CanCreate']=='on') {
		$CanCreate=0;
	} else {
		$CanCreate=1;
	}
	if (isset($_POST['OffHold']) AND $_POST['OffHold']=='on') {
		$OffHold=0;
	} else {
		$OffHold=1;
	}
	if ($_POST['AuthLevel']=='') {
		$_POST['AuthLevel']=0;
	}
	$SQL="SELECT COUNT(*)
		FROM purchorderauth
		WHERE userid='" . $_POST['UserID'] . "'
		AND currabrev='" . $_POST['CurrCode'] . "'";
	$Result = DB_query($SQL);
	$MyRow=DB_fetch_array($Result);
	if ($MyRow[0]==0) {
		$SQL="INSERT INTO purchorderauth ( userid,
						currabrev,
						cancreate,
						offhold,
						authlevel)
					VALUES( '".$_POST['UserID']."',
						'".$_POST['CurrCode']."',
						'".$CanCreate."',
						'".$OffHold."',
						'" . filter_number_format($_POST['AuthLevel'])."')";
	$ErrMsg = __('The authentication details cannot be inserted because');
	$Result = DB_query($SQL, $ErrMsg);
	} else {
		prnMsg(__('There already exists an entry for this user/currency combination'), 'error');
		echo '<br />';
	}
}

if (isset($_POST['Update'])) {
	if (isset($_POST['CanCreate']) AND $_POST['CanCreate']=='on') {
		$CanCreate=0;
	} else {
		$CanCreate=1;
	}
	if (isset($_POST['OffHold']) AND $_POST['OffHold']=='on') {
		$OffHold=0;
	} else {
		$OffHold=1;
	}
	$SQL="UPDATE purchorderauth SET
			cancreate='".$CanCreate."',
			offhold='".$OffHold."',
			authlevel='".filter_number_format($_POST['AuthLevel'])."'
			WHERE userid='".$_POST['UserID']."'
			AND currabrev='".$_POST['CurrCode']."'";

	$ErrMsg = __('The authentication details cannot be updated because');
	$Result = DB_query($SQL, $ErrMsg);
}

if (isset($_GET['Delete'])) {
	$SQL="DELETE FROM purchorderauth
		WHERE userid='".$_GET['UserID']."'
		AND currabrev='".$_GET['Currency']."'";

	$ErrMsg = __('The authentication details cannot be deleted because');
	$Result = DB_query($SQL, $ErrMsg);
}

if (isset($_GET['Edit'])) {
	$SQL="SELECT cancreate,
				offhold,
				authlevel
			FROM purchorderauth
			WHERE userid='".$_GET['UserID']."'
			AND currabrev='".$_GET['Currency']."'";
	$ErrMsg = __('The authentication details cannot be retrieved because');
	$Result = DB_query($SQL, $ErrMsg);
	$MyRow=DB_fetch_array($Result);
	$UserID=$_GET['UserID'];
	$Currency=$_GET['Currency'];
	$CanCreate=$MyRow['cancreate'];
	$OffHold=$MyRow['offhold'];
	$AuthLevel=$MyRow['authlevel'];
}

$SQL="SELECT purchorderauth.userid,
			www_users.realname,
			currencies.currabrev,
			currencies.currency,
			currencies.decimalplaces,
			purchorderauth.cancreate,
			purchorderauth.offhold,
			purchorderauth.authlevel
	FROM purchorderauth INNER JOIN www_users
		ON purchorderauth.userid=www_users.userid
	INNER JOIN currencies
		ON purchorderauth.currabrev=currencies.currabrev";

$ErrMsg = __('The authentication details cannot be retrieved because');
$Result = DB_query($SQL, $ErrMsg);

echo '<div class="db-card">
		<div class="db-card-title">' . __('Existing Authorisation Levels') . '</div>
		<div class="db-card-body">
			<div class="db-table-wrapper">
				<table class="db-table">
					<thead>
						<tr>
							<th>' . __('User') . '</th>
							<th>' . __('Currency') . '</th>
							<th class="text-center">' . __('Create') . '</th>
							<th class="text-center">' . __('Rel. Inv.') . '</th>
							<th class="text-right">' . __('Authority Level') . '</th>
							<th class="text-center">' . __('Actions') . '</th>
						</tr>
					</thead>
					<tbody>';

	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr>
			<td>
				<div class="db-font-bold text-primary">' . $MyRow['userid'] . '</div>
				<div class="db-text-muted db-font-sm">' . $MyRow['realname'] . '</div>
			</td>
			<td>', __($MyRow['currency']), '</td>
			<td class="text-center">' . ($MyRow['cancreate']==0 ? '<span class="db-badge db-badge-success">' . __('Yes') . '</span>' : '<span class="db-badge db-badge-danger">' . __('No') . '</span>') . '</td>
			<td class="text-center">' . ($MyRow['offhold']==0 ? '<span class="db-badge db-badge-success">' . __('Yes') . '</span>' : '<span class="db-badge db-badge-danger">' . __('No') . '</span>') . '</td>
			<td class="text-right db-font-semibold">' . locale_number_format($MyRow['authlevel'], $MyRow['decimalplaces']) . '</td>
			<td class="text-center">
				<div class="db-table-actions">
					<a href="'.$RootPath.'/PO_AuthorisationLevels.php?Edit=Yes&amp;UserID=' . $MyRow['userid'] . '&amp;Currency='.$MyRow['currabrev'].'" class="db-btn db-btn-outline db-btn-sm">' . __('Edit') . '</a>
					<a href="'.$RootPath.'/PO_AuthorisationLevels.php?Delete=Yes&amp;UserID=' . $MyRow['userid'] . '&amp;Currency='.$MyRow['currabrev'].'" class="db-btn db-btn-danger db-btn-sm" onclick="return confirm(\'' . __('Are you sure?') . '\');">' . __('Delete') . '</a>
				</div>
			</td>
		</tr>';
}

echo '					</tbody>
					</table>
				</div>
			</div>
		</div>';

if (!isset($_GET['Edit'])) {
	$UserID=$_SESSION['UserID'];
	$Currency=$_SESSION['CompanyRecord']['currencydefault'];
	$CanCreate=0;
	$OffHold=0;
	$AuthLevel=0;
}

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" id="form1">
	<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
	<div class="db-card" style="margin-top: var(--space-4);">
		<div class="db-card-title">
			<span><svg class="db-card-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/></svg> ' . (isset($_GET['Edit']) ? __('Update Authorisation Level') : __('Define New Authorisation Level')) . '</span>
		</div>
		<div class="db-card-body">
			<div class="db-grid db-grid-2">';

if (isset($_GET['Edit'])) {
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('User Account') . ':</label>
			<div class="db-form-static">' . $_GET['UserID'] . '</div>
			<input type="hidden" name="UserID" value="'.$_GET['UserID'].'" />
		  </div>';
} else {
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Select User Account') . ':</label>
			<select name="UserID" class="db-form-select">';
	$UserSQL="SELECT userid FROM www_users";
	$Userresult=DB_query($UserSQL);
	while ($MyRow=DB_fetch_array($Userresult)) {
		$selected = ($MyRow['userid']==$UserID) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="'.$MyRow['userid'].'">' . $MyRow['userid'] . '</option>';
	}
	echo '	</select>
		  </div>';
}

if (isset($_GET['Edit'])) {
	$SQL="SELECT currency, decimalplaces FROM purchorderauth INNER JOIN currencies ON purchorderauth.currabrev=currencies.currabrev WHERE userid='".$_GET['UserID']."' AND purchorderauth.currabrev='".$_GET['Currency']."'";
	$Result = DB_query($SQL);
	$MyRow=DB_fetch_array($Result);
	$CurrDecimalPlaces=$MyRow['decimalplaces'];

	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Currency') . ':</label>
			<div class="db-form-static">' . $MyRow['currency'] . '</div>
			<input type="hidden" name="CurrCode" value="'.$_GET['Currency'].'" />
		  </div>';
} else {
	echo '<div class="db-form-group">
			<label class="db-form-label">' . __('Select Currency') . ':</label>
			<select name="CurrCode" class="db-form-select">';
	$Currencysql="SELECT currabrev,currency,decimalplaces FROM currencies";
	$Currencyresult=DB_query($Currencysql);
	while ($MyRow=DB_fetch_array($Currencyresult)) {
		$selected = ($MyRow['currabrev']==$Currency) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="'.$MyRow['currabrev'].'">' . $MyRow['currency'] . '</option>';
	}
	$CurrDecimalPlaces=2;
	echo '	</select>
		  </div>';
}

echo '		<div class="db-form-group">
				<label class="db-form-label">' . __('Authority Level Amount') . ':</label>
				<input type="text" name="AuthLevel" class="db-form-input text-right" value="'  . locale_number_format($AuthLevel,$CurrDecimalPlaces) . '" placeholder="0.00" />
				<p class="db-form-help">' . __('Maximum amount this user can authorize') . '</p>
			</div>
			<div class="db-form-group" style="display:flex; flex-direction:column; gap:var(--space-2); justify-content:center;">
				<label class="db-checkbox-container">
					<input type="checkbox" name="CanCreate" ' . ($CanCreate==0 ? 'checked="checked"' : '') . ' />
					<span class="db-checkbox-label">' . __('Allow this user to create purchase orders') . '</span>
				</label>
				<label class="db-checkbox-container">
					<input type="checkbox" name="OffHold" ' . ($OffHold==0 ? 'checked="checked"' : '') . ' />
					<span class="db-checkbox-label">' . __('Allow this user to release supplier invoices') . '</span>
				</label>
			</div>
		</div>
		</div>
		<div class="db-card-footer db-form-actions">
			<button type="submit" name="' . (isset($_GET['Edit']) ? 'Update' : 'Submit') . '" class="db-btn db-btn-primary">' . (isset($_GET['Edit']) ? __('Update Information') : __('Create Authority Level')) . '</button>';
if (isset($_GET['Edit'])) {
	echo '	<a href="'.$RootPath.'/PO_AuthorisationLevels.php" class="db-btn db-btn-secondary">' . __('Cancel') . '</a>';
}
echo '		</div>
	</div>
</form>
</div> <!-- End db-page -->';
include(__DIR__ . '/includes/footer.php');
