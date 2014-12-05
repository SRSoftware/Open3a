<?php
/**
 *  This file is part of ubiquitous.

 *  ubiquitous is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.

 *  ubiquitous is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses></http:>.
 * 
 *  2007 - 2014, Rainer Furtmeier - Rainer@Furtmeier.IT
 */

class mImportLightGUI extends anyC implements iGUIHTMLMP2 {

  private function onclick(){
    return "onclick=\"contentManager.rmePCR('Foo', 1, 'Foo', ['1'], function(transport){ Foo });\"";
  }

	public function getHTML($id, $page){
    return '
    <form id="importcsv">
      <input name="Datei" type="file" size="50" maxlength="100000" accept="text/*"><br/>
      <div '.$this->onclick().'>
        <img src="images/navi/up.png"/>Hochladen
      </div>
    </form>
	  ';	
	}


}
?>
