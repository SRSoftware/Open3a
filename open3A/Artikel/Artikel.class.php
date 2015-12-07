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
class Artikel extends PersistentObject implements iCloneable, iDeletable {
	
	public function __construct($id, $parsers = true){
		parent::__construct($id);

		#$this->parsers = $parsers;
		if($parsers){
			$this->setParser("preis","Util::CLNumberParserZ");
			$this->setParser("EK1","Util::CLNumberParserZ");
			$this->setParser("EK2","Util::CLNumberParserZ");
			$this->setParser("aufschlagGesamt","Util::CLNumberParserZ");
			$this->setParser("aufschlagListenpreis","Util::CLNumberParserZ");
		}
		
		$this->customize();
	}
	
	public function cloneMe(){
		echo $this->newMe();
	}
	
	public function getA(){
		if($this->A == null) $this->loadMe();
		return $this->A;
	}
	
	public function newAttributes(){
		$A = parent::newAttributes();

		if($this->customizer != null)
			$this->customizer->customizeNewAttributes($this->getClearClass(get_class($this)), $A);
		
		
		$AC = anyC::get("Kategorie", "type", "2");
		$AC->addAssocV3("isDefault", "=", "1");
		$M = $AC->getNextEntry();
		if($M != null)
			$A->KategorieID = $M->getID();
		
		$AC = anyC::get("Kategorie", "type", "mwst");
		$AC->addAssocV3("isDefault", "=", "1");
		$M = $AC->getNextEntry();
		if($M != null)
			$A->mwst = Util::parseFloat("de_DE",str_replace("%","",$M->A("name")));
		
		if(Session::isPluginLoaded("mBrutto") AND !Session::isPluginLoaded("mLohngruppe"))
			$A->isBrutto = mUserdata::getUDValueS("DefaultValueArtikelisBrutto", "1");
		
		if(Session::isPluginLoaded("mMwSt")){
			$A->mwst = 0;
			$A->isBrutto = 0;
		}
		
		#$A->sachkonto = mUserdata::getGlobalSettingValue("DVArtikelSachkonto", "8400");;
		
		return $A;
	}
	
	public function newMe($checkUserData = true, $output = false){
		if($this->A->isBrutto == "1"){
			$this->A->bruttopreis = $this->hasParsers ? Util::CLNumberParserZ($this->A->preis,"store") : $this->A->preis;
			$this->setParser("preis","Util::nothingParser");
			
			$mwst = $this->getMwSt();
			if(isset($this->A->steuer))
				$mwst += $this->hasParsers ? Util::CLNumberParserZ($this->A->steuer,"store") : $this->A->steuer;
			
			$this->A->preis = $this->A->bruttopreis / (1 + $mwst / 100);
		}
		
		// <editor-fold defaultstate="collapsed" desc="Aspect:jP">
		return Aspect::joinPoint("after", $this, __METHOD__, parent::newMe($checkUserData, $output));
		// </editor-fold>
	}
	
	public function saveMe($checkUserData = true, $output = false){
		
		if($this->A->isBrutto == "1"){
			$this->A->bruttopreis = $this->hasParsers ? Util::CLNumberParserZ($this->A->preis,"store") : $this->A->preis;
			$this->setParser("preis","Util::nothingParser");
			#echo $this->A->steuer;
			$mwst = $this->getMwSt();
			if(isset($this->A->steuer))
				$mwst += $this->hasParsers ? Util::CLNumberParserZ($this->A->steuer,"store") : $this->A->steuer;
			
			$this->A->preis = $this->A->bruttopreis / (1 + $mwst / 100);
		}

		// <editor-fold defaultstate="collapsed" desc="Aspect:jP">
		return Aspect::joinPoint("after", $this, __METHOD__, parent::saveMe($checkUserData, $output));
		// </editor-fold>
	}
	
	protected function addFile($id){
		$F = new File($id);
		$F->loadMe();
		
		$D = new Datei(-1);
		$A = $D->newAttributes();
		
		$A->DateiClass = "Artikel";
		$A->DateiClassID = $this->ID;
		$A->DateiPath = $id;
		$A->DateiName = basename($id);
		$A->DateiSize = $F->getA()->FileSize;
		$A->DateiIsDir = $F->getA()->FileIsDir;
		
		$D->setA($A);
		$D->newMe();
	}

	/**
	 * PRICE CALCULATIONS
	 */
	public function getArtikelEK1($LieferantID = null, $VarianteArtikelID = 0){
		$LP = null;
		if(Session::isPluginLoaded("mLieferant"))
			$LP = mLieferant::getCheapestEK($this->getID(), $LieferantID, $VarianteArtikelID);
		
		if($LP === null){
			if($this->hasParsers)
				return Util::CLNumberParserZ($this->A("EK1"), "store");
			
			return $this->A("EK1");
		}
		
		return $LP;
	}

	public function getLohnEK(){
		if(!Session::isPluginLoaded("mLohngruppe")) return 0;

		$LG = new Lohngruppe($this->A("LohngruppeID"));

		return $LG->calcNettoPrice($this->A("Lohnminuten"));
	}

	public function getArtikelLP($LieferantID = null){
		if(Session::isPluginLoaded("mLieferant") AND Lieferant::hasArtikelLieferant($this->getID()) AND $this->A("preisModus") == "0")
			return mLieferant::getCheapestLP($this->getID(), $LieferantID);
		else
			return $this->hasParsers ? Util::CLNumberParserZ($this->A("preis"), "store") : $this->A("preis");
	}
	
	public function getGesamtEK1($LieferantID = null, $withStueckliste = true, $VarianteArtikelID = 0){
		$ownPrice = $this->getArtikelEK1($LieferantID, $VarianteArtikelID) + $this->getLohnEK();

		if($withStueckliste)
			$ownPrice += $this->getGesamtEK1Stueckliste ($LieferantID);
		
		return $ownPrice;
	}
	
	public function getGesamtEK1Stueckliste($LieferantID = null){
		$ownPrice = 0;
		if(Session::isPluginLoaded("mStueckliste")){
			$SL = Stueckliste::getStueckliste($this->getID());
			
			foreach($SL AS $I)
				$ownPrice += $I["anzahl"] * $I["artikel"]->getGesamtEK1($LieferantID) * $I["useEK"];
		}
		
		return $ownPrice;
	}

	public function getAufschlagListenpreis($LieferantID = null){
		if($this->A("preisModus") != "0")
			return 0;
		
		$aufschlag = $this->hasParsers ? Util::CLNumberParserZ($this->A("aufschlagListenpreis"), "store") : $this->A("aufschlagListenpreis");
		
		return Util::kRound($this->getArtikelLP($LieferantID) * ($aufschlag / 100));
	}


	public function getAufschlagGesamt($LieferantID = null){
		if($this->A("preisModus") != "0")
			return 0;
		
		$gesamtEK1 = $this->getArtikelLP($LieferantID) + $this->getAufschlagListenpreis($LieferantID) + $this->getLohnEK();
		
		$aufschlag = $this->hasParsers ? Util::CLNumberParserZ($this->A("aufschlagGesamt"), "store") : $this->A("aufschlagGesamt");
		
		return Util::kRound($gesamtEK1 * ($aufschlag / 100));
	}

	public function getMwSt(){
		if(Session::isPluginLoaded("mMwSt")){
			$S = Stammdaten::getActiveStammdaten();
			if($S == null)
				return 0;
			
			$mwst = MwSt::findByBasics($this->A("mwStKategorieID"), $S->A("land"), "");
			if($mwst == null)
				return 0;
				
			return $mwst->A("MwStValue");
		}
		
		return $this->A("mwst");
	}
	
	public function getGesamtNettoVK($withStueckliste = true, $LieferantID = null){
		$ownPrice = $this->getArtikelLP($LieferantID) + $this->getAufschlagListenpreis($LieferantID) + $this->getLohnEK() + $this->getAufschlagGesamt($LieferantID);
		
		if($withStueckliste)
			$ownPrice += $this->getGesamtNettoVKStueckliste();
		
		return $ownPrice;
	}
	
	public function getGesamtNettoVKStueckliste(){
		$ownPrice = 0;
		if(Session::isPluginLoaded("mStueckliste")){
			$SL = Stueckliste::getStueckliste($this->getID());
			
			foreach($SL AS $I)
				$ownPrice += $I["anzahl"] * $I["artikel"]->getGesamtNettoVK() * $I["useVK"];
		}
		
		return $ownPrice;
	}

	public function getGesamtBruttoVK($withStueckliste = true, $LieferantID = null){
		$ownPrice = Util::kRound($this->getGesamtNettoVK(false, $LieferantID) * (1 + $this->getMwSt() / 100));
		if($this->A("isBrutto"))
			$ownPrice = $this->A("bruttopreis");
		
		if($withStueckliste AND Session::isPluginLoaded("mStueckliste")){
			$SL = Stueckliste::getStueckliste($this->getID());
			
			foreach($SL AS $I)
				$ownPrice += $I["anzahl"] * $I["artikel"]->getGesamtBruttoVK(true, $LieferantID) * $I["useVK"];
		}
		
		return $ownPrice;
	}
	

	public function getGesamtVK(){
		if($this->A("isBrutto") == "0")
			return $this->getGesamtNettoVK();

		if($this->A("isBrutto") == "1")
			return $this->getGesamtBruttoVK();
	}
	
	public function getEtiketten(){
		return array(array(array("ART".($this->getID() + 10000), $this->A("EAN")), $this->A("artikelnummer"), $this->A("name"), $this->A("gebinde")));
	}
}
?>
