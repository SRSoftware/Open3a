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
class mGRLBMGUI extends anyC implements iGUIHTMLMP2 {
	function __construct($id = "") {

		if($id == -2) $this->loadOldId = true;
		
		$bps = $this->getMyBPSData();

		$this->type = $bps["type"];
		
		$this->setCollectionOf("GRLBM");
		
		$this->setParser("datum","Datum::parseGerDate");
		$this->setParser("lieferDatum","Util::CLDateParserE");
		$this->setParser("rabatt","Util::CLNumberParserZ");
		$this->setParser("rabattInW","Util::CLNumberParserZ");
		$this->setParser("leasingrate","Util::CLNumberParserZ");
		#$this->setParser("versandkosten", "Util::CLNumberParserZ");
		#$this->setParser("versandkostenMwSt", "Util::CLNumberParserZ");
		
		$this->customize();
		
		if($this->type == "G" OR $this->type == "A" OR $this->type == "R" OR $this->type == "L" OR $this->type == "B"){
			$this->addAssocV3("is$this->type","=","1", "AND", "2");
			
			if($this->type == "A" AND !Auftrag::getBelegArten("B"))
				$this->addAssocV3("isB","=","1", "OR", "2");
			
		}

		else if($this->type != -1)
			$this->addAssocV3("isWhat","=",$this->type);

	}
	
	function cloneAllToAuftrag($AuftragID){
		$this->addAssocV3("AuftragID","=",$AuftragID);

		while(($t = $this->getNextEntry()))
			$t->cloneMe();
	}


	protected $gui;
	protected $type;
	protected $loadOldId = false;

	function constructParent() {
		#$gui->setCollectionOf($this->collectionOf);
	}

	function getHTML($id, $page){
		echo OnEvent::script("Auftrag.highlightSection('$this->type');");
		
		$bps = $this->getMyBPSData();
		#print_r($bps);
		$pSpecData = mUserdata::getPluginSpecificData("Auftraege");
		if(isset($pSpecData["pluginSpecificCanOnlySeeKalk"]) AND $this->type != "Kalk")
			return "Access denied!";

		$G = new GRLBM(-1);
		$A = $G->newAttributes();
		
		$AC = anyC::get("GRLBM");
		$AC->addAssocV3("AuftragID","=",$bps["AuftragID"]);
		
		$t = "is$this->type";
		if(isset($A->$t))
			$AC->addAssocV3($t, "=", "1", "AND", "2");
		else
			$AC->addAssocV3("isWhat", "=", $this->type, "AND", "2");
		
		if($this->type == "A" AND !Auftrag::getBelegArten("B"))
			$AC->addAssocV3 ("isB", "=", "1", "OR", "2");
		
		$AC->setParser("datum","Datum::parseGerDate");
		$AC->setParser("lieferDatum","Util::CLDateParserE");
		$AC->setParser("rabatt","Util::CLNumberParserZ");
		$AC->setParser("rabattInW","Util::CLNumberParserZ");
		$AC->setParser("leasingrate","Util::CLNumberParserZ");
		
		$AC->addOrderV3("LENGTH(nummer)","DESC");
		$AC->addOrderV3("nummer","DESC");
		$AC->customize();
		
		#if($this->loadOldId)
		#	$AC->addAssocV3("GRLBMID","=",BPS::getProperty("GRLBMGUI", "myID"));

		#$this->loadMultiPageMode(-1, $page, 12);
		#$this->resetPointer();
		
		$weiteres = "Zusätzliche";
		$genus = Stammdaten::getGenusType($this->type);
		if($genus == "n")
			$weiteres = "Zusätzliches";
		if($genus == "m")
			$weiteres = "Zusätzlicher";
		
		$B = new Button($weiteres."\n".Stammdaten::getLongType($this->type)."", "new");
		$B->onclick("$('".$this->type."Button').name = '1'; Auftrag.createGRLBM('".$bps["AuftragID"]."','Auftrag','$this->type');");
		$B->className("backgroundColor0");
		$B->id("createNew{$this->type}Button");
		$B->style("margin-left:0px;");
		
		$T = "<div style=\"padding-left:10px;padding-top:10px;padding-bottom:30px;/*background-color:#eee;*/\" class=\"AuftragBelegContent backgroundColor4\">$B</div>";
		
		$html = Aspect::joinPoint("aboveBelege", $this, __METHOD__, array($this->type, $bps["AuftragID"]), "")."<div style=\"clear:right;\"></div><div style=\"margin-left:10px;max-height:400px;overflow:auto;\" class=\"AuftragBelegContent\">";
		
		$month = null;
		while($G = $AC->getNextEntry()){
			$datum = Util::CLDateParser($G->A("datum"), "store");
			if($month != date("Y", $datum))
				$html .= "<div class=\"backgroundColor2\" style=\"padding:3px;".($month != null ? "margin-top:15px;" : "")."\">".date("Y", $datum)."</div>";
			
			$html .= self::belegBox($G, OnEvent::frame("subframe", "GRLBM", $G->getID()));
			$month = date("Y", $datum);
		}
		
		$html .= "</div>";

		if($AC->numLoaded() == 1){
			$AC->resetPointer();
			$G = $AC->getNextEntry();
			
			return $G->getGUIClass()->getHTML($G->getID()).OnEvent::script("Auftrag.reWidth();");
		}
		return $T."<div style=\"border-right:1px solid #eee;padding-right:9px;padding-top:60px;\" class=\"AuftragBelegContent\">".$html."</div>".OnEvent::script("Auftrag.reWidth();");#$gui->getBrowserHTML(-1);
	}
	
	public static function belegBox(GRLBM $G, $onclick, $thirdLine = "", $onSelect = ""){
		if($G->hasParsers)
			$datum = Util::CLDateParser($G->A("datum"), "store");
		else
			$datum = $G->A("datum");
		
		$BPrinted = new Button("Beleg wurde als gedruckt markiert", "./images/i2/printeds.png", "icon");
		$BPrinted->style("float:right;margin-left:5px;");
		if($G->A("isPrinted") == "0")
			$BPrinted = "";

		$BMailed = new Button("Beleg wurde per E-Mail verschickt", "./images/i2/mailed.png", "icon");
		$BMailed->style("float:right;margin-left:5px;");
		if($G->A("isEMailed") == "0")
			$BMailed = "";

		$BAbschluss = new Button("Abschlussrechnung", "./images/i2/ok.gif", "icon");
		$BAbschluss->style("float:right;");
		if($G->A("isAbschlussrechnung") == "0")
		$BAbschluss = "";

		$I = "";
		if($onSelect){
			$I = new HTMLInput("GRLBM_".$G->getID(), "checkbox");
			$I->style("float:right;vertical-align:top;");
			$I->onchange($onSelect);
		}
		
		$B = new Button("Beleg anzeigen","./images/i2/pdf.gif", "icon");
		$B->style("float:left;margin-right:5px;");

		$html = "
			<div class=\"backgroundColor3 selectionBox\" onclick=\"if(event.target.type == 'checkbox') return; $onclick\" style=\"\">
				<span style=\"float:right;color:grey;\">
					<small>".Util::CLFormatCurrency($G->A("bruttobetrag") * 1)."</small>
				</span>
				".$B.substr($G->getMyPrefix(), 0, 1)." ".$G->A("nummer")."<br />
				$I$BPrinted$BMailed$BAbschluss<small style=\"color:grey;\">".date("d", $datum).". ".Util::CLMonthName(date("m", $datum))."</small>
				".($thirdLine != "" ? "<br /><small style=\"color:grey;\">$thirdLine</small>" : "").Aspect::joinPoint("3rdLine", null, __METHOD__, array($G), "")."
			</div>";
		
		return $html;
	}
	
	public function availabeTBSelection($TBType){
		$TB = new Textbausteine();
		$TBs = $TB->getTBs(strtolower(str_replace("textbaustein", "", $TBType)), "");
		
		$TBs = Aspect::joinPoint("tbs", $this, __METHOD__, array($TBs, $TBType), $TBs);
		
		$T = new HTMLTable(2);
		$T->weight("light");
		$T->useForSelection(false);
		$T->setColWidth(1, 20);
		
		$B = new Button("Textbaustein verwenden", "arrow_left", "iconic");
		foreach($TBs[0] AS $k => $v){
			$T->addRow(array($B, $TBs[1][$k]));
			$T->addRowEvent("click", "Auftrag.loadAndUpdateEditedTB($v);");
		}
		
		echo $T;
	}
	
	public function loadTBForEditor($TBID){
		echo Textbaustein::getTextOf($TBID);
	}
/*
	public static function datumParser($w, $E){
		$BPrinted = new Button("Beleg wurde als gedruckt markiert", "./images/i2/printeds.png", "icon");
		$BPrinted->style("float:right;margin-left:5px;");
		if($E->A("isPrinted") == "0")
			$BPrinted = "";

		$BMailed = new Button("Beleg wurde per E-Mail verschickt", "./images/i2/mailed.png", "icon");
		$BMailed->style("float:right;margin-left:5px;");
		if($E->A("isEMailed") == "0")
			$BMailed = "";

		$BAbschluss = new Button("Abschlussrechnung", "./images/i2/ok.gif", "icon");
		$BAbschluss->style("float:right;");
		if($E->A("isAbschlussrechnung") == "0")
			$BAbschluss = "";

		return $BPrinted.$BMailed.$BAbschluss.$w;
	}*/

}
?>