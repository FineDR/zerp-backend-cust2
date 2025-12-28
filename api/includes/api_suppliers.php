<?php

if (!isset($PathPrefix)) {
	header('Location: ../../');
	exit();
}

function ConvertToSupplierSQLDate($DateEntry) {

//for MySQL dates are in the format YYYY-mm-dd

	if (mb_strpos($DateEntry,'/')) {
		$Date_Array = explode('/',$DateEntry);
	} elseif (mb_strpos ($DateEntry,'-')) {
		$Date_Array = explode('-',$DateEntry);
	} elseif (mb_strpos ($DateEntry,'.')) {
		$Date_Array = explode('.',$DateEntry);
	}

	if (mb_strlen($Date_Array[2])>4) {  /*chop off the time stuff */
		$Date_Array[2]= mb_substr($Date_Array[2],0,2);
	}


	if ($_SESSION['DefaultDateFormat']=='d/m/Y'){
		return $Date_Array[2].'-0'.$Date_Array[1].'-'.$Date_Array[0];
	} elseif ($_SESSION['DefaultDateFormat']=='m/d/Y'){
		return $Date_Array[1].'/'.$Date_Array[2].'/'.$Date_Array[0];
	} elseif ($_SESSION['DefaultDateFormat']=='Y/m/d'){
		return $Date_Array[0].'/'.$Date_Array[1].'/'.$Date_Array[2];
	} elseif ($_SESSION['DefaultDateFormat']=='d.m.Y'){
		return $Date_Array[2].'/'.$Date_Array[1].'/'.$Date_Array[0];
	}

} // end function ConvertToSupplierSQLDate

/** Calculate the supplier taxes */
function GetSupplierTaxes($TaxGroup, $DispatchTaxProvince, $TaxCategory) {

	$SQL = "SELECT taxgrouptaxes.calculationorder,
					taxauthorities.description,
					taxgrouptaxes.taxauthid,
					taxauthorities.taxglcode,
					taxgrouptaxes.taxontax,
					taxauthrates.taxrate
			FROM taxauthrates
			INNER JOIN taxgrouptaxes
				ON taxauthrates.taxauthority=taxgrouptaxes.taxauthid
			INNER JOIN taxauthorities
				ON taxauthrates.taxauthority=taxauthorities.taxid
			WHERE taxgrouptaxes.taxgroupid='" . $TaxGroup . "'
				AND taxauthrates.dispatchtaxprovince='" . $DispatchTaxProvince . "'
				AND taxauthrates.taxcatid = '" . $TaxCategory . "'
			ORDER BY taxgrouptaxes.calculationorder";

	$ErrMsg = __('The taxes and rate for this tax group could not be retrieved because');
	$GetTaxesResult = DB_query($SQL, $ErrMsg);

	if (DB_num_rows($GetTaxesResult) >= 1) {
		return $GetTaxesResult;
	} else {
		/*The tax group is not defined with rates */
		return 0;
	}
}

/** Verify that the supplier number is valid, and doesn't already
   exist. */
function VerifySupplierNo($SupplierNumber, $i, $Errors) {
	if ((mb_strlen($SupplierNumber)<1) or (mb_strlen($SupplierNumber)>20)) {
		$Errors[$i] = IncorrectDebtorNumberLength;
	}
	$Searchsql = "SELECT count(supplierid)
				  FROM suppliers
				  WHERE supplierid='".$SupplierNumber."'";
	$SearchResult = DB_query($Searchsql);
	$Answer = DB_fetch_row($SearchResult);
	if ($Answer[0] != 0) {
		$Errors[$i] = SupplierNoAlreadyExists;
	}
	return $Errors;
}

/** Verify that the supplier number is valid, and already
   exists. */
function VerifySupplierNoExists($SupplierNumber, $i, $Errors) {
	if ((mb_strlen($SupplierNumber)<1) or (mb_strlen($SupplierNumber)>20)) {
		$Errors[$i] = IncorrectDebtorNumberLength;
	}
	$Searchsql = "SELECT count(supplierid)
				  FROM suppliers
				  WHERE supplierid='".$SupplierNumber."'";
	$SearchResult = DB_query($Searchsql);
	$Answer = DB_fetch_row($SearchResult);
	if ($Answer[0] == 0) {
		$Errors[$i] = SupplierNoDoesntExists;
	}
	return $Errors;
}

/** Check that the name exists and is 40 characters or less long */
function VerifySupplierName($SupplierName, $i, $Errors) {
	if ((mb_strlen($SupplierName)<1) or (mb_strlen($SupplierName)>40)) {
		$Errors[$i] = IncorrectSupplierNameLength;
	}
	return $Errors;
}

/** Check that the supplier since date is a valid date. The date
 * must be in the same format as the date format specified in the
 * target webERP company */
function VerifySupplierSinceDate($suppliersincedate, $i, $Errors) {
	$SQL="SELECT confvalue FROM config where confname='DefaultDateFormat'";
	$Result = DB_query($SQL);
	$MyRow=DB_fetch_array($Result);
	$DateFormat=$MyRow[0];
	if (mb_strstr('/',$PeriodEnd)) {
		$Date_Array = explode('/',$PeriodEnd);
	} elseif (mb_strstr('.',$PeriodEnd)) {
		$Date_Array = explode('.',$PeriodEnd);
	}
	if ($DateFormat=='d/m/Y') {
		$Day=$DateArray[0];
		$Month=$DateArray[1];
		$Year=$DateArray[2];
	} elseif ($DateFormat=='m/d/Y') {
		$Day=$DateArray[1];
		$Month=$DateArray[0];
		$Year=$DateArray[2];
	} elseif ($DateFormat=='Y/m/d') {
		$Day=$DateArray[2];
		$Month=$DateArray[1];
		$Year=$DateArray[0];
	} elseif ($DateFormat=='d.m.Y') {
		$Day=$DateArray[0];
		$Month=$DateArray[1];
		$Year=$DateArray[2];
	}
	if (!checkdate(intval($Month), intval($Day), intval($Year))) {
		$Errors[$i] = InvalidSupplierSinceDate;
	}
	return $Errors;
}

/** Check that the transaction date and Delivery date are  valid dates. The date
 * must be in the same format as the date format specified in the
 * target webERP company */
function VerifyDateFormat($suppliersincedate, $i, $Errors) {
	$SQL="SELECT confvalue FROM config where confname='DefaultDateFormat'";
	$Result = DB_query($SQL);
	$MyRow=DB_fetch_array($Result);
	$DateFormat=$MyRow[0];
	if (mb_strstr('/',$PeriodEnd)) {
		$Date_Array = explode('/',$PeriodEnd);
	} elseif (mb_strstr('.',$PeriodEnd)) {
		$Date_Array = explode('.',$PeriodEnd);
	}
	if ($DateFormat=='d/m/Y') {
		$Day=$DateArray[0];
		$Month=$DateArray[1];
		$Year=$DateArray[2];
	} elseif ($DateFormat=='m/d/Y') {
		$Day=$DateArray[1];
		$Month=$DateArray[0];
		$Year=$DateArray[2];
	} elseif ($DateFormat=='Y/m/d') {
		$Day=$DateArray[2];
		$Month=$DateArray[1];
		$Year=$DateArray[0];
	} elseif ($DateFormat=='d.m.Y') {
		$Day=$DateArray[0];
		$Month=$DateArray[1];
		$Year=$DateArray[2];
	}
	if (!checkdate(intval($Month), intval($Day), intval($Year))) {
		$Errors[$i] = InvalidSupplierSinceDate;
	}
	return $Errors;
}

function VerifyBankAccount($BankAccount, $i, $Errors) {
	if (mb_strlen($BankAccount)>30) {
		$Errors[$i] = InvalidBankAccount;
	}
	return $Errors;
}

function VerifyBankRef($BankRef, $i, $Errors) {
	if (mb_strlen($BankRef)>12) {
		$Errors[$i] = InvalidBankReference;
	}
	return $Errors;
}

function VerifyBankPartics($BankPartics, $i, $Errors) {
	if (mb_strlen($BankPartics)>12) {
		$Errors[$i] = InvalidBankPartics;
	}
	return $Errors;
}

function VerifyRemittance($Remittance, $i, $Errors) {
	if ($Remittance!=0 and $Remittance!=1) {
		$Errors[$i] = InvalidRemittanceFlag;
	}
	return $Errors;
}

/** Check that the factor company is set up in the weberp database */
function VerifyFactorCompany($factorco , $i, $Errors) {
	$Searchsql = "SELECT COUNT(id)
				 FROM factorcompanies
				  WHERE id='".$factorco."'";
	$SearchResult = DB_query($Searchsql);
	$Answer = DB_fetch_row($SearchResult);
	if ($Answer[0] == 0) {
		$Errors[$i] = FactorCompanyNotSetup;
	}
	return $Errors;
}


/* Common SQL Functions */
/*
function GetNextTransNo($TransType) {

	/* SQL to get the next transaction number these are maintained in the table SysTypes - Transaction Types
	Also updates the transaction number

	10 sales invoice
	11 sales credit note
	12 sales receipt
	etc
	api_DB_query("SELECT typeno FROM systypes WHERE typeid='" . $TransType . "' FOR UPDATE");
	$SQL = "UPDATE systypes SET typeno = typeno + 1 WHERE typeid = '" . $TransType . "'";
	//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': '
	//	. __('The transaction number could not be incremented');
	api_DB_query($SQL);
	$SQL = "SELECT typeno FROM systypes WHERE typeid= '" . $TransType . "'";
//	$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': <BR>'
//		. __('The next transaction number could not be retrieved from the database because');
	$GetTransNoResult = api_DB_query($SQL);
	$MyRow = DB_fetch_array($GetTransNoResult);
	return $MyRow[0];
}
*/
/** Insert a new supplier in the webERP database. This function takes an
   associative array called $SupplierDetails, where the keys are the
   names of the fields in the suppliers table, and the values are the
   values to insert.
*/
function InsertSupplier($SupplierDetails, $user, $password) {
	$Errors = array();
	$db = db($user, $password);
	if (gettype($db)=='integer') {
		$Errors[0]=NoAuthorisation;
		return $Errors;
	}
	foreach ($SupplierDetails as $key => $Value) {
		$SupplierDetails[$key] = DB_escape_string($Value);
	}
	$Errors=VerifySupplierNo($SupplierDetails['supplierid'], sizeof($Errors), $Errors);
	$Errors=VerifySupplierName($SupplierDetails['suppname'], sizeof($Errors), $Errors);
	if (isset($SupplierDetails['address1'])){
		$Errors=VerifyAddressLine($SupplierDetails['address1'], 40, sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['address2'])){
		$Errors=VerifyAddressLine($SupplierDetails['address2'], 40, sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['address3'])){
		$Errors=VerifyAddressLine($SupplierDetails['address3'], 40, sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['address4'])){
		$Errors=VerifyAddressLine($SupplierDetails['address4'], 50, sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['address5'])){
		$Errors=VerifyAddressLine($SupplierDetails['address5'], 20, sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['address6'])){
		$Errors=VerifyAddressLine($SupplierDetails['address6'], 15, sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['lat'])){
		$Errors=VerifyLatitude($SupplierDetails['lat'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['lng'])){
		$Errors=VerifyLongitude($SupplierDetails['lng'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['currcode'])){
		$Errors=VerifyCurrencyCode($SupplierDetails['currcode'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['suppliersince'])){
		$Errors=VerifySupplierSinceDate($SupplierDetails['suppliersince'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['paymentterms'])){
		$Errors=VerifyPaymentTerms($SupplierDetails['paymentterms'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['lastpaid'])){
		$Errors=VerifyLastPaid($SupplierDetails['lastpaid'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['lastpaiddate'])){
		$Errors=VerifyLastPaidDate($SupplierDetails['lastpaiddate'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['bankact'])){
		$Errors=VerifyBankAccount($SupplierDetails['bankact'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['bankref'])){
		$Errors=VerifyBankRef($SupplierDetails['bankref'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['bankpartics'])){
		$Errors=VerifyBankPartics($SupplierDetails['bankpartics'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['remittance'])){
		$Errors=VerifyRemittance($SupplierDetails['remittance'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['taxgroupid'])){
		$Errors=VerifyTaxGroupId($SupplierDetails['taxgroupid'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['factorcompanyid'])){
		$Errors=VerifyFactorCompany($SupplierDetails['factorcompanyid'], sizeof($Errors), $Errors);
	}
	if (isset($CustomerDetails['taxref'])){
		$Errors=VerifyTaxRef($CustomerDetails['taxref'], sizeof($Errors), $Errors);
	}
	$FieldNames='';
	$FieldValues='';
	foreach ($SupplierDetails as $key => $Value) {
		$FieldNames.=$key.', ';
		$FieldValues.='"'.$Value.'", ';
	}
	$SQL = 'INSERT INTO suppliers ('.mb_substr($FieldNames,0,-2).') '.
		'VALUES ('.mb_substr($FieldValues,0,-2).') ';
	if (sizeof($Errors)==0) {
		$Result = DB_query($SQL);
		if (DB_error_no() != 0) {
			$Errors[0] = DatabaseUpdateFailed;
		} else {
			$Errors[0]=0;
		}
	}
	return $Errors;
}

function ModifySupplier($SupplierDetails, $user, $password) {
	$Errors = array();
	$db = db($user, $password);
	if (gettype($db)=='integer') {
		$Errors[0]=NoAuthorisation;
		return $Errors;
	}
	foreach ($SupplierDetails as $key => $Value) {
		$SupplierDetails[$key] = DB_escape_string($Value);
	}
	$Errors=VerifySupplierNoExists($SupplierDetails['supplierid'], sizeof($Errors), $Errors);
	$Errors=VerifySupplierName($SupplierDetails['suppname'], sizeof($Errors), $Errors);
	if (isset($SupplierDetails['address1'])){
		$Errors=VerifyAddressLine($SupplierDetails['address1'], 40, sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['address2'])){
		$Errors=VerifyAddressLine($SupplierDetails['address2'], 40, sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['address3'])){
		$Errors=VerifyAddressLine($SupplierDetails['address3'], 40, sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['address4'])){
		$Errors=VerifyAddressLine($SupplierDetails['address4'], 50, sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['address5'])){
		$Errors=VerifyAddressLine($SupplierDetails['address5'], 20, sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['address6'])){
		$Errors=VerifyAddressLine($SupplierDetails['address6'], 15, sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['lat'])){
		$Errors=VerifyLatitude($SupplierDetails['lat'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['lng'])){
		$Errors=VerifyLongitude($SupplierDetails['lng'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['currcode'])){
		$Errors=VerifyCurrencyCode($SupplierDetails['currcode'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['suppliersince'])){
		$Errors=VerifySupplierSinceDate($SupplierDetails['suppliersince'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['paymentterms'])){
		$Errors=VerifyPaymentTerms($SupplierDetails['paymentterms'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['lastpaid'])){
		$Errors=VerifyLastPaid($SupplierDetails['lastpaid'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['lastpaiddate'])){
		$Errors=VerifyLastPaidDate($SupplierDetails['lastpaiddate'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['bankact'])){
		$Errors=VerifyBankAccount($SupplierDetails['bankact'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['bankref'])){
		$Errors=VerifyBankRef($SupplierDetails['bankref'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['bankpartics'])){
		$Errors=VerifyBankPartics($SupplierDetails['bankpartics'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['remittance'])){
		$Errors=VerifyRemittance($SupplierDetails['remittance'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['taxgroupid'])){
		$Errors=VerifyTaxGroupId($SupplierDetails['taxgroupid'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierDetails['factorcompanyid'])){
		$Errors=VerifyFactorCompany($SupplierDetails['factorcompanyid'], sizeof($Errors), $Errors);
	}
	if (isset($CustomerDetails['taxref'])){
		$Errors=VerifyTaxRef($CustomerDetails['taxref'], sizeof($Errors), $Errors);
	}
	$SQL='UPDATE suppliers SET ';
	foreach ($SupplierDetails as $key => $Value) {
		$SQL .= $key.'="'.$Value.'", ';
	}
	$SQL = mb_substr($SQL,0,-2)." WHERE supplierid='".$SupplierDetails['supplierid']."'";
	if (sizeof($Errors)==0) {
		$Result = DB_query($SQL);
		echo DB_error_no();
		if (DB_error_no() != 0) {
			$Errors[0] = DatabaseUpdateFailed;
		} else {
			$Errors[0]=0;
		}
	}
	return $Errors;
}

/** This function takes a supplier id and returns an associative array containing
   the database record for that supplier. If the supplier id doesn't exist
   then it returns an $Errors array.
*/
function GetSupplier($SupplierID, $user, $password) {
	$Errors = array();
	$db = db($user, $password);
	if (gettype($db)=='integer') {
		$Errors[0]=NoAuthorisation;
		return $Errors;
	}
	$Errors = VerifySupplierNoExists($SupplierID, sizeof($Errors), $Errors);
	if (sizeof($Errors)!=0) {
		return $Errors;
	}
	$SQL="SELECT * FROM suppliers WHERE supplierid='".$SupplierID."'";
	$Result = DB_query($SQL);
	if (sizeof($Errors)==0) {
		return DB_fetch_array($Result);
	} else {
		return $Errors;
	}
}

/** This function takes a field name, and a string, and then returns an
   array of supplier ids that fulfill this criteria.
*/
function SearchSuppliers($Field, $Criteria, $user, $password) {
	$Errors = array();
	$db = db($user, $password);
	if (gettype($db)=='integer') {
		$Errors[0]=NoAuthorisation;
		return $Errors;
	}
	$SQL='SELECT supplierid
		FROM suppliers
		WHERE '.$Field." LIKE '%".$Criteria."%' ORDER BY supplierid";
	$Result = DB_query($SQL);
	$i=0;
	$SupplierList = array();
	while ($MyRow=DB_fetch_array($Result)) {
		$SupplierList[$i]=$MyRow[0];
		$i++;
	}
	return $SupplierList;
}

/** This function takes a supplier id and returns an associative array containing
   the database record for that supplier's Statement Inquiry (i.e. balance, due, overdue1, overdue2). If the supplier id doesn't exist
   then it returns an $Errors array.
*/
function GetSupplierInquiry($SupplierID, $user, $password) {
	$Errors = array();
	$db = db($user, $password);
	if (gettype($db)=='integer') {
		$Errors[0]=NoAuthorisation;
		return $Errors;
	}
	$Errors = VerifySupplierNoExists($SupplierID, sizeof($Errors), $Errors);
	if (sizeof($Errors)!=0) {
		return $Errors;
	}
	$SQL="SELECT suppliers.suppname,
		suppliers.currcode,
		currencies.currency,
		currencies.decimalplaces AS currdecimalplaces,
		paymentterms.terms,
		SUM(supptrans.ovamount + supptrans.ovgst - supptrans.alloc) AS balance,
		SUM(CASE WHEN paymentterms.daysbeforedue > 0 THEN
			CASE WHEN (TO_DAYS(Now()) - TO_DAYS(supptrans.trandate)) >= paymentterms.daysbeforedue
			THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
		ELSE
			CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= 0 THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
		END) AS due,
		SUM(CASE WHEN paymentterms.daysbeforedue > 0  THEN
			CASE WHEN (TO_DAYS(Now()) - TO_DAYS(supptrans.trandate)) > paymentterms.daysbeforedue
					AND (TO_DAYS(Now()) - TO_DAYS(supptrans.trandate)) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays1'] . ")
			THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
		ELSE
			CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= '" . $_SESSION['PastDueDays1'] . "'
			THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
		END) AS overdue1,
		Sum(CASE WHEN paymentterms.daysbeforedue > 0 THEN
			CASE WHEN TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) > paymentterms.daysbeforedue AND TO_DAYS(Now()) - TO_DAYS(supptrans.trandate) >= (paymentterms.daysbeforedue + " . $_SESSION['PastDueDays2'] . ")
			THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
		ELSE
			CASE WHEN TO_DAYS(Now()) - TO_DAYS(ADDDATE(last_day(supptrans.trandate),paymentterms.dayinfollowingmonth)) >= '" . $_SESSION['PastDueDays2'] . "'
			THEN supptrans.ovamount + supptrans.ovgst - supptrans.alloc ELSE 0 END
		END ) AS overdue2
		FROM suppliers INNER JOIN paymentterms
		ON suppliers.paymentterms = paymentterms.termsindicator
     	INNER JOIN currencies
     	ON suppliers.currcode = currencies.currabrev
     	INNER JOIN supptrans
     	ON suppliers.supplierid = supptrans.supplierno
		WHERE suppliers.supplierid = '" . $SupplierID . "'
		GROUP BY suppliers.suppname,
      			currencies.currency,
      			currencies.decimalplaces,
      			paymentterms.terms,
      			paymentterms.daysbeforedue,
      			paymentterms.dayinfollowingmonth
	";
	$SupplierResult = DB_query($SQL);

	if(DB_num_rows($SupplierResult) == 0) {

		/*Because there is no balance - so just retrieve the header information about the Supplier - the choice is do one query to get the balance and transactions for those Suppliers who have a balance and two queries for those who don't have a balance OR always do two queries - I opted for the former */

		$NIL_BALANCE = true;

		$SQL = "SELECT suppliers.suppname,
						suppliers.currcode,
						currencies.currency,
						currencies.decimalplaces AS currdecimalplaces,
						paymentterms.terms
				FROM suppliers INNER JOIN paymentterms
				ON suppliers.paymentterms = paymentterms.termsindicator
				INNER JOIN currencies
				ON suppliers.currcode = currencies.currabrev
				WHERE suppliers.supplierid = '" . $SupplierID . "'";

	//	$ErrMsg = __('The supplier details could not be retrieved by the SQL because');
		$SupplierResult = DB_query($SQL, $ErrMsg);

	} else {
		$NIL_BALANCE = false;
	}

	$SupplierRecord = DB_fetch_array($SupplierResult);

	if($NIL_BALANCE == true) {

		$SupplierRecord['balance'] = 0;
		$SupplierRecord['due'] = 0;
		$SupplierRecord['overdue1'] = 0;
		$SupplierRecord['overdue2'] = 0;

		$Errors[0]=$SupplierRecord['balance']; 
		$Errors[1]=$SupplierRecord['due']; 
		$Errors[2]=$SupplierRecord['overdue1']; 
		$Errors[3]=$SupplierRecord['overdue2']; 

		// $Errors[0]=0; //balance
		// $Errors[1]=0; //due
		// $Errors[2]=0; //overdue1
		// $Errors[3]=0; //overdue2

	}else{
		if (sizeof($Errors)==0) {
			return DB_fetch_array($SupplierResult);
		} else {
			return $Errors;
		}
	}
}

/*
function Add_GLCodes_To_Trans($GLCode,
								$GLActName,
								$Amount,
								$Narrative,
								$Tag) {

	if ($Amount!=0 AND isset($Amount)){
		$this->GLCodes[$this->GLCodesCounter] = new GLCodes($this->GLCodesCounter,
															$GLCode,
															$GLActName,
															$Amount,
															$Narrative,
															$Tag);
		$this->GLCodesCounter++;
		Return 1;
	}
	Return 0;
}
*/

/** Create a Supplier invoice header in webERP. If successful
 * returns $Errors[0]=0 and $Errors[1] will contain the invoice number.
*/
//function InsertSupplierInvoiceHeader($SupplierHeader, $user, $password) {
function InsertSupplierInvoiceHeader($SupplierHeader, $SupplierInvoiceLine, $user, $password) {
	$Errors = array();
	$db = db($user, $password);

	if (gettype($db)=='integer') {
		$Errors[0]=NoAuthorisation;
		return $Errors;
	}

	foreach ($SupplierHeader as $key => $Value) {
		$SupplierHeader[$key] = DB_escape_string($Value);
	}

//$SupplierHeader['trandate'] = ConvertToSupplierSQLDate($SupplierHeader['trandate']);
//return $SupplierHeader['trandate'];
	$Errors=VerifySupplierNoExists($SupplierHeader['supplierno'], sizeof($Errors), $Errors);
	if (isset($SupplierHeader['trandate'])){
		$Errors=VerifyDateFormat($SupplierHeader['trandate'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierHeader['deliverydate'])){
		$Errors=VerifyDateFormat($SupplierHeader['deliverydate'], sizeof($Errors), $Errors);
	}
	/*Now retrieve supplier information - name, currency, default ex rate, terms, tax rate etc */
	$SQL = "SELECT suppliers.suppname,
					suppliers.supplierid,
					paymentterms.terms,
					paymentterms.daysbeforedue,
					paymentterms.dayinfollowingmonth,
					suppliers.currcode,
					currencies.rate AS exrate,
					currencies.decimalplaces,
					suppliers.taxgroupid,
					taxgroups.taxgroupdescription
				FROM suppliers,
					taxgroups,
					currencies,
					paymentterms,
					taxauthorities
				WHERE suppliers.taxgroupid=taxgroups.taxgroupid
				AND suppliers.currcode=currencies.currabrev
				AND suppliers.paymentterms=paymentterms.termsindicator
				AND suppliers.supplierid = '" . $SupplierHeader['supplierno']. "'";
	
	$Result = api_DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if (DB_num_rows($Result)==0){
		$Errors[0] = NoSupplierExist;
		return $Errors;
	}
	$suppname = $MyRow['suppname'];
	$terms = $MyRow['terms'];
	$currcode = $MyRow['currcode'];
	$exrate = $MyRow['exrate'];
	$decimalplaces = $MyRow['decimalplaces'];
	$taxgroupid = $MyRow['taxgroupid'];
	$taxgroupdescription = $MyRow['taxgroupdescription'];
	$InvoiceNo = $SupplierHeader['suppreference'];

	if ($MyRow['daysbeforedue'] == 0) {
		$terms = '1' . $MyRow['dayinfollowingmonth'];
	}
	else {
		$terms = '0' . $MyRow['daysbeforedue'];
	}
	$SupplierID = $SupplierHeader['supplierno'];
	
	//get user default location
	//tempo soln: hardcode user
	$SQL = "SELECT defaultlocation FROM www_users WHERE userid = 'amran'";
	//$SQL = "SELECT defaultlocation FROM www_users WHERE userid = '".$user."'";

	$Result = DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if($MyRow['defaultlocation']==''){
		$MyRow['defaultlocation'] = $SupplierHeader['userlocation'];
	}
    $loccode = $MyRow['defaultlocation'];
	$SQL = "SELECT taxprovinceid
			FROM locations
			WHERE loccode = '" . $MyRow['defaultlocation'] . "'";
    $LocalTaxProvinceResult = api_DB_query($SQL);
	if (DB_num_rows($LocalTaxProvinceResult)==0){
		$Errors[0] = UserTaxProvinceNotSet;
		return $Errors;
	}

	$LocalTaxProvinceRow = DB_fetch_row($LocalTaxProvinceResult);
	$LocalTaxProvince = $LocalTaxProvinceRow[0];
			
	//Hardcode TaxCategory
	$TaxCategory = 1;
	$GetTaxesResult = GetSupplierTaxes($taxgroupid, $LocalTaxProvince, $TaxCategory);
	//get company cofiguration data
	$SQL = "SELECT gllink_creditors, grnact, creditorsact FROM companies";

    $CompanyRecordResult = api_DB_query($SQL);
	if (DB_num_rows($CompanyRecordResult)==0){
		$Errors[0] = CompanyRecordNotSet;
		return $Errors;
	}
    $CompanyRecord = DB_fetch_row($CompanyRecordResult);
	//return 'line 676: '.$GetTaxesResult;
	$GLLink_Creditors = $CompanyRecord[0];
	$GRNAct = $CompanyRecord[1];
	$CreditorsAct = $CompanyRecord[2];
	$InvoiceOrCredit = 'Invoice';
	if ($SupplierHeader['invoicetype'] == 4) {

		/*Need to check that the user has permission to receive goods */
		//return 'line 692: '.$SupplierHeader['invoicetype'];

		//return in_array($_SESSION['PageSecurityArray']['GoodsReceived.php'], $_SESSION['AllowedPageSecurityTokens']);

        /* ======
		if (!in_array($_SESSION['PageSecurityArray']['GoodsReceived.php'], $_SESSION['AllowedPageSecurityTokens'])) {
			prnMsg(__('Your permissions do not allow receiving of goods. Automatic receiving of purchase orders is restricted to those only users who are authorised to receive goods/services') , 'error');
		}
		else {
			/* The user has permission to receive goods then lets go 

			$_GET['ModifyOrderNumber'] = intval($_GET['ReceivePO']);
			include('includes/PO_ReadInOrder.php');

			if ($_SESSION['PO' . $identifier]->Status == 'Authorised') {
        =========== */

				DB_Txn_Begin();
				/*Now Get the next GRN - function in SQL_CommonFunctions*/
				$GRN = GetNextTransNo(25);
				$DeliveryDate = $SupplierHeader['deliverydate'];
				$ExRate = $exrate;
				$TranDate = $SupplierHeader['trandate'];
				$PeriodNo = GetPeriod($DeliveryDate);

				$OrderHasControlledItems = false; //assume the best
				foreach ($SupplierInvoiceLine as $key => $Value) {
		           $SupplierInvoiceLine[$key] = DB_escape_string($Value);
				   $itemcode = $SupplierInvoiceLine['itemcode'];
				   $description = $SupplierInvoiceLine['description'];
				   $quantity = $SupplierInvoiceLine['quantity'];
				   $qtyreceived = $SupplierInvoiceLine['qtyreceived'];
				   $price = $SupplierInvoiceLine['price'];
				   $controlled = $SupplierInvoiceLine['controlled'];
				   $ReceivedQty = $quantity - $qtyreceived ;

				  // return $PeriodNo;
			       //	foreach ($_SESSION['PO' . $identifier]->LineItems as $OrderLine) {
					//Set the quantity to receive with this auto delivery assuming all is well
					/*
					$_SESSION['PO' . $identifier]->LineItems[$OrderLine
						->LineNo]->ReceiveQty = $OrderLine->Quantity - $OrderLine->QtyReceived;
                    */
					if ($controlled == 1) { // it's a controlled item - we can't deal with auto receiving controlled items!!!
						//prnMsg(__('Auto receiving of controlled stock items that require serial number or batch number entry is not currently catered for. Only orders with normal non-serial numbered items can be received automatically') , 'error');
						$OrderHasControlledItems = true;
					}
				}

				//if ($OrderHasControlledItems == false) {
				if ($OrderHasControlledItems == 1) {
					foreach ($SupplierInvoiceLine as $key => $Value) {
						$SupplierInvoiceLine[$key] = DB_escape_string($Value);
					    //foreach ($_SESSION['PO' . $identifier]->LineItems as $OrderLine) {
						$LocalCurrencyPrice = ($SupplierInvoiceLine['price'] / $exrate);
						//
						if ($SupplierInvoiceLine['itemcode'] != '') { //Its a stock item line
							/*Need to get the current standard cost as it is now so we can process GL jorunals later*/
							$SQL = "SELECT actualcost as stdcost
										FROM stockmaster
										WHERE stockid='" . $SupplierInvoiceLine['itemcode'] . "'";
							$Result = api_DB_query($SQL);
							//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The standard cost of the item being received cannot be retrieved because');
							//$Result = DB_query($SQL, $ErrMsg, '', true);
							$MyRow = DB_fetch_row($Result);
							$CurrentStandardCost = $MyRow[0];

							if ($qtyreceived == 0) { //its the first receipt against this line
								/*
								$_SESSION['PO' . $identifier]->LineItems[$OrderLine
									->LineNo]->StandardCost = $CurrentStandardCost;
									*/
									$StandardCost = $CurrentStandardCost;
							}

							/*Set the purchase order line stdcostunit = weighted average / standard cost used for all receipts of this line
							This assures that the quantity received against the purchase order line multiplied by the weighted average of standard
							costs received = the total of standard cost posted to GRN suspense*/
							/*
							$_SESSION['PO' . $identifier]->LineItems[$OrderLine
								->LineNo]->StandardCost = (($CurrentStandardCost * $OrderLine->ReceiveQty) + ($_SESSION['PO' . $identifier]->LineItems[$OrderLine
								->LineNo]->StandardCost * $OrderLine->QtyReceived)) / ($OrderLine->ReceiveQty + $OrderLine->QtyReceived);
								*/
								$StandardCost = (($CurrentStandardCost * $receivedqty) + ($StandardCost * $qtyreceived)) / ($receivedqty + $qtyreceived);

						}
						elseif ($qtyreceived == 0 AND $SupplierInvoiceLine['itemcode'] == '') {
							/*Its a nominal item being received */
							/*Need to record the value of the order per unit in the standard cost field to ensure GRN account entries clear */
							/* 
							$_SESSION['PO' . $identifier]->LineItems[$OrderLine
								->LineNo]->StandardCost = $LocalCurrencyPrice;
								*/
								$StandardCost = $LocalCurrencyPrice;
						}

						if ($SupplierInvoiceLine['itemcode']== '') { /*Its a NOMINAL item line */
							/*
							$CurrentStandardCost = $_SESSION['PO' . $identifier]->LineItems[$OrderLine
								->LineNo]->StandardCost;
								*/
								$CurrentStandardCost = $StandardCost;
						}

						/*Now the SQL to do the update to the PurchOrderDetails */

						$SQL = "UPDATE purchorderdetails SET quantityrecd = quantityrecd + '" . $receivedqty . "',
															stdcostunit='" . $StandardCost . "',
															completed='1'
													WHERE podetailitem = '" . $SupplierInvoiceLine['podetailrec'] . "'";
						
						//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The purchase order detail record could not be updated with the quantity received because');
						//$Result = DB_query($SQL, $ErrMsg, '', true);
						$Result = api_DB_query($SQL);

						if ($SupplierInvoiceLine['itemcode'] != '') { /*Its a stock item so use the standard cost for the journals */
							$UnitCost = $CurrentStandardCost;
						}
						else { /*otherwise its a nominal PO item so use the purchase cost converted to local currency */
							$UnitCost = $SupplierInvoiceLine['price'] / $exrate;
						}

						/*Need to insert a GRN item */

						$SQL = "INSERT INTO grns (grnbatch,
												podetailitem,
												itemcode,
												itemdescription,
												deliverydate,
												qtyrecd,
												supplierid,
												stdcostunit)
										VALUES ('" . $GRN . "',
											'" . $SupplierInvoiceLine['podetailrec'] . "',
											'" . $SupplierInvoiceLine['itemcode'] . "',
											'" . DB_escape_string($SupplierInvoiceLine['description']) . "',
											'" . FormatDateForSQL($DeliveryDate) . "',
											'" . $ReceivedQty . "',
											'" . $SupplierID . "',
											'" . $CurrentStandardCost . "')";

					    //	$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('A GRN record could not be inserted') . '. ' . __('This receipt of goods has not been processed because');
						//$Result = DB_query($SQL, $ErrMsg, '', true);
						$Result = api_DB_query($SQL);

						if ($SupplierInvoiceLine['itemcode']  != '') { /* if the order line is in fact a stock item */

							/* Update location stock records - NB  a PO cannot be entered for a dummy/assembly/kit parts */

							/* Need to get the current location quantity will need it later for the stock movement */
							$SQL = "SELECT locstock.quantity
											FROM locstock
											WHERE locstock.stockid='" . $SupplierInvoiceLine['itemcode']  . "'
											AND loccode= '" . $loccode . "'";

							$Result = api_DB_query($SQL);
							if (DB_num_rows($Result) == 1) {
								$LocQtyRow = DB_fetch_row($Result);
								$QtyOnHandPrior = $LocQtyRow[0];
							}
							else {
								/*There must actually be some error this should never happen */
								$QtyOnHandPrior = 0;
							}

							$SQL = "UPDATE locstock
										SET quantity = locstock.quantity + '" . $receivedqty . "'
									WHERE locstock.stockid = '" . $SupplierInvoiceLine['itemcode'] . "'
									AND loccode = '" . $loccode . "'";
                            /*
								$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The location stock record could not be updated because');
								$Result = DB_query($SQL, $ErrMsg, '', true);
							*/
							$Result = api_DB_query($SQL);

							/* Insert stock movements - with unit cost */

							$SQL = "INSERT INTO stockmoves (stockid,
															type,
															transno,
															loccode,
															trandate,
															userid,
															price,
															prd,
															reference,
															qty,
															standardcost,
															newqoh)
												VALUES (
													'" . $SupplierInvoiceLine['itemcode'] . "',
													25,
													'" . $GRN . "',
													'" . $loccode. "',
													'" . FormatDateForSQL($DeliveryDate) . "',
													'" . $_SESSION['UserID'] . "',
													'" . $LocalCurrencyPrice . "',
													'" . $PeriodNo . "',
													'" . $SupplierID . " (" . DB_escape_string($SupplierName) . ") - " . $_SESSION['PO' . $identifier]->OrderNo . "',
													'" . $OrderLine->ReceiveQty . "',
													'" . $_SESSION['PO' . $identifier]->LineItems[$OrderLine
								->LineNo]->StandardCost . "',
													'" . ($QtyOnHandPrior + $OrderLine->ReceiveQty) . "'
													)";

							//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('stock movement records could not be inserted because');
							//$Result = DB_query($SQL, $ErrMsg, '', true);
							$Result = api_DB_query($SQL);

						} /*end of its a stock item - updates to locations and insert movements*/

						/* Check to see if the line item was flagged as the purchase of an asset */
						if ($SupplierInvoiceLine['assetid'] != '' AND $SupplierInvoiceLine['assetid'] != '0') { //then it is an asset
							/*first validate the AssetID and if it doesn't exist treat it like a normal nominal item  */
							$SQL = "SELECT assetid,
										   datepurchased					
											costact
											FROM fixedassets
										INNER JOIN fixedassetcategories
										ON fixedassets.assetcategoryid=fixedassetcategories.categoryid WHERE assetid='" . $SupplierInvoiceLine['assetid'] . "'";																
							$CheckAssetExistsResult = api_DB_query($SQL);	
							/*								
							$CheckAssetExistsResult = api_DB_query("SELECT assetid,
																		datepurchased,
																		costact
																FROM fixedassets
																INNER JOIN fixedassetcategories
																ON fixedassets.assetcategoryid=fixedassetcategories.categoryid
																WHERE assetid='" . $SupplierInvoiceLine['assetid'] . "'");
																*/

							if (DB_num_rows($CheckAssetExistsResult) == 1) { //then work with the assetid provided
								/*Need to add a fixedassettrans for the cost of the asset being received */
								$SQL = "INSERT INTO fixedassettrans (assetid,
																	transtype,
																	transno,
																	transdate,
																	periodno,
																	inputdate,
																	fixedassettranstype,
																	amount)
												VALUES ('" . $SupplierInvoiceLine['assetid'] . "',
														25,
														'" . $GRN . "',
														'" . FormatDateForSQL($DeliveryDate) . "',
														'" . $PeriodNo . "',
														CURRENT_DATE,
														'" . __('cost') . "',
														'" . $CurrentStandardCost * $receivedqty . "')";
								//$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE The fixed asset transaction could not be inserted because');
								//$Result = DB_query($SQL, $ErrMsg, '', true);
								$Result = api_DB_query($SQL);

								/*Now get the correct cost GL account from the asset category */
								$AssetRow = DB_fetch_array($CheckAssetExistsResult);
								/*Over-ride any GL account specified in the order with the asset category cost account */
								/*
								$_SESSION['PO' . $identifier]->LineItems[$OrderLine
									->LineNo]->GLCode = $AssetRow['costact'];
								*/
								$GLCode = $AssetRow['costact'];
								/*Now if there are no previous additions to this asset update the date purchased */
								if ($AssetRow['datepurchased'] == '1000-01-01') {
									/* it is a new addition as the date is set to 1000-01-01 when the asset record is created
									* before any cost is added to the asset
									*/
									$SQL = "UPDATE fixedassets
												SET datepurchased='" . FormatDateForSQL($DeliveryDate) . "',
													cost = cost + " . ($CurrentStandardCost * $receivedqty) . "
												WHERE assetid = '" . $SupplierInvoiceLine['assetid'] . "'";
								}
								else {
									$SQL = "UPDATE fixedassets SET cost = cost + " . ($CurrentStandardCost * $receivedqty) . "
												WHERE assetid = '" . $SupplierInvoiceLine['assetid'] . "'";
								}
								//$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE. The fixed asset cost and date purchased was not able to be updated because:');
								//$Result = DB_query($SQL, $ErrMsg, '', true);
								$Result = api_DB_query($SQL);

							} //assetid provided doesn't exist so ignore it and treat as a normal nominal item

						} //assetid is set so the nominal item is an asset
						/* If GLLink_Stock then insert GLTrans to debit the GL Code  and credit GRN Suspense account at standard cost*/
						//if ($_SESSION['PO' . $identifier]->GLLink == 1 AND $OrderLine->GLCode != 0) {

						// HARD CODE GLLINK VALUE for testing
						$GLLink = 1;
						if ($GLLink == 1 AND $GLCode != 0) {
							/*GLCode is set to 0 when the GLLink is not activated this covers a situation where the GLLink is now active but it wasn't when this PO was entered */

							/*first the debit using the GLCode in the PO detail record entry*/
							$SQL = "INSERT INTO gltrans (type,
														typeno,
														trandate,
														periodno,
														account,
														narrative,
														amount)
												VALUES (
													25,
													'" . $GRN . "',
													'" . FormatDateForSQL($DeliveryDate) . "',
													'" . $PeriodNo . "',
													'" . $GLCode . "',
													'" . mb_substr('PO: ' . $OrderNo . ' ' . $SupplierID . ' - ' . $StockID . ' - ' . DB_escape_string($ItemDescription) . ' x ' . $ReceiveQty . ' @ ' . locale_number_format($decimalplaces), 0, 200) . "',
													'" . $CurrentStandardCost * $ReceiveQty . "'
													)";

							//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The purchase GL posting could not be inserted because');
							//$Result = DB_query($SQL, $ErrMsg, '', true);
							$Result = api_DB_query($SQL);

							/* If the CurrentStandardCost != UnitCost (the standard at the time the first delivery was booked in,  and its a stock item, then the difference needs to be booked in against the purchase price variance account */

							/*now the GRN suspense entry*/
							$SQL = "INSERT INTO gltrans (type,
														typeno,
														trandate,
														periodno,
														account,
														narrative,
														amount)
												VALUES (25,
													'" . $GRN . "',
													'" . FormatDateForSQL($DeliveryDate) . "',
													'" . $PeriodNo . "',
													'" . $GRNAct . "',
													'" . mb_substr(__('PO' . $identifier) . ': ' . $_SESSION['PO' . $identifier]->OrderNo . ' ' . $_SESSION['PO' . $identifier]->SupplierID . ' - ' . $OrderLine->StockID . ' - ' . DB_escape_string($OrderLine->ItemDescription) . ' x ' . $OrderLine->ReceiveQty . ' @ ' . locale_number_format($UnitCost, $_SESSION['CompanyRecord']['decimalplaces']), 0, 200) . "',
													'" . -$UnitCost * $ReceiveQty . "'
													)";

							//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The GRN suspense side of the GL posting could not be inserted because');
							//$Result = DB_query($SQL, $ErrMsg, '', true);
							$Result = api_DB_query($SQL);

						} /* end of if GL and stock integrated and standard cost !=0 */
					} /*end of OrderLine loop */
					$StatusComment = date($_SESSION['DefaultDateFormat']) . ' - ' . __('Order Completed on entry of GRN') . '<br />' . $_SESSION['PO' . $identifier]->StatusComments;
					$SQL = "UPDATE purchorders
							SET status='Completed',
							stat_comment='" . $StatusComment . "'
							WHERE orderno='" .'PO' . $OrderNo . "'";
					$Result = DB_query($SQL);
						return 'line 1061 GL '. $GLLink  .' && GL code:  '. $GLCode .' SQL: '.$SQL  ;

					if ($_SESSION['PO' . $identifier]->GLLink == 1) {
						EnsureGLEntriesBalance(25, $GRN);
					}

					DB_Txn_Commit();

					//Now add all these deliveries to this purchase invoice


					$SQL = "SELECT grnbatch,
									grnno,
									purchorderdetails.orderno,
									purchorderdetails.unitprice,
									grns.itemcode,
									grns.deliverydate,
									grns.itemdescription,
									grns.qtyrecd,
									grns.quantityinv,
									grns.stdcostunit,
									grns.supplierref,
									purchorderdetails.glcode,
									purchorderdetails.shiptref,
									purchorderdetails.jobref,
									purchorderdetails.podetailitem,
									purchorderdetails.assetid,
									stockmaster.decimalplaces
							FROM grns INNER JOIN purchorderdetails
								ON  grns.podetailitem=purchorderdetails.podetailitem
							LEFT JOIN stockmaster ON grns.itemcode=stockmaster.stockid
							WHERE grns.supplierid ='" . $_SESSION['SuppTrans']->SupplierID . "'
							AND purchorderdetails.orderno = '" . intval($_GET['ReceivePO']) . "'
							AND grns.qtyrecd - grns.quantityinv > 0
							ORDER BY grns.grnno";
					$GRNResults = DB_query($SQL);

					while ($MyRow = DB_fetch_array($GRNResults)) {

						if ($MyRow['decimalplaces'] == '') {
							$MyRow['decimalplaces'] = 2;
						}
						$_SESSION['SuppTrans']->Add_GRN_To_Trans($MyRow['grnno'], $MyRow['podetailitem'], $MyRow['itemcode'], $MyRow['itemdescription'], $MyRow['qtyrecd'], $MyRow['quantityinv'], $MyRow['qtyrecd'] - $MyRow['quantityinv'], $MyRow['unitprice'], $MyRow['unitprice'], true, $MyRow['stdcostunit'], $MyRow['shiptref'], $MyRow['jobref'], $MyRow['glcode'], $MyRow['orderno'], $MyRow['assetid'], 0, $MyRow['decimalplaces'], $MyRow['grnbatch'], $MyRow['supplierref']);
					}
				} //end if the order has no controlled items on it

			//} //only allow auto receiving of all lines if the PO is authorised

		//} //only allow auto receiving if the user has permission to receive goods

	} // Page called with link to receive all the items on a PO

	//=========mwisho wa PO ====================//

	/* Set the session variables to the posted data from the form if the page has called itself */
	if (isset($_POST['ExRate'])) {
		$_SESSION['SuppTrans']->ExRate = filter_number_format($_POST['ExRate']);
		$_SESSION['SuppTrans']->Comments = $_POST['Comments'];
		$_SESSION['SuppTrans']->TranDate = $_POST['TranDate'];

		if (mb_substr($_SESSION['SuppTrans']->Terms, 0, 1) == '1') { /*Its a day in the following month when due */
			$DayInFollowingMonth = (int)mb_substr($_SESSION['SuppTrans']->Terms, 1);
			$DaysBeforeDue = 0;
		}
		else { /*Use the Days Before Due to add to the invoice date */
			$DayInFollowingMonth = 0;
			$DaysBeforeDue = (int)mb_substr($_SESSION['SuppTrans']->Terms, 1);
		}

		$_SESSION['SuppTrans']->DueDate = CalcDueDate($_SESSION['SuppTrans']->TranDate, $DayInFollowingMonth, $DaysBeforeDue);

		$_SESSION['SuppTrans']->SuppReference = $_POST['SuppReference'];

		if ($_SESSION['SuppTrans']->GLLink_Creditors == 1) {

			/*The link to GL from creditors is active so the total should be built up from GLPostings and GRN entries
			if the link is not active then OvAmount must be entered manually. */

			$_SESSION['SuppTrans']->OvAmount = 0; /* for starters */
			if (count($_SESSION['SuppTrans']->GRNs) > 0) {
				foreach ($_SESSION['SuppTrans']->GRNs as $GRN) {
					$_SESSION['SuppTrans']->OvAmount += ($GRN->This_QuantityInv * $GRN->ChgPrice);
				}
			}
			if (count($_SESSION['SuppTrans']->GLCodes) > 0) {
				foreach ($_SESSION['SuppTrans']->GLCodes as $GLLine) {
					$_SESSION['SuppTrans']->OvAmount += $GLLine->Amount;
				}
			}
			if (count($_SESSION['SuppTrans']->Shipts) > 0) {
				foreach ($_SESSION['SuppTrans']->Shipts as $ShiptLine) {
					$_SESSION['SuppTrans']->OvAmount += $ShiptLine->Amount;
				}
			}
			if (count($_SESSION['SuppTrans']->Contracts) > 0) {
				foreach ($_SESSION['SuppTrans']->Contracts as $Contract) {
					$_SESSION['SuppTrans']->OvAmount += $Contract->Amount;
				}
			}
			if (count($_SESSION['SuppTrans']->Assets) > 0) {
				foreach ($_SESSION['SuppTrans']->Assets as $FixedAsset) {
					$_SESSION['SuppTrans']->OvAmount += $FixedAsset->Amount;
				}
			}
			$_SESSION['SuppTrans']->OvAmount = round($_SESSION['SuppTrans']->OvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces);
		}
		else {
			/*OvAmount must be entered manually */
			$_SESSION['SuppTrans']->OvAmount = round(filter_number_format($_POST['OvAmount']) , $_SESSION['SuppTrans']->CurrDecimalPlaces);
		}
	}

 // $_POST['PostInvoice'] is set so do the postings -and dont show the button to process
	/*First do input reasonableness checks
	 then do the updates and inserts to process the invoice entered */
	$TaxTotal = 0;
	foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {
		/*Set the tax rate to what was entered */
		if (isset($_POST['TaxRate' . $Tax->TaxCalculationOrder])) {
			$_SESSION['SuppTrans']->Taxes[$Tax
				->TaxCalculationOrder]->TaxRate = filter_number_format($_POST['TaxRate' . $Tax->TaxCalculationOrder]) / 100;
		}
		if ($_POST['OverRideTax'] == 'Auto' OR !isset($_POST['OverRideTax'])) {
			/*Now recaluclate the tax depending on the method */
			/*Now recaluclate the tax depending on the method */
			if ($Tax->TaxOnTax == 1) {

				$_SESSION['SuppTrans']->Taxes[$Tax
					->TaxCalculationOrder]->TaxOvAmount = $_SESSION['SuppTrans']->Taxes[$Tax
					->TaxCalculationOrder]->TaxRate * ($_SESSION['SuppTrans']->OvAmount + $TaxTotal);

			}
			else { /*Calculate tax without the tax on tax */

				$_SESSION['SuppTrans']->Taxes[$Tax
					->TaxCalculationOrder]->TaxOvAmount = $_SESSION['SuppTrans']->Taxes[$Tax
					->TaxCalculationOrder]->TaxRate * $_SESSION['SuppTrans']->OvAmount;

			}
		}
		else { /*Tax being entered manually accept the taxamount entered as is*/
			$_SESSION['SuppTrans']->Taxes[$Tax
				->TaxCalculationOrder]->TaxOvAmount = filter_number_format($_POST['TaxAmount' . $Tax->TaxCalculationOrder]);
		}
		$TaxTotal += $_SESSION['SuppTrans']->Taxes[$Tax
			->TaxCalculationOrder]->TaxOvAmount;
	}

	$InputError = false;
	if ($TaxTotal + $_SESSION['SuppTrans']->OvAmount < 0) {

		$InputError = true;
		prnMsg(__('The invoice as entered cannot be processed because the total amount of the invoice is less than  0') . '. ' . __('Invoices are expected to have a positive charge') , 'error');
		echo '<p>' . __('The tax total is') . ' : ' . locale_number_format($TaxTotal, $_SESSION['SuppTrans']->CurrDecimalPlaces);
		echo '<p>' . __('The ovamount is') . ' : ' . locale_number_format($_SESSION['SuppTrans']->OvAmount, $_SESSION['SuppTrans']->CurrDecimalPlaces);

	}
	elseif ($TaxTotal + $_SESSION['SuppTrans']->OvAmount == 0) {

		prnMsg(__('The invoice as entered will be processed but be warned the amount of the invoice is  zero!') . '. ' . __('Invoices are normally expected to have a positive charge') , 'warn');

	}
	elseif (mb_strlen($_SESSION['SuppTrans']->SuppReference) < 1) {

		$InputError = true;
		prnMsg(__('The invoice as entered cannot be processed because the there is no suppliers invoice number or reference entered') . '. ' . __('The supplier invoice number must be entered') , 'error');

	}
	elseif (!Is_date($_SESSION['SuppTrans']->TranDate)) {

		$InputError = true;
		prnMsg(__('The invoice as entered cannot be processed because the invoice date entered is not in the format') . ' ' . $_SESSION['DefaultDateFormat'], 'error');

	}
	elseif (DateDiff(date($_SESSION['DefaultDateFormat']) , $_SESSION['SuppTrans']->TranDate, 'd') < 0) {

		$InputError = true;
		prnMsg(__('The invoice as entered cannot be processed because the invoice date is after today') . '. ' . __('Purchase invoices are expected to have a date prior to or today') , 'error');

	}
	elseif ($_SESSION['SuppTrans']->ExRate <= 0) {

		$InputError = true;
		prnMsg(__('The invoice as entered cannot be processed because the exchange rate for the invoice has been entered as a negative or zero number') . '. ' . __('The exchange rate is expected to show how many of the suppliers currency there are in 1 of the local currency') , 'error');

	}
	elseif ($_SESSION['SuppTrans']->OvAmount < round($_SESSION['SuppTrans']->Total_Shipts_Value() + $_SESSION['SuppTrans']->Total_GL_Value() + $_SESSION['SuppTrans']->Total_Contracts_Value() + $_SESSION['SuppTrans']->Total_Assets_Value() + $_SESSION['SuppTrans']->Total_GRN_Value() , $_SESSION['SuppTrans']->CurrDecimalPlaces)) {

		prnMsg(__('The invoice total as entered is less than the sum of the shipment charges, the general ledger entries (if any), the charges for goods received, contract charges and fixed asset charges. There must be a mistake somewhere, the invoice as entered will not be processed') , 'error');
		$InputError = true;

	}
	else {

		$SQL = "SELECT count(*)
				FROM supptrans
				WHERE supplierno='" . $_SESSION['SuppTrans']->SupplierID . "'
				AND supptrans.suppreference='" . $_POST['SuppReference'] . "'";

		$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sql to check for the previous entry of the same invoice failed');
		$Result = DB_query($SQL, $ErrMsg, '', true);

		$MyRow = DB_fetch_row($Result);
		if ($MyRow[0] == 1) { /*Transaction reference already entered */
			prnMsg(__('The invoice number') . ' : ' . $_POST['SuppReference'] . ' ' . __('has already been entered') . '. ' . __('It cannot be entered again') , 'error');
			$InputError = true;
		}
	}

	if ($InputError == false) {

		/* SQL to process the postings for purchase invoice */
		/*Start an SQL transaction */

		DB_Txn_Begin();

		/*Get the next transaction number for internal purposes and the period to post GL transactions in based on the invoice date*/
		$InvoiceNo = GetNextTransNo(20);
		$PeriodNo = GetPeriod($_SESSION['SuppTrans']->TranDate);
		$SQLInvoiceDate = FormatDateForSQL($_SESSION['SuppTrans']->TranDate);

		if ($_SESSION['SuppTrans']->GLLink_Creditors == 1) {
			/*Loop through the GL Entries and create a debit posting for each of the accounts entered */
			$LocalTotal = 0;

			/*the postings here are a little tricky, the logic goes like this:
			if its a shipment entry then the cost must go against the GRN suspense account defined in the company record

			if its a general ledger amount it goes straight to the account specified

			if its a GRN amount invoiced then there are two possibilities:

			1 The PO line is on a shipment.
			The whole charge goes to the GRN suspense account pending the closure of the
			shipment where the variance is calculated on the shipment as a whole and the clearing entry to the GRN suspense
			is created. Also, shipment records are created for the charges in local currency.

			2. The order line item is not on a shipment
			The cost as originally credited to GRN suspense on arrival of goods is debited to GRN suspense.
			Depending on the setting of WeightedAverageCosting:
			If the order line item is a stock item and WeightedAverageCosting set to OFF then use standard costing .....
				Any difference
				between the std cost and the currency cost charged as converted at the ex rate of of the invoice is written off
				to the purchase price variance account applicable to the stock item being invoiced.
			Otherwise
				Recalculate the new weighted average cost of the stock and update the cost - post the difference to the appropriate stock code

			Or if its not a stock item
			but a nominal item then the GL account in the orignal order is used for the price variance account.
			*/

			foreach ($_SESSION['SuppTrans']->GLCodes as $EnteredGLCode) {

				/*GL Items are straight forward - just do the debit postings to the GL accounts specified -
				 the credit is to creditors control act  done later for the total invoice value + tax*/
				//skamnev added tag
				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
									VALUES (20,
										'" . $InvoiceNo . "',
										'" . $SQLInvoiceDate . "',
										'" . $PeriodNo . "',
										'" . $EnteredGLCode->GLCode . "',
										'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . $EnteredGLCode->Narrative, 0, 200) . "',
										'" . $EnteredGLCode->Amount / $_SESSION['SuppTrans']->ExRate . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added because');

				$Result = DB_query($SQL, $ErrMsg, '', true);
				InsertGLTags($EnteredGLCode->Tag);

				$LocalTotal += $EnteredGLCode->Amount / $_SESSION['SuppTrans']->ExRate;
			}

			foreach ($_SESSION['SuppTrans']->Shipts as $ShiptChg) {

				/*shipment postings are also straight forward - just do the debit postings to the GRN suspense account
				 these entries are reversed from the GRN suspense when the shipment is closed*/

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
							VALUES (20,
									'" . $InvoiceNo . "',
									'" . $SQLInvoiceDate . "',
									'" . $PeriodNo . "',
									'" . $_SESSION['SuppTrans']->GRNAct . "',
									'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('Shipment charge against') . ' ' . $ShiptChg->ShiptRef, 0, 200) . "',
									'" . $ShiptChg->Amount / $_SESSION['SuppTrans']->ExRate . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the shipment') . ' ' . $ShiptChg->ShiptRef . ' ' . __('could not be added because');

				$Result = DB_query($SQL, $ErrMsg, '', true);

				$LocalTotal += $ShiptChg->Amount / $_SESSION['SuppTrans']->ExRate;

			}

			foreach ($_SESSION['SuppTrans']->Assets as $AssetAddition) {
				/* only the GL entries if the creditors/GL integration is enabled */
				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
								VALUES ('20',
									'" . $InvoiceNo . "',
									'" . $SQLInvoiceDate . "',
									'" . $PeriodNo . "',
									'" . $AssetAddition->CostAct . "',
									'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' ' . __('Asset Addition') . ' ' . $AssetAddition->AssetID . ': ' . $AssetAddition->Description, 0, 200) . "',
									'" . ($AssetAddition->Amount / $_SESSION['SuppTrans']->ExRate) . "')";
				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the asset addition could not be added because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

				$LocalTotal += ($AssetAddition->Amount / $_SESSION['SuppTrans']->ExRate);
			}

			foreach ($_SESSION['SuppTrans']->Contracts as $Contract) {

				/*contract postings need to get the WIP from the contract items stock category record
				 *  debit postings to this WIP account
				 * the WIP account is tidied up when the contract is closed*/
				$Result = DB_query("SELECT wipact FROM stockcategory
									INNER JOIN stockmaster ON
									stockcategory.categoryid=stockmaster.categoryid
									WHERE stockmaster.stockid='" . $Contract->ContractRef . "'");
				$WIPRow = DB_fetch_row($Result);
				$WIPAccount = $WIPRow[0];
				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
									VALUES ('20',
											'" . $InvoiceNo . "',
											'" . $SQLInvoiceDate . "',
											'" . $PeriodNo . "',
											'" . $WIPAccount . "',
											'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' ' . __('Contract charge against') . ' ' . $Contract->ContractRef, 0, 200) . "',
											'" . ($Contract->Amount / $_SESSION['SuppTrans']->ExRate) . "')";
				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the contract') . ' ' . $Contract->ContractRef . ' ' . __('could not be added because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
				$LocalTotal += ($Contract->Amount / $_SESSION['SuppTrans']->ExRate);
			}

			foreach ($_SESSION['SuppTrans']->GRNs as $EnteredGRN) {

				if (mb_strlen($EnteredGRN->ShiptRef) == 0 OR $EnteredGRN->ShiptRef == 0) {
					/*so its not a GRN shipment item
					 enter the GL entry to reverse the GRN suspense entry created on delivery
					 * at standard cost/or weighted average cost used on delivery */

					/*Always do this - for weighted average costing and also for standard costing */

					if ($EnteredGRN->StdCostUnit * ($EnteredGRN->This_QuantityInv) != 0) {
						$SQL = "INSERT INTO gltrans (type,
													typeno,
													trandate,
													periodno,
													account,
													narrative,
													amount)
								VALUES ('20',
									'" . $InvoiceNo . "',
									'" . $SQLInvoiceDate . "',
									'" . $PeriodNo . "',
									'" . $_SESSION['SuppTrans']->GRNAct . "',
									'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . $EnteredGRN->This_QuantityInv . ' @  ' . __('std cost of') . ' ' . $EnteredGRN->StdCostUnit, 0, 200) . "',
								 	'" . ($EnteredGRN->StdCostUnit * $EnteredGRN->This_QuantityInv) . "')";

						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added because');
						$Result = DB_query($SQL, $ErrMsg, '', true);
					}

					$PurchPriceVar = $EnteredGRN->This_QuantityInv * (($EnteredGRN->ChgPrice / $_SESSION['SuppTrans']->ExRate) - $EnteredGRN->StdCostUnit);

					/*Yes.... but where to post this difference to - if its a stock item the variance account must be retrieved from the stock category record
					if its a nominal purchase order item with no stock item then there will be no standard cost and it will all be variance so post it to the
					account specified in the purchase order detail record */

					if ($PurchPriceVar != 0) { /* don't bother with this lot if there is no difference ! */
						if (mb_strlen($EnteredGRN->ItemCode) > 0 OR $EnteredGRN->ItemCode != '') { /*so it is a stock item */

							/*need to get the stock category record for this stock item - this is function in SQL_CommonFunctions.php */
							$StockGLCode = GetStockGLCode($EnteredGRN->ItemCode);

							/*We have stock item and a purchase price variance need to see whether we are using Standard or WeightedAverageCosting */

							if ($_SESSION['WeightedAverageCosting'] == 1) { /*Weighted Average costing */

								/* First off figure out the new weighted average cost Need the following data:
								- How many in stock now
								- The quantity being invoiced here - $EnteredGRN->This_QuantityInv
								- The cost of these items - $EnteredGRN->ChgPrice  / $_SESSION['SuppTrans']->ExRate */

								$TotalQuantityOnHand = GetQuantityOnHand($EnteredGRN->ItemCode, 'ALL');

								/*The cost adjustment is the price variance / the total quantity in stock
								But that is only provided that the total quantity in stock is greater than the quantity charged on this invoice

								If the quantity on hand is less the amount charged on this invoice then some must have been sold and the price variance on these must be written off to price variances*/

								$WriteOffToVariances = 0;

								if ($EnteredGRN->This_QuantityInv > $TotalQuantityOnHand) {

									/*So we need to write off some of the variance to variances and only the balance of the quantity in stock to go to stock value */

									/*if the TotalQuantityOnHand is negative then this variance to write off is inflated by the negative quantity - which makes sense */

									$WriteOffToVariances = ($EnteredGRN->This_QuantityInv - $TotalQuantityOnHand) * (($EnteredGRN->ChgPrice / $_SESSION['SuppTrans']->ExRate) - $EnteredGRN->StdCostUnit);

									$SQL = "INSERT INTO gltrans (type,
																typeno,
																trandate,
																periodno,
																account,
																narrative,
																amount)
														VALUES (20,
															'" . $InvoiceNo . "',
															'" . $SQLInvoiceDate . "',
															'" . $PeriodNo . "',
															'" . $StockGLCode['purchpricevaract'] . "',
															'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . ($EnteredGRN->This_QuantityInv - $TotalQuantityOnHand) . ' x  ' . __('price var of') . ' ' . round(($EnteredGRN->ChgPrice / $_SESSION['SuppTrans']->ExRate) - $EnteredGRN->StdCostUnit, 2), 0, 200) . "',
															'" . $WriteOffToVariances . "')";

									$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');

									$Result = DB_query($SQL, $ErrMsg, '', true);
								} // end if the quantity being invoiced here is greater than the current stock on hand
								/*Now post any remaining price variance to stock rather than price variances */

								$SQL = "INSERT INTO gltrans (type,
															typeno,
															trandate,
															periodno,
															account,
															narrative,
															amount)
													VALUES (20,
													'" . $InvoiceNo . "',
													'" . $SQLInvoiceDate . "',
													'" . $PeriodNo . "',
													'" . $StockGLCode['stockact'] . "',
													'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('Average Cost Adj') . ' - ' . $EnteredGRN->ItemCode . ' x ' . $TotalQuantityOnHand . ' x ' . round(($EnteredGRN->ChgPrice / $_SESSION['SuppTrans']->ExRate) - $EnteredGRN->StdCostUnit, $_SESSION['CompanyRecord']['decimalplaces']), 0, 200) . "',
													'" . ($PurchPriceVar - $WriteOffToVariances) . "')";

								$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');

								$Result = DB_query($SQL, $ErrMsg, '', true);

							}
							else { //It must be Standard Costing
								$SQL = "INSERT INTO gltrans (type,
															typeno,
															trandate,
															periodno,
															account,
															narrative,
															amount)
													VALUES (20,
														'" . $InvoiceNo . "',
														'" . $SQLInvoiceDate . "',
														'" . $PeriodNo . "',
														'" . $StockGLCode['purchpricevaract'] . "',
														'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . $EnteredGRN->This_QuantityInv . ' x  ' . __('price var of') . ' ' . round(($EnteredGRN->ChgPrice / $_SESSION['SuppTrans']->ExRate) - $EnteredGRN->StdCostUnit, 2), 0, 200) . "',
														'" . $PurchPriceVar . "')";

								$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');
								$Result = DB_query($SQL, $ErrMsg, '', true);
							}
						}
						else {
							/* its a nominal purchase order item that is not on a shipment so post the whole lot to the GLCode specified in the order, the purchase price var is actually the diff between the
							order price and the actual invoice price since the std cost was made equal to the order price in local currency at the time
							the goods were received */
							$GLCode = $EnteredGRN->GLCode; //by default
							if ($EnteredGRN->AssetID != 0) { //then it is an asset
								/*Need to get the asset details  for posting */
								$Result = DB_query("SELECT costact
													FROM fixedassets INNER JOIN fixedassetcategories
													ON fixedassets.assetcategoryid= fixedassetcategories.categoryid
													WHERE assetid='" . $EnteredGRN->AssetID . "'");
								if (DB_num_rows($Result) != 0) { // the asset exists
									$AssetRow = DB_fetch_array($Result);
									$GLCode = $AssetRow['costact'];
								}
							} //the item was an asset received on a purchase order
							$SQL = "INSERT INTO gltrans (type,
														typeno,
														trandate,
														periodno,
														account,
														narrative,
														amount)
									VALUES (20,
											'" . $InvoiceNo . "',
											'" . $SQLInvoiceDate . "',
											'" . $PeriodNo . "',
											'" . $GLCode . "',
											'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemDescription . ' x ' . $EnteredGRN->This_QuantityInv . ' x  ' . __('price var') . ' ' . locale_number_format(($EnteredGRN->ChgPrice / $_SESSION['SuppTrans']->ExRate) - $EnteredGRN->StdCostUnit, $_SESSION['SuppTrans']->CurrDecimalPlaces), 0, 200) . "',
											'" . $PurchPriceVar . "')";

							$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');

							$Result = DB_query($SQL, $ErrMsg, '', true);
						}
					}

				}
				else {
					/*then its a purchase order item on a shipment - whole charge amount to GRN suspense pending closure of the shipment when the variance is calculated and the GRN act cleared up for the shipment */

					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount)
										VALUES (20,
											'" . $InvoiceNo . "',
											'" . $SQLInvoiceDate . "',
											'" . $PeriodNo . "',
											'" . $_SESSION['SuppTrans']->GRNAct . "',
											'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . $EnteredGRN->This_QuantityInv . ' @ ' . $_SESSION['SuppTrans']->CurrCode . ' ' . $EnteredGRN->ChgPrice . ' @ ' . __('a rate of') . ' ' . $_SESSION['SuppTrans']->ExRate, 0, 200) . "',
											'" . (($EnteredGRN->ChgPrice * $EnteredGRN->This_QuantityInv) / $_SESSION['SuppTrans']->ExRate) . "')";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}
				$LocalTotal += ($EnteredGRN->ChgPrice * $EnteredGRN->This_QuantityInv) / $_SESSION['SuppTrans']->ExRate;
			} /* end of GRN postings */

			foreach ($_SESSION['SuppTrans']->Taxes as $Tax) {
				/* Now the TAX account */
				if ($Tax->TaxOvAmount <> 0) {
					$SQL = "INSERT INTO gltrans (type,
												typeno,
												trandate,
												periodno,
												account,
												narrative,
												amount)
										VALUES (20,
												'" . $InvoiceNo . "',
												'" . $SQLInvoiceDate . "',
												'" . $PeriodNo . "',
												'" . $Tax->TaxGLCode . "',
												'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('Inv') . ' ' . $_SESSION['SuppTrans']->SuppReference . ' ' . $Tax->TaxAuthDescription . ' ' . locale_number_format($Tax->TaxRate * 100, 2) . '% ' . $_SESSION['SuppTrans']->CurrCode . $Tax->TaxOvAmount . ' @ ' . __('exch rate') . ' ' . $_SESSION['SuppTrans']->ExRate, 0, 200) . "',
												'" . ($Tax->TaxOvAmount / $_SESSION['SuppTrans']->ExRate) . "')";

					$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the tax could not be added because');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				}

			} /*end of loop to post the tax */
			/* Now the control account */

			$SQL = "INSERT INTO gltrans (type,
										typeno,
										trandate,
										periodno,
										account,
										narrative,
										amount)
								VALUES (20,
									'" . $InvoiceNo . "',
									'" . $SQLInvoiceDate . "',
									'" . $PeriodNo . "',
									'" . $_SESSION['SuppTrans']->CreditorsAct . "',
									'" . mb_substr($_SESSION['SuppTrans']->SupplierID . ' - ' . __('Inv') . ' ' . $_SESSION['SuppTrans']->SuppReference . ' ' . $_SESSION['SuppTrans']->CurrCode . locale_number_format($_SESSION['SuppTrans']->OvAmount + $TaxTotal, $_SESSION['SuppTrans']->CurrDecimalPlaces) . ' @ ' . __('a rate of') . ' ' . $_SESSION['SuppTrans']->ExRate, 0, 200) . "',
									'" . -($LocalTotal + ($TaxTotal / $_SESSION['SuppTrans']->ExRate)) . "')";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the control total could not be added because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			EnsureGLEntriesBalance(20, $InvoiceNo);
		} /*Thats the end of the GL postings */

		/*Now insert the invoice into the SuppTrans table*/

		$SQL = "INSERT INTO supptrans (transno,
										type,
										supplierno,
										suppreference,
										trandate,
										duedate,
										ovamount,
										ovgst,
										rate,
										transtext,
										inputdate)
							VALUES (
								'" . $InvoiceNo . "',
								20 ,
								'" . $_SESSION['SuppTrans']->SupplierID . "',
								'" . $_SESSION['SuppTrans']->SuppReference . "',
								'" . $SQLInvoiceDate . "',
								'" . FormatDateForSQL($_SESSION['SuppTrans']->DueDate) . "',
								'" . $_SESSION['SuppTrans']->OvAmount . "',
								'" . $TaxTotal . "',
								'" . $_SESSION['SuppTrans']->ExRate . "',
								'" . $_SESSION['SuppTrans']->Comments . "',
								CURRENT_DATE)";

		$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The supplier invoice transaction could not be added to the database because');
		$Result = DB_query($SQL, $ErrMsg, '', true);
		$SuppTransID = DB_Last_Insert_ID('supptrans', 'id');

		/* Insert the tax totals for each tax authority where tax was charged on the invoice */
		foreach ($_SESSION['SuppTrans']->Taxes AS $TaxTotals) {

			$SQL = "INSERT INTO supptranstaxes (supptransid,
												taxauthid,
												taxamount)
									VALUES (
										'" . $SuppTransID . "',
										'" . $TaxTotals->TaxAuthID . "',
										'" . $TaxTotals->TaxOvAmount . "')";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The supplier transaction taxes records could not be inserted because');
			$Result = DB_query($SQL, $ErrMsg, '', true);
		}

		/* Now update the GRN and PurchOrderDetails records for amounts invoiced  - can't use the other loop through the GRNs as this was only where the GL link to credtors is active */

		foreach ($_SESSION['SuppTrans']->GRNs as $EnteredGRN) {

			//in local currency
			$ActualCost = $EnteredGRN->ChgPrice / $_SESSION['SuppTrans']->ExRate;
			$PurchPriceVar = $EnteredGRN->This_QuantityInv * ($ActualCost - $EnteredGRN->StdCostUnit);

			$SQL = "UPDATE purchorderdetails
					SET qtyinvoiced = qtyinvoiced + " . $EnteredGRN->This_QuantityInv . ",
						actprice = '" . $EnteredGRN->ChgPrice . "'
					WHERE podetailitem = '" . $EnteredGRN->PODetailItem . "'";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The quantity invoiced of the purchase order line could not be updated because');

			$Result = DB_query($SQL, $ErrMsg, '', true);

			$SQL = "UPDATE grns
					SET quantityinv = quantityinv + " . $EnteredGRN->This_QuantityInv . "
					WHERE grnno = '" . $EnteredGRN->GRNNo . "'";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The quantity invoiced off the goods received record could not be updated because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			$SQL = "INSERT INTO suppinvstogrn VALUES ('" . $InvoiceNo . "',
									'" . $EnteredGRN->GRNNo . "')";
			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The invoice could not be mapped to the
					goods received record because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			if (mb_strlen($EnteredGRN->ShiptRef) > 0 AND $EnteredGRN->ShiptRef != '0') {
				/* insert the shipment charge records */
				$SQL = "INSERT INTO shipmentcharges (shiptref,
													transtype,
													transno,
													stockid,
													value)
										VALUES (
											'" . $EnteredGRN->ShiptRef . "',
											20,
											'" . $InvoiceNo . "',
											'" . $EnteredGRN->ItemCode . "',
											'" . ($EnteredGRN->This_QuantityInv * $EnteredGRN->ChgPrice) / $_SESSION['SuppTrans']->ExRate . "')";

				$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The shipment charge record for the shipment') . ' ' . $EnteredGRN->ShiptRef . ' ' . __('could not be added because');
				$Result = DB_query($SQL, $ErrMsg, '', true);

			} //end of adding GRN shipment charges
			else {
				/*so its not a GRN shipment item its a plain old stock item */

				if ($PurchPriceVar != 0) { /* don't bother with any of this lot if there is no difference ! */

					if (mb_strlen($EnteredGRN->ItemCode) > 0 OR $EnteredGRN->ItemCode != '') { /*so it is a stock item */

						/*We need to:
						 *
						 * a) update the stockmove for the delivery to reflect the actual cost of the delivery
						 *
						 * b) If a WeightedAverageCosting system and the stock quantity on hand now is negative then the cost that has gone to sales analysis and the cost of sales stock movement records will have been incorrect ... attempt to fix it retrospectively
						*/
						/*Get the location that the stock was booked into */
						$Result = DB_query("SELECT intostocklocation
											FROM purchorders
											WHERE orderno='" . $EnteredGRN->PONo . "'");
						$LocRow = DB_fetch_array($Result);
						$LocCode = $LocRow['intostocklocation'];

						/* First update the stockmoves delivery cost */
						$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The stock movement record for the delivery could not have the cost updated to the actual cost');
						$SQL = "UPDATE stockmoves SET price = '" . $ActualCost . "'
											WHERE stockid='" . $EnteredGRN->ItemCode . "'
											AND type=25
											AND loccode='" . $LocCode . "'
											AND transno='" . $EnteredGRN->GRNBatchNo . "'";

						$Result = DB_query($SQL, $ErrMsg, '', true);

						if ($_SESSION['WeightedAverageCosting'] == 1) {
							/*
							 * 	How many in stock now?
							 *  The quantity being invoiced here - $EnteredGRN->This_QuantityInv
							 *  If the quantity in stock now is less than the quantity being invoiced
							 *  here then some items sold will not have had this cost factored in
							 * The cost of these items = $ActualCost
							*/

							$TotalQuantityOnHand = GetQuantityOnHand($EnteredGRN->ItemCode, 'ALL');

							/* If the quantity on hand is less the quantity charged on this invoice then some must have been sold and the price variance should be reflected in the cost of sales*/

							if ($EnteredGRN->This_QuantityInv > $TotalQuantityOnHand) {

								/* The variance to the extent of the quantity invoiced should also be written off against the sales analysis cost - as sales analysis would have been created using the cost at the time the sale was made... this was incorrect as hind-sight has shown here. However, how to determine when these were last sold? To update the sales analysis cost. Work through the last 6 months sales analysis from the latest period in which this invoice is being posted and prior.

								The assumption here is that the goods have been sold prior to the purchase invoice  being entered so it is necessary to back track on the sales analysis cost.
								* Note that this will mean that posting to GL COGS will not agree to the cost of sales from the sales analysis
								* Of course the price variances will need to be included in COGS as well
								* */

								$QuantityVarianceAllocated = $EnteredGRN->This_QuantityInv;
								$CostVarPerUnit = $ActualCost - $EnteredGRN->StdCostUnit;
								$PeriodAllocated = $PeriodNo;
								$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sales analysis records could not be updated for the cost variances on this purchase invoice');

								while ($QuantityVarianceAllocated > 0) {
									$SalesAnalResult = DB_query("SELECT cust,
																	custbranch,
																	typeabbrev,
																	periodno,
																	stkcategory,
																	area,
																	salesperson,
																	cost,
																	qty
																FROM salesanalysis
																WHERE salesanalysis.stockid = '" . $EnteredGRN->ItemCode . "'
																AND salesanalysis.budgetoractual=1
																AND periodno='" . $PeriodAllocated . "'");
									if (DB_num_rows($SalesAnalResult) > 0) {
										while ($SalesAnalRow = DB_fetch_array($SalesAnalResult) AND $QuantityVarianceAllocated > 0) {
											if ($SalesAnalRow['qty'] <= $QuantityVarianceAllocated) {
												$QuantityVarianceAllocated -= $SalesAnalRow['qty'];
												$QuantityAllocated = $SalesAnalRow['qty'];
											}
											else {
												$QuantityAllocated = $QuantityVarianceAllocated;
												$QuantityVarianceAllocated = 0;
											}
											$UpdSalAnalResult = DB_query("UPDATE salesanalysis
																			SET cost = cost + " . ($CostVarPerUnit * $QuantityAllocated) . "
																			WHERE cust ='" . $SalesAnalRow['cust'] . "'
																			AND stockid='" . $EnteredGRN->ItemCode . "'
																			AND custbranch='" . $SalesAnalRow['custbranch'] . "'
																			AND typeabbrev='" . $SalesAnalRow['typeabbrev'] . "'
																			AND periodno='" . $PeriodAllocated . "'
																			AND area='" . $SalesAnalRow['area'] . "'
																			AND salesperson='" . $SalesAnalRow['salesperson'] . "'
																			AND stkcategory='" . $SalesAnalRow['stkcategory'] . "'
																			AND budgetoractual=1", $ErrMsg, '', true);
										}
									} //end if there were sales in that period
									$PeriodAllocated--; //decrement the period
									if ($PeriodNo - $PeriodAllocated > 6) {
										/*if more than 6 months ago when sales were made then forget it */
										break;
									}
								} /*end loop around different periods to see which sales analysis records to update */

								/*now we need to work back through the sales stockmoves up to the quantity on this purchase invoice to update costs
								 * Only go back up to 6 months looking for stockmoves and
								 * Only in the stock location where the purchase order was received
								 * into - if the stock was transferred to another location then
								 * we cannot adjust for this */
								$Result = DB_query("SELECT stkmoveno,
															type,
															qty,
															standardcost
													FROM stockmoves
													WHERE loccode='" . $LocCode . "'
													AND qty < 0
													AND stockid='" . $EnteredGRN->ItemCode . "'
													AND trandate>='" . FormatDateForSQL(DateAdd($_SESSION['SuppTrans']->TranDate, 'm', -6)) . "'
													ORDER BY stkmoveno DESC");
								$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The stock movements for invoices cannot be updated for the cost variances on this purchase invoice');
								$QuantityVarianceAllocated = $EnteredGRN->This_QuantityInv;
								while ($StkMoveRow = DB_fetch_array($Result) AND $QuantityVarianceAllocated > 0) {
									if ($StkMoveRow['qty'] + $QuantityVarianceAllocated > 0) {
										if ($StkMoveRow['type'] == 10) { //its a sales invoice
											$Result = DB_query("UPDATE stockmoves
																SET standardcost = '" . $ActualCost . "'
																WHERE stkmoveno = '" . $StkMoveRow['stkmoveno'] . "'", $ErrMsg, '', true);
										}
									}
									else { //Only $QuantityVarianceAllocated left to allocate so need need to apportion cost using weighted average
										if ($StkMoveRow['type'] == 10) { //its a sales invoice
											$WACost = (((-$StkMoveRow['qty'] - $QuantityVarianceAllocated) * $StkMoveRow['standardcost']) + ($QuantityVarianceAllocated * $ActualCost)) / -$StkMoveRow['qty'];

											$UpdStkMovesResult = DB_query("UPDATE stockmoves
																SET standardcost = '" . $WACost . "'
																WHERE stkmoveno = '" . $StkMoveRow['stkmoveno'] . "'", $ErrMsg, '', true);
										}
									}
									$QuantityVarianceAllocated += $StkMoveRow['qty'];
								}
							} // end if the quantity being invoiced here is greater than the current stock on hand
							/*Now to update the stock cost with the new weighted average */

							/*Need to consider what to do if the cost has been changed manually between receiving the stock and entering the invoice - this code assumes there has been no cost updates made manually and all the price variance is posted to stock.

							A nicety or important?? */

							$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The cost could not be updated because');

							if ($TotalQuantityOnHand > 0) {

								$CostIncrement = ($PurchPriceVar - $WriteOffToVariances) / $TotalQuantityOnHand;

								$SQL = "UPDATE stockmaster
										SET lastcost=materialcost+overheadcost+labourcost,
										materialcost=materialcost+" . $CostIncrement . "
										WHERE stockid='" . $EnteredGRN->ItemCode . "'";
								$Result = DB_query($SQL, $ErrMsg, '', true);
							}
							else {
								/* if stock is negative then update the cost to this cost */
								$SQL = "UPDATE stockmaster
										SET lastcost=materialcost+overheadcost+labourcost,
											materialcost='" . $ActualCost . "'
										WHERE stockid='" . $EnteredGRN->ItemCode . "'";
								$Result = DB_query($SQL, $ErrMsg, '', true);
							}
						} /* End if it is weighted average costing we are working with */
					} /*Its a stock item */
				} /* There was a price variance */
			}
			if ($EnteredGRN->AssetID != 0) { //then it is an asset
				if ($PurchPriceVar != 0) {
					/*Add the fixed asset trans for the difference in the cost */
					$SQL = "INSERT INTO fixedassettrans (assetid,
														transtype,
														transno,
														transdate,
														periodno,
														inputdate,
														fixedassettranstype,
														amount)
											VALUES ('" . $EnteredGRN->AssetID . "',
													20,
													'" . $InvoiceNo . "',
													'" . $SQLInvoiceDate . "',
													'" . $PeriodNo . "',
													CURRENT_DATE,
													'cost',
													'" . ($PurchPriceVar) . "')";
					$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE The fixed asset transaction could not be inserted because');
					$Result = DB_query($SQL, $ErrMsg, '', true);

					/*Now update the asset cost in fixedassets table */
					$SQL = "UPDATE fixedassets SET cost = cost + " . ($PurchPriceVar) . "
							WHERE assetid = '" . $EnteredGRN->AssetID . "'";

					$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE. The fixed asset cost could not be updated because:');
					$Result = DB_query($SQL, $ErrMsg, '', true);
				} //end if there was a difference in the cost

			} //the item was an asset received on a purchase order

		} /* end of the GRN loop to do the updates for the quantity of order items the supplier has invoiced */

		/*Add shipment charges records as necessary */
		foreach ($_SESSION['SuppTrans']->Shipts as $ShiptChg) {

			$SQL = "INSERT INTO shipmentcharges (shiptref,
												transtype,
												transno,
												value)
									VALUES ('" . $ShiptChg->ShiptRef . "',
												'20',
											'" . $InvoiceNo . "',
											'" . $ShiptChg->Amount / $_SESSION['SuppTrans']->ExRate . "')";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The shipment charge record for the shipment') . ' ' . $ShiptChg->ShiptRef . ' ' . __('could not be added because');

			$Result = DB_query($SQL, $ErrMsg, '', true);

		}
		/*Add contract charges records as necessary */

		foreach ($_SESSION['SuppTrans']->Contracts as $Contract) {

			if ($Contract->AnticipatedCost == true) {
				$Anticipated = 1;
			}
			else {
				$Anticipated = 0;
			}
			$SQL = "INSERT INTO contractcharges (contractref,
												transtype,
												transno,
												amount,
												narrative,
												anticipated)
									VALUES ('" . $Contract->ContractRef . "',
										'20',
										'" . $InvoiceNo . "',
										'" . $Contract->Amount / $_SESSION['SuppTrans']->ExRate . "',
										'" . $Contract->Narrative . "',
										'" . $Anticipated . "')";

			$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The contract charge record for contract') . ' ' . $Contract->ContractRef . ' ' . __('could not be added because');
			$Result = DB_query($SQL, $ErrMsg, '', true);
		}

		foreach ($_SESSION['SuppTrans']->Assets as $AssetAddition) {

			/*Asset additions need to have
			 * 	1. A fixed asset transaction inserted for the cost
			 * 	2. A general ledger transaction to fixed asset cost account if creditors linked
			 * 	3. The fixedasset table cost updated by the addition
			*/

			/* First the fixed asset transaction */
			$SQL = "INSERT INTO fixedassettrans (assetid,
												transtype,
												transno,
												transdate,
												periodno,
												inputdate,
												fixedassettranstype,
												amount)
									VALUES ('" . $AssetAddition->AssetID . "',
											20,
											'" . $InvoiceNo . "',
											'" . $SQLInvoiceDate . "',
											'" . $PeriodNo . "',
											CURRENT_DATE,
											'" . __('cost') . "',
											'" . ($AssetAddition->Amount / $_SESSION['SuppTrans']->ExRate) . "')";
			$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE The fixed asset transaction could not be inserted because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			/*Now update the asset cost in fixedassets table */
			$Result = DB_query("SELECT datepurchased
								FROM fixedassets
								WHERE assetid='" . $AssetAddition->AssetID . "'");
			$AssetRow = DB_fetch_array($Result);

			$SQL = "UPDATE fixedassets SET cost = cost + " . ($AssetAddition->Amount / $_SESSION['SuppTrans']->ExRate);
			if ($AssetRow['datepurchased'] == '1000-01-01') {
				$SQL .= ", datepurchased='" . $SQLInvoiceDate . "'";
			}
			$SQL .= " WHERE assetid = '" . $AssetAddition->AssetID . "'";
			$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE. The fixed asset cost and date purchased was not able to be updated because:');
			$Result = DB_query($SQL, $ErrMsg, '', true);
		} //end of non-gl fixed asset stuff
		DB_Txn_Commit();

		prnMsg(__('Supplier invoice number') . ' ' . $InvoiceNo . ' ' . __('has been processed') , 'success');
		echo '<br />
				<div class="centre">
					<a href="' . $RootPath . '/SupplierInvoice.php?&SupplierID=' . $_SESSION['SuppTrans']->SupplierID . '">' . __('Enter another Invoice for this Supplier') . '</a>
					<br />
					<a href="' . $RootPath . '/Payments.php?&SupplierID=' . $_SESSION['SuppTrans']->SupplierID . '&amp;Amount=' . ($_SESSION['SuppTrans']->OvAmount + $TaxTotal) . '">' . __('Enter payment') . '</a>
				</div>';
		unset($_SESSION['SuppTrans']->GRNs);
		unset($_SESSION['SuppTrans']->Shipts);
		unset($_SESSION['SuppTrans']->GLCodes);
		unset($_SESSION['SuppTrans']->Contracts);
		unset($_SESSION['SuppTrans']);
	}

	return $Errors;
} /*end of process invoice */



   //mpaka hapa tuna kosa vifuatavyo
   //invoice totals

/*
		DB_Txn_Begin();
		$InvoiceNo = GetNextTransNo(20);
		$PeriodNo = GetPeriod($SupplierHeader['trandate']);
		$SQLInvoiceDate = FormatDateForSQL($SupplierHeader['trandate']);

		if ($SupplierHeader['gllink_creditors'] == 1) {
			$LocalTotal = 0;

			foreach ($SupplierInvoiceLine as $key => $Value) {
				$SupplierHeader[$key] = DB_escape_string($Value);


				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
									VALUES (20,
										'" . $InvoiceNo . "',
										'" . $SQLInvoiceDate . "',
										'" . $PeriodNo . "',
										'" . $SupplierInvoiceLine['account'] . "',
										'" . mb_substr($SupplierID . ' - ' . $SupplierInvoiceLine['narrative'], 0, 200) . "',
										'" . $SupplierInvoiceLine['amount'] /$SupplierHeader['exrate'] . "')";
		     	//	return 'line 1648: '. $SQL;	
				$Result = api_DB_query($SQL);
			    //	$Result = DB_query($SQL);
				DB_Txn_Commit();
				if (DB_error_no() != 0) {
					$Errors[0] = DatabaseUpdateFailed;
				} else {
					$Errors[0]=0;
				}

				$SQL = "SELECT creditorsact FROM companies
				WHERE gllink_creditors = '" . $SupplierHeader['gllink_creditors'] . "'";
				$GLLink_CreditorsResult = api_DB_query($SQL);
				if (DB_num_rows($GLLink_CreditorsResult)==0){
					$Errors[0] = UserTaxProvinceNotSet;
					return $Errors;
				}
				$GLLink_CreditorsRow = DB_fetch_row($GLLink_CreditorsResult);
				$GLLink_Creditors = $GLLink_CreditorsRow[0];
				$CurrDecimalPlaces = $decimalplaces;

				$SQL = "INSERT INTO gltrans (type,
											typeno,
											trandate,
											periodno,
											account,
											narrative,
											amount)
									VALUES (20,
										'" . $InvoiceNo . "',
										'" . $SQLInvoiceDate . "',
										'" . $PeriodNo . "',
										'" . $GLLink_Creditors. "',
										'" . mb_substr($SupplierID . ' - ' . __('Inv') . ' ' .$SupplierHeader['suppreference']. ' ' .$currcode . locale_number_format($SupplierHeader['ovamount'] + $SupplierHeader['ovgst'], $CurrDecimalPlaces) . ' @ ' . __('a rate of') . ' ' . $SupplierHeader['exrate'], 0, 200) . "',
										'" . -($LocalTotal + ($SupplierHeader['ovgst'] / $SupplierHeader['exrate'])) . "')";
				$Result = api_DB_query($SQL);
				$LocalTotal +=  $SupplierInvoiceLine['amount'] /$SupplierHeader['exrate'];
				DB_Txn_Commit();
				if (DB_error_no() != 0) {
					$Errors[0] = DatabaseUpdateFailed;
				} else {
					$Errors[0]=0;
				}
				EnsureGLEntriesBalance(20, $InvoiceNo);				
			}		
		}
		$SQL = "INSERT INTO supptrans (transno,
							type,
							supplierno,
							suppreference,
							trandate,
							duedate,
							ovamount,
							ovgst,
							rate,
							transtext,
							inputdate)
				VALUES (
					'" . $InvoiceNo . "',
					20 ,
					'" . $SupplierID . "',
					'" . $SupplierHeader['suppreference'] . "',
					'" . FormatDateForSQL($SupplierHeader['trandate']) . "',
					'" . FormatDateForSQL($SupplierHeader['duedate']) . "',
					'" . $LocalTotal. "',
					'" . $SupplierHeader['ovgst']. "',
					'" . $SupplierHeader['exrate']. "',
					'" . $SupplierHeader['transtext']. "',
					CURRENT_DATE)";
		$Result = api_DB_query($SQL);
		DB_Txn_Commit();
		if (DB_error_no() != 0) {
			$Errors[0] = DatabaseUpdateFailed;
		} else {
			$Errors[0]=0;
		}
	return $Errors;
}

*/

/** Modify a supplier invoice header in webERP.
 */
function ModifySupplierInvoiceHeader($OrderHeader, $user, $password) {
	$Errors = array();
	$db = db($user, $password);
	if (gettype($db)=='integer') {
		$Errors[0]=NoAuthorisation;
		return $Errors;
	}
	foreach ($OrderHeader as $key => $Value) {
		$OrderHeader[$key] = DB_escape_string($Value);
	}
	$Errors=VerifyOrderHeaderExists($OrderHeader['orderno'], sizeof($Errors), $Errors);
	$Errors=VerifyDebtorExists($OrderHeader['debtorno'], sizeof($Errors), $Errors);
	$Errors=VerifyBranchNoExists($OrderHeader['debtorno'],$OrderHeader['branchcode'], sizeof($Errors), $Errors);
	if (isset($OrderHeader['customerref'])){
		$Errors=VerifyCustomerRef($OrderHeader['customerref'], sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['buyername'])){
		$Errors=VerifyBuyerName($OrderHeader['buyername'], sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['comments'])){
		$Errors=VerifyComments($OrderHeader['comments'], sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['orddate'])){
		$Errors=VerifyOrderDate($OrderHeader['orddate'], sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['ordertype'])){
		$Errors=VerifyOrderType($OrderHeader['ordertype'], sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['shipvia'])){
		$Errors=VerifyShipVia($OrderHeader['shipvia'], sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['deladd1'])){
		$Errors=VerifyAddressLine($OrderHeader['deladd1'], 40, sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['deladd2'])){
		$Errors=VerifyAddressLine($OrderHeader['deladd2'], 40, sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['deladd3'])){
		$Errors=VerifyAddressLine($OrderHeader['deladd3'], 40, sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['deladd4'])){
		$Errors=VerifyAddressLine($OrderHeader['deladd4'], 40, sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['deladd5'])){
		$Errors=VerifyAddressLine($OrderHeader['deladd5'], 20, sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['deladd6'])){
		$Errors=VerifyAddressLine($OrderHeader['deladd6'], 15, sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['contactphone'])){
		$Errors=VerifyPhoneNumber($OrderHeader['contactphone'], sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['contactemail'])){
		$Errors=VerifyEmailAddress($OrderHeader['contactemail'], sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['deliverto'])){
		$Errors=VerifyDeliverTo($OrderHeader['deliverto'], sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['deliverblind'])){
		$Errors=VerifyDeliverBlind($OrderHeader['deliverblind'], sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['freightcost'])){
		$Errors=VerifyFreightCost($OrderHeader['freightcost'], sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['fromstkloc'])){
		$Errors=VerifyFromStockLocation($OrderHeader['fromstkloc'], sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['deliverydate'])){
		$Errors=VerifyDeliveryDate($OrderHeader['deliverydate'], sizeof($Errors), $Errors);
	}
	if (isset($OrderHeader['quotation'])){
		$Errors=VerifyQuotation($OrderHeader['quotation'], sizeof($Errors), $Errors);
	}
	global  $SOH_DateFields;
	$SQL='UPDATE salesorders SET ';
	foreach ($OrderHeader as $key => $Value) {
		if (in_array($key, $SOH_DateFields) ) {
			$Value = FormatDateforSQL($Value);	// Fix dates
		}
		$SQL .= $key.'="'.$Value.'", ';
	}
	$SQL = mb_substr($SQL,0,-2). " WHERE orderno='" . $OrderHeader['orderno']. "'";
	if (sizeof($Errors)==0) {
		$Result = api_DB_Query($SQL);
		echo DB_error_no();
		if (DB_error_no() != 0) {
			$Errors[0] = DatabaseUpdateFailed;
		} else {
			$Errors[0]=0;
		}
	}
	return $Errors;
}

/** Create a supplier invoiceline in webERP. The order header must
 * already exist in webERP.
 */
function InsertSupplierInvoiceLine($OrderLine, $user, $password) {

	$Errors = array();
	$db = db($user, $password);
	if (gettype($db)=='integer') {
		$Errors[0]=NoAuthorisation;
		return $Errors;
	}
	foreach ($OrderLine as $key => $Value) {
		$OrderLine[$key] = DB_escape_string($Value);
	}
	$OrderLine['orderlineno'] = GetOrderLineNumber($OrderLine['orderno'], sizeof($Errors), $Errors);
	$Errors=VerifyOrderHeaderExists($OrderLine['orderno'], sizeof($Errors), $Errors);
	$Errors=VerifyStockCodeExists($OrderLine['stkcode'], sizeof($Errors), $Errors);
	if (isset($OrderLine['unitprice'])){
		$Errors=VerifyUnitPrice($OrderLine['unitprice'], sizeof($Errors), $Errors);
	}
	if (isset($OrderLine['quantity'])){
		$Errors=VerifyQuantity($OrderLine['quantity'], sizeof($Errors), $Errors);
	}
	if (isset($OrderLine['discountpercent'])){
		//$OrderLine['discountpercent'] = $OrderLine['discountpercent'] * 100;
		$Errors=VerifyDiscountPercent($OrderLine['discountpercent'], sizeof($Errors), $Errors);
		$OrderLine['discountpercent'] = $OrderLine['discountpercent']/100;
	}
	if (isset($OrderLine['narrative'])){
		$Errors=VerifyNarrative($OrderLine['narrative'], sizeof($Errors), $Errors);
	}
	// Not sure why the verification of itemdue doesn't work
	/*
	if (isset($OrderLine['itemdue'])){
		$Errors=VerifyItemDueDate($OrderLine['itemdue'], sizeof($Errors), $Errors);
	}
	*/
	if (isset($OrderLine['poline'])){
		$Errors=VerifyPOLine($OrderLine['poline'], sizeof($Errors), $Errors);
	}
	$FieldNames='';
	$FieldValues='';
	foreach ($OrderLine as $key => $Value) {
		$FieldNames.=$key.', ';
		if ($key == 'actualdispatchdate') {
			$Value = FormatDateWithTimeForSQL($Value);
		} elseif ($key == 'itemdue') {
			$Value = FormatDateForSQL($Value);
		}
		$FieldValues.= "'" . $Value . "', ";
	}

	$SQL = "INSERT INTO salesorderdetails (" . mb_substr($FieldNames,0,-2) . ")
		VALUES (" . mb_substr($FieldValues,0,-2) . ")";

	if (sizeof($Errors)==0) {
		$Result = api_DB_Query($SQL);
		if (DB_error_no() != 0) {
			$Errors[0] = DatabaseUpdateFailed;
		} else {
			$Errors[0]=0;
		}
	}
	return $Errors;
}

/** Modify a supplier invoice line in webERP. The order header must
 * already exist in webERP.
 */
function ModifySupplierInvoiceLine($OrderLine, $user, $password) {
	$Errors = array();
	$db = db($user, $password);
	if (gettype($db)=='integer') {
		$Errors[0]=NoAuthorisation;
		return $Errors;
	}
	foreach ($OrderLine as $key => $Value) {
		$OrderLine[$key] = DB_escape_string($Value);
	}
	$Errors=VerifyOrderHeaderExists($OrderLine['orderno'], sizeof($Errors), $Errors);
	$Errors=VerifyStockCodeExists($OrderLine['stkcode'], sizeof($Errors), $Errors);
	if (isset($OrderLine['unitprice'])){
		$Errors=VerifyUnitPrice($OrderLine['unitprice'], sizeof($Errors), $Errors);
	}
	if (isset($OrderLine['quantity'])){
		$Errors=VerifyQuantity($OrderLine['quantity'], sizeof($Errors), $Errors);
	}
	if (isset($OrderLine['discountpercent'])){
		//$OrderLine['discountpercent'] = $OrderLine['discountpercent'] * 100;
		$Errors=VerifyDiscountPercent($OrderLine['discountpercent'], sizeof($Errors), $Errors);
		$OrderLine['discountpercent'] = $OrderLine['discountpercent']/100;
	}
	if (isset($OrderLine['narrative'])){
		$Errors=VerifyNarrative($OrderLine['narrative'], sizeof($Errors), $Errors);
	}
	if (isset($OrderLine['itemdue'])){
		$Errors=VerifyItemDueDate($OrderLine['itemdue'], sizeof($Errors), $Errors);
	}
	if (isset($OrderLine['poline'])){
		$Errors=VerifyPOLine($OrderLine['poline'], sizeof($Errors), $Errors);
	}
	$SQL='UPDATE salesorderdetails SET ';
	foreach ($OrderLine as $key => $Value) {
		if ($key == 'actualdispatchdate') {
			$Value = FormatDateWithTimeForSQL($Value);
		}
		elseif ($key == 'itemdue')
			$Value = FormatDateForSQL($Value);
		$SQL .= $key.'="'.$Value.'", ';
	}
	//$SQL = mb_substr($SQL,0,-2).' WHERE orderno="'.$OrderLine['orderno'].'" and
		//	" orderlineno='.$OrderLine['orderlineno'];
	$SQL = mb_substr($SQL,0,-2)." WHERE orderno='" . $OrderLine['orderno']."' AND stkcode='" . $OrderLine['stkcode']."'";
			//echo $SQL;
			//exit();
	if (sizeof($Errors)==0) {
		$Result = api_DB_Query($SQL);
		echo DB_error_no();
		if (DB_error_no() != 0) {
			$Errors[0] = DatabaseUpdateFailed;
		} else {
			$Errors[0]=0;
		}
	}
	return $Errors;
}

/** This function takes a supplier invoice no and returns an associative array containing
   the database record for that order. If the order number doesn't exist
   then it returns an $Errors array.
*/
function GetSupplierInvoiceHeaderDetail($OrderNo, $user, $password) {
	$Errors = array();
	$db = db($user, $password);
	if (gettype($db)=='integer') {
		$Errors[0]=NoAuthorisation;
		return $Errors;
	}
	$Errors = VerifyOrderHeaderExists($OrderNo, sizeof($Errors), $Errors);
	if (sizeof($Errors)!=0) {
		return $Errors;
	}
	$SQL="SELECT * FROM salesorders WHERE orderno='".$OrderNo."'";
	$Result = DB_query($SQL);
	if (sizeof($Errors)==0) {
		$Errors[0]=0;
		$Errors[1]=DB_fetch_array($Result);
		return $Errors;
	} else {
		return $Errors;
	}
}

function GetSupplierInvoiceList($user, $password) {
	$Errors = array();
	$db = db($user, $password);
	if (gettype($db)=='integer') {
		$Errors[0]=NoAuthorisation;
		return $Errors;
	}
	$SQL = "SELECT orderno, 
				   orddate  
			FROM salesorders ORDER BY orddate DESC;";
	$Result = DB_query($SQL);
	$SalesTypeList = array();
	$i=0;
	while ($MyRow=DB_fetch_array($Result)) {
		$SalesTypeList[$i]=$MyRow[0];
		$i++;
	}
	$Errors[0]=0;
	$Errors[1]=$SalesTypeList;
	return $Errors;
}

/** This function takes a Invoice Number and returns an associative array containing
   the database record for that Order. If the Order Header ID doesn't exist
   then it returns an $Errors array.
*/
function GetSupplierInvoiceLineDetails($OrderNo, $user = '', $password = '') {
    $Errors = array();
    $db = db($user, $password);
    if (gettype($db)=='integer') {
        $Errors[0] = NoAuthorisation;
        return $Errors;
    }

    $Errors = VerifyOrderHeaderExists($OrderNo, sizeof($Errors), $Errors);
    if (sizeof($Errors) != 0) {
        return $Errors;
    }

    $SQL = "SELECT stkcode,
                    stockmaster.description,
                    stockmaster.longdescription,
                    stockmaster.controlled,
                    stockmaster.serialised,
                    stockmaster.volume,
                    stockmaster.grossweight,
                    stockmaster.units,
                    stockmaster.decimalplaces,
                    stockmaster.mbflag,
                    stockmaster.taxcatid,
                    stockmaster.discountcategory,
                    salesorderdetails.unitprice,
                    salesorderdetails.quantity,
                    salesorderdetails.discountpercent,
                    salesorderdetails.actualdispatchdate,
                    salesorderdetails.qtyinvoiced,
                    salesorderdetails.narrative,
                    salesorderdetails.orderlineno,
                    salesorderdetails.poline,
                    salesorderdetails.itemdue,
                    stockmaster.actualcost as standardcost
            FROM salesorderdetails INNER JOIN stockmaster
                ON salesorderdetails.stkcode = stockmaster.stockid
            WHERE salesorderdetails.orderno ='" . $OrderNo . "'
			AND salesorderdetails.quantity - salesorderdetails.qtyinvoiced >0
			ORDER BY salesorderdetails.orderlineno";

    $Result = api_DB_Query($SQL);
    $OrderLines = array();
	$i=0;
    while ($Row = DB_fetch_array($Result)) {
        $OrderLines[$i] = array(
            'stkcode'           => $Row['stkcode'],
            'description'       => $Row['description'],
            'longdescription'   => $Row['longdescription'],
            'quantity'          => $Row['quantity'],
            'unitprice'         => $Row['unitprice'],
            'discountpercent'   => $Row['discountpercent'],
            'qtyinvoiced'       => $Row['qtyinvoiced'],
            'itemdue'           => $Row['itemdue'],
            'standardcost'      => $Row['standardcost']
        );
        $i++;
    }
	$Errors[0]=0;
	$Errors[1]=$OrderLines;
	return $Errors;
}
