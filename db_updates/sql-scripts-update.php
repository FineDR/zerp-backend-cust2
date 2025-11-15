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


$GetStockCatProperty_sig = array(
	array(Value::$xmlrpcValue, Value::$xmlrpcString, Value::$xmlrpcString),
	array(Value::$xmlrpcValue, Value::$xmlrpcString, Value::$xmlrpcString, Value::$xmlrpcString, Value::$xmlrpcString));
$GetStockCatProperty_doc = apiBuildDocHTML($Description, $Parameter, $ReturnValue);


