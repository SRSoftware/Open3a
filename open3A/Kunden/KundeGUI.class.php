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
class KundeGUI extends Kunde implements iGUIHTML2 {
	
	function getHTML($id){
		$bps = BPS::getAllProperties(get_class($this));
		 
		$this->ID = $id = $bps["AdresseID"];
		
		$Adresse = new Adresse($id);
		
		$KApp = Kappendix::getKappendixToAdresse($this->ID);
		
		if($bps["action"] == "Kappendix" AND $KApp == null){
			$K = new Kunden();
			$K->createKundeToAdresse($id,false);
			
			$KApp = Kappendix::getKappendixToAdresse($this->ID);
		}
		
		$html = "<p>{$Adresse->getHTMLFormattedAddress()}</p>";
		
		
		if(isset($bps["mode"]) AND $bps["mode"] == "short") {
			$_SESSION["BPS"]->registerClass("KappendixGUI");
			$_SESSION["BPS"]->setACProperty("mode","short");
			$html = "";
		}
		
		if($bps["action"] == "Kappendix"){
			$K = new KappendixGUI($KApp->getID());
			return $html.$K->getHTML($id);
		}
		
		if($bps["action"] == "Kundenpreise"){
			if($KApp == null) {
				$html .= "
				<table>
					<tr>
						<td class=\"backgroundColor1\">Diesem Kunden wurde noch keine Kundennummer zugewiesen!</td>
					</tr>
				</table>";

				return $html;
			}
			BPS::setProperty("KundenpreisGUI", "kundennummer", $KApp->A("kundennummer"));
			
			$mKApp = new KundenpreiseGUI();
			$mKApp->setKundennummer($KApp->A("kundennummer"));
			$mKApp->setAdresseID($this->ID);
			$mKApp->setAssocV3("kundennummer","=",$KApp->A("kundennummer"));
			#$KApp = $mKApp->getNextEntry();
			/*if($KApp == null) {
				$K = new Kunden();
				$K->createKundeToAdresse($id);
				
				$mKApp = new mKappendixGUI();
				$mKApp->setAssocV3("AdresseID","=",$this->ID);
				$KApp = $mKApp->getNextEntry();
			}*/
			#echo "<pre>";
			#print_r($_SESSION["BPS"]);
			#echo "</pre>";
			return $mKApp->getHTML(-1,"","","");
		}
	}
	
	public function deleteMe(){
		$A = new Adresse($this->ID);
		$A->deleteMe();
		
		$mKApp = new mKappendixGUI();
		$mKApp->addAssocV3("AdresseID","=",$this->ID);
		$mKApp->lCV3();
		$KApp = $mKApp->getNextEntry();
		
		$KApp->deleteMe();
	}

	public function createAbrufRechnung(){
		echo parent::createAbrufRechnung();
	}
	
	public function createMonatsRechnung($monat){
		echo parent::createMonatsRechnung($monat);
	}
}
?>