--
-- Be carefull to requests order.
-- This file must be loaded by calling /install/index.php page
-- when current version is 3.6.0 or higher. 
--
-- To rename a table:       ALTER TABLE llx_table RENAME TO llx_table_new;
-- To add a column:         ALTER TABLE llx_table ADD COLUMN newcol varchar(60) NOT NULL DEFAULT '0' AFTER existingcol;
-- To rename a column:      ALTER TABLE llx_table CHANGE COLUMN oldname newname varchar(60);
-- To drop a column:        ALTER TABLE llx_table DROP COLUMN oldname;
-- To change type of field: ALTER TABLE llx_table MODIFY COLUMN name varchar(60);
-- To drop a foreign key:   ALTER TABLE llx_table DROP FOREIGN KEY fk_name;
-- To restrict request to Mysql version x.y use -- VMYSQLx.y
-- To restrict request to Pgsql version x.y use -- VPGSQLx.y


-- You can execut this script with URL:
--
-- /install/upgrade.php?dirmodule=cabinetmed&versionfrom=5.0.0&versionto=6.0.0


ALTER TABLE llx_cabinetmed_cons ADD COLUMN fk_user_creation integer;

update llx_cabinetmed_cons set fk_user_creation = fk_user where fk_user_creation is null or fk_user_creation = 0;
