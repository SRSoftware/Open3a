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
class XLSXExport extends UnifiedTable {
	protected $worksheet;
	protected $workbook;
	protected $format_header;
	protected $format_bold;
	protected $format_right;
	protected $format_right_bold;
	protected $cellClass = array();
	protected $PE;
	public function  __construct($numCols = 0, $caption = null) {
		parent::__construct($numCols, $caption);

		require_once Util::getRootPath()."ubiquitous/Excel/PHPExcel/PHPExcel.php";
		
		$this->PE = new PHPExcel();
		$this->PE->setActiveSheetIndex(0);
		$this->PE->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
		$this->worksheet = $this->PE->getActiveSheet();
	}

	#public function getNewWorksheet($worksheetName = ""){
		#$this->worksheet = $this->workbook->addWorksheet($worksheetName);

		#return $this->worksheet;
	#}

	private $regColClasses = array();
	public function registerColClass($name, $values){
		$this->regColClasses[$name] = $values;
	}

	private $regCellClasses = array();
	public function registerCellClass($name, $values){
		$this->regCellClasses[$name] = $values;
	}
	
	private $regRowClasses = array();
	public function registerRowClass($name, $values){
		$this->regRowClasses[$name] = $values;
	}
	
	public function writeWorksheet($worksheetName = "", $worksheet = null){

		
		foreach($this->colWidth AS $k => $v)
			$this->worksheet->getColumnDimensionByColumn($k - 1)->setWidth($v);
		
		$line = 1;
		if($this->caption != null){
			#$this->worksheet->setRow(0,50);
			$this->worksheet->mergeCellsByColumnAndRow(0,1,$this->numCols-1,1);

			#$this->worksheet->write(0, 0, utf8_decode($this->caption), $this->format_header);
			$this->worksheet->setCellValueByColumnAndRow(0, 1, $this->caption);
			$this->worksheet->getStyleByColumnAndRow(0, 1)->getFont()->setSize(15)->setBold(true);
			$this->worksheet->getRowDimension(1)->setRowHeight(50);
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
				
				$this->worksheet->setCellValueByColumnAndRow($k, $line, $v);
				$this->worksheet->getStyleByColumnAndRow($k, $line)->getFont()->setBold(true);
			}

			$line++;
		}
		
		foreach($this->content AS $k => $v){
			foreach($v AS $col => $content){
				/*$layoutClass = null;
				if(isset($this->colClass[$col + 1])){
					switch($this->colClass[$col + 1]){
						case "right":
							#$layoutClass = $this->format_right;
							$this->worksheet->getStyleByColumnAndRow($col, $line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
						break;

						case "rightBold":
							$layoutClass = $this->format_right_bold;
						break;

						default:
							$tempName = $this->colClass[$col + 1];
							if(isset($this->regColClasses[$tempName]))
								$this->worksheet->getStyleByColumnAndRow($col, $line)->applyFromArray($this->regColClasses[$tempName]);
						break;
					}
				}

				if(isset($this->cellClass[$line]) AND isset($this->cellClass[$line][$col])){
					$tempName = $this->cellClass[$line][$col];
					if(isset($this->$tempName))
						$layoutClass = $this->$tempName;
				}*/
				#echo "<pre>";
				#print_r($this->cellClasses);
				#echo "</pre>";
				#die();
				#echo $line.":$col"."<br />";
				
				if(isset($this->rowClasses[$k])){
					$tempName = trim($this->rowClasses[$k]);
					
					if(isset($this->regRowClasses[$tempName]))
						$this->worksheet->getStyleByColumnAndRow($col, $line)->applyFromArray($this->regRowClasses[$tempName]);
					
				}
				
				
				if(isset($this->cellClasses[$k]) AND isset($this->cellClasses[$k][$col])){
					
					$tempName = $this->cellClasses[$k][$col];
					if(isset($this->regCellClasses[$tempName]))
						$this->worksheet->getStyleByColumnAndRow($col, $line)->applyFromArray($this->regCellClasses[$tempName]);
					
				}
				
				$this->worksheet->setCellValueByColumnAndRow($col, $line, $content);
				#$this->worksheet->write($line, $col, utf8_decode($content), $layoutClass);
			}
			
			$line++;
		}
		#die();
		
	}

	public function getExport($filename){
		if(count($this->content) > 0)
			$this->writeWorksheet();
		
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header("Pragma: no-cache");
		
		$objWriter = new PHPExcel_Writer_Excel2007($this->PE);
		$objWriter->save('php://output');
	}
}
?>
