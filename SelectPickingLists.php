<?php

/* Select a picking list */

require(__DIR__ . '/includes/session.php');

$Title = __('Search Pick Lists');
$ViewTopic = 'Sales';
$BookMark = 'SelectPickingLists';
include(__DIR__ . '/includes/header.php');

echo '<div class="dashboard-shell-container">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Manage and search picking lists across locations') . '</p>
			</div>
		</header>
		<div class="MainBody">';

if (isset($_GET['SelectedStockItem'])) {
	$SelectedStockItem = $_GET['SelectedStockItem'];
} elseif (isset($_POST['SelectedStockItem'])) {
	$SelectedStockItem = $_POST['SelectedStockItem'];
} else {
	$SelectedStockItem = '';
}

if (isset($_GET['OrderNumber'])) {
	$OrderNumber = $_GET['OrderNumber'];
} elseif (isset($_POST['OrderNumber'])) {
	$OrderNumber = $_POST['OrderNumber'];
} else {
	$OrderNumber = '';
}

if (isset($_GET['PickList'])) {
	$PickList = $_GET['PickList'];
} elseif (isset($_POST['PickList'])) {
	$PickList = $_POST['PickList'];
} else {
	$PickList = '';
}

if (!isset($_POST['Status'])) {
	$_POST['Status'] = 'New';
}

// Status Tabs
$statuses = [
    'New' => __('New'),
    'Picked' => __('Picked'),
    'Shipped' => __('Shipped'),
    'Invoiced' => __('Invoiced'),
    'Cancelled' => __('Cancelled')
];

echo '<div class="db-tabs-container" style="margin-bottom: var(--space-4);">
		<div class="db-tabs">';
foreach ($statuses as $statusValue => $statusLabel) {
    $activeClass = ($_POST['Status'] == $statusValue) ? 'active' : '';
    echo '<button type="button" class="db-tab ' . $activeClass . '" onclick="document.getElementById(\'StatusSelector\').value=\'' . $statusValue . '\'; this.form.submit();">' . $statusLabel . '</button>';
}
echo '  </div>
	  </div>';

echo '<form action="', htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'), '" method="post">
	<input type="hidden" name="FormID" value="', $_SESSION['FormID'], '" />
    <input type="hidden" name="Status" id="StatusSelector" value="' . $_POST['Status'] . '" />';

if (isset($_POST['ResetPart'])) {
	unset($SelectedStockItem);
}

if ($OrderNumber != '') {
	if (!is_numeric($OrderNumber)) {
		prnMsg(__('The Order Number entered') . ' <u>' . __('MUST') . '</u> ' . __('be numeric'), 'error');
		unset($OrderNumber);
	} else {
		echo __('Order Number') . ' - ' . $OrderNumber;
	}
}

if (isset($PickList) and $PickList != '') {
	if (!is_numeric($PickList)) {
		prnMsg(__('The Pick List entered') . ' <u>' . __('MUST') . '</u> ' . __('be numeric'), 'error');
		unset($PickList);
	} else {
		echo __('Pick List') . ' - ' . $PickList;
	}
}

if (isset($_POST['SearchParts'])) {
	if ($_POST['Keywords'] and $_POST['StockCode']) {
		prnMsg(__('Stock description keywords have been used in preference to the Stock code extract entered'), 'info');
	}
	if ($_POST['Keywords']) {
		//insert wildcard characters in spaces
		$SearchString = '%' . str_replace(' ', '%', $_POST['Keywords']) . '%';
		$SQL = "SELECT stockmaster.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				SUM(locstock.quantity) as qoh,
				stockmaster.units,
				(SELECT SUM(qtypicked)
					FROM pickreqdetails
					INNER JOIN pickreq ON pickreq.prid = pickreqdetails.prid
					INNER JOIN locationusers ON locationusers.loccode = pickreq.loccode
						AND locationusers.userid='" . $_SESSION['UserID'] . "'
						AND locationusers.canview =1
					WHERE pickreq.closed=0
						AND stockmaster.stockid = pickreqdetails.stockid) AS qpicked
			FROM stockmaster INNER JOIN locstock
				ON stockmaster.stockid = locstock.stockid
			INNER JOIN locationusers ON locationusers.loccode = locstock.loccode
				AND locationusers.userid='" . $_SESSION['UserID'] . "'
				AND locationusers.canview=1
			WHERE stockmaster.description " . LIKE . " '" . $SearchString . "'
				AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
			GROUP BY stockmaster.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				stockmaster.units
			ORDER BY stockmaster.stockid";
	} elseif ($_POST['StockCode']) {
		$SQL = "SELECT stockmaster.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				SUM(locstock.quantity) AS qoh,
				(SELECT SUM(qtypicked)
					FROM pickreqdetails
					INNER JOIN pickreq
						ON pickreq.prid = pickreqdetails.prid
					INNER JOIN locationusers
						ON locationusers.loccode = pickreq.loccode
						AND locationusers.userid='" . $_SESSION['UserID'] . "'
						AND locationusers.canview =1
					WHERE pickreq.closed=0
						AND stockmaster.stockid = pickreqdetails.stockid) AS qpicked,
				stockmaster.units
			FROM stockmaster
			INNER JOIN locstock
				ON stockmaster.stockid = locstock.stockid
			INNER JOIN locationusers
				ON locationusers.loccode = locstock.loccode
				AND locationusers.userid='" . $_SESSION['UserID'] . "'
				AND locationusers.canview=1
			WHERE stockmaster.stockid " . LIKE . " '%" . $_POST['StockCode'] . "%'
				AND stockmaster.categoryid='" . $_POST['StockCat'] . "'
			GROUP BY stockmaster.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				stockmaster.units
			ORDER BY stockmaster.stockid";
	} elseif (!$_POST['StockCode'] and !$_POST['Keywords']) {
		$SQL = "SELECT stockmaster.stockid,
				stockmaster.description,
				stockmaster.decimalplaces,
				SUM(locstock.quantity) AS qoh,
				stockmaster.units,
				(SELECT SUM(qtypicked)
					FROM pickreqdetails
					INNER JOIN pickreq
						ON pickreq.prid = pickreqdetails.prid
					INNER JOIN locationusers
						ON locationusers.loccode = pickreq.loccode
						AND locationusers.userid='" . $_SESSION['UserID'] . "'
						AND locationusers.canview =1
					WHERE pickreq.closed=0
						AND stockmaster.stockid = pickreqdetails.stockid) AS qpicked
				FROM stockmaster
				INNER JOIN locstock
					ON stockmaster.stockid = locstock.stockid
				INNER JOIN locationusers
					ON locationusers.loccode = locstock.loccode
					AND locationusers.userid='" . $_SESSION['UserID'] . "'
					AND locationusers.canview =1
				WHERE stockmaster.categoryid='" . $_POST['StockCat'] . "'
				GROUP BY stockmaster.stockid,
					stockmaster.description,
					stockmaster.decimalplaces,
					stockmaster.units
				ORDER BY stockmaster.stockid";
	}

	$ErrMsg = __('No stock items were returned by the SQL because');
	$StockItemsResult = DB_query($SQL, $ErrMsg);
}

if (true or !isset($OrderNumber) or $OrderNumber == "") { //revisit later, right now always show all inputs
	echo '<div class="card-v2">
            <div class="card-header-v2">
                <h3>' . __('Search Filters') . '</h3>
            </div>
            <div class="card-body-v2">
                <div class="db-field-group">';
	if (isset($SelectedStockItem) and $SelectedStockItem != '') {
		echo '<div class="db-field" style="grid-column: span 12;">
                <div class="alert-v2 alert-info">' . __('For the part') . ': <b>' . $SelectedStockItem . '</b> <input type="hidden" name="SelectedStockItem" value="' . $SelectedStockItem . '" /></div>
              </div>';
	}

	echo '<div class="db-field">
            <label>' . __('Sales Order') . '</label>
            <input name="OrderNumber" autofocus="autofocus" maxlength="8" value="' . $OrderNumber . '" placeholder="' . __('Enter Order #') . '"/>
          </div>';
	echo '<div class="db-field">
            <label>' . __('Pick List') . '</label>
            <input name="PickList" maxlength="10" value="' . $PickList . '" placeholder="' . __('Enter Pick List #') . '"/>
          </div>';

	$SQL = "SELECT locations.loccode,
					locationname
				FROM locations
				INNER JOIN locationusers
					ON locationusers.loccode=locations.loccode
					AND locationusers.userid='" . $_SESSION['UserID'] . "'
					AND locationusers.canview=1";
	$ResultStkLocs = DB_query($SQL);
	echo '<div class="db-field">
            <label>' . __('Stock Location') . '</label>
            <select name="StockLocation">';

	while ($MyRow = DB_fetch_array($ResultStkLocs)) {
		if (isset($_POST['StockLocation'])) {
			if ($MyRow['loccode'] == $_POST['StockLocation']) {
				echo '<option selected="selected" value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
			} else {
				echo '<option value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
			}
		} elseif ($MyRow['loccode'] == $_SESSION['UserStockLocation']) {
			echo '<option selected="selected" value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
		} else {
			echo '<option value="', $MyRow['loccode'], '">', $MyRow['locationname'], '</option>';
		}
	}
	echo '</select>
		</div>';

    echo '</div>'; // End db-field-group

    echo '<div class="form-actions" style="margin-top: var(--space-4);">
            <button type="submit" name="SearchPickLists" class="primary-btn-modern">
                <i class="fas fa-search"></i> ' . __('Search Pick Lists') . '
            </button>
          </div>';
    echo '</div></div><br />';
}
$SQL = "SELECT categoryid,
			categorydescription
		FROM stockcategory
		ORDER BY categorydescription";
$Result1 = DB_query($SQL);

echo '<div class="card-v2">
        <div class="card-header-v2">
            <h3>' . __('Search by Stock Item') . '</h3>
        </div>
        <div class="card-body-v2">
            <div class="db-field-group">
                <div class="db-field">
                    <label>' . __('Stock Category') . '</label>
                    <select name="StockCat">';

while ($MyRow1 = DB_fetch_array($Result1)) {
	if (isset($_POST['StockCat']) and $MyRow1['categoryid'] == $_POST['StockCat']) {
		echo '<option selected="selected" value="', $MyRow1['categoryid'], '">', $MyRow1['categorydescription'], '</option>';
	} else {
		echo '<option value="', $MyRow1['categoryid'], '">', $MyRow1['categorydescription'], '</option>';
	}
}

echo '              </select>
                </div>
                <div class="db-field">
                    <label>' . __('Keywords') . '</label>
                    <input type="text" name="Keywords" maxlength="25" placeholder="' . __('e.g. Spare Parts') . '" />
                </div>
                <div class="db-field">
                    <label>' . __('Stock Code') . '</label>
                    <input type="text" name="StockCode" maxlength="18" placeholder="' . __('e.g. COMP01') . '" />
                </div>
            </div>
            <div class="form-actions" style="margin-top: var(--space-4);">
                <button type="submit" name="SearchParts" class="primary-btn-modern">
                    <i class="fas fa-barcode"></i> ' . __('Search Parts Now') . '
                </button>
                <button type="submit" name="ResetPart" class="btn-secondary">
                    <i class="fas fa-undo"></i> ' . __('Show All') . '
                </button>
            </div>
        </div>
    </div><br />';

if (isset($StockItemsResult)) {
	echo '<div class="card-v2">
            <div class="card-header-v2">
                <h3>' . __('Stock Search Results') . '</h3>
            </div>
            <div class="card-body-v2">
                <div class="activity-table-wrapper">
                    <table class="activity-table">
                        <thead>
                            <tr>
                                <th>', __('Code'), '</th>
                                <th>', __('Description'), '</th>
                                <th>', __('On Hand'), '</th>
                                <th>', __('Picked'), '</th>
                                <th>', __('Units'), '</th>
                            </tr>
                        </thead>
                        <tbody>';

	while ($MyRow = DB_fetch_array($StockItemsResult)) {
		echo '<tr>
				<td><input type="submit" name="SelectedStockItem" class="primary-btn-modern" value="', $MyRow['stockid'], '"</td>
				<td>', $MyRow['description'], '</td>
				<td class="number">', locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']), '</td>
				<td class="number">', locale_number_format($MyRow['qpicked'], $MyRow['decimalplaces']), '</td>
				<td>', $MyRow['units'], '</td>
			</tr>';
	}//end of while loop

	echo '      </tbody>
                    </table>
                </div>
            </div>
        </div><br />';
}//end if stock search results to show
else {
	//figure out the SQL required from the inputs available

	if (!isset($_POST['Status']) or $_POST['Status'] == 'All') {
		$StatusCriteria = " AND (pickreq.status='New' OR pickreq.status='Picked' OR pickreq.status='Cancelled' OR pickreq.status='Shipped') ";
	} elseif ($_POST['Status'] == 'Picked') {
		$StatusCriteria = " AND (pickreq.status='Picked' OR pickreq.status='Printed')";
	} elseif ($_POST['Status'] == 'New') {
		$StatusCriteria = " AND pickreq.status='New' ";
	} elseif ($_POST['Status'] == 'Cancelled') {
		$StatusCriteria = " AND pickreq.status='Cancelled' ";
	} elseif ($_POST['Status'] == 'Shipped') {
		$StatusCriteria = " AND pickreq.status='Shipped' ";
	} elseif ($_POST['Status'] == 'Invoiced') {
		$StatusCriteria = " AND pickreq.status='Invoiced' ";
	}

	if (isset($OrderNumber) and $OrderNumber != '') {
		$SQL = "SELECT pickreq.orderno,
						pickreq.prid,
						pickreq.initdate,
						pickreq.requestdate,
						pickreq.initiator,
						pickreq.shipdate,
						pickreq.shippedby,
						pickreq.status,
						salesorders.printedpackingslip,
						debtorsmaster.name
					FROM pickreq
					INNER JOIN salesorders
						ON salesorders.orderno=pickreq.orderno
					INNER JOIN debtorsmaster
						ON salesorders.debtorno = debtorsmaster.debtorno
					WHERE pickreq.orderno='" . filter_number_format($OrderNumber) . "'
					GROUP BY pickreq.orderno
					ORDER BY pickreq.requestdate, pickreq.prid";
	} elseif (isset($PickList) and $PickList != '') {
		$SQL = "SELECT pickreq.orderno,
						pickreq.prid,
						pickreq.initdate,
						pickreq.requestdate,
						pickreq.initiator,
						pickreq.shipdate,
						pickreq.shippedby,
						pickreq.status,
						salesorders.printedpackingslip,
						debtorsmaster.name
					FROM pickreq
					INNER JOIN salesorders
						ON salesorders.orderno=pickreq.orderno
					INNER JOIN debtorsmaster
						ON salesorders.debtorno = debtorsmaster.debtorno
					WHERE pickreq.prid='" . filter_number_format($PickList) . "'
					GROUP BY pickreq.prid
					ORDER BY pickreq.requestdate, pickreq.prid";
	} else {
		if (empty($_POST['StockLocation'])) {
			$_POST['StockLocation'] = $_SESSION['UserStockLocation'];
		}
		if (isset($SelectedDebtor)) {
			//future functionality - search by customer
		} else { //no customer selected
			if (isset($SelectedStockItem)) {
				$SQL = "SELECT pickreq.orderno,
								pickreq.prid,
								pickreq.initdate,
								pickreq.requestdate,
								pickreq.initiator,
								pickreq.shipdate,
								pickreq.shippedby,
								pickreq.status,
								salesorders.printedpackingslip,
								debtorsmaster.name
							FROM pickreq
							INNER JOIN pickreqdetails
								ON pickreq.prid = pickreqdetails.prid
							INNER JOIN locationusers
								ON locationusers.loccode=pickreq.loccode
								AND locationusers.userid='" . $_SESSION['UserID'] . "'
								AND locationusers.canview=1
							INNER JOIN salesorders
								ON salesorders.orderno=pickreq.orderno
							INNER JOIN debtorsmaster
								ON salesorders.debtorno = debtorsmaster.debtorno
							WHERE pickreqdetails.stockid='" . $SelectedStockItem . "'
								AND pickreq.loccode = '" . $_POST['StockLocation'] . "'
								" . $StatusCriteria . "
							GROUP BY pickreq.prid
							ORDER BY pickreq.requestdate, pickreq.prid";
			} else {
				$SQL = "SELECT pickreq.orderno,
								pickreq.prid,
								pickreq.initdate,
								pickreq.requestdate,
								pickreq.initiator,
								pickreq.shipdate,
								pickreq.shippedby,
								pickreq.status,
								salesorders.printedpackingslip,
								debtorsmaster.name
							FROM pickreq
							INNER JOIN pickreqdetails
								ON pickreq.prid = pickreqdetails.prid
							INNER JOIN locationusers
								ON locationusers.loccode=pickreq.loccode
								AND locationusers.userid='" . $_SESSION['UserID'] . "'
								AND locationusers.canview=1
							INNER JOIN salesorders
								ON salesorders.orderno=pickreq.orderno
							INNER JOIN debtorsmaster
								ON salesorders.debtorno = debtorsmaster.debtorno
							WHERE pickreq.loccode = '" . $_POST['StockLocation'] . "'
								" . $StatusCriteria . "
							GROUP BY pickreq.prid
							ORDER BY pickreq.requestdate, pickreq.prid";
			} //no stock item selected
		} //no customer selected

	} //end not order number selected
	$ErrMsg = __('No pick lists were returned by the SQL because');
	$PickReqResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($PickReqResult) > 0) {
		echo '<div class="card-v2">
                <div class="card-header-v2">
                    <h3>' . __('Pick List Results') . '</h3>
                </div>
                <div class="card-body-v2">
                    <div class="activity-table-wrapper">
                        <table class="activity-table">
                            <thead>
                                <tr>
                                    <th>', __('View/Modify'), '</th>
                                    <th>', __('Picking List'), '</th>
                                    <th>', __('Packing List'), '</th>
                                    <th>', __('Labels'), '</th>
                                    <th>', __('Order'), '</th>
                                    <th>', __('Customer'), '</th>
                                    <th>', __('Request Date'), '</th>
                                    <th>', __('Ship Date'), '</th>
                                    <th>', __('Status'), '</th>
                                </tr>
                            </thead>
                            <tbody>';

		echo '<tbody>';

		while ($MyRow = DB_fetch_array($PickReqResult)) {

			$ModifyPickList = $RootPath . '/PickingLists.php?Prid=' . $MyRow['prid'];
			$PrintPickList = $RootPath . '/GeneratePickingList.php?TransNo=' . $MyRow['orderno'];

			if ($_SESSION['PackNoteFormat'] == 1) {
				/*Laser printed A4 default */
				$PrintDispatchNote = $RootPath . '/PrintCustOrder_generic.php?TransNo=' . $MyRow['orderno'];
			} else {
				/*pre-printed stationery default */
				$PrintDispatchNote = $RootPath . '/PrintCustOrder.php?TransNo=' . $MyRow['orderno'];
			}

			if ($MyRow['printedpackingslip'] == 0) {
				$PrintText = __('Print');
			} else {
				$PrintText = __('Reprint');
				$PrintDispatchNote .= '&Reprint=OK';
			}

			$PrintLabels = $RootPath . '/PDFShipLabel.php?Type=Sales&ORD=' . $MyRow['orderno'];
			$FormatedRequestDate = ConvertSQLDate($MyRow['requestdate']);
			$FormatedInitDate = ConvertSQLDate($MyRow['initdate']);
			$FormatedShipDate = ConvertSQLDate($MyRow['shipdate']);
			$Confirm_Invoice = '';

			if ($MyRow['status'] == "Shipped") {
				$Confirm_Invoice = '<td><a href="' . $RootPath . '/ConfirmDispatch_Invoice.php?OrderNumber=' . $MyRow['orderno'] . '">' . __('Invoice Order') . '</a></td>';
			}

			echo '<tr>
					<td><a href="', $ModifyPickList, '" class="primary-btn-modern" style="padding: 4px 12px; font-size: 0.8rem;">' . str_pad($MyRow['prid'], 10, '0', STR_PAD_LEFT) . '</a></td>
					<td><a href="', $PrintPickList, '" class="btn-secondary" style="padding: 4px 12px; font-size: 0.8rem;"><i class="fas fa-file-pdf"></i> ' . __('Print') . '</a></td>
					<td><a target="_blank" href="', $PrintDispatchNote, '" class="btn-secondary" style="padding: 4px 12px; font-size: 0.8rem;"><i class="fas fa-file-pdf"></i> ' . $PrintText . '</a></td>
					<td><a target="_blank" href="', $PrintLabels . '" class="btn-secondary" style="padding: 4px 12px; font-size: 0.8rem;"><i class="fas fa-tag"></i> ' . __('Labels') . '</a></td>
					<td><span class="ref-badge">#', $MyRow['orderno'], '</span></td>
					<td><span class="cust-name">', $MyRow['name'], '</span></td>
					<td><span class="date-stmp">', $FormatedRequestDate, '</span></td>
					<td><span class="date-stmp">', $FormatedShipDate, '</span></td>
					<td><span class="badge-v2 badge-info">', $MyRow['status'], '</span></td>
					', ($Confirm_Invoice != '' ? '<td><a href="' . $RootPath . '/ConfirmDispatch_Invoice.php?OrderNumber=' . $MyRow['orderno'] . '" class="primary-btn-modern" style="padding: 4px 12px; font-size: 0.8rem;">' . __('Invoice') . '</a></td>' : ''), '
				</tr>';
		} //end of while loop

		echo '</tbody>
                        </table>
                    </div>
                </div>
            </div>';
	} // end if Pick Lists to show
}
echo '</form>';
echo '</div></div><!-- .MainBody & .dashboard-shell-container -->';

if (isset($_POST['Status']) && $_POST['Status'] == 'New') {
	//office is generating picks. Warehouse needs to see latest "To Do" list so refresh every 5 minutes
	echo '<meta http-equiv="refresh" content="300" url="' . $RootPath . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" />';
}

include(__DIR__ . '/includes/footer.php');
