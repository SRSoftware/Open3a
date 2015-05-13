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
class mStammdaten extends anyC {
	function __construct() {
		$this->setCollectionOf("Stammdaten");
	}
	
	/**
	 * @return Stammdaten
	 */
	public static function getActiveStammdaten(){
		$msd = new mStammdaten();
		$msd->setAssocV3("aktiv","=","1");
		$msd->lcV3();
		
		return $msd->getNextEntry();
	}
	
	public static function getReNrTemplate(){
		$Stammdaten = new mStammdaten();
		
		$Stammdaten->setAssocV3("aktiv","=","1");
		$Stammdaten->lCV3();
		
		$sd3 = $Stammdaten->getNextEntry();
		
		if($sd3 == null) die("error:AuftraegeMessages.E002");
		$sd3 = $sd3->getA();
		
		$reNrClass = "Auftrag";
		if($sd3->templateReNr != "" AND PMReflector::implementsInterface($sd3->templateReNr,"iReNr")) $reNrClass = $sd3->templateReNr;
		
		return $reNrClass;
	}
	
	public static function getNumberClass(){
		$nr = mStammdaten::getReNrTemplate();
		
		return new $nr(-1);
	}
	
	public static function getNextNumberFor($prefix){
		$nC = mStammdaten::getNumberClass();
		return $nC->getNextNumber($prefix);
	}
	
	public function activate($id){
		$this->addAssocV3("aktiv","=","1");
		while($t = $this->getNextEntry()){
			$t->changeA("aktiv","0");
			$t->saveMe();
		}
		
		$S = new Stammdaten($id);
		$S->changeA("aktiv","1");
		$S->saveMe();
	}
}
?>
