<?php
/**
 *  This file is part of phynx.

 *  phynx is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.

 *  phynx is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 *  2007, 2008, 2009, 2010, Rainer Furtmeier - Rainer@Furtmeier.de
 */
class DemoGUI extends Demo implements iGUIHTML2 {
	function  __construct($ID) {
		parent::__construct($ID);

	}
	
	function getHTML($id){
		$this->loadMeOrEmpty();
		/**
		 * DEFAULT HTML TABLE
		 */
		$T = new HTMLTable(1);

		/**
		 * DEFAULT BUTTON
		 *
		 * This button calls a JS-function when clicked
		 */
		$BJS = new Button("JS-Funktion", "computer");
		$BJS->onclick("Demo.demoJS('erfolgreich');");
		$T->addRow($BJS); //Add button to table


		/**
		 * SIDE TABLE
		 */
		$ST = new HTMLSideTable("right");

		/**
		 * RME BUTTON
		 *
		 * This Button executes a PHP method when clicked
		 */
		$BRME = new Button("RME", "computer");
		$BRME->rmePCR("Demo", "-1", "demoRME", array("'par1'", "'par2'"), " ");
		$ST->addRow($BRME); //Add button to table

		/**
		 * POPUP BUTTON
		 *
		 * This Button executes a PHP method when clicked
		 */
		$BPOP = new Button("Popup\nanzeigen", "template");
		$BPOP->popup("demoPopup", "Popup-Title", "Demo", -1, "demoPopup");
		$ST->addRow($BPOP); //Add button to table


		/**
		 * THE EDIT-GUI
		 */
		/*$gui = new HTMLGUI();
		$gui->setObject($this);
		$gui->setName("Demo");
		
		$gui->setLabel("Dateiname", "Datei");
		$gui->setType("Dateiname", "image");
		
		$gui->setStandardSaveButton($this);
		*/
		
		$gui = new HTMLGUIX($this);
		$gui->name("Vorlage");
		
		$B = $gui->addSideButton("Dateiname", "./open3A/Vorlagen/logo.png");
		$B->popup("", "Dateiname", "Demo", $this->getID(), "demoPopup", "Dateiname");
		
	
		return $ST.$T.$gui->getEditHTML();
	}

	public static function demoRME($p1, $p2){
		Red::alertD("Parameter1: $p1; Parameter2: $p2");
	}

	private function getSub($find){
	
		$fields = array();

	
		return $fields;
	}

	
	
	/**
	 * returns a HTML table with all known demo-entries
	 */
	public function demoPopup($find){
		$fields = $this->getSub($find);
		$attributes = array();
		
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
		
		$initFields = array("Dateiname");
		
		$initFields[] = "upload";
		
		$newData = $this->A("Demo".ucfirst($find));
		$newData = json_decode($newData);

		$F = new HTMLForm("Dateiupload", array_merge($initFields, $fields));
		$F->setValue("Dateiname", $find);
		$F->setType("Dateiname", "hidden");
		$F->getTable()->setColWidth(1, 120);
		
		$F->insertSpaceAbove("upload", "Hintergrund");
		$F->setType("upload", "file");
		$F->addJSEvent("upload", "onChange", "contentManager.rmePCR('Demo', '".$this->getID()."', 'processBackground', [fileName], function(){ alert('Upload erfolgreich'); \$j('#Dateiupload input[name=Dateiname]').val(fileName); });");
		
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
			
			$parser = "DemoGUI::parserLabel";
			$type = gettype(self::$instance->A($name));
			
			preg_match_all("/@type (.*)\n/", $doc, $groups);
			if(isset($groups[1][0]))
				if($groups[1][0] == "string" AND $type == "array"){
					$type = "string";
					self::$instance->changeA($name, implode(" ", self::$instance->A($name)));
				}
					
			
			if($type == "array" AND count(self::$instance->A($name)) == 2)
				$parser = "DemoGUI::parserPosition";
			
			if($type == "array" AND count(self::$instance->A($name)) == 3)
				$parser = "DemoGUI::parserFont";
			
			if($type == "boolean")
				$parser = "DemoGUI::parserShow";
			
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
		
		$F->setSaveJSON("Speichern", "", "Demo", $this->getID(), "saveImage", OnEvent::closePopup("Demo").OnEvent::reload("Left"));
		
		echo "<p><small style=\"color:grey;\">Numerische Werte haben die Einheit Millimeter.<br />Positionen (X,Y) beziehen sich auf die linke obere Ecke.</small></p>";
		
		if($find == "background")
			echo "<p>Hier können Sie eine PDF-Datei hochladen, um sie als Hintergrund-Vorlage zu verwenden. Bitte beachten Sie, dass maximal die PDF-Version 1.4 verwendet werden kann.</p>";
		
		if($find == "append")
			echo "<p>Hier können Sie eine PDF-Datei hochladen, um sie als Anhang zu verwenden. Bitte beachten Sie, dass maximal die PDF-Version 1.4 verwendet werden kann.</p>";
		
		
		echo "<div style=\"max-height:400px;overflow:auto;\">".$F."</div>";
	}
	
	public function processBackground($fileName){
		$ex = explode(".", strtolower($fileName));
		
		$tempDir = Util::getTempFilename();
		
		unlink($tempDir);
		$tempDir = dirname($tempDir);
		
		$imgPath = $tempDir."/".$fileName.".tmp";
		
		copy($imgPath,FileStorage::getFilesDir()."$fileName");
		unlink($imgPath);
	}
	
	public function saveImage($data){
		//Red::alertD("Dateiname");		
		Red::alertD(print_r($data,true));		
	}	
}
?>