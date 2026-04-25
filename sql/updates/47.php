<?php
// INCREASE SIZE OF debtorsmaster.debtorno FROM 10 CHAR TO 32 CHAR
// This is needed to accommodate Student Registration Number (RegNo)
// by Juma Lungo, Tanzania

// 1. delete foreign key constraints (Bottom-Up-Drop aka BUD)
//
// SQL query to list foreign key constraints on debtorsmaster.debtorno:

//     SELECT 
//         TABLE_NAME AS 'Referencing Table', 
//         COLUMN_NAME AS 'Foreign Key Column', 
//         CONSTRAINT_NAME AS 'Constraint Name'
//     FROM 
//         information_schema.KEY_COLUMN_USAGE
//     WHERE 
//         REFERENCED_TABLE_NAME = 'debtorsmaster' 
//         AND REFERENCED_COLUMN_NAME = 'debtorno'
//         AND TABLE_SCHEMA = DATABASE(); -- Limits results to your current webERP database
// +-----------------------------+--------------------+------------------------------------+
// | Referencing Table           | Foreign Key Column | Constraint Name                    |
// +-----------------------------+--------------------+------------------------------------+
// | custbranch                  | debtorno           | custbranch_ibfk_1                  |
// | orderdeliverydifferenceslog | debtorno           | orderdeliverydifferenceslog_ibfk_2 |
// | custitem                    | debtorno           | custitem_ibfk_2                    |
// +-----------------------------+--------------------+------------------------------------+

// 1.1 Drop all debtorno FKs
// -- Drop all debtorno FKs
// ALTER TABLE contracts                   DROP FOREIGN KEY contracts_ibfk_1;
// ALTER TABLE custbranch                  DROP FOREIGN KEY custbranch_ibfk_1;
// ALTER TABLE custitem                    DROP FOREIGN KEY custitem_ibfk_2;
// ALTER TABLE orderdeliverydifferenceslog DROP FOREIGN KEY orderdeliverydifferenceslog_ibfk_2;
// ALTER TABLE recurringsalesorders        DROP FOREIGN KEY recurringsalesorders_ibfk_1;
// ALTER TABLE salesorders                 DROP FOREIGN KEY salesorders_ibfk_1;

// DropConstraint($Table, $Constraint)
DropConstraint('contracts', 'contracts_ibfk_1');
DropConstraint('custbranch', 'custbranch_ibfk_1');
DropConstraint('custitem', 'custitem_ibfk_2');
DropConstraint('orderdeliverydifferenceslog', 'orderdeliverydifferenceslog_ibfk_2');
DropConstraint('recurringsalesorders', 'recurringsalesorders_ibfk_1');
DropConstraint('salesorders', 'salesorders_ibfk_1');

//2. Listdown all tables with field debtorno that will be needed to be changed fron char(10) to char(32)
// SELECT TABLE_NAME, COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH
// FROM information_schema.COLUMNS
// WHERE TABLE_SCHEMA = 'zerp_backend'
// AND COLUMN_NAME = 'debtorno'
// ORDER BY CHARACTER_MAXIMUM_LENGTH, TABLE_NAME;
// +-----------------------------+-------------+--------------------------+
// | TABLE_NAME                  | COLUMN_NAME | CHARACTER_MAXIMUM_LENGTH |
// +-----------------------------+-------------+--------------------------+
// | contracts                   | debtorno    |                       10 |
// | custbranch                  | debtorno    |                       10 |
// | custcontacts                | debtorno    |                       10 |
// | custitem                    | debtorno    |                       10 |
// | custnotes                   | debtorno    |                       10 |
// | debtorsmaster               | debtorno    |                       10 |
// | debtortrans                 | debtorno    |                       10 |
// | orderdeliverydifferenceslog | debtorno    |                       10 |
// | prices                      | debtorno    |                       10 |
// | recurringsalesorders        | debtorno    |                       10 |
// | salesorders                 | debtorno    |                       10 |
// | sellthroughsupport          | debtorno    |                       10 |
// | stockmoves                  | debtorno    |                       10 |
// +-----------------------------+-------------+--------------------------+

// 3. Alter the two remaining tables by changing "debtorno" parent, child and grandchild foreign key columns (bottom-up order, same as BUD for fk constraints)
// -- Alter the two remaining tables
// ALTER TABLE contracts     MODIFY COLUMN debtorno VARCHAR(32) NOT NULL;
// ALTER TABLE custbranch MODIFY COLUMN debtorno VARCHAR(32) NOT NULL;
// ALTER TABLE custcontacts      MODIFY COLUMN debtorno VARCHAR(32) NOT NULL;
// ALTER TABLE custnotes MODIFY COLUMN debtorno VARCHAR(32) NOT NULL;
// ALTER TABLE orderdeliverydifferenceslog      MODIFY COLUMN debtorno VARCHAR(32) NOT NULL;
// ALTER TABLE debtortrans MODIFY COLUMN debtorno VARCHAR(32) NOT NULL;
// ALTER TABLE prices MODIFY COLUMN debtorno VARCHAR(32) NOT NULL;
// ALTER TABLE recurringsalesorders      MODIFY COLUMN debtorno VARCHAR(32) NOT NULL;
// ALTER TABLE salesorders MODIFY COLUMN debtorno VARCHAR(32) NOT NULL;
// ALTER TABLE sellthroughsupport      MODIFY COLUMN debtorno VARCHAR(32) NOT NULL;
// ALTER TABLE stockmoves MODIFY COLUMN debtorno VARCHAR(32) NOT NULL;
// ALTER TABLE custitem      MODIFY COLUMN debtorno VARCHAR(32) NOT NULL;
// ALTER TABLE debtorsmaster MODIFY COLUMN debtorno VARCHAR(32) NOT NULL;
// ALTER TABLE custbranch           MODIFY COLUMN branchcode VARCHAR(32) NOT NULL;
// ALTER TABLE contracts            MODIFY COLUMN branchcode VARCHAR(32) NOT NULL;
// ALTER TABLE recurringsalesorders MODIFY COLUMN branchcode VARCHAR(32) NOT NULL;
// ALTER TABLE prices               MODIFY COLUMN branchcode VARCHAR(32) NOT NULL;
// ALTER TABLE salesorders          MODIFY COLUMN branchcode VARCHAR(32) NOT NULL;
// ALTER TABLE debtortrans          MODIFY COLUMN branchcode VARCHAR(32) NOT NULL;
// ALTER TABLE www_users            MODIFY COLUMN branchcode VARCHAR(32) NOT NULL;
// ALTER TABLE stockmoves           MODIFY COLUMN branchcode VARCHAR(32) NOT NULL;

// ChangeColumnSize($Column, $Table, $Type, $Null, $Default, $Size)
ChangeColumnSize('debtorno', 'debtorsmaster', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('debtorno', 'contracts', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('debtorno', 'custbranch', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('debtorno', 'custcontacts', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('debtorno', 'custitem', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('debtorno', 'custnotes', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('debtorno', 'debtortrans', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('debtorno', 'orderdeliverydifferenceslog', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('debtorno', 'prices', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('debtorno', 'recurringsalesorders', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('debtorno', 'salesorders', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('debtorno', 'sellthroughsupport', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('debtorno', 'stockmoves', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('branchcode', 'custbranch', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('branchcode', 'contracts', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('branchcode', 'recurringsalesorders', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('branchcode', 'prices', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('branchcode', 'salesorders', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('branchcode', 'debtortrans', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('branchcode', 'www_users', 'VARCHAR(34)', ' NOT NULL ', '', '32');
ChangeColumnSize('branchcode', 'stockmoves', 'VARCHAR(34)', ' NOT NULL ', '', '32');


// 3. Recreate all FKs (Top-Down-Add aka TDA)
// -- Recreate all FKs
// ALTER TABLE custbranch                  ADD CONSTRAINT custbranch_ibfk_1                  FOREIGN KEY (debtorno) REFERENCES debtorsmaster(debtorno);
// ALTER TABLE custitem                    ADD CONSTRAINT custitem_ibfk_2                    FOREIGN KEY (debtorno) REFERENCES debtorsmaster(debtorno);
// ALTER TABLE orderdeliverydifferenceslog ADD CONSTRAINT orderdeliverydifferenceslog_ibfk_2 FOREIGN KEY (debtorno) REFERENCES debtorsmaster(debtorno);
// ALTER TABLE contracts                   ADD CONSTRAINT contracts_ibfk_1                   FOREIGN KEY (debtorno, branchcode) REFERENCES custbranch(debtorno, branchcode);
// ALTER TABLE recurringsalesorders        ADD CONSTRAINT recurringsalesorders_ibfk_1        FOREIGN KEY (debtorno, branchcode) REFERENCES custbranch(debtorno, branchcode);
// ALTER TABLE salesorders                 ADD CONSTRAINT salesorders_ibfk_1                 FOREIGN KEY (debtorno, branchcode) REFERENCES custbranch(debtorno, branchcode);

// AddConstraint($Table, $Constraint, $Field, $ReferenceTable, $ReferenceField)
AddConstraint('custbranch', 'custbranch_ibfk_1', 'debtorno', 'debtorsmaster', 'debtorno');
AddConstraint('custitem', 'custitem_ibfk_2', 'debtorno', 'debtorsmaster', 'debtorno');
AddConstraint('orderdeliverydifferenceslog', 'orderdeliverydifferenceslog_ibfk_2', 'debtorno', 'debtorsmaster', 'debtorno');
AddConstraint('contracts', 'contracts_ibfk_1', 'debtorno,branchcode', 'custbranch', 'debtorno,branchcode');
AddConstraint('recurringsalesorders', 'recurringsalesorders_ibfk_1', 'debtorno,branchcode', 'custbranch', 'debtorno,branchcode');
AddConstraint('salesorders', 'salesorders_ibfk_1', 'debtorno,branchcode', 'custbranch', 'debtorno,branchcode');



// -- Final verify
// SELECT TABLE_NAME, COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH
// FROM information_schema.COLUMNS
// WHERE TABLE_SCHEMA = 'zerp_backend'
// AND COLUMN_NAME = 'debtorno'
// ORDER BY CHARACTER_MAXIMUM_LENGTH, TABLE_NAME;

UpdateDBNo(basename(__FILE__, '.php'), __('Increase the size of debtorsmaster.debtorno'));