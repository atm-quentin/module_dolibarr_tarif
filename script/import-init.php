<?php

ini_set('memory_limit', '1024M');
set_time_limit(0);

require('../config.php');
//dol_include_once("/product/class/product.class.php");
dol_include_once("/product/class/product.class.php");
dol_include_once("/custom/tarif/class/tarif.class.php");
dol_include_once("/custom/asset/class/asset.class.php");
dol_include_once("/societe/class/societe.class.php");
dol_include_once("/contact/class/contact.class.php");

$ATMdb = new Tdb;
$articlesfile = fopen('../import/produits.csv', 'r');
$flaconsfile = fopen('../import/flacons.csv','r');
$lotsfile = fopen('../import/lots.csv','r');
$unitesfile = fopen('../import/unites.csv','r');
$societesfile = fopen('../import/contacts.csv','r');
$fournisseursfile = fopen('../import/fournisseurs.csv','r');
$typeclifile = fopen('../import/type_cli.csv','r');
$TGlobal = array();
$i = 0;

function _unit($unite){
	switch ($unite) {
		case 'µg':
			return -9;
			break;
		case 'mg':
			return -6;
			break;
		case 'g':
			return -3;
			break;
		case 'gr':
			return -3;
			break;
		case 'kg':
			return 0;
			break;
	}
}

function _add_condi($ATMdb,$line,$produit,$nbColUnit,$nbColPrix,$nbColRem=0){
	($nbColRem != 0) ? $remise_percent = $line[$bnColRem] : $remise_percent = 0;
	$string_unite = explode(" ", $line[$nbColUnit]);
			
	$tarif = new TTarif;
	$tarif->unite_value 		= _unit($string_unite[1]);
	$tarif->unite 				= $string_unite[1];
	$tarif->quantite 			= $string_unite[0];
	$tarif->price_base_type 	= "HT";
	$tarif->fk_product 			= $produit->id;
	$tarif->fk_user_author 		= 1;
	$tarif->tva_tx 				= 19.6;
	$tarif->remise_percent 		= $remise_percent;
	$tarif->prix 				= $line[$nbColPrix];
	$tarif->save($ATMdb);
	echo "$string_unite[0] "._unit($string_unite[1])." ";
}

function _add_tiers($ATMdb,$user,$db,$line,$type){
	$num_ligne = ($type=="client" || $type == "prospect") ? 9: 6;
	
	$base_pays = array('JAPAN' => 'Japon',
						'Japan' => 'Japon',
						'Poland' => 'Pologne',
						'United Kindom' => 'United Kingdom',
						'Great Britain' => 'United Kingdom',
						'UK' => 'United Kingdom',
						'USA' => 'United States',
						'U.S.A.' => 'United States',
						'U.S.A' => 'United States',
						'Singapore' => 'Singapour',					
						'SINGAPORE' => 'Singapour',
						'Northern Ireland-UK' => 'Irland',
						'Ireland' => 'Irland',
						'Nothern Ireland' => 'Irland',
						'Egypt' => 'Egypte',
						'Hungary' => 'Hongrie',
						'Mexico DF' => 'Mexique',
						'Mexico' => 'Mexique',
						'Austria' => 'Autriche',
						'AUSTRIA' => 'Autriche',
						'Argentina' => 'Argentine',
						'Denmark' => 'Danemark',
						'Belgique' => 'Belgium',
						'Italia' => 'Italy',
						'Italie' => 'Italy',
						'ITALIA' => 'Italy',
						'Vicenza Italia' => 'Italy',
						'Belgique' => 'Belgium',
						'Lebanon' => 'Liban',
						'Puerto Rico' => 'Porto Rico',
						'TAIWAN ROC' => 'Taïwan',
						'New Zealand' => 'Nouvelle-Zélande',
						'Croatia' => 'Croatie',
						'Brasil' => 'Brazil',
						'Brésil' => 'Brazil',
						'Vietnam' => 'Viêt Nam',
						'South Korea' => 'South Corea',
						'South Africa' => 'Afrique du Sud',
						'MALAYSIA' => 'Malaisie',
						'Malaysia' => 'Malaisie',
						'New Mexico' => 'United States',
						'CZECH Republik' =>'République Tchèque',
						'Thailand' => 'Thaïlande',
						'PEROU' => 'Pérou',
						'AUSTRALIE' => 'Australia',
						'Chine' => 'China',
						'The Netherlands' => 'Nerderland',
						'Nederland' => 'Nerderland',
						'Netherlands' => 'Nerderland',
						'NEDERLAND' => 'Nerderland',
						'Pays-Bas' => 'Nerderland',
						'Hong-Kong' => 'Hong Kong',
						'HONG KONG PRC' => 'Hong Kong',
						'HONG-KONG-PRC' => 'Hong Kong',
						'ARGENTINA' => 'Argentine',
						'DEUTSCHLAND' => 'Germany',
						'Tunisie' => 'Tunisia',
						'Finland' =>'Finlande',
						'PR CHINA' => 'China',
						'PR China' => 'China',
						'Québec, Canada' => 'Canada',
						'Ontario-Canada' => 'Canada'
					);
	
	if(in_array(htmlentities($line[$num_ligne],ENT_QUOTES,'UTF-8'), array_keys($base_pays)))
		$pays = htmlentities($base_pays[$line[$num_ligne]],ENT_QUOTES,'UTF-8');
	else
		$pays = htmlentities($line[$num_ligne],ENT_QUOTES,'UTF-8');
						
	
	$ATMdb->Execute('SELECT rowid FROM '.MAIN_DB_PREFIX."c_pays WHERE libelle = '".html_entity_decode($pays,ENT_QUOTES,'ISO-8859-1')."' LIMIT 1");
	
	$societe = new Societe($db);
	if($type == "client"){
		$societe->client 			= 1; //Client
		$societe->fournisseur 		= 0; //fournisseur
		$societe->particulier 		= 0; //Société/Association
		$societe->name 				= (!empty($line[5]))? $line[5]: "";
		$societe->status 			= 1; //En activité
		$societe->address 			= (!empty($line[6]))? $line[6]: "";
		$societe->zip 				= (!empty($line[8]))? $line[8]: "";
		$societe->town 				= (!empty($line[7]))? $line[7]: "";
		$societe->country_id 		= ($ATMdb->Get_field('rowid')) ? $ATMdb->Get_field('rowid') : 0;
		$societe->email 			= (!empty($line[14]))? strtolower($line[14]): "";
		$societe->phone 			= (!empty($line[11]))? $line[11]: "";
		$societe->fax 				= (!empty($line[12]))? $line[12]: "";
		$societe->ref_ext 			= (!empty($line[0]))? $line[0]: "";
		$societe->default_lang      = (!empty($line[10]))? $line[10]: "";
	}
	elseif($type = "fournisseur"){
		$societe->client 			= 0; //Prospect
		$societe->fournisseur 		= 1; //fournisseur
		$societe->particulier 		= 0; //Société/Association
		$societe->name 				= (!empty($line[1]))? $line[1]: "";
		$societe->status 			= 1; //En activité
		$societe->address 			= (!empty($line[2]))? $line[2]: "";
		$societe->zip 				= (!empty($line[4]))? $line[4]: "";
		$societe->town 				= (!empty($line[5]))? $line[5]: "";
		$societe->country_id 		= ($ATMdb->Get_field('rowid')) ? $ATMdb->Get_field('rowid') : 0;
		$societe->email 			= (!empty($line[9]))? strtolower($line[9]): "";
		$societe->phone 			= (!empty($line[7]))? $line[7]: "";
		$societe->fax 				= (!empty($line[8]))? $line[8]: "";
		$societe->ref_ext 			= (!empty($line[0]))? $line[0]: "";
	}
	else{
		$societe->client 			= 2; //Client/Prospect
		$societe->fournisseur 		= 0; //fournisseur
		$societe->particulier 		= 0; //Société/Association
		$societe->name 				= (!empty($line[1]))? $line[1]: "";
		$societe->status 			= 1; //En activité
		$societe->address 			= (!empty($line[2]))? $line[2]: "";
		$societe->zip 				= (!empty($line[4]))? $line[4]: "";
		$societe->town 				= (!empty($line[5]))? $line[5]: "";
		$societe->country_id 		= ($ATMdb->Get_field('rowid')) ? $ATMdb->Get_field('rowid') : 0;
		$societe->email 			= (!empty($line[9]))? strtolower($line[9]): "";
		$societe->phone 			= (!empty($line[7]))? $line[7]: "";
		$societe->fax 				= (!empty($line[8]))? $line[8]: "";
		$societe->ref_ext 			= (!empty($line[0]))? $line[0]: "";
		$societe->default_lang      = (!empty($line[10]))? $line[10]: "";
	}
	
	$societe->create($user);
	
	if($type == "client"){
		if(!empty($line[3])){
			$contact=new Contact($db);
	        $contact->name			= $line[3];
	        $contact->firstname		= (!empty($line[2]))? $line[2]: "";
	        $contact->address		= (!empty($line[6]))? $line[6]: "";
	        $contact->zip			= (!empty($line[8]))? $line[8]: "";
	        $contact->town			= (!empty($line[7]))? $line[7]: "";
	        $contact->country_id	= ($ATMdb->Get_field('rowid')) ? $ATMdb->Get_field('rowid') : 0;;
	        $contact->socid			= $societe->id;	// fk_soc
	        $contact->status		= 1;
	        $contact->email			= (!empty($line[14]))? $line[14]: "";
			$contact->phone_pro		= (!empty($line[11]))? $line[11]: "";
			$contact->fax			= (!empty($line[12]))? $line[12]: "";
	        $contact->priv			= 0;
			
			$contact->create($user);
		}
		
		if(!empty($line[17])){
			$contact=new Contact($db);
	        $contact->name			= $line[17];
	        $contact->firstname		= (!empty($line[16]))? $line[16]: "";
	        $contact->address		= (!empty($line[19]))? $line[19]: "";
	        $contact->zip			= (!empty($line[21]))? $line[21]: "";
	        $contact->town			= (!empty($line[20]))? $line[20]: "";
	        $contact->country_id	= ($ATMdb->Get_field('rowid')) ? $ATMdb->Get_field('rowid') : 0;;
	        $contact->socid			= $societe->id;	// fk_soc
	        $contact->status		= 1;
	        $contact->email			= (!empty($line[26]))? $line[26]: "";
			$contact->phone_pro		= (!empty($line[23]))? $line[23]: "";
			$contact->fax			= (!empty($line[24]))? $line[24]: "";
	        $contact->priv			= 0;
			
			$contact->create($user);
		}
	}
	
	return $societe;
}

function _add_equipement($ATMdb,$TGlobal,$line,$produit){
	foreach($TGlobal['lot'] as $ref_lot=>$Tinfos_lot){
		if($Tinfos_lot['ref_produit'] == $line[0]){
			$equipement = new TAsset;
			$equipement->fk_product 			= $produit->id;
			$equipement->entity 				= 0;
			$equipement->lot_number 			= $ref_lot;
			$equipement->contenance_value 		= $Tinfos_lot['quantite'];
			$equipement->contenancereel_value 	= $Tinfos_lot['quantite'];
			$equipement->contenance_units 		= _unit($TGlobal['unite'][$line[8]]);
			$equipement->contenancereel_units 	= _unit($TGlobal['unite'][$line[8]]);
			
			echo $ref_lot." ".$Tinfos_lot['quantite']." "._unit($TGlobal['unite'][$line[8]])." ";
			
			foreach($TGlobal['flacon'] as $ref_flacon=>$flacon_ref_produit){
				if($flacon_ref_produit == $line[0]){
					$equipement->serial_number = strtoupper($ref_flacon);
					switch (strtoupper(substr($ref_flacon,0,1))) {
						case 'A':
							$equipement->tare = 10;
							$equipement->tare_unit = -3;
							break;
						case 'B':
							$equipement->tare = 5;
							$equipement->tare_unit = -3;
							break;
						case 'C':
							$equipement->tare = 1;
							$equipement->tare_unit = -3;
							break;
						case 'Y':
							$equipement->tare = 70;
							$equipement->tare_unit = -3;
							break;
					}
					echo "$ref_flacon<br>";
					break;
				}
			}
			/*echo '<pre>';
			print_r($produit);
			echo '</pre>'; exit;*/
			$equipement->save($ATMdb);
			break;
		}
	}
}

//Création de tableau intermédiaire
//Pour optimisation du traitement

/*
 * TAB UNITE
 */
$unite = fgetcsv($unitesfile,0,'|','"');
while($unite = fgetcsv($unitesfile,0,'|','"')){
	$TGlobal['unite'][$unite[0]] = $unite[1];
}

/*
 * TAB FLACON
 */
$flacon = fgetcsv($flaconsfile,0,'|','"');
while($flacon = fgetcsv($flaconsfile,0,'|','"')){
	$TGlobal['flacon'][$flacon[1]] = $flacon[8]; // TGlobal['flacon']['ref_flacon'] = id_produit;
}

/*
 * TAB LOTS
 */
$lot = fgetcsv($lotsfile,0,'|','"');
while($lot = fgetcsv($lotsfile,0,'|','"')){
	$TGlobal['lot'][$lot[2]] = array('ref_produit'=>$lot[1],'quantite'=>$lot[12]); // TGlobal['lot']['ref_lot'] = array(id_produit,quantite);
}

/*
 * TAB TYPE CLIENT 
 */
$type_cli = fgetcsv($typeclifile,0,'|','"');
while($type_cli = fgetcsv($typeclifile,0,'|','"')){
	if($TGlobal['type_cli'][$type_cli[2]] < $type_cli[1])
		$TGlobal['type_cli'][$type_cli[2]] = $type_cli[1];
}
 
/*
 * PRODUITS
 */
$line = fgetcsv($articlesfile,0,'|','"');
while($line = fgetcsv($articlesfile,0,'|','"')){
	if(empty($TGlobal['product'][$line[2]]) && !empty($line[2])) { // Création du produit la première fois que l'on a la référence
		echo "<hr>$i - $line[2] - $line[3]<br>";
		
		if($line[1] == 1 || $line[1] == 2 || $line[1] == 3 || $line[1] == 4 || $line[1] == 5 || $line[1] == 7 || $line[1] == 11 || $line[1] == 18)
			$tva_tx = "7";
		else
			$tva_tx = "19.6";
		
		$produit = new Product($db);
		$produit->ref 				= $line[2];
		$produit->libelle 			= $line[3];
		$produit->description 		= "";
		$produit->price_base_type 	= 'TTC';
		$produit->price_ttc 		= 0;
		$produit->tva_tx 			= $tva_tx;
		$produit->type				= 0;
		$produit->status 			= 1;
		$produit->status_buy 		= 1;
		$produit->finished 			= 1;
		$produit->ref_ext 			= $line[0];
		
		$produit->create($user);
		
		$string_unite = explode(" ", $line[35]);
		
		$ATMdb->Execute('UPDATE '.MAIN_DB_PREFIX.'product SET weight_units = '._unit($string_unite[1]));
		
		$produit->updatePrice($produit->id, $line[58], 'HT', $user);
		
		//Tarifs par conditionnement
		//Conditionnement 1
		if(!empty($line[36]) && $line[36] > 0)
			_add_condi($ATMdb,$line,$produit,35,58);
		
		//Conditionnement 2
		if(!empty($line[38]) && $line[38] > 0)
			_add_condi($ATMdb,$line,$produit,37,58,41);
		
		//Conditionnement 3
		if(!empty($line[40]) && $line[40] > 0)
			_add_condi($ATMdb,$line,$produit,39,58,42);
		
		/*
		 * Equitements (Flacons et lots)
		 */
		_add_equipement($ATMdb,$TGlobal,$line,$produit);
		
		$TGlobal['product'][$line[2]] = $produit->id;
	} else {
		continue;
	}
	
	$i++;
}
fclose($articlesfile);

/*
 * CLIENTS
 */
$line = fgetcsv($societesfile,0,'|','"');
while($line = fgetcsv($societesfile,0,'|','"')){
	if(empty($TGlobal['societe'][$line[5]]) && !empty($line[5])){
		if($TGlobal['type_cli'][$line[0]] == 1 || $TGlobal['type_cli'][$line[0]] == 3 || $TGlobal['type_cli'][$line[0]] == 4 ||$TGlobal['type_cli'][$line[0]] == 6)
			$type = "client";
		else 
			$type = "prospect";
		$societe = _add_tiers($ATMdb,$user, $db, $line,$type);
		$TGlobal['societe'][$line[5]] = $societe->id;
	}
	else {
		continue;
	}
}
fclose($societesfile);

 /*
 * FOURNISSEURS
 */
$line = fgetcsv($fournisseursfile,0,'|','"');
while($line = fgetcsv($fournisseursfile,0,'|','"')){
	if(empty($TGlobal['fournisseur'][$line[0]]) && !empty($line[0])){
		$type = "fournisseur";
		$societe = _add_tiers($ATMdb,$user, $db, $line,$type);
		$TGlobal['fournisseur'][$line[0]] = $societe->id;
	}
	else {
		continue;
	}
}
fclose($fournisseursfile);