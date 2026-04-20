<?php

//Scripts to update the database
ALTER TABLE salesorders MODIFY deladd6 VARCHAR(32);

INSERT INTO debtortrans (debtorno, branchcode, trandate, transno, type, ovamount, ovgst, salesperson, prd)
VALUES ('NS1369/005', 'NS1369/005','', '38', '10', '5000', '150', 'SP001', '');


on the issue of adjusting the size of debtorno:

ALTER TABLE custbranch DROP FOREIGN KEY custbranch_ibfk_1;
ALTER TABLE debtorsmaster MODIFY debtorno VARCHAR(32);
ALTER TABLE custbranch MODIFY debtorno VARCHAR(32);
ALTER TABLE custbranch 
  ADD CONSTRAINT custbranch_ibfk_1 
  FOREIGN KEY (debtorno) REFERENCES debtorsmaster(debtorno);

  ================= lllllll =============================

-- Drop all known FKs referencing debtorno
ALTER TABLE custbranch DROP FOREIGN KEY custbranch_ibfk_1;
ALTER TABLE custitem DROP FOREIGN KEY custitem_ibfk_2;
ALTER TABLE contracts DROP FOREIGN KEY contracts_ibfk_1;
-- (add any others the query reveals)

-- Modify debtorno in ALL affected tables
ALTER TABLE debtorsmaster MODIFY debtorno VARCHAR(32);
ALTER TABLE custbranch MODIFY debtorno VARCHAR(32);
ALTER TABLE custitem MODIFY debtorno VARCHAR(32);
ALTER TABLE contracts MODIFY debtorno VARCHAR(32);
-- (add any others)

-- Recreate all FKs
ALTER TABLE custbranch ADD CONSTRAINT custbranch_ibfk_1 
  FOREIGN KEY (debtorno) REFERENCES debtorsmaster(debtorno);
ALTER TABLE custitem ADD CONSTRAINT custitem_ibfk_2 
  FOREIGN KEY (debtorno) REFERENCES debtorsmaster(debtorno);  -- verify this is correct
ALTER TABLE contracts ADD CONSTRAINT contracts_ibfk_1 
  FOREIGN KEY (debtorno) REFERENCES custbranch(debtorno);    -- verify this is correct

-- Drop remaining FKs
ALTER TABLE recurringsalesorders DROP FOREIGN KEY recurringsalesorders_ibfk_1;
ALTER TABLE salesorders DROP FOREIGN KEY salesorders_ibfk_1;
ALTER TABLE orderdeliverydifferenceslog DROP FOREIGN KEY orderdeliverydifferenceslog_ibfk_2;

-- Modify debtorno in remaining tables
ALTER TABLE custbranch MODIFY debtorno VARCHAR(32);
ALTER TABLE recurringsalesorders MODIFY debtorno VARCHAR(32);
ALTER TABLE salesorders MODIFY debtorno VARCHAR(32);
ALTER TABLE orderdeliverydifferenceslog MODIFY debtorno VARCHAR(32);
ALTER TABLE debtorsmaster MODIFY debtorno VARCHAR(32);
ALTER TABLE debtorsmaster MODIFY editransport VARCHAR(32);

-- Recreate the FKs (verify targets before running)
ALTER TABLE recurringsalesorders ADD CONSTRAINT recurringsalesorders_ibfk_1
  FOREIGN KEY (debtorno) REFERENCES debtorsmaster(debtorno);
ALTER TABLE salesorders ADD CONSTRAINT salesorders_ibfk_1
  FOREIGN KEY (debtorno) REFERENCES debtorsmaster(debtorno);
ALTER TABLE orderdeliverydifferenceslog ADD CONSTRAINT orderdeliverydifferenceslog_ibfk_2
  FOREIGN KEY (debtorno) REFERENCES custbranch(debtorno);



ALTER TABLE custbranch MODIFY branchcode VARCHAR(32);
-- Drop the FK on custbranch
ALTER TABLE custbranch DROP FOREIGN KEY custbranch_ibfk_1;
-- Now modify
ALTER TABLE custbranch MODIFY debtorno VARCHAR(32);
-- Recreate the FK
ALTER TABLE custbranch ADD CONSTRAINT custbranch_ibfk_1
  FOREIGN KEY (debtorno) REFERENCES debtorsmaster(debtorno);


================
SELECT TABLE_NAME, COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'zerp_backend'
  AND COLUMN_NAME = 'debtorno'
  AND CHARACTER_MAXIMUM_LENGTH < 32;
================

-- Drop all FKs blocking custbranch.debtorno
ALTER TABLE contracts DROP FOREIGN KEY contracts_ibfk_1;
ALTER TABLE custbranch DROP FOREIGN KEY custbranch_ibfk_1;

-- Modify
ALTER TABLE custbranch MODIFY debtorno VARCHAR(32);
ALTER TABLE contracts MODIFY debtorno VARCHAR(32);

-- Recreate both FKs
ALTER TABLE custbranch ADD CONSTRAINT custbranch_ibfk_1
  FOREIGN KEY (debtorno) REFERENCES debtorsmaster(debtorno);
ALTER TABLE contracts ADD CONSTRAINT contracts_ibfk_1
  FOREIGN KEY (debtorno) REFERENCES custbranch(debtorno);

  =======
  -- Drop FKs blocking custbranch
ALTER TABLE contracts DROP FOREIGN KEY contracts_ibfk_1;
ALTER TABLE orderdeliverydifferenceslog DROP FOREIGN KEY orderdeliverydifferenceslog_ibfk_2;
ALTER TABLE custbranch DROP FOREIGN KEY custbranch_ibfk_1;

-- Modify all 7 remaining tables
ALTER TABLE custbranch MODIFY debtorno VARCHAR(32);
ALTER TABLE custnotes MODIFY debtorno VARCHAR(32);
ALTER TABLE sellthroughsupport MODIFY debtorno VARCHAR(32);
ALTER TABLE prices MODIFY debtorno VARCHAR(32);
ALTER TABLE custcontacts MODIFY debtorno VARCHAR(32);
ALTER TABLE debtortrans MODIFY debtorno VARCHAR(32);
ALTER TABLE stockmoves MODIFY debtorno VARCHAR(32);

-- Also modify debtorno in the FK child tables if not already done
ALTER TABLE orderdeliverydifferenceslog MODIFY debtorno VARCHAR(32);

-- Recreate all FKs
ALTER TABLE custbranch ADD CONSTRAINT custbranch_ibfk_1
  FOREIGN KEY (debtorno) REFERENCES debtorsmaster(debtorno);
ALTER TABLE contracts ADD CONSTRAINT contracts_ibfk_1
  FOREIGN KEY (debtorno) REFERENCES custbranch(debtorno);
ALTER TABLE orderdeliverydifferenceslog ADD CONSTRAINT orderdeliverydifferenceslog_ibfk_2
  FOREIGN KEY (debtorno) REFERENCES custbranch(debtorno);

  
  After this, verify everything is done:
sqlSELECT TABLE_NAME, COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'zerp_backend'
  AND COLUMN_NAME = 'debtorno'
  AND CHARACTER_MAXIMUM_LENGTH < 32;
That should return 0 rows and you'll be done.