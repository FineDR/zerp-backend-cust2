<?php

require(__DIR__ . '/includes/session.php');

ob_start();

$Title = __('Stock Check Sheets Entry');
$ViewTopic = 'Inventory';
$BookMark = '';
include(__DIR__ . '/includes/header.php');

echo '<style>
	.db-side-btn {
		transition: all 0.2s ease-in-out !important;
	}
	.db-side-btn:hover {
		background-color: var(--primary-soft) !important;
		color: var(--primary) !important;
		transform: translateX(4px);
		box-shadow: var(--shadow-sm);
	}
	.db-side-btn-active:hover {
		transform: none !important;
		background-color: var(--primary) !important;
		color: white !important;
	}
</style>';

if (!isset($_POST['Action']) AND !isset($_GET['Action'])) {

	$_GET['Action'] = 'Enter';
}
if (isset($_POST['Action'])) {
	$_GET['Action'] = $_POST['Action'];
}
if ($_GET['Action']!='View' AND $_GET['Action']!='Enter'){
	$_GET['Action'] = 'Enter';
}

echo '<form name="EnterCountsForm" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" method="post" enctype="multipart/form-data">
		<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
		<input type="hidden" name="Action" value="' . $_GET['Action'] . '" />
		<div class="db-bottom-layout">';


// SIDEBAR START
echo '<aside class="db-col-aside">';

// CARD 1: WORKFLOW MODE
echo '<div class="db-card" style="margin-bottom: 20px;">
		<div class="db-card-header">
			<div class="db-card-title"><i class="fas fa-tasks"></i> ' . __('Workflow') . '</div>
		</div>
		<div class="db-card-body" style="padding: 15px; display: flex; flex-direction: column; gap: 10px;">';
if ($_GET['Action']=='View'){
	echo '		<a href="' . $RootPath . '/StockCounts.php?Action=Enter" class="db-btn db-btn-outline-primary db-side-btn" style="text-align: left; padding: 12px 15px; width: 100%;">
					<i class="fas fa-plus-circle" style="margin-right: 10px;"></i> ' . __('Enter New Counts') . '
				</a>
				<div class="db-btn db-btn-primary db-side-btn-active" style="text-align: left; padding: 12px 15px; width: 100%; cursor: default; box-shadow: var(--shadow-sm);">
					<i class="fas fa-list-ul" style="margin-right: 10px;"></i> ' . __('Viewing Entered Counts') . '
				</div>';
} else {
	echo '		<div class="db-btn db-btn-primary db-side-btn-active" style="text-align: left; padding: 12px 15px; width: 100%; cursor: default; box-shadow: var(--shadow-sm);">
					<i class="fas fa-plus-circle" style="margin-right: 10px;"></i> ' . __('Entering Counts Now') . '
				</div>
				<a href="' . $RootPath . '/StockCounts.php?Action=View" class="db-btn db-btn-outline-primary db-side-btn" style="text-align: left; padding: 12px 15px; width: 100%;">
					<i class="fas fa-list-ul" style="margin-right: 10px;"></i> ' . __('View Entered Counts') . '
				</a>';
}

echo '		</div>
	  </div>';


// CARD 2: LOCATION SELECTION (If in Enter mode)
if ($_GET['Action'] == 'Enter') {
	echo '<div class="db-card" style="margin-bottom: 20px;">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-warehouse"></i> ' . __('Inventory Site') . '</div>
			</div>
			<div class="db-card-body">
				<div class="db-form-group">
					<label class="db-label">' . __('Counting Location') . '</label>
					<select name="Location" class="db-select db-input-light" onchange="this.form.submit();">';
	
	$SQL = "SELECT locations.loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canupd=1";
	$LocRes = DB_query($SQL);
	while ($MyRow = DB_fetch_array($LocRes)) {
		$selected = (isset($_POST['Location']) AND $MyRow['loccode']==$_POST['Location']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['loccode'] . '">' . $MyRow['locationname'] . '</option>';
	}
	echo '			</select>
				</div>
			</div>
		  </div>';

	// CARD 3: CSV IMPORT
	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-file-import"></i> ' . __('Bulk Import') . '</div>
			</div>
			<div class="db-card-body">
				<div class="db-form-group">
					<label class="db-label">' . __('CSV Count Sheet') . '</label>
					<input name="userfile" type="file" class="db-input" style="font-size: 0.8rem; padding: 6px;" />
				</div>
				<button type="submit" class="db-btn db-btn-secondary" style="width: 100%; margin-bottom: 15px;">
					<i class="fas fa-upload"></i> ' . __('Upload & Process') . '
				</button>
				<div class="text-center">
					<a href="' . $RootPath . '/StockCounts.php?gettemplate=1" class="db-link" style="font-size: 0.85rem;"><i class="fas fa-download"></i> ' . __('Get Template') . '</a>
				</div>
			</div>
		  </div>';
}

echo '</aside>';
// SIDEBAR END

echo '<main class="db-col-main">';


$FieldHeadings = array(
	'StockCode',       	//  0 'STOCKCODE',
	'QtyCounted',	 	//  1 'QTYCOUNTED',
	'Reference'      	//  2 'REFERENCE'
);

if (isset($_GET['gettemplate'])) //download an import template
{

	// clean up any previous outputs
	ob_clean();

	header("Content-Type: application/force-download");
	header("Content-Type: application/octet-stream");
	header("Content-Type: application/download");

	// disposition / encoding on response body
	header("Content-Disposition: attachment; filename=ImportTemplate.csv");
	header("Content-Transfer-Encoding: binary");

	echo '"' . implode('","',$FieldHeadings) . '"';

	// exit cleanly to prevent any unwanted outputs
	include(__DIR__ . '/includes/footer.php');
	exit();
} else {
	ob_end_flush();
}

if ($_GET['Action'] == 'Enter') {

	if (isset($_POST['EnterCounts'])){

		$Added=0;
		$Counter = $_POST['RowCount'] ?? 10; // Arbitrary number of 10 hard coded as default as originally used - should there be a setting?
			for ($i=1;$i<=$Counter;$i++){
			$InputError =false; //always assume the best to start with

			$Quantity = 'Qty_' . $i;
			$BarCode = 'BarCode_' . $i;
			$StockID = 'StockID_' . $i;
			$Reference = 'Ref_' . $i;

			if (strlen($_POST[$BarCode])>0){
				$SQL = "SELECT stockmaster.stockid
								FROM stockmaster
								WHERE stockmaster.barcode='". $_POST[$BarCode] ."'";

				$ErrMsg = __('Could not determine if the part being ordered was a kitset or not because');
				$KitResult = DB_query($SQL, $ErrMsg);
				$MyRow=DB_fetch_array($KitResult);

				$_POST[$StockID] = strtoupper($MyRow['stockid']);
			}

			if (mb_strlen($_POST[$StockID])>0){
				if (!is_numeric($_POST[$Quantity])){
					$InputError=true;
				}
			$SQL = "SELECT stockid FROM stockcheckfreeze WHERE stockid='" . $_POST[$StockID] . "'";
				$Result = DB_query($SQL);
				if (DB_num_rows($Result)==0){
					prnMsg( __('The stock code entered on line') . ' ' . $i . ' ' . __('is not a part code that has been added to the stock check file') . ' - ' . __('the code entered was') . ' ' . $_POST[$StockID] . '. ' . __('This line will have to be re-entered'),'warn');
					$InputError = true;
				}

				if ($InputError==false){
					$Added++;
					$SQL = "INSERT INTO stockcounts (stockid,
									loccode,
									qtycounted,
									reference)
								VALUES ('" . $_POST[$StockID] . "',
									'" . $_POST['Location'] . "',
									'" . $_POST[$Quantity] . "',
									'" . $_POST[$Reference] . "')";

					$ErrMsg = __('The stock count line number') . ' ' . $i . ' ' . __('could not be entered because');
					$EnterResult = DB_query($SQL, $ErrMsg);
				}
			}
		} // end of loop
		prnMsg($Added . __(' Stock Counts Entered'), 'success' );
		unset($_POST['EnterCounts']);
	} // end of if enter counts button hit
	elseif (isset($_FILES['userfile']) and $_FILES['userfile']['name'])
	{
		//initialize
		$FieldTarget = count($FieldHeadings);
		$InputError = 0;

		//check file info
		$FileName = $_FILES['userfile']['name'];
		$TempName  = $_FILES['userfile']['tmp_name'];
		$FileSize = $_FILES['userfile']['size'];

		//get file handle
		$FileHandle = fopen($TempName, 'r');

		//get the header row
		$HeadRow = fgetcsv($FileHandle, 10000, ",",'"');  // Modified to handle " "" " enclosed csv - useful if you need to include commas in your text descriptions

		//check for correct number of fields
		if ( count($HeadRow) != count($FieldHeadings) ) {
			prnMsg(__('File contains '. count($HeadRow). ' columns, expected '. count($FieldHeadings). '. Try downloading a new template.'),'error');
			fclose($FileHandle);
			include(__DIR__ . '/includes/footer.php');
			exit();
		}

		//test header row field name and sequence
		$Head = 0;
		foreach ($HeadRow as $HeadField) {
			if ( mb_strtoupper($HeadField) != mb_strtoupper($FieldHeadings[$Head]) ) {
				prnMsg(__('File contains incorrect headers '. mb_strtoupper($HeadField). ' != '. mb_strtoupper($FieldHeadings[$Head]). '. Try downloading a new template.'),'error');  //Fixed $FieldHeadings from $Headings
				fclose($FileHandle);
				include(__DIR__ . '/includes/footer.php');
				exit();
			}
			$Head++;
		}

		//start database transaction
		DB_Txn_Begin();

		//loop through file rows
		$Row = 1;
		while ( ($MyRow = fgetcsv($FileHandle, 10000, ",")) !== false ) {

			//check for correct number of fields
			$FieldCount = count($MyRow);
			if ($FieldCount != $FieldTarget){
				prnMsg(__($FieldTarget. ' fields required, '. $FieldCount. ' fields received'),'error');
				fclose($FileHandle);
				include(__DIR__ . '/includes/footer.php');
				exit();
			}

			// cleanup the data (csv files often import with empty strings and such)
			$StockID = mb_strtoupper($MyRow[0]);
			foreach ($MyRow as &$Value) {
				$Value = trim($Value);
			}

			//first off check if the item is in freeze
			$SQL = "SELECT stockid FROM stockcheckfreeze WHERE stockid='" . $StockID . "'";
			$Result = DB_query($SQL);
			if (DB_num_rows($Result)==0){
				$InputError = 1;
				prnMsg( __('Stock item '. $StockID. ' is not a part code that has been added to the stock check file'),'warn');
			}

			//next validate inputs are sensible
			if (mb_strlen($MyRow[2]) >20) {
				$InputError = 1;
				prnMsg(__('The reference field must be 20 characters or less long'),'error');
			}
			elseif (!is_numeric($MyRow[1])) {
				$InputError = 1;
				prnMsg(__('The quantity counted must be numeric') ,'error');
			}
			elseif ($MyRow[1] < 0) {
				$InputError = 1;
				prnMsg(__('The quantity counted must be zero or a positive number'),'error');
			}

			if ($InputError !=1){

				//attempt to insert the stock item
				$SQL = "INSERT INTO stockcounts (stockid,
									loccode,
									qtycounted,
									reference)
								VALUES ('" . $MyRow[0] . "',
									'" . $_POST['Location'] . "',
									'" . $MyRow[1] . "',
									'" . $MyRow[2] . "')";

				$ErrMsg = __('The stock count line number') . ' ' . $Row . ' ' . __('could not be entered because');
				$EnterResult = DB_query($SQL, $ErrMsg, '', true);

				if (DB_error_no() != 0) {
					$InputError = 1;
					prnMsg(__($EnterResult),'error');
				}
			}

			if ($InputError == 1) { //this row failed so exit loop
				break;
			}
			$Row++;
		}

		if ($InputError == 1) { //exited loop with errors so rollback
			prnMsg(__('Failed on row '. $Row. '. Batch import has been rolled back.'),'error');
			DB_Txn_Rollback();
		} else { //all good so commit data transaction
			DB_Txn_Commit();
			prnMsg( __('Batch Import of') .' ' . $FileName  . ' '. __('has been completed. All transactions committed to the database.'),'success');
		}

		fclose($FileHandle);
	} // end of if import file button hit

	if (DB_num_rows($CatsResult) ==0) {
		echo '<div class="db-card" style="height: 100%; min-height: 400px; display: flex; align-items: center; justify-content: center; text-align: center;">
				<div class="db-card-body">
					<div style="width: 80px; height: 80px; background: var(--db-bg-alt); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: var(--db-text-muted);">
						<i class="fas fa-exclamation-triangle" style="font-size: 2.5rem; opacity: 0.3;"></i>
					</div>
					<h3 class="db-font-bold" style="color: var(--text-main); margin-bottom: 8px;">' . __('No Active Stock Check') . '</h3>
					<p style="max-width: 400px; margin: 0 auto 20px; color: var(--text-muted);">' . __('A stock check must be initialized before counts can be entered. There are currently no items frozen for counting.') . '</p>
					<a href="' . $RootPath . '/StockCheck.php" class="db-btn db-btn-primary">' . __('Initialize New Stock Check') . '</a>
				</div>
			</div>';
	} else {
		echo '<div class="db-card">
				<div class="db-card-header">
					<div class="db-card-title"><i class="fas fa-clipboard-list"></i> ' . __('Entering Stock Counts') . '</div>
				</div>
				<div class="db-card-body">';
		
		echo '<div style="display: flex; gap: 20px; align-items: flex-end; margin-bottom: 25px; flex-wrap: wrap;">
				<div class="db-form-group" style="flex: 1; min-width: 250px;">
					<label class="db-label">' . __('Select Product Category') . '</label>
					<select name="StkCat" class="db-select db-input-light" onChange="ReloadForm(EnterCountsForm.EnterByCat)">
						<option value="">' . __('All Categories / Manual Entry') . '</option>';
		while ($MyRow=DB_fetch_array($CatsResult)){
			$selected = ($_POST['StkCat']==$MyRow['categoryid']) ? 'selected="selected"' : '';
			echo '<option ' . $selected . ' value="' . $MyRow['categoryid'] . '">' . $MyRow['categorydescription'] . '</option>';
		}
		echo '		</select>
				</div>
				<button type="submit" name="EnterByCat" class="db-btn db-btn-secondary" style="height: 44px; padding: 0 25px;">
					<i class="fas fa-filter"></i> ' . __('Update List') . '
				</button>
			</div>';

		if (isset($_POST['EnterByCat'])){
			$StkCatResult = DB_query("SELECT categorydescription FROM stockcategory WHERE categoryid='" . $_POST['StkCat'] . "'");
			$StkCatRow = DB_fetch_row($StkCatResult);

			echo '	<div class="db-status-bar db-status-info" style="margin-bottom: 20px;">

						<div class="db-status-icon"><i class="fas fa-info-circle"></i></div>
						<div class="db-status-text">' . __('Entering counts for stock category') . ': <strong>' . $StkCatRow[0] . '</strong></div>
					</div>
					<div class="db-table-wrapper">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Stock Code') . '</th>
									<th>' . __('Description') . '</th>
									<th>' . __('Quantity Counted') . '</th>
									<th>' . __('Reference / Note') . '</th>
								</tr>
							</thead>
							<tbody>';
			$StkItemsResult = DB_query("SELECT stockcheckfreeze.stockid,
												description
										FROM stockcheckfreeze INNER JOIN stockmaster
										ON stockcheckfreeze.stockid=stockmaster.stockid
										WHERE categoryid='" . $_POST['StkCat'] . "' AND loccode = '" . $_POST['Location'] . "'
										ORDER BY stockcheckfreeze.stockid");

			$RowCount=1;
			while ($StkRow = DB_fetch_array($StkItemsResult)) {
				echo '<tr>
						<td><input type="hidden" name="StockID_' . $RowCount . '" value="' . $StkRow['stockid'] . '" /><span class="db-font-bold text-primary">' . $StkRow['stockid'] . '</span></td>
						<td style="font-size: 0.85rem; color: var(--text-main);">' . $StkRow['description'] . '</td>
						<td><input type="text" name="Qty_' . $RowCount . '" class="db-input number" maxlength="10" placeholder="0.00" /></td>
						<td><input type="text" name="Ref_' . $RowCount . '" class="db-input" maxlength="20" placeholder="' . __('Optional ref...') . '" /></td>
					</tr>';
				$RowCount++;
			}
			echo '			</tbody>
						</table>
					</div>';

		} else {
			echo '	<div class="db-status-bar db-status-active" style="margin-bottom: 20px;">
						<div class="db-status-icon"><i class="fas fa-keyboard"></i></div>
						<div class="db-status-text">' . __('Manual Entry: Use barcodes or stock codes to enter counts quickly.') . '</div>
					</div>
					<div class="db-table-wrapper">
						<table class="db-table">
							<thead>
								<tr>
									<th>' . __('Bar Code') . '</th>
									<th>' . __('Stock Code') . '</th>
									<th>' . __('Quantity') . '</th>
									<th>' . __('Reference') . '</th>
								</tr>
							</thead>
							<tbody>';

			for ($RowCount=1;$RowCount<=10;$RowCount++){
				echo '<tr>
						<td><input type="text" name="BarCode_' . $RowCount . '" class="db-input" maxlength="20" placeholder="' . __('Scan barcode...') . '" /></td>
						<td><input type="text" name="StockID_' . $RowCount . '" class="db-input" maxlength="20" placeholder="' . __('Stock code...') . '" /></td>
						<td><input type="text" name="Qty_' . $RowCount . '" class="db-input number" maxlength="10" placeholder="0.00" /></td>
						<td><input type="text" name="Ref_' . $RowCount . '" class="db-input" maxlength="20" placeholder="' . __('Ref...') . '" /></td>
					</tr>';
			}
			echo '			</tbody>
						</table>
					</div>';
		}

		echo '			</div> <!-- end card body -->
						<div class="db-card-footer" style="padding: 20px; text-align: right; background: var(--db-bg-alt);">';
		echo '				<input type="hidden" name="RowCount" value="' .$RowCount . '" />
							<button type="submit" name="EnterCounts" class="db-btn db-btn-primary db-btn-lg" style="padding-left: 40px; padding-right: 40px;">
								<i class="fas fa-check-double"></i> ' . __('Submit Counts') . '
							</button>
						</div>
					</div>';
	} // there is a stock check to enter counts for

//END OF action=ENTER
} elseif ($_GET['Action']=='View'){

	if (isset($_POST['DEL']) AND is_array($_POST['DEL']) ){
		foreach ($_POST['DEL'] as $id=>$val){
			if ($val == 'on'){
				$id = (int)$id;
				$SQL = "DELETE FROM stockcounts WHERE id='".$id."'";
				$ErrMsg = __('Failed to delete StockCount ID #').' '.$i;
				$EnterResult = DB_query($SQL, $ErrMsg);
				prnMsg( __('Deleted Id #') . ' ' . $id, 'success');
			}
		}
	}

	$SQL = "select stockcounts.*,
					canupd from stockcounts
					INNER JOIN locationusers ON locationusers.loccode=stockcounts.loccode AND locationusers.userid='" .  $_SESSION['UserID'] . "' AND locationusers.canview=1";
	$Result = DB_query($SQL);
	
	echo '<div class="db-card">
			<div class="db-card-header">
				<div class="db-card-title"><i class="fas fa-list-alt"></i> ' . __('Review Entered Counts') . '</div>
			</div>
			<div class="db-card-body" style="padding: 0;">
				<div class="db-table-wrapper">
					<table class="db-table">
						<thead>
							<tr>
								<th>' . __('Stock Code') . '</th>
								<th>' . __('Location') . '</th>
								<th>' . __('Qty Counted') . '</th>
								<th>' . __('Reference') . '</th>
								<th class="text-center">' . __('Remove?') . '</th>
							</tr>
						</thead>
						<tbody>';

	if (DB_num_rows($Result) == 0) {
		echo '<tr><td colspan="5" class="text-center" style="padding: 40px; color: var(--text-muted);">' . __('No counts have been entered yet.') . '</td></tr>';
	}

	while ($MyRow=DB_fetch_array($Result)){
		echo '<tr>
			<td><span class="db-font-bold text-primary">'.$MyRow['stockid'].'</span></td>
			<td>'.$MyRow['loccode'].'</td>
			<td>'.locale_number_format($MyRow['qtycounted'], 2).'</td>
			<td style="font-size: 0.85rem; color: var(--text-muted);">'.$MyRow['reference'].'</td>
			<td class="text-center">';
		if ($MyRow['canupd']==1) {
			echo '<label class="db-checkbox">
					<input type="checkbox" name="DEL[' . $MyRow['id'] . ']" />
					<span class="db-checkbox-mark"></span>
				  </label>';
		}
		echo '</td></tr>';

	}
	echo '				</tbody>
					</table>
				</div>
			</div>
			<div class="db-card-footer" style="padding: 20px; text-align: right; background: var(--db-bg-alt);">
				<button type="submit" name="SubmitChanges" class="db-btn db-btn-danger">
					<i class="fas fa-trash-alt"></i> ' . __('Delete Selected Counts') . '
				</button>
			</div>
		</div>';


//END OF action=VIEW
}

	echo '	</main>
	</div> <!-- end db-bottom-layout -->
</form>';
include(__DIR__ . '/includes/footer.php');

