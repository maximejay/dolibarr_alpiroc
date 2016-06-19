-- ===================================================================
-- Copyright (C) 2014      Maxime Jay-Allemand   <maxime.jay-allemand@laposte.net>
-- Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
--
-- ===================================================================
-- INSERT INTO llx_alpiroc (name,content) VALUES ('1','NomTemplate','');

--INSERT INTO llx_alpiroc (name,remerciement,titre1,titre2,phraserappel,phraserappelfin,selected_template,acompte,signaturearea,thanksarea,displayacompte,posadresse,dispreglement,dispcondreglement,dispslogan,displaypuqtx,soustotaux,brouillon,rappel,notepublic,contact,disptva) VALUES ('default','write your own sentence','Title of the public note','Contact or responsible','Reminder sentence','Reminder sentence','1','0','0','0','0','0','0','0','0','0','0','0','0','1','1','1');
INSERT INTO llx_alpiroc (name,remerciement,titre1,titre2,phraserappel,phraserappelfin,selected_template,acompte,signaturearea,thanksarea,displayacompte,posadresse,dispreglement,dispcondreglement,dispslogan,displaypuqtx,soustotaux,brouillon,rappel,notepublic,contact) VALUES ('default','write your own sentence','Title of the public note','Contact or responsible','Reminder sentence','Reminder sentence','1','0','0','0','0','0','0','0','0','0','0','0','0','1','1');
--Pour les mises à jour, à l'activation du module tout les valeurs des colonnes ajouté sont à null. Elle sont donc passée à la valeur par default
UPDATE llx_alpiroc SET disptva='1' WHERE disptva IS NULL ;
UPDATE llx_alpiroc SET repeathead='1' WHERE repeathead IS NULL ;
UPDATE llx_alpiroc SET hidedetails='0' WHERE hidedetails IS NULL ;
UPDATE llx_alpiroc SET head='alpiroc' WHERE head IS NULL ;
UPDATE llx_alpiroc SET dispprivatenote='0' WHERE dispprivatenote IS NULL ;
UPDATE llx_alpiroc SET affichemmemr='1' WHERE affichemmemr IS NULL ;
UPDATE llx_alpiroc SET cvg='0' WHERE cvg IS NULL ;
UPDATE llx_alpiroc SET cvg_doc='' WHERE cvg_doc IS NULL ;
UPDATE llx_alpiroc SET paymentdone='0' WHERE paymentdone IS NULL ;

INSERT INTO llx_extrafields (name,entity,elementtype,tms,label,type,size,fieldunique,fieldrequired,pos,param) VALUES ('titrealpiroc','1','propaldet',NULL,'Insérer un titre au dessus','varchar','255','0','0','0','a:1:{s:7:"options";a:1:{s:0:"";N;}}');
INSERT INTO llx_extrafields (name,entity,elementtype,tms,label,type,size,fieldunique,fieldrequired,pos,param) VALUES ('titrealpiroc','1','facturedet',NULL,'Insérer un titre au dessus','varchar','255','0','0','0','a:1:{s:7:"options";a:1:{s:0:"";N;}}');
INSERT INTO llx_extrafields (name,entity,elementtype,tms,label,type,size,fieldunique,fieldrequired,pos,param) VALUES ('titrealpiroc','1','commandedet',NULL,'Insérer un titre au dessus','varchar','255','0','0','0','a:1:{s:7:"options";a:1:{s:0:"";N;}}');


INSERT INTO llx_extrafields (name,entity,elementtype,tms,label,type,size,fieldunique,fieldrequired,pos,param) VALUES ('profilalpiroc','1','propal',NULL,'choisir le type de profil alpiroc','sellist','','0','0','0','a:1:{s:7:"options";a:1:{s:19:"alpiroc:name:name::";N;}}');
INSERT INTO llx_extrafields (name,entity,elementtype,tms,label,type,size,fieldunique,fieldrequired,pos,param) VALUES ('profilalpiroc','1','facture',NULL,'choisir le type de profil alpiroc','sellist','','0','0','0','a:1:{s:7:"options";a:1:{s:19:"alpiroc:name:name::";N;}}');
INSERT INTO llx_extrafields (name,entity,elementtype,tms,label,type,size,fieldunique,fieldrequired,pos,param) VALUES ('profilalpiroc','1','commande',NULL,'choisir le type de profil alpiroc','sellist','','0','0','0','a:1:{s:7:"options";a:1:{s:19:"alpiroc:name:name::";N;}}');

ALTER TABLE llx_propaldet_extrafields ADD titrealpiroc VARCHAR(255);
ALTER TABLE llx_facturedet_extrafields ADD titrealpiroc VARCHAR(255);
ALTER TABLE llx_commandedet_extrafields ADD titrealpiroc VARCHAR(255);

ALTER TABLE llx_propal_extrafields ADD profilalpiroc VARCHAR(255);
ALTER TABLE llx_facture_extrafields ADD profilalpiroc VARCHAR(255);
ALTER TABLE llx_commande_extrafields ADD profilalpiroc VARCHAR(255);

UPDATE llx_propal_extrafields SET profilalpiroc='default';
UPDATE llx_facture_extrafields SET profilalpiroc='default';
UPDATE llx_commande_extrafields SET profilalpiroc='default';
