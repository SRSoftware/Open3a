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
class KundennummernBelegungGUI implements iGUIHTML2 {

	public function getHTML($id){
		$Ks = new mKappendix();
		$Ks->addAssocV3("kundennummer","IS","NOT NULL");
		$Ks->addOrderV3("kundennummer","ASC");
		$lueckenCounter = 0;
		$html = "
		<table>
			<colgroup>
				<col class=\"backgroundColor2\" />
				<col class=\"backgroundColor3\" />
			</colgroup>";
		while(($k = $Ks->getNextEntry())){
			if(!isset($oldKN)) $oldKN = $k->getA()->kundennummer;
			if($k->getA()->kundennummer - $oldKN > 1) {
				$lueckenCounter = $k->getA()->kundennummer - $oldKN  - 1;
				$firstFree = $oldKN + 1;
				$lastFree = $k->getA()->kundennummer - 1;
				$html .= "
			<tr>
				<td>".($firstFree).($lastFree != $firstFree ? " - ".($lastFree) : "")."</td><td><span style=\"color:red;\">".($lueckenCounter)." freie Nummer".($lueckenCounter == 1 ? "" : "n")."</span></td>
			<tr>
				<td class=\"backgroundColor0\"></td>
			</tr>";
			}
			
			$oldKN = $k->getA()->kundennummer;
		}
		$html .= "
			<tr>
				<td colspan=\"2\">letzte belegte Kundennummer: $oldKN</td>
			</tr>
		</table>";
		
		return $html;
	}
	
}
?>
