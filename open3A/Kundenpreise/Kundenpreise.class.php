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
class Kundenpreise extends anyC {
	function __construct() {
		$this->setCollectionOf("Kundenpreis");
		$this->addJoinV3("Artikel","ArtikelID","=","ArtikelID");
		$this->setParser("kundenPreis","Util::CLNumberParserZ");
	}

	public static function getKundenpreisFor($ArtikelID, $kundennummer, $VarianteID = 0){
		$KP = anyC::get("Kundenpreis");
		$KP->addJoinV3("Artikel","ArtikelID","=","ArtikelID");
		#$KP = new Kundenpreise();
		$KP->addAssocV3("t1.kundennummer", "=", $kundennummer);
		$KP->addAssocV3("t1.ArtikelID", "=", $ArtikelID);
		$KP->addAssocV3("KundenpreisVarianteArtikelID", "=", $VarianteID);
		$K = $KP->getNextEntry();

		if($K == null) return null;

		return $K->A("kundenPreis");
	}
}
?>
