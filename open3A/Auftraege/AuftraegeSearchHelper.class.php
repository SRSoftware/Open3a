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

class AuftraegeSearchHelper extends anyC {
	function __construct(){
		$this->setCollectionOf("Auftrag");
		$this->addJoinV3("Adresse","AdresseID","=","AdresseID");
		$this->addJoinV3("GRLBM","AuftragID","=","AuftragID");
		
		$this->setLimitV3("10");
		$this->addOrderV3("auftragDatum","DESC");
		$fields = array("nachname","datum","firma","nummer"/*,"username"*/,"vorname", "t1.UserID", "GRLBMID", "AuftragAdresseNiederlassungID", "AuftragAdresseNiederlassungData");
		$this->setFieldsV3($fields);

		$this->customize();
	}
	
	function getCollector(){
		return $this->collector;
	}
}
?>