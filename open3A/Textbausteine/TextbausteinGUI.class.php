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
class TextbausteinGUI extends Textbaustein implements iGUIHTML2/*, iFPDF */{
	function getHTML($id){
		$BelegArten = array();
		if(Session::isPluginLoaded("Auftraege"))
			$BelegArten = Auftrag::getBelegArten(null, true);
		#if(!Auftrag::getBelegArten("B"))
		#	$BelegArten[] = "B";
		
		if(Applications::activeApplication() == "openWaWi")
			$BelegArten = LBestellungGUI::getBelegArten(null, true);

		$this->loadMeOrEmpty();
		$new = $this->newAttributes();

		if(($this->A("KategorieID") == "41" OR $this->A("KategorieID") == "42") AND $this->A("isKatDefault") == "1")
			$this->changeA("isKatDefault","0");
		

		$gui = new HTMLGUIX($this);
		#$gui->setObject($this);
		$gui->name("Textbaustein");
		
		foreach($BelegArten AS $B)
			$gui->type("is".$B."Standard","checkbox");

		$gui->type("isMStandard","checkbox");
		$gui->type("isKatDefault","checkbox");

		$gui->label("isRStandard","Rechnung");
		$gui->descriptionField("isRStandard","Standard für Rechnungen?");

		$gui->label("isMStandard","Mahnung");
		$gui->descriptionField("isMStandard","Standard für Mahnungen?");
		
		$gui->label("isAStandard","Angebot");
		$gui->descriptionField("isAStandard","Standard für Angebote?");
		
		$gui->label("isGStandard","Gutschrift");
		$gui->descriptionField("isGStandard","Standard für Gutschriften?");

		$gui->label("isLStandard","Lieferschein");
		$gui->descriptionField("isLStandard","Standard für Lieferscheine?");

		$gui->label("isBStandard","Bestätigung");
		$gui->descriptionField("isBStandard","Standard für Bestätigungen?");

		$gui->label("isOStandard","Bestellung");
		$gui->descriptionField("isOStandard","Standard für Bestellungen?");

		$gui->label("isPStandard","Preisanfrage");
		$gui->descriptionField("isPStandard","Standard für Preisanfragen?");
		
		$gui->label("isKatDefault","Standard");

		$typ = (isset($_SESSION["TBKategorien"][$this->A->KategorieID]) ? $_SESSION["TBKategorien"][$this->A->KategorieID] : "");
		while($R = Registry::callNext("Textbausteine"))
			if($R[0] == $this->A("KategorieID"))
				$typ = $R[1];
			
		Registry::reset("Textbausteine");
		
		$gui->descriptionField("isKatDefault","Soll dieser Textbaustein als Standard beim Typ '<span style=\"font-weight:bold;\" id=\"TBType\">".$typ."</span>' verwendet werden?");
		
		$gui->label("label","Name");
		$gui->label("text","Text");
		$gui->label("betreff","Betreff");
		
		$gui->type("text","tinyMCE", array("Textbaustein.updateTBVariables", strpos(self::parserKID($this->A("KategorieID")), "E-Mail") === 0 ? "Textbausteine" : null));
		$gui->type("betreff","textarea");
		
		$gui->parser("KategorieID", "parserKID");
		$gui->label("KategorieID","Typ");

		#$gui->addFieldEvent("KategorieID","onChange","Textbaustein.katChange(['".implode("','", $BelegArten)."']);");

		
		$f = array("KategorieID", "label", "betreff", "text");

		foreach($BelegArten AS $B){
			$na = "is".$B."Standard";
			if(isset($new->$na)) $f[] = "is".$B."Standard";
		}

		$f[] = "isMStandard";
		$f[] = "isKatDefault";

		$gui->attributes($f);

		$gui->customize($this->customizer);

		return $gui->getEditHTML().OnEvent::script("Textbaustein.katChange(['".implode("','", $BelegArten)."']);");
	}
	
	public static function parserKID($w){
		$I = new HTMLInput("KategorieID", "hidden", $w);
		
		$t = self::types();
		
		return $t[$w].$I;
	}
}
?>