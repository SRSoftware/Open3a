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

		/**
		 * PARSERS
		 *
		 * Use Util::CLDateParserE, if the field contais a date or is empty
		 * Use Util::CLNumberParserZ if the field contains a number and you always want to display at least two decimals
		 */
		$this->setParser("DemoFeld3", "Util::CLDateParserE");
		$this->setParser("DemoFeld5", "Util::CLNumberParserZ");
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
		$gui = new HTMLGUI();
		$gui->setObject($this);
		$gui->setName("Demo");

		/**
		 * LABELS
		 */
		$gui->setLabel("DemoFeld1", "Name");
		$gui->setLabel("DemoFeld2", "Beschreibung");
		$gui->setLabel("DemoFeld3", "Datum");
		$gui->setLabel("DemoFeld4", "Aktiv?");
		$gui->setLabel("DemoFeld5", "Preis");
		$gui->setLabel("DemoFeld6", "Typ");

		/**
		 * TYPES
		 */
		$gui->setType("DemoFeld2", "textarea");
		$gui->setType("DemoFeld3", "calendar");
		$gui->setType("DemoFeld4", "checkbox");
		$gui->setType("DemoFeld6", "select");
		$gui->setType("DemoFeld7", "hidden");

		/**
		 * VALUES
		 */
		$gui->setOptions("DemoFeld6", array("0", "1", "2"), array("keine Angabe", "langsam", "schnell"));

		/**
		 * LAYOUT
		 */
		$gui->insertSpaceAbove("DemoFeld4");

		
		$gui->setStandardSaveButton($this);
	
		return $ST.$T.$gui->getEditHTML();
	}

	public static function demoRME($p1, $p2){
		Red::alertD("Parameter1: $p1; Parameter2: $p2");
	}

	/**
	 * returns a HTML table with all known demo-entries
	 */
	public static function demoPopup(){
		$AC = new anyC();
		$AC->setCollectionOf("Demo");

		$T = new HTMLTable(1);
		while($D = $AC->getNextEntry()){
			$T->addRow(array($D->A("DemoFeld1")));
		}

		echo $T;
	}
}
?>