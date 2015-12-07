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

 *   JahrMonat2Nummer.class.php by www.hc-media.org / office@hc-media.org
 */

class BelegnummernEditor extends Auftrag implements iReNr {

	function getLabel() {
		return "Belegnummerneditor";
	}

	public static function getNextNumber($type, Auftrag $Auftrag = null) {
		$S = Stammdaten::getActiveStammdaten();
		#$DB = new DBStorage();
		#$C = $DB->getConnection();
		
		switch ($S->A("belegNummerResetR")) {
			case "":
				#$C->query("")
				$next = mUserdata::getGlobalSettingValue("belegNummerNext$type", 1);
				
				$nummer = self::replace($S, $next, false, $Auftrag);
				
				mUserdata::setUserdataS("belegNummerNext$type", ++$next, "belegNummer", -1);
				
				return $nummer;
			break;
			
			case "yearly":
			case "monthly":
				$next = mUserdata::getGlobalSettingValue("belegNummerNext$type", 1);
				
				
				$test = self::replace($S, 1, $S->A("belegNummerResetR") == "yearly", $Auftrag);
				$AC = anyC::get("GRLBM");
				if(strpos("RLGBMA", $type) !== false)
					$AC->addAssocV3("is$type", "=", "1");
				else
					$AC->addAssocV3("isWhat", "=", $type);
				$AC->addAssocV3("nummer", "LIKE", $test);
				$AC->setFieldsV3(array("nummer"));
				
				
				$E = $AC->getNextEntry();
				if($E == null)
					$next = 1;
				
				$nummer = $test = self::replace($S, $next, false, $Auftrag);
				
				
				mUserdata::setUserdataS("belegNummerNext$type", ++$next, "belegNummer", -1);
				
				return $nummer;
			break;
			
		}
		
		return;
	}
	
	private static function replace($S, $next, $wildcardMonth = false, Auftrag $Auftrag = null){
		$replace = array(
			"{J}" => date("Y"),
			"{J2}" => date("y"),
			"{T}" => str_pad(date("z"), 3, "0", STR_PAD_LEFT),
			"{M}" => $wildcardMonth ? "%" : date("m"),
			"{M1}" => $wildcardMonth ? "%" : date("m") * 1
		);
		
		if($Auftrag != null)
			$replace["{K}"] = $Auftrag->A("kundennummer") > 0 ? $Auftrag->A("kundennummer") : 0;

		$nummer = $S->A("belegNummerFormatR");
		foreach($replace AS $k => $v)
			$nummer = str_replace($k, $v, $nummer);

		$useNext = $next;
		preg_match("/\{N:([0-9]+)\}/", $nummer, $matches);
		if(isset($matches[1])){
			$useNext = str_pad($next, $matches[1], "0", STR_PAD_LEFT);
			$nummer = str_replace($matches[0], $useNext, $nummer);
		}
		
		return $nummer;
	}

}

?>