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

class Brief extends UnpersistentClass {
	
	private $letter = null;
/*
	private $adresse = null;
	private $longType = "";
	private $content = null;

	private $contents = array();
	*/
	public $Adresse;
	public $GRLBM;
	public $Posten;
	public $Auftrag;
	private $TextbausteinOben;
	private $TextbausteinUnten;
	private $Zahlungsbedingungen;
	private $stammdaten = null;

	public $nummer;
	public $datum;
	public $kunde;
	public $type = "";
	
	public $rabatt = "";
	public $leasingrate = "";
	public $rabattInW = "";
	
	public $isCopy = false;
	private $target;
	
	function __construct($target = null) {
		$this->target = $target;
		parent::__construct();
	}
	
	public function setAuftrag(Auftrag $Auftrag){
		$this->Auftrag = $Auftrag;
	}
	
	public function setStammdaten(Stammdaten $S){
		$S->loadMe();
		$this->stammdaten = $S;
	}
	
	public function setGRLBM(GRLBM $G){
		$this->GRLBM = $G;
	}
	
	public function setAdresse(Adresse $A){
		$this->Adresse = $A;
	}
	
	public function setTextbausteinOben(Textbaustein $T){
		$this->TextbausteinOben = $T;
	}
	
	public function setZahlungsbedingungen(Textbaustein $T){
		$this->Zahlungsbedingungen = $T;
	}
	
	public function setTextbausteinUnten(Textbaustein $T){
		$this->TextbausteinUnten = $T;
	}
	
	public function setPosten(mPosten $P){
		$this->Posten = $P;
	}
	
	public function PDFObjectFactory($SpracheID = null){
		if($this->stammdaten == null) {
			$_SESSION["messages"]->addMessage("No Stammdaten set. Please use setStammdaten() before calling PDFObjectFactory()!");
			die("No Stammdaten set. See Message log for details.");
		}

		$SA = $this->stammdaten->getA();
		
		#$_SESSION["BPS"]->setActualClass("Brief");
		#$bps = $_SESSION["BPS"]->getAllProperties();
		
		#if($bps == -1 OR !isset($bps["templateType"]))
		#	BPS::setProperty("Brief", "templateType", "PDF");
		
		$n = "StPDFBriefkopf";
		if($SA->ownTemplate != "")
			$n = $SA->ownTemplate;
			
		if($this->Auftrag AND $this->Auftrag->A("AuftragVorlage") != "")
			$n = $this->Auftrag->A("AuftragVorlage");
		
		#if($_SESSION["BPS"]->getProperty("Brief","templateType") == "Email" AND $SA->ownTemplateEmail != "") 
		#	$n = $SA->ownTemplateEmail;
			
		if($this->target == "Print" AND $SA->ownTemplatePrint != "") 
			$n = $SA->ownTemplatePrint;
		
		$n = Aspect::joinPoint("template", $this, __METHOD__, array($this->Auftrag, $this->GRLBM), $n);
		
		$ud = new mUserdata();
		$aT = $ud->getUDValue("activePDFCopyTemplate");
		
		if($aT != null AND $aT != "" AND $this->isCopy)
			$n = $aT;

		if(BPS::getProperty("Brief", "templateType", "PDF") == "PDF3rd")
			$n = mUserdata::getUDValueS("activePDF3rdTemplate", $n);
		
		try{
			new $n();
		} catch (ClassNotFoundException $e){
			if(!defined("PHYNX_VIA_INTERFACE"))
				die(Util::getBasicHTMLError("Die Vorlage $n konnte nicht gefunden werden.<br />Bitte überprüfen Sie, ob sich die Vorlage im specifics-Verzeichnis befindet.","Vorlagenfehler"));
			else
				throw $e;
		}
		
		$this->letter = new $n($this->stammdaten, $SpracheID);

		return $this->letter;
	}
	
	public function getFilename(){
		return Util::makeFilename($this->letter->getFilename($this));
	}
	
	public function generate($safe = false, $pdfToUse = null){
		if($this->stammdaten == null) {
			$_SESSION["messages"]->addMessage("No Stammdaten set. Please use setStammdaten() before calling generate()!");
			die("No Stammdaten set. See Message log for details.");
		}

		$this->letter = ($pdfToUse == null ? $this->PDFObjectFactory($this->Adresse != null ? $this->Adresse->A("AdresseSpracheID") : null) : $pdfToUse);

		$this->letter->leasingrate = $this->leasingrate;
		$this->letter->rabatt = $this->rabatt;
		$this->letter->rabattInW = $this->rabattInW;

		$this->letter->setBrief($this);
		$this->letter->setBeleg($this->GRLBM);
		
		if($this->Auftrag != null)
			$this->letter->setAuftrag($this->Auftrag);
		
		if($this->Adresse != null)
			$this->letter->printAdresse($this->Adresse);
		
		if($this->GRLBM != null)
			$this->letter->printGRLBM($this->GRLBM);
		
		if($this->TextbausteinOben != null)
			$this->letter->printTextbaustein($this->TextbausteinOben);
		
		if($this->GRLBM != null AND $this->GRLBM->getMyPrefix() == "M")
			$this->letter->printMahnungTable($this->GRLBM);
		
		if($this->GRLBM != null AND $this->GRLBM->getA()->isAbschlussrechnung == "1")
			$this->letter->makeAbschlussrechnung($this->GRLBM);
		
		if($this->GRLBM != null AND $this->GRLBM->getA()->isAbschlussrechnung == "1")
			$this->letter->printTeilrechnungen($this->GRLBM, $this->Posten);
		
		if($this->GRLBM != null AND $this->GRLBM->getA()->isAbschlagsrechnung == "1")
			$this->letter->makeAbschlagsrechnung($this->GRLBM, $this->Posten);
		
		if($this->Posten != null)
			$this->letter->printPosten($this->Posten);
		
		
		if($this->GRLBM != null AND Session::isPluginLoaded("mFeRD") AND Session::isPluginLoaded("mTCPDF")){
			$F = new FeRD(new GRLBM($this->GRLBM->getID(), false));
			$F->PDF($this->letter);
			$F->textbausteine($this->TextbausteinOben, $this->Zahlungsbedingungen, $this->TextbausteinUnten);

			$this->letter->embedDataAsFile($F, "ZUGFeRD-invoice.xml");
		}
		
		$this->letter->printPaymentQR($this->stammdaten, $this->GRLBM);
		
		if($this->Zahlungsbedingungen != null)
			$this->letter->printTextbaustein($this->Zahlungsbedingungen);
			
		if($this->TextbausteinUnten != null)
			$this->letter->printTextbaustein($this->TextbausteinUnten);

		$filename = $this->getFilename();
		
		$_SESSION["BPS"]->registerClass("PDFFilename");
		$_SESSION["BPS"]->setACProperty("filename",$filename);

		if($pdfToUse == null) {
			$tmpfname = $this->getTemp($safe);
	
			$this->letter->Output(($safe ? $tmpfname : $filename.".pdf"),($safe ? "F" : "I"));
			
			if($safe) return $tmpfname;
		}
	}
	
	public function getTemp($safe){
		if(!$safe) return "";
		
		$_SESSION["BPS"]->setActualClass("PDFFilename");
		$bps = $_SESSION["BPS"]->getAllProperties();
		$_SESSION["BPS"]->unregisterClass("PDFFilename");

		return Util::getTempFilename(($bps != -1 AND isset($bps["filename"])) ? $bps["filename"] : null);
	}
	
	function getMultiDruckOutput($safe = false){
		
		$tmpfname = $this->getTemp($safe);

		$this->letter->Output(($safe ? $tmpfname : "GRLBM.pdf"),($safe ? "F" : "I"));
		
		if($safe) return $tmpfname;
	}
}
?>