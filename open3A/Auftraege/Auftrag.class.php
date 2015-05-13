<?php
/**
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
class Auftrag extends PersistentObject implements iReNr, iCloneable, iDeletable, iRepeatable {#, iDesktopLink {

	function  __construct($ID) {
		parent::__construct($ID);

		$this->customize();
	}

	public $GRLBMGUI = null;
	public $AdresseObj = null;

	// <editor-fold defaultstate="collapsed" desc="checkUserRestrictions">
	function checkUserRestrictions($pSpecData = null){
		if($pSpecData == null) $pSpecData = mUserdata::getPluginSpecificData("Auftraege");

		if(isset($pSpecData["pluginSpecificCanOnlyEditOwn"]))
			if($this->A("UserID") != $_SESSION["S"]->getCurrentUser()->getID()) return;

		$hasRestrictions = false;
		foreach($pSpecData as $key => $value)
			if(strstr($key,"pluginSpecificCanSeeAuftraegeFrom"))
				$hasRestrictions = true;

		if($hasRestrictions AND !isset($pSpecData["pluginSpecificCanSeeAuftraegeFrom".$this->A("UserID")]))
			die();
	}
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="getLabel">
	function getLabel(){
		return "Standard: JahrNummer, z.B. 08001";
	}
	// </editor-fold>

	private static $lastNewAttributes = null;
	// <editor-fold defaultstate="collapsed" desc="newWithDefaultValues">
	function newWithDefaultValues($AdresseID = null, $status = null, Stammdaten $S = null){
		if(self::$lastNewAttributes == null)
			self::$lastNewAttributes = $this->newAttributes();
		
		$this->A = self::$lastNewAttributes;
		$this->A->UserID = Session::currentUser()->getID();
		$this->A->auftragDatum = time();
		if($status != null)
			$this->A->status = $status;
		
		if(Session::isPluginLoaded("mStammdaten")){
			if($S == null)
				$S = mStammdaten::getActiveStammdaten();
			$this->A->AuftragVorlage = $S->A("ownTemplate");
			$this->A->AuftragStammdatenID = $S->getID();
		}
		
		$id = $this->newMe(true, false);
		$this->forceReload();

		
		if($AdresseID != null)
			$this->getAdresseCopy($AdresseID);

		return $id;
	}
	// </editor-fold>

	/*function getA(){
		return $this->A;
	}*/

	public static function insertAfter($array, $letter, $what){
		$first = array_splice($array, 0, array_search($letter, $array) + 1);
		$last = array_splice($array, array_search($letter, $array));

		return array_merge($first, array($what), $last);
		
	}
	
	public static function getBelegArten($existsType = null, $addB = false, $forApp = null){
		$belege = array("A");
		if($addB)
			$belege[] = "B";
		
		
		if(($forApp == null OR $forApp == "upFab") AND Applications::activeApplication() == "upFab"){
			$belege = array("L");
			
			if($existsType != null)
				return array_search($existsType, $belege) !== false;
		
			return $belege;
		}
		
		if(($forApp == null OR $forApp == "multiPOS") AND Applications::activeApplication() == "multiPOS"){
			$belege = array("C");
			
			if($existsType != null)
				return array_search($existsType, $belege) !== false;
		
			return $belege;
		}
		
		
		if(($forApp == null OR $forApp == "lightCRM") AND Applications::activeApplication() == "lightCRM"){
			if($existsType != null)
				return array_search($existsType, $belege) !== false;
		
			return $belege;
		}
		
		
		$belege[] = "L";
		$belege[] = "R";
		$belege[] = "G";#, "Kalk");

		$ASBelege = Aspect::joinPoint("belege", __CLASS__, __METHOD__, $belege);
		if(is_array($ASBelege)){
			if(is_array($ASBelege[0]))
				foreach($ASBelege AS $v){
					foreach ($v AS $k => $b){
						if(in_array($b, $belege))
							continue;

						$belege = self::insertAfter($belege, $v[$k - 1], $b);
					}
				}
			else
				$belege = $ASBelege;
		}
		
		if($existsType != null)
			return array_search($existsType, $belege) !== false;
		
		return $belege;
	}

	// <editor-fold defaultstate="collapsed" desc="sendGRLBMToCustomer">
	public function sendGRLBMToCustomer($GRLBMID, $method, $die = true){
		#if($GRLBMID != null) $_SESSION["BPS"]->setProperty("Brief","GRLBMID", $GRLBMID);

		switch($method){
			case "2":
			case "email":
				$this->sendViaEmail($GRLBMID, "", "", "", $die);
			break;

			case "3":
			case "sign":
				$this->signLetter($GRLBMID, "", "", "", $die);
			break;

			case "1":
			case "mail":
				$this->sendViaMail($GRLBMID, $die);
			break;

			case "4":
			case "print":
				$this->printLetter($GRLBMID, "false", $die);
			break;

			case "5":
			case "printWithCopy":
				$this->printLetter($GRLBMID, "true", $die);
				$this->printLetter($GRLBMID, "false", $die);
			break;

			case "6":
			case "signAndPrintOriginal":
				$oldValue = mUserdata::getUDValueS("PLsendToCustomer");
				
				if($oldValue != null)
					mUserdata::setUserdataS("PLsendToCustomer", "false");
				
				$this->signLetter($GRLBMID, "", "", "", $die);

				if($oldValue != null)
					mUserdata::setUserdataS("PLsendToCustomer", $oldValue);

				$this->printLetter($GRLBMID, "false", $die);
			break;
			
			case "7":
			case "mailAndLetter":
				$this->sendViaEmail($GRLBMID, "", "", "", $die);
				
				$this->sendViaMail($GRLBMID, $die);
			break;
			
			case "customerDefault":
				if($this->A("kundennummer") == "-2") 
					if($die) Red::errorD("Keine Kundendaten vorhanden!");
					else throw new Exception("Auftrag: No customer data found!");

				$K = Kappendix::getKappendixToKundennummer($this->A("kundennummer"));
				
				if($K == null)
					if($die) Red::errorD("Keine Kundennummer vorhanden!");
					else throw new Exception("Auftrag: No customer number found!");

				if(!isset($K->getA()->KappendixRechnungsversand) OR $K->A("KappendixRechnungsversand") == "0")
					if($die) Red::errorD("Kein Standardversand ausgewählt");
					else throw new Exception("Auftrag: No default set!");

				$this->sendGRLBMToCustomer($GRLBMID, $K->A("KappendixRechnungsversand"), $die);
			break;

			case "0":
			case "none":
			break;
		}
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="doDefaultAction">
	/*public function doDefaultAction($GRLBMID, $die = true){
		if($this->A("kundennummer") == "-2") return;

		$this->loadMe();

		$K = new mKappendix();
		$K->addAssocV3("kundennummer", "=", $this->A("kundennummer"));
		$K = $K->getNextEntry();

		if($K == null)
			if($die) die("error:AuftraegeMessages.E016");
			else throw new Exception("Auftrag: No Kundennummer found!");

		if(!isset($K->getA()->KappendixRechnungsversand) OR $K->A("KappendixRechnungsversand") == "0") return;

		$this->sendGRLBMToCustomer($GRLBMID, $K->A("KappendixRechnungsversand"), $die);

	}*/
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getGRLBMToCustomerOptions">
	public static function getGRLBMToCustomerOptions($value = null){
		$a = array("none" => "kein Belegversand", "email" => "E-Mail");
		
		if(Session::isPluginLoaded("PL")){
			$a["sign"] = "E-Mail signiert via PixelLetter";
			$a["mail"] = "Brief via PixelLetter";
		}
		
		if(Session::isPluginLoaded("mDrucker")){
			$a["print"] = "Drucken";
			$a["printWithCopy"] = "Drucken mit Kopie";
			if(Session::isPluginLoaded("PL"))
				$a["signAndPrintOriginal"] = "Signieren und Original drucken";
		}
		
		if(Session::isPluginLoaded("PL"))
			$a["mailAndLetter"] = "E-Mail und Brief via PixelLetter";
		
		if($value != null)
			return $a[$value];
		
		return $a;
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="printLetter">
	function printLetter($GRLBMID, $copy, $die = true){
		$brief = $this->getLetter("Print", $copy == "true" ? true : false, $GRLBMID);

		try {
			$drucker = mDrucker::getStandardPrinter($copy == "true" ? true : false);
		} catch (NoStandardPrinterInstalledException $e){
			echo -1;
			return -1;
		}

		$filename = $brief->generate(true);

		$drucker->makePaper($filename, true);
		
		if($die)
			Red::messageD("Druckauftrag übergeben");
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="signLetter">
	function signLetter($GRLBMID, $Recipient = "", $Subject = "", $Body = "", $die = true){
		$G = new GRLBM($GRLBMID);#$_SESSION["BPS"]->getProperty("Brief","GRLBMID"));
		if($G->A("GRLBM1xEMail") != null AND $G->A("GRLBM1xEMail")){
			$altEMailAddress = $G->A("GRLBM1xEMail");
			$G->changeA("GRLBM1xEMail","");
			$G->saveMe();
		} else $altEMailAddress = "";
		$brief = $this->getLetter("", false, $GRLBMID);
		
		$filename = $brief->generate(true);

		$AnAdresse = new Adresse($this->A->AdresseID);
		$AnAdresse->loadMe();

		if($Recipient != "") $AnAdresse->changeA("email", $Recipient);
		if($altEMailAddress != "") $AnAdresse->changeA("email", $altEMailAddress);

		if($_SESSION["S"]->checkForPlugin("SP")) $PL = new SPGUI();
		if(isset($_SESSION["viaInterface"]) OR $_SESSION["S"]->checkForPlugin("PL")) $PL = new PLGUI();

		return $PL->sign($GRLBMID, $filename, $AnAdresse, $Subject, $Body, $die);
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="sendViaMail">
	function sendViaMail($GRLBMID, $die = true, $mode = ""){
		$brief = $this->getLetter("", false, $GRLBMID);

		$filename = $brief->generate(true);

    	if(Session::isPluginLoaded("mFile") AND mUserdata::getUDValueS("sendBelegViaEmailAttachments", "false") == "true"){
			$D = new mDateiGUI();
			$D->addAssocV3("DateiClassID", "=", $GRLBMID);#$_SESSION["BPS"]->getProperty("GRLBMGUI","myID")
			$D->addAssocV3("DateiClass", "=", "GRLBM");

			while($f = $D->getNextEntry()){
				if(!is_array($filename))
					$filename = array($filename);
				
				$filename[] = $f->A("DateiPath");
			}

    	}
		
		$AnAdresse = new Adresse($this->A->AdresseID);
		$AnAdresse->loadMe();

		$PL = new PLGUI();
		return $PL->mail($GRLBMID, $filename, $AnAdresse, $die, $mode);
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getRepeatableActions">
	public function getRepeatableActions(){
		return array("cloneForRepeatable" => "kopieren");
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="cloneForRepeatable">
	public function cloneForRepeatable(Repeatable $R){
		$this->cloneMe(date("d.m.Y",$R->getNextDate()));
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="cloneMe">
	public function cloneMe($useDate = 0, $onlyBelegType = null){
		$this->loadMe();
		$oldID = $this->ID;
		$this->A->auftragDatum = time();
		$newAuftragID = $this->newMe();
		
		$_SESSION["BPS"]->registerClass("cloneGRLBM");
		$_SESSION["BPS"]->setACProperty("AuftragID",$newAuftragID);
		if($useDate != 0) {
			$_SESSION["BPS"]->setACProperty("datum",$useDate);
			$_SESSION["BPS"]->setACProperty("lieferDatum",$useDate);
		}
		
		$_SESSION["BPS"]->setActualClass("mGRLBMGUI");
		$_SESSION["BPS"]->setACProperty("type",$onlyBelegType != null ? $onlyBelegType : -1);
		$mG = new mGRLBMGUI();
		ob_start();
		$mG->cloneAllToAuftrag($oldID);
		ob_end_clean();
		echo $newAuftragID;
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="deleteMe">
	function deleteMe(){
		$mP = new anyC();
		$mP->setCollectionOf("GRLBM");
		$mP->addAssocV3("AuftragID","=",$this->ID);
		$mP->addAssocV3("isM","=","0");
		$mP->lCV3();

		if($mP->numLoaded() > 0)
			die("alert:AuftraegeMessages.A002");
		
		parent::deleteMe();
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="updateAdressID">
	function updateAdressID($newID){ //For 1x Adressen
		// <editor-fold defaultstate="collapsed" desc="Aspect:jP">
		try {
			$MArgs = func_get_args();
			return Aspect::joinPoint("around", $this, __METHOD__, $MArgs);
		} catch (AOPNoAdviceException $e) {}
		Aspect::joinPoint("before", $this, __METHOD__, $MArgs);
		// </editor-fold>
		
		if($this->ID == 0) return;
		if($this->A == null) $this->loadMe();
		
		$this->A->AdresseID = $newID;
		$this->A->kundennummer = -2;
		$this->A->UStIdNr = "";
		$this->saveMe(true, false);
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getLetter">
	function getLetter($target, $copy = false, $GRLBMID = null){		
		$this->loadMe();
		$brief = new Brief($target);
		$brief->setStammdaten($this->A("AuftragStammdatenID") == "0" ? Stammdaten::getActiveStammdaten() : new Stammdaten($this->A("AuftragStammdatenID")));
		/*
		#$Gid = null;
		$_SESSION["BPS"]->setActualClass("GRLBMGUI");
		$bps = $_SESSION["BPS"]->getAllProperties("GRLBMGUI");
		if($bps != -1 AND isset($bps["myID"]) AND $GRLBMID == null)
			$GRLBMID = $bps["myID"];
		
			
		$_SESSION["BPS"]->setActualClass("Brief");
		$bps = $_SESSION["BPS"]->getAllProperties();
		if($bps != -1 AND isset($bps["GRLBMID"]) AND $GRLBMID == null) {
			$GRLBMID = $bps["GRLBMID"];
			#$_SESSION["BPS"]->unsetACProperty("GRLBMID");
		}*/

		if($GRLBMID == null)
			throw new Exception("No GRLBM ID given in ".__FILE__." in method ".__METHOD__);

		#if($GRLBMID == null) die(Util::getBasicHTMLError ("Es wurde keine GRLBMID übergeben!", "open3A Fehler"));#.die("Fehler, keine GRLBMID!!");
		
		$G = new GRLBMGUI($GRLBMID);
		$G->isCopy = $copy;
		$brief->isCopy = $copy;
		$brief->setGRLBM($G);

		$ad = new Adresse(($G->A("lieferAdresseID") != "0") ? $G->A("lieferAdresseID") : $this->A("AdresseID"));

		$brief->setAdresse($ad);
		$brief->setAuftrag($this);
		$brief->setTextbausteinOben(Textbaustein::getFakeTextbaustein($G->getA()->textbausteinOben));
		
		$posten = new mPosten();
		$posten->addAssocV3("GRLBMID","=",$G->getID());
		$posten->lCV3();
		$posten->setLetterType($G->getMyPrefix());
		$brief->setPosten($posten);
				
		$brief->setZahlungsbedingungen(Textbaustein::getFakeTextbaustein($G->getA()->zahlungsbedingungen));
		$brief->setTextbausteinUnten(Textbaustein::getFakeTextbaustein($G->getA()->textbausteinUnten));
		
		//für Dateiname
		$brief->nummer = $G->A("nummer");
		$brief->datum = $G->A("datum");
		$brief->kunde = trim(($ad->A("firma") != "" ? $ad->A("firma") : $ad->A("vorname")." ".$ad->A("nachname")));
		$brief->type = $G->getMyPrefix();
		
		$brief->rabatt = $G->getA()->rabatt;
		$brief->leasingrate = $G->getA()->leasingrate;
		$brief->rabattInW = $G->getA()->rabattInW;
		return $brief;
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getAdresseCopy">
	public function getAdresseCopy($AdresseID){
		$this->loadMe();
		#$_SESSION["messages"]->addMessage("creating Adresse for Auftrag ".$this->ID." of Adresse $AdresseID");
		$p = new Adresse($AdresseID);

		$newAdresseID = $p->newFromAdresse($this->ID);	
		$this->changeA("AdresseID", $newAdresseID);
		$this->saveMe();
		
		try {
			$mKApp = new mKappendix();
			$mKApp->setAssocV3("AdresseID","=",$AdresseID);
			$KApp = $mKApp->getNextEntry();
			
			if($KApp != null) {
				$this->forceReload();
				$this->A->kundennummer = $KApp->A("kundennummer");
				$this->A->UStIdNr = $KApp->A("UStIdNr");
				
				Aspect::joinPoint("beforeSave", $this, __METHOD__, array($AdresseID, $KApp));
				
				$this->saveMe(true, false);
		
			}
		} catch (ClassNotFoundException $e){
			
		}
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getNextNumber">
	public static function getNextNumber($type){
		$startNumber = $re_nr = "".date("y")."001";
		
		$_SESSION["BPS"]->setActualClass("mGRLBMGUI");
		$_SESSION["BPS"]->setACProperty("type",$type);
		$n = new mGRLBMGUI();
		
		$re_nr = $n->getIncrementedField("nummer");
		if($re_nr == 1) $re_nr = $startNumber;

		if(date("y") < 10) $re_nr = "0".$re_nr;

		$re_nr2 = $re_nr."";
		if(date("y") > $re_nr2{0}.$re_nr2{1}) $re_nr = $startNumber;
		
		return $re_nr;
	}
	// </editor-fold>

	private static $reNrTemplate = null;
	// <editor-fold defaultstate="collapsed" desc="createGRLBM">
	public function createGRLBM($belegart, $returnID = false, $belegNummer = false, $referenz = "", $datum = null, $additional = array()){
		$type = $belegart;

		$this->loadMe();
		
		if (!$belegNummer) {
			if (self::$reNrTemplate == null) {
				$reNrClass = mStammdaten::getReNrTemplate();
				Timer::now("2", __FILE__, __LINE__);

				$c = self::$reNrTemplate = new $reNrClass(-1);
			} else
				$c = self::$reNrTemplate;
			
			$re_nr = $c->getNextNumber($belegart, $this);
		} else
			$re_nr = $belegNummer;

		$G = new GRLBM(-1);
		$GA = $G->newAttributes();
		
		$GA->nummer = $re_nr;
		$f = "is".$type;
		
		if($type != "G" AND $type != "R" AND $type != "A" AND $type != "L" AND $type != "B")
			$GA->isWhat = $type;
		else $GA->$f = "1";
			
		$GA->AuftragID = $this->getID();
		
		if($datum === null)
			$datum = time();
		$GA->datum = Util::CLDateParser($datum);#date("d.m.Y");
		$GA->lieferDatum = Util::CLDateParser($datum);#date("d.m.Y");
		$GA->GRLBMpayedVia = "transfer";
		$GA->GRLBMReferenz = $referenz;
		$GA->GRLBMSEPAMode = "OOFF";
		
		$GA->prefix = mStammdaten::getActiveStammdaten()->getPrefix($type);

		if(Session::currentUser() != null){
			$GA->GRLBMCreatedByUsername = Session::currentUser()->A("name");
			$GA->GRLBMCreatedByUserID = Session::currentUser()->getID();
		}
		
		if(Session::isPluginLoaded("Kunden"))
			$KA = Kappendix::getKappendixToKundennummer($this->A("kundennummer"));
		else
			$KA = null;
		
		$f1 = $type."TextbausteinOben";
		$f2 = $type."TextbausteinUnten";
		$f3 = $type."Zahlungsbedingungen";

		if($KA != null AND $KA->A($f1) != null){
			if($type == "R" AND $KA->A("KappendixEinzugsermaechtigung") === "1" AND $KA->A("KappendixEinzugsermaechtigungAltZBTB") != null AND $KA->A("KappendixEinzugsermaechtigungAltZBTB") != "0")
				$f3 = "KappendixEinzugsermaechtigungAltZBTB";

			$TBOID = $KA->A($f1);
			$TBUID = $KA->A($f2);
			$TBZID = $KA->A($f3);

			if($TBOID == 0)
				$TBOID = Textbausteine::getDefaultID("oben", $type);

			if($TBUID == 0)
				$TBUID = Textbausteine::getDefaultID("unten", $type);

			if($TBZID == 0)
				$TBZID = Textbausteine::getDefaultID("zahlungsbedingungen", $type);

			$GA->textbausteinOben = Textbaustein::getTextOf($TBOID);
			$GA->textbausteinUnten = Textbaustein::getTextOf($TBUID);
			$GA->zahlungsbedingungen = Textbaustein::getTextOf($TBZID);

			$GA->textbausteinObenID = $TBOID;
			$GA->textbausteinUntenID = $TBUID;
			$GA->zahlungsbedingungenID = $TBZID;

			if(isset($GA->zahlungsziel) AND $KA->A("KappendixZahlungsziel") != null)
				$GA->zahlungsziel = (time() + $KA->A("KappendixZahlungsziel") * 3600 * 24);
		} else {

			try {
				$TBS = Textbausteine::getDefaultID("oben", $type);
				$GA->textbausteinOben = Textbaustein::getTextOf($TBS);
				$GA->textbausteinObenID = $TBS;
				
				$TBS = Textbausteine::getDefaultID("unten", $type);
				$GA->textbausteinUnten = Textbaustein::getTextOf($TBS);
				$GA->textbausteinUntenID = $TBS;
				
				$TBS = Textbausteine::getDefaultID("zahlungsbedingungen", $type);
				$GA->zahlungsbedingungen = Textbaustein::getTextOf($TBS);
				$GA->zahlungsbedingungenID = $TBS;
			} catch(FieldDoesNotExistException $e) {
				$GA->textbausteinOben = "";
				$GA->textbausteinUnten = "";
				$GA->zahlungsbedingungen = "";
			}

			if(isset($GA->zahlungsziel))
				$GA->zahlungsziel = (time() + 14 * 3600 * 24);
		}
		
		if(Session::isPluginLoaded("mZahlungsart") AND $type == "R"){
			$ZAD = Zahlungsart::getDefault();
			if($KA != null AND $KA->A("KappendixRZahlungsart") != "")
				$ZAD = $KA->A("KappendixRZahlungsart");
			
			if($ZAD != ""){
				$GA->GRLBMpayedVia = $ZAD;
				$TBD = Zahlungsart::getTB($ZAD);
				
				if($TBD != null){
					$GA->zahlungsbedingungen = $TBD->A("text");
					$GA->zahlungsbedingungenID = $TBD->getID();
				}
			}
		}
		
		if(Session::isPluginLoaded("mAnsprechpartner")){
			$ARecipient = Ansprechpartner::getAnsprechpartner("Adresse", $this->A("kundennummer"), $belegart);
			if($ARecipient != null)
				$GA->GRLBMAnsprechpartnerID = $ARecipient->getID();
		}
		
		$Adresse = new Adresse($this->A("AdresseID"));
		if(Session::isPluginLoaded("mSprache") AND $Adresse->A("AdresseSpracheID") != "0"){
			$Sprache = new Sprache($Adresse->A("AdresseSpracheID"));
			
			$GA->GRLBMWaehrungFaktor = $Sprache->A("SpracheWaehrungFaktor");
		}
		
		foreach($additional AS $k => $v)
			$GA->$k = $v;
		
		$G->setA($GA);
		
		Aspect::joinPoint("newGRLBM", $this, __METHOD__, array($G));
		
		$newID = $G->newMe();
		
		$this->A->auftragDatum = time();

		$this->updateStatus($type, false);

		$this->saveMe(true, false);
		
		if($returnID) return $newID;
		return $re_nr.":".Stammdaten::getLongType($type);
	}
	// </editor-fold>

	public function updateStatus($type, $save = true){
		$newStatus = "";
		if($type == "A")
			$newStatus = "open";

		if($type == "B")
			$newStatus = "confirmed";

		if($type == "L")
			$newStatus = "delivered";

		if($type == "R")
			$newStatus = "billed";

		if($type == "declined")
			$newStatus = "declined";

		if($newStatus != "")
			$this->changeA("status", $newStatus);

		if($save AND $newStatus != "")
			$this->saveMe();
	}

	
	public function getSingleEMailRecipient(GRLBM $G, Adresse $AnAdresse, $delete1x = true, $die = true){#, $AnsprechpartnerID = null){
		$args = func_get_args();
		$recipient = Aspect::joinPoint("alterRecipient", null, __METHOD__, $args, $AnAdresse->A("email"));
		
		if(Session::isPluginLoaded("mAnsprechpartner")){
			$Auftrag = new Auftrag($G->A("AuftragID"));
			#if($AnsprechpartnerID == null)
			#else
			#	$ARecipient = new Ansprechpartner($AnsprechpartnerID);
			
			if($G->A("GRLBMAnsprechpartnerID") != "0"){
				$ARecipient = new Ansprechpartner($G->A("GRLBMAnsprechpartnerID"));
				if($ARecipient->A("AnsprechpartnerEmail") != "")
					return $ARecipient->A("AnsprechpartnerEmail");
			}
			
			$ARecipient = Ansprechpartner::getAnsprechpartner("Adresse", $Auftrag->A("kundennummer"), $G->getMyPrefix());
			if($ARecipient != null AND $ARecipient->A("AnsprechpartnerEmail") != ""){
				$AnAdresse->changeA("vorname", $ARecipient->A("AnsprechpartnerVorname"));
				$AnAdresse->changeA("nachname", $ARecipient->A("AnsprechpartnerNachname"));
				$AnAdresse->changeA("anrede", $ARecipient->A("AnsprechpartnerAnrede"));
				$AnAdresse->changeA("email", $ARecipient->A("AnsprechpartnerEmail"));
				
				$recipient = $ARecipient->A("AnsprechpartnerEmail");
			}
		}

		
		if($G->A("GRLBM1xEMail") !== null AND $G->A("GRLBM1xEMail")){
			$recipient = $G->A("GRLBM1xEMail");
			$G->changeA("GRLBM1xEMail","");
			if($delete1x) $G->saveMe();
		}

	    if($recipient == ""){
	    	if($die) die("error:AuftraegeMessages.E004");
			else throw new Exception("E-Mail: No recipient address found!");
		}

		return $recipient;
	}
	
	// <editor-fold defaultstate="collapsed" desc="sendViaEmail">
	function sendViaEmail($GRLBMID, $Recipient = "", $Subject = "", $Body = "", $die = true){
		$G = new GRLBM($GRLBMID);
		
		$brief = $this->getLetter("", false, $GRLBMID);
		$filename = $brief->generate(true);
		
		$Stammdaten = mStammdaten::getActiveStammdaten();
		$AnAdresse = new Adresse($this->A("AdresseID"));

		if($Recipient == "")
			$Recipient = $this->getSingleEMailRecipient($G, $AnAdresse, true, $die);
		
		list($OSubject, $OBody) = AuftragGUI::getEMailTBs($AnAdresse, $Stammdaten, $G, $die);

		if($Subject == "") $Subject = $OSubject;
		if($Body == "") $Body = $OBody;

		
		list($fromName, $from) = AuftragGUI::getEMailSender($Stammdaten, $die);
		
		try {
			$senderDomain = substr($from, stripos($from, "@") + 1);
			$mail = new htmlMimeMail5($senderDomain);
		} catch (Exception $e){
			if($die) Red::errorD($e->getMessage());
			else throw new Exception("E-Mail: ".$e->getMessage());
		}
		
		$images = tinyMCEGUI::findImages($Body);
		$Body = tinyMCEGUI::fixImages($Body);
		
		foreach($images AS $image)
			$mail->addEmbeddedImage(new fileEmbeddedImage($image, "image/jpg"));
		
		if(mUserdata::getUDValueS("sendBelegViaEmailDSN", "false") == "true")
			$mail->setDSN(true, true, true);
		
	    $mail->setFrom(utf8_decode(trim($fromName)." <".trim($from).">"));
	    if(!ini_get('safe_mode')) $mail->setReturnPath($from);
	    $mail->setSubject(utf8_decode(trim(str_replace("\n", " ", $Subject))));

		#$ud = new mUserdata();
		if($_SESSION["S"]->getCurrentUser()->A("UserEmail") != "" AND mUserdata::getUDValueS("BCCToUser", "false") == "true")
			$mail->setBcc($_SESSION["S"]->getCurrentUser()->A("UserEmail"));

		$mail->addAttachment(
	    	new fileAttachment(
	    		$filename,
	    		'application/pdf',
	    		new Base64Encoding())
	    );

		$filenameInvoice = null;
		if($G->getMyPrefix() == "M" AND mUserdata::getUDValueS("sendBelegViaEmailAttachInvoice", "false") == "true"){
			$GRLBMOrig = new GRLBM($G->A("AuftragID"));
			$AuftragOrig = new Auftrag($GRLBMOrig->A("AuftragID"));
			
			$briefInvoice = $AuftragOrig->getLetter("", true, $GRLBMOrig->getID());
			$filenameInvoice = $briefInvoice->generate(true);
		
			$mail->addAttachment(
				new fileAttachment(
					$filenameInvoice,
					'application/pdf',
					new Base64Encoding())
			);
		}
		
		#$ud = new mUserdata();
    	if(Session::isPluginLoaded("mFile") AND mUserdata::getUDValueS("sendBelegViaEmailAttachments", "false") == "true"){
			$D = new mDateiGUI();
			$D->addAssocV3("DateiClassID", "=", $G->getID());#$_SESSION["BPS"]->getProperty("GRLBMGUI","myID")
			$D->addAssocV3("DateiClass", "=", "GRLBM");

			while($f = $D->getNextEntry())
				$mail->addAttachment(new fileAttachment($f->A("DateiPath")));
			
			$attachmentsDir = FileStorage::getFilesDir()."GRLBMID".str_pad($G->getID(), 4, "0", STR_PAD_LEFT);
			if(file_exists($attachmentsDir)){
				$dir = new DirectoryIterator($attachmentsDir);
				foreach ($dir as $file) {
					if($file->isDot()) continue;
					if($file->isDir()) continue;

					$mail->addAttachment(new fileAttachment($file->getPathname()));
				}
			}
    	}

		if(Session::isPluginLoaded("mMicropayment"))
			$Body = Micropayment::attach($mail, $Body);
		
		
	    if(strpos($Body, "<p") !== false AND strpos($Body, "</p>") !== false) {
			$mail->setHTML(Util::makeHTMLMail($Body));
			$mail->setHTMLCharset("UTF-8");
		} else $mail->setText(utf8_decode($Body));

	    $adressen = array($Recipient);
	    #if($altEMailAddress == "")
			#$adressen[] = $AnAdresse->A("email");
	    #else $adressen[] = $altEMailAddress;

		if(!$mail->send($adressen)){
			if($die) Red::errorD("Fehler beim E-Mail-Versand!");
			else throw new Exception("E-Mail: Failed to send e-mail!");
		}
		
		$G->changeA("isEMailed", "1");
		$G->changeA("isEMailedTime", time());
		$G->saveMe(true, false, true);
		
		if($filenameInvoice)
			unlink($filenameInvoice);
		unlink($filename);

		if($die)
			echo "message:AuftraegeMessages.M001";

		return true;
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="updateDatum">
	public function updateDatum($newDatum){
		$this->loadMe();

		if($newDatum > $this->A->auftragDatum){
			$this->A->auftragDatum = $newDatum;
			$this->saveMe();
			$_SESSION["messages"]->addMessage("updating date of Auftrag... $newDatum, ".$this->A->auftragDatum);
		} $_SESSION["messages"]->addMessage("updating date of Auftrag... no update necessary");
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="saveMultiEditField">
	protected function saveMultiEditField($field,$value){
		if($this->A == null)
			$this->loadMe();
		
		$this->A->$field = $value;
		
		if($field == "AuftragAdresseNiederlassungID" AND $value > 0){
			$AN = new AdresseNiederlassung($value);
			
			$this->changeA("AuftragAdresseNiederlassungData", $AN->getJSON());
		}
		
		if($field == "AuftragAdresseNiederlassungID" AND $value == 0)
			$this->changeA("AuftragAdresseNiederlassungData", "");
		
		
		$this->saveMe();
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getDesktopLink">
	/*public function getDesktopLink(){
		$Adresse = new Adresse($this->A("AdresseID"));

		return array("edit",$Adresse->A("firma") == "" ? $Adresse->A("vorname")." ".$Adresse->A("nachname") : $Adresse->A("firma"));
	}*/
	// </editor-fold>
}
?>
