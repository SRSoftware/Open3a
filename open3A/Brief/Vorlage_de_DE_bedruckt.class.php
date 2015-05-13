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

class Vorlage_de_DE_bedruckt extends Vorlage_any implements iVorlage {
	function getLabel(){
		return "de_DE; für bedrucktes Papier";
	}
	
	function Header(){
		$this->SetFont(self::DEFAULT_FONT, '', 8);
		if($this->PageNo() == 1){
			$this->SetXY(20,50);
			$text = utf8_decode(($this->sd->firmaKurz != "" ? $this->sd->firmaKurz : $this->sd->vorname." ".$this->sd->nachname).", ".$this->sd->strasse." ".$this->sd->nr.", ".$this->sd->plz." ".$this->sd->ort);
			$this->Cell(100 , 5 , $text,"",1);
			$this->Line(20,54,$this->GetStringWidth($text) + 22,54);
		}
		$this->SetXY(20,35);
	}

	function Footer(){
	
	}
}
?>