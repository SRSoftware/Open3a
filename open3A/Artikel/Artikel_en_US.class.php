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

class Artikel_en_US implements iTranslation {
	public function getLabels(){
		return array(
		"name" => "Name",
		"mwst" => "VAT",
		"gebinde" => "Unit",
		"preis" => "Net price",
		"KategorieID" => "Category",
		"hideInReport" => "Hide?",
		"artikelnummer" => "Item number",
		"beschreibung" => "Description",
		"bemerkung" => "Note",
		"bild" => "Image",
		"EK1" => "PP1",
		"EK2" => "PP2");
	}
	
	public function getMenuEntry(){
		return "";
	}
	
	public function getLabelDescriptions(){
		return array(
		"bemerkung" => "intern");
	}
	
	public function getFieldDescriptions(){
		return array(
		"bemerkung" => "Will be displayed in the list on the right side",
		"hideInReport" => "Will the item be shown in a report?",
		"beschreibung" => "Will be displayed on the invoice");
	}
	
	public function getText(){
		return array(
		"bitte auswählen" => "please select...",
		"Lagerbestand anzeigen" => "show\nstock",
		"Lieferant anlegen" => "add\ndistributor",
		"Lagerbestand" => "Stock",
		"Stück singular" => "piece",
		"Stück plural" => "pieces",
		"kein Lieferant" => "no distributor",
		"Lieferant" => "distributor",
		"Lieferanten" => "distributors"/*,
		"Rechner" => "calculate net price from gross price"*/);
	}

	public function getSingular(){
		return "item";
	}
	
	public function getPlural(){
		return "items";
	}
	
	public function getEditCaption(){
		return "edit ".$this->getSingular();
	}
	
	public function getSaveButtonLabel(){
		return "save ".$this->getSingular();
	}
	
	public function getBrowserCaption(){
		return "Please select an ".$this->getSingular();
	}
	
	public function getSearchHelp(){
		$labels = $this->getLabels();
		return "These fields will be searched:<br /><br />".$labels["name"]."<br />".$labels["KategorieID"]."<br />".$labels["bemerkung"]."<br />".$labels["beschreibung"]."<br />".$labels["artikelnummer"]."</p><p>You may use UND to connect the search terms.<br/>E.g. \"".$labels["KategorieID"]." UND ".$labels["name"]."\"";
	}
	
	public function getBrowserNewEntryLabel(){
		return "create new ".$this->getSingular();
	}
}
?>