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
function InsertSupplierInvoice($SupplierInvoiceHeader, $SupplierInvoiceLine, $user, $password) {
	$Errors = array();
	$db = db($user, $password);
	if (gettype($db)=='integer') {
		$Errors[0]=NoAuthorisation;
		return $Errors;
	}

	foreach ($SupplierInvoiceHeader as $key => $value) {
		if (is_string($value)) {
			$SupplierInvoiceHeader[$key] = DB_escape_string($value);
		}
	}
	foreach ($SupplierInvoiceLine as $key => $value) {
		if (is_string($value)) {
			$SupplierInvoiceLine[$key] = DB_escape_string($value);
		}
	}

	$Errors=VerifySupplierNoExists($SupplierInvoiceHeader['supplierno'], sizeof($Errors), $Errors);
		return $SupplierInvoiceHeader['supplierno']; exit;

	if (isset($SupplierInvoiceHeader['trandate'])){
		$Errors=VerifyDateFormat($SupplierInvoiceHeader['trandate'], sizeof($Errors), $Errors);
	}
	if (isset($SupplierInvoiceHeader['deliverydate'])){
		$Errors=VerifyDateFormat($SupplierInvoiceHeader['deliverydate'], sizeof($Errors), $Errors);
	}
	return $SupplierInvoiceHeader['supplierno']; exit;
	/*
	CREATE TABLE IF NOT EXISTS api_supplier_invoice_drafts (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	draft_uuid CHAR(36) NOT NULL,
	created_by VARCHAR(20) NOT NULL,
	supplierid VARCHAR(10) NOT NULL,
	tran_date DATE NULL,
	due_date DATE NULL,
	supp_reference VARCHAR(20) NULL,
	ex_rate DECIMAL(18,10) NOT NULL DEFAULT 1.0000000000,
	comments TEXT NULL,
	tax_mode ENUM('AUTO','MANUAL') NOT NULL DEFAULT 'AUTO',
	currency CHAR(3) NOT NULL,
	curr_decimal_places INT NOT NULL DEFAULT 2,
	taxgroupid INT NOT NULL,
	local_tax_province INT NOT NULL,
	status ENUM('DRAFT','POSTED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
	posted_transno INT NULL,
	posted_supptrans_id BIGINT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE KEY uq_draft_uuid (draft_uuid),
	KEY idx_created_by_status (created_by, status),
	KEY idx_supplierid_status (supplierid, status)
	) ENGINE=InnoDB;

	CREATE TABLE IF NOT EXISTS api_supplier_invoice_lines (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	draft_uuid CHAR(36) NOT NULL,
	line_type ENUM('GRN','GL','SHIPMENT','CONTRACT','ASSET') NOT NULL,
	ref1 VARCHAR(50) NULL,
	ref2 VARCHAR(50) NULL,
	ref3 VARCHAR(50) NULL,
	description VARCHAR(255) NULL,
	qty DECIMAL(18,4) NULL,
	unit_price DECIMAL(18,4) NULL,
	amount DECIMAL(18,4) NULL,
	meta JSON NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY idx_draft_uuid (draft_uuid),
	CONSTRAINT fk_lines_draft FOREIGN KEY (draft_uuid)
		REFERENCES api_supplier_invoice_drafts (draft_uuid)
		ON DELETE CASCADE
	) ENGINE=InnoDB;

	CREATE TABLE IF NOT EXISTS api_supplier_invoice_taxes (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	draft_uuid CHAR(36) NOT NULL,
	taxauthid INT NOT NULL,
	taxglcode INT NOT NULL,
	tax_rate DECIMAL(18,8) NOT NULL DEFAULT 0,
	tax_on_tax TINYINT NOT NULL DEFAULT 0,
	calc_order INT NOT NULL DEFAULT 1,
	amount_supplier DECIMAL(18,4) NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	UNIQUE KEY uq_draft_tax (draft_uuid, taxauthid),
	KEY idx_draft_uuid (draft_uuid),
	CONSTRAINT fk_taxes_draft FOREIGN KEY (draft_uuid)
		REFERENCES api_supplier_invoice_drafts (draft_uuid)
		ON DELETE CASCADE
	) ENGINE=InnoDB;

	CREATE TABLE IF NOT EXISTS api_idempotency_keys (
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	idem_key VARCHAR(80) NOT NULL,
	created_by VARCHAR(20) NOT NULL,
	action VARCHAR(40) NOT NULL,
	response_json JSON NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY(id),
	UNIQUE KEY uq_idem (idem_key, created_by, action)
	) ENGINE=InnoDB;
	 */

	//declare(strict_types=1);

	/**
	 * Stateless Supplier Invoice XML-RPC API for webERP
	 * Auth: cookie session (must be logged-in); API stores drafts in MySQL.
	 */

	require_once(__DIR__ . '/../includes/session.php');
	require_once(__DIR__ . '/../includes/DefineSuppTransClass.php');
	require_once(__DIR__ . '/../includes/SQL_CommonFunctions.php');
	require_once(__DIR__ . '/../includes/StockFunctions.php');
	require_once(__DIR__ . '/../includes/GLFunctions.php');

	if (!isset($_SESSION['UserID']) || $_SESSION['UserID'] === '') {
		header('Content-Type: text/plain', true, 401);
		echo "Unauthorized (no session)";
		exit;
	}

	function ok(array $data = []): array { return ['ok'=>true,'data'=>$data]; }
	function fail(string $msg, array $extra=[]): array { return ['ok'=>false,'error'=>$msg,'extra'=>$extra]; }

	function uuidv4(): string {
		$data = random_bytes(16);
		$data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
		$data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	function require_draft_owned(string $draftUuid, bool $forUpdate=false): array {
		$lock = $forUpdate ? " FOR UPDATE" : "";
		$sql = "SELECT * FROM api_supplier_invoice_drafts
				WHERE draft_uuid='" . DB_escape_string($draftUuid) . "'
				AND created_by='" . DB_escape_string($_SESSION['UserID']) . "'
				AND status='DRAFT' " . $lock;
		$res = DB_query($sql, 'Draft fetch failed');
		if (DB_num_rows($res) !== 1) {
			return [null, fail("Draft not found or not owned", ['draft_uuid'=>$draftUuid])];
		}
		return [DB_fetch_array($res), null];
	}

	function idem_get(string $key, string $action): ?array {
		if ($key === '') return null;
		$sql = "SELECT response_json FROM api_idempotency_keys
				WHERE idem_key='" . DB_escape_string($key) . "'
				AND created_by='" . DB_escape_string($_SESSION['UserID']) . "'
				AND action='" . DB_escape_string($action) . "'";
		$res = DB_query($sql);
		if (DB_num_rows($res) === 1) {
			$row = DB_fetch_row($res);
			return json_decode($row[0], true);
		}
		return null;
	}
	function idem_store(string $key, string $action, array $response): void {
		if ($key === '') return;
		$sql = "INSERT INTO api_idempotency_keys (idem_key, created_by, action, response_json)
				VALUES ('" . DB_escape_string($key) . "',
						'" . DB_escape_string($_SESSION['UserID']) . "',
						'" . DB_escape_string($action) . "',
						'" . DB_escape_string(json_encode($response)) . "')";
		// ignore duplicate insert errors safely
		@DB_query($sql);
	}

	/**
	 * Build taxes using SuppTrans logic (but stateless):
	 * We instantiate SuppTrans temporarily and call GetTaxes().
	 */
	function build_taxes_for_draft(array $draft): array {
		$st = new SuppTrans();
		$st->SupplierID = $draft['supplierid'];
		$st->SupplierName = '';
		$st->CurrCode = $draft['currency'];
		$st->CurrDecimalPlaces = (int)$draft['curr_decimal_places'];
		$st->ExRate = (float)$draft['ex_rate'];
		$st->TaxGroup = (int)$draft['taxgroupid'];
		$st->LocalTaxProvince = (int)$draft['local_tax_province'];

		$st->GetTaxes();

		return $st->Taxes ?? [];
	}

	function draft_create(string $supplierID, string $idempotencyKey=''): array {
		$cached = idem_get($idempotencyKey, 'draft_create');
		if ($cached) return $cached;

		// Load supplier essentials (currency, decimals, tax group)
		$sql = "SELECT suppliers.supplierid, suppliers.currcode, currencies.decimalplaces, suppliers.taxgroupid
				FROM suppliers
				INNER JOIN currencies ON suppliers.currcode=currencies.currabrev
				WHERE suppliers.supplierid='" . DB_escape_string($supplierID) . "'";
		$res = DB_query($sql, 'Supplier lookup failed');
		if (DB_num_rows($res) !== 1) return fail("Supplier not found", ['supplierid'=>$supplierID]);
		$row = DB_fetch_array($res);

		// Local tax province from user location
		$res2 = DB_query("SELECT taxprovinceid FROM locations WHERE loccode='" . DB_escape_string($_SESSION['UserStockLocation']) . "'");
		if (DB_num_rows($res2) !== 1) return fail("UserStockLocation has no taxprovinceid", ['loc'=>$_SESSION['UserStockLocation']]);
		$prov = DB_fetch_row($res2)[0];

		$draftUuid = uuidv4();

		$sqlIns = "INSERT INTO api_supplier_invoice_drafts
				(draft_uuid, created_by, supplierid, ex_rate, tax_mode, currency, curr_decimal_places, taxgroupid, local_tax_province)
				VALUES
				('" . DB_escape_string($draftUuid) . "',
					'" . DB_escape_string($_SESSION['UserID']) . "',
					'" . DB_escape_string($row['supplierid']) . "',
					1.0000000000,
					'AUTO',
					'" . DB_escape_string($row['currcode']) . "',
					'" . (int)$row['decimalplaces'] . "',
					'" . (int)$row['taxgroupid'] . "',
					'" . (int)$prov . "')";
		DB_query($sqlIns, 'Draft insert failed', '', true);

		// Seed taxes table
		$draft = [
			'supplierid'=>$row['supplierid'],
			'currency'=>$row['currcode'],
			'curr_decimal_places'=>$row['decimalplaces'],
			'ex_rate'=>1,
			'taxgroupid'=>$row['taxgroupid'],
			'local_tax_province'=>$prov
		];
		$taxes = build_taxes_for_draft($draft);
		foreach ($taxes as $t) {
			$sqlT = "INSERT INTO api_supplier_invoice_taxes
					(draft_uuid, taxauthid, taxglcode, tax_rate, tax_on_tax, calc_order, amount_supplier)
					VALUES
					('" . DB_escape_string($draftUuid) . "',
					'" . (int)$t->TaxAuthID . "',
					'" . (int)$t->TaxGLCode . "',
					'" . (float)$t->TaxRate . "',
					'" . (int)$t->TaxOnTax . "',
					'" . (int)$t->TaxCalculationOrder . "',
					0)";
			DB_query($sqlT, 'Tax seed insert failed', '', true);
		}

		$out = ok(['draft_uuid'=>$draftUuid, 'supplierid'=>$supplierID, 'currency'=>$row['currcode']]);
		idem_store($idempotencyKey, 'draft_create', $out);
		return $out;
	}

	function draft_set_header(string $draftUuid, string $tranDate, string $suppRef, float $exRate, string $comments='', string $taxMode='AUTO'): array {
		DB_Txn_Begin();
		[$draft, $err] = require_draft_owned($draftUuid, true);
		if ($err) { DB_Txn_Rollback(); return $err; }

		// due date from payment terms (same logic as UI, derived from supplier record)
		$sql = "SELECT paymentterms.daysbeforedue, paymentterms.dayinfollowingmonth
				FROM suppliers
				INNER JOIN paymentterms ON suppliers.paymentterms=paymentterms.termsindicator
				WHERE suppliers.supplierid='" . DB_escape_string($draft['supplierid']) . "'";
		$res = DB_query($sql);
		$row = DB_fetch_array($res);

		// mimic Terms calc
		$DayInFollowingMonth = (int)$row['dayinfollowingmonth'];
		$DaysBeforeDue = (int)$row['daysbeforedue'];
		$due = CalcDueDate($tranDate, ($DaysBeforeDue===0 ? $DayInFollowingMonth : 0), ($DaysBeforeDue===0 ? 0 : $DaysBeforeDue));

		$taxMode = strtoupper($taxMode) === 'MANUAL' ? 'MANUAL' : 'AUTO';

		$upd = "UPDATE api_supplier_invoice_drafts
				SET tran_date='" . DB_escape_string($tranDate) . "',
					due_date='" . DB_escape_string(FormatDateForSQL($due)) . "',
					supp_reference='" . DB_escape_string($suppRef) . "',
					ex_rate='" . (float)$exRate . "',
					comments='" . DB_escape_string($comments) . "',
					tax_mode='" . DB_escape_string($taxMode) . "'
				WHERE draft_uuid='" . DB_escape_string($draftUuid) . "'";
		DB_query($upd, 'Header update failed', '', true);

		DB_Txn_Commit();
		return ok(['draft_uuid'=>$draftUuid, 'tran_date'=>$tranDate, 'due_date'=>$due, 'tax_mode'=>$taxMode]);
	}

	function draft_add_line(string $draftUuid, array $line): array {
		DB_Txn_Begin();
		[$draft, $err] = require_draft_owned($draftUuid, true);
		if ($err) { DB_Txn_Rollback(); return $err; }

		$type = strtoupper((string)($line['line_type'] ?? ''));
		$allowed = ['GRN','GL','SHIPMENT','CONTRACT','ASSET'];
		if (!in_array($type, $allowed, true)) { DB_Txn_Rollback(); return fail("Invalid line_type"); }

		$ref1 = (string)($line['ref1'] ?? null);
		$ref2 = (string)($line['ref2'] ?? null);
		$ref3 = (string)($line['ref3'] ?? null);
		$desc = (string)($line['description'] ?? null);
		$qty  = isset($line['qty']) ? (float)$line['qty'] : null;
		$unit = isset($line['unit_price']) ? (float)$line['unit_price'] : null;
		$amt  = isset($line['amount']) ? (float)$line['amount'] : null;
		$meta = isset($line['meta']) ? json_encode($line['meta']) : null;

		// GRN safety check (optional but strongly recommended)
		if ($type === 'GRN') {
			// Expect ref1 = grnno, qty = qty_to_invoice
			$grnno = (int)$ref1;
			$res = DB_query("SELECT qtyrecd, quantityinv, supplierid FROM grns
							WHERE grnno='" . (int)$grnno . "'
							AND supplierid='" . DB_escape_string($draft['supplierid']) . "'");
			if (DB_num_rows($res) !== 1) { DB_Txn_Rollback(); return fail("GRN not found for supplier", ['grnno'=>$grnno]); }
			$r = DB_fetch_array($res);
			$available = (float)$r['qtyrecd'] - (float)$r['quantityinv'];
			if ($qty === null || $qty <= 0 || $qty > $available) {
				DB_Txn_Rollback();
				return fail("Invalid GRN qty (exceeds available)", ['available'=>$available, 'requested'=>$qty]);
			}
		}

		$sql = "INSERT INTO api_supplier_invoice_lines
				(draft_uuid, line_type, ref1, ref2, ref3, description, qty, unit_price, amount, meta)
				VALUES
				('" . DB_escape_string($draftUuid) . "',
				'" . DB_escape_string($type) . "',
				" . ($ref1!=='' ? "'" . DB_escape_string($ref1) . "'" : "NULL") . ",
				" . ($ref2!=='' ? "'" . DB_escape_string($ref2) . "'" : "NULL") . ",
				" . ($ref3!=='' ? "'" . DB_escape_string($ref3) . "'" : "NULL") . ",
				" . ($desc!=='' ? "'" . DB_escape_string($desc) . "'" : "NULL") . ",
				" . ($qty!==null ? "'" . $qty . "'" : "NULL") . ",
				" . ($unit!==null ? "'" . $unit . "'" : "NULL") . ",
				" . ($amt!==null ? "'" . $amt . "'" : "NULL") . ",
				" . ($meta!==null ? "'" . DB_escape_string($meta) . "'" : "NULL") . ")";
		DB_query($sql, 'Line insert failed', '', true);

		DB_Txn_Commit();
		return ok(['draft_uuid'=>$draftUuid, 'added'=>$type]);
	}

	function draft_compute_totals(string $draftUuid): array {
		[$draft, $err] = require_draft_owned($draftUuid, false);
		if ($err) return $err;

		// Sum supplier-currency base amount
		$res = DB_query("SELECT line_type, qty, unit_price, amount, meta
						FROM api_supplier_invoice_lines
						WHERE draft_uuid='" . DB_escape_string($draftUuid) . "'");
		$ov = 0.0;
		while ($l = DB_fetch_array($res)) {
			$type = $l['line_type'];
			if ($type === 'GRN') {
				// qty * unit_price (in supplier currency)
				$ov += ((float)$l['qty'] * (float)$l['unit_price']);
			} elseif ($type === 'GL' || $type === 'SHIPMENT' || $type === 'CONTRACT' || $type === 'ASSET') {
				$ov += (float)$l['amount'];
			}
		}
		$ov = round($ov, (int)$draft['curr_decimal_places']);

		// Tax calc
		$taxTotal = 0.0;

		if ($draft['tax_mode'] === 'MANUAL') {
			$rt = DB_query("SELECT amount_supplier FROM api_supplier_invoice_taxes WHERE draft_uuid='" . DB_escape_string($draftUuid) . "'");
			while ($t = DB_fetch_row($rt)) $taxTotal += (float)$t[0];
			$taxTotal = round($taxTotal, (int)$draft['curr_decimal_places']);
		} else {
			// AUTO — compute using stored tax_rate/tax_on_tax order
			$rt = DB_query("SELECT id, tax_rate, tax_on_tax, calc_order
							FROM api_supplier_invoice_taxes
							WHERE draft_uuid='" . DB_escape_string($draftUuid) . "'
							ORDER BY calc_order ASC");
			$computed = [];
			$running = 0.0;
			while ($t = DB_fetch_array($rt)) {
				$rate = (float)$t['tax_rate'];
				$onTax = (int)$t['tax_on_tax'] === 1;
				$amt = $rate * ($onTax ? ($ov + $running) : $ov);
				$amt = round($amt, (int)$draft['curr_decimal_places']);
				$computed[] = ['id'=>(int)$t['id'], 'amt'=>$amt];
				$running += $amt;
			}
			DB_Txn_Begin();
			foreach ($computed as $c) {
				DB_query("UPDATE api_supplier_invoice_taxes
						SET amount_supplier='" . (float)$c['amt'] . "'
						WHERE id='" . (int)$c['id'] . "'", 'Tax update failed', '', true);
				$taxTotal += (float)$c['amt'];
			}
			DB_Txn_Commit();
			$taxTotal = round($taxTotal, (int)$draft['curr_decimal_places']);
		}

		$invoiceTotal = round($ov + $taxTotal, (int)$draft['curr_decimal_places']);

		return ok([
			'draft_uuid'=>$draftUuid,
			'OvAmount'=>$ov,
			'TaxTotal'=>$taxTotal,
			'InvoiceTotal'=>$invoiceTotal,
			'Currency'=>$draft['currency']
		]);
	}

	function draft_set_tax_manual(string $draftUuid, int $taxAuthId, float $amountSupplier): array {
		DB_Txn_Begin();
		[$draft, $err] = require_draft_owned($draftUuid, true);
		if ($err) { DB_Txn_Rollback(); return $err; }

		// set manual mode
		DB_query("UPDATE api_supplier_invoice_drafts SET tax_mode='MANUAL' WHERE draft_uuid='" . DB_escape_string($draftUuid) . "'", 'Mode update failed', '', true);

		$sql = "UPDATE api_supplier_invoice_taxes
				SET amount_supplier='" . (float)$amountSupplier . "'
				WHERE draft_uuid='" . DB_escape_string($draftUuid) . "'
				AND taxauthid='" . (int)$taxAuthId . "'";
		DB_query($sql, 'Tax manual set failed', '', true);

		DB_Txn_Commit();
		return ok(['draft_uuid'=>$draftUuid,'taxauthid'=>$taxAuthId,'amount_supplier'=>$amountSupplier,'tax_mode'=>'MANUAL']);
	}

	function post_invoice(string $draftUuid, string $idempotencyKey=''): array {
		$cached = idem_get($idempotencyKey, 'post_invoice');
		if ($cached) return $cached;

		DB_Txn_Begin();
		[$draft, $err] = require_draft_owned($draftUuid, true);
		if ($err) { DB_Txn_Rollback(); return $err; }

		$tot = draft_compute_totals($draftUuid);
		if (!$tot['ok']) { DB_Txn_Rollback(); return $tot; }
		$Ov = (float)$tot['data']['OvAmount'];
		$TaxTotal = (float)$tot['data']['TaxTotal'];

		// Validate header fields
		if (empty($draft['supp_reference'])) { DB_Txn_Rollback(); return fail("Missing supp_reference. Call draft_set_header."); }
		if (empty($draft['tran_date'])) { DB_Txn_Rollback(); return fail("Missing tran_date. Call draft_set_header."); }
		if ((float)$draft['ex_rate'] <= 0) { DB_Txn_Rollback(); return fail("Invalid ex_rate"); }

		// Duplicate invoice ref check
		$res = DB_query("SELECT COUNT(*) FROM supptrans
						WHERE supplierno='" . DB_escape_string($draft['supplierid']) . "'
						AND suppreference='" . DB_escape_string($draft['supp_reference']) . "'", 'Dup check failed');
		$row = DB_fetch_row($res);
		if ((int)$row[0] > 0) { DB_Txn_Rollback(); return fail("Duplicate supplier invoice reference"); }

		$InvoiceNo = GetNextTransNo(20);
		$PeriodNo = GetPeriod($draft['tran_date']);
		$SQLInvoiceDate = FormatDateForSQL($draft['tran_date']);
		$DueDateSQL = $draft['due_date'] ?? $SQLInvoiceDate;

		// Insert supptrans
		$sql = "INSERT INTO supptrans (transno, type, supplierno, suppreference, trandate, duedate, ovamount, ovgst, rate, transtext, inputdate)
				VALUES ('" . (int)$InvoiceNo . "', 20,
						'" . DB_escape_string($draft['supplierid']) . "',
						'" . DB_escape_string($draft['supp_reference']) . "',
						'" . $SQLInvoiceDate . "',
						'" . DB_escape_string($DueDateSQL) . "',
						'" . (float)$Ov . "',
						'" . (float)$TaxTotal . "',
						'" . (float)$draft['ex_rate'] . "',
						'" . DB_escape_string($draft['comments'] ?? '') . "',
						CURRENT_DATE)";
		DB_query($sql, 'supptrans insert failed', '', true);
		$SuppTransID = DB_Last_Insert_ID('supptrans','id');

		// Taxes
		$rt = DB_query("SELECT taxauthid, amount_supplier FROM api_supplier_invoice_taxes WHERE draft_uuid='" . DB_escape_string($draftUuid) . "'");
		while ($t = DB_fetch_array($rt)) {
			DB_query("INSERT INTO supptranstaxes (supptransid, taxauthid, taxamount)
					VALUES ('" . (int)$SuppTransID . "',
							'" . (int)$t['taxauthid'] . "',
							'" . (float)$t['amount_supplier'] . "')", 'supptranstaxes insert failed', '', true);
		}

		// Apply lines (GRN updates + mapping; others can be extended similarly)
		$lines = DB_query("SELECT * FROM api_supplier_invoice_lines WHERE draft_uuid='" . DB_escape_string($draftUuid) . "' ORDER BY id ASC");
		while ($l = DB_fetch_array($lines)) {
			if ($l['line_type'] === 'GRN') {
				$grnNo = (int)$l['ref1'];
				$qtyInv = (float)$l['qty'];
				$unitPrice = (float)$l['unit_price']; // supplier currency

				// Lock GRN row to prevent race
				$grnRes = DB_query("SELECT qtyrecd, quantityinv FROM grns WHERE grnno='" . (int)$grnNo . "' FOR UPDATE");
				$grnRow = DB_fetch_array($grnRes);
				$available = (float)$grnRow['qtyrecd'] - (float)$grnRow['quantityinv'];
				if ($qtyInv > $available) { DB_Txn_Rollback(); return fail("GRN qty no longer available", ['grnno'=>$grnNo,'available'=>$available]); }

				DB_query("UPDATE grns SET quantityinv = quantityinv + " . $qtyInv . " WHERE grnno='" . (int)$grnNo . "'", 'grns update failed', '', true);
				DB_query("INSERT INTO suppinvstogrn VALUES ('" . (int)$InvoiceNo . "', '" . (int)$grnNo . "')", 'suppinvstogrn insert failed', '', true);

				// If you also want to update purchorderdetails.qtyinvoiced, store podetailitem in meta and apply update here.
			}
			// Extend here for GL/Shipment/Contract/Asset postings as needed (same as your UI logic).
		}

		// Mark draft posted
		DB_query("UPDATE api_supplier_invoice_drafts
				SET status='POSTED', posted_transno='" . (int)$InvoiceNo . "', posted_supptrans_id='" . (int)$SuppTransID . "'
				WHERE draft_uuid='" . DB_escape_string($draftUuid) . "'", 'Draft mark posted failed', '', true);

		DB_Txn_Commit();

		$out = ok(['draft_uuid'=>$draftUuid, 'InvoiceNo'=>$InvoiceNo, 'SuppTransID'=>$SuppTransID]);
		idem_store($idempotencyKey, 'post_invoice', $out);
		return $out;
	}

	/* ---------------- XML-RPC registration ---------------- */

	$server = xmlrpc_server_create();

	xmlrpc_server_register_method($server, "supplier_invoice.draft_create", function($method, $params) {
		return draft_create((string)($params[0] ?? ''), (string)($params[1] ?? ''));
	});

	xmlrpc_server_register_method($server, "supplier_invoice.draft_set_header", function($method, $params) {
		return draft_set_header(
			(string)($params[0] ?? ''),
			(string)($params[1] ?? ''),
			(string)($params[2] ?? ''),
			(float)($params[3] ?? 1),
			(string)($params[4] ?? ''),
			(string)($params[5] ?? 'AUTO')
		);
	});

	xmlrpc_server_register_method($server, "supplier_invoice.draft_add_line", function($method, $params) {
		return draft_add_line((string)($params[0] ?? ''), (array)($params[1] ?? []));
	});

	xmlrpc_server_register_method($server, "supplier_invoice.draft_compute_totals", function($method, $params) {
		return draft_compute_totals((string)($params[0] ?? ''));
	});

	xmlrpc_server_register_method($server, "supplier_invoice.draft_set_tax_manual", function($method, $params) {
		return draft_set_tax_manual((string)($params[0] ?? ''), (int)($params[1] ?? 0), (float)($params[2] ?? 0));
	});

	xmlrpc_server_register_method($server, "supplier_invoice.post", function($method, $params) {
		return post_invoice((string)($params[0] ?? ''), (string)($params[1] ?? ''));
	});

	$request = file_get_contents('php://input');
	$response = xmlrpc_server_call_method($server, $request, null, ['encoding' => 'utf-8']);
	header('Content-Type: text/xml; charset=utf-8');
	echo $response;

	xmlrpc_server_destroy($server);

} /*end of process invoice */