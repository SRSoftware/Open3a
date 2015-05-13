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
class KappendixCRMGUI extends Kappendix implements iGUIHTMLMP2 {
	
	public function setOwner($class, $id){
		BPS::setProperty(get_class($this), "ownerClassID", $id);
		BPS::setProperty(get_class($this), "ownerClass", $class);
	}
	
	public function getHTML($id, $page){
		$AID = BPS::getProperty(get_class($this), "ownerClassID");
		$K = Kappendix::getKappendixToAdresse($AID);
		$pSpecData = mUserdata::getPluginSpecificData("mAkquise");
		if($K == null){
			$B = new Button("Kundendaten\nanlegen", "new");
			$B->style("margin:10px;");
			$B->rmePCR("KappendixCRM", "-1", "createCustomerData", array("$AID"), OnEvent::reload("Left"));
			if(!Session::isPluginLoaded("Auftraege") OR isset($pSpecData["pluginSpecificCanSeeOnlyOwn"]))
				$B = "";
			
			return "<div style=\"min-height:500px;\">"."<p style=\"padding-left:10px;\">Zu dieser Adresse wurden noch keine Kundendaten angelegt.</p>$B</div>";
		}
		#$T = new HTMLTable(4, "Kundendaten");
		$widths = Aspect::joinPoint("changeWidths", $this, "CRMHTMLGUI::getEditHTML");
		if($widths == null) $widths = array(700, 132, 218);

		$T = new HTMLTable(4, "Kundendaten");
		$T->setTableStyle("width:$widths[0]px;margin-left:10px;");
		$T->setColWidth(1, "$widths[1]px");
		$T->setColWidth(2, "$widths[2]px");
		$T->setColWidth(3, "$widths[1]px");
		$T->setColWidth(4, "$widths[2]px");
		
		$T->addRow(array("<label>Kundennummer:</label>", $K->A("kundennummer"), "<label>USt-IdNr/St.Nr.:</label>", $K->A("UStIdNr")));
		
		$T->addRow(array("Kontodaten", "", "", ""));
		$T->addRowColspan(1, 4);
		$T->addRowClass("backgroundColor0");
		
		$T->addRow(array("<label>Kontoinhaber:</label>", $K->A("KappendixKontoinhaber"), "<label>Kontonummer:</label>", $K->A("KappendixKontonummer")));
		$T->addRow(array("<label>BLZ:</label>", $K->A("KappendixBLZ"), "<label>SWIFT/BIC:</label>", $K->A("KappendixSWIFTBIC")));
		$T->addRow(array("<label>Einzugserm.:</label>", Util::catchParser($K->A("KappendixEinzugsermaechtigung")), "", ""));
		
		$T->addRow(array("Kreditkartendaten", "", "", ""));
		$T->addRowColspan(1, 4);
		$T->addRowClass("backgroundColor0");
		
		$T->addRow(array("<label>Kreditkarte:</label>", $K->A("KappendixKreditkarte") != "" ? KappendixGUI::kreditkarten($K->A("KappendixKreditkarte")) : "keine", "<label>Kartennummer:</label>", $K->A("KappendixKartennummer")));
		$T->addRow(array("<label>Gültig bis:</label>", $K->A("KappendixKarteValidUntil"), "", ""));
		
		return "<div style=\"min-height:500px;\">".$T."</div>";
	}
	
	public function createCustomerData($AdresseID){
		$K = new Kunden();
		$K->createKundeToAdresse($AdresseID, false);
	}
	
	
}

?>