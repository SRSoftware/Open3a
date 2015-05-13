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
class Kunde {
	
	protected $ID;
	
	function __construct($id){
		$this->ID = $id;
	}

	protected function createRechnung($type,$monat = 0){
		$O = new ObjekteGUI();
		$O->addAssocV3("rechnungAdresseID","=",$this->ID);
		$O->lCV3();
		
		$anzahlPosten = 0;
		while(($OO = $O->getNextEntry()))
			$anzahlPosten += $OO->loadPosten($type, $monat);//$OO->copyPostenToOGRLBM($OGRLBMID,"abruf");

		if($anzahlPosten == 0) return $anzahlPosten;
			
		$OGR = new OGRLBMsGUI();
		$OGRLBMID = $OGR->newOGRLBM("R",$type,$this->ID,$monat);
			
		$O->resetPointer();
		
		while(($OO = $O->getNextEntry()))
			$OO->copyPostenToOGRLBM($OGRLBMID,$type);
		
		return $anzahlPosten;
	}
	
	protected function createAbrufRechnung(){
		return $this->createRechnung("abruf");
	}
	
	protected function createMonatsRechnung($monat){
		return $this->createRechnung("monat",$monat);
	}
}
?>