<?php
require (__DIR__ . '/includes/session.php');

$Title = __('Supplier Maintenance');
/* webERP manual links before header.php */
$ViewTopic = 'AccountsPayable';
$BookMark = 'NewSupplier';
include ('includes/header.php');

include ('includes/SQL_CommonFunctions.php');
include ('includes/CountriesArray.php');

if (isset($_POST['SupplierSince'])) {
	$_POST['SupplierSince'] = ConvertSQLDate($_POST['SupplierSince']);
}

function Is_ValidAccount($ActNo) {

	if (mb_strlen($ActNo) < 16) {
		echo __('NZ account numbers must have 16 numeric characters in it');
		return false;
	}

	if (!Is_double((float)$ActNo)) {
		echo __('NZ account numbers entered must use all numeric characters in it');
		return false;
	}

	$BankPrefix = mb_substr($ActNo, 0, 2);
	$BranchNumber = (int)(mb_substr($ActNo, 3, 4));

	if ($BankPrefix == '29') {
		echo __('NZ Accounts codes with the United Bank are not verified') . ', ' . __('be careful to enter the correct account number');
		return false;
	}

	//Verify correct branch details
	switch ($BankPrefix) {

		case '01':
			if (!(($BranchNumber >= 1 and $BranchNumber <= 999) or ($BranchNumber >= 1100 and $BranchNumber <= 1199))) {
				echo __('ANZ branches must be between 0001 and 0999 or between 1100 and 1199') . '. ' . __('The branch number used is invalid');
				return false;
			}
		break;
		case '02':
			if (!(($BranchNumber >= 1 and $BranchNumber <= 999) or ($BranchNumber >= 1200 and $BranchNumber <= 1299))) {
				echo __('Bank Of New Zealand branches must be between 0001 and 0999 or between 1200 and 1299') . '. ' . __('The branch number used is invalid');
				return false;
			}
		break;
		case '03':
			if (!(($BranchNumber >= 1 and $BranchNumber <= 999) or ($BranchNumber >= 1300 and $BranchNumber <= 1399))) {
				echo __('Westpac Trust branches must be between 0001 and 0999 or between 1300 and 1399') . '. ' . __('The branch number used is invalid');
				return false;
			}
		break;

		case '06':
			if (!(($BranchNumber >= 1 and $BranchNumber <= 999) or ($BranchNumber >= 1400 and $BranchNumber <= 1499))) {
				echo __('National Bank branches must be between 0001 and 0999 or between 1400 and 1499') . '. ' . __('The branch number used is invalid');
				return false;
			}
		break;

		case '08':
			if (!($BranchNumber >= 6500 and $BranchNumber <= 6599)) {
				echo __('National Australia branches must be between 6500 and 6599') . '. ' . __('The branch number used is invalid');
				return false;
			}
		break;
		case '09':
			if ($BranchNumber != 0) {
				echo __('The Reserve Bank branch should be 0000') . '. ' . __('The branch number used is invalid');
				return false;
			}
		break;
		case '12':

			//"13" "14" "15", "16", "17", "18", "19", "20", "21", "22", "23", "24":
			if (!($BranchNumber >= 3000 and $BranchNumber <= 4999)) {
				echo __('Trust Bank and Regional Bank branches must be between 3000 and 4999') . '. ' . __('The branch number used is invalid');
				return false;
			}
		break;

		case '11':
			if (!($BranchNumber >= 5000 and $BranchNumber <= 6499)) {
				echo __('Post Office Bank branches must be between 5000 and 6499') . '. ' . __('The branch number used is invalid');
				return false;
			}
		break;

		case '25':
			if (!($BranchNumber >= 2500 and $BranchNumber <= 2599)) {
				echo __('Countrywide Bank branches must be between 2500 and 2599') . '. ' . __('The branch number used is invalid');
				return false;
			}
		break;
		case '29':
			if (!($BranchNumber >= 2150 and $BranchNumber <= 2299)) {
				echo __('United Bank branches must be between 2150 and 2299') . '. ' . __('The branch number used is invalid');
				return false;
			}
		break;

		case '30':
			if (!($BranchNumber >= 2900 and $BranchNumber <= 2949)) {
				echo __('Hong Kong and Shanghai branches must be between 2900 and 2949') . '. ' . __('The branch number used is invalid');
				return false;
			}
		break;

		case '31':
			if (!($BranchNumber >= 2800 and $BranchNumber <= 2849)) {
				echo __('Citibank NA branches must be between 2800 and 2849') . '. ' . __('The branch number used is invalid');
				return false;
			}
		break;

		case '33':
			if (!($BranchNumber >= 6700 and $BranchNumber <= 6799)) {
				echo __('Rural Bank branches must be between 6700 and 6799') . '. ' . __('The branch number used is invalid');
				return false;
			}
		break;

		default:
			echo __('The prefix') . ' - ' . $BankPrefix . ' ' . __('is not a valid New Zealand Bank') . '.<br />' . __('If you are using webERP outside New Zealand error trapping relevant to your country should be used');
			return false;

	} // end of first Bank prefix switch
	for ($i = 3;$i <= 14;$i++) {

		$DigitVal = (float)(mb_substr($ActNo, $i, 1));

		switch ($i) {
			case 3:
				if ($BankPrefix == '08' or $BankPrefix == '09' or $BankPrefix == '25' or $BankPrefix == '33') {
					$CheckSum = 0;
				}
				else {
					$CheckSum = $CheckSum + ($DigitVal * 6);
				}
			break;

			case 4:
				if ($BankPrefix == '08' or $BankPrefix == '09' or $BankPrefix == '25' or $BankPrefix == '33') {
					$CheckSum = 0;
				}
				else {
					$CheckSum = $CheckSum + ($DigitVal * 3);
				}
			break;

			case 5:
				if ($BankPrefix == '08' or $BankPrefix == '09' or $BankPrefix == '25' or $BankPrefix == '33') {
					$CheckSum = 0;
				}
				else {
					$CheckSum = $CheckSum + ($DigitVal * 7);
				}
			break;

			case 6:
				if ($BankPrefix == '08' or $BankPrefix == '09' or $BankPrefix == '25' or $BankPrefix == '33') {
					$CheckSum = 0;
				}
				else {
					$CheckSum = $CheckSum + ($DigitVal * 9);
				}
			break;

			case 7:
				if ($BankPrefix == '08') {
					$CheckSum = $CheckSum + $DigitVal * 7;
				}
				elseif ($BankPrefix == '25' or $BankPrefix == '33') {
					$CheckSum = $CheckSum + $DigitVal * 1;
				}
			break;

			case 8:
				if ($BankPrefix == '08') {
					$CheckSum = $CheckSum + ($DigitVal * 6);
				}
				elseif ($BankPrefix == '09') {
					$CheckSum = 0;
				}
				elseif ($BankPrefix == '25' or $BankPrefix == '33') {
					$CheckSum = $CheckSum + $DigitVal * 7;
				}
				else {
					$CheckSum = $CheckSum + $DigitVal * 10;
				}
			break;

			case 9:
				if ($BankPrefix == '09') {
					$CheckSum = 0;
				}
				elseif ($BankPrefix == '25' or $BankPrefix == '33') {
					$CheckSum = $CheckSum + $DigitVal * 3;
				}
				else {
					$CheckSum = $CheckSum + $DigitVal * 5;
				}
			break;

			case 10:
				if ($BankPrefix == '08') {
					$CheckSum = $CheckSum + $DigitVal * 4;
				}
				elseif ($BankPrefix == '09') {
					if (($DigitVal * 5) > 9) {
						$CheckSum = $CheckSum + (int)mb_substr((string)($DigitVal * 5), 0, 1) + (int)mb_substr((string)($DigitVal * 5), mb_strlen((string)($DigitVal * 5)) - 1, 1);
					}
					else {
						$CheckSum = $CheckSum + $DigitVal * 5;
					}
				}
				elseif ($BankPrefix == '25' or $BankPrefix == '33') {
					$CheckSum = $CheckSum + $DigitVal;
				}
				else {
					$CheckSum = $CheckSum + $DigitVal * 8;
				}
			break;

			case 11:
				if ($BankPrefix == '08') {
					$CheckSum = $CheckSum + $DigitVal * 3;
				}
				elseif ($BankPrefix == '09') {
					if (($DigitVal * 4) > 9) {
						$CheckSum = $CheckSum + (int)mb_substr(($DigitVal * 4), 0, 1) + (int)mb_substr(($DigitVal * 4), mb_strlen($DigitVal * 4) - 1, 1);
					}
					else {
						$CheckSum = $CheckSum + $DigitVal * 4;
					}
				}
				elseif ($BankPrefix == '25' or $BankPrefix == '33') {
					$CheckSum = $CheckSum + $DigitVal * 7;
				}
				else {
					$CheckSum = $CheckSum + $DigitVal * 4;
				}
			break;

			case 12:
				if ($BankPrefix == '25' or $BankPrefix == '33') {
					$CheckSum = $CheckSum + $DigitVal * 3;
				}
				elseif ($BankPrefix == '09') {
					if (($DigitVal * 3) > 9) {
						$CheckSum = $CheckSum + (int)mb_substr(($DigitVal * 3), 0, 1) + (int)mb_substr(($DigitVal * 3), mb_strlen($DigitVal * 3) - 1, 1);
					}
					else {
						$CheckSum = $CheckSum + $DigitVal * 3;
					}
				}
				else {
					$CheckSum = $CheckSum + $DigitVal * 2;
				}
			break;

			case 13:
				if ($BankPrefix == '09') {
					if (($DigitVal * 2) > 9) {
						$CheckSum = $CheckSum + (int)mb_substr(($DigitVal * 2), 0, 1) + (int)mb_substr(($DigitVal * 2), mb_strlen($DigitVal * 2) - 1, 1);
					}
					else {
						$CheckSum = $CheckSum + $DigitVal * 2;
					}
				}
				else {
					$CheckSum = $CheckSum + $DigitVal;
				}
			break;

			case 14:
				if ($BankPrefix == '09') {
					$CheckSum = $CheckSum + $DigitVal;
				}
			break;
		} //end switch

	} //end for loop
	if ($BankPrefix == '25' or $BankPrefix == '33') {
		if ($CheckSum / 10 - (int)($CheckSum / 10) != 0) {
			echo '<p>' . __('The account number entered does not meet the banking check sum requirement and cannot be a valid account number');
			return false;
		}
	}
	else {
		if ($CheckSum / 11 - (int)($CheckSum / 11) != 0) {
			echo '<p>' . __('The account number entered does not meet the banking check sum requirement and cannot be a valid account number');
			return false;
		}
	}
	return false;
} //End Function


if (isset($_GET['SupplierID'])) {
	$SupplierID = mb_strtoupper($_GET['SupplierID']);
} elseif (isset($_POST['SupplierID'])) {
	$SupplierID = mb_strtoupper($_POST['SupplierID']);
} else {
	unset($SupplierID);
}

echo '<div class="db-page">
		<div class="db-page-header">
			<div>
				<h2 class="db-page-title"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="db-title-icon"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg> ' . __('Supplier Management') . '</h2>
				<p class="db-page-subtitle">' . (isset($SupplierID) ? __('Edit and update supplier details') : __('Register a new supplier in the system')) . '</p>
			</div>
			<div class="db-header-actions">
				<a href="' . $RootPath . '/SelectSupplier.php" class="db-btn db-btn-secondary">' . __('Back to Selection') . '</a>
			</div>
		</div>';

if (isset($SupplierID)) {
	echo '<div class="db-alert db-alert-info">
			<span class="db-alert-icon"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg></span>
			<span>' . __('You are currently managing supplier:') . ' <strong>' . $SupplierID . '</strong> &mdash; <a href="' . $RootPath . '/SupplierContacts.php?SupplierID=' . $SupplierID . '" class="db-alert-link">' . __('Review Contact Details') . ' →</a></span>
		</div>';
}
$InputError = 0;

$Errors = Array();
if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$i = 1;
	/* actions to take once the user has clicked the submit button
	 ie the page has called itself with some user input */

	//first off validate inputs sensible
	$SQL = "SELECT COUNT(supplierid) FROM suppliers WHERE supplierid='" . $SupplierID . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0 and isset($_POST['New'])) {
		$InputError = 1;
		prnMsg(__('The supplier number already exists in the database'), 'error');
		$Errors[$i] = 'ID';
		$i++;
	}
	if (mb_strlen(trim($_POST['SuppName'])) > 40 or mb_strlen(trim($_POST['SuppName'])) == 0 or trim($_POST['SuppName']) == '') {

		$InputError = 1;
		prnMsg(__('The supplier name must be entered and has maximum 40 characters)'), 'error');
		$Errors[$i] = 'Name';
		$i++;
	}
	if ($_SESSION['AutoSupplierNo'] == 0 and mb_strlen($SupplierID) == 0) {
		$InputError = 1;
		prnMsg(__('The Supplier Code cannot be empty'), 'error');
		$Errors[$i] = 'ID';
		$i++;
	}
	if (ContainsIllegalCharacters($SupplierID)) {
		$InputError = 1;
		prnMsg(__('The supplier code cannot contain any of the illegal characters') . ' ' . '" \' - &amp; or a space', 'error');
		$Errors[$i] = 'ID';
		$i++;
	}
	if (mb_strlen($_POST['Phone']) > 25) {
		$InputError = 1;
		prnMsg(__('The telephone number must be 25 characters or less long'), 'error');
		$Errors[$i] = 'Telephone';
		$i++;
	}
	if (mb_strlen($_POST['Fax']) > 25) {
		$InputError = 1;
		prnMsg(__('The fax number must be 25 characters or less long'), 'error');
		$Errors[$i] = 'Fax';
		$i++;
	}
	if (mb_strlen($_POST['Email']) > 55) {
		$InputError = 1;
		prnMsg(__('The email address must be 55 characters or less long'), 'error');
		$Errors[$i] = 'Email';
		$i++;
	}
	if (mb_strlen($_POST['Email']) > 0 and !IsEmailAddress($_POST['Email'])) {
		$InputError = 1;
		prnMsg(__('The email address is not correctly formed'), 'error');
		$Errors[$i] = 'Email';
		$i++;
	}
	if (mb_strlen($_POST['URL']) > 50) {
		$InputError = 1;
		prnMsg(__('The URL address must be 50 characters or less long'), 'error');
		$Errors[$i] = 'URL';
		$i++;
	}
	if (mb_strlen($_POST['BankRef']) > 12) {
		$InputError = 1;
		prnMsg(__('The bank reference text must be less than 12 characters long'), 'error');
		$Errors[$i] = 'BankRef';
		$i++;
	}
	if (!Is_Date($_POST['SupplierSince'])) {
		$InputError = 1;
		prnMsg(__('The supplier since field must be a date in the format') . ' ' . $_SESSION['DefaultDateFormat'], 'error');
		$Errors[$i] = 'SupplierSince';
		$i++;
	}

	/*
	elseif (mb_strlen($_POST['BankAct']) > 1 ) {
		if (!Is_ValidAccount($_POST['BankAct'])) {
			prnMsg(__('The bank account entry is not a valid New Zealand bank account number. This is (of course) no concern if the business operates outside of New Zealand'),'warn');
		}
	}
	*/

	if ($InputError != 1) {

		$SQL_SupplierSince = FormatDateForSQL($_POST['SupplierSince']);

		$latitude = 0;
		$longitude = 0;
		if ($_SESSION['geocode_integration'] == 1) {
			// Get the lat/long from OpenStreetMap Nominatim
			$SQL = "SELECT * FROM geocode_param";
			$Resultgeo = DB_query($SQL);
			$Row = DB_fetch_array($Resultgeo);

			// Build address string
			$Address = urlencode($_POST['Address1'] . ', ' . $_POST['Address2'] . ', ' . $_POST['Address3'] . ', ' . $_POST['Address4'] . ', ' . $_POST['Address5'] . ', ' . $_POST['Address6']);
			$BaseURL = "https://nominatim.openstreetmap.org/search?format=json&q=";
			$RequestURL = $BaseURL . $Address . '&limit=1';

			// Set up proper headers for Nominatim usage policy
			$opts = array(
				'http'=>array(
					'method'=>"GET",
					'header'=>"User-Agent: webERP-geocoding\r\n"
				)
			);
			$context = stream_context_create($opts);
			$buffer = @file_get_contents($RequestURL, false, $context);

			if ($buffer !== false) {
				$json = json_decode($buffer, true);
				if (!empty($json) && isset($json[0]['lat']) && isset($json[0]['lon'])) {
					// Successful geocode
					$latitude = $json[0]['lat'];
					$longitude = $json[0]['lon'];
				} else {
					// No results found
					echo '<h3>' . __('Address') . ': ' . $Address . ' ' . __('failed to geocode') . ' - ' . __('No results found') . '</h3>';
				}
			} else {
				// Connection failed
				echo '<h3>' . __('Address') . ': ' . $Address . ' ' . __('failed to geocode') . ' - ' . __('Connection failed') . '</h3>';
			}

			// Respect Nominatim usage policy: 1 request per second
			usleep(1000000);
		}
		if (!isset($_POST['New'])) {

			$SuppTransSQL = "SELECT supplierno
							FROM supptrans
							WHERE supplierno='" . $SupplierID . "'";
			$SuppResult = DB_query($SuppTransSQL);
			$SuppTrans = DB_num_rows($SuppResult);

			$SuppCurrsSQL = "SELECT currcode
							FROM suppliers
							WHERE supplierid='" . $SupplierID . "'";
			$Currresult = DB_query($SuppCurrsSQL);
			$SuppCurrs = DB_fetch_row($Currresult);

			if ($SuppTrans == 0) {
				$SQL = "UPDATE suppliers SET suppname='" . $_POST['SuppName'] . "',
							address1='" . $_POST['Address1'] . "',
							address2='" . $_POST['Address2'] . "',
							address3='" . $_POST['Address3'] . "',
							address4='" . $_POST['Address4'] . "',
							address5='" . $_POST['Address5'] . "',
							address6='" . $_POST['Address6'] . "',
							telephone='" . $_POST['Phone'] . "',
							fax = '" . $_POST['Fax'] . "',
							email = '" . $_POST['Email'] . "',
							url = '" . $_POST['URL'] . "',
							supptype = '" . $_POST['SupplierType'] . "',
							currcode='" . $_POST['CurrCode'] . "',
							suppliersince='" . $SQL_SupplierSince . "',
							paymentterms='" . $_POST['PaymentTerms'] . "',
							bankpartics='" . $_POST['BankPartics'] . "',
							bankref='" . $_POST['BankRef'] . "',
					 		bankact='" . $_POST['BankAct'] . "',
							remittance='" . $_POST['Remittance'] . "',
							taxgroupid='" . $_POST['TaxGroup'] . "',
							salespersonid='" . $_POST['SalesPersonID'] . "',
							factorcompanyid='" . $_POST['FactorID'] . "',
							lat='" . $latitude . "',
							lng='" . $longitude . "',
							taxref='" . $_POST['TaxRef'] . "',
							defaultshipper='" . $_POST['DefaultShipper'] . "',
							defaultgl='" . $_POST['DefaultGL'] . "'
						WHERE supplierid = '" . $SupplierID . "'";
			}
			else {
				if ($SuppCurrs[0] != $_POST['CurrCode']) {
					prnMsg(__('Cannot change currency code as transactions already exist'), 'info');
				}
				$SQL = "UPDATE suppliers SET suppname='" . $_POST['SuppName'] . "',
							address1='" . $_POST['Address1'] . "',
							address2='" . $_POST['Address2'] . "',
							address3='" . $_POST['Address3'] . "',
							address4='" . $_POST['Address4'] . "',
							address5='" . $_POST['Address5'] . "',
							address6='" . $_POST['Address6'] . "',
							telephone='" . $_POST['Phone'] . "',
							fax = '" . $_POST['Fax'] . "',
							email = '" . $_POST['Email'] . "',
							url = '" . $_POST['URL'] . "',
							supptype = '" . $_POST['SupplierType'] . "',
							suppliersince='" . $SQL_SupplierSince . "',
							paymentterms='" . $_POST['PaymentTerms'] . "',
							bankpartics='" . $_POST['BankPartics'] . "',
							bankref='" . $_POST['BankRef'] . "',
					 		bankact='" . $_POST['BankAct'] . "',
							remittance='" . $_POST['Remittance'] . "',
							taxgroupid='" . $_POST['TaxGroup'] . "',
							factorcompanyid='" . $_POST['FactorID'] . "',
							salespersonid='" . $_POST['SalesPersonID'] . "',
							lat='" . $latitude . "',
							lng='" . $longitude . "',
							taxref='" . $_POST['TaxRef'] . "',
							defaultshipper='" . $_POST['DefaultShipper'] . "',
							defaultgl='" . $_POST['DefaultGL'] . "'
						WHERE supplierid = '" . $SupplierID . "'";
			}

			$ErrMsg = __('The supplier could not be updated because');
			// echo $SQL;
			$Result = DB_query($SQL, $ErrMsg);

			prnMsg(__('The supplier master record for') . ' ' . $SupplierID . ' ' . __('has been updated'), 'success');

		}
		else { //its a new supplier
			if ($_SESSION['AutoSupplierNo'] == 1) {
				/* system assigned, sequential, numeric */
				$SupplierID = GetNextTransNo(600);
			}
			$SQL = "INSERT INTO suppliers (supplierid,
										suppname,
										address1,
										address2,
										address3,
										address4,
										address5,
										address6,
										telephone,
										fax,
										email,
										url,
										supptype,
										currcode,
										suppliersince,
										paymentterms,
										bankpartics,
										bankref,
										bankact,
										remittance,
										taxgroupid,
										factorcompanyid,
										salespersonid,
										lat,
										lng,
										taxref,
										defaultshipper,
										defaultgl)
								 VALUES ('" . $SupplierID . "',
								 	'" . $_POST['SuppName'] . "',
									'" . $_POST['Address1'] . "',
									'" . $_POST['Address2'] . "',
									'" . $_POST['Address3'] . "',
									'" . $_POST['Address4'] . "',
									'" . $_POST['Address5'] . "',
									'" . $_POST['Address6'] . "',
									'" . $_POST['Phone'] . "',
									'" . $_POST['Fax'] . "',
									'" . $_POST['Email'] . "',
									'" . $_POST['URL'] . "',
									'" . $_POST['SupplierType'] . "',
									'" . $_POST['CurrCode'] . "',
									'" . $SQL_SupplierSince . "',
									'" . $_POST['PaymentTerms'] . "',
									'" . $_POST['BankPartics'] . "',
									'" . $_POST['BankRef'] . "',
									'" . $_POST['BankAct'] . "',
									'" . $_POST['Remittance'] . "',
									'" . $_POST['TaxGroup'] . "',
									'" . $_POST['FactorID'] . "',
									'" . $_POST['SalesPersonID'] . "',
									'" . $latitude . "',
									'" . $longitude . "',
									'" . $_POST['TaxRef'] . "',
									'" . $_POST['DefaultShipper'] . "',
									'" . $_POST['DefaultGL'] . "'
								)";
			$ErrMsg = __('The supplier') . ' ' . $_POST['SuppName'] . ' ' . __('could not be added because');

			$Result = DB_query($SQL, $ErrMsg);

			prnMsg(__('A new supplier for') . ' ' . $_POST['SuppName'] . ' ' . __('has been added to the database'), 'success');

			echo '<p>
				<a class="toplink"  href="' . $RootPath . '/SupplierContacts.php?SupplierID=' . $SupplierID . '">' . __('Review Supplier Contact Details') . '</a>
				</p>';

			unset($SupplierID);
			unset($_POST['SuppName']);
			unset($_POST['Address1']);
			unset($_POST['Address2']);
			unset($_POST['Address3']);
			unset($_POST['Address4']);
			unset($_POST['Address5']);
			unset($_POST['Address6']);
			unset($_POST['Phone']);
			unset($_POST['Fax']);
			unset($_POST['Email']);
			unset($_POST['URL']);
			unset($_POST['SupplierType']);
			unset($_POST['CurrCode']);
			unset($SQL_SupplierSince);
			unset($_POST['PaymentTerms']);
			unset($_POST['BankPartics']);
			unset($_POST['BankRef']);
			unset($_POST['BankAct']);
			unset($_POST['Remittance']);
			unset($_POST['TaxGroup']);
			unset($_POST['FactorID']);
			unset($_POST['TaxRef']);
			unset($_POST['DefaultGL']);

		}

	}
	else {

		prnMsg(__('Validation failed') . __('no updates or deletes took place'), 'warn');

	}

} elseif (isset($_POST['delete']) and $_POST['delete'] != '') {

	//the link to delete a selected record was clicked instead of the submit button
	$CancelDelete = 0;

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'SuppTrans' , PurchOrders, SupplierContacts
	$SQL = "SELECT COUNT(*) FROM supptrans WHERE supplierno='" . $SupplierID . "'";
	$Result = DB_query($SQL);
	$MyRow = DB_fetch_row($Result);
	if ($MyRow[0] > 0) {
		$CancelDelete = 1;
		prnMsg(__('Cannot delete this supplier because there are transactions that refer to this supplier'), 'warn');
		echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('transactions against this supplier');

	}
	else {
		$SQL = "SELECT COUNT(*) FROM purchorders WHERE supplierno='" . $SupplierID . "'";
		$Result = DB_query($SQL);
		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] > 0) {
			$CancelDelete = 1;
			prnMsg(__('Cannot delete the supplier record because purchase orders have been created against this supplier'), 'warn');
			echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('orders against this supplier');
		}
		else {
			$SQL = "SELECT COUNT(*) FROM suppliercontacts WHERE supplierid='" . $SupplierID . "'";
			$Result = DB_query($SQL);
			$MyRow = DB_fetch_row($Result);
			if ($MyRow[0] > 0) {
				$CancelDelete = 1;
				prnMsg(__('Cannot delete this supplier because there are supplier contacts set up against it') . ' - ' . __('delete these first'), 'warn');
				echo '<br />' . __('There are') . ' ' . $MyRow[0] . ' ' . __('supplier contacts relating to this supplier');

			}
		}

	}
	if ($CancelDelete == 0) {
		$SQL = "DELETE FROM suppliers WHERE supplierid='" . $SupplierID . "'";
		$Result = DB_query($SQL);
		prnMsg(__('Supplier record for') . ' ' . $SupplierID . ' ' . __('has been deleted'), 'success');
		unset($SupplierID);
		unset($_SESSION['SupplierID']);
	} //end if Delete supplier

}

if (!isset($SupplierID)) {

	/*If the page was called without $SupplierID passed to page then assume a new supplier is to be entered show a form with a Supplier Code field other wise the form showing the fields with the existing entries against the supplier will show for editing with only a hidden SupplierID field*/

	$Result = DB_query("SELECT typeid, typename FROM suppliertype");
	if (DB_num_rows($Result) == 0) {
		prnMsg(__('There are no supplier types setup. These must be created first'), 'error');
		include(__DIR__ . '/includes/footer.php');
		exit();
	}

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<input type="hidden" name="New" value="Yes" />
			
			<div class="db-card">
				<div class="db-card-header">
					<h3 class="db-card-title">' . __('New Supplier Registration') . '</h3>
				</div>
				<div class="db-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-4); padding: var(--space-4);">
					
					<!-- Primary Details -->
					<div class="db-form-section" style="display: flex; flex-direction: column; gap: var(--space-3);">
						<h4 style="font-size: 0.8rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-soft); padding-bottom: 4px;">' . __('Identity & Basic Info') . '</h4>';

	/* if $AutoSupplierNo is off (not 0) then provide an input box for the SupplierID to manually assigned */
	if ($_SESSION['AutoSupplierNo'] == 0) {
		echo '<div class="db-form-group">
				<label for="SupplierID">' . __('Supplier Code') . '</label>
				<input type="text" data-type="no-illegal-chars" required="required" name="SupplierID" placeholder="' . __('e.g. SUP001') . '" maxlength="10" />
				<span class="db-field-help">' . __('Unique identifier (max 10 chars)') . '</span>
			</div>';
	}
	echo '<div class="db-form-group">
			<label for="SuppName">' . __('Supplier Name') . '</label>
			<input type="text" pattern="(?!^\s+$)[^<>+]{1,40}" required="required" name="SuppName" placeholder="' . __('Business Name') . '" maxlength="40" />
		</div>
		<div class="db-form-group">
			<label for="SupplierType">' . __('Supplier Type') . '</label>
			<select name="SupplierType">';
	$Result = DB_query("SELECT typeid, typename FROM suppliertype");
	while ($MyRow = DB_fetch_array($Result)) {
		echo '<option value="' . $MyRow['typeid'] . '">' . $MyRow['typename'] . '</option>';
	}
	echo '</select>
		</div>
		<div class="db-form-group">
			<label for="SupplierSince">' . __('Supplier Since') . '</label>
			<input type="date" name="SupplierSince" value="' . date('Y-m-d') . '" />
		</div>
	</div>

	<!-- Address Information -->
	<div class="db-form-section" style="display: flex; flex-direction: column; gap: var(--space-3);">
		<h4 style="font-size: 0.8rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-soft); padding-bottom: 4px;">' . __('Location Details') . '</h4>
		<div class="db-form-group">
			<label for="Address1">' . __('Address Line 1') . '</label>
			<input type="text" name="Address1" placeholder="' . __('Street Address') . '" maxlength="40" />
		</div>
		<div class="db-form-group">
			<label for="Address2">' . __('Address Line 2') . '</label>
			<input type="text" name="Address2" placeholder="' . __('Building/Floor') . '" maxlength="40" />
		</div>
		<div class="db-form-group">
			<label for="Address3">' . __('City / Suburb') . '</label>
			<input type="text" name="Address3" placeholder="' . __('City Name') . '" maxlength="40" />
		</div>
		<div class="db-form-group">
			<label for="Address4">' . __('State / Region') . '</label>
			<input type="text" name="Address4" placeholder="' . __('Province/Region') . '" maxlength="50" />
		</div>
		<div class="db-form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3);">
			<div class="db-form-group">
				<label for="Address5">' . __('Post Code') . '</label>
				<input type="text" name="Address5" placeholder="' . __('Postal Code') . '" maxlength="20" />
			</div>
			<div class="db-form-group">
				<label for="Address6">' . __('Country') . '</label>
				<select name="Address6">';
	foreach ($CountriesArray as $CountryEntry => $CountryName) {
		echo '<option value="' . $CountryName . '">' . $CountryName . '</option>';
	}
	echo '</select>
			</div>
		</div>
	</div>

	<!-- Contact Details -->
	<div class="db-form-section" style="display: flex; flex-direction: column; gap: var(--space-3);">
		<h4 style="font-size: 0.8rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-soft); padding-bottom: 4px;">' . __('Communication') . '</h4>
		<div class="db-form-group">
			<label for="Phone">' . __('Telephone') . '</label>
			<input type="tel" pattern="[\s\d+)(-]{1,40}" name="Phone" placeholder="+255..." maxlength="40" />
		</div>
		<div class="db-form-group">
			<label for="Fax">' . __('Facsimile') . '</label>
			<input type="tel" pattern="[\s\d+)(-]{1,40}" name="Fax" maxlength="40" />
		</div>
		<div class="db-form-group">
			<label for="Email">' . __('Email Address') . '</label>
			<input type="email" name="Email" placeholder="info@supplier.com" maxlength="50" />
		</div>
		<div class="db-form-group">
			<label for="URL">' . __('Website URL') . '</label>
			<input type="url" name="URL" placeholder="https://..." maxlength="50" />
		</div>
	</div>

	<!-- Financials -->
	<div class="db-form-section" style="display: flex; flex-direction: column; gap: var(--space-3);">
		<h4 style="font-size: 0.8rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-soft); padding-bottom: 4px;">' . __('Financial Settings') . '</h4>
		<div class="db-form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3);">
			<div class="db-form-group">
				<label for="BankPartics">' . __('Bank Initials') . '</label>
				<input type="text" name="BankPartics" maxlength="12" />
			</div>
			<div class="db-form-group">
				<label for="BankRef">' . __('Bank Ref') . '</label>
				<input type="text" name="BankRef" value="0" maxlength="12" />
			</div>
		</div>
		<div class="db-form-group">
			<label for="BankAct">' . __('Bank Account No') . '</label>
			<input type="text" name="BankAct" maxlength="30" />
		</div>
		<div class="db-form-group">
			<label for="TaxRef">' . __('Tax Reference') . '</label>
			<input type="text" name="TaxRef" maxlength="20" />
		</div>
	</div>';

	$ptResult = DB_query("SELECT terms, termsindicator FROM paymentterms");
	$fcResult = DB_query("SELECT id, coyname FROM factorcompanies");
	$smResult = DB_query("SELECT salesmancode, salesmanname FROM salesman");
	$cuResult = DB_query("SELECT currency, currabrev FROM currencies");
	$shResult = DB_query("SELECT shipper_id, shippername FROM shippers ORDER BY shippername");
	$glResult = DB_query("SELECT accountcode, accountname FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=1 ORDER BY chartmaster.accountcode");
	$txResult = DB_query("SELECT taxgroupid, taxgroupdescription FROM taxgroups");

	echo '<div class="db-form-section" style="display: flex; flex-direction: column; gap: var(--space-3);">
			<h4 style="font-size: 0.8rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-soft); padding-bottom: 4px;">' . __('Commercial Terms') . '</h4>
			<div class="db-form-group">
				<label for="PaymentTerms">' . __('Payment Terms') . '</label>
				<select name="PaymentTerms">';
	while ($MyRow = DB_fetch_array($ptResult)) {
		echo '<option value="' . $MyRow['termsindicator'] . '">' . $MyRow['terms'] . '</option>';
	}
	echo '</select>
			</div>
			<div class="db-form-group">
				<label for="CurrCode">' . __('Currency') . '</label>
				<select name="CurrCode">';
	while ($MyRow = DB_fetch_array($cuResult)) {
		echo '<option value="' . $MyRow['currabrev'] . '">' . $MyRow['currency'] . '</option>';
	}
	echo '</select>
			</div>
			<div class="db-form-group">
				<label for="FactorID">' . __('Factor Company') . '</label>
				<select name="FactorID">
					<option value="0">' . __('None') . '</option>';
	while ($MyRow = DB_fetch_array($fcResult)) {
		echo '<option value="' . $MyRow['id'] . '">' . $MyRow['coyname'] . '</option>';
	}
	echo '</select>
			</div>
		</div>

		<div class="db-form-section" style="display: flex; flex-direction: column; gap: var(--space-3);">
			<h4 style="font-size: 0.8rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-soft); padding-bottom: 4px;">' . __('Operational Defaults') . '</h4>
			<div class="db-form-group">
				<label for="SalesPersonID">' . __('Sales Person') . '</label>
				<select name="SalesPersonID">
					<option value="">' . __('None') . '</option>';
	while ($MyRow = DB_fetch_array($smResult)) {
		echo '<option value="' . $MyRow['salesmancode'] . '">' . $MyRow['salesmanname'] . '</option>';
	}
	echo '</select>
			</div>
			<div class="db-form-group">
				<label for="DefaultShipper">' . __('Default Shipper') . '</label>
				<select required="required" name="DefaultShipper">';
	while ($MyRow = DB_fetch_array($shResult)) {
		echo '<option value="' . $MyRow['shipper_id'] . '">' . $MyRow['shippername'] . '</option>';
	}
	echo '</select>
			</div>
			<div class="db-form-group">
				<label for="DefaultGL">' . __('Default GL Account') . '</label>
				<select name="DefaultGL">
					<option value="0">' . __('None') . '</option>';
	while ($MyRow = DB_fetch_array($glResult)) {
		echo '<option value="' . $MyRow['accountcode'] . '">' . htmlspecialchars($MyRow['accountname'], ENT_QUOTES, 'UTF-8') . ' (' . $MyRow['accountcode'] . ')</option>';
	}
	echo '</select>
			</div>
			<div class="db-form-group">
				<label for="TaxGroup">' . __('Tax Group') . '</label>
				<select name="TaxGroup">';
	while ($MyRow = DB_fetch_array($txResult)) {
		echo '<option value="' . $MyRow['taxgroupid'] . '">' . $MyRow['taxgroupdescription'] . '</option>';
	}
	echo '</select>
			</div>
			<div class="db-form-group">
				<label for="Remittance">' . __('Remittance Advice') . '</label>
				<select name="Remittance">
					<option value="0">' . __('Not Required') . '</option>
					<option value="1">' . __('Required') . '</option>
				</select>
			</div>
		</div>
	</div> <!-- end db-form-grid -->
	
	<div class="db-card-footer" style="padding: var(--space-4); background: var(--surface-alt); border-top: 1px solid var(--border-soft); display: flex; justify-content: flex-end;">
		<input type="submit" name="submit" value="' . __('Create Supplier Account') . '" class="db-btn db-btn-primary" />
	</div>
</div> <!-- end db-card -->
</form>';
} else {

	//SupplierID exists - either passed when calling the form or from the form itself
	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . '">
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (!isset($_POST['New'])) {
		$SQL = "SELECT supplierid,
				suppname,
				address1,
				address2,
				address3,
				address4,
				address5,
				address6,
				telephone,
				fax,
				email,
				url,
				supptype,
				currcode,
				suppliersince,
				paymentterms,
				bankpartics,
				bankref,
				bankact,
				remittance,
				taxgroupid,
				factorcompanyid,
				salespersonid,
				taxref,
				defaultshipper,
				defaultgl
			FROM suppliers
			WHERE supplierid = '" . $SupplierID . "'";

		$Result = DB_query($SQL);
		$MyRow = DB_fetch_array($Result);

		$_POST['SuppName'] = stripcslashes($MyRow['suppname']);
		$_POST['Address1'] = stripcslashes($MyRow['address1']);
		$_POST['Address2'] = stripcslashes($MyRow['address2']);
		$_POST['Address3'] = stripcslashes($MyRow['address3']);
		$_POST['Address4'] = stripcslashes($MyRow['address4']);
		$_POST['Address5'] = stripcslashes($MyRow['address5']);
		$_POST['Address6'] = stripcslashes($MyRow['address6']);
		$_POST['CurrCode'] = stripcslashes($MyRow['currcode']);
		$_POST['Phone'] = $MyRow['telephone'];
		$_POST['Fax'] = $MyRow['fax'];
		$_POST['Email'] = $MyRow['email'];
		$_POST['URL'] = $MyRow['url'];
		$_POST['SupplierType'] = $MyRow['supptype'];
		$_POST['SupplierSince'] = ConvertSQLDate($MyRow['suppliersince']);
		$_POST['PaymentTerms'] = $MyRow['paymentterms'];
		$_POST['BankPartics'] = stripcslashes($MyRow['bankpartics']);
		$_POST['Remittance'] = $MyRow['remittance'];
		$_POST['BankRef'] = stripcslashes($MyRow['bankref']);
		$_POST['BankAct'] = $MyRow['bankact'];
		$_POST['TaxGroup'] = $MyRow['taxgroupid'];
		$_POST['FactorID'] = $MyRow['factorcompanyid'];
		$_POST['SalesPersonID'] = $MyRow['salespersonid'];
		$_POST['TaxRef'] = $MyRow['taxref'];
		$_POST['DefaultGL'] = $MyRow['defaultgl'];
		$_POST['DefaultShipper'] = $MyRow['defaultshipper'];

		echo '<input type="hidden" name="SupplierID" value="' . $SupplierID . '" />';
	} else {
		echo '<input type="hidden" name="New" value="Yes" />';
	}

	echo '<div class="db-card">
			<div class="db-card-header">
				<h3 class="db-card-title">' . (isset($_POST['New']) ? __('Confirm New Supplier Details') : __('General Information')) . '</h3>
			</div>
			<div class="db-form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-4); padding: var(--space-4);">
				
				<!-- Primary Details -->
				<div class="db-form-section" style="display: flex; flex-direction: column; gap: var(--space-3);">
					<h4 style="font-size: 0.8rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-soft); padding-bottom: 4px;">' . __('Identity & Basic Info') . '</h4>
					<div class="db-form-group">
						<label>' . __('Supplier Code') . '</label>
						<div style="padding: 8px var(--space-3); background: var(--surface-alt); border-radius: var(--radius-sm); border: 1px solid var(--border-soft); font-weight: 700; color: var(--text-main);">' . $SupplierID . '</div>';
						if (isset($_POST['New']) && $_SESSION['AutoSupplierNo'] == 0) {
							echo '<input type="hidden" name="SupplierID" value="' . $SupplierID . '" />';
						}
	echo '			</div>
					<div class="db-form-group">
						<label for="SuppName">' . __('Supplier Name') . '</label>
						<input ' . (in_array('Name', $Errors) ? 'class="inputerror"' : '') . ' type="text" name="SuppName" value="' . $_POST['SuppName'] . '" maxlength="40" required />
					</div>
					<div class="db-form-group">
						<label for="SupplierType">' . __('Supplier Type') . '</label>
						<select name="SupplierType">';
	$stResult = DB_query("SELECT typeid, typename FROM suppliertype");
	while ($stRow = DB_fetch_array($stResult)) {
		$selected = ($_POST['SupplierType'] == $stRow['typeid']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $stRow['typeid'] . '">' . $stRow['typename'] . '</option>';
	}
	echo '				</select>
					</div>
					<div class="db-form-group">
						<label for="SupplierSince">' . __('Supplier Since') . '</label>
						<input type="date" name="SupplierSince" value="' . FormatDateForSQL($_POST['SupplierSince']) . '" />
					</div>
				</div>

				<!-- Address Information -->
				<div class="db-form-section" style="display: flex; flex-direction: column; gap: var(--space-3);">
					<h4 style="font-size: 0.8rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-soft); padding-bottom: 4px;">' . __('Location Details') . '</h4>
					<div class="db-form-group">
						<label for="Address1">' . __('Address Line 1') . '</label>
						<input type="text" name="Address1" value="' . $_POST['Address1'] . '" maxlength="40" />
					</div>
					<div class="db-form-group">
						<label for="Address2">' . __('Address Line 2') . '</label>
						<input type="text" name="Address2" value="' . $_POST['Address2'] . '" maxlength="40" />
					</div>
					<div class="db-form-group">
						<label for="Address3">' . __('City / Suburb') . '</label>
						<input type="text" name="Address3" value="' . $_POST['Address3'] . '" maxlength="40" />
					</div>
					<div class="db-form-group">
						<label for="Address4">' . __('State / Region') . '</label>
						<input type="text" name="Address4" value="' . $_POST['Address4'] . '" maxlength="40" />
					</div>
					<div class="db-form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3);">
						<div class="db-form-group">
							<label for="Address5">' . __('Post Code') . '</label>
							<input type="text" name="Address5" value="' . $_POST['Address5'] . '" maxlength="40" />
						</div>
						<div class="db-form-group">
							<label for="Address6">' . __('Country') . '</label>
							<select name="Address6">';
	foreach ($CountriesArray as $CountryEntry => $CountryName) {
		$selected = (isset($_POST['Address6']) && $_POST['Address6'] == $CountryName) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $CountryName . '">' . $CountryName . '</option>';
	}
	echo '				</select>
						</div>
					</div>
				</div>

				<!-- Contact Details -->
				<div class="db-form-section" style="display: flex; flex-direction: column; gap: var(--space-3);">
					<h4 style="font-size: 0.8rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-soft); padding-bottom: 4px;">' . __('Communication') . '</h4>
					<div class="db-form-group">
						<label for="Phone">' . __('Telephone') . '</label>
						<input type="tel" pattern="[\s\d+)(-]{1,40}" name="Phone" value="' . $_POST['Phone'] . '" maxlength="40" />
					</div>
					<div class="db-form-group">
						<label for="Fax">' . __('Facsimile') . '</label>
						<input type="tel" pattern="[\s\d+)(-]{1,40}" name="Fax" value="' . $_POST['Fax'] . '" maxlength="40" />
					</div>
					<div class="db-form-group">
						<label for="Email">' . __('Email Address') . '</label>
						<input type="email" name="Email" value="' . $_POST['Email'] . '" maxlength="40" />
					</div>
					<div class="db-form-group">
						<label for="URL">' . __('Website URL') . '</label>
						<input type="url" name="URL" value="' . $_POST['URL'] . '" maxlength="40" />
					</div>
				</div>

				<!-- Financials -->
				<div class="db-form-section" style="display: flex; flex-direction: column; gap: var(--space-3);">
					<h4 style="font-size: 0.8rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-soft); padding-bottom: 4px;">' . __('Financial Settings') . '</h4>
					<div class="db-form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-3);">
						<div class="db-form-group">
							<label for="BankPartics">' . __('Bank Initials') . '</label>
							<input type="text" name="BankPartics" value="' . $_POST['BankPartics'] . '" maxlength="12" />
						</div>
						<div class="db-form-group">
							<label for="BankRef">' . __('Bank Ref') . '</label>
							<input ' . (in_array('BankRef', $Errors) ? 'class="inputerror"' : '') . ' type="text" name="BankRef" value="' . $_POST['BankRef'] . '" maxlength="12" />
						</div>
					</div>
					<div class="db-form-group">
						<label for="BankAct">' . __('Bank Account No') . '</label>
						<input type="text" name="BankAct" value="' . $_POST['BankAct'] . '" maxlength="30" />
					</div>
					<div class="db-form-group">
						<label for="TaxRef">' . __('Tax Reference') . '</label>
						<input type="text" name="TaxRef" value="' . $_POST['TaxRef'] . '" maxlength="20" />
					</div>
				</div>';

	$ptResult = DB_query("SELECT terms, termsindicator FROM paymentterms");
	$fcResult = DB_query("SELECT id, coyname FROM factorcompanies");
	$smResult = DB_query("SELECT salesmancode, salesmanname FROM salesman");
	$cuResult = DB_query("SELECT currency, currabrev FROM currencies");
	$shResult = DB_query("SELECT shipper_id, shippername FROM shippers ORDER BY shippername");
	$glResult = DB_query("SELECT accountcode, accountname FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_=accountgroups.groupname WHERE accountgroups.pandl=1 ORDER BY chartmaster.accountcode");
	$txResult = DB_query("SELECT taxgroupid, taxgroupdescription FROM taxgroups");

	echo '<div class="db-form-section" style="display: flex; flex-direction: column; gap: var(--space-3);">
			<h4 style="font-size: 0.8rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-soft); padding-bottom: 4px;">' . __('Commercial Terms') . '</h4>
			<div class="db-form-group">
				<label for="PaymentTerms">' . __('Payment Terms') . '</label>
				<select name="PaymentTerms">';
	while ($MyRow = DB_fetch_array($ptResult)) {
		$selected = ($_POST['PaymentTerms'] == $MyRow['termsindicator']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['termsindicator'] . '">' . $MyRow['terms'] . '</option>';
	}
	echo '		</select>
			</div>
			<div class="db-form-group">
				<label for="CurrCode">' . __('Currency') . '</label>
				<select name="CurrCode">';
	while ($MyRow = DB_fetch_array($cuResult)) {
		$selected = ($_POST['CurrCode'] == $MyRow['currabrev']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['currabrev'] . '">' . $MyRow['currency'] . '</option>';
	}
	echo '		</select>
			</div>
			<div class="db-form-group">
				<label for="FactorID">' . __('Factor Company') . '</label>
				<select name="FactorID">
					<option value="0">' . __('None') . '</option>';
	while ($MyRow = DB_fetch_array($fcResult)) {
		$selected = ($_POST['FactorID'] == $MyRow['id']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['id'] . '">' . $MyRow['coyname'] . '</option>';
	}
	echo '		</select>
			</div>
		</div>

		<div class="db-form-section" style="display: flex; flex-direction: column; gap: var(--space-3);">
			<h4 style="font-size: 0.8rem; color: var(--primary); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-soft); padding-bottom: 4px;">' . __('Operational Defaults') . '</h4>
			<div class="db-form-group">
				<label for="SalesPersonID">' . __('Sales Person') . '</label>
				<select name="SalesPersonID">
					<option value="">' . __('None') . '</option>';
	while ($MyRow = DB_fetch_array($smResult)) {
		$selected = ($_POST['SalesPersonID'] == $MyRow['salesmancode']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['salesmancode'] . '">' . $MyRow['salesmanname'] . '</option>';
	}
	echo '		</select>
			</div>
			<div class="db-form-group">
				<label for="DefaultShipper">' . __('Default Shipper') . '</label>
				<select required="required" name="DefaultShipper">';
	while ($MyRow = DB_fetch_array($shResult)) {
		$selected = ($_POST['DefaultShipper'] == $MyRow['shipper_id']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['shipper_id'] . '">' . $MyRow['shippername'] . '</option>';
	}
	echo '		</select>
			</div>
			<div class="db-form-group">
				<label for="DefaultGL">' . __('Default GL Account') . '</label>
				<select name="DefaultGL">';
	while ($MyRow = DB_fetch_row($glResult)) {
		$selected = ($_POST['DefaultGL'] == $MyRow[0]) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow[0] . '">' . htmlspecialchars($MyRow[1], ENT_QUOTES, 'UTF-8') . ' (' . $MyRow[0] . ')</option>';
	}
	echo '		</select>
			</div>
			<div class="db-form-group">
				<label for="TaxGroup">' . __('Tax Group') . '</label>
				<select name="TaxGroup">';
	while ($MyRow = DB_fetch_array($txResult)) {
		$selected = ($MyRow['taxgroupid'] == $_POST['TaxGroup']) ? 'selected="selected"' : '';
		echo '<option ' . $selected . ' value="' . $MyRow['taxgroupid'] . '">' . $MyRow['taxgroupdescription'] . '</option>';
	}
	echo '		</select>
			</div>
			<div class="db-form-group">
				<label for="Remittance">' . __('Remittance Advice') . '</label>
				<select name="Remittance">
					<option ' . ($_POST['Remittance'] == 0 ? 'selected' : '') . ' value="0">' . __('Not Required') . '</option>
					<option ' . ($_POST['Remittance'] == 1 ? 'selected' : '') . ' value="1">' . __('Required') . '</option>
				</select>
			</div>
		</div>
	</div> <!-- end db-form-grid -->
	
	<div class="db-card-footer" style="padding: var(--space-4); background: var(--surface-alt); border-top: 1px solid var(--border-soft); display: flex; justify-content: space-between; align-items: center;">
		<div>';
		if (!isset($_POST['New'])) {
			echo '<input type="submit" name="delete" value="' . __('Delete Supplier') . '" class="db-btn db-btn-danger" style="background: var(--danger); color: white;" onclick="return confirm(\'' . __('Are you sure you wish to delete this supplier?') . '\');" />';
		}
	echo '</div>
		<input type="submit" name="submit" value="' . (isset($_POST['New']) ? __('Add These New Supplier Details') : __('Update Supplier')) . '" class="db-btn db-btn-primary" />
	</div>
</div> <!-- end db-card -->
</form>
</div><!-- end db-page -->';
} // end of main ifs
include ('includes/footer.php');

