--
-- Script run to make a migration of module version x.x.x to module version y.y.y
--
 

ALTER TABLE llx_cabinetmed_cons ADD COLUMN entity integer DEFAULT 1 NOT NULL;

ALTER TABLE llx_cabinetmed_motifcons ADD COLUMN lang varchar(12) NULL;

ALTER TABLE llx_cabinetmed_examenprescrit ADD COLUMN lang varchar(12) NULL;

ALTER TABLE llx_cabinetmed_c_examconclusion ADD COLUMN lang varchar(12) NULL;

CREATE TABLE llx_cabinetmed_medicaments (
  rowid             integer AUTO_INCREMENT PRIMARY KEY,
  code              varchar(8) NOT NULL,
  label             varchar(64) NOT NULL,
  active            smallint DEFAULT 1  NOT NULL,
  position          integer DEFAULT 10,  
  lang              varchar(12) NULL,
) ENGINE=innodb;

