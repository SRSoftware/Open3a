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

class Vorlage_en_US_leer extends Vorlage_de_DE_leer implements iVorlage {
	function __construct($S = null){
		$this->labelTelefon = "Phone";
		$this->labelHandy = "Mobile";
		
		$this->labelRechnung = "Invoice";
		$this->labelGutschrift = "Credit memo";
		$this->labelAngebot = "Quotation";
		$this->labelLieferschein = "Shipping invoice";
		$this->labelKalkulation = "Calculation";
		
		$this->labelDatum = "Date";
		$this->labelLieferdatum = "Delivery date";
		$this->labelKundennummer = "Customer number";
		
		$this->labelMenge = "Quantity";
		$this->labelEinheit = "Unit";
		$this->labelBezeichnung = "Description";
		$this->labelEinzelpreis = "Amount";
		$this->labelGesamt = "Total";
		
		$this->labelGesamtNetto = "Subtotal";
		$this->labelUmsatzsteuer = "Tax total";
		$this->labelRechnungsbetrag = "Total amount due";
		$this->labelGutschriftsbetrag = "Total amount";
		
		$this->labelSeite = "Page";
		
		$this->language = "en_US";
		
		$this->positionFirmaSchriftzug = array(20, 12);
		
		parent::__construct($S);
	}
	
	function getLabel(){
		return "en_US; für leeres Papier (Übersetzung unvollständig)";
	}
}
?>