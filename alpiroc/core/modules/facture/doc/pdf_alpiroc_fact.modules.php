<?php
/* Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Raphael Bertrand     <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2012 Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2014      Maxime Jay-Allemand   <maxime.jay-allemand@laposte.net>
 * Copyright (C) 2016      Frédéric Roux  <ami-pc07@laposte.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/propale/doc/pdf_alpiroc.modules.php
 *	\ingroup    propale
 *	\brief      Fichier de la classe permettant de generer les propales au modele alpiroc
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

include_once(DOL_DOCUMENT_ROOT.'/alpiroc/sql/alpiroc.class.php');
include_once(DOL_DOCUMENT_ROOT.'/alpiroc/core/modules/fonctions/function.php');

/**
 *	Classe permettant de generer les propales au modele alpiroc
 */
class pdf_alpiroc_fact extends ModelePDFFactures
{
	var $db;
	var $name;
	var $description;
	var $type;

	var $phpmin = array(4,3,0); // Minimum version of PHP required by module
	var $version = 'dolibarr';

	var $page_largeur;
	var $page_hauteur;
	var $format;
	var $marge_gauche;
	var	$marge_droite;
	var	$marge_haute;
	var	$marge_basse;

	var $emetteur;	// Objet societe qui emet


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;
		

		$langs->load("main");
		$langs->load("bills");

		$this->db = $db;
		$this->name = "alpiroc_fact";
		$this->description = $langs->trans('PDFCrabeDescription');

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		
		
		
		$this->franchise=!$mysoc->tva_assuj;

		// Get source company
		$this->emetteur=$mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if was not defined

		// Define position of columns
		$this->posX_colonne=140;
		$this->largeur_colonne=30;
		
		$this->posxdesc=$this->marge_gauche+1;
		$this->posxtva=155	;
		$this->posxup=110;
		$this->posxqty=130;
		$this->posxdiscount=140;
		$this->postotalht=174;
		
		//Nouvelle facon de gérer le positionnement des colonnes => par les largeurs
		$this->largeur_ht=$this->page_largeur-$this->marge_droite-$this->postotalht-2;
		$this->largeur_tva=15;
		$this->largeur_discount=15;
		$this->largeur_qtx=15;
		$this->largeur_up=20;

		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxtva-=20;
			$this->posxup-=20;
			$this->posxqty-=20;
			$this->posxdiscount-=20;
			$this->postotalht-=20;
		}

		$this->tva=array();
		$this->localtax1=array();
		$this->localtax2=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
	}

	/**
     *  Function to build pdf onto disk
     *
     *  @param		Object		$object				Object to generate
     *  @param		Translate	$outputlangs		Lang output object
     *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int			$hidedetails		Do not show line details
     *  @param		int			$hidedesc			Do not show desc
     *  @param		int			$hideref			Do not show ref
     *  @param		object		$hookmanager		Hookmanager object
     *  @return     int             				1=OK, 0=KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0,$hookmanager=false)
	{
		global $user,$langs,$conf,$mysoc,$db;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("propal");
		$outputlangs->load("products");
		$outputlangs->load("alpiroc@alpiroc");

		if ($conf->facture->dir_output)
		{
			$object->fetch_thirdparty();


			// Frédéric Roux pour show versement
			$deja_regle = $object->getSommePaiement();
			$amount_credit_notes_included = $object->getSumCreditNotesUsed();
			$amount_deposits_included = $object->getSumDepositsUsed();
			// Frédéric Roux pour show versement
			
			// $deja_regle = 0;

			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->facture->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->facture->dir_output . "/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
			}

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}
			
			
		//PARAMETRES / REGLAGE : 
		
		//~ //creer la nouvelle classe alpiroc qui contient les info de la table alpiroc. On y accède ensuite par la fonction fetch_name
		$alpiroc=new Alpiroc($this->db);
		$alpiroc->fetch_selectprofil($object->id,"facture");//Sélectionne le nom du profil choisi 
		$this->profil=$alpiroc->name;
		
		$alpiroc->fetch_profil("alpiroc");//récupère tout les profils différents
		$tab_template=array_unique($alpiroc->tab_name);//Créer un tableau avec des valeurs uniques
		
		if (array_search($this->profil,$tab_template)===false)
		{
			$this->profil='';
		}
		
		
		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 1;                // Affiche si il y a eu escompte
		$this->option_credit_note = 1;             // Support credit notes
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts
		
		
		if ($this->profil!=''){

			$alpiroc=new Alpiroc($this->db);

			$alpiroc->fetchValueFromProfil("dispreglement",$this->profil);
			$this->option_modereg=$alpiroc->content;//Affiche mode reglement
			//~ $this->option_modereg=0;

			$alpiroc->fetchValueFromProfil("dispcondreglement",$this->profil);
			$this->option_condreg=$alpiroc->content;//Affiche les conditions de reglement
			//$this->option_condreg=0;

			$alpiroc->fetchValueFromProfil("thanksarea",$this->profil);
			$this->option_conclusion_sentence=$alpiroc->content;//Phrase de conclusion en fin de page
		
			$alpiroc->fetchValueFromProfil("signaturearea",$this->profil);
			$this->option_sign_aera=$alpiroc->content;//Box de signature en bas à droite
			$this->option_sign_aera=0;//Toujours désactivé sur une facture
		
			$alpiroc->fetchValueFromProfil("displayacompte",$this->profil);
			$this->option_acompte=$alpiroc->content;//Gestion des acomptes
			//~ $this->option_acompte=0;//Toujours désactivé sur les factures
			
			$alpiroc->fetchValueFromProfil("posadresse",$this->profil);
			$this->option_posaddresse=$alpiroc->content;	//Gestion des acomptes
		
			$alpiroc->fetchValueFromProfil("dispslogan",$this->profil);
			$this->option_slogan=$alpiroc->content;	//Gestion des acomptes
		
			$alpiroc->fetchValueFromProfil("displaypuqtx",$this->profil);//Gestion des colonne PU et QTX
			$this->option_prixunit_qty=$alpiroc->content;
			
			$alpiroc->fetchValueFromProfil("disptva",$this->profil);//Affichage de la colonne TVA
			$this->option_disp_tva=$alpiroc->content;//Affichage de la TVA
		
			$alpiroc->fetchValueFromProfil("soustotaux",$this->profil);
			$this->option_soustotaux=$alpiroc->content;	   //Affiche mode reglement
		
			$alpiroc->fetchValueFromProfil("brouillon",$this->profil);
			$this->option_brouillon=$alpiroc->content;	   //Affiche Brouillon
			//~ $this->option_brouillon=0;
			
			$alpiroc->fetchValueFromProfil("rappel",$this->profil);
			$this->option_rappel=$alpiroc->content;	   //Affiche rappel
			
			$alpiroc->fetchValueFromProfil("notepublic",$this->profil);
			$this->option_notepublic=$alpiroc->content;	   //Affiche notepublic
			
			$alpiroc->fetchValueFromProfil("contact",$this->profil);
			$this->option_contact=$alpiroc->content;	   //Affiche contact
			
			$alpiroc->fetchValueFromProfil("repeathead",$this->profil);
			$this->option_repeat_head=$alpiroc->content;	   //Affiche contact

			$alpiroc->fetchValueFromProfil("hidedetails",$this->profil);
			$this->option_hidedetails=$alpiroc->content;	   //Affiche contact
			
			$alpiroc->fetchValueFromProfil("head",$this->profil);
			$this->option_head=$alpiroc->content;	   //choix de l'entête
			
			$alpiroc->fetchValueFromProfil("dispprivatenote",$this->profil);
			$this->option_dispprivatenote=$alpiroc->content;//Affichage de la note privé
			
			$alpiroc->fetchValueFromProfil("affichemmemr",$this->profil);
			$this->option_affichemmemr=$alpiroc->content;//Affichage Mme,Mr devant le nom
			
			$alpiroc->fetchValueFromProfil("cvg",$this->profil);
			$this->option_cvg=$alpiroc->content;//Joint les CVG
			
			$alpiroc->fetchValueFromProfil("paymentdone",$this->profil);
			$this->option_paymentdone=$alpiroc->content;//Affiche les paiements déja effectués
			
		}else{
			//Default value
			$this->option_modereg=1;
			$this->option_condreg=1;
			$this->option_conclusion_sentence=1;
			$this->option_sign_aera=0;
			$this->option_acompte=0;
			$this->option_slogan=1;
			$this->option_prixunit_qty=1;
			$this->option_disp_tva=1;
			$this->option_posaddresse=1;
			$this->option_soustotaux=0;
			$this->option_brouillon=0;
			$this->option_rappel=0;
			$this->option_contact=1;
			$this->option_notepublic=1;
			$this->option_repeat_head=1;
			$this->option_hidedetails=0;
			$this->option_head="alpiroc";
			$this->option_dispprivatenote=0;
			$this->option_affichemmemr=0;
			$this->option_cvg=0;
			$this->option_paymentdone=0;
		}
		//Une variable global non défini existe pour ne pas répéter l'header, on l'utilise. 
			if ($this->option_repeat_head==0){
				$conf->global->MAIN_PDF_DONOTREPEAT_HEAD=TRUE;
			}
			
			if (file_exists($dir))
			{
				$nblignes = count($object->lines);

				// Create pdf instance
                $pdf=pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
                $heightforinfotot = 50;	// Height reserved to output the info and total part
		        $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
	            $heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
                $pdf->SetAutoPageBreak(1,0);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                // Set path to the background PDF File
                if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
                {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Invoice"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Invoice"));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right


				// Positionne $this->atleastonediscount si on a au moins une remise
				//On regarde si une colonne remise sera placée
				$largeur_description=$this->postotalht;//Initialement c'est posxtva car pas de remise
				
				if ($this->option_disp_tva==1){
					$largeur_description=$largeur_description-$this->largeur_tva;
				}

				$x_pos_qtx=$this->posxtva;
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					if ($object->lines[$i]->remise_percent)
					{
						$this->atleastonediscount++;
						$x_pos_qtx=$this->posxdiscount;
					}
				}
				if ($this->atleastonediscount>0){
					$largeur_description=$largeur_description-$this->largeur_discount;
				}
				
				//Ajuste la largeur de la description en fonction des colonnes affichées
				if ($this->option_prixunit_qty==1)
				{
					$largeur_description=$largeur_description-$this->largeur_qtx-$this->largeur_up;
				}
				
				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs, $hookmanager); //Les adresses sont écrites !
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				$posy_bas_pagehead=$pdf->GetY();
				if ($posy_bas_pagehead>90-10){
					//$tab_top = min($posy_bas_pagehead + 10,110);//on force la position vers le bas (max 110)
					$tab_top=min(max(90,$posy_bas_pagehead),190);
					//Si posy > 190 ca fou la merde dans le tableau, ajoute une page automatiquement, tout merde !
				}else{
					$tab_top = 90;
				}
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?$posy_bas_pagehead:10);
				$tab_height = 130;
				$tab_height_newpage = 150;
				
				
				
				//Le type de document n'est écrit ici que si il s'agit de l'entête alpiroc
				if ($this->option_head=="alpiroc"){
					//Ecrit le titre du document avant les références
					$pdf->SetXY($this->marge_gauche,$tab_top);
					$pdf->SetFont('','B', $default_font_size +2);
					if ($this->option_rappel==1){
						$pdf->MultiCell(100, 4, $outputlangs->transnoentities("RappelFacturation")." - ".date('d\/m\/Y'), 0, 'L');
					}else{
						$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Invoice"), 0, 'L');
					}
					$posy=$pdf->GetY();
					$nexY=$posy;

					 if ($object->ref_client){//On ecrit de la même manière les refs clients
						//Ecrit ref client = objet de la proposistion comercial + la date
						$pdf->SetXY($this->marge_gauche,$posy);
						$pdf->SetFont('','B', $default_font_size - 1);
						$pdf->MultiCell(80, 4, $outputlangs->transnoentities("Objet")." : ".$object->ref_client, 0, 'L');
						$nexY=max($nexY,$pdf->GetY());
						$pdf->SetXY($this->page_largeur-$this->marge_droite-100,$posy);
						$pdf->MultiCell(50, 4, $outputlangs->transnoentities("Ref")." : ".$object->ref, 0, 'L');
						$nexY=max($nexY,$pdf->GetY());
						$pdf->SetXY($this->page_largeur-$this->marge_droite-70,$posy);
						$pdf->MultiCell(70, 4, $outputlangs->transnoentities("DateInvoice")." : " . dol_print_date($object->date,"day",false,$outputlangs,true), '', 'R');
						$nexY=max($nexY,$pdf->GetY());
					 }else{//Que la ref + date
						$pdf->SetFont('','B', $default_font_size - 1);
						$pdf->SetXY($this->marge_gauche,$posy);
						$pdf->MultiCell(80, 4, $outputlangs->transnoentities("Ref")." : ".$object->ref, 0, 'L');
						$nexY=max($nexY,$pdf->GetY());
						$pdf->SetXY($this->page_largeur-$this->marge_droite-100,$posy);
						$pdf->MultiCell(70, 4, $outputlangs->transnoentities("DateInvoice")." : " . dol_print_date($object->date,"day",false,$outputlangs,true), '', 'L');
						$nexY=max($nexY,$pdf->GetY());
					 }
				

					$posx=$this->marge_gauche;
					$linkedobjects = pdf_getLinkedObjects($object,$outputlangs);
					foreach($linkedobjects as $linkedobject)
					{
						$posx=$this->marge_gauche;
						$nexY=max($nexY,$pdf->GetY());
						$posy=$nexY;
						$pdf->SetXY($posx,$posy);
						$pdf->SetFont('','', $default_font_size - 1);
						$pdf->MultiCell(80, 4, $linkedobject["ref_title"].' : '.$linkedobject["ref_value"], '', 'L');

						if (! empty($linkedobject["date_title"]) && ! empty($linkedobject["date_value"]))
						{
							$posx=$this->page_largeur-$this->marge_droite-100;
							$pdf->SetXY($posx,$posy);
							$pdf->MultiCell(80, 4, $linkedobject["date_title"].' : '.$linkedobject["date_value"], '', 'L');
						}
					}
					
				}else{
					$pdf->SetXY($this->marge_gauche,$tab_top);
					$nexY=$pdf->GetY();
				}
				
				$nexY=max($nexY,$pdf->GetY());
				$height_note=$nexY-$tab_top+4;
				$tab_height = $tab_height - $height_note;
				$tab_top = $tab_top+$height_note;
				
				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;
				
				//Initialisation des sous totaux à 0
				$sous_totaux_ht=0;
				$rubrique_titre=false;
				$total_rubrique=0;
				
			//Si l'option rappel est activé, seulement le total sera écrit + une phrase de description
			if ($this->option_rappel==0){
					
				// Loop on each lines
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$curY = $nexY+2;
					
					$pdf->SetFont('','', $default_font_size - 1);   // Into loop to work with multipage
					$pdf->SetTextColor(0,0,0);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore=$pdf->getPage();

					// Description of product line
					$curX = $this->posxdesc-1;

					$showpricebeforepagebreak=1;

					$pdf->startTransaction();
					$curY_rollback=$curY;
					
					//Insert d'un Titre
					$alpiroc=new Alpiroc($this->db);
					$alpiroc->fetch_titre($object->lines[$i]->rowid,"titrealpiroc","facturedet");
					if ($alpiroc->name!=''){
						$pdf->SetXY($curX,$curY);
						$pdf->SetTextColor(0,0,0);
						$pdf->SetFont('','B', $default_font_size +2);
						$pdf->MultiCell($largeur_description-$curX, 3,$alpiroc->name, 0, 'L');
						$pageposafter=$pdf->getPage();
						if (($pdf->GetY()>$curY)){
							$curY=$pdf->GetY();//Si assez de place sur la page on note la position pour ecrire ensuite la description du future produit
						}else{//Si plus de place sur la page on l'enlève
							$pdf->rollbackTransaction(true);//Annule la derniere écriture si startTransaction activé
						}
						$pdf->SetFont('','', $default_font_size - 1); 
						$rubrique_titre=true;
						$total_rubrique=0;
					}
					
					//On ecrit la description du produit
					pdf_writelinedesc($pdf,$object,$i,$outputlangs,$largeur_description-$curX,3,$curX,$curY,$hideref,$hidedesc,0,$hookmanager);
					$pageposafter=$pdf->getPage();
					$posY_soustotaux=$pdf->GetY();
					
					if ($pageposafter > $pageposbefore)	// There is a pagebreak : En fait ca correspond au point ou le tableau sera trop grand et qu'il ne sera plus possible d'ajouté les toto en bas de page. Dans tout les cas une page sera ajoutée
					{
						$pdf->rollbackTransaction(true);//On efface les dernière écriture : le titre et la description
						$pageposafter=$pageposbefore;
						$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
						
						$curY=$curY_rollback;
						
						//Insertion d'un Titre : Ici on regarde si le titre peut être ecrit sur la page. Sinon on ajoute une page !
						$pdf->startTransaction();
						if ($alpiroc->name!=''){
							$pdf->SetXY($curX,$curY);
							$pdf->SetTextColor(0,0,0);
							$pdf->SetFont('','B', $default_font_size +2);
							$pdf->MultiCell($largeur_description-$curX, 3,$alpiroc->name, 0, 'L');
							
							$curYdesc=$pdf->GetY();
							$pdf->SetFont('','', $default_font_size - 1); 
							pdf_writelinedesc($pdf,$object,$i,$outputlangs,$largeur_description-$curX,4,$curX,$curYdesc,$hideref,$hidedesc,0,$hookmanager);
							
							if($pdf->GetY()<$curY){
								$pdf->rollbackTransaction(true);
								$pdf->AddPage('','',true);
								if (! empty($tplidx)) $pdf->useTemplate($tplidx);
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
									$this->_pagehead($pdf, $object, 0, $outputlangs, $hookmanager);
									$tab_top_newpage=$pdf->GetY();
								}
								$pdf->setPage($pagenb+1);
								$curY = $tab_top_newpage+6;
								$pdf->SetXY($curX,$curY);
								$pdf->SetTextColor(0,0,0);
								$pdf->SetFont('','B', $default_font_size +2);
								$pdf->MultiCell($largeur_description-$curX, 3,$alpiroc->name, 0, 'L');
								
								$curYdesc=$pdf->GetY();
								$pdf->SetFont('','', $default_font_size - 1); 
							    pdf_writelinedesc($pdf,$object,$i,$outputlangs,$largeur_description-$curX,4,$curX,$curYdesc,$hideref,$hidedesc,0,$hookmanager);
							
							}
							
							$pdf->SetFont('','', $default_font_size - 1); 
							$curY=$curYdesc;//Pour la suite : écriture des tva, prix... on prend le curY de la descrtiption
							$rubrique_titre=true;
							$total_rubrique=0;
							
							$pdf->commitTransaction();
						}else{
							//Lorsqu'une description est écrite, on vérifie qu'elle rentre sur toutes la page, sinon on la place sur la page suivante
							$pdf->startTransaction();
							pdf_writelinedesc($pdf,$object,$i,$outputlangs,$largeur_description-$curX,4,$curX,$curY,$hideref,$hidedesc,0,$hookmanager);
							if($pdf->GetY()<$curY){
								$pdf->rollbackTransaction(true);//Annule la derniere écriture si startTransaction activé
								$pdf->AddPage('','',true);
								if (! empty($tplidx)) $pdf->useTemplate($tplidx);
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
									$this->_pagehead($pdf, $object, 0, $outputlangs, $hookmanager);
									$tab_top_newpage=$pdf->GetY();
								}
								$pdf->setPage($pagenb+1);
								$curY = $tab_top_newpage+6;
								$pdf->SetFont('','', $default_font_size - 1); 
								pdf_writelinedesc($pdf,$object,$i,$outputlangs,$largeur_description-$curX,4,$curX,$curY,$hideref,$hidedesc,0,$hookmanager);
							}
							$pdf->commitTransaction();
					    	}
						
						$pageposafter=$pdf->getPage();
						$posyafter=$pdf->GetY();
						$posY_soustotaux=$pdf->GetY();
						
						
						if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
						{
							if ($i == ($nblignes-1))	// No more lines, and no space left to show total, so we create a new page
							{
								$pdf->AddPage('','',true);
								if (! empty($tplidx)) $pdf->useTemplate($tplidx);
								if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
									$this->_pagehead($pdf, $object, 0, $outputlangs, $hookmanager);
									$tab_top_newpage=$pdf->GetY();
								}
								$pdf->setPage($pagenb+1);
							}
						}
						else
						{
							// We found a page break
							$showpricebeforepagebreak=0;
						}
					}
					else	// No pagebreak
					{
						$pdf->commitTransaction();
						$posY_soustotaux=$pdf->GetY();
					}

					$nexY = $pdf->GetY();
					$pageposafter=$pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.


					//Ecriture des entité dans le tableau !!!
					//~ // We suppose that a too long description is moved completely on next page : FAUX !!! C'est pas clair !'
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter); 
						$curY = max($curY,$tab_top_newpage+6);//Le titre est écrit avec la description => curY>tabtop+6
					}
					
					
					$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut

					
					//Le détail des produit n'est écrit que si le prix est supérieur à 0
					if ($object->lines[$i]->subprice==0 && $this->option_hidedetails==1){	
						$hidedetails=1;
					}

					
					$posx_col=$this->postotalht;
					
					// Total HT line
					$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails, $hookmanager);
					$pdf->SetXY($posx_col+1, $curY);
					$pdf->MultiCell($this->largeur_ht, 3, $total_excl_tax, 0, 'R', 0);
					
					
					// VAT Rate
					if ($this->option_disp_tva==1)
					{
						$posx_col=$posx_col-$this->largeur_tva;
						if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
						{
							$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails, $hookmanager);
							$pdf->SetXY($posx_col+1, $curY);
							$pdf->MultiCell($this->largeur_tva-2, 3, $vat_rate, 0, 'R');
						}
					}
						

					// Discount on line
					if ($this->atleastonediscount>0){
						$posx_col=$posx_col-$this->largeur_discount;
					}
					if ($object->lines[$i]->remise_percent)
					{
						$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails, $hookmanager);
						$pdf->SetXY($posx_col+1, $curY);
						$pdf->MultiCell($this->largeur_discount-2, 3, $remise_percent, 0, 'R');
					}
									

					// Unit price before discount
					if ($this->option_prixunit_qty==1)
					{
						//~ // Quantity
						$posx_col=$posx_col-$this->largeur_qtx;
						$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails, $hookmanager);
						$pdf->SetXY($posx_col+1, $curY);
						$pdf->MultiCell($this->largeur_qtx-1, 3, $qty, 0, 'R');	// Enough for 6 chars

						//Unit Price
						$posx_col=$posx_col-$this->largeur_up;
						$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails, $hookmanager);
						$pdf->SetXY($posx_col+1, $curY);
						$pdf->MultiCell($this->largeur_up-1, 3, $up_excl_tax, 0, 'R', 0);
					}


					//Enregistrement des sous totaux HT dans une variable :
					if ($rubrique_titre==true){
						//~ // Il faut la quantité pour le calcul du prix
						$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails, $hookmanager);
						//Prise en compte des réductions
						if ($object->lines[$i]->remise_percent){
							$total_rubrique=$total_rubrique+($object->lines[$i]->subprice*$qty)*(1-$object->lines[$i]->remise_percent/100);
						}else{
							$total_rubrique=$total_rubrique+($object->lines[$i]->subprice*$qty);
						}
						//Arrondi le sous total
						$total_rubrique=round($total_rubrique,2);
					}
					
					
					//reset variable hide detail pour affiacher les prix au prochain tour
					$hidedetails=0;

					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					$tvaligne=$object->lines[$i]->total_tva;
					$localtax1ligne=$object->lines[$i]->total_localtax1;
					$localtax2ligne=$object->lines[$i]->total_localtax2;
					$localtax1_rate=$object->lines[$i]->localtax1_tx;
					$localtax2_rate=$object->lines[$i]->localtax2_tx;
					$localtax1_type=$object->lines[$i]->localtax1_type;
					$localtax2_type=$object->lines[$i]->localtax2_type;

					if ($object->remise_percent) $tvaligne-=($tvaligne*$object->remise_percent)/100;
					if ($object->remise_percent) $localtax1ligne-=($localtax1ligne*$object->remise_percent)/100;
					if ($object->remise_percent) $localtax2ligne-=($localtax2ligne*$object->remise_percent)/100;

					$vatrate=(string) $object->lines[$i]->tva_tx;
					// TODO : store local taxes types into object lines and remove this
					$localtax1_array=getLocalTaxesFromRate($vatrate,1,$object->thirdparty,$mysoc);
					$localtax2_array=getLocalTaxesFromRate($vatrate,2,$object->thirdparty,$mysoc);
					if (! isset($localtax1_type)) $localtax1_type = $localtax1_array[0];
					if (! isset($localtax2_type)) $localtax2_type = $localtax2_array[0];
					//end TODO

				    // retrieve global local tax
					if ($localtax1_type == '7')
						$localtax1_rate = $localtax1_array[1];
					if ($localtax2_type == '7')
						$localtax2_rate = $localtax2_array[1];

					if ($localtax1_type && ($localtax1ligne != 0 || $localtax1_type == '7'))
						$this->localtax1[$localtax1_type][$localtax1_rate]+=$localtax1ligne;
					if ($localtax2_type && ($localtax2ligne != 0 || $localtax2_type == '7'))
						$this->localtax2[$localtax2_type][$localtax2_rate]+=$localtax2ligne;

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate.='*';
					if (! isset($this->tva[$vatrate]))				$this->tva[$vatrate]=0;
					$this->tva[$vatrate] += $tvaligne;



					// Add line
					if (! empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblignes - 1))
					{
						$pdf->SetLineStyle(array('dash'=>'1,1','color'=>array(210,210,210)));
						$pdf->line($this->marge_gauche, $nexY+1, $this->page_largeur - $this->marge_droite, $nexY+1);
						$pdf->SetLineStyle(array('dash'=>0));
					}
					
					//L'écriture des sous totaux doit être faite sur la page d'après si plus de place sur la première page
					if ($posY_soustotaux>($this->page_hauteur - ($heightforfooter+$heightforfreetext))){
						$pdf->AddPage('','',true);
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) {
									$this->_pagehead($pdf, $object, 0, $outputlangs, $hookmanager);
									$tab_top_newpage=$pdf->GetY();
						}
						$pdf->setPage($pagenb+1);
						$pageposafter=$pagenb+1;
						$posY_soustotaux = $tab_top_newpage+6;
						$nexY=$tab_top_newpage+6;
					}

					$alpiroc=new Alpiroc($this->db);
					$alpiroc->fetch_titre($object->lines[$i+1]->rowid,"titrealpiroc","facturedet");//next ligne à un titre ?
					
					if ($rubrique_titre==true AND $this->option_soustotaux==1){	
						if ($alpiroc->name!='' OR $i==$nblignes-1){
							$varY=$posY_soustotaux+1;
							$sous_totaux_ht=$total_rubrique;
							$p_sous_tot=price($sous_totaux_ht, 0, $outputlang);
							$pdf->SetFont('','', $default_font_size);
							$pdf->SetXY($curX,$varY);
							$pdf->SetFillColor(224,224,224);
							$pdf->SetTextColor(0,0,60);
							//Ecriture de la description sous total
							$pdf->MultiCell($largeur_description-$curX, 3,$outputlangs->transnoentities("SousTotal"), 0, 'R',0);
							//Ecriture du sous total
							$pdf->SetXY($this->postotalht, $varY);
							$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->postotalht, 3, $p_sous_tot, 0, 'R', 0);
							//Remplit la ligne de gris
							$pdf->SetXY($this->posxdesc-1, $varY);
							$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->marge_gauche, 3, "", 0, 'R',1);
							$rubrique_titre=false;
							$total_rubrique=0;
							$nexY=$nexY+4;//Epaisseur du sous total
						}
					}




					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter)
					{
						$pdf->setPage($pagenb);
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD))  {
									$this->_pagehead($pdf, $object, 0, $outputlangs, $hookmanager);
									$tab_top_newpage=$pdf->GetY();
						}
					}
					
					if (isset($object->lines[$i+1]->pagebreak) && $object->lines[$i+1]->pagebreak)
					{
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);
						// New page
						$pdf->AddPage();
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$pagenb++;
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD))  {
									$this->_pagehead($pdf, $object, 0, $outputlangs, $hookmanager);
									$tab_top_newpage=$pdf->GetY();
						}
					}

				
					if ($this->option_brouillon==1){
						//BROUILLON
						$pdf->SetTextColor(255,192,203);
						$pdf->SetFont('','B',70);
						$pdf->SetXY(40,160);
						$pdf->Rotate(45,40,160);
						$pdf->Cell(40, 40, $outputlangs->transnoentities("brouillon"));
						$pdf->Rotate(-45,40,160);
					}
					
				}

				// Show square
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}


			}

				
				//Rappel de paiement
				if ($this->option_rappel==1){
					$total_total=0;
					for ($i = 0 ; $i < $nblignes ; $i++)
					{
						// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
						$tvaligne=$object->lines[$i]->total_tva;
						$localtax1ligne=$object->lines[$i]->total_localtax1;
						$localtax2ligne=$object->lines[$i]->total_localtax2;
						$localtax1_rate=$object->lines[$i]->localtax1_tx;
						$localtax2_rate=$object->lines[$i]->localtax2_tx;
						$localtax1_type=$object->lines[$i]->localtax1_type;
						$localtax2_type=$object->lines[$i]->localtax2_type;

						if ($object->remise_percent) $tvaligne-=($tvaligne*$object->remise_percent)/100;
						if ($object->remise_percent) $localtax1ligne-=($localtax1ligne*$object->remise_percent)/100;
						if ($object->remise_percent) $localtax2ligne-=($localtax2ligne*$object->remise_percent)/100;

						$vatrate=(string) $object->lines[$i]->tva_tx;
						// TODO : store local taxes types into object lines and remove this
						$localtax1_array=getLocalTaxesFromRate($vatrate,1,$object->thirdparty,$mysoc);
						$localtax2_array=getLocalTaxesFromRate($vatrate,2,$object->thirdparty,$mysoc);
						if (! isset($localtax1_type)) $localtax1_type = $localtax1_array[0];
						if (! isset($localtax2_type)) $localtax2_type = $localtax2_array[0];
						//end TODO

						// retrieve global local tax
						if ($localtax1_type == '7')
							$localtax1_rate = $localtax1_array[1];
						if ($localtax2_type == '7')
							$localtax2_rate = $localtax2_array[1];

						if ($localtax1_type && ($localtax1ligne != 0 || $localtax1_type == '7'))
							$this->localtax1[$localtax1_type][$localtax1_rate]+=$localtax1ligne;
						if ($localtax2_type && ($localtax2ligne != 0 || $localtax2_type == '7'))
							$this->localtax2[$localtax2_type][$localtax2_rate]+=$localtax2ligne;

						if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate.='*';
						if (! isset($this->tva[$vatrate]))				$this->tva[$vatrate]=0;
						$this->tva[$vatrate] += $tvaligne;
					}
					// Affiche zone totaux
					$curY = $nexY+2;
					$posX=$this->posxdesc-1;
					$pdf->SetXY($posX, $curY);
					$pdf->SetFont('','',$default_font_size);
					$alpiroc=new Alpiroc($this->db);
					//Affiche la première phrase
					$alpiroc->fetchValueFromProfil("phraserappel",$this->profil);
					$pdf->writeHTMLCell($this->page_largeur-$this->marge_droite-$this->marge_gauche, 3, $posX, $curY, dol_htmlentitiesbr($alpiroc->content), 0, 1,false,true,'L');
					$curY=$pdf->GetY()+4;
					// Affiche zone totaux
					$posy1=$this->_tableau_tot($pdf, $object, 0, $curY, $outputlangs);
					$posy2=0;
					if ($this->option_condreg!=0 OR $this->option_modereg!=0){//SI on demlande d'affiché qqch
						// Affiche zone infos
						$posy2=$this->_tableau_info($pdf, $object, $curY, $outputlangs);
					}
					//Affiche la deuxième phrase
					$posy=max($posy1,$posy2)+1;
					$alpiroc->fetchValueFromProfil("phraserappelfin",$this->profil);
					$pdf->SetXY($posX, $posy+4);
					$pdf->SetFont('','',$default_font_size);
					$pdf->writeHTMLCell($this->page_largeur-$this->marge_droite-$this->marge_gauche, 3, $posX, $posy+4, dol_htmlentitiesbr($alpiroc->content), 0, 1,false,true,'L');
					$posy=$pdf->GetY()+5;
					
				}else{
					
				// Affiche zone infos
				$posy=$this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

				// Affiche zone totaux
				$posy=$this->_tableau_tot($pdf, $object, 0, $bottomlasttab, $outputlangs);
				}

				//~ //Zone de confirmation 
				$this->_signature_conclusion($pdf, $object, $outputlangs);
				
				
				// Affiche zone versements
				if ($this->option_paymentdone==1){
					if ($deja_regle || $amount_credit_notes_included || $amount_deposits_included)
					{
						$posy=$this->_tableau_versements($pdf, $object, $posy, $outputlangs);
					}
				}
				

				// Pied de page
				$this->_pagefoot($pdf,$object,$outputlangs);
				if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

				
				// Condition Generale de Vente
				// Ajout des CGV dans la propale
				// Par Philippe SAGOT (philazerty) le 21/09/2012
				if ($this->option_cvg==1 && file_exists(DOL_DATA_ROOT."/mycompany/cgv.pdf")){
					$pagecount = $pdf->setSourceFile(DOL_DATA_ROOT."/mycompany/cgv.pdf");
					for ($i = 1; $i <= $pagecount; $i++) {
						$tplidx = $pdf->ImportPage($i);
						$s = $pdf->getTemplatesize($tplidx);
						$pdf->AddPage('P', array($s['w'], $s['h']));
						$pdf->useTemplate($tplidx);
						// Ajout du watermark (brouillon)
						if ($object->statut==0 && (!empty($conf->global->FACTURE_DRAFT_WATERMARK))) {
							pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->FACTURE_DRAFT_WATERMARK);
							$pdf->SetTextColor(0,0,60);
						}
						// Ajout du footer / pied de page
						pdf_pagefoot($pdf,$outputlangs,'',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object);
					}
                }


				$pdf->Close();

				$pdf->Output($file,'F');

				//Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","PROP_OUTPUTDIR");
			return 0;
		}

		$this->error=$langs->trans("ErrorUnknown");
		return 0;   // Erreur par defaut
	}




	/**
	 *  Show payments table
	 *
     *  @param	PDF			&$pdf           Object PDF
     *  @param  Object		$object         Object proposal
     *  @param  int			$posy           Position y in PDF
     *  @param  Translate	$outputlangs    Object langs for output
     *  @return int             			<0 if KO, >0 if OK
	 */
	// Frédéric Roux Show Versement	 
	function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

        $sign=1;
        if ($object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

        $tab3_posx = 120;
		$tab3_top = $posy + 8;
		$tab3_width = 80;
		$tab3_height = 4;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$tab3_posx -= 20;
		}

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$title=$outputlangs->transnoentities("PaymentsAlreadyDone");
		if ($object->type == 2) $title=$outputlangs->transnoentities("PaymentsBackAlreadyDone");

		$pdf->SetFont('','', $default_font_size - 3);
		$pdf->SetXY($tab3_posx, $tab3_top - 4);
		$pdf->MultiCell(60, 3, $title, 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top, $tab3_posx+$tab3_width, $tab3_top);

		$pdf->SetFont('','', $default_font_size - 4);
		$pdf->SetXY($tab3_posx, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Payment"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+21, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Amount"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+40, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Type"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+58, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Num"), 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top-1+$tab3_height, $tab3_posx+$tab3_width, $tab3_top-1+$tab3_height);

		$y=0;

		$pdf->SetFont('','', $default_font_size - 4);

		// Loop on each deposits and credit notes included
		$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
		$sql.= " re.description, re.fk_facture_source,";
		$sql.= " f.type, f.datef";
		$sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re, ".MAIN_DB_PREFIX ."facture as f";
		$sql.= " WHERE re.fk_facture_source = f.rowid AND re.fk_facture = ".$object->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			$invoice=new Facture($this->db);
			while ($i < $num)
			{
				$y+=3;
				$obj = $this->db->fetch_object($resql);

				if ($obj->type == 2) $text=$outputlangs->trans("CreditNote");
				elseif ($obj->type == 3) $text=$outputlangs->trans("Deposit");
				else $text=$outputlangs->trans("UnknownType");

				$invoice->fetch($obj->fk_facture_source);

				$pdf->SetXY($tab3_posx, $tab3_top+$y);
				$pdf->MultiCell(20, 3, dol_print_date($obj->datef,'day',false,$outputlangs,true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+21, $tab3_top+$y);
				$pdf->MultiCell(20, 3, price($obj->amount_ttc, 0, $outputlangs), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+40, $tab3_top+$y);
				$pdf->MultiCell(20, 3, $text, 0, 'L', 0);
				$pdf->SetXY($tab3_posx+58, $tab3_top+$y);
				$pdf->MultiCell(20, 3, $invoice->ref, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3);

				$i++;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

		// Loop on each payment
		$sql = "SELECT p.datep as date, p.fk_paiement as type, p.num_paiement as num, pf.amount as amount,";
		$sql.= " cp.code";
		$sql.= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."paiement as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp ON p.fk_paiement = cp.id";
		$sql.= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = ".$object->id;
		$sql.= " ORDER BY p.datep";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num) {
				$y+=3;
				$row = $this->db->fetch_object($resql);

				$pdf->SetXY($tab3_posx, $tab3_top+$y);
				$pdf->MultiCell(20, 3, dol_print_date($this->db->jdate($row->date),'day',false,$outputlangs,true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+21, $tab3_top+$y);
				$pdf->MultiCell(20, 3, price($sign * $row->amount, 0, $outputlangs), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+40, $tab3_top+$y);
				$oper = $outputlangs->transnoentitiesnoconv("PaymentTypeShort" . $row->code);

				$pdf->MultiCell(20, 3, $oper, 0, 'L', 0);
				$pdf->SetXY($tab3_posx+58, $tab3_top+$y);
				$pdf->MultiCell(30, 3, $row->num, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3);

				$i++;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

	}





	/**
	 *   Show confirmation box and a conlusion sentence
	 *
	 *   @param		PDF			&$pdf     		Object PDF
	 *   @param		Object		$object			Object to show
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
	 */
	function _signature_conclusion(&$pdf, $object, $outputlangs)
	{
		global $conf;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		//creer la nouvell classe alpiroc qui contient les info de la table alpiroc. On y accède ensuite par la fonction fetch_name
		$alpiroc=new Alpiroc($this->db);
		
	
		
		if ($this->option_sign_aera==1){
			//Zone de confirmation 
			$posy =$this->marge_basse - 18 - 45;	// Height reserved to output the footer (value include bottom margin) + Hauteur reservé pour les infos
			$posx=$this->marge_gauche+$this->page_largeur/2+4;
			$pdf->SetFont('','B', $default_font_size);
			$pdf->SetXY($posx, $posy);
			
			$titre = $outputlangs->transnoentities("BonPourAccord");
			$pdf->MultiCell(80-2, 4, $titre, 0, 'L');
			$pdf->SetFont('','', $default_font_size-2);
			$texte=$outputlangs->transnoentities("SignatureAcceptation").".";
			
			$posy+=5;
			$pdf->SetXY($posx, $posy);
			$pdf->MultiCell(80-2, 4, $texte, 0, 'L');
			$texte=$outputlangs->transnoentities("Le")." ......./......./........... ".$outputlangs->transnoentities("A")." .......................";
			$posy+=10;
			$pdf->SetXY($posx, $posy);
			$pdf->MultiCell(80-2, 4, $texte, 0, 'L');
			
			$texte = $outputlangs->transnoentities("SignatureClient");
			$posy+=5;
			$pdf->SetXY($posx, $posy);
			$pdf->MultiCell(80-2, 4, $texte, 0, 'L');
		}
		
		//Phrase de conclusion TODO A mettre dans la fonction tableau tot et ajouter un positionnement relatif. 
		if ($this->option_conclusion_sentence==1){	
			$posx=$this->page_largeur/2-80;
			$posy =$this->marge_basse - 18 - 12;
			$pdf->SetFont('','', $default_font_size-2);
			
			$alpiroc->fetchValueFromProfil("remerciement",$this->profil);
			$texte=$alpiroc->content;
			
			if ($texte==""){
				$texte=$outputlangs->transnoentities("AddRemerciement");
			}
			$pdf->SetXY($posx, $posy);
			$pdf->MultiCell(160-2, 4, $texte, 0, 'C');
		}
	}

	
	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		PDF			&$pdf     		Object PDF
	 *   @param		Object		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
	 */
	function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;
		
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('','', $default_font_size - 1);

		// If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && $this->franchise == 1)
		{
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(65, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy=$pdf->GetY()+4;
		}

		$posxval=52;

        // Show shipping date
        if ($object->date_livraison)
		{
            $outputlangs->load("sendings");
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("DateDeliveryPlanned").':';
			$pdf->MultiCell(65, 4, $titre, 0, 'L');
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posxval, $posy);
			$dlp=dol_print_date($object->date_livraison,"daytext",false,$outputlangs,true);
			$pdf->MultiCell(65, 4, $dlp, 0, 'L');

            $posy=$pdf->GetY()+1;
		}
        elseif ($object->availability_code || $object->availability)    // Show availability conditions
		{
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("AvailabilityPeriod").':';
			$pdf->MultiCell(65, 4, $titre, 0, 'L');
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posxval, $posy);
			$lib_availability=$outputlangs->transnoentities("AvailabilityType".$object->availability_code)!=('AvailabilityType'.$object->availability_code)?$outputlangs->transnoentities("AvailabilityType".$object->availability_code):$outputlangs->convToOutputCharset($object->availability);
			$lib_availability=str_replace('\n',"\n",$lib_availability);
			$pdf->MultiCell(65, 4, $lib_availability, 0, 'L');

			$posy=$pdf->GetY()+1;
		}
		
		//~ Gestion des acompte que le client doit verser : 
		//~ if ($this->option_acompte==1){
			//~ //Récupère la valeur de l'acompte
			//~ $alpiroc=new Alpiroc($this->db);
			//~ $alpiroc->fetchValueFromProfil("acompte",$this->profil);
			//~ $acompte=$alpiroc->content;
			//~ if ($this->profil==''){$acompte=30;}//Default value
			//~ $pdf->SetFont('','B', $default_font_size - 2);
			//~ $pdf->SetXY($this->marge_gauche, $posy);
			//~ $titre = $outputlangs->transnoentities("VersementAcompte").":";
			//~ $texte1=$outputlangs->transnoentities("DebutVersement")." ";
			//~ $texte2=" ".$outputlangs->transnoentities("ComplementVersement")." ";
			//~ $texte=$texte1.$acompte."%".$texte2.round($acompte/100*$object->total_ttc)." ".$outputlangs->transnoentities("Euros");
			//~ $pdf->MultiCell(65, 4, $titre, 0, 'L');
			//~ $pdf->SetXY($posxval, $posy);
			//~ $pdf->SetFont('','', $default_font_size - 2);
			//~ $pdf->MultiCell(50, 4, $texte, 0, 'L');
			//~ $posy=$pdf->GetY()+1;
		//~ }

		// Show payments conditions
		if (empty($conf->global->FACTURE_PDF_HIDE_PAYMENTTERMCOND) && ($object->cond_reglement_code || $object->cond_reglement))
		{
			if ($this->option_condreg==1){
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche, $posy);
				$titre = $outputlangs->transnoentities("PaymentConditions").':';
				$pdf->MultiCell(65, 4, $titre, 0, 'L');
				$pdf->SetFont('','', $default_font_size - 2);
				$pdf->SetXY($posxval, $posy);
				$lib_condition_paiement=$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code)!=('PaymentCondition'.$object->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code):$outputlangs->convToOutputCharset($object->cond_reglement_doc);
				$lib_condition_paiement=str_replace('\n',"\n",$lib_condition_paiement);
				$pdf->MultiCell(65, 4, $lib_condition_paiement,0,'L');
				$posy=$pdf->GetY()+1;
			}
		}

		if (empty($conf->global->FACTURE_PDF_HIDE_PAYMENTTERMCOND))
		{
			if ($this->option_modereg==1)
			{
			// Check a payment mode is defined
			/* Not required on a proposal
			if (empty($object->mode_reglement_code)
			&& ! $conf->global->FACTURE_CHQ_NUMBER
			&& ! $conf->global->FACTURE_RIB_NUMBER)
			{
				$pdf->SetXY($this->marge_gauche, $posy);
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->MultiCell(90, 3, $outputlangs->transnoentities("ErrorNoPaiementModeConfigured"),0,'L',0);
				$pdf->SetTextColor(0,0,0);

				$posy=$pdf->GetY()+1;
			}
			*/

			// Show payment mode
			if ($object->mode_reglement_code
			&& $object->mode_reglement_code != 'CHQ'
			&& $object->mode_reglement_code != 'VIR')
			{
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche, $posy);
				$titre = $outputlangs->transnoentities("PaymentMode").':';
				$pdf->MultiCell(65, 5, $titre, 0, 'L');
				$pdf->SetFont('','', $default_font_size - 2);
				$pdf->SetXY($posxval, $posy);
				$lib_mode_reg=$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code)!=('PaymentType'.$object->mode_reglement_code)?$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code):$outputlangs->convToOutputCharset($object->mode_reglement);
				$pdf->MultiCell(65, 5, $lib_mode_reg,0,'L');

				$posy=$pdf->GetY()+2;
			}

			// Show payment mode CHQ
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ')
			{
				// Si mode reglement non force ou si force a CHQ
				if (! empty($conf->global->FACTURE_CHQ_NUMBER))
				{
					if ($conf->global->FACTURE_CHQ_NUMBER > 0)
					{
						$account = new Account($this->db);
						$account->fetch($conf->global->FACTURE_CHQ_NUMBER);

						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('','B', $default_font_size - 3);
						$pdf->MultiCell(65, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$account->proprio),0,'L',0);
						$posy=$pdf->GetY()+1;

			            if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS))
			            {
							$pdf->SetXY($this->marge_gauche, $posy);
							$pdf->SetFont('','', $default_font_size - 3);
							$pdf->MultiCell(70, 3, $outputlangs->convToOutputCharset($account->adresse_proprio), 0, 'L', 0);
							$posy=$pdf->GetY()+2;
			            }
					}
					if ($conf->global->FACTURE_CHQ_NUMBER == -1)
					{
						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('','B', $default_font_size - 3);
						$pdf->MultiCell(65, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$this->emetteur->name),0,'L',0);
						$posy=$pdf->GetY()+1;

			            if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS))
			            {
							$pdf->SetXY($this->marge_gauche, $posy);
							$pdf->SetFont('','', $default_font_size - 3);
							$pdf->MultiCell(65, 3, $outputlangs->convToOutputCharset($this->emetteur->getFullAddress()), 0, 'L', 0);
							$posy=$pdf->GetY()+2;
			            }
					}
				}
			}

			// If payment mode not forced or forced to VIR, show payment with BAN
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR')
			{
				if (! empty($conf->global->FACTURE_RIB_NUMBER))
				{
					$account = new Account($this->db);
					$account->fetch($conf->global->FACTURE_RIB_NUMBER);

					$curx=$this->marge_gauche;
					$cury=$posy;

					$posy=pdf_bank($pdf,$outputlangs,$curx,$cury,$account,0,$default_font_size);

					$posy+=2;
				}
			}
			}
		}

		return $posy;
	}


	/**
	 *	Show total to pay
	 *
	 *	@param	PDF			&$pdf           Object PDF
	 *	@param  Facture		$object         Object invoice
	 *	@param  int			$deja_regle     Montant deja regle
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		global $conf,$mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$tab2_top = $posy;
		$tab2_hl = 4;
		$pdf->SetFont('','', $default_font_size - 1);

		// Tableau total
		if ($this->option_condreg==0 AND $this->option_modereg==0 AND $this->option_rappel==1){
			$col1x = 52.5; $col2x = 105; $coeflargeur=0.6; //Seulement pour la lettre de rappel
		}else{
			$col1x = 120; $col2x = 170;$coeflargeur=1;
		}
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$col2x-=20;
		}
		$largcol2 = ($this->page_largeur - $this->marge_droite - $col2x)*$coeflargeur;
		
		$useborder=0;
		$index = 0;

		// Total HT
		$pdf->SetFillColor(255,255,255);
		$pdf->SetXY($col1x, $tab2_top + 0);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

		$pdf->SetXY($col2x, $tab2_top + 0);
		$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ht + (! empty($object->remise)?$object->remise:0), 0, $outputlangs), 0, 'R', 1);

		// Show VAT by rates and total
		$pdf->SetFillColor(248,248,248);

		$this->atleastoneratenotnull=0;
		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
		{
			$tvaisnull=((! empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
			if (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_ISNULL) && $tvaisnull)
			{
				// Nothing to do
			}
			else
			{
				//Local tax 1 before VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')
				//{
					foreach( $this->localtax1 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('1','3','5','7'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey!=0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;

								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalLT1",$mysoc->country_code).' ';
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
							}
						}
					}
	      		//}
				//Local tax 2 before VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')
				//{
					foreach( $this->localtax2 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('1','3','5','7'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey!=0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;



								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalLT2".$mysoc->country_code).' ';
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);

							}
						}
					}
				//}
				// VAT
				foreach($this->tva as $tvakey => $tvaval)
				{
					if ($tvakey > 0)    // On affiche pas taux 0
					{
						$this->atleastoneratenotnull++;

						$index++;
						$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

						$tvacompl='';
						if (preg_match('/\*/',$tvakey))
						{
							$tvakey=str_replace('*','',$tvakey);
							$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
						}
						$totalvat =$outputlangs->transnoentities("TotalVAT").' ';
						$totalvat.=vatrate($tvakey,1).$tvacompl;
						$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

						$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
						$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
					}
				}

				//Local tax 1 after VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')
				//{
					foreach( $this->localtax1 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('2','4','6'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey != 0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;

								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalLT1",$mysoc->country_code).' ';
								if ($localtax_type == '7') {  // amount on order
									$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

									$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
									$pdf->MultiCell($largcol2, $tab2_hl, price($tvakey, 0, $outputlangs), 0, 'R', 1);
								}
								else
								{
									$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
									$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);
									$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
									$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
								}
							}
						}
					}
	      		//}
				//Local tax 2 after VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')
				//{
					foreach( $this->localtax2 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('2','4','6'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey != 0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;

								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalLT2",$mysoc->country_code).' ';

								if ($localtax_type == '7') {  // amount on order
									$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);
									$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
									$pdf->MultiCell($largcol2, $tab2_hl, price($tvakey, 0, $outputlangs), 0, 'R', 1);
								}
								else
								{
									$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
									$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

									$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
									$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
								}
							}
						}
					}
				// Gestion des acomptes lors de la facturation et des lettre de rappel, cet acompte à déja été payé !
				$deja_paye=0;
				if ($this->option_acompte==2 ){
					//Récupère la valeur de l'acompte
					$alpiroc=new Alpiroc($this->db);
					$alpiroc->fetchValueFromProfil("acompte",$this->profil);
					$acompte=$alpiroc->content;
					if ($this->profil==''){$acompte=30;}//Default value
					$deja_paye=($object->total_ttc)*$acompte/100;
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->SetFillColor(240,240,240);
					$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("DejaPaye"), $useborder, 'L', 1);
					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price(-1*$deja_paye, 0, $outputlangs), $useborder, 'R', 1);
				}

				// Total TTC
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->SetTextColor(0,0,60);
				$pdf->SetFillColor(224,224,224);
				if ($this->option_rappel==1){
					$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalRestantDu")." (".$outputlangs->transnoentitiesnoconv("Currency".$conf->currency).")", $useborder, 'L', 1);
				}else{
					$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC")." (".$outputlangs->transnoentitiesnoconv("Currency".$conf->currency).")", $useborder, 'L', 1);
				}
				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc-$deja_paye, 0, $outputlangs), $useborder, 'R', 1);
			}
		}

		$pdf->SetTextColor(0,0,0);

		/*
		$resteapayer = $object->total_ttc - $deja_regle;
		if (! empty($object->paye)) $resteapayer=0;
		*/

		if ($deja_regle > 0)
		{
			$index++;

			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("AlreadyPaid"), 0, 'L', 0);

			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle, 0, $outputlangs), 0, 'R', 0);

			/*
			if ($object->close_code == 'discount_vat')
			{
				$index++;
				$pdf->SetFillColor(255,255,255);

				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOffered"), $useborder, 'L', 1);

				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle, 0, $outputlangs), $useborder, 'R', 1);

				$resteapayer=0;
			}
			*/

			$index++;
			$pdf->SetTextColor(0,0,60);
			$pdf->SetFillColor(224,224,224);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);

			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs), $useborder, 'R', 1);

			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetTextColor(0,0,0);
		}

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
	}

	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			&$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop=0, $hidebottom=0)
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;
		//On force l'écriture des entêtes dans le tableau car c'est plus clair pour le lecteur
		$hidetop=0;//La fonction empty renvoi TRUE
		
		
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','',$default_font_size - 2);

		if (empty($hidetop))
		{
			//On dégage le montant exprimé en euros
			//~ $titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$conf->currency));
			//~ $pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top-4);
			//~ $pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);
		}
		
		

		$pdf->SetDrawColor(128,128,128);
		$pdf->SetFont('','',$default_font_size - 1);

		// Output Rect
		$this->printRect($pdf,$this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, $hidetop, $hidebottom);	// Rect prend une longueur en 3eme param et 4eme param

		if (empty($hidetop))
		{
			$pdf->line($this->marge_gauche, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);	// line prend une position y en 2eme param et 4eme param : LIGNE HORIZONTALE

			$pdf->SetXY($this->posxdesc-1, $tab_top+1);
			$pdf->MultiCell(108,2, $outputlangs->transnoentities("Designation"),'','L');
		}
		

		$posx_col=$this->postotalht;
		$pdf->line($posx_col, $tab_top, $this->postotalht, $tab_top +$tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($posx_col+1, $tab_top+1);
			$pdf->MultiCell($this->largeur_ht,2, $outputlangs->transnoentities("TotalHT"),'','C');
		}
		

		
		if ($this->option_disp_tva==1){
			$posx_col=$posx_col-$this->largeur_tva;
			$pdf->line($posx_col, $tab_top, $posx_col, $tab_top + $tab_height);
			if (empty($hidetop)){
				$pdf->SetXY($posx_col+1, $tab_top+1);
				$pdf->MultiCell($this->largeur_tva,2, $outputlangs->transnoentities("VAT"),'','C');//Premier parametre est la largeur de la cellule
			}	

		}

		
		$x_pos_qtx=$this->posxtva;
		if ($this->atleastonediscount){//Si deuxième page, La ligne est tout de même tracée pour être homogène avec la première page
			$posx_col=$posx_col-$this->largeur_discount;
			$pdf->line($posx_col, $tab_top,$posx_col, $tab_top + $tab_height);
			$x_pos_qtx=$this->posxdiscount;
		}

		if ($this->atleastonediscount and empty($hidetop)){
			$pdf->SetXY($posx_col+1, $tab_top+1);
			$pdf->MultiCell($this->largeur_discount,2, $outputlangs->transnoentities("ReductionShort"),'','C');
		}


		if($this->option_prixunit_qty==1){
			$posx_col=$posx_col-$this->largeur_qtx;
			if (empty($hidetop)){
				$pdf->SetXY($posx_col+1, $tab_top+1);
				$pdf->MultiCell($this->largeur_qtx,2, $outputlangs->transnoentities("Qty"),'','C');
			}
			$pdf->line($posx_col, $tab_top, $posx_col, $tab_top + $tab_height);
			
			$posx_col=$posx_col-$this->largeur_up;
			if (empty($hidetop)){
				$pdf->SetXY($posx_col+1, $tab_top+1);
				$pdf->MultiCell($this->largeur_up,2, $outputlangs->transnoentities("PriceUHT"),'','C');
			}
			$pdf->line($posx_col, $tab_top, $posx_col, $tab_top + $tab_height);
		}	
	}


	
	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			&$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param	object		$hookmanager	Hookmanager object
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $hookmanager)
	{
		
		//Si le choix de l'entete est azur on appelle la fonction azur
		if ($this->option_head=="azur"){
			$this->_pagehead_azur($pdf, $object, $showaddress, $outputlangs, $hookmanager);
			return;
		}
		
		
		global $conf,$langs;

		$outputlangs->load("main");
		$outputlangs->load("bills");
		$outputlangs->load("propal");
		$outputlangs->load("companies");
		$outputlangs->load("alpiroc@alpiroc");

		//crer la nouvelle classe alpiroc qui contient les info de la table alpiroc. On y accède ensuite par la fonction fetch_name
		$alpiroc=new Alpiroc($this->db);
		

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		//  Show Draft Watermark
		if($object->statut==0 && (! empty($conf->global->FACTURE_DRAFT_WATERMARK)) )
		{
            pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->FACTURE_DRAFT_WATERMARK);
		}

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 3);

		$posy=$this->marge_haute;//posY est defini initialement à la marge haute
		$pdf->SetXY($this->marge_gauche,$posy);


		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
			    //Utilise une nouvelle fonction qui dimmenssionne le logo de manière optimale => 3cm de haut si possible sinon moins en fonciton de la largeur
			    $height=pdf_getHeightForLogoAlpiroc($logo);
			    //$height=30;// Hauteur du logos Alpiroc !
			    $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B',$default_font_size - 2);
				$pdf->MultiCell(50, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(50, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell(50, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}
		
		


		//BLOC : ADRESSE DE L ENVOYEUR : SOIT L ENTREPRISE 
		$posy=$this->marge_haute;//posY est defini initialement à la marge haute
		
		//We always show the adress on the 2nd page : TODO Add a custom tip
			// Sender properties
			$carac_emetteur='';
			$tel='';
			$email='';
			$town='';
			$street='';
			$compagny_name='';
			$city_code='';
			
			//Caractéristique de l'entreprise
		 	$source_compagny=$this->emetteur;
		 	
    		if ($source_compagny->phone){
				$tel=$source_compagny->phone;
			}
    		if ($source_compagny->email){
				$email=$source_compagny->email;
			}
			if ($source_compagny->name){
				$compagny_name=$source_compagny->name;
			}
			if ($source_compagny->address){
				$street=$source_compagny->address;
			}
			if ($source_compagny->country_code){
				$city_code=$source_compagny->zip;
			}
			if ($source_compagny->town){
				$town=$source_compagny->town;
			}
    	// Frédéric Roux
			if ($street=='') {
				$address= $compagny_name;
			}else{
				$address= $compagny_name.", ".$street;
			}
		// Fin Frédéric Roux
    		$ville=$city_code." ".$town;
    		$carac_emetteur=$outputlangs->transnoentities("Phone")." : ".$tel."  - ".$outputlangs->transnoentities("Email")." : ".$email;

			// Show sender
			$posx=$this->page_largeur-$this->marge_droite-150;//On defini $posX à -100 depuis la marge de droite
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-100;

			// Show sender name
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell($this->page_largeur-$this->marge_droite-$posx, 4, $address, 0, 'R');
			
			$posy=$pdf->getY();
			$pdf->SetXY($posx,$posy);
			$pdf->MultiCell($this->page_largeur-$this->marge_droite-$posx, 4, $ville, 0, 'R');
			
			// Show sender information
			$posy+=5;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','B', $default_font_size - 1);
			$pdf->MultiCell($this->page_largeur-$this->marge_droite-$posx, 4, $carac_emetteur, 0, 'R');
			
			//Print slogan/note de entreprise : TODO Prevoir une rubrique ?!
			if ($this->option_slogan==1){
				$Slogan=$this->emetteur->note_private;
				$posy+=5;
				$pdf->SetXY($posx,$posy);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('','B', $default_font_size - 1);
				$pdf->MultiCell($this->page_largeur-$this->marge_droite-$posx, 4, $Slogan, 0, 'R');
			}
			//Récupère la position Y finale
			$posy_bas_entete=$pdf->getY();


		if ($showaddress)
		{
			//BLOC : REFERENCE COMMERCIAL + ADRESSE CHANTIER (Note public) + Contact Chantier + Ref CLient
			if ($posy_bas_entete<42-5){
				$posy=42;//Position pour les lettres
			}else{
				$posy=min(60,$posy_bas_entete+5);//SI l'adress plus la note public/privé de l'entreprise déborde alors on force la position vers le bas
			}
			$posx=$this->marge_gauche;//set to marge à gauche
			$widthrecbox=80;
			

			if ($this->option_notepublic==1){
				// Ecrit titre de la note public si présent
				if ($object->note_public!="")
				{
					$alpiroc->fetchValueFromProfil("titre1",$this->profil);
					$titre = $alpiroc->content;
					if ($this->profil==''){$titre=$outputlangs->transnoentities("AdresseChantier");}
					if ($titre!=""){
						$pdf->SetFont('','B', $default_font_size);
						$pdf->SetXY($posx,$posy);
						$pdf->SetTextColor(0,0,0);
						$pdf->MultiCell($widthrecbox-1, 4, $titre, 0, 'L');
						$posy=$pdf->getY();
					}
			
					// Ecrit Adresse du chantier : Note public
					$pdf->SetXY($posx,$posy);
					$pdf->SetFont('','', $default_font_size);
					$pdf->writeHTMLCell($widthrecbox-1, 3, $posx, $posy, dol_htmlentitiesbr($object->note_public), 0, 1);
					// Get new y
					$posy=$pdf->getY();
				}
			}
			
			
			// Add internal contact of proposal if defined
			$arrayidcontact=$object->getIdContact('internal','SALESREPFOLL');
			if (count($arrayidcontact) > 0)
			{
				$object->fetch_user($arrayidcontact[0]);
			}
			
			
			if ($this->option_contact==1){
				if (! empty($object->user->firstname) | ! empty($object->user->lastname))
				{	
					//Ecrit Contact/Responsable du chantier
					//Get le contact chantier : titre 2
					$dislaytel = '';  // Frédéric Roux
					$alpiroc->fetchValueFromProfil("titre2",$this->profil);
					$titre = $alpiroc->content;
					if ($this->profil==""){$titre=$outputlangs->transnoentities("ContactChantier");}
					if ($titre!="")
					{
						$posy+=1;
						$pdf->SetFont('','B', $default_font_size);
						$pdf->SetXY($posx,$posy);
						$pdf->SetTextColor(0,0,0);
						$pdf->MultiCell($widthrecbox-1, 4, $titre, 0, 'L');
					$posy=$pdf->getY();
					}
				
					$pdf->SetXY($posx,$posy);
					$pdf->SetFont('','', $default_font_size);
					// Frédéric Roux
					if (! empty($object->user->user_mobile)) {
						$displaytel = ' - '.$object->user->user_mobile;
					}
					$pdf->MultiCell($widthrecbox-1, 3,  $object->user->firstname.' '.$object->user->lastname.$displaytel, '', 'L');
					// Fin Frédéric Roux
					$pdf->MultiCell($widthrecbox-1, 3,  $object->user->email, '', 'L');
					$posy=$pdf->getY();
				}
			}
			// Here we store the y position to take the max after the address will be written
			$posy_stored=$posy;
			
			//BLOC : ADRESSE CLIENT
			// If CUSTOMER contact defined, we use it
			$usecontact=false;
			$arrayidcontact=$object->getIdContact('external','BILLING');
			if (count($arrayidcontact) > 0)
			{
				$usecontact=true;
				$result=$object->fetch_contact($arrayidcontact[0]);
			}

			//Recipient name
			// On peut utiliser le nom de la societe du contact
			if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
				$thirdparty = $object->contact;
			} elseif (version_compare(DOL_VERSION, "4.0.0")>=0) {
				$thirdparty = $object->thirdparty;
			} else {
				$thirdparty = $object->client;
			}
			

			$carac_client_name= pdfBuildThirdpartyNameAlpiroc($thirdparty,$outputlangs);
			$carac_client=pdf_build_address($outputlangs,$this->emetteur,$thirdparty,($usecontact?$object->contact:''),$usecontact,'target');

			// Show recipient
			$widthrecbox=100;
			if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
			
			if ($posy_bas_entete<42-5){
				$posy=42;//Position pour les lettres
			}else{
				$posy=min(60,$posy_bas_entete+5);//SI l'adress plus la note public/privé de l'entreprise déborde alors on force la position vers le bas
			}
			$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

			//Recupère la position de l'adresse
			if ($this->option_posaddresse==1){
				$html_pos_addresse='R';
				$dposx=-2;
			}else{
				$html_pos_addresse='L';
				$dposx=2;
			}
			
			// Show recipient frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx+$dposx,$posy-5);
			$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("Adressee_a")." :", 0, $html_pos_addresse);
			$hautcadre=40;
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

			// Show recipient name
			//Si le client est un individu on ajoute la mention Mme Mr
			if ($this->option_affichemmemr==1){
				if (array_key_exists('typent_code',$thirdparty)){
					if($thirdparty->typent_code=="TE_PRIVATE"){
						$carac_client_name=$outputlangs->transnoentities("MmeMr")." ".$carac_client_name;
					}
				}
			}
			
			
			$pdf->SetXY($posx+$dposx,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, $html_pos_addresse);

			// Show recipient information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+$dposx,$posy+4+(dol_nboflines_bis($carac_client_name,50)*4));
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, $html_pos_addresse);
			
			$pdf->SetTextColor(0,0,0);
			//Here we get the y position max between the two column. Add a 5 mm margin after the box (posy+40+5)
			$posy=max($posy+45,$posy_stored);
			//And we finally set posy as the final y position (posx probably useless)
			$pdf->SetXY($this->marge_gauche,$posy);
			
			//Affiche la note privé
			if ($object->note_private!="" && $this->option_dispprivatenote==1){
				$pdf->MultiCell(0, 4, $object->note_private, 0,"L");
				$posy=$pdf->GetY();
			}
			
			$pdf->SetXY($this->marge_gauche,$posy);
			
		}
		$posy=max($posy,$posy_bas_entete+5);
		$pdf->SetXY($posx,$posy);
		$posy=$pdf->GetY();
		
	}



	
	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			&$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param	object		$hookmanager	Hookmanager object
	 *  @return	void
	 */
	function _pagehead_azur(&$pdf, $object, $showaddress, $outputlangs, $hookmanager)
{
		global $conf,$langs;

		$outputlangs->load("main");
		$outputlangs->load("bills");
		$outputlangs->load("propal");
		$outputlangs->load("companies");

		#we define $thirdparty depending the version of dolibarr
		if (version_compare(DOL_VERSION, "4.0.0")>=0) {
			$thirdparty = $object->thirdparty;
		}else{
			$thirdparty = $object->client;
		}

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);
		
		$alpiroc=new Alpiroc($this->db);
		
		//  Show Draft Watermark
		if($object->statut==0 && (! empty($conf->global->PROPALE_DRAFT_WATERMARK)) )
		{
            pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->PROPALE_DRAFT_WATERMARK);
		}

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 3);

		$posy=$this->marge_haute;
		$posx=$this->page_largeur-$this->marge_droite-100;

		$pdf->SetXY($this->marge_gauche,$posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
			    $height=pdf_getHeightForLogo($logo);
			    $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B',$default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		$pdf->SetFont('','B',$default_font_size + 3);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$title=$outputlangs->transnoentities("Invoice");
		$pdf->MultiCell(100, 4, $title, '', 'R');

		$pdf->SetFont('','B',$default_font_size);

		$posy+=5;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

		$posy+=1;
		$pdf->SetFont('','', $default_font_size - 2);

		if ($object->ref_client)
		{
			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("RefCustomer")." : " . $outputlangs->convToOutputCharset($object->ref_client), '', 'R');
		}

		$posy+=4;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->date,"day",false,$outputlangs,true), '', 'R');

		$posy+=4;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
        	$pdf->MultiCell(100, 3, $outputlangs->transnoentities("DateDue")." : " . dol_print_date($object->date_lim_reglement,"day",false,$outputlangs,true), '', 'R');

		if ($thirdparty->code_client)
		{
			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($thirdparty->code_client), '', 'R');
		}

		$posy+=2;

		// Show list of linked objects
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);
		$posy_note_private=$pdf->GetY();
		
		if ($showaddress)
		{
			// Sender properties
			$carac_emetteur='';
		 	// Add internal contact of proposal if defined
			$arrayidcontact=$object->getIdContact('internal','SALESREPFOLL');
		 	if (count($arrayidcontact) > 0)
		 	{
		 		$object->fetch_user($arrayidcontact[0]);
		 		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
		 	}

		 	$carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $thirdparty);

			// Show sender
			$posy=max($posy_note_private+7,42);
		 	$posx=$this->marge_gauche;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-80;
			$hautcadre=40;

			// Show sender frame
			$pdf->SetFillColor(230,230,230);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx,$posy-5);
			$pdf->MultiCell(66,5, $outputlangs->transnoentities("BillFrom").":", 0, 'L');
			$pdf->SetXY($posx+2,$posy);
			
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(80, 3, "", 0, 'R', 1);
			// Show sender name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L',1);
			$posy=$pdf->getY();

			// Show sender information
			$pdf->SetXY($posx+2,$posy);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L',1);

			$posy_from=$pdf->GetY();

			// If CUSTOMER contact defined, we use it
			$usecontact=false;
			$arrayidcontact=$object->getIdContact('external','BILLING');
			if (count($arrayidcontact) > 0)
			{
				$usecontact=true;
				$result=$object->fetch_contact($arrayidcontact[0]);
			}

			//Recipient name
			// On peut utiliser le nom de la societe du contact
			if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
				$thirdparty = $object->contact;
			} elseif (version_compare(DOL_VERSION, "4.0.0")>=0) {
				$thirdparty = $object->thirdparty;
			} else {
				$thirdparty = $object->client;
			}

			$carac_client_name= pdfBuildThirdpartyNameAlpiroc($thirdparty,$outputlangs);
			$carac_client=pdf_build_address($outputlangs,$this->emetteur,$thirdparty,($usecontact?$object->contact:''),$usecontact,'target');

			// Show recipient
			$widthrecbox=100;
			if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
			$posy=max($posy_note_private+7,42);
			$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx+2,$posy-5);
			$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":", 0, 'L');
			
			
			// Show recipient name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, 'L');

			// Show recipient information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy+4+(dol_nboflines_bis($carac_client_name,50)*4));
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
			
			$posy_to=$pdf->GetY();
			
			//Write boxes
			$hautcadre=max($posy_from,$posy_to)-(max($posy_note_private+7,42));
			$posx_from=$posx=$this->marge_gauche;
			$posx_to=$this->page_largeur-$this->marge_droite-$widthrecbox;
			//~ $pdf->Rect($posx_from, 42, 80, $hautcadre,"F");
			$pdf->Rect($posx_to, max($posy_note_private+7,42), 100, $hautcadre);
			
			$posy=max($posy_note_private+7,42)+$hautcadre+5;
			
			//And we finally set posy as the final y position (posx probably useless)
			$pdf->SetXY($this->marge_gauche,$posy);
			if ($object->note_private!="" && $this->option_dispprivatenote==1){
                		$pdf->writeHTMLCell(0, 1,$this->marge_gauche,$posy, $object->note_private, 0,2);
				$posy=$pdf->GetY()+5;
			}
			
			$pdf->SetXY($this->marge_gauche,$posy);
			if ($object->note_public!="" && $this->option_notepublic==1){
				$alpiroc->fetchValueFromProfil("titre1",$this->profil);
				$titre = $alpiroc->content;
				$pdf->SetFont('','B', $default_font_size);
				$pdf->SetXY($posx,$posy);
				$pdf->SetTextColor(0,0,0);
				$pdf->MultiCell($widthrecbox-1, 4, $titre, 0, 'L');
				$posy=$pdf->getY();
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
                		$pdf->writeHTMLCell(0, 1,$this->marge_gauche,$posy, $object->note_public, 0,2);
				$posy=$pdf->GetY();
			}
			
		}
		$posy=max($posy,$posy_note_private+7);
		$pdf->SetXY($posx,$posy);
		$posy=$pdf->GetY();
		
		
		$pdf->SetTextColor(0,0,0);
		
		
		
	}





	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			&$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
		return pdf_pagefoot($pdf,$outputlangs,'FACTURE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,0,$hidefreetext);
	}

}

?>
