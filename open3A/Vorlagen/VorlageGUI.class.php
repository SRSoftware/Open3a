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
class VorlageGUI extends Vorlage implements iGUIHTML2 {
	private static $instance;
	private static $fields = array(
			"VorlageName",
			"VorlageLogo",
			"VorlageBackground",
			"VorlageAppend",
			"VorlageLabel",
			"VorlageWidth",
			"VorlageFont",
			"VorlageColor",
			"VorlagePosition",
			"VorlageMargin",
			"VorlageSum",
			"VorlageFooter",
			"VorlageAlign",
			"VorlagePayment",
			"VorlageShow",
			"VorlageNewFonts",
			"VorlageOrder"
		);
	
	private static $c = 1;
	private static $locked = false;
	function getHTML($id){
		if($id == -1){
			$this->loadMeOrEmpty();
			$id = $this->newMe();
			$this->forceReload();
			
			echo "<script type=\"text/javascript\">lastLoadedLeft = $id;</script>";
		}
		
		$bps = $this->getMyBPSData();
		
		$AC = anyC::get("GRLBM", "isR", "1");
		$AC->addAssocV3("isA", "=", "1", "OR");
		$AC->addAssocV3("isL", "=", "1", "OR");
		$AC->setLimitV3("1");
		$AC->addOrderV3("GRLBMID", "DESC");
		
		$G = $AC->getNextEntry();
		
		$gui = new HTMLGUIX($this);
		$gui->name("Vorlage");
	
		$AC = anyC::get("Auftrag", "AuftragVorlage", $this->className());
		$AC->addAssocV3("AuftragVorlage", "=", $this->className(false), "OR");
		$AC->setLimitV3(11);
		$AC->lCV3();
		
		self::$locked = $AC->numLoaded() > 10;
		if($bps != -1 AND isset($bps["overwrite"]))
			self::$locked = false;
		#if(self::$locked)
		#	$gui->optionsEdit(false, false);
		
		$S = Stammdaten::getActiveStammdaten();
		
		$B = new Button("Diese Vorlage\nverwenden", "bestaetigung");
		$B->rmePCR("Vorlage", $this->getID(), "useMe", "", OnEvent::reload("Left"));
		
		$T = new HTMLTable(1);
		$T->setColClass(1, "");
		if($S != null AND $S->A("ownTemplate") == $this->className())
			$T->addRow("<p style=\"color:green\">Diese Vorlage wird verwendet, um Belege zu erstellen.</p>");
		else
			$T->addRow($B);
		
		$B = $gui->addSideButton("Logo und\nKopfzeile", "./open3A/Vorlagen/logo.png");
		$B->popup("", "Logo und Kopfzeile", "Vorlage", $this->getID(), "getSubPopup", "logo");
		if(self::$locked)
			$B->disabled (true);
	
		$B = $gui->addSideButton("Beschriftungen", "./open3A/Vorlagen/label.png");
		$B->popup("", "Beschriftungen", "Vorlage", $this->getID(), "getSubPopup", "label");
		if(self::$locked)
			$B->disabled (true);
	
		$B = $gui->addSideButton("Breiten", "./open3A/Vorlagen/width.png");
		$B->popup("", "Breiten", "Vorlage", $this->getID(), "getSubPopup", "width");
		if(self::$locked)
			$B->disabled (true);
	
		$B = $gui->addSideButton("Schriften", "./open3A/Vorlagen/font.png");
		$B->popup("", "Schriften", "Vorlage", $this->getID(), "getSubPopup", "font");
		if(self::$locked)
			$B->disabled (true);
	
		$B = $gui->addSideButton("Farben", "./open3A/Vorlagen/color.png");
		$B->popup("", "Farben", "Vorlage", $this->getID(), "getSubPopup", "color");
		if(self::$locked)
			$B->disabled (true);
	
		$B = $gui->addSideButton("Positionen", "./open3A/Vorlagen/position.png");
		$B->popup("", "Positionen", "Vorlage", $this->getID(), "getSubPopup", "position");
		if(self::$locked)
			$B->disabled (true);
	
		$B = $gui->addSideButton("Seitenränder", "./open3A/Vorlagen/margin.png");
		$B->popup("", "Seitenränder", "Vorlage", $this->getID(), "getSubPopup", "margin");
		if(self::$locked)
			$B->disabled (true);
	
		$B = $gui->addSideButton("Summe", "./open3A/Vorlagen/sum.png");
		$B->popup("", "Summe", "Vorlage", $this->getID(), "getSubPopup", "sum");
		if(self::$locked)
			$B->disabled (true);
	
		$B = $gui->addSideButton("Fußzeile", "./open3A/Vorlagen/footer.png");
		$B->popup("", "Fußzeile", "Vorlage", $this->getID(), "getSubPopup", "footer");
		if(self::$locked)
			$B->disabled (true);
	
		$B = $gui->addSideButton("Ausrichtung", "./open3A/Vorlagen/ausrichtung.png");
		$B->popup("", "Ausrichtung", "Vorlage", $this->getID(), "getSubPopup", "align");
		if(self::$locked)
			$B->disabled (true);
	
		$B = $gui->addSideButton("Reihenfolge", "./open3A/Vorlagen/reihenfolge.png");
		$B->popup("", "Reihenfolge", "Vorlage", $this->getID(), "getSubOrderPopup", "order", "", "{width: 600}");
		if(self::$locked)
			$B->disabled (true);
	
		if(Session::isPluginLoaded("mBezahlCode")){
			$B = $gui->addSideButton("Zahlung", "./open3A/Vorlagen/zahlung.png");
			$B->popup("", "Fußzeile", "Vorlage", $this->getID(), "getSubPopup", "payment");
		}
		
		$B = $gui->addSideButton("Optionen", "./open3A/Vorlagen/show.png");
		$B->popup("", "Optionen", "Vorlage", $this->getID(), "getSubPopup", "show");
		if(self::$locked)
			$B->disabled (true);
		
		if($G != null){
			$B = $gui->addSideButton("Beispiel\nanzeigen", "pdf");
			$B->windowRme("Vorlage",$this->getID(),"getGRLBMPDF",array(),"_Brief;templateType:PDF");
		}
		
		$gui->attributes(self::$fields);
		
		$gui->parser("VorlageWidth", "VorlageGUI::parserGUILabel");
		$gui->parser("VorlageLabel", "VorlageGUI::parserGUILabel");
		$gui->parser("VorlageMargin", "VorlageGUI::parserGUILabel");
		$gui->parser("VorlageFont", "VorlageGUI::parserGUILabel");
		$gui->parser("VorlageColor", "VorlageGUI::parserGUILabel");
		$gui->parser("VorlagePosition", "VorlageGUI::parserGUILabel");
		$gui->parser("VorlageShow", "VorlageGUI::parserGUILabel");
		$gui->parser("VorlageMargin", "VorlageGUI::parserGUILabel");
		$gui->parser("VorlageSum", "VorlageGUI::parserGUILabel");
		$gui->parser("VorlageFooter", "VorlageGUI::parserGUILabel");
		if(Session::isPluginLoaded("mBezahlCode"))
			$gui->parser("VorlagePayment", "VorlageGUI::parserGUILabel");
		$gui->parser("VorlageAlign", "VorlageGUI::parserGUILabel");
		$gui->parser("VorlageLogo", "VorlageGUI::parserGUILabel");
		$gui->parser("VorlageBackground", "VorlageGUI::parserGUILabel");
		$gui->parser("VorlageAppend", "VorlageGUI::parserGUILabel");
		$gui->parser("VorlageNewFonts", "VorlageGUI::parserNewFonts");
		$gui->parser("VorlageOrder", "VorlageGUI::parserOrder");
		
		$gui->space("VorlageLogo");
		
		$gui->label("VorlageLabel", "Beschriftungen");
		$gui->label("VorlageWidth", "Breiten");
		$gui->label("VorlageFont", "Schriften");
		$gui->label("VorlagePosition", "Positionen");
		$gui->label("VorlageShow", "Optionen");
		$gui->label("VorlageMargin", "Seitenränder");
		$gui->label("VorlageSum", "Summe");
		$gui->label("VorlagePayment", "Zahlung");
		$gui->label("VorlageFooter", "Fußzeile");
		$gui->label("VorlageNewFonts", "Neue Schriften");
		$gui->label("VorlageAlign", "Ausrichtung");
		$gui->label("VorlageBackground", "Hintergrund");
		$gui->label("VorlageAppend", "Anhang");
		$gui->label("VorlageColor", "Farben");
		$gui->label("VorlageOrder", "Reihenfolge");
		
		if(!Session::isPluginLoaded("mBezahlCode"))
			$gui->type("VorlagePayment", "hidden");
		
		$BE = new Button("Code-Editor", "./open3A/Vorlagen/code.png");
		$BE->popup("", "Code-Editor", "Vorlage", $this->getID(), "getCustomCodePopup", "", "", "{width:800}");
		if(self::$locked)
			$BE->disabled (true);
		
		$CH = Util::getCloudHost();
		if($CH != null)
			$BE = "";
		
		
		$BS = new Button("Stammdaten\nüberschreiben", "system");
		$BS->popup("", "Optionen", "Vorlage", $this->getID(), "getStammdatenPopup", "show");
		$BS->style("float:right;");
		if(self::$locked)
			$BS->disabled (true);
		
		
		$BH = new Button("Hintergrund", "./open3A/Vorlagen/hintergrund.png");
		$BH->popup("", "Hintergrund", "Vorlage", $this->getID(), "getSubPopup", "background");
		if(self::$locked)
			$BH->disabled (true);
		
		$BA = new Button("Anhang", "./open3A/Vorlagen/attach.png");
		$BA->popup("", "Anhang", "Vorlage", $this->getID(), "getSubPopup", "append");
		$BA->style("float:right;");
		if(self::$locked)
			$BA->disabled (true);
		
		$TB = new HTMLTable(1, "Erweiterte Funktionen");
		#$TB->setTableStyle("margin-top:30px;");
		
		$TB->weight("light");
		
		$TB->addRow($BA.$BH);
		$TB->addRow($BS.$BE);
		
		$TL = new HTMLTable(1);
		$TL->setColClass(1, "");
		if(self::$locked){
			$B = new Button("Vorlage\nkopieren", "seiten");
			$B->style("float:right;margin-left:10px;");
			$B->onclick("contentManager.rmePCR('Vorlage', '".$this->getID()."', 'cloneMe', [''], function(transport){ lastLoadedLeft = (transport.responseText == '' ? -1 : transport.responseText); contentManager.reloadFrameLeft(); contentManager.reloadFrameRight(); }, '', true );");
			
			$TL->addRow(array("{$B}Diese Vorlage kann nicht mehr bearbeitet werden, da Sie in mehr als 10 Aufträgen verwendet wird. Bitte kopieren Sie diese Vorlage, um Änderungen vorzunehmen.<a href=\"#\" onclick=\"".OnEvent::frame("Left", "Vorlage", $this->getID(), 0, "", "VorlageGUI;overwrite:true")."return false;\" class=\"hiddenLink\">&nbsp;</a>"));
			$TL->addRowClass("highlight");
		} else {
			$BN = new Button("", "notice", "icon");
			$BN->style("float:left;margin-right:10px;");
			
			$TL->addRow(array("{$BN}Bitte beachten Sie: Diese Vorlage wird gegen Veränderungen gesperrt, sobald Sie mehr als 10 Aufträge damit erstellt haben."));
		}
		return $T.$TL.$gui->getEditHTML().$TB;
	}
	
	public function getGRLBMPDF(){
		Environment::load();
		
		$AC = anyC::get("GRLBM", "isR", "1");
		$AC->addAssocV3("isA", "=", "1", "OR");
		$AC->addAssocV3("isG", "=", "1", "OR");
		$AC->addAssocV3("isL", "=", "1", "OR");
		
		$AC->setLimitV3("1");
		$AC->addOrderV3("GRLBMID", "DESC");
		
		$G = $AC->getNextEntry();
		
		$Auftrag = new AuftragGUI($G->A("AuftragID"));
		
		$old = mUserdata::getUDValueS("activePDFCopyTemplate", "");
		mUserdata::setUserdataS("activePDFCopyTemplate", $this->className());

		$Auftrag->getGRLBMPDF("true", null, $G->getID());
		
		mUserdata::setUserdataS("activePDFCopyTemplate", $old);
	}
	
	public function useMe(){
		$S = Stammdaten::getActiveStammdaten();
		#echo $this->className();
		$S->changeA("ownTemplate", $this->className());
		$S->saveMe(true, true);
	}
	
	public static function parserGUILabel($w, $l, $E){
		$attributes = self::getAttributes();
		#echo "<pre style=\"font-size:10px;\">";
		#print_r($attributes);
		#echo "</pre>";
		$data = json_decode($w == "" ? "[]" : $w);
		
		$html = "";
		$oldLabel = null;
		$optionals = array();
		foreach($data AS $field){
			if(strpos($field->name, "optional") === 0){
				$optionals[substr($field->name, 8)] = $field->value;
				
				continue;
			}
			$fieldName = $field->name;
			if(is_numeric(substr($field->name, -1)) AND strpos($field->name, "labelCustomField") === false)
				$fieldName = substr($field->name, 0, strlen($field->name) - 1);
			
			$type = gettype(self::$instance->A($fieldName));
			
			if(!isset($attributes[$fieldName])) //When user entered font name without uploading the font itself
				continue;
			
			$doc = $attributes[$fieldName]->getDocComment();
			preg_match_all("/@label (.*)\n/", $doc, $labels);
			
			$val = $field->value;
				
			if($type == "boolean")
				$val = ($val == "1" ? "Ja" : "Nein");
			
			if($oldLabel != $labels[1][0])
				$html .= ($html != "" ? "<br />" : "").$labels[1][0].": $val";
			else
				$html .= ", $val";
			
			$oldLabel = $labels[1][0];
		}
		
		foreach($optionals AS $key => $value){
			if($value == "1")
				continue;
			
			if(self::$instance->A($key) === null)
				continue;
			
			$doc = $attributes[$key]->getDocComment();
			preg_match_all("/@label (.*)\n/", $doc, $labels);
			
			$html .= ($html != "" ? "<br />" : "").$labels[1][0].": ausgeblendet";
		}
		
		$B = new Button("Änderungen löschen", "./images/i2/delete.gif", "icon");
		$B->style("float:right;");
		$B->rmePCR("Vorlage", $E->getID(), "clearChanges", self::$fields[self::$c++], OnEvent::reload("Left"));
		if(self::$locked)
			$B = "";
		
		return "<small>".($html == "" ? "Keine Änderungen" : $B.$html)."</small>";
	}
	
	public function clearChanges($field){
		$this->changeA($field, "");
		$this->saveMe();
	}

	private static function getAttributes(){
		if(self::$instance == null)
			self::$instance = new PDFBrief(Stammdaten::getActiveStammdaten());
		
		$reflect = new ReflectionClass(self::$instance);
		
		$props = $reflect->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED);
		
		$attribute = array();
		foreach($props AS $property){
			#echo "<pre style=\"font-size:10px;\">";
			#var_dump($property->getDocComment());
			#echo "</pre>";
			if(stripos($property->getDocComment(), "@editor true") === false)
				continue;
			
			if($property->getDeclaringClass()->getName() != "PDFBrief")
				continue;
			
			$attribute[$property->getName()] = $property;
			
		}
		
		return $attribute;
	}
	
	public function getCustomCodePopup(){
		$CH = Util::getCloudHost();
		if($CH != null)
			die("<p class=\"highlight\">Diese Funktionalität steht in der Cloud leider nicht zur Verfügung. Bitte wenden Sie sich an den Support.</p>");
		
		echo "<p><small style=\"color:grey;\">Mit diesem Editor können Sie Ihre Vorlage um eigenen PHP-Code erweitern. Bitte verwenden Sie diesen Editor daher nur, wenn Sie wissen, was Sie tun.</small></p>";
		
		$I = new HTMLInput("VorlageCustomCode", "textarea", $this->A("VorlageCustomCode"));
		$I->style("width:100%;height:450px;");
		echo $I;
		
		$BS = new Button("Änderungen\nspeichern", "save");
		$BS->style("float:right;margin:10px;");
		$BS->rmePCR("Vorlage", $this->getID(), "saveCustomCode", array("\$j('[name=VorlageCustomCode]').val()"), OnEvent::closePopup("Vorlage"));
		
		echo $BS."<div style=\"clear:both;\"></div>";
	}
	
	public function saveCustomCode($code){
		$CH = Util::getCloudHost();
		if($CH != null)
			return;
		
		$this->changeA("VorlageCustomCode", $code);
		$this->saveMe(true, true);
	}
	
	public function getStammdatenPopup(){
		$fields = array(
			"firmaKurz",
			"firmaLang",
			"vorname",
			"nachname",
			"inhaber",
			"geschaeftsfuehrer",
			"ustidnr",
			"amtsgericht",
			"handelsregister",
			"strasse",
			"nr",
			"land",
			"plz",
			"ort",
			"telefon",
			"mobil",
			"fax",
			"email",
			"internet",
			"bank",
			#"bankOrt",
			"blz",
			"ktonr",
			"SWIFTBIC",
			"IBAN");
		
		$data = $this->A("VorlageStammdaten") == "" ? "[]" : $this->A("VorlageStammdaten");
		$data = json_decode($data);
		
		
		$F = new HTMLForm("FormVorlageneditor", $fields);
		$F->getTable()->setColWidth(1, 120);
		
		$F->insertSpaceAbove("bank");
		$F->insertSpaceAbove("inhaber");
		$F->insertSpaceAbove("template","Vorlagen");
		$F->insertSpaceAbove("firmaKurz","Firma");
		$F->insertSpaceAbove("telefon", "Kontakt");
		$F->insertSpaceAbove("bank", "Bank");
		$F->insertSpaceAbove("strasse", "Adresse");
		$F->insertSpaceAbove("ustidnr");
		
		$F->setLabel("firmaKurz","Firma kurz");
		$F->setLabel("firmaLang","Firma lang");
		$F->setLabel("vorname","Vorname");
		$F->setLabel("nachname","Nachname");
		$F->setLabel("nr","Hausnummer");
		$F->setLabel("land","Land");
		$F->setLabel("plz","PLZ");
		$F->setLabel("ort","Ort");
		$F->setLabel("telefon","Telefon");
		$F->setLabel("mobil","Mobil");
		$F->setLabel("fax","Fax");
		$F->setLabel("email","E-Mail");
		$F->setLabel("internet","Internet");
		$F->setLabel("inhaber","Inhaber");
		$F->setLabel("amtsgericht","Amtsgericht");
		$F->setLabel("handelsregister","Handelsregister");
		$F->setLabel("ustidnr","USt-IdNr/St.Nr.");
		$F->setLabel("strasse","Straße");
		$F->setLabel("geschaeftsfuehrer","Geschäftsführer");
		
		$F->setLabel("blz","Bankleitzahl");
		$F->setLabel("ktonr","Kontonummer");
		$F->setLabel("SWIFTBIC","SWIFT/BIC");
		$F->setLabel("bank","Name der Bank");
		$F->setLabel("bankOrt", "Ort der Bank");
		
		foreach($data AS $D)
			$F->setValue($D->name, html_entity_decode($D->value, ENT_COMPAT, "UTF-8"));
		
		
		$F->setSaveJSON("Speichern", "", "Vorlage", $this->getID(), "saveStammdaten", OnEvent::closePopup("Vorlage").OnEvent::reload("Left"));
		
		echo "<p><small style=\"color:grey;\">Überschreiben Sie die Stammdaten, indem Sie in den Feldern etwas eintragen.</small></p><div style=\"max-height:400px;overflow:auto;\">".$F."</div>";
	}
	
	public function getSubOrderPopup($find){
		$fields = $this->getSub($find);
		$attributes = $this->getAttributes();
		
		$this->check($attributes);
		
		#print_r($fields);
		
		echo "<p><small style=\"color:grey;\">Ziehen Sie die gewünschten Felder von rechts nach links.</small></p>";
		
		$lab = array(
			"belegnummer" => "Belegnummer",
			"custom1" => "Benutzerfelder oben",
			"leer" => "Leer",
			"custom2" => "Benutzerfelder unten",
			"KundeAnsprechpartner" => "Kunde Ansprechpartner",
			"KundeTelefon" => "Kunde Telefon",
			"KundeEMail" => "Kunde E-Mail",
			"MwSt" => "MwSt %",
			"MwStBetrag" => "MwSt Betrag",
			"EinzelpreisNetto" => "Einzelpreis Netto",
			"Rabattpreis" => "Rabattpreis",
			"Rabatt" => "Rabatt in %");
		
		$noStrike = array(
			"belegnummer",
			"custom1",
			"leer",
			"custom2",
		);
		
		$doStrike = array();
		
		if($this->A("VorlageLabel") != ""){
			$data = json_decode($this->A("VorlageLabel"));
			foreach($data AS $field){
				if(strpos($field->name, "optional") !== 0)
					continue;
				
				if($field->value == "0")
					$doStrike[] = str_replace("optionallabel", "", $field->name);
				else
					$noStrike[] = str_replace("optionallabel", "", $field->name);
				#echo ." : $field->value<br>";
			}
		}
		
		if($this->A("VorlageWidth") != ""){
			$data = json_decode($this->A("VorlageWidth"));
			foreach($data AS $field){
				if(strpos($field->name, "optional") !== 0)
					continue;
				
				if($field->value == "0")
					$doStrike[] = str_replace("optionalwidth", "", $field->name);
				else
					$noStrike[] = str_replace("optionalwidth", "", $field->name);
				#echo ." : $field->value<br>";
			}
		}
		
		
		
		
		$D = array();
		foreach(explode(";", trim($this->A("VorlageOrder"), ";")) AS $v){
			$ex = explode(":", $v);
			$D[$ex[0]] = $ex[1];
		}
		
		echo "<div style=\"max-height:400px;overflow:auto;\">";
		foreach($fields AS $name){
			$doc = $attributes[$name]->getDocComment();
			preg_match_all("/@label (.*)\n/", $doc, $labels);
			
			if(isset($labels[1][0]))
				echo "<p class=\"prettySubtitle\">".$labels[1][0]."</p>";#$F->setLabel($name, $labels[1][0]);
			
			$available = self::$instance->A($name);
			
			$LU = new HTMLList();
			$LU->setListID($name."used");
			$LU->addListStyle("min-height:100px;");
			$LU->addListClass("usedFields");
			$LU->addListData("name", $name);
			
			if(isset($D[$name]) AND $D[$name]){
				$ex = explode(",", $D[$name]);
				foreach($ex AS $A){
					$N = $A;
					if(self::$instance->A("label".$A))
						$N = self::$instance->A("label".$A);
					
					if($name == "orderRechnungsInfo" AND !self::$instance->A("label".$A) AND !in_array($A, $noStrike))
						$strike = true;
					
					if($name == "orderRechnungsInfo" AND in_array($A, $doStrike))
						$strike = true;
					
					if(($name == "orderColsPrice" OR $name == "orderCols") AND !self::$instance->A("label".$A) AND !in_array($A, $noStrike))
						$strike = true;
					
					if(($name == "orderColsPrice" OR $name == "orderCols") AND in_array($A, $doStrike))
						$strike = true;
				
					if(isset($lab[$A]))
						$N = $lab[$A];

					$B = new Button("Element löschen", "trash_stroke", "iconic");
					$B->style("float:right;margin-right:10px;");
					$B->onclick("\$j(this).parent().remove();");

					$LU->addItem($B."<span ".($strike ? "title=\"Eintrag wurde ausgeblendet\"" : "").">".$N."</span>");
					$LU->addItemStyle("cursor:move;".($strike ? "text-decoration:line-through;" : ""));
					$LU->addItemData("field", $A);
				}
			}
			
			$LA = new HTMLList();
			$LA->setListID($name."available");
			
			foreach($available AS $A){
				$strike  = false;
				
				$N = $A;
				if(self::$instance->A("label".$A))
					$N = self::$instance->A("label".$A);
				
				if($name == "orderRechnungsInfo" AND !self::$instance->A("label".$A) AND !in_array($A, $noStrike))
					$strike = true;
					
				if($name == "orderRechnungsInfo" AND in_array($A, $doStrike))
					$strike = true;
				
				if(($name == "orderColsPrice" OR $name == "orderCols") AND !self::$instance->A("width".$A) AND !in_array($A, $noStrike))
					$strike = true;

				if(($name == "orderColsPrice" OR $name == "orderCols") AND in_array($A, $doStrike))
					$strike = true;

				if(isset($lab[$A]))
					$N = $lab[$A];
				
				$B = new Button("Element löschen", "trash_stroke", "iconic");
				$B->style("float:right;margin-right:10px;display:none;");
				$B->onclick("\$j(this).parent().remove();");
				
				$LA->addItem($B."<span ".($strike ? "title=\"Eintrag wurde ausgeblendet\"" : "").">".$N."</span>");
				$LA->addItemStyle("cursor:move;".($strike ? "text-decoration:line-through;" : ""));
				$LA->addItemData("field", $A);
			}
			
			echo "<div style=\"width:50%;display:inline-block;vertical-align:top;\" class=\"backgroundColor2\">$LU</div><div class=\"backgroundColor4\" style=\"vertical-align:top;width:50%;display:inline-block;\">$LA</div>";
			
			echo OnEvent::script("
				\$j( '#{$name}used' ).sortable({
					revert: true,
					receive: function(event, ui){
						\$j(\$j(this).data().uiSortable.currentItem).find('.iconic').show();
					}
				}).disableSelection();
				
				\$j( '#{$name}available li' ).draggable({
					connectToSortable: '#{$name}used',
					helper: 'clone',
					revert: 'invalid'
				}).disableSelection();");
		}
		
		$B = new Button("Änderungen speichern", "bestaetigung");
		$B->style("float:right;margin:10px;");
		$B->onclick("var t = ''; \$j('.usedFields').each(function(k, v){ var fields = []; \$j(v).find('li').each(function(k, v){ fields.push(\$j(v).data('field')); }); t = t+\$j(v).data('name')+':'+fields.join(',')+';'; }); ".OnEvent::rme($this, "saveSubOrder", array("t"), OnEvent::reload("Left").OnEvent::closePopup("Vorlage")));
		echo "</div>";
		echo $B."<div style=\"clear:both;\"></div>";
	}
	
	public function getSubPopup($find){
		$fields = $this->getSub($find);
		$attributes = $this->getAttributes();
		
		$this->check($attributes);
		
		$initFields = array("subFind");
		
		if($find == "logo" OR $find == "background" OR $find == "append")
			$initFields[] = "upload";
		
		if($find == "font"){
			$initFields[] = "newFont";
			$initFields[] = "upload";
		}
		
		if($find == "show")
			$fields = array_merge (array("language", "currency", "currencyUseSymbol"), $fields);
		
		
		$newData = $this->A("Vorlage".ucfirst($find));
		$newData = json_decode($newData);

		$F = new HTMLForm("FormVorlageneditor", array_merge($initFields, $fields));
		$F->setValue("subFind", $find);
		$F->setType("subFind", "hidden");
		$F->getTable()->setColWidth(1, 120);
		
		if($find == "background"){
			$F->insertField("before", "backgroundFileNameSecondPage", "upload2");
			$F->insertSpaceAbove("upload", "Hintergrund");
			$F->insertSpaceAbove("upload2", "Hintergrund ab Seite 2");
			
			$F->setType("upload", "file");
			$F->setType("upload2", "file");
			
			$F->addJSEvent("upload", "onChange", "contentManager.rmePCR('Vorlage', '".$this->getID()."', 'processBackground', [fileName], function(){ alert('Upload erfolgreich'); \$j('#FormVorlageneditor input[name=backgroundFileName]').val(fileName); });");
			$F->addJSEvent("upload2", "onChange", "contentManager.rmePCR('Vorlage', '".$this->getID()."', 'processBackground', [fileName], function(){ alert('Upload erfolgreich'); \$j('#FormVorlageneditor input[name=backgroundFileNameSecondPage]').val(fileName); });");
		}
		
		if($find == "append"){
			$F->insertSpaceAbove("upload", "Datei");
			$F->setType("upload", "file");
			$F->addJSEvent("upload", "onChange", "contentManager.rmePCR('Vorlage', '".$this->getID()."', 'processBackground', [fileName], function(){ alert('Upload erfolgreich'); \$j('#FormVorlageneditor input[name=appendPDFFile]').val(fileName); });");
		}
		
		if($find == "logo"){
			$F->insertSpaceAbove("upload", "Logo");
			$F->setType("upload", "file");
			$F->addJSEvent("upload", "onChange", "contentManager.rmePCR('Vorlage', '".$this->getID()."', 'processLogo', [fileName], function(){ alert('Upload erfolgreich'); \$j('#FormVorlageneditor input[name=logoFileName]').val(fileName); });");
		}
		
		if($find == "font"){
			$F->setType("upload", "file");
			$F->insertSpaceAbove("newFont", "Neue Schriftart");
			$F->setType("newFont", "parser", "", array("VorlageGUI::parserNewFont"));
			$F->setLabel("newFont", "Neue Schriftart");
			$F->addJSEvent("upload", "onChange", "contentManager.rmePCR('Vorlage', '".$this->getID()."', 'processFont', [fileName, \$j('#FormVorlageneditor input[name=newFontName]').val(), \$j('#FormVorlageneditor select[name=newFontType]').val()], function(){ alert('Upload erfolgreich'); ".OnEvent::reloadPopup("Vorlage")." });");
		}
		
		foreach($fields AS $key => $name){
			$description = "";
			$doc = $attributes[$name]->getDocComment();
			preg_match_all("/@label (.*)\n/", $doc, $labels);
			
			if(isset($labels[1][0]))
				$F->setLabel($name, $labels[1][0]);
			
			
			preg_match_all("/@group (.*)\n/", $doc, $groups);
			
			if(isset($groups[1][0]))
				$F->insertSpaceAbove($name, $groups[1][0]);
			
			
			$possibleValues = null;
			preg_match_all("/@values (.*)\n/", $doc, $values);
			if(isset($values[1][0])){
				$possibleValues = array();
				$ex = explode(",", $values[1][0]);
				foreach($ex AS $k => $v)
					$possibleValues[trim($v)] = trim($v);
				
				$description = "Mögliche Werte: ".implode(", ", $possibleValues);
			}
			
			$isOptional = null;
			preg_match_all("/@optional (.*)\n/", $doc, $groups);
			
			if(isset($groups[1][0]))
				$isOptional = $groups[1][0] == "true";
			
			$parser = "VorlageGUI::parserLabel";
			$type = gettype(self::$instance->A($name));
			
			preg_match_all("/@type (.*)\n/", $doc, $groups);
			if(isset($groups[1][0]))
				if($groups[1][0] == "string" AND $type == "array"){
					$type = "string";
					self::$instance->changeA($name, implode(" ", self::$instance->A($name)));
				}
					
			
			if($type == "array" AND count(self::$instance->A($name)) == 2)
				$parser = "VorlageGUI::parserPosition";
			
			if($type == "array" AND count(self::$instance->A($name)) == 3)
				$parser = "VorlageGUI::parserFont";
			
			if($type == "array" AND count(self::$instance->A($name)) == 3 AND strpos($name, "color") === 0)
				$parser = "VorlageGUI::parserColor";
			
			if($type == "boolean")
				$parser = "VorlageGUI::parserShow";
			
			preg_match_all("/@description (.*)\n/", $doc, $values);
			if(isset($values[1][0]))
				$description .= ($description != "" ? "<br />" : "").$values[1][0];
			
			$F->setType($name, "parser", $newData, array($parser, array_merge(!is_array(self::$instance->A($name)) ? array(self::$instance->A($name)) : self::$instance->A($name), array($name, $isOptional, $this->A("VorlageNewFonts")))));
			if($description != "")
				$F->setDescriptionField($name, $description);
			
				
			preg_match_all("/@requires (.*)\n/", $doc, $values);
			if(isset($values[1][0])){
				try {
					$c = $values[1][0];
					$c = new $c();
				} catch(Exception $e){
					$F->setType($name, "hidden");
				}
			}
			#if($possibleValues)
			#	$F->setType($name, "select", $value, $possibleValues);
		}
		
		$F->setSaveJSON("Speichern", "", "Vorlage", $this->getID(), "saveSub", OnEvent::closePopup("Vorlage").OnEvent::reload("Left"));
		
		echo "<p><small style=\"color:grey;\">Numerische Werte haben die Einheit Millimeter.<br />Positionen (X,Y) beziehen sich auf die linke obere Ecke.</small></p>";
		
		if($find == "background")
			echo "<p>Hier können Sie eine PDF-Datei hochladen, um sie als Hintergrund-Vorlage zu verwenden. Bitte beachten Sie, dass maximal die PDF-Version 1.4 verwendet werden kann.</p>";
		
		if($find == "append")
			echo "<p>Hier können Sie eine PDF-Datei hochladen, um sie als Anhang zu verwenden. Bitte beachten Sie, dass maximal die PDF-Version 1.4 verwendet werden kann.</p>";
		
		
		echo "<div style=\"max-height:400px;overflow:auto;\">".$F."</div>";
	}
	
	public function saveStammdaten($data){
		$data = json_decode($data);
		
		foreach($data AS $k => $D){
			if($D->value == "")
				unset($data[$k]);
		}
		
		$changes = json_encode($data);
		
		$changes = preg_replace_callback('/\\\u(\w\w\w\w)/', create_function('$matches', 'return "&#".hexdec($matches[1]).";";'), $changes);
		
		//5.3!
		/*$changes = preg_replace_callback('/\\\u(\w\w\w\w)/',
			function($matches) {
				return '&#'.hexdec($matches[1]).';';
			}
			, $changes);*/
		
		$this->changeA("VorlageStammdaten", $changes);
		$this->saveMe();
	}
	
	public function check($attributes){
		if(count($attributes) == 0 AND extension_loaded("eAccelerator")){
			if(is_writable(Util::getRootPath()) AND !file_exists(Util::getRootPath().".htaccess")){
				file_put_contents(Util::getRootPath().".htaccess", "php_flag eaccelerator.enable 0\nphp_flag eaccelerator.optimizer 0");
				echo OnEvent::script(OnEvent::reloadPopup("Vorlage"));
				die();
			}
			
			$T = new HTMLTable(1);
			
			$B = new Button("", "warning", "icon");
			$B->style("float:left;margin-right:10px;");
			
			$T->addRow(array($B."Das System kann die Liste der Optionen nicht auslesen. Bitte erstellen Sie im Verzeichnis <code>".Util::getRootPath()."</code> eine Datei Namens <b>.htaccess</b> mit folgenden Inhalt:<br /><br /><pre style=\"font-size:12px;padding:5px;\">php_flag eaccelerator.enable 0\nphp_flag eaccelerator.optimizer 0</pre>"));
			$T->setColClass(1, "highlight");
			die($T);
		}
	}
	
	public function saveSubOrder($data){
		$this->changeA("VorlageOrder", $data);
		$this->saveMe();
	}
	
	public function saveSub($data){
		
		$data = json_decode($data);
		$this->getAttributes();
		
		$find = "";
		foreach($data AS $key => $field){
			if($field->name == "subFind" OR $field->name == "newFont"){
				if($find == "")
					$find = $data[$key]->value;
				
				unset($data[$key]);
			}
		}

		$changes = array();
		
		foreach($data AS $k => $field){
			#$action = "label";
			$fieldName = $field->name;
			if(is_numeric(substr($field->name, -1)))
				$fieldName = substr($field->name, 0, strlen($field->name) - 1);
			
			$type = gettype(self::$instance->A($fieldName));
			
			switch($type){

				case "array":
					$index = substr($field->name, -1);
					
					$value = self::$instance->A($fieldName);
					if($value[$index] != $field->value)
						$changes[] = $field;
				break;
				
				default:
					if(self::$instance->A($field->name) != $field->value OR strlen(self::$instance->A($field->name)) != strlen($field->value))
						$changes[] = $field;
				break;
			}
		}
				
		$changes = json_encode($changes);
		
		$changes = preg_replace_callback('/\\\u(\w\w\w\w)/', create_function('$matches', 'return "&#".hexdec($matches[1]).";";'), $changes);
		
		//5.3!
		/*$changes = preg_replace_callback('/\\\u(\w\w\w\w)/',
			function($matches) {
				return '&#'.hexdec($matches[1]).';';
			}
			, $changes);*/
			
		$this->changeA("Vorlage".ucfirst($find), $changes);
		$this->saveMe();
	}
	
	private function getSub($find){
		$attribute = $this->getAttributes();
		
		$fields = array();
		foreach($attribute AS $name => $v){
			
			$isAssigned = false;
			if(strpos($v->getDocComment(), "@assign $find") !== false)
				$isAssigned = true;
			
			if(strpos($v->getDocComment(), "@assign") !== false AND !$isAssigned)
				continue;
			
			if(strpos($name, $find) === false AND !$isAssigned)
				continue;
			
			$fields[] = $name;
		}
		
		return $fields;
	}
	
	public static function parserLabel($w, $l, $p){
		$x = $p[0];
		$o = $p[2] ? "1" : "0";
		if(is_array($w))
			foreach($w AS $fieldValue){
				if($fieldValue->name == $p[1])
					$x = $fieldValue->value;
				
				if($fieldValue->name == "optional".$p[1])
					$o = $fieldValue->value;
			}
			
		$x = html_entity_decode($x, ENT_COMPAT, "UTF-8");
			
		$I = new HTMLInput($p[1], "text", $x);
		
		$IO = "";
		if($p[2] !== null){
			$IO = new HTMLInput("optional$p[1]", "checkbox", $o);
			$IO->style("margin-right:5px;");
			$IO->onchange("\$j('#FormVorlageneditor input[name=$p[1]]').attr('disabled', !this.checked);");
			$I->style("width:86%;");
			if($o == "0")
				$I->isDisabled(true);
		}
			
		return $IO.$I;
	}
	
	public static function parserNewFont(){
		
		$IN = new HTMLInput("newFontName");
		$IN->style("width:80px;");
		
		$IW = new HTMLInput("newFontType", "select", "", array("" => "Normal", "B" => "Fett", "I" => "Kursiv", "BI" => "Beides"));
		$IW->style("width:80px;margin-left:5px;font-size:10px;");
		
		return "Name: $IN $IW<br /><small>Bitte geben Sie immer den gleichen Schriftnamen an, wenn Sie nur andere Schriftvarianten hochladen.</small>";
	}
	
	public static function parserNewFonts($w, $l, $E){
		if($w == "" OR $w == "[]")
			return "<small>Keine Änderungen</small>";
		
		$w = json_decode($w);
		$html = "";
		foreach($w AS $font){
			$html .= $font->name." ".$font->type." <span style=\"color:grey;\">".basename($font->file)."</span><br />";
		}
		
		
		$B = new Button("Änderungen löschen", "./images/i2/delete.gif", "icon");
		$B->style("float:right;");
		$B->rmePCR("Vorlage", $E->getID(), "clearChanges", "VorlageNewFonts", OnEvent::reload("Left"));
		if(self::$locked)
			$B = "";
		
		return "$B<small>".$html."</small>";
	}
	
	public static function parserOrder($w, $l, $E){
		if($w == "" OR $w == "[]")
			return "<small>Keine Änderungen</small>";
		
		$html = "";
		$attributes = self::getAttributes();
		
		$ex = explode(";", trim($w, ";"));
		foreach($ex AS $D){
			
			$ex2 = explode(":", $D);
			$doc = $attributes[$ex2[0]]->getDocComment();
			preg_match_all("/@label (.*)\n/", $doc, $labels);
			
			if($ex2[1])
				$html .= ($html != "" ? ", " : "").$labels[1][0]."";
		}
		
		$B = new Button("Änderungen löschen", "./images/i2/delete.gif", "icon");
		$B->style("float:right;");
		$B->rmePCR("Vorlage", $E->getID(), "clearChanges", "VorlageOrder", OnEvent::reload("Left"));
		if(self::$locked)
			$B = "";
		
		if(!trim($html))
			return "<small>Keine Änderungen</small>";
		
		return "$B<small>".$html."</small>";
	}
	
	public static function parserColor($w, $l, $p){
		$r = $p[0];
		$g = $p[1];
		$b = $p[2];
		if(is_array($w))
			foreach($w AS $fieldValue){
				if($fieldValue->name == $p[3]."0")
					$r = $fieldValue->value;

				if($fieldValue->name == $p[3]."1")
					$g = $fieldValue->value;

				if($fieldValue->name == $p[3]."2")
					$b = $fieldValue->value;
			}
		
		
		$IS = new HTMLInput($p[3]."0", "hidden", $r);
		$IS->style("width:30px;font-size:10px;text-align:right;");
		
		$IW = new HTMLInput($p[3]."1", "hidden", $g);
		$IW->style("width:30px;margin-left:5px;font-size:10px;text-align:right;");
		
		$IH = new HTMLInput($p[3]."2", "hidden", $b);
		$IH->style("width:40px;margin-left:5px;font-size:10px;text-align:right;");
		
		$IC = new HTMLInput("$p[3]c", "color", Util::rgb2hex(array($r, $g, $b)));
		$IC->onchange("\$j('[name=$p[3]0]').val(Util.hexToRgb(this.value).r); \$j('[name=$p[3]1]').val(Util.hexToRgb(this.value).g); \$j('[name=$p[3]2]').val(Util.hexToRgb(this.value).b);");
		$IC->style("border:0px;background-color:transparent;");
		
		return $IS.$IW.$IH.$IC;
	}
	
	public static function parserFont($w, $l, $p){
		$newFonts = json_decode($p[5] == "" ? "[]" : $p[5]);
		$font = $p[0];
		$weight = $p[1];
		$height = $p[2];
		if(is_array($w))
			foreach($w AS $fieldValue){
				if($fieldValue->name == $p[3]."0")
					$font = $fieldValue->value;

				if($fieldValue->name == $p[3]."1")
					$weight = $fieldValue->value;

				if($fieldValue->name == $p[3]."2")
					$height = $fieldValue->value;
			}
		
		$fonts = array("Helvetica" => "Helvetica", "Courier" => "Courier", "Times" => "Times New Roman");
		foreach($newFonts AS $F)
			if(!in_array($F, $fonts))
				$fonts[$F->name] = $F->name;
		
		if(file_exists(Util::getRootPath()."ubiquitous/Fonts/")){
			$fonts["Ubuntu"] = "Ubuntu";
			$fonts["Orbitron"] = "Orbitron";
			$fonts["Raleway"] = "Raleway";
		}
		
		$IS = new HTMLInput($p[3]."0", "select", $font, $fonts);
		$IS->style("width:80px;font-size:10px;");
		
		$IW = new HTMLInput($p[3]."1", "select", $weight, array("" => "Normal", "B" => "Fett", "I" => "Kursiv", "BI" => "Beides"));
		$IW->style("width:60px;margin-left:5px;font-size:10px;");
		
		$IH = new HTMLInput($p[3]."2", "text", $height);
		$IH->style("width:40px;margin-left:5px;font-size:10px;text-align:right;");
		return $IS.$IW.$IH;
	}
	
	public static function parserPosition($w, $l, $p){
		$x = $p[0];
		$y = $p[1];
		$o = $p[3] ? "1" : "0";
		
		if(is_array($w))
			foreach($w AS $fieldValue){
				if($fieldValue->name == $p[2]."0")
					$x = $fieldValue->value;

				if($fieldValue->name == $p[2]."1")
					$y = $fieldValue->value;
				
				if($fieldValue->name == "optional".$p[2])
					$o = $fieldValue->value;
			}
		$IX = new HTMLInput($p[2]."0", "text", $x);
		$IX->style("width:40px;text-align:right;");
		
		$IY = new HTMLInput($p[2]."1", "text", $y);
		$IY->style("width:40px;margin-left:5px;text-align:right;");
		
		$IO = "";
		if($p[3] !== null){
			$IO = new HTMLInput("optional$p[2]", "checkbox", $o);
			$IO->style("margin-right:5px;");
		}
		
		return $IO." <span style=\"color:grey;\">X:</span> ".$IX." <span style=\"color:grey;\">Y:</span> ".$IY;
	}
	
	public static function parserShow($w, $l, $p){
		$x = $p[0];
		if(is_array($w))
			foreach($w AS $fieldValue)
				if($fieldValue->name == $p[1])
					$x = $fieldValue->value;
		
		$IY = new HTMLInput($p[1], "checkbox", $x);
		
		return $IY;
	}
	
	public function processLogo($fileName){
		$ex = explode(".", strtolower($fileName));
		
		$mime = null;
		if($ex[count($ex) - 1] == "jpg" OR $ex[count($ex) - 1] == "jpeg")
			$mime = "jpg";
		
		if($ex[count($ex) - 1] == "png")
			$mime = "png";
		
		if($mime == null)
			Red::alertD("Bildtyp unbekannt. Bitte verwenden Sie jpg oder png-Dateien ohne Alphakanal.");
		
		$tempDir = Util::getTempFilename();
		
		unlink($tempDir);
		$tempDir = dirname($tempDir);
		
		$imgPath = $tempDir."/".$fileName.".tmp";
		
		if($mime == "png" AND $this->hasAlpha($imgPath)){
			Red::errorD("Bitte verwenden Sie eine jpg-Datei oder eine png-Datei ohne Alpha-Kanal");
			unlink($imgPath);
		}
		
		copy($imgPath,FileStorage::getFilesDir()."$fileName");
		unlink($imgPath);
	}
	
	private function hasAlpha($filename){
		return (ord(@file_get_contents($filename, NULL, NULL, 25, 1)) == 6);
	}
	
	public function processBackground($fileName){
		$ex = explode(".", strtolower($fileName));
		
		$mime = null;
		#if($ex[count($ex) - 1] == "jpg" OR $ex[count($ex) - 1] == "jpeg")
		#	$mime = "jpg";
		
		#if($ex[count($ex) - 1] == "png")
		#	$mime = "png";
		
		if($ex[count($ex) - 1] == "pdf")
			$mime = "pdf";
		
		if($mime == null)
			Red::alertD("Dateityp unbekannt. Bitte verwenden Sie pdf-Dateien bis Version 1.4.");
		
		$tempDir = Util::getTempFilename();
		
		unlink($tempDir);
		$tempDir = dirname($tempDir);
		
		$imgPath = $tempDir."/".$fileName.".tmp";
		
		$version = $this->pdfVersion($imgPath);
		if(Util::versionCheck($version, "1.4")){
			unlink($imgPath);
			Red::alertD("Diese PDF-Datei ist höher als Version 1.4.");
		}
		
		copy($imgPath,FileStorage::getFilesDir()."$fileName");
		unlink($imgPath);
	}
	
	/**
	 * FROM http://www.codediesel.com/php/read-the-version-of-a-pdf-in-php/
	 */
	private function pdfVersion($filename) {
		$fp = @fopen($filename, 'rb');

		if (!$fp)
			return 0;
		

		/* Reset file pointer to the start */
		fseek($fp, 0);

		/* Read 20 bytes from the start of the PDF */
		preg_match('/\d\.\d/', fread($fp, 20), $match);

		fclose($fp);

		if (isset($match[0]))
			return $match[0];
		else
			return 0;
	}

	public function processFont($fileName, $fontName, $fontType){
		$tempDir = Util::getTempFilename();
		
		unlink($tempDir);
		$tempDir = dirname($tempDir);
		
		$tempPath = $tempDir."/".$fileName.".tmp";
		
		if($fontName == ""){
			unlink($tempPath);
			Red::alertD("Bitte geben Sie den Schriftnamen an");
		}
		
		$ex = explode(".", strtolower($fileName));
		
		$mime = null;
		$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
		if($ext == "ttf")
			$mime = "ttf";
		
		if($mime == null)
			Red::alertD("Schrifttyp unbekannt. Bitte verwenden Sie TrueType-Schriftarten.");
		

		$fontPath = FileStorage::getFilesDir()."$fileName";
		copy($tempPath, $fontPath);
		
		require_once dirname(__FILE__).'/makefont/makefont.php';
		
		MakeFont($fontPath);
		#print_r($GLOBALS["makeFontMessages"]);
		unlink($tempPath);
		unlink($fontPath);
		
		$newFonts = $this->A("VorlageNewFonts") == "" ? "[]" : $this->A("VorlageNewFonts");
		$newFonts = json_decode($newFonts);
		
		$in = false;
		foreach($newFonts AS $font){
			if($font->name == $fontName AND $font->type == $fontType){
				$font->file = $fileName;
				$in = true;
			}
		}
		if(!$in){
			$NF = new stdClass();
			$NF->name = $fontName;
			$NF->type = $fontType;
			$NF->file = str_ireplace(".$mime", ".php", $fileName);

			$newFonts[] = $NF;
		}
		
		$this->changeA("VorlageNewFonts", json_encode($newFonts));
		$this->saveMe();
		
	}
}
?>