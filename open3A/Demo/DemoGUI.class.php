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
		$B->popup("", "Dateiname", "Demo", $this->getID(), "demoPopup", "Datename");
		
	
		return $ST.$T.$gui->getEditHTML();
	}

	public static function demoRME($p1, $p2){
		Red::alertD("Parameter1: $p1; Parameter2: $p2");
	}

	/**
	 * returns a HTML table with all known demo-entries
	 */
	public static function demoPopup($find){
		
		$F = new HTMLForm("FormVorlageneditor", array_merge($initFields, $fields));

		echo $F;
	}
	
	
}
?>