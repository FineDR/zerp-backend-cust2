<?php

if (!isset($PathPrefix)) {
	header('Location: ../../');
	exit();
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
	$SQL = "SELECT defaultlocation FROM www_users WHERE userid = '" . $user . "'";

	return $SQL;

	$Result = api_DB_query($SQL);
	$MyRow = DB_fetch_array($Result);
	if($MyRow['defaultlocation']==''){
		$MyRow['defaultlocation'] = $SupplierHeader['userlocation'];
	}

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

	/*
		$_SESSION['SuppTrans']->GetTaxes();

		$_SESSION['SuppTrans']->GLLink_Creditors = $_SESSION['CompanyRecord']['gllink_creditors'];
		$_SESSION['SuppTrans']->GRNAct = $_SESSION['CompanyRecord']['grnact'];
		$_SESSION['SuppTrans']->CreditorsAct = $_SESSION['CompanyRecord']['creditorsact'];

		$_SESSION['SuppTrans']->InvoiceOrCredit = 'Invoice';
    */
	$InvoiceOrCredit = 'Invoice';

   //mpaka hapa tuna kosa vifuatavyo
   //invoice totals
   #1 insert the Invoice header in the supplier Trans

		/*Now insert the invoice into the SuppTrans table*/
				/* SQL to process the postings for purchase invoice */
		/*Start an SQL transaction */

		DB_Txn_Begin();
		/*Get the next transaction number for internal purposes and the period to post GL transactions in based on the invoice date*/
		$InvoiceNo = GetNextTransNo(20);
		$PeriodNo = GetPeriod($SupplierHeader['trandate']);
		$SQLInvoiceDate = FormatDateForSQL($SupplierHeader['trandate']);

		//return $SupplierHeader['gllink_creditors'];

		if ($SupplierHeader['gllink_creditors'] == 1) {
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
            //return FormatDateForSQL($SupplierHeader['trandate']);
			foreach ($SupplierInvoiceLine as $key => $Value) {
				$SupplierHeader[$key] = DB_escape_string($Value);

							//	foreach ($SupplierInvoiceLine as $EnteredGLCode => $Value) {
						//	$SupplierHeader[$key] = DB_escape_string($Value);
					//	}
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

				//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added because');

			    //	$Result = DB_query($SQL, $ErrMsg, '', true);
				//InsertGLTags($EnteredGLCode->Tag);

								//return $Errors;

				/* Now the control account */
				# get the company creditors accounts
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
