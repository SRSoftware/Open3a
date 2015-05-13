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
class ExpsGUI extends Exps implements iGUIHTML2 {
	public function getHTML($id){

		$FB = new FileBrowser();
		$FB->addDir(dirname(__FILE__));
		$FB->addDir(Util::getRootPath()."specifics");

		while($return = Registry::callNext("Export", "directory"))
			$FB->addDir($return);
		
		#if(isset($_SESSION["phynx_exporteOrdner"]) AND count($_SESSION["phynx_exporteOrdner"]) > 0)
		#	foreach($_SESSION["phynx_exporteOrdner"] AS $k => $v)
		#		$FB->addDir($v);

		$exports = $FB->getAsLabeledArray("iExport",".class.php");

		#print_r($exports);

		$T = new HTMLTable(2, "Exporte");
		$T->addColStyle(1, "width:20px;");

		foreach($exports AS $k => $v){
			$c = new $v();
			
			if(!in_array(Applications::activeApplication(), $c->getApps()))
				continue;
			
			$B = new Button("", "./images/i2/edit.png", "icon");
			$B->className("editButton");
			$B->onclick("contentManager.loadFrame('contentLeft','$v');");

			$T->addRow(array($B,$k));
		}

		$gui = new HTMLGUI();
		$gui->VersionCheck("Exps");
		if($this->A == null) $this->lCV3($id);
		
		$gui->setName("Export");
		if($this->collector != null) $gui->setAttributes($this->collector);
		
		$gui->setShowAttributes(array("name"));
		$gui->setCollectionOf($this->collectionOf,"Export");
		
		$gui->setParser("name","ExpsGUI::nameParser",array("\$aid"));
		
		try {
			return ($id == -1 ? $T.HTMLGUIX::tipJS("Exps") : "");#.$gui->getBrowserHTML($id);
		} catch (Exception $e){ }
	}
	
	public static function nameParser($w,$l,$p){
		return "<img style=\"float:right;\" src=\"./images/i2/export.png\" onclick=\"makeExport('$p');\" class=\"mouseoverFade\" />$w";
	}
	
	
	function getExport($id){
		$Export = new Exp($id);
		
		$data = $Export->getExportData();
		
		header("Content-Type: text/plain;  charset=".strtolower($Export->getA()->kodierung));
		header("Content-Disposition: attachment; filename=\"".$Export->getA()->filename."\"");
		echo ($Export->getA()->kodierung == "UTF-8" ? $data : utf8_decode($data));
	}
}
?>
