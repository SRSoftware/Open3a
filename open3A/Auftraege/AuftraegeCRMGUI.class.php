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
class AuftraegeCRMGUI extends Auftraege implements iGUIHTMLMP2 {
	public static $GRLBMS;
	public static $first = true;
	
	public function setOwner($class, $id){
		BPS::setProperty(get_class($this), "ownerClassID", $id);
		BPS::setProperty(get_class($this), "ownerClass", $class);
	}
	
	public function getHTML($id, $page){
		$bps = $this->getMyBPSData();
		
		$this->addOrderV3("auftragDatum", "DESC");
		
		$this->setParser("auftragDatum", "Util::CLDateParser");
		if($bps["ownerClass"] == "Projekt")
			$this->addAssocV3("ProjektID", "=", $bps["ownerClassID"]);
		
		if($bps["ownerClass"] == "WAdresse"){
			$KID = Kappendix::getKappendixIDToAdresse($bps["ownerClassID"], true);
			if($KID == null)
				return false;
			
			$this->addAssocV3("kundennummer", "=", $KID);
		}
		
		$this->loadMultiPageMode($id, $page, 8);
		
		$i = 0;
		self::$GRLBMS = anyC::get("GRLBM");
		self::$GRLBMS->addAssocV3("isM", "=", "0", "AND", "1");
		while($A = $this->getNextEntry())
			self::$GRLBMS->addAssocV3("AuftragID", "=", $A->getID(), ($i++ == 0 ? "AND" : "OR"), "2");
		
		$this->resetPointer();
		
		
		
		$gui = new HTMLGUIX($this, "AuftraegeCRM");
		
		$gui->name("Auftrag");
		$gui->options(false, false, false, false);
		$gui->attributes(array("status"));


		$gui->activateFeature("CRMEditAbove", $this);
		$gui->displayMode("CRMSubframeContainer");

		$gui->parser("status", "AuftraegeCRMGUI::parserStatus");
		
		return $gui->getBrowserHTML($id);
	}
	
	public static function parserStatus($w, $E){
		$html = "";
		
		if(self::$first){
			$html .= "<div class=\"backgroundColor0\" style=\"float:right;padding:3px;margin-right:-3px;margin-top:-3px;\">";
			self::$first = false;
			
			$BA = Auftrag::getBelegArten(null, false, "open3A");
			$show = BPS::getProperty("AuftraegeCRMGUI", "show", "all");
			
			foreach($BA AS $v){
				$B = new Button((($show == $v) ? "Filter aufheben" : "Filtern nach ".Stammdaten::getLongType($v)), Stammdaten::getIconType($v), "icon");
				$B->style("margin-right:10px;".(($show != "all" AND $show != $v) ? "opacity:0.3;" : ""));
				$B->loadFrame("subFrameContainerAuftraegeCRM", "AuftraegeCRM", "-1", "0", "_AuftraegeCRMGUI;show:".(($show == $v) ? "all" : $v));
				$html .= $B;
			}
			
			$html .= "</div>";
		}
		
		$C = Auftraege::getStatus();
		
		$html .= $E->A("auftragDatum").(isset($C[$w]) ? " (".$C[$w].")" : "")."<div style=\"clear:both;height:10px;\"></div>";
		$show = BPS::getProperty("AuftraegeCRMGUI", "show", "all");
		
		while($G = self::$GRLBMS->getNextEntry()){
			if($G->A("AuftragID") != $E->getID())
				continue;
			
			if($show != "all" AND $G->getMyPrefix() != $show)
				continue;
			
			$B = new Button("Beleg anzeigen","./images/i2/pdf.gif", "icon");
			#$B->windowRme();
			$B->style("float:left;margin-right:5px;");
			
			$BP = "";
			if($G->A("isPayed")){
				$BP = new Button("Bezahlt am ".Util::CLDateParser($G->A("GRLBMpayedDate")), "check", "iconicG");
				$BP->style("font-size:12px;margin-right:3px;");
			}
			
			$html .= "<div class=\"backgroundColor3 selectionBox\" onclick=\"".OnEvent::window(new Auftrag($E->A("AuftragID")),"getGRLBMPDF",array("false", "", $G->getID()),"_Brief;templateType:PDF")."\" style=\"width:150px;\"><span style=\"float:right;color:grey;cursor:default;\"><small>$BP".Util::CLFormatCurrency($G->A("bruttobetrag") * 1)."</small></span><strong>".$B.substr($G->getMyPrefix(), 0, 1)."</strong>".$G->A("nummer")."<br /><small style=\"color:grey;\">".Util::CLDateParser($G->A("datum"))."</small></div>";
		}
		
		self::$GRLBMS->resetPointer();
		
		return $html;
	}
}

?>
