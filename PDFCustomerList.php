<?php

/* Creates a report of the customer and branch information held. This report has options to print only customer branches in a specified sales area and sales person. Additional option allows to list only those customers with activity either under or over a specified amount, since a specified date. */

require(__DIR__ . '/includes/session.php');

use Dompdf\Dompdf;

include(__DIR__ . '/includes/SetDomPDFOptions.php');

$ViewTopic = 'ARReports';
$BookMark = 'CustomerListing';

if (isset($_POST['ActivitySince'])){$_POST['ActivitySince'] = ConvertSQLDate($_POST['ActivitySince']);}

if (isset($_POST['PrintPDF']) or isset($_POST['View'])) {

	if ($_POST['Activity']!='All') {
		if (!is_numeric($_POST['ActivityAmount'])) {
			$Title = __('Customer List') . ' - ' . __('Problem Report') . '....';
			include(__DIR__ . '/includes/header.php');
			echo '<p />';
			prnMsg( __('The activity amount is not numeric and you elected to print customer relative to a certain amount of activity') . ' - ' . __('this level of activity must be specified in the local currency') .'.', 'error');
			include(__DIR__ . '/includes/footer.php');
			exit();
		}
	}

	/* Now figure out the customer data to report for the selections made */

	if (in_array('All', $_POST['Areas'])) {
		if (in_array('All', $_POST['SalesPeople'])) {
			$SQL = "SELECT debtorsmaster.debtorno,
						debtorsmaster.name,
						debtorsmaster.address1,
						debtorsmaster.address2,
						debtorsmaster.address3,
						debtorsmaster.address4,
						debtorsmaster.address5,
						debtorsmaster.address6,
						debtorsmaster.salestype,
						custbranch.branchcode,
						custbranch.brname,
						custbranch.braddress1,
						custbranch.braddress2,
						custbranch.braddress3,
						custbranch.braddress4,
						custbranch.braddress5,
						custbranch.braddress6,
						custbranch.contactname,
						custbranch.phoneno,
						custbranch.faxno,
						custbranch.email,
						custbranch.area,
						custbranch.salesman,
						areas.areadescription,
						salesman.salesmanname
					FROM debtorsmaster INNER JOIN custbranch
					ON debtorsmaster.debtorno=custbranch.debtorno
					INNER JOIN areas
					ON custbranch.area = areas.areacode
					INNER JOIN salesman
					ON custbranch.salesman=salesman.salesmancode
					ORDER BY area,
						salesman,
						debtorsmaster.debtorno,
						custbranch.branchcode";
		} else {
		/* there are a range of salesfolk selected need to build the where clause */
			$SQL = "SELECT debtorsmaster.debtorno,
						debtorsmaster.name,
						debtorsmaster.address1,
						debtorsmaster.address2,
						debtorsmaster.address3,
						debtorsmaster.address4,
						debtorsmaster.address5,
						debtorsmaster.address6,
						debtorsmaster.salestype,
						custbranch.branchcode,
						custbranch.brname,
						custbranch.braddress1,
						custbranch.braddress2,
						custbranch.braddress3,
						custbranch.braddress4,
						custbranch.braddress5,
						custbranch.braddress6,
						custbranch.contactname,
						custbranch.phoneno,
						custbranch.faxno,
						custbranch.email,
						custbranch.area,
						custbranch.salesman,
						areas.areadescription,
						salesman.salesmanname
					FROM debtorsmaster INNER JOIN custbranch
					ON debtorsmaster.debtorno=custbranch.debtorno
					INNER JOIN areas
					ON custbranch.area = areas.areacode
					INNER JOIN salesman
					ON custbranch.salesman=salesman.salesmancode
					WHERE (";

				$i=0;
				foreach ($_POST['SalesPeople'] as $Salesperson) {
					if ($i>0) {
						$SQL .= " OR ";
					}
					$i++;
					$SQL .= "custbranch.salesman='" . $Salesperson ."'";
				}

				$SQL .=") ORDER BY area,
						salesman,
						debtorsmaster.debtorno,
						custbranch.branchcode";
		} /*end if SalesPeople =='All' */
	} else { /* not all sales areas has been selected so need to build the where clause */
		if (in_array('All', $_POST['SalesPeople'])) {
			$SQL = "SELECT debtorsmaster.debtorno,
						debtorsmaster.name,
						debtorsmaster.address1,
						debtorsmaster.address2,
						debtorsmaster.address3,
						debtorsmaster.address4,
						debtorsmaster.address5,
						debtorsmaster.address6,
						debtorsmaster.salestype,
						custbranch.branchcode,
						custbranch.brname,
						custbranch.braddress1,
						custbranch.braddress2,
						custbranch.braddress3,
						custbranch.braddress4,
						custbranch.braddress5,
						custbranch.braddress6,
						custbranch.contactname,
						custbranch.phoneno,
						custbranch.faxno,
						custbranch.email,
						custbranch.area,
						custbranch.salesman,
						areas.areadescription,
						salesman.salesmanname
					FROM debtorsmaster INNER JOIN custbranch
					ON debtorsmaster.debtorno=custbranch.debtorno
					INNER JOIN areas
					ON custbranch.area = areas.areacode
					INNER JOIN salesman
					ON custbranch.salesman=salesman.salesmancode
					WHERE (";

			$i=0;
			foreach ($_POST['Areas'] as $Area) {
				if ($i>0) {
					$SQL .= " OR ";
				}
				$i++;
				$SQL .= "custbranch.area='" . $Area ."'";
			}

			$SQL .= ") ORDER BY custbranch.area,
					custbranch.salesman,
					debtorsmaster.debtorno,
					custbranch.branchcode";
		} else {
		/* there are a range of salesfolk selected need to build the where clause */
			$SQL = "SELECT debtorsmaster.debtorno,
					debtorsmaster.name,
					debtorsmaster.address1,
					debtorsmaster.address2,
					debtorsmaster.address3,
					debtorsmaster.address4,
					debtorsmaster.address5,
					debtorsmaster.address6,
					debtorsmaster.salestype,
					custbranch.branchcode,
					custbranch.brname,
					custbranch.braddress1,
					custbranch.braddress2,
					custbranch.braddress3,
					custbranch.braddress4,
					custbranch.braddress5,
					custbranch.braddress6,
					custbranch.contactname,
					custbranch.phoneno,
					custbranch.faxno,
					custbranch.email,
					custbranch.area,
					custbranch.salesman,
					areas.areadescription,
					salesman.salesmanname
				FROM debtorsmaster INNER JOIN custbranch
				ON debtorsmaster.debtorno=custbranch.debtorno
				INNER JOIN areas
				ON custbranch.area = areas.areacode
				INNER JOIN salesman
				ON custbranch.salesman=salesman.salesmancode
				WHERE (";

			$i=0;
			foreach ($_POST['Areas'] as $Area) {
				if ($i>0) {
					$SQL .= " OR ";
				}
				$i++;
				$SQL .= "custbranch.area='" . $Area ."'";
			}

			$SQL .= ") AND (";

			$i=0;
			foreach ($_POST['SalesPeople'] as $Salesperson) {
				if ($i>0) {
					$SQL .= " OR ";
				}
				$i++;
				$SQL .= "custbranch.salesman='" . $Salesperson ."'";
			}

			$SQL .=") ORDER BY custbranch.area,
					custbranch.salesman,
					debtorsmaster.debtorno,
					custbranch.branchcode";
		} /*end if Salesfolk =='All' */

	} /* end if not all sales areas was selected */

	$ErrMsg = __('The customer List could not be retrieved');
	$CustomersResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($CustomersResult) == 0) {
	  $Title = __('Customer List') . ' - ' . __('Problem Report') . '....';
	  include(__DIR__ . '/includes/header.php');
	  prnMsg( __('This report has no output because there were no customers retrieved'), 'error' );
	  echo '<br /><a href="' .$RootPath .'/index.php">' .  __('Back to the menu'). '</a>';
	  include(__DIR__ . '/includes/footer.php');
	  exit();
	}

	$HTML = '';

	if (isset($_POST['PrintPDF'])) {
		$HTML .= '<html>
					<head>';
		$HTML .= '<link href="css/reports.css" rel="stylesheet" type="text/css" />';
	}

	$HTML .= '<meta name="author" content="WebERP " . $Version">
					<meta name="Creator" content="webERP https://www.weberp.org">
				</head>
				<body>';

	$Heading = __('Customers List for'). ' ';

	if (in_array('All', $_POST['Areas'])){
		$Heading .= __('All Territories'). ' ';
	} else {
		if (count($_POST['Areas'])==1){
			$Heading .= __('Territory') . ' ' . $_POST['Areas'][0];
		} else {
			$Heading .= __('Territories'). ' ';
			$NoOfAreas = count($_POST['Areas']);
			$i=1;
			foreach ($_POST['Areas'] as $Area){
				if ($i==$NoOfAreas){
					$Heading .= __('and') . ' ' . $Area . ' ';
				} elseif ($i==($NoOfAreas-1)) {
					$Heading .= $Area . ' ';
				} else {
					$Heading .= $Area . ', ';
				}
			}
		}
	}

	$Heading .= ' '. __('and for').' ';
	if (in_array('All', $_POST['SalesPeople'])){
		$Heading .= __('All Salespeople');
	} else {
		if (count($_POST['SalesPeople'])==1){
			$Heading .= __('only') .' ' . $_POST['SalesPeople'][0];
		} else {
			$Heading .= __('Salespeople') .' ';
			$NoOfSalesfolk = count($_POST['SalesPeople']);
			$i=1;
			foreach ($_POST['SalesPeople'] as $Salesperson){
				if ($i==$NoOfSalesfolk){
					$Heading .= __('and') . ' ' . $Salesperson . " ";
				} elseif ($i==($NoOfSalesfolk-1)) {
					$Heading .= $Salesperson . " ";
				} else {
					$Heading .= $Salesperson . ", ";
				}
			}
		}
	}

	$HTML .= '<div class="centre" id="ReportHeader">
				' . $_SESSION['CompanyRecord']['coyname'] . '<br />
				' . $Heading . '<br />
				' . __('Printed') . ': ' . date($_SESSION['DefaultDateFormat']) . '<br />
			</div>
			<table>
				<thead>
					<tr>
						<th>' . __('Act Code') . '</th>
						<th>' . __('Postal Address') . '</th>
						<th>' . __('Branch Code') . '</th>
						<th>' . __('Branch Contact Information') . '</th>
						<th>' . __('Branch Delivery Address') . '</th>
					</tr>
				</thead>
				<tbody>';

	$Area ='';
	$SalesPerson='';

	while($Customers = DB_fetch_array($CustomersResult)) {

		if ($_POST['Activity']!='All') {

			/*Get the total turnover in local currency for the customer/branch
			since the date entered */

			$SQL = "SELECT SUM((ovamount+ovfreight+ovdiscount)/rate) AS turnover
					FROM debtortrans
					WHERE debtorno='" . $Customers['debtorno'] . "'
					AND branchcode='" . $Customers['branchcode'] . "'
					AND (type=10 or type=11)
					AND trandate >='" . FormatDateForSQL($_POST['ActivitySince']). "'";
			$ActivityResult = DB_query($SQL, __('Could not retrieve the activity of the branch because'), __('The failed SQL was'));

			$ActivityRow = DB_fetch_row($ActivityResult);
			$LocalCurrencyTurnover = $ActivityRow[0];

			if ($_POST['Activity'] =='GreaterThan') {
				if ($LocalCurrencyTurnover > $_POST['ActivityAmount']) {
					$PrintThisCustomer = true;
				} else {
					$PrintThisCustomer = false;
				}
			} elseif ($_POST['Activity'] =='LessThan') {
				if ($LocalCurrencyTurnover < $_POST['ActivityAmount']) {
					$PrintThisCustomer = true;
				} else {
					$PrintThisCustomer = false;
				}
			}
		} else {
			$PrintThisCustomer = true;
		}

		if ($PrintThisCustomer) {

			$HTML .='<tr class="striped_row">';
			if ($Area!=$Customers['area']) {
				$HTML .= '<th colspan="3">' . __('Customers in') . ' ' . $Customers['areadescription'] . '<br />';
				$Area = $Customers['area'];
			}

			if ($SalesPerson!=$Customers['salesman']) {
				$HTML .= '' . __('Salesman') . ' ' . $Customers['salesmanname'] . '</th>';
				$SalesPerson = $Customers['salesman'];
			}
			$HTML .= '</tr>';

			$CustomerDetails = $Customers['name'];
			for ($i = 1; $i<=6; $i++) {
				if ($Customers['address' . $i] != '') {
					$CustomerDetails .= '<br />' . $Customers['address' . $i];
				}
			}

			$HTML .= '<tr class="striped_row">
						<td>' . $Customers['debtorno'] . '</td>
						<td>' . $CustomerDetails . '</td>
						<td>' . $Customers['branchcode'] . '<br />
							' . __('Price List') . ': ' . $Customers['salestype'] . '
						</td>';


			if ($_POST['Activity']!='All') {
				$HTML .= '<td>' . __('Turnover') . ' - ' . locale_number_format($LocalCurrencyTurnover,0) . '</td>';
			}

			$HTML .= '<td>' . $Customers['brname'] . '<br />
						  ' . $Customers['contactname'] . '<br />
						  ' . __('Ph'). ': ' . $Customers['phoneno'] . '<br />
						  ' . __('Fax').': ' . $Customers['faxno'] . '
						</td>';

			$BranchAddress = $Customers['name'];
			for ($i = 1; $i<=6; $i++) {
				if ($Customers['braddress' . $i] != '') {
					$BranchAddress .= '<br />' . $Customers['braddress' . $i];
				}
			}

			$HTML .= '<td>' . $BranchAddress . '</td>
					</tr>';
		} /*end if $PrintThisCustomer == true */
	} /*end while loop */


	if (isset($_POST['PrintPDF'])) {
		$HTML .= '</tbody>
				<div class="footer fixed-section">
					<div class="right">
						<span class="page-number">Page </span>
					</div>
				</div>
			</table>';
	} else {
		$HTML .= '</tbody>
				</table>
				<div class="centre">
					<form><input type="submit" name="close" value="' . __('Close') . '" onclick="window.close()" /></form>
				</div>';
	}
	$HTML .= '</body>
		</html>';

	if (isset($_POST['PrintPDF'])) {
		$DomPDF = new Dompdf($DomPDFOptions); // Pass the options object defined in SetDomPDFOptions.php containing common options
		$DomPDF->loadHtml($HTML);

		// (Optional) Setup the paper size and orientation
		$DomPDF->setPaper($_SESSION['PageSize'], 'portrait');

		// Render the HTML as PDF
		$DomPDF->render();

		// Output the generated PDF to Browser
		$DomPDF->stream($_SESSION['DatabaseName'] . '_CustomerListing_' . date('Y-m-d') . '.pdf', array(
			"Attachment" => false
		));
	} else {
		$Title = __('Customer Details Listing');
		include(__DIR__ . '/includes/header.php');
		echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/bank.png" title="' . __('Receipts') . '" alt="" />' . ' ' . __('Create PDF Customer Details Listing') . '</p>';
		echo $HTML;
		include(__DIR__ . '/includes/footer.php');
	}

} else {
	$ExtraHeadContent = '
<style>
	.ScriptTitle { display: none !important; }
	.MainBody { padding: 0 !important; gap: 0 !important; background: transparent !important; }
	.db-page { padding: var(--space-8) var(--space-6); background: var(--bg-main); min-height: 100vh; font-family: "Inter", sans-serif; }
	
	.premium-header { margin-bottom: 40px; position: relative; }
	.premium-header::before { display: none !important; }
	
	.db-card-header { 
		background: #f9fafb; 
		border-bottom: 1px solid #f3f4f6; 
		padding: 20px 30px;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
	.db-card-title {
		font-size: 1.1rem;
		font-weight: 850;
		color: #064e3b;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 12px;
		text-transform: uppercase;
		letter-spacing: 1px;
	}
	
	.architect-btn {
		display: inline-flex; align-items: center; justify-content: center; gap: 10px;
		padding: 14px 28px; border-radius: 12px;
		background: #059669; color: #ffffff; border: none;
		font-weight: 700; font-size: 0.85rem; text-decoration: none;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		box-shadow: 0 4px 12px rgba(5, 150, 105, 0.2);
		cursor: pointer; width: 100%;
	}
	.architect-btn:hover { background: #065f46; transform: translateY(-2px); box-shadow: 0 8px 20px rgba(5, 150, 105, 0.3); }
	.architect-btn i { color: #ffffff !important; }
	.architect-btn.secondary { background: #e5e7eb; color: #374151; box-shadow: none; }
	.architect-btn.secondary:hover { background: #d1d5db; color: #111827; }
	.architect-btn.secondary i { color: #374151 !important; }
	
	.custom-bottom-layout { 
		display: grid; 
		grid-template-columns: 380px 1fr; 
		gap: 32px; 
		align-items: start; 
	}
	.custom-range-grid {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 20px;
		margin-bottom: 24px;
	}
	
	.breadcrumb-item { display: flex; align-items: center; gap: 8px; color: var(--text-secondary); text-decoration: none; transition: all 0.2s; }
	.breadcrumb-item:hover { color: #059669; }
	.breadcrumb-separator { font-size: 0.6rem; opacity: 0.4; margin: 0 4px; }
	
	@media (max-width: 900px) {
		.custom-bottom-layout { 
			display: flex; 
			flex-direction: column; 
		}
		.custom-range-grid {
			grid-template-columns: 1fr;
		}
	}
</style>';

	include(__DIR__ . '/includes/header.php');

	echo '<div class="db-page">
		<div class="premium-header">
			<div style="display: flex; justify-content: space-between; align-items: flex-end;">
				<div>
					<div style="font-size: 0.72rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; text-transform: lowercase; letter-spacing: 1px;">
						<a href="index.php" class="breadcrumb-item"><i class="fas fa-home"></i> ' . __('home') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<a href="index.php?Application=AR" class="breadcrumb-item">' . __('receivables') . '</a>
						<i class="fas fa-chevron-right breadcrumb-separator"></i>
						<span style="color: #064e3b; opacity: 0.9;">' . __('customer listing') . '</span>
					</div>
					<div style="display: flex; align-items: center; gap: 24px;">
						<div>
							<h1 style="font-size: 2.5rem; font-weight: 950; letter-spacing: -2px; color: #064e3b; margin: 0; line-height: 1;">' . $Title . '</h1>
							<p style="font-size: 1.1rem; margin-top: 8px; color: #065f46; font-weight: 500; opacity: 0.8;">' . __('Comprehensive branch and territory information report with activity filters') . '</p>
						</div>
					</div>
				</div>
			</div>
		</div>';

	echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" target="_blank" style="display: contents;">';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	echo '<div class="custom-bottom-layout">
			<aside class="db-sidebar" style="display: flex; flex-direction: column; gap: 24px;">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-cog" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Actions') . '
						</h3>
					</div>
					<div style="padding: 24px; display: flex; flex-direction: column; gap: 12px; background: #fff;">
						<button type="submit" name="PrintPDF" class="architect-btn">
							<i class="fas fa-file-pdf"></i> ' . __('Generate PDF') . '
						</button>
						<button type="submit" name="View" class="architect-btn secondary">
							<i class="fas fa-eye"></i> ' . __('View Online') . '
						</button>
					</div>
				</div>

				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-filter" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Activity Filter') . '
						</h3>
					</div>
					<div style="padding: 24px; background: #fff; display: flex; flex-direction: column; gap: 20px;">
						<div class="db-form-group">
							<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Filter Mode') . '</label>
							<select name="Activity" class="db-input" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5;">
								<option selected="selected" value="All">' .  __('All customers') . '</option>
								<option value="GreaterThan">' .  __('Sales Greater Than') . '</option>
								<option value="LessThan">' .  __('Sales Less Than') . '</option>
							</select>
						</div>

						<div class="db-form-group">
							<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Activity Amount') . '</label>
							<input type="text" class="db-input number" name="ActivityAmount" size="8" maxlength="8" value="0" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5; padding: 0 16px; box-sizing: border-box;" />
						</div>

						<div class="db-form-group">
							<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 8px;">' . __('Activity Since') . '</label>
							<input type="date" name="ActivitySince" class="db-input" value="' . FormatDateForSQL(date($_SESSION['DefaultDateFormat'], mktime(0,0,0,date('m')-6,0,date('y')))) . '" style="width: 100%; border-radius: 12px; height: 50px; font-weight: 600; border-color: #d1fae5; padding: 0 16px; box-sizing: border-box;" />
						</div>
					</div>
				</div>
			</aside>

			<main class="db-main" style="display: flex; flex-direction: column; gap: 32px;">
				<div class="db-card" style="border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 1px 2px rgba(0,0,0,0.05); overflow: hidden;">
					<div class="db-card-header">
						<h3 class="db-card-title">
							<i class="fas fa-list-ul" style="font-size: 0.9rem; opacity: 0.7;"></i>' . __('Report Selection') . '
						</h3>
					</div>
					<div style="padding: 30px; background: #fff;">
						<div class="custom-range-grid">
							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 12px;">' . __('Sales Areas') . '</label>
								<select name="Areas[]" multiple="multiple" class="db-input" style="width: 100%; border-radius: 12px; height: 300px; font-weight: 600; border-color: #d1fae5; padding: 12px;">';
	$SQL="SELECT areacode, areadescription FROM areas";
	$AreasResult = DB_query($SQL);
	echo '<option selected="selected" value="All">' . __('All Areas') . '</option>';
	while($MyRow = DB_fetch_array($AreasResult)) {
		echo '<option value="' . $MyRow['areacode'] . '">' . $MyRow['areadescription'] . '</option>';
	}
	echo '						</select>
							</div>

							<div class="db-form-group">
								<label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 900; letter-spacing: 1.2px; color: #065f46; display: block; margin-bottom: 12px;">' . __('Salespeople') . '</label>
								<select name="SalesPeople[]" multiple="multiple" class="db-input" style="width: 100%; border-radius: 12px; height: 300px; font-weight: 600; border-color: #d1fae5; padding: 12px;">
									<option selected="selected" value="All">' .  __('All Salespeople') . '</option>';
	$SQL = "SELECT salesmancode, salesmanname FROM salesman";
	$SalesFolkResult = DB_query($SQL);
	while($MyRow = DB_fetch_array($SalesFolkResult)) {
		echo '<option value="' . $MyRow['salesmancode'] . '">' . $MyRow['salesmanname'] . '</option>';
	}
	echo '						</select>
							</div>
						</div>
					</div>
				</div>
			</main>
		</div>';

	echo '</form>
	</div>';

	include(__DIR__ . '/includes/footer.php');
	exit();
} /*end of else not PrintPDF */
