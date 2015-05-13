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
class mArtikelGUI extends mArtikel implements iGUIHTMLMP2, iAutoCompleteHTML, icontextMenu, iCategoryFilter, iOrderByField, iSearchFilter {

	public $isJoined = "";
	public $searchFields = array("t1.name", "artikelnummer", "beschreibung", "bemerkung");
	public static $ids = array();
	public static $artikelMitStueckliste = array();
	public static $artikelMitLieferant = array();
	
	function __construct(){
		parent::__construct();
		$bps = $this->getMyBPSData();
		#print_r($bps);
		#echo "<pre>";
		#print_r($_SESSION["BPS"]);
		#echo "</pre>";
		
		$this->setParser("bruttopreis","Util::CLNumberParserZ");
			
		if(Session::isPluginLoaded("Kundenpreise") 
			AND $bps != -1 
			AND $_SESSION["BPS"]->isPropertySet(get_class($this),"kundennummer")){
				
			$this->setParser("kundenPreis","Util::CLNumberParserZ");
				
			$this->addJoinV3("Kundenpreis", "ArtikelID", "=", "ArtikelID");
			$this->addJoinV3("Kundenpreis", "kundennummer", "=", $bps["kundennummer"]);
			$this->addJoinV3("Kundenpreis", "KundenpreisVarianteArtikelID", "=", "0");
			
			$this->isJoined .= "K";
		}
		
		if(Session::isPluginLoaded("mLieferant") AND BPS::getProperty("mArtikelGUI", "lieferantFilter", "0") != "0"){
			$LID = BPS::getProperty("mArtikelGUI", "lieferantFilter", "0");
			$this->addJoinV3("LieferantPreis", "ArtikelID", "=", "LieferantPreisArtikelID");
			$this->isJoined .= "L";

			if($LID != "-1") {
				$this->addJoinV3("LieferantPreis", "LieferantPreisLieferantID", "=", $LID);
				$this->addAssocV3("LieferantPreisID", "IS NOT", "NULL");
				#$this->isJoined = 4;
			} else {
				#$this->addJoinV3("LieferantPreis", "LieferantPreisLieferantID", "=", $LID);
				$this->addAssocV3("LieferantPreisID", "IS", "NULL");
			}
		}
		
		$this->customize();
		
	}
	
	function getAvailableCategories(){
		$kat = new Kategorien();
		$kat->addAssocV3("type","=","2");
		return $kat->getArrayWithKeysAndValues();
	}
	
	function getCategoryFieldName(){
		return "t1.KategorieID";
	}
	
	function getHTML($id, $page){
		$this->addJoinV3("Kategorie", "KategorieID", "=", "KategorieID");
		$this->addOrderV3("t".(strlen($this->isJoined)+2).".name", "ASC");
		$this->addOrderV3("t1.KategorieID");
		$this->addOrderV3("t1.name");
		$fields = array("t1.*");
		if(strpos($this->isJoined, "K") !== false)
			$fields[] = "kundenPreis";
		$this->setFieldsV3($fields);
		
		$gui = new HTMLGUIX($this);
		$gui->version("mArtikel");
		$gui->tip();
		$gui->screenHeight();
		
		if(!mUserdata::getUDValueS("OrderByFieldInHTMLGUI".$this->getClearClass()))
			$gui->displayGroup($this->getCategoryFieldName(), "mArtikelGUI::DGParser");

		$shownFields = array("name");#,"preis");

		$gui->options(true, true, true, true);
		
		$gui->displayGroup("KategorieID", "mArtikelGUI::parserDG");
		
		if(strpos($this->isJoined, "K") !== false) {
			$gui->parser("kundenPreis","mArtikelGUI::kPParser");
			
			$shownFields = array_merge(array("kundenPreis"), $shownFields);
		}

		$gui->attributes($shownFields);

		$gui->parser("name","mArtikelGUI::nameParser");

		$gui->customize($this->customizer);
		$this->filterCategories();
		$this->loadMultiPageMode($id, $page, 0);
		
		$this->findOptions($this);
		
		$gui->object($this);//to be able to set sort order via customizer
		
		#if(Session::isPluginLoaded("mEtikette"))
		#	$gui->addSideButton(Etikette::getButton("mArtikel", "-1"));
		
		$TLF = "";
		if(Session::isPluginLoaded("mLieferant") AND $id == -1){
			$TLF = new HTMLTable(1);
			$TLF->addTableClass("browserContainerSubHeight");
			
			$I = new HTMLInput("lieferantenFilter", "select", BPS::getProperty("mArtikelGUI", "lieferantFilter", "0"));
			$I->setOptions(anyC::get("Lieferant"), "LieferantFirma", "alle Lieferanten", array(/*"-1" => "ohne Lieferant"*/));
			$I->onchange(OnEvent::rme($this, "setLieferantFilter", array("this.value"), OnEvent::reload("Right")));
			$TLF->addRow(array($I));
		}
		
		$gui->prepend($TLF);
		return $gui->getBrowserHTML($id);

	}

	public function checkBestand(){
		$checks = func_get_args();
		
		
		foreach($checks AS $check){
			$ex = explode(":", $check);
			$ex2 = explode("_", $ex[0]);
			$ex3 = explode(",", $ex[1]);
			
			if($ex3[0] == "melde")
				$message = "<span style=\"color:orange\">Meldebestand von $ex3[2] Stück erreicht!</span> Im Lager: $ex3[1]";
			
			if($ex3[0] == "mindest")
				$message = "<span style=\"color:red\">Mindestbestand von $ex3[2] Stück erreicht!</span> Im Lager: $ex3[1]";
			
			if($ex2[0] == "LArtikel"){
				$Artikel = new Artikel($ex2[1]);
				echo "<p class=\"prettySubtitle\">".$Artikel->A("name")."</p><p>$message</p>";
			}
			
			if($ex2[0] == "VarianteArtikel"){
				$Variante = new VarianteArtikel($ex2[1]);
				#$Artikel = new Artikel($ex2[1]);
				echo "<p class=\"prettySubtitle\">".$Variante->A("VarianteArtikelName")."</p><p>$message</p>";
			}
		}

	}
	
	public function findOptions($AC){
		while($A = $AC->n())
			self::$ids[] = $A->getID();

		$AC->resetPointer();
		
		if(count(self::$ids) > 0 AND Session::isPluginLoaded("mStueckliste")){
			$AC = anyC::get("Stueckliste");
			$AC->addAssocV3("StuecklisteArtikelID", "IN", "(".implode(",", self::$ids).")");
			$AC->setGroupV3("StuecklisteArtikelID");
			$AC->setFieldsV3(array("StuecklisteArtikelID"));
			while($S = $AC->n())
				self::$artikelMitStueckliste[$S->A("StuecklisteArtikelID")] = true;

			#print_r(self::$artikelMitStueckliste);
		}
		
		if(count(self::$ids) > 0 AND Session::isPluginLoaded("mLieferant")){
			$AC = anyC::get("LieferantPreis");
			$AC->addAssocV3("LieferantPreisArtikelID", "IN", "(".implode(",", self::$ids).")");
			$AC->setGroupV3("LieferantPreisArtikelID");
			$AC->setFieldsV3(array("LieferantPreisArtikelID"));
			while($S = $AC->n())
				self::$artikelMitLieferant[$S->A("LieferantPreisArtikelID")] = true;
		}
		
		
	}
	
	public static function parserDG($w){
		if($w == "0") return "-";
		
		$K = new Kategorie($w);
		return $K->A("name");
	}

	public static function kPParser($w,$E){
		if($w == "0,00" OR $w == "") return "";
		else return "<span style=\"text-align:right;float:right;\">".$w."<br><small style=\"color:grey;\">Kundenpreis</small></span>";
	}
	
	public static function nameParser($w, $E){
		return self::buttonInfoOptions($E)."<span style=\"color:grey;float:right;\">".$E->A("artikelnummer")."</span>".stripslashes($w)."<br /><small style=\"color:grey;float:right;\">".$E->A("gebinde")."</small>".($E->A("bemerkung") != "" ? "<small style=\"color:grey;\">".$E->A("bemerkung")."</small>" : "");
	}
	
	public static function buttonInfoOptions($E){
		$BUTT = array();
		#$BV = "";
		#if(Session::isPluginLoaded("mVariante"))
		#	$BV = "<div style=\"float:right;margin-left:5px;margin-bottom:5px;width:18px;height:18px;\"></div>";
		
		if(Session::isPluginLoaded("mVariante") AND Variante::has($E->getID())){
			$BV = new Button("Dieser Artikel hat Varianten", "./open3A/Varianten/hasVariant.png", "icon");
			$BV->style("float:right;margin-left:5px;margin-bottom:5px;");
			$BUTT[] = $BV;
		}
		
		#$BS = "";
		#if(Session::isPluginLoaded("mStueckliste"))
		#	$BS = "<div style=\"float:right;margin-left:5px;margin-bottom:5px;width:18px;height:18px;\"></div>";
		
		if(Session::isPluginLoaded("mStueckliste") AND isset(self::$artikelMitStueckliste[$E->getID()])){
			$BS = new Button("Dieser Artikel hat eine Stückliste", "./openWaWi/Stueckliste/Stueckliste18.png", "icon");
			$BS->style("float:right;margin-left:5px;margin-bottom:5px;");
			$BUTT[] = $BS;
		}
		
		#$BL = "";
		#if(Session::isPluginLoaded("mLieferant"))
		#	$BL = "<div style=\"float:right;margin-left:5px;margin-bottom:5px;width:18px;height:18px;\"></div>";
		
		if(Session::isPluginLoaded("mLieferant") AND isset(self::$artikelMitLieferant[$E->getID()])){
			$BL = new Button("Dieser Artikel hat Lieferanten", "./openWaWi/Lieferanten/Lieferant18.png", "icon");
			$BL->style("float:right;margin-left:5px;margin-bottom:5px;");
			$BUTT[] = $BL;
		}
		
		#$BN = "";
		#if(Session::isPluginLoaded("mSeriennummer"))
		#	$BN = "<div style=\"float:right;margin-left:5px;margin-bottom:5px;width:18px;height:18px;\"></div>";
		
		if(Session::isPluginLoaded("mSeriennummer") AND $E->A("hatSeriennummer")){
			$BN = new Button("Dieser Artikel hat Seriennummern", "./openWaWi/Seriennummer/Seriennummer18.png", "icon");
			$BN->style("float:right;margin-left:5px;margin-bottom:5px;");
			$BUTT[] = $BN;
		}
		
		while(count($BUTT) < 3)
			$BUTT[] = "<div style=\"float:right;margin-left:5px;margin-bottom:5px;width:18px;height:18px;\"></div>";
		
		if(isset($BUTT[3]))
			unset($BUTT[3]);
		
		return implode("", $BUTT);
	}
	
	public static function preisParser($w, $E){
		#$E->parsers = false;
		#echo $E->hasParsers;
		return Util::CLNumberParserZ($E->getGesamtVK()).($E->A("isBrutto") == "1" ? "<br /><small style=\"color:grey;\">Brutto</small>" : "");
	}
	
	public static function mwst($w){
		return $w."%";
	}

	public function getACData($attributeName, $query, $asJSON = true){
		if(substr($query, 0, 3) == "ART"){
			$id = substr($query, 3) - 10000;
			$this->addAssocV3("ArtikelID", "=", $id);
		} else {
			$this->setSearchStringV3($query);
			$this->setSearchFieldsV3(array("name", "artikelnummer", "EAN"));
		}
		
		$this->setFieldsV3(array("name AS label", "IF(artikelnummer = '', CONCAT('ART', ArtikelID + 10000), artikelnummer) AS value", "CONCAT(IF(artikelnummer = '', CONCAT('ART', ArtikelID + 10000), artikelnummer), '<br />', beschreibung) AS description"));

		if($attributeName == "artikelnummer")
			$this->setFieldsV3(array("artikelnummer AS label", "name AS value", "name AS description"));
		
		$this->setLimitV3("10");
		if(!$asJSON)
			return $this;
		
		#$this->setParser("value", "mArtikelGUI::parserACArtikelnummer");
		echo $this->asJSON();
	}
	
	public function getACHTML($attributeName, $query){
		$gui = new HTMLGUI2();
		
		$this->setSearchStringV3($query);
		
		$bps = $this->getMyBPSData();
		$mode = "quickSearchLoadFrame";
		
		if(isset($bps["selectionMode"]) AND $bps["selectionMode"] != "") {
			$mode = "quickSearchSelectionMode";
			#$gui->setMode($bps["mode"]);
			#$this->addAssocV3(($this->isJoined ? "t3" : "t2").".name","LIKE", "%$query%","AND","1");
			
		}# else $this->setSearchFieldsV3(array("t2.name","t1.name","bemerkung","beschreibung","artikelnummer"));
		$fields = $this->searchFields;
		$settings = mUserdata::getUDValueS("searchmArtikel", "");
		if($settings != "")
			$fields = explode(",", $settings);
		
		$this->setSearchFieldsV3($fields);#array(/*"t".(2 + strlen($this->isJoined)).".name",*/"t1.name"/*,"bemerkung","beschreibung"*/,"artikelnummer","beschreibung"));
		#$this->addAssocV3("t2.name","LIKE", "%$query%","AND","1");
		$this->setFieldsV3(array("t1.name", "gebinde", "bemerkung", "beschreibung", "artikelnummer"));
		#$this->addJoinV3("Kategorie","KategorieID","=","KategorieID","ArtikelAttributes");
		
				
		/*$this->addAssocV3("name","LIKE", "%$query%","OR","1");
		$this->addAssocV3("bemerkung","LIKE", "%$query%","OR", "1");
		$this->addAssocV3("beschreibung","LIKE", "%$query%","OR", "1");
		$this->addAssocV3("artikelnummer","LIKE", "%$query%","OR", "1");*/
		
		$this->setLimitV3("10");
		$this->addOrderV3("t1.name","ASC");
		$this->lCV3();
		
		$this->findOptions($this);
		
		$gui->setObject($this);
		$gui->setShowAttributes(array("name"));
		$gui->setParser("name","mArtikelGUI::parserACName", array("\$beschreibung", "\$gebinde","\$artikelnummer", "\$aid"));
		
		$_SESSION["BPS"]->registerClass("HTMLGUI2");
		$_SESSION["BPS"]->setACProperty("targetFrame","contentLeft");
		$_SESSION["BPS"]->setACProperty("targetPlugin","Artikel");
		
		$gui->customize($this->customizer);
		
		$gui->autoCheckSelectionMode(get_class($this));
		echo $gui->getACHTMLBrowser($mode);
	}

	public function getContextMenuHTML($identifier){
		
		switch($identifier){
			case "searchHelp":
				$F = new HTMLFormCheckList("searchedmArtikel", $this->searchFields);
				
				foreach($this->searchFields AS $v){
					$F->setLabel($v, ucfirst(preg_replace("/[a-z0-9]+\./", "", $v)));
				}
		
				$F->setSaveCheckListUD("mArtikel", "search", true, OnEvent::closeContext());
				
				echo "<p style=\"padding:5px;\">Es werden folgende Felder durchsucht:</p>$F<p>Sie können Ihre Suchanfrage mit UND verknüpfen.<br/>Also z.B. \"Artikelnummer UND Artikelname\"</p>";
			break;
		}
		
	}
	
	public static function parserACName($w, $l, $p){
		$p = HTMLGUI::getArrayFromParametersString($p);
		$p[0] = str_replace("\n", " ", $p[0]);
		return self::buttonInfoOptions(new Artikel($p[3]))."<div style=\"width:100px;overflow:hidden;float:right;color:grey;text-align:right;\">$p[2]<br /><small>$p[1]</small></div>".$w."<br /><small style=\"color:grey;\">".(strlen($p[0]) > 45 ? substr($p[0], 0, 45)."..." : $p[0])."</small>";
	}
	
	public function setLieferantFilter($LieferantID){
		BPS::setProperty("mArtikelGUI", "lieferantFilter", $LieferantID);
	}
	
	public static function numberParser($w,$l){
		if($l == "load") return number_format($w, 2, ",", "");
		if($l == "store") return str_replace(",",".",$w);
	}


	public static function doSomethingElse(){
		if(!isset($_SESSION[$_SESSION["applications"]->getActiveApplication()]["kategorien"]))
			$_SESSION[$_SESSION["applications"]->getActiveApplication()]["kategorien"] = array();

		$_SESSION[$_SESSION["applications"]->getActiveApplication()]["kategorien"]["Artikel"] = "2";
		$_SESSION[$_SESSION["applications"]->getActiveApplication()]["kategorien"]["Mehrwertsteuer"] = "mwst";
	}

	public function getOrderByFields() {
		return array("name" => "Artikelname", "artikelnummer" => "Artikelnummer");
	}

	public function getSearchedFields() {
		$fields = $this->searchFields;
		$settings = mUserdata::getUDValueS("searchmArtikel", "");
		if($settings != "")
			$fields = explode(",", $settings);
		
		return $fields;
	}
}
?>
