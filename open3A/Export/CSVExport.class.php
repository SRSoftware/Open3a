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
class CSVExport extends UnifiedTable {
	private $spareNumerics = false;
	private $enclosedBy = "\"";
	private $separator = ";";
	
	public function spareNumerics($b){
		$this->spareNumerics = $b;
	}
	
	public function enclosedBy($char = "\""){
		$this->enclosedBy = $char;
	}
	
	public function separator($char = ";"){
		$this->separator = $char;
	}
	
	public function getExport($filename = null){
		$csv = "";

		if($this->header)
			$csv .= utf8_decode($this->enclosedBy.implode($this->enclosedBy.$this->separator.$this->enclosedBy, $this->header).$this->enclosedBy."$this->CSVNewline");


		foreach($this->content AS $v){
			foreach($v AS $key => $value){
				$value = utf8_decode(addslashes($value));
				
				if($this->enclosedBy == "")
					$value = str_replace($this->separator, " ", $value);
				
				if($this->spareNumerics AND is_numeric($value))
					$csv .= "$value$this->separator";
				else
					$csv .= "$this->enclosedBy$value$this->enclosedBy$this->separator";
			}
			
			$csv[strlen($csv) - 1] = " ";
			$csv = trim($csv);
			$csv .= $this->CSVNewline;
		}

		if($filename != null){
			header("Content-Type: text/plain;  charset=ISO-8859-1");
			header("Content-Disposition: attachment; filename=\"$filename\"");

			echo $csv;
		}

		else return $csv;
	}
}
?>
