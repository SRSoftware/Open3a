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

class Vorlage_de_DE_Logo extends Vorlage_any implements iVorlage {
	function __construct($S = null){
		$this->positionDetailsAdresse = null;
		
		parent::__construct($S);
	}
	
	function getLabel(){
		return "de_DE; mit Logo";
	}
	
	function Header(){
		$img = Util::getRootPath()."specifics/Logo.jpg";
		if(!file_exists($img)){
			$this->setXY(10, 10);
			$this->SetFont("Arial", "", 10);
			$this->MultiCell8(0, 6, "Kopieren Sie Ihr Logo mit dem Dateinamen 'Logo.jpg' in das Verzeichnis\n'".Util::getRootPath()."specifics'\nund es wird automatisch mit einer Höhe von 3cm hier angezeigt.", 0, "R");
		} else {
			list($width, $height) = getimagesize($img);
			$this->Image($img,200 - (30 / $height * $width),5, 0, 30);
		}
		parent::Header();
	}
}
?>