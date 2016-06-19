<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014      Maxime Jay-Allemand   <maxime.jay-allemand@laposte.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       dev/skeletons/alpiroc.class.php
 *  \ingroup    mymodule othermodule1 othermodule2
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *				Initialy built by build_class_from_table on 2014-09-15 20:40
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");



/**
 *	Put here description of your class
 */
class Alpiroc extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='alpiroc';			//!< Id that identify managed objects
	var $table_element='alpiroc';		//!< Name of table without prefix where object is stored

    	var $id;
    
	var $name;
	var $content;
	var $profil;
	var $tab_name=array();
    


    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }



    
    /**
     *  Load object in memory from the database
     *
     *  @param	int		$id    Id object
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetchValueFromSelectedTemplate($col)
    {
		
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.name,";
		$sql.= " t.".$col.",";
		$sql.= " t.selected_template";

		
        $sql.= " FROM ".MAIN_DB_PREFIX."alpiroc as t";
        $sql.= " WHERE t.selected_template LIKE '1'";

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid; 
				$this->name = $col;
				$this->content = $obj->$col;
				$this->profil = $obj->name;

                
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }


	/**
     *  Load object in memory from the database Used to retrieve value from profil name during the generation of the pdf
     *
     *  @param	int		$id    Id object
     *  @return int          	<0 if KO, >0 if OK
     */
function fetchValueFromProfil($col,$profil)
    {
		
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.name,";
		$sql.= " t.".$col.",";
		$sql.= " t.selected_template";

		
        $sql.= " FROM ".MAIN_DB_PREFIX."alpiroc as t";
        $sql.= " WHERE t.name LIKE '".$profil."'";

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid; 
				$this->name = $col;
				$this->content = $obj->$col;
				$this->profil = $obj->name;

                
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }




    /**
     *  Load object in memory from the database
     *
     *  @param	string		$name   name object
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch_titre($id,$key,$tab_name)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " rowid,";
		$sql.= $key;

		
        $sql.= " FROM ".MAIN_DB_PREFIX.$tab_name."_extrafields";
        $sql.= " WHERE fk_object = '".$id."'";
    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
    	
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
                
				$this->name = $obj->$key;
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }



    /**
     *  Load object in memory from the database
     *
     *  @param	string		$name   name object
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch_selectprofil($fk_obj,$type)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " profilalpiroc";
		
        $sql.= " FROM ".MAIN_DB_PREFIX.$type."_extrafields";
        $sql.= " WHERE fk_object='".$fk_obj."'";
    	//~ dol_syslog(get_class($this)."MAXOU::fetch sql=".$sql, LOG_DEBUG);
    	
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);  
				$this->name = $obj->profilalpiroc;
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }



 

/**
     *  Permet de récupérer tous les nom de profil alpiroc dans un tableau
     *
     *  @param	string		$tab_name  nom de la table alpiroc
     *  @return array          	liste des profils
     */
 function fetch_profil($tab_name)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " name";
        $sql.= " FROM ".MAIN_DB_PREFIX.$tab_name;
    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);

	$resql=$this->db->query($sql);
	if ($resql){
		//dol_syslog(get_class($this)."::maxdebug_numrows=".$this->db->num_rows($resql), LOG_DEBUG);
		$nb_rows=$this->db->num_rows($resql);
		$i=0;
		if($nb_rows>0){
			while ($i<$nb_rows){
				$row = $this->db->fetch_object($resql);  
				$result[$i]=$row->name;
				//dol_syslog(get_class($this)."::maxdebug=:".$row->name, LOG_DEBUG);
				$i=$i+1;
			}
		}else{
			dol_syslog(get_class($this)."::error=resultat vide nb_rows=:".$nb_rows, LOG_DEBUG);
			return -1;
		}	
	}else{
		$this->error="Error ".$this->db->lasterror();
		dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
		return -1;
	}

	//dol_syslog(get_class($this)."::maxdebug=".$result[0], LOG_DEBUG);
	$this->tab_name =$result;
	//dol_syslog(get_class($this)."::maxdebug_affectation=".$this->tab_name[0], LOG_DEBUG);
        $this->db->free($resql);

        return 1;
    }



    /**
     *  Update object into database
     *
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function update()
    {
    	global $conf, $langs;
		$error=0;
		$pseudo= $this->db->escape($this->profil);
		$pseudo=trim($pseudo);
		
        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."alpiroc SET";
		$sql.= " ".$this->name."=".(isset($this->content)?"'".$this->db->escape($this->content)."'":"null")."";
		$sql.= " WHERE selected_template='1'";

		$this->db->begin();
		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
    }



 /**
     *  Change value of the selected profil in the database
     *
     *  @return int     		   	 <0 if KO, >0 if OK
     */
function update_profil()
    {
    	global $conf, $langs;
		$error=0;
		
		// Clean parameters
		$pseudo= $this->db->escape($this->profil);
		$pseudo=trim($pseudo);

        // Update request
        //Set all to 0
        $sql = "UPDATE ".MAIN_DB_PREFIX."alpiroc SET";
		$sql.= " selected_template='0'";
		
		$this->db->begin();
		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
		}
		
        // Set name=pseudo to 1
        $sql = "UPDATE ".MAIN_DB_PREFIX."alpiroc SET";
		$sql.= " selected_template='1'";
        $sql.= " WHERE name='".$pseudo."'";
        
		$this->db->begin();
		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}	
    }




 	/**
	 *  Delete a profil
	 *
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete_profil()
	{
		global $conf, $langs;
		
		$pseudo= $this->db->escape($this->profil);
		$pseudo=trim($pseudo);
		
		if (! $error)
		{
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX."alpiroc";
    		$sql.= " WHERE name='".$pseudo."'";

    		dol_syslog(get_class($this)."::delete sql=".$sql);
    		$resql = $this->db->query($sql);
        	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}
		
        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
		}
		else
		{
			$this->db->commit();
		}
		
		$error=0;
		$this->db->begin();
		//Supprime la configuration pour chaque proposition commercial
		if (! $error)
		{
    		$sql = "UPDATE ".MAIN_DB_PREFIX."propal_extrafields";
    		$sql.= " SET profilalpiroc=''";
			$sql.= " WHERE profilalpiroc='".$pseudo."'";
			
    		dol_syslog(get_class($this)."::delete sql=".$sql);
    		$resql = $this->db->query($sql);
        	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}
		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
		}
		
		
		
		$error=0;
		$this->db->begin();
		//Supprime la configuration pour chaque commande
		if (! $error)
		{
    		$sql = "UPDATE ".MAIN_DB_PREFIX."commande_extrafields";
    		$sql.= " SET profilalpiroc=''";
			$sql.= " WHERE profilalpiroc='".$pseudo."'";
			
    		dol_syslog(get_class($this)."::delete sql=".$sql);
    		$resql = $this->db->query($sql);
        	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}
		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
		}
		
		
		
		//Supprime la configuration pour chaque facture
		$error=0;
		$this->db->begin();
		if (! $error)
		{
    		$sql = "UPDATE ".MAIN_DB_PREFIX."facture_extrafields";
    		$sql.= " SET profilalpiroc=''";
			$sql.= " WHERE profilalpiroc='".$pseudo."'";
			
    		dol_syslog(get_class($this)."::delete sql=".$sql);
    		$resql = $this->db->query($sql);
        	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}
		// Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
			
	}



	/**
	 *	Create a new profil
	 *
	 *	@param	string		$pseudo     Name of the template
	 * 	@return	int					1 if succes -1 if error
	 */
	function create_template($pseudo){
		
		$pseudo= $this->db->escape($pseudo);
		$pseudo=trim($pseudo);
		
		//Set all to 0
        $sql = "UPDATE ".MAIN_DB_PREFIX."alpiroc SET";
		$sql.= " selected_template='0'";
		
		$this->db->begin();
		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
       
		if ($resql)
        {	
			$this->db->free($resql);
			$this->db->commit();
		}else{
			$this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
		}
		
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."alpiroc (name,remerciement,titre1,titre2,phraserappel,phraserappelfin,selected_template,acompte,signaturearea,thanksarea,displayacompte,posadresse,dispreglement,dispcondreglement,dispslogan,displaypuqtx,soustotaux,brouillon,rappel,notepublic,contact,disptva,repeathead,hidedetails,head,dispprivatenote,affichemmemr,cvg,cvg_doc,paymentdone) ";
		$sql.= "VALUES ('".$pseudo."','write your own sentence','Title of the public note','Contact or responsible','Reminder sentence','Reminder sentence','1','0','0','0','0','0','0','0','0','0','0','0','0','1','1','1','1','0','alpiroc','1','1','0','','0')";
		
		$this->db->begin();
		dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
       
		if ($resql)
        {	
			$this->db->free($resql);
			$this->db->commit();
			
			return 1;
		}else{
			$this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
		}
	}


	//Function
	//Remove Alpiroc DataBase
	function delete_alpiroc_db(){
		$sql_table=array(
			"DROP TABLE ".MAIN_DB_PREFIX."alpiroc;",
			
			//Fait en désactivant le module Alpiroc !
			//"ALTER TABLE ".MAIN_DB_PREFIX."propal_extrafields DROP COLUMN profilalpiroc;",
			//"ALTER TABLE ".MAIN_DB_PREFIX."facture_extrafields DROP COLUMN profilalpiroc;",
			//"ALTER TABLE ".MAIN_DB_PREFIX."commande_extrafields DROP COLUMN profilalpiroc;",
			
			"DELETE FROM ".MAIN_DB_PREFIX."extrafields  WHERE name='titrealpiroc';",
			"DELETE FROM ".MAIN_DB_PREFIX."extrafields  WHERE name='profilalpiroc';",
			
			"DELETE FROM ".MAIN_DB_PREFIX."document_model  WHERE nom='alpiroc_fact';",
			"DELETE FROM ".MAIN_DB_PREFIX."document_model  WHERE nom='alpiroc_com';",
			"DELETE FROM ".MAIN_DB_PREFIX."document_model  WHERE nom='alpiroc';",
		);
		
		$error=0;
		foreach($sql_table as $sql){
			$this->db->begin();
			dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql)
			{	
				$this->db->free($resql);
				$this->db->commit();
			}else{
				$this->error="Error ".$this->db->lasterror();
				dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
				$this->db->rollback();
				$error=$error+1;
			}
		}
		
		if ($error>0){
			return -1;
		}else{
			return 1;
		}
	}



	/**
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		
		$this->name='';
		$this->content='';
		$this->profil='';
		$tab_name=array();
		
	}

}
