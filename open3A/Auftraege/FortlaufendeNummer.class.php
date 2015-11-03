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
 * k
 *  2007 - 2015, Rainer Furtmeier - Rainer@Furtmeier.IT
 */
class FortlaufendeNummer extends Auftrag implements iReNr {
	
	function getLabel(){
		return "Fortlaufende Nummer, z.B. 1,2,3,4,5,6,7...";
	}
	
	public static function getNextNumber($type){
		$_SESSION["BPS"]->setActualClass("mGRLBMGUI");
		$_SESSION["BPS"]->setACProperty("type",$type);
		$n = new mGRLBMGUI();
		
		$re_nr = $n->getIncrementedField("nummer");
		return $re_nr;
	}
}
?>
