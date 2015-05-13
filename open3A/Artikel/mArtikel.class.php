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
class mArtikel extends anyC {
	function __construct() {
		$this->setCollectionOf("Artikel");
		/*
		$this->setParser("preis","mArtikelGUI::numberParser");
		$this->setParser("EK1","mArtikelGUI::numberParser");
		$this->setParser("EK2","mArtikelGUI::numberParser");*/
		
		$this->setParser("preis","Util::CLNumberParserZ");
		$this->setParser("EK1","Util::CLNumberParserZ");
		$this->setParser("EK2","Util::CLNumberParserZ");
		$this->setParser("aufschlagListenpreis","Util::CLNumberParserZ");
		$this->setParser("aufschlagGesamt","Util::CLNumberParserZ");
	}

	
	public function getEtiketten(){
		$Artikel = anyC::get("Artikel");
		
		$array = array();
		while($A = $Artikel->getNextEntry())
			$array[] = array("ART".($A->getID() + 10000), $A->A("artikelnummer"), $A->A("name"));
		
		return $array;
	}
	
	public static function getBerichteDir(){
		return dirname(__FILE__);
	}
}
?>