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



-- idprof1 -> extrafield   height
-- idprof2 -> extrafield   weigth
-- idprof3/ape -> extrafield   birthdate
-- idprof4 -> extrafield   profession


insert into llx_societe_extrafields(fk_object) (select rowid from llx_societe where rowid not in (select fk_object from llx_societe_extrafields));

update llx_societe_extrafields as se set height = (select idprof1 from llx_societe as s where s.rowid = se.fk_object) where prof is null or prof = '';

update llx_societe_extrafields as se set weigth = (select idprof2 from llx_societe as s where s.rowid = se.fk_object) where prof is null or prof = '';

update llx_societe_extrafields as se set birthdate = (select IF(ape != '' and ape is not null and substr(ape,3,1) = '/', concat(substr(ape,7,4), '-', substr(ape,4,2), '-', substr(ape,1,2)), null) from llx_societe as s where s.rowid = se.fk_object) where birthdate is null or birthdate = '' or birthdate = '2010-10-10';

update llx_societe_extrafields as se set prof = (select idprof4 from llx_societe as s where s.rowid = se.fk_object) where prof is null or prof = '';

