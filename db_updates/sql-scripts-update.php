<?php

The column categoryid in the table stockcategory could not be changed to type varchar(6) and returned error number 1833
ALTER TABLE stockcategory CHANGE COLUMN categoryid categoryid varchar(6) NOT NULL; 

Solution
1.0 Drop the foreign key
ALTER TABLE contracts DROP FOREIGN KEY contracts_ibfk_2;

2.0 Make sure contracts.categoryid matches the new type
ALTER TABLE contracts MODIFY COLUMN categoryid VARCHAR(6) NOT NULL;

3.0 Change the column in stockcategory
ALTER TABLE stockcategory CHANGE COLUMN categoryid categoryid VARCHAR(6) NOT NULL;

4.0 Recreate the foreign key
ALTER TABLE contracts
  ADD CONSTRAINT contracts_ibfk_2
  FOREIGN KEY (categoryid)
  REFERENCES stockcategory (categoryid)
  ON UPDATE CASCADE
  ON DELETE RESTRICT;

ALTER TABLE contracts ADD CONSTRAINT contracts_ibfk_2 FOREIGN KEY (categoryid) REFERENCES stockcategory (categoryid) ON UPDATE CASCADE ON DELETE RESTRICT;

5.0 Check for any other FKs to stockcategory
SELECT 
    TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME
FROM
    information_schema.KEY_COLUMN_USAGE
WHERE
    REFERENCED_TABLE_NAME = 'stockcategory'
    AND REFERENCED_COLUMN_NAME = 'categoryid';

6.0 Update SalesTypes
insert into salestypes values ('01', 'Retail');
insert into salestypes values ('02', 'Whole sale');

7.0 Update Payment Terms
insert into paymentterms values (1,'Net 7',0,7);

8.0 Grant Priviledges
grant all privileges on zerp_backend.* to zerp@localhost;
flush privileges;

9.0 How to MySQL: Reset the Next Value in AUTO_INCREMENT column ?
ALTER TABLE taxauthorities AUTO_INCREMENT = 1;

ALTER TABLE taxgroups AUTO_INCREMENT = 1;

ALTER TABLE taxcategories AUTO_INCREMENT = 1;

ALTER TABLE debtortype AUTO_INCREMENT = 1;

ALTER TABLE suppliertype AUTO_INCREMENT = 1;

ALTER TABLE debtorsmaster to have debtorno to 20 VARCHAR

// DROP Constraints
ALTER TABLE custitem DROP FOREIGN KEY ` custitem _ibfk_2`;
ALTER TABLE custbranch DROP FOREIGN KEY `custbranch_ibfk_1`;
ALTER TABLE contracts DROP FOREIGN KEY `contracts_ibfk_1`;
ALTER TABLE orderdeliverydifferenceslog DROP FOREIGN KEY `orderdeliverydifferenceslog_ibfk_2`;
ALTER TABLE recurringsalesorders DROP FOREIGN KEY `recurringsalesorders_ibfk_1`;
ALTER TABLE salesorders DROP FOREIGN KEY `salesorders_ibfk_1`;


//CHANGE Columns
ALTER TABLE debtorsmaster CHANGE COLUMN debtorno debtorno VARCHAR(20) NOT NULL;
ALTER TABLE custbranch CHANGE COLUMN debtorno debtorno VARCHAR(20) NOT NULL;
ALTER TABLE custitem CHANGE COLUMN debtorno debtorno VARCHAR(20) NOT NULL;
ALTER TABLE contracts CHANGE COLUMN debtorno debtorno VARCHAR(20) NOT NULL;
ALTER TABLE orderdeliverydifferenceslog CHANGE COLUMN debtorno debtorno VARCHAR(20) NOT NULL;
ALTER TABLE recurringsalesorders CHANGE COLUMN debtorno debtorno VARCHAR(20) NOT NULL;
ALTER TABLE salesorders CHANGE COLUMN debtorno debtorno VARCHAR(20) NOT NULL;
ALTER TABLE debtortrans CHANGE COLUMN debtorno debtorno VARCHAR(20) NOT NULL;
ALTER TABLE debtortrans CHANGE COLUMN branchcode branchcode VARCHAR(20) NOT NULL;
ALTER TABLE custbranch CHANGE COLUMN branchcode branchcode VARCHAR(20) NOT NULL;
ALTER TABLE salesorders CHANGE COLUMN branchcode branchcode VARCHAR(20) NOT NULL;
ALTER TABLE recurringsalesorders CHANGE COLUMN branchcode branchcode VARCHAR(20) NOT NULL;
ALTER TABLE orderdeliverydifferenceslog CHANGE COLUMN branch branchcode VARCHAR(20) NOT NULL;
ALTER TABLE contracts CHANGE COLUMN branchcode branchcode VARCHAR(20) NOT NULL;

//ADD Constraints
ALTER TABLE salesorders
ADD CONSTRAINT `salesorders_ibfk_1`
FOREIGN KEY (`branchcode`, `debtorno`)
REFERENCES `custbranch` (`branchcode`, `debtorno`);

ALTER TABLE recurringsalesorders
ADD CONSTRAINT `recurringsalesorders_ibfk_1`
FOREIGN KEY (`branchcode`, `debtorno`)
REFERENCES `custbranch` (`branchcode`, `debtorno`);

ALTER TABLE orderdeliverydifferenceslog
ADD CONSTRAINT `orderdeliverydifferenceslog_ibfk_2`
FOREIGN KEY (`branch`, `debtorno`) 
REFERENCES `custbranch` (`branchcode`, `debtorno`);

ALTER TABLE contracts
ADD CONSTRAINT `contracts_ibfk_1`
FOREIGN KEY (`branchcode`, `debtorno`)
REFERENCES `custbranch` (`branchcode`, `debtorno`);

ALTER TABLE custbranch
ADD CONSTRAINT `custbranch_ibfk_1`
FOREIGN KEY (`debtorno`)
REFERENCES `debtorsmaster` (`debtorno`);

ALTER TABLE custitem
ADD CONSTRAINT ` custitem _ibfk_2`
FOREIGN KEY (`debtorno`)
REFERENCES `debtorsmaster` (`debtorno`);

// Grant TRIGGER command
GRANT TRIGGER ON zerp_backend.* TO mum@localhost;
FLUSH PRIVILEGES;



//Scripts to update the database
ALTER TABLE salesorders MODIFY deladd6 VARCHAR(32);

INSERT INTO debtortrans (debtorno, branchcode, trandate, transno, type, ovamount, ovgst, salesperson, prd)
VALUES ('NS1369/005', 'NS1369/005','', '38', '10', '5000', '150', 'SP001', '');





MUM2025-06-12345
INSERT INTO debtorsmaster (debtorno, name, address1, address2, address3, address4, address5,
                address6, currcode, salestype, paymentterms, clientsince)&#10;&#9; VALUES (&apos;&quot;&quot;,
               Ludovick Rwabiz,1,2,3,4,5,
               6,TZS,01,2,2025-11-27&apos;)

INSERT INTO debtorsmaster (debtorno, name, address1, address2, address3, address4, address5,
                address6, currcode, salestype, paymentterms, clientsince) VALUES (&quot;,Ludovick
                Rwabiz&quot;,1&quot;, &quot;2&quot;, &quot;3&quot;, &quot;4&quot;, &quot;5&quot;, &quot;6&quot;,
                &quot;TZS&quot;, &quot;01&quot;, &quot;2&quot;, &quot;2025-11-27&quot;)

UPDATE debtorsmaster SET debtorno=&quot;RAH&quot;, name=&quot;Ludovick Rwabiz&quot;, address1=&quot;1&quot;, address2=&quot;2&quot;, address3=&quot;3&quot;, address4=&quot;4&quot;, address5=&quot;5&quot;, address6=&quot;TZS&quot;, currcode=&quot;TZS&quot;, salestype=&quot;01&quot;, paymentterms=&quot;2&quot;, edireference=&quot;TZS&quot;, clientsince=&quot;2025-11-27&quot; WHERE debtorno=&apos;RAH&apos;<

CONSTRAINT ` custitem _ibfk_2` FOREIGN KEY (`debtorno`) REFERENCES `debtorsmaster` (`debtorno`)

$GetStockCatProperty_sig = array(
	array(Value::$xmlrpcValue, Value::$xmlrpcString, Value::$xmlrpcString),
	array(Value::$xmlrpcValue, Value::$xmlrpcString, Value::$xmlrpcString, Value::$xmlrpcString, Value::$xmlrpcString));
$GetStockCatProperty_doc = apiBuildDocHTML($Description, $Parameter, $ReturnValue);



INSERT INTO supptrans (transno,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;type,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;supplierno,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;suppreference,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;trandate,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;duedate,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;ovamount,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;ovgst,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;rate,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;transtext,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;inputdate)&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;VALUES (&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&apos;5&apos;,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;20 ,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&apos;104824986&apos;,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&apos;&apos;,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&apos;2025-12-03&apos;,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&apos;2025-12-03&apos;,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&apos;11000&apos;,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&apos;0&apos;,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&apos;1&apos;,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&apos;&apos;,&#10;&#9;&#9;&#9;&#9;&#9;&#9;&#9;&#9;CURRENT_DATE)


SELECT debtortrans.trandate, debtortrans.ovamount, debtortrans.ovdiscount, debtortrans.ovfreight, debtortrans.ovgst, debtortrans.rate, 
debtortrans.invtext, debtortrans.consignment, debtortrans.packages, debtorsmaster.name, debtorsmaster.address1, debtorsmaster.address2, 
debtorsmaster.address3, debtorsmaster.address4, debtorsmaster.address5, debtorsmaster.address6, debtorsmaster.currcode, 
debtorsmaster.invaddrbranch, debtorsmaster.taxref, debtorsmaster.language_id, paymentterms.terms, paymentterms.dayinfollowingmonth, 
paymentterms.daysbeforedue, locations.locationname, 
shippers.shippername, custbranch.brname, custbranch.braddress1, custbranch.braddress2, custbranch.braddress3, custbranch.braddress4, 
custbranch.braddress5, custbranch.braddress6, custbranch.brpostaddr1, custbranch.brpostaddr2, custbranch.brpostaddr3, custbranch.brpostaddr4, 
custbranch.brpostaddr5, custbranch.brpostaddr6, custbranch.salesman, salesman.salesmanname, debtortrans.debtorno, debtortrans.branchcode, 
currencies.decimalplaces FROM debtortrans INNER JOIN debtorsmaster ON debtortrans.debtorno=debtorsmaster.debtorno INNER JOIN custbranch ON 
debtortrans.debtorno=custbranch.debtorno AND debtortrans.branchcode=custbranch.branchcode INNER JOIN shippers ON debtortrans.shipvia=shippers.shipper_id 
		INNER JOIN salesman ON custbranch.salesman=salesman.salesmancode 
		INNER JOIN locationusers ON locationusers.loccode=locations.loccode 
		AND 
		locationusers.userid='amran' 
		AND 
		locationusers.canview=1 
		INNER JOIN paymentterms ON debtorsmaster.paymentterms=paymentterms.termsindicator 
		INNER JOIN currencies ON debtorsmaster.currcode=currencies.currabrev 
		WHERE debtortrans.type=10 
		AND 
		debtortrans.transno='2'

SELECT debtortrans.trandate, debtortrans.ovamount, debtortrans.ovdiscount, debtortrans.ovfreight, debtortrans.ovgst, debtortrans.rate, debtortrans.invtext, debtortrans.consignment, debtortrans.packages, debtorsmaster.name, debtorsmaster.address1, debtorsmaster.address2, debtorsmaster.address3, debtorsmaster.address4, debtorsmaster.address5, debtorsmaster.address6, debtorsmaster.currcode, debtorsmaster.invaddrbranch, debtorsmaster.taxref, debtorsmaster.language_id, paymentterms.terms, paymentterms.dayinfollowingmonth, paymentterms.daysbeforedue, salesorders.deliverto, salesorders.deladd1, salesorders.deladd2, salesorders.deladd3, salesorders.deladd4, salesorders.deladd5, salesorders.deladd6, salesorders.customerref, salesorders.orderno, salesorders.orddate, locations.locationname, shippers.shippername, custbranch.brname, custbranch.braddress1, custbranch.braddress2, custbranch.braddress3, custbranch.braddress4, custbranch.braddress5, custbranch.braddress6, custbranch.brpostaddr1, custbranch.brpostaddr2, custbranch.brpostaddr3, custbranch.brpostaddr4, custbranch.brpostaddr5, custbranch.brpostaddr6, custbranch.salesman, salesman.salesmanname, debtortrans.debtorno, debtortrans.branchcode, currencies.decimalplaces FROM debtortrans INNER JOIN debtorsmaster ON debtortrans.debtorno=debtorsmaster.debtorno INNER JOIN custbranch ON debtortrans.debtorno=custbranch.debtorno AND debtortrans.branchcode=custbranch.branchcode INNER JOIN salesorders ON debtortrans.order_ = salesorders.orderno INNER JOIN shippers ON debtortrans.shipvia=shippers.shipper_id INNER JOIN salesman ON custbranch.salesman=salesman.salesmancode INNER JOIN locations ON salesorders.fromstkloc=locations.loccode INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='amran' AND locationusers.canview=1 INNER JOIN paymentterms ON debtorsmaster.paymentterms=paymentterms.termsindicator INNER JOIN currencies ON debtorsmaster.currcode=currencies.currabrev WHERE debtortrans.type=10 AND debtortrans.transno='3'
SELECT debtortrans.trandate, debtortrans.ovamount, debtortrans.ovdiscount, debtortrans.ovfreight, debtortrans.ovgst, debtortrans.rate, debtortrans.invtext, debtortrans.consignment, debtortrans.packages, debtorsmaster.name, debtorsmaster.address1, debtorsmaster.address2, debtorsmaster.address3, debtorsmaster.address4, debtorsmaster.address5, debtorsmaster.address6, debtorsmaster.currcode, debtorsmaster.invaddrbranch, debtorsmaster.taxref, debtorsmaster.language_id, paymentterms.terms, paymentterms.dayinfollowingmonth, paymentterms.daysbeforedue, salesorders.deliverto, salesorders.deladd1, salesorders.deladd2, salesorders.deladd3, salesorders.deladd4, salesorders.deladd5, salesorders.deladd6, salesorders.customerref, salesorders.orderno, salesorders.orddate, locations.locationname, shippers.shippername, custbranch.brname, custbranch.braddress1, custbranch.braddress2, custbranch.braddress3, custbranch.braddress4, custbranch.braddress5, custbranch.braddress6, custbranch.brpostaddr1, custbranch.brpostaddr2, custbranch.brpostaddr3, custbranch.brpostaddr4, custbranch.brpostaddr5, custbranch.brpostaddr6, custbranch.salesman, salesman.salesmanname, debtortrans.debtorno, debtortrans.branchcode, currencies.decimalplaces FROM debtortrans INNER JOIN debtorsmaster ON debtortrans.debtorno=debtorsmaster.debtorno INNER JOIN custbranch ON debtortrans.debtorno=custbranch.debtorno AND debtortrans.branchcode=custbranch.branchcode INNER JOIN salesorders ON debtortrans.order_ = salesorders.orderno INNER JOIN shippers ON debtortrans.shipvia=shippers.shipper_id INNER JOIN salesman ON custbranch.salesman=salesman.salesmancode INNER JOIN locations ON salesorders.fromstkloc=locations.loccode INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='amran' AND locationusers.canview=1 INNER JOIN paymentterms ON debtorsmaster.paymentterms=paymentterms.termsindicator INNER JOIN currencies ON debtorsmaster.currcode=currencies.currabrev WHERE debtortrans.type=10 AND debtortrans.transno='124'

select orderno, debtorno,branchcode,orddate,ordertype,salesperson,quotation,deliverydate,fromstkloc from salesorders;

INSERT INTO salesorders (orderno,debtorno,branchcode,orddate,ordertype,salesperson,deliverydate,fromstkloc,shipvia) VALUES ('120','NS1369/005','NS1369/005','2025-12-23','1','1','2025-12-23','3','2');

INSERT INTO gltrans VALUES(null,10,'179',0,'2025-12-17','1','1100','Invoice for -NS1369/005 Total - 5000','5000',0,'',1)

Tausi Credential:                                                   Username: 19741117-11485-00001-29                                             Password: ZZalongwa@2025

SELECT debtortrans.trandate, debtortrans.ovamount, debtortrans.ovdiscount, debtortrans.ovfreight, debtortrans.ovgst, debtortrans.rate, debtortrans.invtext, debtortrans.consignment, debtortrans.packages, debtorsmaster.name, debtorsmaster.address1, debtorsmaster.address2, debtorsmaster.address3, debtorsmaster.address4, debtorsmaster.address5, debtorsmaster.address6, debtorsmaster.currcode, debtorsmaster.invaddrbranch, debtorsmaster.taxref, debtorsmaster.language_id, paymentterms.terms, paymentterms.dayinfollowingmonth, paymentterms.daysbeforedue, salesorders.deliverto, salesorders.deladd1, salesorders.deladd2, salesorders.deladd3, salesorders.deladd4, salesorders.deladd5, salesorders.deladd6, salesorders.customerref, salesorders.orderno, salesorders.orddate, locations.locationname, shippers.shippername, custbranch.brname, custbranch.braddress1, custbranch.braddress2, custbranch.braddress3, custbranch.braddress4, custbranch.braddress5, custbranch.braddress6, custbranch.brpostaddr1, custbranch.brpostaddr2, custbranch.brpostaddr3, custbranch.brpostaddr4, custbranch.brpostaddr5, custbranch.brpostaddr6, custbranch.salesman, salesman.salesmanname, debtortrans.debtorno, debtortrans.branchcode, currencies.decimalplaces FROM debtortrans INNER JOIN debtorsmaster ON debtortrans.debtorno=debtorsmaster.debtorno INNER JOIN custbranch ON debtortrans.debtorno=custbranch.debtorno AND debtortrans.branchcode=custbranch.branchcode INNER JOIN shippers ON debtortrans.shipvia=shippers.shipper_id INNER JOIN salesman ON custbranch.salesman=salesman.salesmancode INNER JOIN locations ON salesorders.fromstkloc=locations.loccode INNER JOIN locationusers ON locationusers.loccode=locations.loccode AND locationusers.userid='amran' AND locationusers.canview=1 INNER JOIN paymentterms ON debtorsmaster.paymentterms=paymentterms.termsindicator INNER JOIN currencies ON debtorsmaster.currcode=currencies.currabrev WHERE debtortrans.type=10 AND debtortrans.transno='1';

<?xml version="1.0"?>
<methodCall>
    <methodName>weberp.xmlrpc_InsertSupplierInvoice</methodName>
    <params>
        <param>
             <value>
                <struct>
                    <member>
                        <name>invoiceType</name>
                        <value><string>GL</string></value>
                    </member>
                    <member>
                        <name>SupplierID</name>
                        <value><string>GL</string></value>
                    </member>
                    <member>
                        <name>InvoiceNo</name>
                        <value><string>GL</string></value>
                    </member>
                    <member>
                        <name>TransDate</name>
                        <value><string>GL</string></value>
                    </member>
                    <member>
                        <name>TotalInvoice</name>
                        <value><string>GL</string></value>
                    </member>
                    <member>
                        <name>TotalTax</name>
                        <value><string>GL</string></value>
                    </member>
                    <member>
                        <name>narrative</name>
                        <value><string>GL</string></value>
                    </member>
                </struct>
            </value>
        </param>
        <param>
            <value>
                <struct>
                    <member>
                        <name>glCode</name>
                        <value><string>GL</string></value>
                    </member>
                    <member>
                        <name>transdate</name>
                        <value><string>GL</string></value>
                    </member>
                    <member>
                        <name>amount</name>
                        <value><string>GL</string></value>
                    </member>
                    <member>
                        <name>narrative</name>
                        <value><string>GL</string></value>
                    </member>
                    <member>
                        <name>tag</name>
                        <value><string>GL</string></value>
                    </member>
                </struct>
            </value>
        </param>
    </params>
</methodCall>






	$test=ZALONGWA;
    return $test;
===============
    /********************** expected parameters ****************************************  
	 * (1) InvoiceType (i. Purchase Order
	 * 					ii. Shipments
	 * 					iii. General Ledger (GL)
	 * 					iv. Contracts
	 * 					v. Fixed Assets
	 *                 )
	 * (2) SupplierID
	 * (3) InvoiceHeader array (InvoiceNo, Narrative, TransDate, TotalInvoice, TotalTax)
	 * if (InvoiceType == GL){
	 * (4) InvoiceLineDetails array (GlCode, Amount, Narrative, Tag)
	 * }
	 * Retrive Supplier Information
	 * (5) SupplierInfo array (i. daysbeforedue
	 * 						   ii. dayinfollowingmonth
	 * 						   iii. suppname
	 * 						   iv. Currcode
	 *                         v. taxrate
	 *                         vi. taxgroupid
	 *                         vii. taxgroupdescription
	 *                         viii. terms
	 *                        )
	 *********************** expected parameters ****************************************  
	*/
	foreach ($Header as $key => $Value) {
		$HeaderData[$key] = DB_escape_string($Value);
	}
	$Errors=VerifySupplierNo($HeaderData['supplierid'], sizeof($Errors), $Errors);
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
			AND suppliers.supplierid = '$SupplierID'";

	$Result = DB_query($SQL, $ErrMsg);
	
	//=========show output============
	
		if (sizeof($Errors)==0) {
			$i=0;
			while ($MyRow=DB_fetch_array($Result)) {
				$Answer[$i]=$MyRow;
				$i++;
			}
			return $Answer;
		} else {
			return $Errors;
		}
	

    	//=========end of show==========
	/*
	if (DB_num_rows($Result)==0){
		$Errors[0] = SupplierCannotbeRetrieved;
		return $Errors;
	}
		\*/
	$MyRow = DB_fetch_array($Result);
	
	/* listdown all the values from the submitted Invoice Header */
	$invoicetype = $HeaderData['invoicetype'];
	$SupplierID = $HeaderData['supplierid'];
	$InvoiceNo = $HeaderData['invoiceno'];
	$TransDate = $HeaderData['transdate'];
	$Narrative = $HeaderData['narrative'];
	$TotalInvoice = $HeaderData['totalinvoice'];
	$TotalTax = $HeaderData['totaltax'];
	/* listdown all the values from the retrieved Supplier Information */
	//terms
	if ($MyRow['daysbeforedue'] == 0) {
		$Terms = '1' . $MyRow['dayinfollowingmonth'];
	}
	else {
		$Terms = '0' . $MyRow['daysbeforedue'];
	}
	$SupplierName = $MyRow['suppname'];
	$CurrencyCode = $MyRow['currcode'];
	$ExRate = $MyRow['exrate'];
	$TaxGroupId = $MyRow['taxgroupid'];
	$TaxGroupDescription = $MyRow['taxgroupdescription'];
	$CurrDecimalPlaces = $MyRow['decimalplaces'];

	$GLLink_Creditors = 0;
    if($InvoiceDetails['InvoiceType']=='General Ledger'){
		//set GLLink_creditors to true
	   $GLLink_Creditors = 1;
			/*Loop through the Invoice Header array to retrieve the values */
				foreach ($LineDetails as $LineData){
					$InputError = false;
					//validate the values
					$SQL = "SELECT accountcode,
							accountname
						FROM chartmaster
						WHERE accountcode='" . $LineData['GLCode'] . "'";
					$Result = DB_query($SQL);
					if (DB_num_rows($Result)==0){
						$InputError = true;
						$Errors[0] = InvalidGLCode;
						return $Errors;
					}else if ($LineData['GLCode'] != '') {
						$MyRow = DB_fetch_row($Result);
						$GLActName = $MyRow[1];
					}
					if (!is_numeric(filter_number_format($LineData['Amount']))) {
						$InputError = true;
						$Errors[0] = AmountNotNumeric;
					} 
					/*
					if($InputError==false){
						$TotalGLValue += $EnteredGLCode->Amount;
						$TaxTotal += $EnteredGLCode->Tax;
					}
						*/
				}
				if ($TaxTotal+$TotalInvoice < 0) {
					$InputError = true;
					$Errors[0]=TotalAmountLessThanZero;
					return $Errors;
				}
				elseif ($TaxTotal+$TotalInvoice == 0) {
					$InputError = true;
					$Errors[0]=WarnedInvoiceAmountIsZero;
					return $Errors;
				}
				elseif (mb_strlen($InvoiceNo) < 1) {
					$InputError = true;
					$Errors[0]=NoInvoiceNo;
					return $Errors;
				}
				elseif (!Is_date($TransDate)) {
					$InputError = true;
					$Errors[0]=InvalidInvoiceDate;
					return $Errors;
				}
				elseif (DateDiff(date($_SESSION['DefaultDateFormat']) , $TransDate, 'd') < 0) {
					$InputError = true;
					$Errors[0]=InvoiceDateAfterToday;
					return $Errors;
				}
				elseif ($ExRate <= 0) {
					$InputError = true;
					$Errors[0]=NegativeOrZeroExRate;
					return $Errors;
				}
				elseif ($TotalInvoice < round(Total_Shipts_Value() + Total_GL_Value() + Total_Contracts_Value() + Total_Assets_Value() + Total_GRN_Value() , $CurrDecimalPlaces)) {
					$InputError = true;
					$Errors[0]=LessInvoiceTotal;
					return $Errors;
				}
				else {

					$SQL = "SELECT count(*)
							FROM supptrans
							WHERE supplierno='$SupplierID'
							AND supptrans.suppreference='$InvoiceNo'";
					$Result = DB_query($SQL, $ErrMsg, '', true);

					$MyRow = DB_fetch_row($Result);
					if ($MyRow[0] == 1) { /*Transaction reference already entered */
						$Errors[0]=DuplicateInvoiceNo;
						return $Errors;
					}
				}
					
		if ($InputError == false) {

			/* SQL to process the postings for purchase invoice */
			/*Start an SQL transaction */

			DB_Txn_Begin();

			/*Get the next transaction number for internal purposes and the period to post GL transactions in based on the invoice date*/
			$InvoiceNo = GetNextTransNo(20);
			$PeriodNo = GetPeriod($TransDate);
			$SQLInvoiceDate = FormatDateForSQL($TransDate);

			if ($GLLink_Creditors == 1) {
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

				foreach ($LineDetails as $EnteredGLCode) {

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
									'" . mb_substr($SupplierID . ' - ' . $EnteredGLCode->Narrative, 0, 200) . "',
									'" . $EnteredGLCode->Amount / $ExRate . "')";
			//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added because');
			$Result = api_DB_query($SQL,'', '', true);
			//InsertGLTags($EnteredGLCode->Tag);
			$LocalTotal += $EnteredGLCode->Amount / $ExRate;
		}

		foreach ($Shipts as $ShiptChg) {

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
								'" . $GRNAct . "',
								'" . mb_substr($SupplierID . ' - ' . __('Shipment charge against') . ' ' . $ShiptChg->ShiptRef, 0, 200) . "',
								'" . $ShiptChg->Amount / $ExRate . "')";
			//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the shipment') . ' ' . $ShiptChg->ShiptRef . ' ' . __('could not be added because');
			$Result = api_DB_query($SQL,'', '', true);
			$LocalTotal += $ShiptChg->Amount / $ExRate;
		}

		foreach ($Assets as $AssetAddition) {
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
								'" . mb_substr($SupplierID . ' ' . __('Asset Addition') . ' ' . $AssetAddition->AssetID . ': ' . $AssetAddition->Description, 0, 200) . "',
								'" . ($AssetAddition->Amount / $ExRate) . "')";
			//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the asset addition could not be added because');
			$Result = api_DB_query($SQL,'', '', true);
			$LocalTotal += ($AssetAddition->Amount / $ExRate);
		}

		foreach ($Contracts as $Contract) {

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
										'" . mb_substr($SupplierID . ' ' . __('Contract charge against') . ' ' . $Contract->ContractRef, 0, 200) . "',
										'" . ($Contract->Amount / $ExRate) . "')";
		//	$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the contract') . ' ' . $Contract->ContractRef . ' ' . __('could not be added because');
			$Result = api_DB_query($SQL,'', '', true);
			$LocalTotal += ($Contract->Amount / $ExRate);
		}

		foreach ($GRNs as $EnteredGRN) {

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
							'" . $GRNAct . "',
							'" . mb_substr($SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . $EnteredGRN->This_QuantityInv . ' @  ' . __('std cost of') . ' ' . $EnteredGRN->StdCostUnit, 0, 200) . "',
							'" . ($EnteredGRN->StdCostUnit * $EnteredGRN->This_QuantityInv) . "')";

				//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added because');
			    $Result = api_DB_query($SQL,'', '', true);
			}

			$PurchPriceVar = $EnteredGRN->This_QuantityInv * (($EnteredGRN->ChgPrice / $ExRate) - $EnteredGRN->StdCostUnit);

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
													'" . mb_substr($SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . ($EnteredGRN->This_QuantityInv - $TotalQuantityOnHand) . ' x  ' . __('price var of') . ' ' . round(($EnteredGRN->ChgPrice / $ExRate) - $EnteredGRN->StdCostUnit, 2), 0, 200) . "',
													'" . $WriteOffToVariances . "')";
						//	$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');
							$Result = api_DB_query($SQL,'', '', true);
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
											'" . mb_substr($SupplierID . ' - ' . __('Average Cost Adj') . ' - ' . $EnteredGRN->ItemCode . ' x ' . $TotalQuantityOnHand . ' x ' . round(($EnteredGRN->ChgPrice / $ExRate) - $EnteredGRN->StdCostUnit, $_SESSION['CompanyRecord']['decimalplaces']), 0, 200) . "',
											'" . ($PurchPriceVar - $WriteOffToVariances) . "')";

						//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');
						$Result = api_DB_query($SQL,'', '', true);
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
												'" . mb_substr($SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . $EnteredGRN->This_QuantityInv . ' x  ' . __('price var of') . ' ' . round(($EnteredGRN->ChgPrice / $ExRate) - $EnteredGRN->StdCostUnit, 2), 0, 200) . "',
												'" . $PurchPriceVar . "')";

						//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');
						$Result = api_DB_query($SQL,'', '', true);
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
									'" . mb_substr($SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemDescription . ' x ' . $EnteredGRN->This_QuantityInv . ' x  ' . __('price var') . ' ' . locale_number_format(($EnteredGRN->ChgPrice / $ExRate) - $EnteredGRN->StdCostUnit, $CurrDecimalPlaces), 0, 200) . "',
									'" . $PurchPriceVar . "')";

						//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');
						$Result = api_DB_query($SQL,'', '', true);
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
									'" . $GRNAct . "',
									'" . mb_substr($SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . $EnteredGRN->This_QuantityInv . ' @ ' . $CurrCode . ' ' . $EnteredGRN->ChgPrice . ' @ ' . __('a rate of') . ' ' . $ExRate, 0, 200) . "',
									'" . (($EnteredGRN->ChgPrice * $EnteredGRN->This_QuantityInv) / $_SESSION['SuppTrans']->ExRate) . "')";

					//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added because');
					$Result = api_DB_query($SQL,'', '', true);
				}
				$LocalTotal += ($EnteredGRN->ChgPrice * $EnteredGRN->This_QuantityInv) / $_SESSION['SuppTrans']->ExRate;
			} /* end of GRN postings */

			foreach ($Taxes as $Tax) {
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
										'" . mb_substr($SupplierID . ' - ' . __('Inv') . ' ' . $InvoiceNo . ' ' . $Tax->TaxAuthDescription . ' ' . locale_number_format($Tax->TaxRate * 100, 2) . '% ' . $CurrCode . $Tax->TaxOvAmount . ' @ ' . __('exch rate') . ' ' . $ExRate, 0, 200) . "',
										'" . ($Tax->TaxOvAmount / $ExRate) . "')";

					//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the tax could not be added because');
					$Result = api_DB_query($SQL,'', '', true);
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
							'" . $CreditorsAct . "',
							'" . mb_substr($SupplierID . ' - ' . __('Inv') . ' ' . $InvoiceNo . ' ' . $CurrCode . locale_number_format($OvAmount + $TaxTotal, $CurrDecimalPlaces) . ' @ ' . __('a rate of') . ' ' . $ExRate, 0, 200) . "',
							'" . -($LocalTotal + ($TaxTotal / $ExRate)) . "')";

				//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the control total could not be added because');
				$Result = api_DB_query($SQL,'', '', true);

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
						'" . $SupplierID . "',
						'" . $InvoiceNo . "',
						'" . $SQLInvoiceDate . "',
						'" . FormatDateForSQL($DueDate) . "',
						'" . $OvAmount . "',
						'" . $TaxTotal . "',
						'" . $ExRate . "',
						'" . $Comments . "',
						CURRENT_DATE)";

			//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The supplier invoice transaction could not be added to the database because');
			$Result = api_DB_query($SQL,'', '', true);
			$SuppTransID = DB_Last_Insert_ID('supptrans', 'id');

			/* Insert the tax totals for each tax authority where tax was charged on the invoice */
			foreach ($Taxes AS $TaxTotals) {

				$SQL = "INSERT INTO supptranstaxes (supptransid,
										taxauthid,
										taxamount)
							VALUES (
								'" . $SuppTransID . "',
								'" . $TaxTotals->TaxAuthID . "',
								'" . $TaxTotals->TaxOvAmount . "')";

				//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The supplier transaction taxes records could not be inserted because');
				$Result = api_DB_query($SQL,'', '', true);
			}

		/* Now update the GRN and PurchOrderDetails records for amounts invoiced  - can't use the other loop through the GRNs as this was only where the GL link to credtors is active */

		foreach ($GRNs as $EnteredGRN) {

			//in local currency
			$ActualCost = $EnteredGRN->ChgPrice / $ExRate;
			$PurchPriceVar = $EnteredGRN->This_QuantityInv * ($ActualCost - $EnteredGRN->StdCostUnit);

			$SQL = "UPDATE purchorderdetails
					SET qtyinvoiced = qtyinvoiced + " . $EnteredGRN->This_QuantityInv . ",
						actprice = '" . $EnteredGRN->ChgPrice . "'
					WHERE podetailitem = '" . $EnteredGRN->PODetailItem . "'";

		//	$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The quantity invoiced of the purchase order line could not be updated because');

			$Result = api_DB_query($SQL,'', '', true);

			$SQL = "UPDATE grns
					SET quantityinv = quantityinv + " . $EnteredGRN->This_QuantityInv . "
					WHERE grnno = '" . $EnteredGRN->GRNNo . "'";

			//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The quantity invoiced off the goods received record could not be updated because');
			$Result = api_DB_query($SQL,'', '', true);

			$SQL = "INSERT INTO suppinvstogrn VALUES ('" . $InvoiceNo . "',
									'" . $EnteredGRN->GRNNo . "')";
			/*$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The invoice could not be mapped to the
					goods received record because'); */
			$Result = api_DB_query($SQL,'', '', true);

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
									'" . ($EnteredGRN->This_QuantityInv * $EnteredGRN->ChgPrice) / $ExRate . "')";

				//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The shipment charge record for the shipment') . ' ' . $EnteredGRN->ShiptRef . ' ' . __('could not be added because');
				$Result = api_DB_query($SQL,'', '', true);

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
				$Result = api_DB_query("SELECT intostocklocation
									FROM purchorders
									WHERE orderno='" . $EnteredGRN->PONo . "'");
				$LocRow = DB_fetch_array($Result);
				$LocCode = $LocRow['intostocklocation'];

				/* First update the stockmoves delivery cost */
			//	$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The stock movement record for the delivery could not have the cost updated to the actual cost');
				$SQL = "UPDATE stockmoves SET price = '" . $ActualCost . "'
									WHERE stockid='" . $EnteredGRN->ItemCode . "'
									AND type=25
									AND loccode='" . $LocCode . "'
									AND transno='" . $EnteredGRN->GRNBatchNo . "'";

				$Result = api_DB_query($SQL,'', '', true);

				if ($WeightedAverageCosting == 1) {
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
						//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sales analysis records could not be updated for the cost variances on this purchase invoice');

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
											AND trandate>='" . FormatDateForSQL(DateAdd($TransDate, 'm', -6)) . "'
											ORDER BY stkmoveno DESC");
						//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The stock movements for invoices cannot be updated for the cost variances on this purchase invoice');
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

					//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The cost could not be updated because');

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
						//$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE The fixed asset transaction could not be inserted because');
						$Result = DB_query($SQL, $ErrMsg, '', true);

						/*Now update the asset cost in fixedassets table */
						$SQL = "UPDATE fixedassets SET cost = cost + " . ($PurchPriceVar) . "
								WHERE assetid = '" . $EnteredGRN->AssetID . "'";

						//$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE. The fixed asset cost could not be updated because:');
						$Result = DB_query($SQL, $ErrMsg, '', true);
					} //end if there was a difference in the cost

				} //the item was an asset received on a purchase order

			} /* end of the GRN loop to do the updates for the quantity of order items the supplier has invoiced */

			/*Add shipment charges records as necessary */
			foreach ($Shipts as $ShiptChg) {

				$SQL = "INSERT INTO shipmentcharges (shiptref,
													transtype,
													transno,
													value)
							VALUES ('" . $ShiptChg->ShiptRef . "',
										'20',
									'" . $InvoiceNo . "',
									'" . $ShiptChg->Amount / $ExRate . "')";

				//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The shipment charge record for the shipment') . ' ' . $ShiptChg->ShiptRef . ' ' . __('could not be added because');

				$Result = DB_query($SQL, $ErrMsg, '', true);

			}
			/*Add contract charges records as necessary */

			foreach ($Contracts as $Contract) {

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
								'" . $Contract->Amount / $ExRate . "',
								'" . $Contract->Narrative . "',
								'" . $Anticipated . "')";

				//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The contract charge record for contract') . ' ' . $Contract->ContractRef . ' ' . __('could not be added because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			}

			foreach ($Assets as $AssetAddition) {

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
									'" . ($AssetAddition->Amount / $ExRate) . "')";
			//$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE The fixed asset transaction could not be inserted because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			/*Now update the asset cost in fixedassets table */
			$Result = DB_query("SELECT datepurchased
								FROM fixedassets
								WHERE assetid='" . $AssetAddition->AssetID . "'");
			$AssetRow = DB_fetch_array($Result);

			$SQL = "UPDATE fixedassets SET cost = cost + " . ($AssetAddition->Amount / $ExRate);
			if ($AssetRow['datepurchased'] == '1000-01-01') {
				$SQL .= ", datepurchased='" . $SQLInvoiceDate . "'";
			}
			$SQL .= " WHERE assetid = '" . $AssetAddition->AssetID . "'";
			//$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE. The fixed asset cost and date purchased was not able to be updated because:');
			$Result = DB_query($SQL, $ErrMsg, '', true);
			} //end of non-gl fixed asset stuff
			DB_Txn_Commit();
			$Errors[0] = InvoiceProcessedSuccessfully;
			return $Errors;
			/*
			unset($_SESSION['SuppTrans']->GRNs);
			unset($_SESSION['SuppTrans']->Shipts);
			unset($_SESSION['SuppTrans']->GLCodes);
			unset($_SESSION['SuppTrans']->Contracts);
			unset($_SESSION['SuppTrans']);
			*/
		}
	}


	About JICTS server

	check file log size:
	/var/log/apache2
	ls -lah 

	check file contents and the frequency of updating the contents: 
	tail -f error_tjpsd.log

	Changed log file Persmission at:
	/var/www/tjpsdudsm/files/usageStats/usageEventLogs

	changed file permission at :
	/var/www/tjpsd.udsm.ac.tz/plugins/generic/usageStats
	the command is 
	chown -R www-data:www-data UsageStatsPlugin.inc.php
	the command is 
	chown -R www-data:www-data usage_events_20251022.log

	removed/renamed some files:
	cd /var/www/tjpsd.udsm.ac.tz/plugins/generic/usageStats/lib/geoIp/admin/
	mv generate_geoipregionvars.php generate_geoipregionvars.php_lastgood

	Blocked IP with ufw as follows
	sudo ufw deny from 130.94.44.124
	sudo ufw deny from 122.129.107.205
	sudo ufw deny from 122.129.107.203
	sudo ufw deny from 130.94.44.124 to any port 443
	sudo ufw deny from 130.94.44.124 to any port 80
	sudo ufw deny from 43.173.180.149
	sudo ufw deny from 43.173.180.149 to any port 80
	sudo ufw deny from 43.173.180.149 to any port 443

	sudo ufw deny from 103.59.160.46 to any port 80
	sudo ufw deny from 103.59.160.46 to any port 443
	sudo ufw deny from 122.129.107.203 to any port 80
	sudo ufw deny from 122.129.107.203 to any port 443

	sudo systemctl reload apache2

	sudo systemctl restart php8.1-fpm
	sudo systemctl restart apache2.service
	sudo systemctl restart apache2
	sudo systemctl status apache2

	==== UPDATED -SSL.CONFIG ======
	# Don't allow direct access to OJS internals
	<DirectoryMatch "^.*/(classes|lib|controllers|cache)/">
		Require all denied
	</DirectoryMatch>

	# Don't allow direct access to plugin PHP files
	<Directory "/var/www/tjpsd.udsm.ac.tz/plugins/">
		Require all denied
	</Directory>

	# Extra safety: no direct execution of *.inc.php files anywhere
	<FilesMatch "\.inc\.php$">
		Require all denied
	</FilesMatch>

	# Disable directory listing
	Options -Indexes

	==== END UPDATED -SSL.CONFIG ======


========= START =================
<IfModule mod_ssl.c>
<VirtualHost jgat.udsm.ac.tz:443>
        # The ServerName directive sets the request scheme, hostname and port that
        # the server uses to identify itself. This is used when creating
        # redirection URLs. In the context of virtual hosts, the ServerName
        # specifies what hostname must appear in the request's Host: header to
        # match this virtual host. For the default virtual host (this file) this
        # value is not decisive as it is used as a last resort host regardless.
        # However, you must set it for any further virtual host explicitly.
        #ServerName www.example.com

        ServerName jgat.udsm.ac.tz
        ServerAdmin webmaster@jgat.udsm.ac.tz
        DocumentRoot /var/www/jgat.udsm.ac.tz

        # Available loglevels: trace8, ..., trace1, debug, info, notice, warn,
        # error, crit, alert, emerg.
        # It is also possible to configure the loglevel for particular
        # modules, e.g.
        #LogLevel info ssl:warn

            # Use PHP 7.3 FPM socket
        <FilesMatch \.php$>
           SetHandler "proxy:unix:/run/php/php7.3-fpm.sock|fcgi://localhost/"
        </FilesMatch>

		# Don't allow direct access to OJS internals
		<DirectoryMatch "^.*/(classes|lib|controllers|cache)/">
			Require all denied
		</DirectoryMatch>

		# Don't allow direct access to plugin PHP files
		<Directory "/var/www/tjpsd.udsm.ac.tz/plugins/">
			Require all denied
		</Directory>

		# Extra safety: no direct execution of *.inc.php files anywhere
		<FilesMatch "\.inc\.php$">
			Require all denied
		</FilesMatch>

		# Disable directory listing
		Options -Indexes

        ErrorLog ${APACHE_LOG_DIR}/error_jgat.log
        CustomLog ${APACHE_LOG_DIR}/access_jgat.log combined

        # For most configuration files from conf-available/, which are
        # enabled or disabled at a global level, it is possible to
        # include a line for only one particular virtual host. For example the
        # following line enables the CGI configuration for this host only
        # after it has been globally disabled with "a2disconf".
        #Include conf-available/serve-cgi-bin.conf

SSLEngine on
#Include /etc/letsencrypt/options-ssl-apache.conf
#SSLCertificateFile /etc/letsencrypt/live/jgat.udsm.ac.tz-0001/fullchain.pem
#SSLCertificateKeyFile /etc/letsencrypt/live/jgat.udsm.ac.tz-0001/privkey.pem

========== END =================

/** Create a customer invoice in webERP. This function will bypass the
 * normal procedure in webERP for creating a sales order first, and then
 * delivering it.

 * NB: There are no stock updates no accounting for assemblies no updates
 * to sales analysis records - no cost of sales entries in GL

 ************ USE ONLY WITH CAUTION********************
*/
function InsertSupplierInvoice($Header, $LineDetails, $user, $password) {
    $Errors = array();
	$db = db($user, $password);
	if (gettype($db)=='integer') {
		$Errors[0]=NoAuthorisation;
		return $Errors;
	}

	/********************** expected parameters ****************************************  
	 * (1) InvoiceType (i. Purchase Order
	 * 					ii. Shipments
	 * 					iii. General Ledger (GL)
	 * 					iv. Contracts
	 * 					v. Fixed Assets
	 *                 )
	 * (2) SupplierID
	 * (3) InvoiceHeader array (InvoiceNo, Narrative, TransDate, TotalInvoice, TotalTax)
	 * if (InvoiceType == GL){
	 * (4) InvoiceLineDetails array (GlCode, Amount, Narrative, Tag)
	 * }
	 * Retrive Supplier Information
	 * (5) SupplierInfo array (i. daysbeforedue
	 * 						   ii. dayinfollowingmonth
	 * 						   iii. suppname
	 * 						   iv. Currcode
	 *                         v. taxrate
	 *                         vi. taxgroupid
	 *                         vii. taxgroupdescription
	 *                         viii. terms
	 *                        )
	 *********************** expected parameters ****************************************  
	*/
	foreach ($Header as $key => $Value) {
		$HeaderData[$key] = DB_escape_string($Value);
	}

	return $HeaderData['supplierid'];
	
	$Errors=VerifySupplierNo($HeaderData['supplierid'], sizeof($Errors), $Errors);
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
			AND suppliers.supplierid = '$SupplierID'";

	$Result = DB_query($SQL, $ErrMsg);
	
	//=========show output============
	
		if (sizeof($Errors)==0) {
			$i=0;
			while ($MyRow=DB_fetch_array($Result)) {
				$Answer[$i]=$MyRow;
				$i++;
			}
			return $Answer;
		} else {
			return $Errors;
		}
	
	//=========end of show==========
	/*
	if (DB_num_rows($Result)==0){
		$Errors[0] = SupplierCannotbeRetrieved;
		return $Errors;
	}
		\*/
	$MyRow = DB_fetch_array($Result);
	
	/* listdown all the values from the submitted Invoice Header */
	$invoicetype = $HeaderData['invoicetype'];
	$SupplierID = $HeaderData['supplierid'];
	$InvoiceNo = $HeaderData['invoiceno'];
	$TransDate = $HeaderData['transdate'];
	$Narrative = $HeaderData['narrative'];
	$TotalInvoice = $HeaderData['totalinvoice'];
	$TotalTax = $HeaderData['totaltax'];
	/* listdown all the values from the retrieved Supplier Information */
	//terms
	if ($MyRow['daysbeforedue'] == 0) {
		$Terms = '1' . $MyRow['dayinfollowingmonth'];
	}
	else {
		$Terms = '0' . $MyRow['daysbeforedue'];
	}
	$SupplierName = $MyRow['suppname'];
	$CurrencyCode = $MyRow['currcode'];
	$ExRate = $MyRow['exrate'];
	$TaxGroupId = $MyRow['taxgroupid'];
	$TaxGroupDescription = $MyRow['taxgroupdescription'];
	$CurrDecimalPlaces = $MyRow['decimalplaces'];

	$GLLink_Creditors = 0;
    if($InvoiceDetails['InvoiceType']=='General Ledger'){
		//set GLLink_creditors to true
	   $GLLink_Creditors = 1;
			/*Loop through the Invoice Header array to retrieve the values */
				foreach ($LineDetails as $LineData){
					$InputError = false;
					//validate the values
					$SQL = "SELECT accountcode,
							accountname
						FROM chartmaster
						WHERE accountcode='" . $LineData['GLCode'] . "'";
					$Result = DB_query($SQL);
					if (DB_num_rows($Result)==0){
						$InputError = true;
						$Errors[0] = InvalidGLCode;
						return $Errors;
					}else if ($LineData['GLCode'] != '') {
						$MyRow = DB_fetch_row($Result);
						$GLActName = $MyRow[1];
					}
					if (!is_numeric(filter_number_format($LineData['Amount']))) {
						$InputError = true;
						$Errors[0] = AmountNotNumeric;
					} 
					/*
					if($InputError==false){
						$TotalGLValue += $EnteredGLCode->Amount;
						$TaxTotal += $EnteredGLCode->Tax;
					}
						*/
				}
				if ($TaxTotal+$TotalInvoice < 0) {
					$InputError = true;
					$Errors[0]=TotalAmountLessThanZero;
					return $Errors;
				}
				elseif ($TaxTotal+$TotalInvoice == 0) {
					$InputError = true;
					$Errors[0]=WarnedInvoiceAmountIsZero;
					return $Errors;
				}
				elseif (mb_strlen($InvoiceNo) < 1) {
					$InputError = true;
					$Errors[0]=NoInvoiceNo;
					return $Errors;
				}
				elseif (!Is_date($TransDate)) {
					$InputError = true;
					$Errors[0]=InvalidInvoiceDate;
					return $Errors;
				}
				elseif (DateDiff(date($_SESSION['DefaultDateFormat']) , $TransDate, 'd') < 0) {
					$InputError = true;
					$Errors[0]=InvoiceDateAfterToday;
					return $Errors;
				}
				elseif ($ExRate <= 0) {
					$InputError = true;
					$Errors[0]=NegativeOrZeroExRate;
					return $Errors;
				}
				elseif ($TotalInvoice < round(Total_Shipts_Value() + Total_GL_Value() + Total_Contracts_Value() + Total_Assets_Value() + Total_GRN_Value() , $CurrDecimalPlaces)) {
					$InputError = true;
					$Errors[0]=LessInvoiceTotal;
					return $Errors;
				}
				else {

					$SQL = "SELECT count(*)
							FROM supptrans
							WHERE supplierno='$SupplierID'
							AND supptrans.suppreference='$InvoiceNo'";
					$Result = DB_query($SQL, $ErrMsg, '', true);

					$MyRow = DB_fetch_row($Result);
					if ($MyRow[0] == 1) { /*Transaction reference already entered */
						$Errors[0]=DuplicateInvoiceNo;
						return $Errors;
					}
				}
					
		if ($InputError == false) {

			/* SQL to process the postings for purchase invoice */
			/*Start an SQL transaction */

			//DB_Txn_Begin();

			/*Get the next transaction number for internal purposes and the period to post GL transactions in based on the invoice date*/
			$InvoiceNo = GetNextTransNo(20);
			return $InvoiceNo;
		//	$PeriodNo = GetPeriod($TransDate);
			//$SQLInvoiceDate = FormatDateForSQL($TransDate);

			if ($GLLink_Creditors == 1) {
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

				foreach ($LineDetails as $EnteredGLCode) {

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
									'" . mb_substr($SupplierID . ' - ' . $EnteredGLCode->Narrative, 0, 200) . "',
									'" . $EnteredGLCode->Amount / $ExRate . "')";
			//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added because');
			$Result = api_DB_query($SQL,'', '', true);
			//InsertGLTags($EnteredGLCode->Tag);
			$LocalTotal += $EnteredGLCode->Amount / $ExRate;
		}

		foreach ($Shipts as $ShiptChg) {

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
								'" . $GRNAct . "',
								'" . mb_substr($SupplierID . ' - ' . __('Shipment charge against') . ' ' . $ShiptChg->ShiptRef, 0, 200) . "',
								'" . $ShiptChg->Amount / $ExRate . "')";
			//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the shipment') . ' ' . $ShiptChg->ShiptRef . ' ' . __('could not be added because');
			$Result = api_DB_query($SQL,'', '', true);
			$LocalTotal += $ShiptChg->Amount / $ExRate;
		}

		foreach ($Assets as $AssetAddition) {
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
								'" . mb_substr($SupplierID . ' ' . __('Asset Addition') . ' ' . $AssetAddition->AssetID . ': ' . $AssetAddition->Description, 0, 200) . "',
								'" . ($AssetAddition->Amount / $ExRate) . "')";
			//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the asset addition could not be added because');
			$Result = api_DB_query($SQL,'', '', true);
			$LocalTotal += ($AssetAddition->Amount / $ExRate);
		}

		foreach ($Contracts as $Contract) {

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
										'" . mb_substr($SupplierID . ' ' . __('Contract charge against') . ' ' . $Contract->ContractRef, 0, 200) . "',
										'" . ($Contract->Amount / $ExRate) . "')";
		//	$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the contract') . ' ' . $Contract->ContractRef . ' ' . __('could not be added because');
			$Result = api_DB_query($SQL,'', '', true);
			$LocalTotal += ($Contract->Amount / $ExRate);
		}

		foreach ($GRNs as $EnteredGRN) {

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
							'" . $GRNAct . "',
							'" . mb_substr($SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . $EnteredGRN->This_QuantityInv . ' @  ' . __('std cost of') . ' ' . $EnteredGRN->StdCostUnit, 0, 200) . "',
							'" . ($EnteredGRN->StdCostUnit * $EnteredGRN->This_QuantityInv) . "')";

				//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added because');
			    $Result = api_DB_query($SQL,'', '', true);
			}

			$PurchPriceVar = $EnteredGRN->This_QuantityInv * (($EnteredGRN->ChgPrice / $ExRate) - $EnteredGRN->StdCostUnit);

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
													'" . mb_substr($SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . ($EnteredGRN->This_QuantityInv - $TotalQuantityOnHand) . ' x  ' . __('price var of') . ' ' . round(($EnteredGRN->ChgPrice / $ExRate) - $EnteredGRN->StdCostUnit, 2), 0, 200) . "',
													'" . $WriteOffToVariances . "')";
						//	$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');
							$Result = api_DB_query($SQL,'', '', true);
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
											'" . mb_substr($SupplierID . ' - ' . __('Average Cost Adj') . ' - ' . $EnteredGRN->ItemCode . ' x ' . $TotalQuantityOnHand . ' x ' . round(($EnteredGRN->ChgPrice / $ExRate) - $EnteredGRN->StdCostUnit, $_SESSION['CompanyRecord']['decimalplaces']), 0, 200) . "',
											'" . ($PurchPriceVar - $WriteOffToVariances) . "')";

						//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');
						$Result = api_DB_query($SQL,'', '', true);
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
												'" . mb_substr($SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . $EnteredGRN->This_QuantityInv . ' x  ' . __('price var of') . ' ' . round(($EnteredGRN->ChgPrice / $ExRate) - $EnteredGRN->StdCostUnit, 2), 0, 200) . "',
												'" . $PurchPriceVar . "')";

						//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');
						$Result = api_DB_query($SQL,'', '', true);
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
									'" . mb_substr($SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemDescription . ' x ' . $EnteredGRN->This_QuantityInv . ' x  ' . __('price var') . ' ' . locale_number_format(($EnteredGRN->ChgPrice / $ExRate) - $EnteredGRN->StdCostUnit, $CurrDecimalPlaces), 0, 200) . "',
									'" . $PurchPriceVar . "')";

						//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added for the price variance of the stock item because');
						$Result = api_DB_query($SQL,'', '', true);
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
									'" . $GRNAct . "',
									'" . mb_substr($SupplierID . ' - ' . __('GRN') . ' ' . $EnteredGRN->GRNNo . ' - ' . $EnteredGRN->ItemCode . ' x ' . $EnteredGRN->This_QuantityInv . ' @ ' . $CurrCode . ' ' . $EnteredGRN->ChgPrice . ' @ ' . __('a rate of') . ' ' . $ExRate, 0, 200) . "',
									'" . (($EnteredGRN->ChgPrice * $EnteredGRN->This_QuantityInv) / $_SESSION['SuppTrans']->ExRate) . "')";

					//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction could not be added because');
					$Result = api_DB_query($SQL,'', '', true);
				}
				$LocalTotal += ($EnteredGRN->ChgPrice * $EnteredGRN->This_QuantityInv) / $_SESSION['SuppTrans']->ExRate;
			} /* end of GRN postings */

			foreach ($Taxes as $Tax) {
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
										'" . mb_substr($SupplierID . ' - ' . __('Inv') . ' ' . $InvoiceNo . ' ' . $Tax->TaxAuthDescription . ' ' . locale_number_format($Tax->TaxRate * 100, 2) . '% ' . $CurrCode . $Tax->TaxOvAmount . ' @ ' . __('exch rate') . ' ' . $ExRate, 0, 200) . "',
										'" . ($Tax->TaxOvAmount / $ExRate) . "')";

					//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the tax could not be added because');
					$Result = api_DB_query($SQL,'', '', true);
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
							'" . $CreditorsAct . "',
							'" . mb_substr($SupplierID . ' - ' . __('Inv') . ' ' . $InvoiceNo . ' ' . $CurrCode . locale_number_format($OvAmount + $TaxTotal, $CurrDecimalPlaces) . ' @ ' . __('a rate of') . ' ' . $ExRate, 0, 200) . "',
							'" . -($LocalTotal + ($TaxTotal / $ExRate)) . "')";

				//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The general ledger transaction for the control total could not be added because');
				$Result = api_DB_query($SQL,'', '', true);

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
						'" . $SupplierID . "',
						'" . $InvoiceNo . "',
						'" . $SQLInvoiceDate . "',
						'" . FormatDateForSQL($DueDate) . "',
						'" . $OvAmount . "',
						'" . $TaxTotal . "',
						'" . $ExRate . "',
						'" . $Comments . "',
						CURRENT_DATE)";

			//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The supplier invoice transaction could not be added to the database because');
			$Result = api_DB_query($SQL,'', '', true);
			$SuppTransID = DB_Last_Insert_ID('supptrans', 'id');

			/* Insert the tax totals for each tax authority where tax was charged on the invoice */
			foreach ($Taxes AS $TaxTotals) {

				$SQL = "INSERT INTO supptranstaxes (supptransid,
										taxauthid,
										taxamount)
							VALUES (
								'" . $SuppTransID . "',
								'" . $TaxTotals->TaxAuthID . "',
								'" . $TaxTotals->TaxOvAmount . "')";

				//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The supplier transaction taxes records could not be inserted because');
				$Result = api_DB_query($SQL,'', '', true);
			}

		/* Now update the GRN and PurchOrderDetails records for amounts invoiced  - can't use the other loop through the GRNs as this was only where the GL link to credtors is active */

		foreach ($GRNs as $EnteredGRN) {

			//in local currency
			$ActualCost = $EnteredGRN->ChgPrice / $ExRate;
			$PurchPriceVar = $EnteredGRN->This_QuantityInv * ($ActualCost - $EnteredGRN->StdCostUnit);

			$SQL = "UPDATE purchorderdetails
					SET qtyinvoiced = qtyinvoiced + " . $EnteredGRN->This_QuantityInv . ",
						actprice = '" . $EnteredGRN->ChgPrice . "'
					WHERE podetailitem = '" . $EnteredGRN->PODetailItem . "'";

		//	$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The quantity invoiced of the purchase order line could not be updated because');

			$Result = api_DB_query($SQL,'', '', true);

			$SQL = "UPDATE grns
					SET quantityinv = quantityinv + " . $EnteredGRN->This_QuantityInv . "
					WHERE grnno = '" . $EnteredGRN->GRNNo . "'";

			//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The quantity invoiced off the goods received record could not be updated because');
			$Result = api_DB_query($SQL,'', '', true);

			$SQL = "INSERT INTO suppinvstogrn VALUES ('" . $InvoiceNo . "',
									'" . $EnteredGRN->GRNNo . "')";
			/*$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The invoice could not be mapped to the
					goods received record because'); */
			$Result = api_DB_query($SQL,'', '', true);

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
									'" . ($EnteredGRN->This_QuantityInv * $EnteredGRN->ChgPrice) / $ExRate . "')";

				//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The shipment charge record for the shipment') . ' ' . $EnteredGRN->ShiptRef . ' ' . __('could not be added because');
				$Result = api_DB_query($SQL,'', '', true);

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
				$Result = api_DB_query("SELECT intostocklocation
									FROM purchorders
									WHERE orderno='" . $EnteredGRN->PONo . "'");
				$LocRow = DB_fetch_array($Result);
				$LocCode = $LocRow['intostocklocation'];

				/* First update the stockmoves delivery cost */
			//	$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The stock movement record for the delivery could not have the cost updated to the actual cost');
				$SQL = "UPDATE stockmoves SET price = '" . $ActualCost . "'
									WHERE stockid='" . $EnteredGRN->ItemCode . "'
									AND type=25
									AND loccode='" . $LocCode . "'
									AND transno='" . $EnteredGRN->GRNBatchNo . "'";

				$Result = api_DB_query($SQL,'', '', true);

				if ($WeightedAverageCosting == 1) {
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
						//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The sales analysis records could not be updated for the cost variances on this purchase invoice');

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
											AND trandate>='" . FormatDateForSQL(DateAdd($TransDate, 'm', -6)) . "'
											ORDER BY stkmoveno DESC");
						//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The stock movements for invoices cannot be updated for the cost variances on this purchase invoice');
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

					//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The cost could not be updated because');

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
						//$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE The fixed asset transaction could not be inserted because');
						$Result = DB_query($SQL, $ErrMsg, '', true);

						/*Now update the asset cost in fixedassets table */
						$SQL = "UPDATE fixedassets SET cost = cost + " . ($PurchPriceVar) . "
								WHERE assetid = '" . $EnteredGRN->AssetID . "'";

						//$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE. The fixed asset cost could not be updated because:');
						$Result = DB_query($SQL, $ErrMsg, '', true);
					} //end if there was a difference in the cost

				} //the item was an asset received on a purchase order

			} /* end of the GRN loop to do the updates for the quantity of order items the supplier has invoiced */

			/*Add shipment charges records as necessary */
			foreach ($Shipts as $ShiptChg) {

				$SQL = "INSERT INTO shipmentcharges (shiptref,
													transtype,
													transno,
													value)
							VALUES ('" . $ShiptChg->ShiptRef . "',
										'20',
									'" . $InvoiceNo . "',
									'" . $ShiptChg->Amount / $ExRate . "')";

				//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The shipment charge record for the shipment') . ' ' . $ShiptChg->ShiptRef . ' ' . __('could not be added because');

				$Result = DB_query($SQL, $ErrMsg, '', true);

			}
			/*Add contract charges records as necessary */

			foreach ($Contracts as $Contract) {

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
								'" . $Contract->Amount / $ExRate . "',
								'" . $Contract->Narrative . "',
								'" . $Anticipated . "')";

				//$ErrMsg = __('CRITICAL ERROR') . '! ' . __('NOTE DOWN THIS ERROR AND SEEK ASSISTANCE') . ': ' . __('The contract charge record for contract') . ' ' . $Contract->ContractRef . ' ' . __('could not be added because');
				$Result = DB_query($SQL, $ErrMsg, '', true);
			}

			foreach ($Assets as $AssetAddition) {

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
									'" . ($AssetAddition->Amount / $ExRate) . "')";
			//$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE The fixed asset transaction could not be inserted because');
			$Result = DB_query($SQL, $ErrMsg, '', true);

			/*Now update the asset cost in fixedassets table */
			$Result = DB_query("SELECT datepurchased
								FROM fixedassets
								WHERE assetid='" . $AssetAddition->AssetID . "'");
			$AssetRow = DB_fetch_array($Result);

			$SQL = "UPDATE fixedassets SET cost = cost + " . ($AssetAddition->Amount / $ExRate);
			if ($AssetRow['datepurchased'] == '1000-01-01') {
				$SQL .= ", datepurchased='" . $SQLInvoiceDate . "'";
			}
			$SQL .= " WHERE assetid = '" . $AssetAddition->AssetID . "'";
			//$ErrMsg = __('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE. The fixed asset cost and date purchased was not able to be updated because:');
			$Result = DB_query($SQL, $ErrMsg, '', true);
			} //end of non-gl fixed asset stuff
			DB_Txn_Commit();
			$Errors[0] = InvoiceProcessedSuccessfully;
			return $Errors;
			/*
			unset($_SESSION['SuppTrans']->GRNs);
			unset($_SESSION['SuppTrans']->Shipts);
			unset($_SESSION['SuppTrans']->GLCodes);
			unset($_SESSION['SuppTrans']->Contracts);
			unset($_SESSION['SuppTrans']);
			*/
		}
	}



	SELECT debtortrans.trandate,
							debtortrans.ovamount,
							debtortrans.ovdiscount,
							debtortrans.ovfreight,
							debtortrans.ovgst,
							debtortrans.rate,
							debtortrans.invtext,
							debtortrans.consignment,
							debtortrans.packages,
							debtorsmaster.name,
							debtorsmaster.address1,
							debtorsmaster.address2,
							debtorsmaster.address3,
							debtorsmaster.address4,
							debtorsmaster.address5,
							debtorsmaster.address6,
							debtorsmaster.currcode,
							debtorsmaster.invaddrbranch,
							debtorsmaster.taxref,
							debtorsmaster.language_id,
							paymentterms.terms,
							paymentterms.dayinfollowingmonth,
							paymentterms.daysbeforedue,
							salesorders.deliverto,
							salesorders.deladd1,
							salesorders.deladd2,
							salesorders.deladd3,
							salesorders.deladd4,
							salesorders.deladd5,
							salesorders.deladd6,
							salesorders.customerref,
							salesorders.orderno,
							salesorders.orddate,
							locations.locationname,
							shippers.shippername,
							custbranch.brname,
							custbranch.braddress1,
							custbranch.braddress2,
							custbranch.braddress3,
							custbranch.braddress4,
							custbranch.braddress5,
							custbranch.braddress6,
							custbranch.brpostaddr1,
							custbranch.brpostaddr2,
							custbranch.brpostaddr3,
							custbranch.brpostaddr4,
							custbranch.brpostaddr5,
							custbranch.brpostaddr6,
							custbranch.salesman,
							salesman.salesmanname,
							debtortrans.debtorno,
							debtortrans.branchcode,
							currencies.decimalplaces
						FROM debtortrans INNER JOIN debtorsmaster
						ON debtortrans.debtorno=debtorsmaster.debtorno
						INNER JOIN custbranch
						ON debtortrans.debtorno=custbranch.debtorno
						AND debtortrans.branchcode=custbranch.branchcode
						INNER JOIN salesorders
						ON debtortrans.order_ = salesorders.orderno
						INNER JOIN shippers
						ON debtortrans.shipvia=shippers.shipper_id
						INNER JOIN salesman
						ON custbranch.salesman=salesman.salesmancode
						INNER JOIN locations
						ON salesorders.fromstkloc=locations.loccode
						INNER JOIN locationusers
						ON locationusers.loccode=locations.loccode AND locationusers.userid='apiuser' AND locationusers.canview=1
						INNER JOIN paymentterms
						ON debtorsmaster.paymentterms=paymentterms.termsindicator
						INNER JOIN currencies
						ON debtorsmaster.currcode=currencies.currabrev
						WHERE debtortrans.type=10
						AND debtortrans.transno='288';






						SELECT stockmoves.stockid,
								stockmaster.description,
								-stockmoves.qty as quantity,
								stockmoves.discountpercent,
								((1 - stockmoves.discountpercent) * stockmoves.price * 1* -stockmoves.qty) AS fxnet,
								(stockmoves.price * 1) AS fxprice,
								stockmoves.narrative,
								stockmaster.controlled,
								stockmaster.serialised,
								stockmaster.units,
								stockmoves.stkmoveno,
								stockmaster.decimalplaces
							FROM stockmoves INNER JOIN stockmaster
							ON stockmoves.stockid = stockmaster.stockid
							WHERE stockmoves.type=10
							AND stockmoves.transno='288'
							AND stockmoves.show_on_inv_crds=1;



							INSERT INTO debtortrans (transno, type, debtorno, trandate, inputdate, 
							                         prd, reference, rate, ovamount, alloc, 
													 invtext, settled, salesperson) 
							VALUES ('61', 12, 'COUNTER', '2026-02-11', '2026-02-11 17-39-44', 
							         '3', '293', '1', '-1180', '-1180', 
				                      'Morogoro Counter Sale', '1', '1');
}


======================git pull conflict===========================
user@Users-MacBook-Pro zerp-backend % git pull origin2 master
remote: Enumerating objects: 605, done.
remote: Counting objects: 100% (364/364), done.
remote: Compressing objects: 100% (36/36), done.
remote: Total 605 (delta 341), reused 329 (delta 328), pack-reused 241 (from 3)
Receiving objects: 100% (605/605), 1.11 MiB | 1.03 MiB/s, done.
Resolving deltas: 100% (416/416), completed with 142 local objects.
From github.com:timschofield/webERP
 * branch                master     -> FETCH_HEAD
   69c45272c..884d75fc2  master     -> origin2/master
Auto-merging ConfirmDispatch_Invoice.php
Auto-merging CounterSales.php
Auto-merging ManualContents.php
CONFLICT (content): Merge conflict in ManualContents.php
Auto-merging PrintCustTrans.php
Auto-merging Suppliers.php
Auto-merging api/includes/api_debtortransactions.php
Auto-merging api/includes/api_php.php
CONFLICT (content): Merge conflict in api/includes/api_php.php
Auto-merging api/includes/api_session.php
CONFLICT (content): Merge conflict in api/includes/api_session.php
Auto-merging doc/Manual/ManualAPIFunctions.php
Auto-merging includes/SQL_CommonFunctions.php
Auto-merging sql/updates/32.php
CONFLICT (add/add): Merge conflict in sql/updates/32.php
Auto-merging sql/updates/33.php
CONFLICT (add/add): Merge conflict in sql/updates/33.php
Auto-merging sql/updates/34.php
CONFLICT (add/add): Merge conflict in sql/updates/34.php
Automatic merge failed; fix conflicts and then commit the result.
user@Users-MacBook-Pro zerp-backend % 
==========================================