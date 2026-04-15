<?php

/* Select a picking list */

require(__DIR__ . '/includes/session.php');

$Title = __('Search Pick Lists');
$ViewTopic = 'Sales';
$BookMark = 'SelectPickingLists';
include(__DIR__ . '/includes/header.php');

echo '<div class="dashboard-shell-container" style="max-width: 1400px; margin: 0 auto;">
		<header class="db-page-header">
			<div>
				<h2 class="db-page-title">' . $Title . '</h2>
				<p class="db-page-subtitle">' . __('Manage and search picking lists across locations') . '</p>
			</div>
			<div class="db-page-actions">
				<a href="PickingLists.php?New=Yes" class="db-btn db-btn-primary"><i class="fas fa-plus"></i> ' . __('New Request') . '</a>
			</div>
		</header>';

        // Premium KPI Metrics Row
        $sqlPending = "SELECT COUNT(*) FROM pickreq WHERE status='New' AND closed=0";
        $resPending = DB_query($sqlPending);
        $rowPending = DB_fetch_row($resPending);
        $PendingCount = $rowPending[0];

        $sqlItems = "SELECT SUM(quantity) FROM pickreqdetails INNER JOIN pickreq ON pickreq.prid=pickreqdetails.prid WHERE pickreq.status='New' AND pickreq.closed=0";
        $resItems = DB_query($sqlItems);
        $rowItems = DB_fetch_row($resItems);
        $TotalItems = $rowItems[0] ?? 0;

        $sqlOldest = "SELECT MIN(initdate) FROM pickreq WHERE status='New' AND closed=0";
        $resOldest = DB_query($sqlOldest);
        $rowOldest = DB_fetch_row($resOldest);
        $OldestDate = $rowOldest[0];
        $DelayDays = $OldestDate ? floor((time() - strtotime($OldestDate)) / 86400) : 0;

        echo '<div class="kpi-grid" style="padding: 0 var(--space-6); margin-bottom: var(--space-6);">
            <div class="kpi-card-v2">
                <div class="kpi-icon" style="background: var(--warning-soft); color: var(--warning);">
                    <i class="fas fa-hourglass-start"></i>
                </div>
                <div class="kpi-data">
                    <span class="label">' . __('To Be Picked') . '</span>
                    <span class="value">' . $PendingCount . '</span>
                </div>
            </div>
            
            <div class="kpi-card-v2">
                <div class="kpi-icon" style="background: var(--info-soft); color: var(--info);">
                    <i class="fas fa-list-ol"></i>
                </div>
                <div class="kpi-data">
                    <span class="label">' . __('Scheduled Units') . '</span>
                    <span class="value">' . locale_number_format($TotalItems, 0) . '</span>
                </div>
            </div>

            <div class="kpi-card-v2">
                <div class="kpi-icon" style="background: ' . ($DelayDays > 2 ? 'var(--danger-soft)' : 'var(--primary-soft)') . '; color: ' . ($DelayDays > 2 ? 'var(--danger)' : 'var(--primary)') . ';">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="kpi-data">
                    <span class="label">' . __('Oldest Delay') . '</span>
                    <span class="value">' . $DelayDays . ' ' . __('Days') . '</span>
                </div>
            </div>
        </div>';

echo '<div class="MainBody" style="display: flex; flex-direction: column; gap: var(--space-6);">';


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

echo '<div class="db-card" style="margin: 0 var(--space-6);">
        <div class="db-card-header">
            <div class="db-card-title"><i class="fas fa-filter"></i> ' . __('Fulfillment Status') . '</div>
        </div>
        <div class="db-card-body">
            <div class="db-tabs" style="justify-content: flex-start; border-bottom: none;">';
            foreach ($statuses as $statusValue => $statusLabel) {
                $activeClass = (($_POST['Status'] ?? 'New') == $statusValue) ? 'active' : '';
                echo '<button type="submit" name="Status" value="' . $statusValue . '" class="db-tab ' . $activeClass . '">' . $statusLabel . '</button>';
            }
echo '      </div>
        </div>
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

echo '<div class="db-card" style="margin: 0 var(--space-6);">
        <div class="db-card-header">
            <div class="db-card-title"><i class="fas fa-search"></i> ' . __('Fulfillment Search') . '</div>
        </div>
        <div class="db-card-body">
            <div class="db-grid-3">
                <div class="db-field">
                    <label>' . __('Sales Order #') . '</label>
                    <input type="text" name="OrderNumber" value="' . ($OrderNumber ?? '') . '" placeholder="e.g. 10452" />
                </div>
                <div class="db-field">
                    <label>' . __('Pick List ID') . '</label>
                    <input type="text" name="PickList" value="' . ($PickList ?? '') . '" placeholder="e.g. 0000045" />
                </div>
                <div class="db-field">
                    <label>' . __('Warehouse') . '</label>
                    <select name="StockLocation" class="db-select">';
                    $SQL = "SELECT loccode, locationname FROM locations INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='" . $_SESSION['UserID'] . "' AND locationusers.canview=1";
                    $ResLoc = DB_query($SQL);
                    while ($LRow = DB_fetch_array($ResLoc)) {
                        $sel = ($LRow['loccode'] == ($_POST['StockLocation'] ?? $_SESSION['UserStockLocation'])) ? 'selected' : '';
                        echo '<option ' . $sel . ' value="' . $LRow['loccode'] . '">' . $LRow['locationname'] . '</option>';
                    }
echo '              </select>
                </div>
            </div>
            <div class="db-action-btn-row" style="margin-top: 15px; justify-content: flex-end;">
                <button type="submit" name="SearchPickLists" class="db-btn db-btn-primary">
                    <i class="fas fa-search"></i> ' . __('Search Registers') . '
                </button>
            </div>
        </div>
    </div>';
$SQL = "SELECT categoryid,
			categorydescription
		FROM stockcategory
		ORDER BY categorydescription";
$Result1 = DB_query($SQL);

echo '<div class="db-card" style="margin: 0 var(--space-6);">
        <div class="db-card-header" style="cursor: pointer;" onclick="document.getElementById(\'PartSearchBody\').style.display= (document.getElementById(\'PartSearchBody\').style.display==\'none\'?\'block\':\'none\')">
            <div class="db-card-title"><i class="fas fa-barcode"></i> ' . __('Search by Stock Item') . ' <span style="font-size: 0.8rem; font-weight: normal; color: var(--text-muted);">(' . __('Advanced') . ')</span></div>
        </div>
        <div id="PartSearchBody" class="db-card-body" style="display: ' . (isset($StockItemsResult)?'block':'none') . ';">
            <div class="db-grid-3">
                <div class="db-field">
                    <label>' . __('Keywords') . '</label>
                    <input type="text" name="Keywords" placeholder="e.g. Spare Parts" />
                </div>
                <div class="db-field">
                    <label>' . __('Category') . '</label>
                    <select name="StockCat" class="db-select">';
                    $SQL = "SELECT categoryid, categorydescription FROM stockcategory ORDER BY categorydescription";
                    $ResCat = DB_query($SQL);
                    while ($CRow = DB_fetch_array($ResCat)) {
                        echo '<option value="' . $CRow['categoryid'] . '">' . $CRow['categorydescription'] . '</option>';
                    }
echo '              </select>
                </div>
                <div class="db-field">
                    <label>' . __('Action') . '</label>
                    <button type="submit" name="SearchParts" class="db-btn db-btn-outline" style="width: 100%;">
                        <i class="fas fa-bolt"></i> ' . __('Search Parts') . '
                    </button>
                </div>
            </div>
        </div>
    </div>';

if (isset($StockItemsResult)) {
	echo '<div class="db-card" style="margin: 0 var(--space-6);">
            <div class="db-card-header">
                <div class="db-card-title"><i class="fas fa-barcode"></i> ' . __('Parts Found') . '</div>
            </div>
            <div class="db-card-body p-0">
                <div class="db-table-wrapper">
                    <table class="db-table">
                        <thead>
                            <tr>
                                <th>', __('Pick Part'), '</th>
                                <th>', __('Description'), '</th>
                                <th>', __('On Hand'), '</th>
                                <th>', __('Units'), '</th>
                            </tr>
                        </thead>
                        <tbody>';

	while ($MyRow = DB_fetch_array($StockItemsResult)) {
		echo '<tr>
				<td><button type="submit" name="SelectedStockItem" value="', $MyRow['stockid'], '" class="db-btn db-btn-outline" style="padding: 4px 12px; font-weight: 700;">', $MyRow['stockid'], '</button></td>
				<td><div class="db-font-bold">', $MyRow['description'], '</div></td>
				<td>', locale_number_format($MyRow['qoh'], $MyRow['decimalplaces']), '</td>
				<td>', $MyRow['units'], '</td>
			</tr>';
	}

	echo '      </tbody>
                    </table>
                </div>
            </div>
        </div>';
}
//end if stock search results to show
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
	if (DB_num_rows($PickReqResult) > 0) {
		echo '<div class="db-card" style="margin: 0 var(--space-6);">
                <div class="db-card-header">
                    <div class="db-card-title"><i class="fas fa-list-ul"></i> ' . __('Picking List Register') . '</div>
                </div>
                <div class="db-card-body p-0">
                    <div class="db-table-wrapper">
                        <table class="db-table">
                            <thead>
                                <tr>
                                    <th>', __('Pick ID'), '</th>
                                    <th>', __('Order'), '</th>
                                    <th>', __('Customer'), '</th>
                                    <th>', __('Request Date'), '</th>
                                    <th>', __('Status'), '</th>
                                    <th class="text-right">', __('Actions'), '</th>
                                </tr>
                            </thead>
                            <tbody>';

		while ($MyRow = DB_fetch_array($PickReqResult)) {
			$ModifyPickList = $RootPath . '/PickingLists.php?Prid=' . $MyRow['prid'];
			$PrintPickList = $RootPath . '/GeneratePickingList.php?TransNo=' . $MyRow['orderno'];
			$PrintDispatchNote = ($_SESSION['PackNoteFormat'] == 1 ? $RootPath . '/PrintCustOrder_generic.php?TransNo=' : $RootPath . '/PrintCustOrder.php?TransNo=') . $MyRow['orderno'] . ($MyRow['printedpackingslip'] == 0 ? '' : '&Reprint=OK');
			$PrintLabels = $RootPath . '/PDFShipLabel.php?Type=Sales&ORD=' . $MyRow['orderno'];
			
            $stCfg = [
                'New' => 'warning',
                'Picked' => 'info',
                'Shipped' => 'success',
                'Cancelled' => 'danger',
                'Invoiced' => 'primary'
            ];
            $color = $stCfg[$MyRow['status']] ?? 'secondary';

			echo '<tr>
					<td>
                        <a href="', $ModifyPickList, '" class="db-btn db-btn-outline" style="padding: 4px 12px; font-weight: 700;">
                            ' . str_pad($MyRow['prid'], 8, '0', STR_PAD_LEFT) . '
                        </a>
                    </td>
					<td><span class="db-badge db-badge-secondary">#', $MyRow['orderno'], '</span></td>
					<td><div class="db-font-bold">', $MyRow['name'], '</div></td>
					<td>', ConvertSQLDate($MyRow['requestdate']), '</td>
					<td><span class="db-badge db-badge-' . $color . '">', $MyRow['status'], '</span></td>
					<td class="text-right db-action-btn-row">
						<a href="', $PrintPickList, '" class="db-btn db-btn-outline-primary" title="' . __('Pick List') . '"><i class="fas fa-file-pdf"></i> ' . __('Pick') . '</a>
						<a target="_blank" href="', $PrintDispatchNote, '" class="db-btn db-btn-outline-primary" title="' . __('Packing Slip') . '"><i class="fas fa-box-open"></i></a>
						<a target="_blank" href="', $PrintLabels . '" class="db-btn db-btn-outline-primary" title="' . __('Labels') . '"><i class="fas fa-tag"></i></a>
					    ' . ($MyRow['status'] == "Shipped" ? '<a href="' . $RootPath . '/ConfirmDispatch_Invoice.php?OrderNumber=' . $MyRow['orderno'] . '" class="db-btn db-btn-outline-success"><i class="fas fa-file-invoice"></i></a>' : '') . '
                    </td>
				</tr>';
		}
		echo '</tbody></table></div></div></div>';
	} else {
        echo '<div class="db-card p-10 text-center" style="margin: 0 var(--space-6);">
                <i class="fas fa-search fa-3x mb-4" style="color: var(--text-muted); opacity: 0.3;"></i>
                <h3>' . __('No Pick Lists Found') . '</h3>
                <p>' . __('There are no picking lists matching the selected criteria.') . '</p>
              </div>';
    }
}
}
echo '</div> <!-- End MainBody vertical stack -->
      </form>';

if (isset($_POST['Status']) && $_POST['Status'] == 'New') {
	//office is generating picks. Warehouse needs to see latest "To Do" list so refresh every 5 minutes
	echo '<meta http-equiv="refresh" content="300" url="' . $RootPath . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '" />';
}

include(__DIR__ . '/includes/footer.php');
