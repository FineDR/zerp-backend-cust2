<?php

require(__DIR__ . '/includes/session.php');

$Title = __('Inventory Item Notes');
$ViewTopic = 'Inventory';
$BookMark = 'ItemNotes';
include(__DIR__ . '/includes/header.php');
include(__DIR__ . '/includes/SQL_CommonFunctions.php');

if (isset($_GET['Id'])) {
	$Id = (int)$_GET['Id'];
} elseif (isset($_POST['Id'])) {
	$Id = (int)$_POST['Id'];
}
if (isset($_POST['StockID'])) {
	$StockID = $_POST['StockID'];
} elseif (isset($_GET['StockID'])) {
	$StockID = $_GET['StockID'];
} else {
	$StockID = '';
}

if (isset($_POST['submit']) && $StockID != '') {
	$InputError = 0;
	if (trim($_POST['Note']) == '') {
		$InputError = 1;
		prnMsg(__('Note content cannot be empty'), 'error');
	}

	if (isset($Id) && $InputError != 1) {
		$SQL = "UPDATE stockitemnotes SET note='" . $_POST['Note'] . "', date='" . $_POST['NoteDate'] . "' WHERE stockid ='" . $StockID . "' AND noteid='" . $Id . "'";
		DB_query($SQL);
		prnMsg(__('Note updated'), 'success');
		unset($Id);
	} elseif ($InputError != 1) {
		$SQL = "INSERT INTO stockitemnotes (stockid, note, date) VALUES ('" . $StockID. "', '" . $_POST['Note'] . "', '" . $_POST['NoteDate'] . "')";
		DB_query($SQL);
		prnMsg(__('New note added'), 'success');
	}
	unset($_POST['Note']);
} elseif (isset($_GET['delete'])) {
	$SQL = "DELETE FROM stockitemnotes WHERE noteid='".$Id."' AND stockid='".$StockID."'";
	DB_query($SQL);
	prnMsg(__('Note removed'), 'success');
	unset($Id);
}

echo '<div class="db-page">
		<div class="db-page-header">
			<div class="db-page-title"><i class="fas fa-sticky-note"></i> ' . $Title . '</div>
			<div class="db-page-actions">
				<a href="' . $RootPath . '/SelectProduct.php?StockID=' . $StockID . '" class="db-btn db-btn-outline db-btn-small"><i class="fas fa-arrow-left"></i> ' . __('Back to Item') . '</a>
			</div>
		</div>';

echo '<div class="db-bottom-layout">';

// SIDEBAR
echo '<aside class="db-col-aside">';
echo '<div class="db-card">
		<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-pencil-alt"></i> ' . (isset($Id) ? __('Edit Note') : __('Create Note')) . '</h3></div>
		<div class="db-card-body">
			<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?StockID=' . $StockID . '">
				<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
				<input type="hidden" name="StockID" value="' . $StockID . '" />';
if (isset($Id)) {
	$SQL = "SELECT * FROM stockitemnotes WHERE noteid='".$Id."'";
	$nRow = DB_fetch_array(DB_query($SQL));
	$_POST['Note'] = $nRow['note'];
	$_POST['NoteDate'] = $nRow['date'];
	echo '<input type="hidden" name="Id" value="' . $Id . '" />';
}
echo '			<div class="db-form-group">
					<label class="db-label">' . __('Note Content') . '</label>
					<textarea name="Note" class="db-input" rows="5" required autofocus>' . ($_POST['Note'] ?? '') . '</textarea>
				</div>
				<div class="db-form-group">
					<label class="db-label">' . __('Reference Date') . '</label>
					<input type="date" name="NoteDate" class="db-input" value="' . ($_POST['NoteDate'] ?? date('Y-m-d')) . '" required />
				</div>
				<button type="submit" name="submit" class="db-btn db-btn-primary" style="width: 100%; margin-top: 20px;"><i class="fas fa-save"></i> ' . __('Save Note') . '</button>';
if (isset($Id)) {
	echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?StockID=' . $StockID . '" class="db-btn db-input-light" style="width: 100%; margin-top: 10px; text-align:center;"><i class="fas fa-times"></i> ' . __('Cancel') . '</a>';
}
echo '			</form>
		</div>
	  </div>';
echo '</aside>';

// MAIN
echo '<main class="db-col-main">';
$SQL = "SELECT noteid, stockid, note, date FROM stockitemnotes WHERE stockid='".$StockID."' ORDER BY date DESC";
$Result = DB_query($SQL);
$Row = DB_fetch_array(DB_query("SELECT description FROM stockmaster WHERE stockid='".$StockID."'"));

echo '<div class="db-card">
		<div class="db-card-header"><h3 class="db-card-title"><i class="fas fa-history"></i> ' . __('Note Timeline') . ': <span class="text-primary">' . ($Row['description'] ?? $StockID) . '</span></h3></div>
		<div class="db-card-body p-0">
			<div class="db-table-wrapper">
				<table class="db-table">
					<thead>
						<tr>
							<th style="width: 150px;">' . __('Date') . '</th>
							<th>' . __('Detailed Note') . '</th>
							<th class="text-right">' . __('Actions') . '</th>
						</tr>
					</thead>
					<tbody>';
if (DB_num_rows($Result) == 0) {
	echo '<tr><td colspan="3" class="text-center db-muted p-5">' . __('No notes found for this inventory item.') . '</td></tr>';
} else {
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<tr>
				<td><div class="db-badge db-badge-secondary">' . ConvertSQLDate($MyRow['date']) . '</div></td>
				<td style="white-space: pre-wrap;">' . $MyRow['note'] . '</td>
				<td class="text-right">
					<div style="display: flex; gap: 8px; justify-content: flex-end;">
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Id=' . $MyRow['noteid'] . '&StockID=' . $StockID . '" class="db-btn db-btn-sm db-input-light"><i class="fas fa-edit"></i></a>
						<a href="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '?Id=' . $MyRow['noteid'] . '&StockID=' . $StockID . '&delete=1" class="db-btn db-btn-sm db-btn-outline-danger" onclick="return confirm(\'Delete note?\');"><i class="fas fa-trash"></i></a>
					</div>
				</td>
			  </tr>';
	}
}
echo '					</tbody>
				</table>
			</div>
		</div>
	  </div>';
echo '</main></div></div>';

include(__DIR__ . '/includes/footer.php');
