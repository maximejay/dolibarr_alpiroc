<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       dev/Alpirocs/Alpiroc_page.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *					Initialy built by build_class_from_table on 2014-09-15 20:40
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';			// to work if your module directory is into the subdir custom
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
include_once (DOL_DOCUMENT_ROOT .'/alpiroc/sql/alpiroc.class.php');
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
include_once(DOL_DOCUMENT_ROOT.'/alpiroc/core/modules/fonctions/function.php');


// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
$langs->load("alpiroc@alpiroc");

// Get parameters
$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$myparam	= GETPOST('myparam','alpha');
$value = GETPOST('value','alpha');

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/
$options=array(
	"azur"=>array("thanksarea","notepublic","displayacompte","signaturearea","dispreglement","dispcondreglement","displaypuqtx","disptva","hidedetails","soustotaux","repeathead","brouillon","rappel","dispprivatenote","paymentdone"),
	"alpiroc"=>array("thanksarea","notepublic","contact","displayacompte","signaturearea","posadresse","dispreglement","dispcondreglement","dispslogan","displaypuqtx","disptva","hidedetails","soustotaux","repeathead","brouillon","rappel","dispprivatenote","affichemmemr","paymentdone")
);


$object=new Alpiroc($db);
$object->fetchValueFromSelectedTemplate("selected_template");


if ($action=='selecttemplate'){
	if ($_POST["suppr"]=="Supprimer"){
		$object=new Alpiroc($db);
		$object->profil=$_POST["selecttemplate"];
		$result=$object->delete_profil();
		$object->profil=null;
	}elseif($_POST["suppr"]=="Selectionner"){
		$object=new Alpiroc($db);
		$object->profil=$_POST["selecttemplate"];
		$result=$object->update_profil();
	}
}


//Sélection de l'entête : par defaut c'est alpiroc
$head="alpiroc";
if ($action=='selecthead'){
	$object=new Alpiroc($db);
	$object->content=$_POST["selecthead"];
	$object->name="head";
	$result=$object->update();
	$head=$object->content;
}



if ($object->content!=0){
	$profil=$object->profil;
}else{
	$profil=false;
}


if ($action == 'add')
{
	$object=new Alpiroc($db);
	$object->content=$_POST["content"];
	$object->name=$_POST["value"];
	$object->profil=$profil;
	$result=$object->update();
}


if ($action == 'switch'){	
	//Get id, name and value :
	$inv=explode("_",$value);
	$name=$inv[0];
	$object=new Alpiroc($db);
	$object->content=$inv[1];
	$object->name=$inv[0];
	$object->profil=$profil;
	$result=$object->update();
}


if ($action=='template'){
	$print_alert=0;
	$message_update="";
	$object->fetch_profil("alpiroc");//récupère tout les profils différents
	$tab_template=array_unique($object->tab_name);//Créer un tableau avec des valeurs uniques
	$pseudo_new=$_POST["pseudo"];
	$pseudo_new=str_replace(array("'","\"","\\"),array("","","/"),$pseudo_new);//Les guilhemet (simple et) double ne sont pas autorisé car cela caus un pb dans les extrafields ; Le \ non plus car pb d'échapement ds requette SQL...
	if ($pseudo_new!='' AND array_search($pseudo_new,$tab_template)==false){
		$object=new Alpiroc($db);
		$result=$object->create_template($pseudo_new);
		if ($result==-1){
			$message_update="I can't create a new profile. You probably update your Alpiroc Module. Please disable and enable it again. This will probably fix this problem.";
		}
	}else{
		$print_alert=1;
	}
}



$module_active=TRUE;
if ($action=='removedb'){
	 $object->delete_alpiroc_db();
	 $module_active=FALSE;
	 
}


	/***************************************************
	* VIEW
	*
	* Put here all code to build page
	****************************************************/
	llxHeader('','Alpiroc','');
	
	$form=new Form($db);
	//~ echo $_SERVER['PHP_SELF'];
	// print title
	$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'."Back to module list".'</a>';
	//print_fiche_titre("Voici la page de configuration du module Alpiroc !",$linkback,'setup');
	print_fiche_titre($langs->trans('titre_fiche')." !",$linkback,'setup');

if ($module_active){
	
	// print long description (to help first time users and provide with a link to the wiki, kind of a contextual help) - but only if it's the customfields admin page
	dol_fiche_head();
	print($langs->trans('Description').": <br />".$langs->trans('Description_Aliproc'));
	dol_fiche_end();
	// Put here content of your page
	
	//Recupere le profil sélectionné
	$object=new Alpiroc($db);
	$object->fetchValueFromSelectedTemplate("selected_template");
	if ($object->content!=0){
		$profil=$object->profil;
	}else{
		$profil=false;
	}
	
	//Sélection d'une template
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("GestionDesTemplate").' : </td>'."\n";
	print '<td> </td>'."\n";
	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?module=alpiroc&action=template">';
	 print '<tr >';
	  print '<td>';
		   print '<label for="pseudo">'.$langs->trans("CreerNouvelleTemplate").'</label> : <input type="text" maxlength="510" name="pseudo" id="pseudo" />';
		   print '<input type="submit" value="'.$langs->trans("Creer").'" />';
		   if ($print_alert==1){print ' '.$langs->trans("Nomexistant");}
		   if (strlen($message_update)>0){print $message_update;}
	   print '</td>';
	  print '</tr >';
	print '</form>';

	$object=new Alpiroc($db);
	$object->fetch_profil("alpiroc");
	$tab_template=array_unique($object->tab_name);
	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?module=alpiroc&action=selecttemplate">';
	   print '<tr >';
	   print '<td>';
		  print ' <label for="selecttemplate">'.$langs->trans("SelectOrSuppr").' :</label><br />';
		  print ' <select name="selecttemplate" id="selecttemplate">';
			foreach ($tab_template as $template) {
				if ($template==$profil){
					print '<option value="'.htmlspecialchars($template,ENT_QUOTES).'" selected>'.$template.'</option>';
				}else{
					print '<option value="'.htmlspecialchars($template,ENT_QUOTES).'">'.$template.'</option>';
				}
			}
		  print ' </select>';
		  print ' <input type="submit" name="suppr" id="suppr" value="'.$langs->trans("Selectionner").'" />';
		  print ' <input type="submit" name="suppr" id="suppr" value="'.$langs->trans("Supprimer").'" />';
	   print '</td>';
	  print '</tr >';
	print '</form>';
	
	$object=new Alpiroc($db);
	$object->fetchValueFromSelectedTemplate("head");
	$head=$object->content;
	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?module=alpiroc&action=selecthead">';
	   print '<tr >';
	   print '<td>';
		  print ' <label for="selecthead">'.$langs->trans("SelectHead").' :</label><br />';
		  print ' <select name="selecthead" id="selecthead">';
		  
		  if($head=="azur"){
				print '<option value="azur" selected>Azur</option>';
				print '<option value="alpiroc">Alpiroc</option>';
		  }else{
			  print '<option value="alpiroc" selected>Alpiroc</option>';
			  print '<option value="azur">Azur</option>';
		  }
				
		  print ' </select>';
		  print ' <input type="submit" name="head" id="head" value="'.$langs->trans("Selectionner").'" />';
	   print '</td>';
	  print '</tr >';
	print '</form>';
	
	
	print '</tr>';
	print '</table>';


	if ($profil!=false){

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Parameters").', '.$langs->trans("TemplateSelection").' : '.$profil.' </td>'."\n";
		print '<td> </td>'."\n";
		print '<td> </td>'."\n";
		print '<tr> </tr>';



		//Bouton activation la zone de remerciement
		if (in_array("thanksarea",$options[$head],true)){
			print '</br>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("thanksarea");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}

			print '<td align="left">'."\n".$langs->trans("zone_remerciement") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."thanksarea_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."thanksarea_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
			
			//Phrase de remerciement en bas de la facture
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("remerciement");
			if ($object->content) {$value_default=$object->content;}else{$value_default="";}
			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?module=alpiroc&action=add">';
			print '<input type="hidden" name="value" value="remerciement">';
			print '<tr >';
			print '<td>'.$langs->trans("Phrase_Remerciement")." : ".'</td>';
			print '<td align="right"><input size="40" type="text" class="flat" name="content" maxlength="510" value="'.htmlspecialchars($value_default,ENT_QUOTES).'"></td>';
			print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr>';
			print '</form>';

			print '</br>';

			print '<tr> </tr>';
		}


		//Bouton activation note public
		if (in_array("notepublic",$options[$head],true)){
			print '</br>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("notepublic");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("option_notepublic") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."notepublic_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."notepublic_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';


			//Titre de la note public
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("titre1");
			if ($object->content) {$value_default=$object->content;}else{$value_default="";}
			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?module=alpiroc&action=add">';
			print '<input type="hidden" name="value" value="titre1">';
			print '<tr >';
			print '<td>'.$langs->trans("Titre_1")." : ".'</td>';
			print '<td align="right"><input size="40" type="text" class="flat" name="content" maxlength="510" value="'.htmlspecialchars($value_default,ENT_QUOTES).'"></td>';
			print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr>';
			print '</form>';

			print '</br>';
		}


		print '<tr> </tr>';
		//Bouton activation contact
		if (in_array("contact",$options[$head],true)){
			print '</br>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("contact");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("option_contact") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."contact_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."contact_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		
	
			//Titre Gestionnaire chantier
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("titre2");
			if ($object->content) {$value_default=$object->content;}else{$value_default="";}
			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?module=alpiroc&action=add">';
			print '<input type="hidden" name="value" value="titre2">';
			print '<tr >';
			print '<td>'.$langs->trans("Titre_2")." : ".'</td>';
			print '<td align="right"><input size="40" type="text" class="flat" name="content" maxlength="510" value="'.htmlspecialchars($value_default,ENT_QUOTES).'"></td>';
			print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr>';
			print '</form>';
			print '<tr> </tr>';
		}



		
		//Bouton activation de l'acompte
		if (in_array("displayacompte",$options[$head],true)){
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("displayacompte");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("display_acompte") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."displayacompte_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."displayacompte_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';

			//Acompte
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("acompte");
			if ($object->content) {$value_default=$object->content;}else{$value_default="";}
			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?module=alpiroc&action=add">';
			print '<input type="hidden" name="value" value="acompte">';
			print '<tr >';
			print '<td>'.$langs->trans("acompte")." : ".'</td>';
			print '<td align="right"><input size="10" type="text" class="flat" name="content" maxlength="3" value="'.htmlspecialchars($value_default,ENT_QUOTES).'"></td>';
			print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr>';
			print '</form>';

			
		}




		//Bouton activation d'affichage de la note privé
		if (in_array("dispprivatenote",$options[$head],true)){
			print '<tr> </tr>';
			print '</br>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("dispprivatenote");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("dispprivatenote") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."dispprivatenote_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."dispprivatenote_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		}




		//Bouton activation zone de signature
		if (in_array("signaturearea",$options[$head],true)){
			print '<tr> </tr>';
			print '</br>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("signaturearea");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("option_sign_aera") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."signaturearea_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."signaturearea_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		}

		//Bouton activation des payments deja effectuée
		if (in_array("paymentdone",$options[$head],true)){
			print '<tr> </tr>';
			print '</br>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("paymentdone");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("option_paymentdone") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."paymentdone_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."paymentdone_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		}

		//Bouton pour definir le positionnement de l'adresse dans le cadre
		if (in_array("posadresse",$options[$head],true)){
			print '<tr> </tr>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("posadresse");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("position_adresse") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."posadresse_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."posadresse_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		}


		//Bouton pour afficher le mode de règlement
		if (in_array("dispreglement",$options[$head],true)){
			print '<tr> </tr>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("dispreglement");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("display_mode_reglement") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."dispreglement_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."dispreglement_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		}



		//Bouton pour afficher les conditions de règlement
		if (in_array("dispcondreglement",$options[$head],true)){
			print '<tr> </tr>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("dispcondreglement");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("display_cond_reglement") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."dispcondreglement_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."dispcondreglement_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		}


		//Bouton pour afficher le slogan/note public de l'entreprise
		if (in_array("dispslogan",$options[$head],true)){
			print '<tr> </tr>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("dispslogan");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("display_slogan") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."dispslogan_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."dispslogan_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		}



		//Bouton pour afficher les colonnes prix unitaire et quantité
		if (in_array("displaypuqtx",$options[$head],true)){
			print '<tr> </tr>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("displaypuqtx");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("display_pu_qtx") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."displaypuqtx_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."displaypuqtx_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		}


		//Bouton pour afficher les colonnes prix unitaire et quantité
		if (in_array("disptva",$options[$head],true)){
			print '<tr> </tr>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("disptva");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("display_tva") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."disptva_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."disptva_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		}

		

		//Bouton cacher les detail d'un produit lorsque le prix est null
		if (in_array("hidedetails",$options[$head],true)){
			print '<tr> </tr>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("hidedetails");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("hidedetails") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."hidedetails_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."hidedetails_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		}



		//Bouton pour afficher les sous totaux
		if (in_array("soustotaux",$options[$head],true)){
			print '<tr> </tr>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("soustotaux");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("display_sous_totaux") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."soustotaux_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."soustotaux_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		}

		
		//Bouton pour repeathead
		if (in_array("affichemmemr",$options[$head],true)){
			print '<tr> </tr>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("affichemmemr");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("affichemmemr") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."affichemmemr_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."affichemmemr_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		}


		//Bouton pour repeathead
		if (in_array("repeathead",$options[$head],true)){
			print '<tr> </tr>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("repeathead");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("repeat_header") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."repeathead_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."repeathead_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		}


		//Bouton pour afficher "Brouillon"
		if (in_array("brouillon",$options[$head],true)){
			print '<tr> </tr>';
			$object=new Alpiroc($db);
			$object->fetchValueFromSelectedTemplate("brouillon");
			if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
			print '<td align="left">'."\n".$langs->trans("display_brouillon") ." : \n";
			if ($value_default==1){
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."brouillon_0".'">';
				print img_picto($langs->trans("Enabled"),'switch_on');
				print '</a>';
			}
			else
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."brouillon_1".'">';
				print img_picto($langs->trans("Disabled"),'switch_off');
				print '</a>';
			}
			print '</td>';
		}

		print '</tr>';
		print '</table>';




// #############################################"
//      CONDITION GENERALE DE VENTE
// #############################################"
		if (count($_FILES)>0){
			//Lors de l'import d'un fichier, celui-ci est stoké ds un repertoire tmp. La variable _FILES permet d'y acceder
			$info_file=$_FILES["fichier"];
			
			//Gestion des erreurs et contraintes sur le format et la taille
			//Fontion renvoie true or false
			$fileOK=pdfFileCheckCGV($info_file);
			
			if ($fileOK){
				//Copy les CGV vers...
				$tmp_dir=$info_file["tmp_name"];
				copy($tmp_dir,DOL_DATA_ROOT."/mycompany/cgv.pdf");
				
				$object=new Alpiroc($db);
				$object->content=$info_file["name"];
				$object->name="cvg_doc";
				$object->profil=$profil;
				$result=$object->update();
			}
		}

		
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("GestionCVG")." - ".$langs->trans("WarningCGV").' </td>'."\n";
		print '<td> </td>'."\n";
		print '<td> </td>'."\n";

		
		print '<tr> </tr>';
		$object=new Alpiroc($db);
		$object->fetchValueFromSelectedTemplate("cvg");
		if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
		print '<td align="left">'."\n".$langs->trans("boutton_cvg") ." : \n ";
		if ($value_default==1){
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."cvg_0".'">';
			print img_picto($langs->trans("Enabled"),'switch_on');
			print '</a>';
		}
		else
		{
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."cvg_1".'">';
			print img_picto($langs->trans("Disabled"),'switch_off');
			print '</a>';
		}
		print '</td>';

		print '</br>';


		//CVG file (PDF)
		$object=new Alpiroc($db);
		$object->fetchValueFromSelectedTemplate("cvg_doc");
		if ($object->content) {$value_default=$object->content;}else{$value_default="";}
		
		print '<td align="left">'."".$langs->trans("file_cvg") ." : ".$value_default;
			print("<form method='post' action='".$_SERVER['PHP_SELF']."' "."enctype='multipart/form-data'>");
			print("<div>");
			//~ echo ("Import data file : ");
			print("<input type='file' name='fichier' value='".$value_default."'/>");
			print("<input type='submit' name='cvg_doc' value='".$langs->trans('Selectionner')."'/></br>");	
			print("</div>");
			print("</form>");
		print '</td>';

		print '</br>';
		print '</table>';



		print '</br>';



// #############################################"
//      RAPPEL IMPAYE
// #############################################"
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("GestionDesImpayes").', '.$langs->trans("TemplateSelection").' : '.$profil.' </td>'."\n";
		print '<td> </td>'."\n";
		print '<td> </td>'."\n";

		//Bouton pour construire une feuille de rappel d'impayé
		print '<tr> </tr>';
		$object=new Alpiroc($db);
		$object->fetchValueFromSelectedTemplate("rappel");
		if ($object->content) {$value_default=$object->content;}else{$value_default="0";}
		print '<td align="left">'."\n".$langs->trans("boutton_rappel") ." : \n";
		if ($value_default==1){
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&value='."rappel_0".'">';
			print img_picto($langs->trans("Enabled"),'switch_on');
			print '</a>';
		}
		else
		{
			print '<a href="'.$_SERVER["PHP_SELF"].'?action=switch&amp;value='."rappel_1".'">';
			print img_picto($langs->trans("Disabled"),'switch_off');
			print '</a>';
		}
		print '</td>';

		print '</br>';


		//Phrase de rappel
		$object=new Alpiroc($db);
		$object->fetchValueFromSelectedTemplate("phraserappel");
		if ($object->content) {$value_default=$object->content;}else{$value_default="";}
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?module=alpiroc&action=add">';
		print '<input type="hidden" name="value" value="phraserappel">';
		print '<tr >';
		print '<td>'.$langs->trans("phrase_rappel_dessus")." : ".'</td>';
		print '<td>';
		print '<textarea name="content" class="flat" cols="45" rows="5" maxlength="510">'.$value_default.'</textarea>';
		print '</td>';
		print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
		print '</tr>';
		print '</form>';

		print '</br>';

		//Phrase de rappel fin
		$object=new Alpiroc($db);
		$object->fetchValueFromSelectedTemplate("phraserappelfin");
		if ($object->content) {$value_default=$object->content;}else{$value_default="";}
		print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?module=alpiroc&action=add">';
		print '<input type="hidden" name="value" value="phraserappelfin">';
		print '<tr >';
		print '<td>'.$langs->trans("phrase_rappel_dessous")." : ".'</td>';
		print '<td>';
		print '<textarea name="content" class="flat" cols="45" rows="5" maxlength="510">'.$value_default.'</textarea>';
		print '</td>';
		print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
		print '</tr>';
		print '</form>';
		print '</br>';
		print '</tr>';
		print '</table>';



		print '</br>';



// #############################################"
//      DELETE DB
// #############################################"
		print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("remove_settings").'</td>'."\n";
			//print '<td> Remove Alpiroc settings </td>'."\n";
			print '<td> </td>'."\n";
			print '<td> </td>'."\n";
			print '<tr> </tr>';
			//print '<td>'."Click to remove Alpiroc DB (delete settings) : ".'</td>';
			print '<td>'.$langs->trans("click_remove_settings").' : </td>';
				print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?module=alpiroc&action=removedb">';
					print '<td>';
						print '<input type="submit" class="button" value="'.$langs->trans("Supprimer").'">';
					print '</td>';
				print '</form>';
				print '<td> </td>'."\n";
				print '<td> </td>'."\n";
			print '</tr>';
		print '</table>';


		//~ unset $object;


		// Example 1 : Adding jquery code
		//~ print '<script type="text/javascript" language="javascript">
		//~ jQuery(document).ready(function() {
			//~ function init_myfunc()
			//~ {
				//~ jQuery("#myid").removeAttr(\'disabled\');
				//~ jQuery("#myid").attr(\'disabled\',\'disabled\');
			//~ }
			//~ init_myfunc();
			//~ jQuery("#mybutton").click(function() {
				//~ init_needroot();
			//~ });
		//~ });
		//~ </script>';


		// Example 2 : Adding links to objects
		// The class must extends CommonObject class to have this method available
		//$somethingshown=$object->showLinkedObjectBlock();


		// Example 3 : List of data
		//~ if ($action == 'list')
		//~ {
			//~ $sql = "SELECT";
			//~ $sql.= " t.rowid,";
			//~ 
				//~ $sql.= " t.name,";
				//~ $sql.= " t.content";
		//~ 
			//~ 
			//~ $sql.= " FROM ".MAIN_DB_PREFIX."alpiroc as t";
			//~ $sql.= " WHERE field3 = 'xxx'";
			//~ $sql.= " ORDER BY field1 ASC";
		//~ 
			//~ print '<table class="noborder">'."\n";
			//~ print '<tr class="liste_titre">';
			//~ print_liste_field_titre($langs->trans('field1'),$_SERVER['PHP_SELF'],'t.field1','',$param,'',$sortfield,$sortorder);
			//~ print_liste_field_titre($langs->trans('field2'),$_SERVER['PHP_SELF'],'t.field2','',$param,'',$sortfield,$sortorder);
			//~ print '</tr>';
		//~ 
			//~ dol_syslog($script_file." sql=".$sql, LOG_DEBUG);
			//~ $resql=$db->query($sql);
			//~ if ($resql)
			//~ {
				//~ $num = $db->num_rows($resql);
				//~ $i = 0;
				//~ if ($num)
				//~ {
					//~ while ($i < $num)
					//~ {
						//~ $obj = $db->fetch_object($resql);
						//~ if ($obj)
						//~ {
							//~ // You can use here results
							//~ print '<tr><td>';
							//~ print $obj->field1;
							//~ print $obj->field2;
							//~ print '</td></tr>';
						//~ }
						//~ $i++;
					//~ }
				//~ }
			//~ }
			//~ else
			//~ {
				//~ $error++;
				//~ dol_print_error($db);
			//~ }
		//~ 
			//~ print '</table>'."\n";
		//~ }

	}//if Profil

}else{
	dol_fiche_head();
		print($langs->trans('desc_suppr_settings'));
		//print("Alpiroc settings has been removed... disable and enable the module again to restore initial configuration.");
	dol_fiche_end();
}


// End of page
llxFooter();
$db->close();

?>
