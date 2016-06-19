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
create table if not exists llx_alpiroc
(
  rowid            	int(11) NOT NULL AUTO_INCREMENT,
  name       		varchar(25) DEFAULT NULL, -- id of the associated invoice
  remerciement        	varchar(512) DEFAULT NULL, -- a dummy custom field
  titre1        	varchar(512) DEFAULT NULL, -- a dummy custom field
  titre2        	varchar(512) DEFAULT NULL, -- a dummy custom field
  phraserappel       	varchar(512) DEFAULT NULL, -- a dummy custom field
  phraserappelfin       varchar(512) DEFAULT NULL, -- a dummy custom field
  selected_template     varchar(512) DEFAULT NULL, -- a dummy custom field
  acompte        	int(11) DEFAULT NULL, -- a dummy custom field
  signaturearea        	int(11) DEFAULT NULL, -- a dummy custom field
  thanksarea        	int(11) DEFAULT NULL, -- a dummy custom field
  displayacompte        int(11) DEFAULT NULL, -- a dummy custom field
  posadresse        	int(11) DEFAULT NULL, -- a dummy custom field
  dispreglement        	int(11) DEFAULT NULL, -- a dummy custom field
  dispcondreglement     int(11) DEFAULT NULL, -- a dummy custom field
  dispslogan        	int(11) DEFAULT NULL, -- a dummy custom field
  displaypuqtx        	int(11) DEFAULT NULL, -- a dummy custom field
  soustotaux        	int(11) DEFAULT NULL, -- a dummy custom field
  brouillon        	int(11) DEFAULT NULL, -- a dummy custom field
  rappel        	int(11) DEFAULT NULL, -- a dummy custom field
  notepublic        	int(11) DEFAULT NULL, -- a dummy custom field
  contact        	int(11) DEFAULT NULL, -- a dummy custom field
  disptva        	int(11) DEFAULT NULL, -- a dummy custom field
  repeathead     	int(11) DEFAULT NULL, -- a dummy custom field
  hidedetails     	int(11) DEFAULT NULL, -- a dummy custom field
  head     	varchar(25) DEFAULT "alpiroc", -- a dummy custom field
  dispprivatenote     	int(11) DEFAULT NULL, -- a dummy custom field
  affichemmemr     	int(11) DEFAULT NULL, -- a dummy custom field
  cvg     	int(11) DEFAULT NULL, -- a dummy custom field
  cvg_doc     	varchar(512) DEFAULT NULL, -- a dummy custom field
  paymentdone     	int(11) DEFAULT NULL, -- a dummy custom field
  PRIMARY KEY (rowid),
  UNIQUE (name)
) ENGINE=InnoDB AUTO_INCREMENT=1 ;

-- Pour les mises à jour : on ajoute les colonnes par des commandes séparées. Au moins lors de l'activation du module, les colonne maquantes sont automatiquement ajoutées
ALTER TABLE llx_alpiroc ADD disptva int(11) ;
ALTER TABLE llx_alpiroc ADD repeathead int(11) ;
ALTER TABLE llx_alpiroc ADD hidedetails int(11) ;
ALTER TABLE llx_alpiroc ADD head varchar(25) ;
ALTER TABLE llx_alpiroc ADD dispprivatenote int(11) ;
ALTER TABLE llx_alpiroc ADD affichemmemr int(11) ;
ALTER TABLE llx_alpiroc ADD cvg int(11) ;
ALTER TABLE llx_alpiroc ADD cvg_doc varchar(512) ;
ALTER TABLE llx_alpiroc ADD paymentdone int(11) ;
