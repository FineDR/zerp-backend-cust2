UPDATE www_users SET blocked = 0, password = '$2y$12$l5Mv1Tcn2AOX9wQm9yJh4u6GV5VuLf2l3mpunJXq7MJhHAr.p2rNG' WHERE userid = 'admin';
UPDATE config SET confvalue = '0' WHERE confname = 'MonthsAuditTrail';
