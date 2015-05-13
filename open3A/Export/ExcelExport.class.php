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
class ExcelExport extends UnifiedTable {
	protected $worksheet;
	protected $workbook;
	protected $format_header;
	protected $format_bold;
	protected $format_right;
	protected $format_right_bold;
	protected $cellClass = array();

	public function  __construct($numCols = 0, $caption = null) {
		parent::__construct($numCols, $caption);

		$this->workbook = new Spreadsheet_Excel_Writer();

		$this->format_header = $this->workbook->addFormat();
		$this->format_header->setSize(15);
		$this->format_header->setBold();
		$this->format_header->setTextWrap(true);

		$this->format_bold = $this->workbook->addFormat();
		$this->format_bold->setBold();

		$this->format_right = $this->workbook->addFormat();
		$this->format_right->setAlign('right');

		$this->format_right_bold = $this->workbook->addFormat();
		$this->format_right_bold->setAlign('right');
		$this->format_right_bold->setBold();

	}

	public function getNewWorksheet($worksheetName = ""){
		$this->worksheet = $this->workbook->addWorksheet($worksheetName);

		return $this->worksheet;
	}

	public function writeWorksheet($worksheetName = "", $worksheet = null){

		if($worksheet == null)
			$this->worksheet = $this->workbook->addWorksheet($worksheetName);
		else
			$this->worksheet = $worksheet;

		foreach($this->colWidth AS $k => $v)
			$this->worksheet->setColumn($k - 1,$k - 1,$v);

		$line = 0;

		if($this->caption != null){
			$this->worksheet->setRow(0,50);
			$this->worksheet->setMerge(0,0,0,$this->numCols-1);

			$this->worksheet->write(0, 0, utf8_decode($this->caption), $this->format_header);

			$line++;
		}

		if($this->header != null){
			foreach($this->header AS $k => $v){
				$layoutClass = $this->format_bold;
				if(isset($this->colClass[$k + 1])){
					switch($this->colClass[$k + 1]){
						case "right":
						case "rightBold":
							$layoutClass = $this->format_right_bold;
						break;
					}
				}

				$this->worksheet->write($line, $k, utf8_decode($v), $layoutClass);
			}

			$line++;
		}

		foreach($this->content AS $k => $v){
			foreach($v AS $col => $content){
				$layoutClass = null;
				if(isset($this->colClass[$col + 1])){
					switch($this->colClass[$col + 1]){
						case "right":
							$layoutClass = $this->format_right;
						break;

						case "rightBold":
							$layoutClass = $this->format_right_bold;
						break;

						default:
							$tempName = $this->colClass[$col + 1];
							if(isset($this->$tempName))
								$layoutClass = $this->$tempName;
						break;
					}
				}

				if(isset($this->cellClass[$line]) AND isset($this->cellClass[$line][$col])){
					$tempName = $this->cellClass[$line][$col];
					if(isset($this->$tempName))
						$layoutClass = $this->$tempName;
				}

				$this->worksheet->write($line, $col, utf8_decode($content), $layoutClass);
			}

			$line++;
		}

		$this->content = array();
	}

	public function getExport($filename){
		if(count($this->content) > 0)
			$this->writeWorksheet();

		$this->workbook->send($filename);
		$this->workbook->close();
	}
}
?>
