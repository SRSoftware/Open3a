<?php
/*
 *  This file is part of open3A.

 *  open3A is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.

 *  open3A is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 *  2007 - 2015, Rainer Furtmeier - Rainer@Furtmeier.IT
 */
class Posten extends PersistentObject {
	public $parsers;
	public $recalcNetto = true;
	public $updateLagerOn = "R";
	public $updateLagerDoParent = true;
	public $messageBestand = "";
	public $useAutoMwSt = true;
	
	public static $recalcBeleg = true;
	
	public function getA(){
		return $this->A;
	}
	
	function __construct($id, $parsers = true){
		parent::__construct($id);
		//für Interface
		$this->parsers = $parsers;
		if($parsers){
			$this->setParser("menge","Util::CLNumberParserZ");
			$this->setParser("menge2","Util::CLNumberParserZ");
			$this->setParser("preis","Util::CLNumberParserZ");
			$this->setParser("EK1","Util::CLNumberParserZ");
			$this->setParser("EK2","Util::CLNumberParserZ");
		}
		/*$this->setParser("menge","mPosten::numberParser");
		$this->setParser("preis","mPosten::numberParser");
		$this->setParser("EK1","mPosten::numberParser");
		$this->setParser("EK2","mPosten::numberParser");
		$this->setParser("mwst","mPosten::numberParser");*/
		$this->customize();
	}

	public function calcPrices($fpdf = null){
		$A = $this->getA();
		$nettoPreis = $A->preis * 1;
		$mwstFaktor = 1 + $A->mwst / 100;
		
		$rabattFaktor = 1;
		if(isset($A->rabatt) AND $A->rabatt > 0)
			$rabattFaktor = (100 - $A->rabatt) / 100;
		
		$rabattBetrag = $nettoPreis - ($nettoPreis * $rabattFaktor);
		
		$nettoPreisOhneRabatt = $nettoPreis;
		
		$nettoPreis = $nettoPreis * $rabattFaktor;
		$menge2 = $A->menge2 != 0 ? $A->menge2 : 1;
		
		$bruttoPreis = $nettoPreis * $mwstFaktor;
		if($A->isBrutto == "1")
			$bruttoPreis = $A->bruttopreis * $rabattFaktor;
		
		
		$mwstBetrag = $bruttoPreis - $nettoPreis;
		
		$bruttoPreisGesamt = $A->menge * $menge2 * $bruttoPreis;
	
		if (stripos($A->name,'option') !== false){
			$A->PostenIsAlternative = 1;
		}	
		if($fpdf == null OR (isset($A->PostenIsAlternative) AND $A->PostenIsAlternative > 0))
			return array("nettoPreis" => $nettoPreis, "bruttoPreis" => $bruttoPreis, "nettoPreisOhneRabatt" => $nettoPreisOhneRabatt, "bruttoPreisGesamt" => $bruttoPreisGesamt, "mwstFaktor" => $mwstFaktor, "mwstBetrag" => $mwstBetrag, "rabattFaktor" => $rabattFaktor, "rabattBetrag" => $rabattBetrag);
	

		if(!isset($fpdf->gesamt_netto[$A->mwst]))
			$fpdf->gesamt_netto[$A->mwst] = 0;

		$fpdf->gesamt_netto[$A->mwst] += $A->menge * $menge2 * $nettoPreis;#$A->preis * $rabattFaktor;
		
		$artikelsteuer = 0;
		if(isset($A->steuer)){
			$artikelsteuer = $A->menge * $menge2 * $nettoPreis * ($A->steuer / 100);
			
			if(!isset($fpdf->artikelsteuern[$A->steuer]))
				$fpdf->artikelsteuern[$A->steuer] = 0;
			
			$fpdf->artikelsteuern[$A->steuer] += $artikelsteuer;
		}
		
		$fpdf->gesamt_brutto += $bruttoPreisGesamt;#$A->menge * $menge2 * $bruttoPreis;

		return array("nettoPreis" => $nettoPreis, "bruttoPreis" => $bruttoPreis, "nettoPreisOhneRabatt" => $nettoPreisOhneRabatt, "bruttoPreisGesamt" => $bruttoPreisGesamt, "mwstFaktor" => $mwstFaktor, "mwstBetrag" => $mwstBetrag, "rabattFaktor" => $rabattFaktor, "rabattBetrag" => $rabattBetrag);
	}
	
	public function newAttributes(GRLBM $GRLBM = null){
		$A = parent::newAttributes();

		if($this->customizer != null)
			$this->customizer->customizeNewAttributes($this->getClearClass(get_class($this)), $A);
			
		if($_SESSION["S"]->checkForPlugin("mBrutto"))
			$A->isBrutto = mUserdata::getUDValueS("DefaultValuePostenisBrutto", "1");

		#$AC = anyC::get("Kategorie", "type", "mwst");
		#$AC->addAssocV3("isDefault", "=", "1");
		#$M = $AC->getNextEntry();
		#if($M != null){
		#	$defaultMwst = Util::parseFloat("de_DE",str_replace("%","",$M->A("name")));
		#	$A->mwst = $this->hasParsers ? Util::CLNumberParserZ($defaultMwst) : $defaultMwst;
		#}
		
			
		if($GRLBM != null){
			$A->GRLBMID = $GRLBM->getID();
			
			$Auftrag = new Auftrag($GRLBM->A("AuftragID"));
			$Adresse = new Adresse($Auftrag->A("AdresseID"));
			$calc = self::calcMwst($Adresse, $Auftrag->A("UStIdNr"));
			
			$A->mwst = $this->hasParsers ? Util::CLNumberParserZ($calc) : $calc;
		} else
			$A->mwst = $this->hasParsers ? Util::CLNumberParserZ(self::calcMwst()) : self::calcMwst();

		return $A;
	}
	
	/*public function newFromProvision($ProvisionID,$GRLBMID){
		$this->A = $this->newAttributes();
		
		$prov = new Provision($ProvisionID);
		$prov->loadMe();
		$provA = $prov->getA();
		
		if($provA->GRLBMID != -1) {
			$R = new GRLBM($provA->GRLBMID);
			$R->loadMe();
		}
		$provA->provisionBetrag = str_replace(",",".",$provA->provisionBetrag);
		
		$this->A->GRLBMID = $GRLBMID;
		if($provA->GRLBMID != -1) $this->A->name = "Provision zu Rechnung ".$R->getA()->nummer;
		else $this->A->name = "Provision";
		$this->A->menge = 1;
		$this->A->bemerkung = "";
		$this->A->oldArtikelID = $ProvisionID;
		$skonto = 0;
		if($provA->GRLBMID != -1) $skonto = $provA->provisionBetrag * $R->getA()->payedWithSkonto / 100;
		$this->A->preis = number_format(round($provA->provisionBetrag - $skonto + 0.0000000000001,2),2,",","");
		$this->newMe();

		$provA->RechnungID = $GRLBMID;
		$prov->saveMe();
	}*/
	
	public $skipVariantTest = false;
	public $skipLieferantTest = false;
	public $skipStuecklisteTest = false;
	public $skipSeriennummernTest = false;
	public function newFromArtikel($ArtikelID, $GRLBMID, $menge = 1, $beschreibung = null, $kundennummer = null, $preis = null, $VarianteArtikelID = 0, $LieferantID = null){
		$GRLBM = new GRLBM($GRLBMID);
		$Auftrag = new Auftrag($GRLBM->A("AuftragID"));
		
		$this->A = $this->newAttributes();
		$a = new Artikel($ArtikelID);
		
		if(!$this->skipStuecklisteTest AND Session::isPluginLoaded("mStueckliste") AND ($GRLBM->getMyPrefix() == "O" OR $GRLBM->getMyPrefix() == "P") AND Stueckliste::has($ArtikelID))
			Red::redirect(OnEvent::popup("Stückliste", "mStueckliste", "-1", "stuecklisteSelectionPopup", array("'$ArtikelID'", "'$GRLBMID'")));
		
		$showOptions = false;
		if(!$this->skipVariantTest AND Session::isPluginLoaded("mVariante") AND Variante::has($ArtikelID) AND !defined("PHYNX_VIA_INTERFACE"))
			$showOptions = true;
			#Red::redirect(OnEvent::popup("Variante hinzufügen", "mVariante", "-1", "variantSelectionPopup", array("'$ArtikelID'", "'addToBeleg'", "'$GRLBMID'")));
		
		if(!$this->skipSeriennummernTest AND Session::isPluginLoaded("mSeriennummer") AND Seriennummer::has($ArtikelID) AND Seriennummer::check($GRLBM->getMyPrefix()) AND !defined("PHYNX_VIA_INTERFACE"))
			$showOptions = true;
			#Red::redirect(OnEvent::popup("Variante hinzufügen", "mVariante", "-1", "variantSelectionPopup", array("'$ArtikelID'", "'addToBeleg'", "'$GRLBMID'")));
		
		
		if(
			!$this->skipLieferantTest 
			AND !defined("PHYNX_VIA_INTERFACE")
			AND Session::isPluginLoaded("mLieferant")
			AND Lieferant::check($GRLBM->getMyPrefix())
			AND Lieferant::has($ArtikelID)){
			
			$AC = Lieferant::getLieferanten($ArtikelID);
			$AC->lCV3();
			if($AC->numLoaded() > 1)
				$showOptions = true;
				#Red::redirect(OnEvent::popup("Lieferant auswählen", "mLieferant", "-1", "lieferantSelectionPopup", array("'$ArtikelID'", "'$GRLBMID'")));
			
			if($AC->numLoaded() == 1){
				$L = $AC->n();
				if($L != null)
					$LieferantID = $L->A("LieferantID");
			}
		}
		
		$showOptions = Aspect::joinPoint("showPopup", $this, __METHOD__, array($ArtikelID), $showOptions);
		$keepName = false;
		
		if($showOptions)
			Red::redirect(OnEvent::popup("Postenoptionen", "Posten", "-1", "popupOptions", array("'$ArtikelID'", "'$GRLBMID'"), "", "{ignoreWidth: true}"));
		
		$this->recalcNetto = false;
		
		$aAttr = $a->getA();
		if($aAttr == null)
			return false;
		$aArray = PMReflector::getAttributesArray($aAttr);
		$pArray = PMReflector::getAttributesArray($this->A);

		$inters = array_intersect($aArray, $pArray);
		
		foreach($inters AS $key => $value)
			$this->A->$value = $aAttr->$value;
	
		if($aAttr->bildDateiName != "" AND file_exists($aAttr->bildDateiName))
			$this->A->bild = DBImageGUI::stringifyS("image/jpg", $aAttr->bildDateiName, 400, 400);
		
		$this->A->VarianteArtikelID = $VarianteArtikelID;
		$this->A->PostenLieferantID = $LieferantID === null ? 0 : $LieferantID;
		
		#$_SESSION["BPS"]->setActualClass("mArtikelGUI");
		#$bps = $_SESSION["BPS"]->getAllProperties();

		if($kundennummer == null AND $Auftrag->A("kundennummer"))
			$kundennummer = $Auftrag->A("kundennummer");

		if($GRLBM->getMyPrefix() != "O" AND $GRLBM->getMyPrefix() != "P"){
			$this->A->EK1 = $a->getGesamtEK1($LieferantID, true, $VarianteArtikelID);
			$this->A->preis = $a->getGesamtNettoVK(true, $LieferantID);
			$this->A->bruttopreis = $a->getGesamtBruttoVK(true, $LieferantID);
		} else {
			$this->A->EK1 = 0;
			$this->A->preis = $a->getGesamtEK1($Auftrag->A("lieferantennummer") != "0" ? $Auftrag->A("lieferantennummer")  : null, true, $VarianteArtikelID);
			
			if($Auftrag->A("lieferantennummer") != "0"){
				$AC = anyC::get("LieferantPreis", "LieferantPreisArtikelID", $a->getID());
				$AC->addAssocV3 ("LieferantPreisLieferantID", "=", $Auftrag->A("lieferantennummer"));
				$AC->addAssocV3("LieferantPreisVarianteArtikelID", "=", $VarianteArtikelID);
				$LP = $AC->n();
				
				if($LP != null){
					if($LP->A("LieferantPreisArtikelnummer") != "")
						$this->A->artikelnummer = $LP->A("LieferantPreisArtikelnummer");
					
					if($LP->A("LieferantPreisArtikelname") != ""){
						$this->A->name = $LP->A("LieferantPreisArtikelname");
						$keepName = true;
					}
				}
				
				if($LP === null AND $VarianteArtikelID != 0){
					$AC = anyC::get("LieferantPreis", "LieferantPreisArtikelID", $a->getID());
					$AC->addAssocV3 ("LieferantPreisLieferantID", "=", $Auftrag->A("lieferantennummer"));
					$AC->addAssocV3("LieferantPreisVarianteArtikelID", "=", 0);
					$LP = $AC->n();

					if($LP != null){
						if($LP->A("LieferantPreisArtikelnummer") != "")
							$this->A->artikelnummer = $LP->A("LieferantPreisArtikelnummer");

						if($LP->A("LieferantPreisArtikelname") != ""){
							$this->A->name = $LP->A("LieferantPreisArtikelname");
						#	$keepName = true;
						}
					}
				}
				
			}
		}
		
		if($kundennummer != null){
			if(Session::isPluginLoaded("mPreisgruppe")){
				$Kappendix = Kappendix::getKappendixToKundennummer($kundennummer);
				if($Kappendix->A("KappendixPreisgruppe") != "0"){
					$PGPrice = Preisgruppe::getPrice("Artikel", $ArtikelID, $Kappendix->A("KappendixPreisgruppe"));
					
					if($PGPrice !== false){
						$this->A->preis = $PGPrice;
						$this->recalcNetto = true;
					}
				}
			}
			
			try {
				$Ks = new Kundenpreise();
				$Ks->addAssocV3("t1.kundennummer", "=", $kundennummer);
				$Ks->addAssocV3("t1.ArtikelID", "=", $ArtikelID);
				$K = $Ks->getNextEntry();

				if($K != null) {
					$this->A->preis = $K->A("kundenPreis");
					$this->recalcNetto = true;
				}
			} catch(ClassNotFoundException $e){
				
			}
		}
		
		if($preis != null)
			$this->A->preis = $preis;
		
		$this->A->GRLBMID = $GRLBMID;
		$this->A->bemerkung = "";
		if($beschreibung != null)
			$this->A->beschreibung = $beschreibung;
		$this->A->menge = $menge;
		$this->A->oldArtikelID = $ArtikelID;
		
		if(Session::isPluginLoaded("mMultiLanguage") OR Session::isPluginLoaded("mStammdaten") OR isset($_SESSION["viaInterface"])){
			#$Auftrag = new Auftrag($GRLBM->A("AuftragID"));
			$Adresse = new Adresse($Auftrag->A("AdresseID"));
		}
		
		if(Session::isPluginLoaded("mMultiLanguage") OR isset($_SESSION["viaInterface"])){
					
			if($Adresse->A("AdresseSpracheID") != "0"){
				
				//LEGACY AS OF 20150408
				$ML = new mMultiLanguageGUI();
				$ML->addAssocV3("MultiLanguageClass","=","Artikel");
				$ML->addAssocV3("MultiLanguageClassID","=",$ArtikelID);
				
				$ML->addAssocV3("MultiLanguageSpracheID","=",$Adresse->A("AdresseSpracheID"));
				
				while($T = $ML->getNextEntry())
					$this->changeA($T->A("MultiLanguageClassField"),$T->A("MultiLanguageValue"));
				
				
				//NEW
				$S = new Sprache($Adresse->A("AdresseSpracheID"));
				
				$ML = new mMultiLanguageGUI();
				$ML->addAssocV3("MultiLanguageClass","=","Artikel");
				$ML->addAssocV3("MultiLanguageClassID", "=", $ArtikelID);
				
				$ML->addAssocV3("MultiLanguageSpracheID", "=", "0");
				$ML->addAssocV3("MultiLanguageSprache", "=", $S->A("SpracheSprache"));
				
				while($T = $ML->getNextEntry())
					$this->changeA($T->A("MultiLanguageClassField"), $T->A("MultiLanguageValue"));
			}
		}

		if(Session::isPluginLoaded("mStammdaten") AND !Session::isPluginLoaded("mMwSt")){
			#$ISO3166_2 = ISO3166::getCodeToCountry($Adresse->A("land"));
			$S = Stammdaten::getActiveStammdaten();
			if($Adresse->A("land") != ISO3166::getCountryToCode($S->A("land")) AND $Adresse->A("land") != ""){
				if($this->A("isBrutto") == "1"){
					$this->A->preis = $this->A->bruttopreis / (1 + $this->A->mwst / 100);
					$this->A->isBrutto = 0;
				}
				
				if($this->useAutoMwSt){
					$this->A->mwst = 0;
					if($Auftrag->A("UStIdNr") == "")
						$this->A->mwstCheck = 1;
				}
			}
		}
		
		
		if(Session::isPluginLoaded("mStammdaten") AND Session::isPluginLoaded("mMwSt")){
			$this->A->mwst = 0;
			
			$MwSt = MwSt::find($a, $Adresse, $Auftrag);
			if($MwSt != null){
				$this->A->mwst = $MwSt->A("MwStValue");
				$this->A->erloeskonto = $MwSt->A("MwStErloeskonto");
				
				if($this->A->isBrutto){
					$this->A->preis = $this->A->bruttopreis;
					if($this->hasParsers)
						$this->A->preis = Util::CLNumberParserZ($this->A->bruttopreis);
					
					$this->recalcNetto = true;
				}
			}
		}
		
		
		if($this->A->differenzbesteuert == "1"){
			$this->A->differenzbesteuertMwSt = $this->A->mwst;
			$this->A->mwst = 0;
		}
		
		Aspect::joinPoint("newPosten", $this, __METHOD__, array($GRLBM, $Auftrag, $a));
		
		$id = $this->newMe();
		
		if($VarianteArtikelID != 0){# AND $GRLBM->getMyPrefix() != "O" AND $GRLBM->getMyPrefix() != "P"){
			$keepPrice = false;
			if($GRLBM->getMyPrefix() == "O" OR $GRLBM->getMyPrefix() == "P")
				$keepPrice = true;
			
			$V = new VarianteArtikel($VarianteArtikelID);
			$V->fixPosten($ArtikelID, $id, $keepPrice, $keepName);
		}
		
		$P = new Posten($id);
		if(Session::isPluginLoaded("mDArtikel")){
			try {
				DArtikel::execute($ArtikelID,  $P);
			} catch (ClassNotFoundException $e){
				
			}
		}
		
		return $id;
	}
	
	public static function calcMwst(Adresse $Adresse = null, $UStIdNr = null){
		$mwst = 0;
		
		$AC = anyC::get("Kategorie", "type", "mwst");
		$AC->addAssocV3("isDefault", "=", "1");
		$M = $AC->getNextEntry();
		if($M != null)
			$mwst = Util::parseFloat("de_DE",str_replace("%", "", $M->A("name")));
		
		if($Adresse == null)
			return $mwst;
		
		if(Session::isPluginLoaded("mStammdaten") AND !Session::isPluginLoaded("mMwSt")){
			$S = Stammdaten::getActiveStammdaten();
			if($Adresse->A("land") != ISO3166::getCountryToCode($S->A("land")) AND $Adresse->A("land") != "")
				$mwst = 0;
		}
		
		return $mwst;
	}
	
	protected function saveMultiEditField($field, $value){
		if($field != "preis")
			$this->recalcNetto = false;
		
		if($field == "menge" AND $this->A("oldArtikelID") != "0" AND Session::isPluginLoaded("mStaffelpreis") AND $this->A("rabatt") != null){
			$SP = Staffelpreis::get("Artikel", $this->A("oldArtikelID"), Util::CLNumberParserZ($value, "store"));
			$this->changeA("rabatt", $this->hasParsers ? Util::CLNumberParserZ($SP) : $SP);
		}
		
		if($this->A == null) $this->loadMe();
		$this->A->$field = $value;
		$this->saveMe();
	}

	private function createArtikel(){
		$a = new Artikel(-1);

		$aAttr = $a->newAttributes();
		$aArray = PMReflector::getAttributesArray($aAttr);
		$pArray = PMReflector::getAttributesArray($this->A);

		$inters = array_intersect($aArray, $pArray);

		foreach($inters AS $key => $value)
			$aAttr->$value = $this->A->$value;

		$a->setA($aAttr);
		return $a->newMe();
	}

	public function newMe($checkuserdata = true, $output = false){
		if($this->A->createArtikel == "1"){
			$this->A->oldArtikelID = $this->createArtikel();
			$this->A->createArtikel = "0";
		}
		
		$preis = $this->A->preis;
		$mwst = $this->A->mwst;
		if($this->parsers){
			$preis = Util::CLNumberParserZ($this->A->preis,"store");
			
			if($this instanceof PostenGUI)
				$mwst = Util::CLNumberParserZ($this->A->mwst,"store"); //only if via PostenGUI
		}
		
		if($this->A->isBrutto == "0")
			$this->A->bruttopreis = $preis * ((100 + $mwst) / 100);
		
		if($this->A->isBrutto == "1" AND $this->recalcNetto){
			$this->A->bruttopreis = $preis;
			if($this->hasParsers)
				$this->setParser("preis","Util::nothingParser");
			$this->A->preis = $this->A->bruttopreis / (1 + $mwst / 100);
		}

		$G = new GRLBM($this->A->GRLBMID);
		$G->loadMe();
		
		if($G->getA()->isPayed == "1" AND ($G->A("isR") == "1" OR $G->A("isB") == "1" OR $G->A("isL") == "1"))
			die("alert:AuftraegeMessages.A004");

		$newid = parent::newMe($checkuserdata, false);
		
		$P = new Posten($newid, false);
		
		if(!$this->A("keinLagerbestand") AND !Session::isPluginLoaded("mLagerbestandWare") AND Session::isPluginLoaded("mLager") AND $this->A("oldArtikelID") > 0){# AND $G->A("is".$this->updateLagerOn) == "1")
			if(!Session::isPluginLoaded("mStueckliste") OR (Session::isPluginLoaded("mStueckliste") AND !Stueckliste::has($this->A("oldArtikelID"))))
				$this->messageBestand = Lagerbestand::updateMain(
					$this->A("oldArtikelID"),
					$P->A("menge") * -1,
					"Beleg ".$G->A("prefix").$G->A("nummer"),
					$G->getMyPrefix(),
					$this->updateLagerOn,
					$this->A("VarianteArtikelID"));
			
			if(Session::isPluginLoaded("mStueckliste") AND Stueckliste::has($this->A("oldArtikelID")))
				$this->messageBestand = Stueckliste::updateLagerMain(
					$this->A("oldArtikelID"), 
					$P->A("menge") * -1,
					"Beleg ".$G->A("prefix").$G->A("nummer"),
					$G->getMyPrefix(),
					$this->updateLagerOn,
					$this->A("VarianteArtikelID"),
					$this->updateLagerDoParent);
			
			Aspect::joinPoint("alterLager", $this, __METHOD__, array($P, $G));
		}
		
		if(self::$recalcBeleg){
			$G = new GRLBM($this->A("GRLBMID"));
			$G->getSumOfPosten(false, true);
		}
		
		return $newid;
	}
	
	public function saveMe($checkuserdata = true, $output = false){
		if($this->A->createArtikel == "1")
			$this->A->oldArtikelID = $this->createArtikel();

		$this->A->mwstCheck = 0;
		
		
		$preis = $this->A->preis;
		$mwst = $this->A->mwst;
		if($this->parsers){
			$preis = Util::CLNumberParserZ($this->A->preis, "store");
			$mwst = Util::CLNumberParserZ($this->A->mwst, "store");
		}
		
		if($this->recalcNetto) //required when set via saveMultiEditField() because the preis field is loaded from DB and not set from interface
			$this->A->bruttopreis = $preis * ((100 + $mwst) / 100);

		if($this->A->isBrutto == "1" AND $this->recalcNetto){
			$this->A->bruttopreis = $preis;
			if($this->hasParsers)
				$this->setParser("preis","Util::nothingParser");
			$this->A->preis = $this->A->bruttopreis / (1 + $mwst / 100);
		}
		
		$G = new GRLBM($this->A->GRLBMID);
		#$G->loadMe();
		
		if($G->A("isPayed") == "1" AND ($G->A("isR") == "1" OR $G->A("isB") == "1" OR $G->A("isL") == "1"))
			die("alert:AuftraegeMessages.A005");

		
		if(!$this->A("keinLagerbestand") AND !Session::isPluginLoaded("mLagerbestandWare") AND Session::isPluginLoaded("mLager") AND $this->A("oldArtikelID") > 0){# AND $G->A("is".$this->updateLagerOn) == "1"){
			$PostenAlt = new Posten($this->getID(), false);
			$menge = (($this->hasParsers ? Util::CLNumberParserZ($this->A("menge"), "store") : $this->A("menge")) - $PostenAlt->A("menge")) * -1;
			if($menge != 0){
				if(!Session::isPluginLoaded("mStueckliste") OR (Session::isPluginLoaded("mStueckliste") AND !Stueckliste::has($this->A("oldArtikelID"))))
					$this->messageBestand = Lagerbestand::updateMain(
						$this->A("oldArtikelID"), 
						$menge,
						"Beleg ".$G->A("prefix").$G->A("nummer"),
						$G->getMyPrefix(),
						$this->updateLagerOn,
						$this->A("VarianteArtikelID"));

				if(Session::isPluginLoaded("mStueckliste") AND Stueckliste::has($this->A("oldArtikelID")))
					$this->messageBestand = Stueckliste::updateLagerMain(
						$this->A("oldArtikelID"), 
						$menge,
						"Beleg ".$G->A("prefix").$G->A("nummer"),
						$G->getMyPrefix(),
						$this->updateLagerOn,
						$this->A("VarianteArtikelID"),
						$this->updateLagerDoParent);
			}
			
			Aspect::joinPoint("alterLager", $this, __METHOD__, array($PostenAlt, $G));
		}
		
		parent::saveMe($checkuserdata, false);
		
		if(self::$recalcBeleg){
			$G = new GRLBM($this->A("GRLBMID"));
			$G->getSumOfPosten(false, true);
		}
		#echo $message;
		if($output)
			Red::messageSaved();
	}
	
	public function deleteMe(){
		if($this->A == null) $this->loadMe();
		
		$G = new GRLBM($this->A->GRLBMID);
		$G->loadMe();
		
		if($G->getA()->isPayed == "1" AND ($G->A("isR") == "1" OR $G->A("isB") == "1" OR $G->A("isL") == "1"))
			die("alert:AuftraegeMessages.A003");
		
		$P = new Posten($this->getID(), false);
		
		if(!$this->A("keinLagerbestand") AND !Session::isPluginLoaded("mLagerbestandWare") AND Session::isPluginLoaded("mLager") AND $this->A("oldArtikelID") > 0){# AND $G->A("is".$this->updateLagerOn) == "1"){
			if(!Session::isPluginLoaded("mStueckliste") OR (Session::isPluginLoaded("mStueckliste") AND !Stueckliste::has($this->A("oldArtikelID"))))
				$this->messageBestand = Lagerbestand::updateMain(
					$this->A("oldArtikelID"),
					$P->A("menge"),
					"Beleg ".$G->A("prefix").$G->A("nummer"),
					$G->getMyPrefix(),
					$this->updateLagerOn,
					$this->A("VarianteArtikelID"));
			
			if(Session::isPluginLoaded("mStueckliste") AND Stueckliste::has($this->A("oldArtikelID")))
				$this->messageBestand = Stueckliste::updateLagerMain(
					$this->A("oldArtikelID"), 
					$P->A("menge"),
					"Beleg ".$G->A("prefix").$G->A("nummer"),
					$G->getMyPrefix(),
					$this->updateLagerOn,
					$this->A("VarianteArtikelID"),
					$this->updateLagerDoParent);
			
			if($this->A("PostenUsedSerials") != "[]" AND $this->A("PostenUsedSerials") != ""){
				$serials = json_decode($this->A("PostenUsedSerials"));
				#$nummern = implode("\n", $serials);
				mSeriennummerGUI::doPutIn($serials, "Artikel", $this->A("oldArtikelID"));
			}
			
			Aspect::joinPoint("alterLager", $this, __METHOD__, array($P, $G));
		}
		
		
		parent::deleteMe();
		
		if(self::$recalcBeleg){
			$G = new GRLBM($this->A("GRLBMID"));
			$G->getSumOfPosten(false, true);
		}
	}
	
	private static $kundenpreise = array();
	public function cloneMe($updatePrices = false, $kundennummer = 0){
		$this->setParser("menge","Util::nothingParser");
		$this->setParser("preis","Util::nothingParser");
		#$this->setParser("bruttopreis","Util::nothingParser");
		$this->setParser("EK1","Util::nothingParser");
		$this->setParser("EK2","Util::nothingParser");
		
		#$this->forceReload();
		
		$_SESSION["BPS"]->setActualClass("clone".get_class($this));
		$bps = $_SESSION["BPS"]->getAllProperties();
		foreach($bps AS $key => $value)
			if(isset($this->A->$key))
				$this->A->$key = $value;
		
		$this->recalcNetto = false;
		
		if($updatePrices AND $this->A("oldArtikelID") != "0"){
			$A = new Artikel($this->A("oldArtikelID"));
			$this->changeA("preis", !$this->A("isBrutto") ? $A->getGesamtNettoVK() : $A->getGesamtBruttoVK());
			$this->recalcNetto = true;
			
			try {
				if(Session::isPluginLoaded("Kundenpreise") AND $kundennummer > 0){
					if(!isset(self::$kundenpreise[$kundennummer])){
						$Ks = anyC::get("Kundenpreis");
						$Ks->addAssocV3("kundennummer", "=", $kundennummer);
						$Ks->lCV3();
						self::$kundenpreise[$kundennummer] = $Ks;
					}
					
					
					while($K = self::$kundenpreise[$kundennummer]->n()){
						if($K->A("ArtikelID") != $this->A("oldArtikelID"))
							continue;
						
						$this->changeA("preis", $K->A("kundenPreis"));
						$this->recalcNetto = true;
					}
					
					self::$kundenpreise[$kundennummer]->resetPointer();

				}
			} catch(ClassNotFoundException $e){	}
		}
			
		return $this->newMe();
	}
}
?>
