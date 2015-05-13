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

class Artikel_de_DE implements iTranslation {
	public function getLabels(){
		return array(
		"name" => "Name",
		"mwst" => "MwSt",
		"gebinde" => "Einheit",
		"preis" => "Nettopreis",
		"KategorieID" => "Kategorie",
		"hideInReport" => "verstecken?");
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
		"bemerkung" => "Wird in der Liste rechts angezeigt",
		"hideInReport" => "Soll der Artikel in einem Bericht nicht angezeigt werden?",
		"beschreibung" => "Wird auf den Belegen angezeigt");
	}
	
	public function getText(){
		return array(
		"bitte auswählen" => "bitte auswählen",
		"Lagerbestand anzeigen" => "Lagerbestand\nanzeigen",
		"Lieferant anlegen" => "Lieferant\nanlegen",
		"Lagerbestand" => "Lagerbestand",
		"Stück singular" => "Stück",
		"Stück plural" => "Stück",
		"kein Lieferant" => "kein Lieferant",
		"Lieferant" => "Lieferant",
		"Lieferanten" => "Lieferanten"/*,
		"Rechner" => "Brutto- in Nettopreis umrechnen"*/);
	}

	public function getSingular(){
		return "Artikel";
	}
	
	public function getPlural(){
		return "Artikel";
	}

	public function getSearchHelp(){
		return "";
	}
	
	public function getEditCaption(){
		return $this->getSingular()." editieren";
	}
	
	public function getSaveButtonLabel(){
		return $this->getSingular(). " speichern";
	}
	
	public function getBrowserCaption(){
		return "Bitte ".$this->getSingular()." auswählen";
	}
	
	public function getBrowserNewEntryLabel(){
		return "".$this->getSingular()." neu anlegen";
	}
}
?>