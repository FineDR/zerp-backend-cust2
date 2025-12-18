<?php

//Scripts to update the database
ALTER TABLE salesorders MODIFY deladd6 VARCHAR(32);

INSERT INTO debtortrans (debtorno, branchcode, trandate, transno, type, ovamount, ovgst, salesperson, prd)
VALUES ('NS1369/005', 'NS1369/005','', '38', '10', '5000', '150', 'SP001', '');