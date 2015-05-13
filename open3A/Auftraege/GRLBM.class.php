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
class GRLBM extends PersistentObject implements iCloneable, iRepeatable, iDeletable2 {

	public $AdresseObj = null;
	public $isCopy = false;
	protected $letterType = "";
	public $CustomizerTeilzahlungen = false;
	public $CustomizerPostenAddLabelInsertOrigin = true;
	protected $newPostenMessageBestand = "";
	// <editor-fold defaultstate="collapsed" desc="getOnDeleteEvent">
	public function getOnDeleteEvent(){
		return "function() { contentManager.reloadFrameRight(); contentManager.reloadFrameLeft(); }";
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getOnDeleteQuestion">
	public function getOnDeleteQuestion(){
		return "Soll dieser Beleg und alle Positionen wirklich gelöscht werden?";
	}
	// </editor-fold>
	
	function __construct($ID, $parsers = true){
		$this->myAdapterClass = "GRLBMAdapter";
		parent::__construct($ID);
		
		if($parsers){
			$this->setParser("datum","Util::CLDateParser");
			$this->setParser("lieferDatum","Util::CLDateParserE");
			$this->setParser("rabatt","Util::CLNumberParserZ");
			$this->setParser("rabattInW","Util::CLNumberParserZ");
			$this->setParser("leasingrate","Util::CLNumberParserZ");
			$this->setParser("payedWithSkonto","Util::CLNumberParserZ");
			$this->setParser("GRLBMpayedDate","Util::CLDateParserE");
			#$this->setParser("versandkosten", "Util::CLNumberParserZ");
			#$this->setParser("versandkostenMwSt", "Util::CLNumberParserZ");
		}
		
		$this->customize();
	}

	// <editor-fold defaultstate="collapsed" desc="makeAB">
	public function makeAB(){
		$this->changeA("isB", "1");
		$this->changeA("isA", "0");
		$this->changeA("isEMailedTime", "0");
		$this->changeA("isEMailed", "0");
		#$this->changeA("isPayed", "1");
		$this->changeA("prefix", Stammdaten::getActiveStammdaten()->getPrefix("B"));

		$T = new Textbausteine();
		$TBO = $T->getTBs("oben", "B", true);
		
		$T = new Textbausteine();
		$TBU = $T->getTBs("unten", "B", true);
		
		$T = new Textbausteine();
		$TBZ = $T->getTBs("zahlungsbedingungen", "B", true);
		
		$this->changeA("textbausteinObenID", $TBO[0][0]);
		$this->changeA("textbausteinUntenID", isset($TBU[0][0]) ? $TBU[0][0] : 0);
		$this->changeA("zahlungsbedingungenID", isset($TBZ[0][0]) ? $TBZ[0][0] : 0);
		
		$this->changeA("datum", Util::CLDateParser(time()));
		#$this->changeA("lieferDatum", Util::CLDateParser(time()));

		$this->saveMe(true, false);

		$Auftrag = new Auftrag($this->A("AuftragID"));
		$Auftrag->updateStatus("B", true);
		
		// <editor-fold defaultstate="collapsed" desc="Aspect:jP">
		return Aspect::joinPoint("after", $this, __METHOD__, true);
		// </editor-fold>
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getRepeatableActions">
	public function getRepeatableActions(){
		$t = array("cloneForRepeatable" => "kopieren", "cloneAndMail" => "kopieren und per E-Mail an Kunde verschicken");
		#if($_SESSION["S"]->checkForPlugin("mDrucker")) {
		#	$t["cloneAndPrintOriginal"] = "kopieren und Original drucken";
		#	$t["cloneAndPrintWithCopy"] = "kopieren und Original und Kopie drucken";
		#}

		if(Session::isPluginLoaded("SP"))
			$t["cloneAndMailSigned"] = "kopieren und über signaturportal an Kunde verschicken";

		if(Session::isPluginLoaded("PL"))
			$t["cloneAndMailSigned"] = "kopieren und über PixelLetter an Kunde verschicken";
		
		return $t;
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getSumOfPosten">
	public function getSumOfPosten($returnArray = false, $saveValues = false, $calcUpToPostenID = null){
		$this->loadMe();
		$aC = new mPosten();
		$aC->addAssocV3("GRLBMID", "=", $this->getID());
		
		#$aC = anyC::get("Posten", "GRLBMID", $this->getID());
		
		$gesamt_netto_array = array();
		$artikelsteuern = array();
		#$ges_netto = 0;
		$ges_brutto = 0;
		$ges_ek1 = 0;
		$mwst = array();
		$mwstSums = array();
		while($t = $aC->getNextEntry()){
			if($t->A("PostenIsAlternative") !== null AND $t->A("PostenIsAlternative") > 0)
				continue;
			
			
			$rabatt = 1;
			if(isset($t->getA()->rabatt))
				$rabatt = (100 - $t->getA()->rabatt) / 100;
			
			
			$menge2 = ($t->A("menge2") != 0 ? $t->A("menge2") : 1);
			$nettopreis = $t->A("menge") * $menge2 * $t->A("preis") * $rabatt;
			
			
			if(!isset($gesamt_netto_array[$t->A("mwst")]))
				$gesamt_netto_array[$t->A("mwst")] = 0;

			$gesamt_netto_array[$t->A("mwst")] += $nettopreis;
			
			if(isset($t->getA()->steuer)){
				if(!isset($artikelsteuern[$t->A("steuer")]))
					$artikelsteuern[$t->A("steuer")] = 0;

				$artikelsteuern[$t->A("steuer")] += $nettopreis * ($t->A("steuer") / 100);
			}
			
			
			$posten_brutto = ($t->A("isBrutto") == "1" ? $t->A("bruttopreis") * $t->A("menge") * $menge2 * $rabatt : $t->A("preis") * $t->A("menge") * $menge2 * (100 + $t->A("mwst")) / 100 * $rabatt) ;

			$ges_brutto += $posten_brutto;

			$ges_ek1 += $t->A("menge") * $menge2 * $t->A("EK1");

			if(array_search($t->A("mwst"), $mwst) === false){
				$mwst[] = $t->A("mwst");
				$mwstSums[$t->A("mwst")] = 0;
			}
			$mwstSums[$t->A("mwst")] += $posten_brutto;
			
			if($calcUpToPostenID != null AND $calcUpToPostenID == $t->getID())
				break;
		}

		#if($aC->numLoaded() == 0 AND !$returnArray) return null;

		/**
		 * VERSANDKOSTEN
		 */
		$Versand = $this->A("versandkosten") * 1;
		if($Versand != 0 AND $calcUpToPostenID == null){

			$posten_brutto = $Versand * ((100 + $this->A("versandkostenMwSt")) / 100);

			$ges_brutto += $posten_brutto;

			$ges_ek1 += $Versand;

			if(!isset($gesamt_netto_array[$this->A("versandkostenMwSt")]))
				$gesamt_netto_array[$this->A("versandkostenMwSt")] = 0;
			
			$gesamt_netto_array[$this->A("versandkostenMwSt")] += $Versand;

			if(array_search($this->A("versandkostenMwSt"), $mwst) === false)
				$mwst[] = $this->A("versandkostenMwSt");

			$mwstSums[$this->A("versandkostenMwSt")] += $posten_brutto;
		}

		
		foreach($mwstSums AS $key => $value)
			$mwstSums[$key] = Util::kRound($value);
		
		$steuern = array();
		$ges_mwst = 0;
		foreach($gesamt_netto_array AS $key => $value){
			$ges_mwst += Util::kRound($value * ($key / 100));
			$steuern[$key] = $mwstSums[$key] - $value;
		}
		
		foreach($artikelsteuern AS $key => $value)
			$artikelsteuern[$key] = Util::kRound ($value, 2);
			
		$ges_brutto = Util::kRound($ges_brutto);
		$ges_mwst = Util::kRound($ges_mwst);

		if(array_sum($mwstSums) != $ges_brutto){ //fix round errors
			$diff = array_sum($mwstSums) - $ges_brutto;
			foreach($mwstSums AS $m => $v){ //just to find the first entry!
				$mwstSums[$m] = $v - $diff;
				break;
			}
		}

		if($this->A("isAbschlussrechnung") == "1" AND $calcUpToPostenID == null){
			$oC = new anyC();
			$oC->setCollectionOf("GRLBM");
			$oC->addAssocV3("AuftragID","=",$this->A->AuftragID);
			$oC->addAssocV3("GRLBMID", "!=" , $this->ID);
			$oC->addAssocV3("isR", "=" , "1");

			while($r = $oC->getNextEntry()){
				$ges_brutto -= $r->A("bruttobetrag");
				$ges_mwst -= $r->A("steuern");
			}
		}
		
		$ges_brutto = Aspect::joinPoint("alterGesBrutto", $this, __METHOD__, array($ges_brutto), $ges_brutto);
		
		$ges_netto = $ges_brutto - $ges_mwst;

		$ges_brutto += array_sum($artikelsteuern);
		
		if($saveValues) {
			$this->changeA("nettobetrag", $ges_netto);
			$this->changeA("bruttobetrag", $ges_brutto);
			$this->changeA("steuern", $ges_mwst);
			$this->changeA("ek1betrag", $ges_ek1);
			$this->saveMe();
		}

		if(!$returnArray) return $ges_brutto;
		else return array($ges_netto, $ges_mwst, $ges_brutto, $mwst, $ges_ek1, $mwstSums, $gesamt_netto_array, $steuern);
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="cloneForRepeatable">
	public function cloneForRepeatable(Repeatable $R){
		$this->loadMe();

		$_SESSION["BPS"]->registerClass("cloneGRLBM");
		$_SESSION["BPS"]->setACProperty("datum", date("d.m.Y", $R->getNextDate()));
		$_SESSION["BPS"]->setACProperty("lieferDatum", date("d.m.Y", $R->getNextDate()));
		if($R->A("RepeatableInterval") == "1Month")
			$_SESSION["BPS"]->setACProperty("GRLBMCustomField3", date("m/Y", $R->getNextDate()));
		if($R->A("RepeatableInterval") == "1Year")
			$_SESSION["BPS"]->setACProperty("GRLBMCustomField3", date("Y", $R->getNextDate()));

		return $this->cloneMe(true);
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="printMe">
	private function printMe($copy){
		if($this->A == null) $this->loadMe();
		
		$Auftrag = new AuftragGUI($this->A->AuftragID);
		$Auftrag->loadMe();
		#$_SESSION["BPS"]->registerClass("GRLBMGUI");
		#$_SESSION["BPS"]->setACProperty("myID", $this->ID);
		
		$_SESSION["BPS"]->registerClass("mGRLBMGUI");
		$_SESSION["BPS"]->setACProperty("type", $this->getMyPrefix());
		
		$Auftrag->printLetter($this->ID, $copy ? "true" : "false");
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="cloneAndPrintOriginal">
	public function cloneAndPrintOriginal(Repeatable $R){
		$newID = $this->cloneForRepeatable($R);

		$G = new GRLBM($newID);
		$G->printMe(false);
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="cloneAndMail">
	public function cloneAndMail(Repeatable $R){
		$newID = $this->cloneForRepeatable($R);

		#$_SESSION["BPS"]->setProperty("Brief","GRLBMID",$newID);

		$G = new GRLBM($newID);

		$A = new AuftragGUI($G->A("AuftragID"));
		try {
			$A->sendViaEmail($newID, "", "", "", false);
		} catch(Exception $e){
			return "ERROR: ".$e->getMessage()."\n";
		}
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="cloneAndMailSigned">
	public function cloneAndMailSigned(Repeatable $R){
		$newID = $this->cloneForRepeatable($R);

		$_SESSION["BPS"]->setProperty("Brief","GRLBMID",$newID);

		$G = new GRLBM($newID);

		$A = new AuftragGUI($G->A("AuftragID"));
		try {
			$A->sendGRLBMToCustomer($newID, "sign", false);
		} catch(Exception $e){
			return "ERROR: ".$e->getMessage()."\n";
		}
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="cloneAndPrintWithCopy">
	public function cloneAndPrintWithCopy(Repeatable $R){
		$newID = $this->cloneForRepeatable($R);
		
		$G = new GRLBM($newID);
		$G->printMe(false);
		$G->printMe(true);
	}
	//  </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="cloneMe">
	function cloneMe($quiet = false, $AuftragID = null, $date = null){
		if($this->A == null) $this->loadMe();
		
		$_SESSION["BPS"]->setActualClass("clone".get_class($this));
		$bps = $_SESSION["BPS"]->getAllProperties();
		if(count($bps) > 0 AND $bps != -1)
			foreach($bps AS $key => $value)
				if(isset($this->A->$key))
					$this->A->$key = $value;
		
		
		$this->A->nummer = mStammdaten::getNextNumberFor($this->getMyPrefix());
		if(!isset($bps["datum"]))
			$this->A->datum = Util::CLDateParser(time());#date("d.m.Y");
		
		if($date)
			$this->A->datum = Util::CLDateParser($date);
		
		if(!isset($bps["lieferDatum"]))
			$this->A->lieferDatum = Util::CLDateParser(time());#date("d.m.Y");
		
		$this->A->isPrinted = 0;
		$this->A->isPayed = 0;
		$this->A->isEMailed = 0;
		if($AuftragID !== null)
			$this->A->AuftragID = $AuftragID;
		
		if($this->A("isB") == "1"){
			$this->A->isB = 0;
			$this->A->isA = 1;
		}
		$oldGRLBMID = $this->ID;


		$Auftrag = new Auftrag($this->A->AuftragID);
		$Auftrag->updateDatum($this->hasParsers ? Util::CLDateParser($this->A("datum"), "store") : $this->A("datum"));

		if(isset($this->A->zahlungsziel) AND $Auftrag->A("kundennummer") > 0){
			$Kappendix = Kappendix::getKappendixToKundennummer($Auftrag->A("kundennummer"));
			$this->A->zahlungsziel = time() + $Kappendix->A("KappendixZahlungsziel") * 3600 * 24;
			if($this->hasParsers)
				$this->A->zahlungsziel = Util::CLDateParser ($this->A->zahlungsziel);
		}

		$newGRLBMID = $this->newMe();
		
		$_SESSION["BPS"]->registerClass("clonePosten");
		$_SESSION["BPS"]->setACProperty("GRLBMID",$newGRLBMID);
		
		Posten::$recalcBeleg = false;
		$mP = new mPosten();
		$mP->cloneAllToGRLBM($oldGRLBMID);
		if(!$quiet)
			echo $this->A->AuftragID;
		Posten::$recalcBeleg = true;
		
		return $newGRLBMID;
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="deleteMe">
	function deleteMe(){
		$mP = new anyC();
		$mP->setCollectionOf("Posten");
		$mP->addAssocV3("GRLBMID","=",$this->ID);
		#$mP->lCV3();
		
		/*if($mP->numLoaded() > 0)
			die("alert:AuftraegeMessages.A001");*/
		
		Posten::$recalcBeleg = false;
		while($t = $mP->getNextEntry())
			$t->deleteMe();
		Posten::$recalcBeleg = true;
		
		parent::deleteMe();
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getA">
	function getA(){
		if($this->A == null) $this->loadMe();
		return $this->A;
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="updateTBs">
	function updateTBs($o, $u, $zb){
		$this->A->textbausteinOben = $o;
		$this->A->textbausteinUnten = $u;
		$this->A->zahlungsbedingungen = $zb;
		$this->saveMe();
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getProvisionCopy">
	protected function getProvisionCopy($ProvisionID){
		$p = new Posten(-1);
		$p->newFromProvision($ProvisionID,$this->ID);
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getPostenCopy">
	public function getPostenCopy($ArtikelID, $menge = 1, $beschreibung = null, $kundennummer = null, $preis = null){
		if($this->A("isPayed") == "1")
			die("alert:AuftraegeMessages.A004");
			
		#$_SESSION["messages"]->addMessage("creating Posten for Auftrag ".$this->ID." of Artikel $ArtikelID");
		$p = new Posten(-1);
		$id = $p->newFromArtikel($ArtikelID, $this->ID, $menge, $beschreibung, $kundennummer, $preis);
		$this->newPostenMessageBestand = $p->messageBestand;
		return $id;
	}
	// </editor-fold>

	public static $copySortOrder = 0;
	public $copyPostenFromPostenIDs = array();
	// <editor-fold defaultstate="collapsed" desc="copyPostenFrom">
	public function copyPostenFrom($fromId){#, $addToSort = 0){
		if($this->A("isPayed") == "1")
			die("error:AuftraegeMessages.A004");
		
		$this->copyPostenFromPostenIDs = array();
		
		$GRLBM = new GRLBM($fromId);
		if($GRLBM->A("AuftragID") == $this->A("AuftragID")){
			if($GRLBM->getMyPrefix() == "B" AND $this->getMyPrefix() != "B")
				if($this->getMyPrefix() == "L")
					$this->changeA("datum", $GRLBM->A("lieferDatum"));
				else
					$this->changeA("lieferDatum", $GRLBM->A("lieferDatum"));

			if($GRLBM->getMyPrefix() == "L" AND $this->getMyPrefix() == "R")
				$this->changeA("lieferDatum", $GRLBM->A("datum"));

			if($GRLBM->getMyPrefix() == "A" AND $this->getMyPrefix() == "B")
				$this->changeA("lieferDatum", $GRLBM->A("lieferDatum"));
		}
		
		$P = new Posten(-1);
		$P->loadMeOrEmpty();
		
		$ps = new anyC();
		$ps->setCollectionOf("Posten");
		$ps->addAssocV3("GRLBMID", "=", $fromId);
		if($P->A("PostenSortOrder") !== null)
			$ps->addOrderV3("PostenSortOrder");
		
		$ps->addOrderV3("PostenID");
		$i = 0;
		while(($t = $ps->n())){
			$A = $t->getA();
			$A->GRLBMID = $this->getID();
			if(isset($A->PostenSortOrder))
				$A->PostenSortOrder = self::$copySortOrder++;#+= $addToSort;
			
			if($i == 0 AND isset($A->PostenAddLine) AND $this->getMyPrefix() != "A" AND $this->CustomizerPostenAddLabelInsertOrigin){
				$oldGRLBM = new GRLBM($fromId);
				if($oldGRLBM->A("AuftragID") == $this->A("AuftragID"))
					$A->PostenAddLine = "Aus ".Stammdaten::getLongType($oldGRLBM->getMyPrefix())." ".$oldGRLBM->A("nummer")." vom ".$oldGRLBM->A("datum");
			}
			
			if($this->getMyPrefix() == "O" AND $t->A("oldArtikelID") != "0"){
				$nP = new Posten(-1, false);
				$this->copyPostenFromPostenIDs[] = $nP->newFromArtikel($t->A("oldArtikelID"), $this->getID(), $A->menge);
			} else {
				$nP = new Posten(-1, false);
				$nP->recalcNetto = false;
				$nP->setA($A);
				
				$temp = Posten::$recalcBeleg;
				Posten::$recalcBeleg = false;
				$this->copyPostenFromPostenIDs[] = $newID = $nP->newMe();
				
				if(Session::isPluginLoaded("mPostenKalkulation")){
					$AC = anyC::get("PostenKalkulation", "PostenKalkulationPostenID", $t->getID());
					while($PK = $AC->n()){
						$PK->changeA("PostenKalkulationPostenID", $newID);
						$PK->newMe();
					}
				}
				
				Posten::$recalcBeleg = $temp;
			}
			
			$i++;
		}
		
		
		if($GRLBM->A("AuftragID") == $this->A("AuftragID"))
			for($i = 1;$i < 20; $i++)
				if($GRLBM->A("GRLBMCustomField$i") !== null)
					$this->changeA("GRLBMCustomField$i", $GRLBM->A("GRLBMCustomField$i"));


		$this->saveMe();
		$G = new GRLBM($this->getID(), false);
		$G->getSumOfPosten(false, true);
		
		return Aspect::joinPoint("after", $this, __METHOD__, $fromId);
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="deleteAllPosten">
	public function deleteAllPosten(){
		$ac = new anyC();
		$ac->setCollectionOf("Posten");
		$ac->addAssocV3("GRLBMID", "=", $this->ID);

		while($t = $ac->getNextEntry())
			$t->deleteMe();
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="addPosten">
	public function addPosten($artikelName, $einheit, $anzahl, $preis, $mwst, $beschreibung = "", $isBrutto = false, $oldArtikelID = null, $EK1 = 0){
		$preis = $preis * 1;
		$P = new Posten(-1, false);
		$A = $P->newAttributes();
		
		$A->GRLBMID = $this->ID;
		$A->name = $artikelName;
		$A->gebinde = $einheit;
		$A->preis = $preis;
		$A->mwst = $mwst;
		$A->menge = $anzahl;
		$A->beschreibung = $beschreibung;
		$A->EK1 = $EK1;
		
		if(property_exists($A, "PostenSortOrder")) //Fix unsorted new Posten after sorting
			$A->PostenSortOrder = 127;
		
		if($oldArtikelID != null) $A->oldArtikelID = $oldArtikelID;

		if($isBrutto == false AND isset($A->isBrutto))
			$A->isBrutto = "0";
		
		if($isBrutto AND isset($A->isBrutto)){
			$A->isBrutto = "1";
			$A->bruttopreis = $preis;
			$A->preis = $preis / ((100 + $mwst) / 100);
			$P->recalcNetto = false;
		}


		$P->setA($A);
		return $P->newMe(false);
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="addPersonal">
	public function addPersonal($PersonalID, $stunden = 1){
		#$this->getPostenCopy($ArtikelID, $menge, $beschreibung, $kundennummer);
		$P = new Personal($PersonalID);
		
		$Preis = 0;
		if($P->A("LohngruppeID") != "0"){
			$L = new Lohngruppe($P->A("LohngruppeID"));
			$Preis = $L->calcNettoPrice(60);
		}
		
		$mwst = 0;
		$M = Kategorien::getDefault("mwst");
		#$AC = anyC::get("Kategorie", "type", "mwst");
		#$AC->addAssocV3("isDefault", "=", "1");
		#$M = $AC->getNextEntry();
		if($M != null)
			$mwst = Util::parseFloat("de_DE",str_replace("%","",$M));
		
		$this->addPosten($P->A("vorname")." ".$P->A("nachname"), "Stunde(n)", $stunden, $Preis, $mwst, "", 0, 0);
	}
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="addArtikel">
	public function addArtikel($ArtikelID, $menge = 1, $beschreibung = null, $kundennummer = null, $preis = null){
		return $this->getPostenCopy($ArtikelID, $menge, $beschreibung, $kundennummer, $preis);
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="setTextbaustein">
	public function setTextbaustein($type, $text, $save = true){
		if($type != "textbausteinUnten" AND $type != "textbausteinOben" AND $type != "zahlungsbedingungen") return;
		$tID = $type."ID";

		$this->loadMe();

		$this->A->$tID = 0;
		$this->A->$type = $text;
		
		if($save) $this->saveMe();
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="setLieferdatum">
	public function setLieferdatum($newDatum, $save = true){
		$this->loadMe();

		$this->A->lieferDatum = $newDatum;

		if($save) $this->saveMe();
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="copyPostenByTypeAndNumber">
	protected function copyPostenByTypeAndNumber($fromNumber, $fromType){
		try {
			$ac = new anyC();
			$ac->setCollectionOf("GRLBM");
			$ac->addAssocV3("is$fromType","=","1");
			$ac->addAssocV3("nummer","=",$fromNumber);
			$id = $ac->getNextEntry();
		} catch(FieldDoesNotExistException $e){
			$ac = new anyC();
			$ac->setCollectionOf("GRLBM");
			$ac->addAssocV3("isWhat","=","$fromType");
			$ac->addAssocV3("nummer","=",$fromNumber);
			$id = $ac->getNextEntry();
		}

		
		if($id == null) die("error:AuftraegeMessages.E017");
		if($ac->numLoaded() > 1) die("error:AuftraegeMessages.E018");
		$this->copyPostenFrom($id->getID());
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="markMeAsUnprinted">
	protected function markMeAsUnprinted(){
		if($this->A == null) $this->loadMe();
		$this->A->isPrinted = 0;
		$this->saveMe();
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="markMeAsPrinted">
	public function markMeAsPrinted(){
		$this->resetParsers();

		if($this->A == null) $this->loadMe();
		$this->A->isPrinted = 1;
		$this->saveMe();
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="setPayed">
	public function setPayed($p, $skonto = "0,00", $datum = "0", $isTeilzahlung = "false", $TeilzahlungBetrag = "", $save = true){

		$pSpecData = mUserdata::getPluginSpecificData("Auftraege");
		
		if(!isset($pSpecData["pluginSpecificCanSetPayed"])) return "error:AuftraegeMessages.E001";

		$this->loadMe();
		if($isTeilzahlung == "false"){
			$this->A->isPayed = $p == "true" ? 1 : 0;
			$this->A->GRLBMpayedDate = $datum;#Util::CLDateParser($datum,"store");
			$this->A->payedWithSkonto = Util::parseFloat("de_DE", $skonto);
		} else {
			$betrag = !is_numeric($TeilzahlungBetrag) ? Util::CLNumberParser($TeilzahlungBetrag, "store") : $TeilzahlungBetrag;
			$this->A->GRLBMTeilzahlungen .= $betrag.";".Util::CLDateParser($datum,"store")."\n";
			$this->A->GRLBMTeilzahlungenSumme += $betrag;
		}
		if($save) $this->saveMe();

		// <editor-fold defaultstate="collapsed" desc="Aspect:jP">
		$args = func_get_args();
		Aspect::joinPoint("after", $this, __METHOD__, $args);
		// </editor-fold>
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="deleteTeilzahlung">
	public function deleteTeilzahlung($zeile){
		$pSpecData = mUserdata::getPluginSpecificData("Auftraege");
		
		if(!isset($pSpecData["pluginSpecificCanSetPayed"])) die("error:AuftraegeMessages.E001");
		
		$t = trim($this->A("GRLBMTeilzahlungen"));
		$t2 = "";
		$s = explode("\n",$t);
		for($i = 0; $i < count($s); $i++){
			if($zeile != $i){
				$t2 .= $s[$i]."\n";
			} else {
				$b = explode(";",$s[$i]);
				$this->A->GRLBMTeilzahlungenSumme -= $b[0] * 1;
			}
		}
		$this->A->GRLBMTeilzahlungen = $t2;
		$this->saveMe(true, true);
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="saveMe">
	public function saveMe($checkuserdata = true, $output = false, $checkPayed = true){
		$oldMe = new GRLBM($this->ID);
		
		if($this->A("isPrinted") != $oldMe->A("isPrinted") OR $this->A("isEMailed") != $oldMe->A("isEMailed") OR $this->A("isEMailedTime") != $oldMe->A("isEMailedTime"))
			$print = true;
		else
			$print = false;
		
		$type = null;
		if($this->A("isR") == "1")
			$type = "isR";
		
		if($this->A("isG") == "1")
			$type = "isG";
		
		if($type != null){
			$AC = anyC::get("GRLBM", $type, "1");
			$AC->addAssocV3("GRLBMID", "!=", $this->getID());
			$AC->addAssocV3("nummer", "=", $this->A("nummer"));
			
			$E = $AC->getNextEntry();
			if($E != null)
				Red::alertD ("Die Belegnummer ".$this->A("nummer")." wurde bereits vergeben!");
		}
		
		if(Session::isPluginLoaded("Provisionen") AND $this->A("isPayed") != $oldMe->A("isPayed"))
			Provisionen::zuweisen($this, $this->A("isPayed") == "1");
		
		if($checkPayed AND $this->A("isPayed") == "1" AND ($this->A("isR") == "1"/* OR $this->A("isB") == "1"*/) AND $oldMe->A("isPayed") == "1" AND !$print)
			die("alert:AuftraegeMessages.A006");
	
		if($this->A("textbausteinObenID") != null AND $this->A("textbausteinObenID") != "0"){
			$T = new Textbaustein($this->A("textbausteinObenID"));
			$this->changeA("textbausteinOben", $T->A("text"));
		}

		if($this->A("textbausteinUntenID") != null AND $this->A("textbausteinUntenID") != "0"){
			$T = new Textbaustein($this->A("textbausteinUntenID"));
			$this->changeA("textbausteinUnten", $T->A("text"));
		}
		
		if($this->A("zahlungsbedingungenID") != null AND $this->A("zahlungsbedingungenID") != "0"){
			$T = new Textbaustein($this->A("zahlungsbedingungenID"));
			$this->changeA("zahlungsbedingungen", $T->A("text"));
		}

		Aspect::joinPoint("after", $this, __METHOD__, array($this->getA()));
		
		parent::saveMe($checkuserdata, $output);
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getMyPrefix">
	public function getMyPrefix($forceB = false){
		if($this->A == null)
			$this->loadMe();

		if($this->A->isR == "1") return "R";
		if($this->A->isL == "1") return "L";
		if($this->A->isB == "1") return "B";
		if($this->A->printAB == "1" AND (Auftrag::getBelegArten("B") OR $forceB)) return "B";
		if($this->A->isA == "1") return "A";
		if($this->A->isG == "1") return "G";
		if($this->A->isM == "1") return "M";
		if($this->A->isWhat != "") return $this->A->isWhat;
		
		return "-1";
	}
	// </editor-fold>

	public static function getPaymentVia($t = null, $limitTo = null, $onlyDefaults = false){
		$ts = array("cash" => "Bar", "debit" => "Lastschrift", "transfer" => "Überweisung", "paypal" => "PayPal", "cashing" => "Inkasso", "creditcard" => "Kreditkarte");
		
		if(Session::isPluginLoaded("mZahlungsart") AND !$onlyDefaults){
			$AC = anyC::get("Zahlungsart");
			$AC->addAssocV3("ZahlungsartStammdatenID", "=", Stammdaten::getActiveStammdaten()->getID());
			$AC->addAssocV3("ZahlungsartUseFor", "=", "");
			
			while($Z = $AC->getNextEntry())
				$ts[$Z->getID()] = $Z->A("ZahlungsartName");
		}
		
		if($limitTo !== null){
			if(!is_array($limitTo))
				$limitTo = array($limitTo);
			
			$ts2 = array();
			
			foreach($limitTo AS $v)
				$ts2[$v] = $ts[$v];
			
			$ts = $ts2;
		}
		
		if($t != null)
			return $ts[$t];
		
		return $ts;
	}
	
	public function getEtiketten(){
		$Auftrag = new Auftrag($this->A("AuftragID"));
		$Adresse = new Adresse($Auftrag->A("AdresseID"));

		$Stammdaten = Stammdaten::getActiveStammdaten();

		return array(array($Stammdaten->A("firmaLang").", ".$Stammdaten->A("strasse")." ".$Stammdaten->A("nr").", ".$Stammdaten->A("plz")." ".$Stammdaten->A("ort"), $Adresse->getFormattedAddress()));
	}
	
	public function updateTodo(){
		if(!Session::isPluginLoaded("mTodo") OR !Session::isPluginLoaded("mKalender"))
			return;
	}
	
	public function getCalendarTitle(){
		return Stammdaten::getLongType($this->getMyPrefix()).": ".$this->A("nummer");
	}
	
	public function getBankingData(){
		$Auftrag = new Auftrag($this->A("AuftragID"));
		$Adresse = new Adresse($Auftrag->A("AdresseID"));
		$Kappendix = Kappendix::getKappendixToKundennummer($Auftrag->A("kundennummer"));
		
		$rep = array("ä", "ö", "ü", "Ä", "Ö", "Ü");
		$repWith = array("ae", "oe", "ue", "Ae", "Oe", "Ue");
		
		return array(
			"name" => str_replace($rep, $repWith, $Kappendix->A("KappendixSameKontoinhaber") ? trim($Adresse->getShortAddress()) : $Kappendix->A("KappendixKontoinhaber")),
			"BLZ" => $Kappendix->A("KappendixBLZ"), 
			"konto" => $Kappendix->A("KappendixKontonummer"),
			"BIC" => $Kappendix->A("KappendixSWIFTBIC"),
			"IBAN" => $Kappendix->A("KappendixIBAN"),
			"mandatDatum" => $Kappendix->A("KappendixIBANMandatDatum"),
			"referenz" => $this->A("prefix").$this->A("nummer"),
			"betrag" => Util::kRound($this->A("bruttobetrag")),
			"zweck" => $this->A("prefix").$this->A("nummer")." vom ".$this->A("datum"),
			"kontoID" => 0);
	}
	
	public function completed(){
		#$this->changeA("EingangsbelegBezahltAm", time());
		#$this->saveMe();
	}
}
?>