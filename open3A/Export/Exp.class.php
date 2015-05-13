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
class Exp extends PersistentObject implements iCloneable, iNewWithValues, iDeletable {
	public function cloneMe(){
		echo $this->newMe();
	}
	
	function newAttributes() {
		$A = parent::newAttributes();
		$A->filename = "export.txt";
		$A->sep = ",";
		$A->textSep = "\"";
		
		return $A;
	}

	function getExportData(){
		$felder = explode(",",$this->A("felder"));
		$felder = array_map("trim",$felder);

		$toExport = $this->A("plugin")."GUI";
		$toExport = new $toExport();

		$toExport->setFieldsV3($felder);

		list($sFields,$sOps,$sValues, $sLogOps) = $this->selectorsParser($this->A("selectors"));

		for($i=0;$i<count($sFields)-1;$i++)
			$toExport->addAssocV3($sFields[$i],$sOps[$i],$sValues[$i],$sLogOps[$i]);

		list($orderBy,$order) = $this->orderByParser($this->getA()->sort);
		for($i=0;$i<count($orderBy);$i++)
			if($i == 0) if($orderBy[$i] != "") $toExport->setOrderV3($orderBy[$i], $order[$i]);
			else if($orderBy[$i] != "") $toExport->addOrderV3($orderBy[$i], $order[$i]);

		$toExport->lCV3();

		$textSep = "";
		if($this->A("textSep") == "1") $textSep = "'";
		if($this->A("textSep") == "2") $textSep = "\"";

		$nl = "";
		if($this->A("newline") == "Windows") $nl = "\r\n";
		if($this->A("newline") == "Unix") $nl = "\n";
		if($this->A("newline") == "HTML") $nl = "<br />";

		$data = "";

		if($this->A("showID") == "1") $data.= $textSep.$this->A("plugin")."ID$textSep";
		for($i=0;$i<count($felder);$i++)
			$data .= (($i != 0 OR $this->A("showID") == "1") ? $this->A("sep") : "").$textSep.$felder[$i].$textSep;

		$data .= $nl;

		while(($tE = $toExport->getNextEntry())){
			if($this->A("showID") == "1") $data.= $textSep.$tE->getID().$textSep;
			for($i=0;$i<count($felder);$i++)
				$data .= (($i != 0 OR $this->A("showID") == "1") ? $this->A("sep") : "").$textSep.$tE->A($felder[$i]).$textSep;

			$data .= $nl;
		}

		return $data;
	}

	function selectorsParser($selectors){

		$mode = "field";

		$field = array("");
		$ops = array("");
		$values = array("");
		$logOps = array("AND","");

		$apoCounter = 0;

		for($i=0;$i<strlen($selectors);$i++){

			if($mode == "logOps"){
				if($selectors{$i} == " " AND $logOps[count($logOps)-1] == "") continue;
				if($selectors{$i} == " " AND $logOps[count($logOps)-1] != "") {
					$mode = "field";
					$logOps[] = "";
					continue;
				}

				$logOps[count($logOps)-1] .= $selectors{$i};
			}

			if($mode == "values"){
				if($selectors{$i} == "'" AND $selectors{$i-1} != "\\"){
					$apoCounter++;
					if($apoCounter == 2){
						$mode = "logOps";
						$values[] = "";
					}
					continue;
				}
				$values[count($values)-1] .= $selectors{$i};
			}

			if($mode == "ops"){
				if($selectors{$i} == " ") {
					$mode = "values";
					$ops[] = "";
					continue;
				}

				$ops[count($ops)-1] .= $selectors{$i};
			}

			if($mode == "field"){

				if($selectors{$i} == " ") {
					$mode = "ops";
					$field[] = "";
					continue;
				}

				$field[count($field)-1] .= $selectors{$i};
			}


		}
		return array($field, $ops, $values, $logOps);
	}

	function orderByParser($orderBy){
		$s = split(",",$orderBy);
		$s = array_map("trim",$s);

		$o = array();
		$b = array();

		foreach($s AS $key => $value){
			$s2 = explode(" ",$value);
			$o[] = $s2[0];
			$b[] = $s2[1];
		}
		return array($o, $b);
	}
}
?>
