--
-- Script run to make a migration of module version x.x.x to module version y.y.y
--
 
 
ALTER TABLE llx_cabinetmed_cons ADD COLUMN entity integer DEFAULT 1 NOT NULL;
