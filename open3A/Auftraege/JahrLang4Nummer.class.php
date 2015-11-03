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
class JahrLang4Nummer extends Auftrag implements iReNr {

	function getLabel(){
		return "Jahr4Nummer, z.B. 20080001";
	}
	
	public static function getNextNumber($type){
		$jahr = date("Y");
		$startNumber = $re_nr = $jahr."0001";
		
		$_SESSION["BPS"]->setActualClass("mGRLBMGUI");
		$_SESSION["BPS"]->setACProperty("type", $type);
		$n = new mGRLBMGUI();
		
		$re_nr = $n->getIncrementedField("nummer");
		if($re_nr - 1 < $startNumber) $re_nr = $startNumber;
		if($re_nr == 1) $re_nr = $startNumber;
		
		if($jahr < 10) $re_nr = "0".$re_nr;
		
		$re_nr2 = $re_nr."";
		if($jahr > $re_nr2{0}.$re_nr2{1}.$re_nr2{2}.$re_nr2{3}) $re_nr = $startNumber;
		return $re_nr;
	}
}
?>
