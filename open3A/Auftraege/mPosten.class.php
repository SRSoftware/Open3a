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
class mPosten extends anyC {
	private $letterType = "";
	#public $postenButtons = "";
	public $CustomizerPostenSort = false;
	public static $positions = array(0);
	public static $positionsCurrentGRLBMID = 0;
	
	function __construct() {
		$this->customize();

		$this->setCollectionOf("Posten");
		if($this->CustomizerPostenSort)
			$this->addOrderV3("PostenSortOrder");
		$this->addOrderV3("PostenID");
	}
	
	public function setLetterType($type){
		$this->letterType = $type;
	}
	
	function getCollector(){
		return $this->collector;
	}
	
	protected function getPositionsNummern(){
		$nummern = array();
		
		while($t = $this->getNextEntry()){
			if($t->A("GRLBMID") != self::$positionsCurrentGRLBMID){
				self::$positionsCurrentGRLBMID = $t->A("GRLBMID");
				self::$positions = array(0);
			}

			$ZS = false;
			try {
				if(defined("PHYNX_VIA_INTERFACE") AND !class_exists("CustomizerPostenZwischensummeGUI", false))
					throw new ClassNotFoundException("CustomizerPostenZwischensummeGUI");
				
				$C = new CustomizerPostenZwischensummeGUI(null, false);
				$ZS = true;
			} catch (ClassNotFoundException $e){

			}
			
			if($t->A("PostenAddLine") != "" AND $ZS){
				if(strpos($t->A("PostenAddLine"), "[") === 0){
					$titles = json_decode($t->A("PostenAddLine"));
					foreach($titles AS $T){
						if($T->label == "")
							continue;
						
						#$step = 1;
						#if(isset($T->step))
						#	$step = $T->step;
						
						if(isset($T->level) AND $T->level != 0)
							while(count(self::$positions) > $T->level)
								array_pop (self::$positions);
							
						
						#self::$positions[count(self::$positions) - 1] += $step;
						if(isset($T->number) AND $T->number != "")
							self::$positions[count(self::$positions) - 1] = $T->number;
						else
							self::$positions[count(self::$positions) - 1]++;
						
						self::$positions[] = 0;
					}
				} else {
					self::$positions[count(self::$positions) - 1]++;
					self::$positions[] = 0;
				}
			}

			self::$positions[count(self::$positions) - 1]++;

			$nummern[$t->getID()] = implode(".", self::$positions);
			
			if($t->A("PostenAddSum") != "" AND count(self::$positions) > 1){
				if($t->A("PostenAddSum") == "1")
					array_pop(self::$positions);
				if($t->A("PostenAddSum") > 1)
					for($i = 0; $i < $t->A("PostenAddSum"); $i++)
						array_pop(self::$positions);
			}
				
		}
		
		$this->resetPointer();
		
		return $nummern;
	}
	
	/*public static function numberParser($w,$l){
		if($l == "load") return number_format($w, 2, ",", "");
		if($l == "store") return str_replace(",",".",$w);
	}*/

	function cloneAllToGRLBM($GRLBMID){
		$this->addAssocV3("GRLBMID","=",$GRLBMID);
		
		while($t = $this->n())
			$t->cloneMe();
	}
	
	function addVirtualPosten($menge, $einheit, $bezeichnung, $beschreibung, $preis, $mwst, $isBrutto, $bruttopreis, $artikelnummer = ""){
		if($this->collector == null)
			$this->lCV3();
		
		$P = new Posten(-1);
		$PA = $P->newAttributes();
		$PA->name = $bezeichnung;
		$PA->gebinde = $einheit;
		$PA->preis = $preis;
		$PA->menge = $menge;
		$PA->mwst = $mwst;
		$PA->beschreibung = $beschreibung;
		$PA->isBrutto = $isBrutto;
		$PA->bruttopreis = $bruttopreis;
		$PA->artikelnummer = $artikelnummer;
		
		$P->setA($PA);
		$this->collector[] = $P;
	}

	public function getFPDF($fpdf, GRLBM $GRLBM){
		if($this->letterType == "M")
			return;
		
		$userHiddenFields = mUserdata::getHides("Artikel");
		
		$fpdf->gesamtEK1 = 0;
		$fpdf->gesamtEK2 = 0;
		$fpdf->gesamt_netto = array();
		$fpdf->gesamt_brutto = 0;
		
		#$fpdf->positionPreis = "start";
				
		$positionsNummern = $this->getPositionsNummern();
		$i = 0;
		while($PC = $this->getNextEntry()){

			$A = $PC->getA();
			
			if(!$fpdf->showPositionen){
				$PC->calcPrices($fpdf);
				continue;
			}
			
			if($i == 0 AND (!isset($A->PostenNewPage) OR $A->PostenNewPage == "0"))
				$fpdf->printPDFHeader();

			if(isset($A->PostenNewPage) AND $A->PostenNewPage == "1")
					$fpdf->AddPage();
			
			
			Aspect::joinPoint("abovePosten", $this, __METHOD__, array($fpdf, $PC, $i));
			
			$yImage = null;
			$fpdf->currentArticle = $PC;
			$im = null;
			if($A->bild != "" AND strpos($fpdf->showImagesOn, $this->letterType) !== false){
				$im = imagecreatefromstring(DBImageGUI::getData($A->bild));
				$ratio = imagesx($im) / imagesy($im);
				$imHeight = $fpdf->widthPositionBild / $ratio;
				
				$yImage = $fpdf->GetY() + $imHeight + 10 + 5; //+5 because of first line of text in posten
				
				$fpdf->currentArticle = null;
				if($yImage > $fpdf->h - $fpdf->GetMargin("B")){
					$fpdf->AddPage();
					$yImage = $fpdf->GetY() + $imHeight + 10 + 5;
				}
				$fpdf->currentArticle = $PC;
			}
			
			if($i != 0)
				$fpdf->SetXY($fpdf->GetX(),$fpdf->GetY() + 2);

			$fpdf->SetTextColorArray($fpdf->colorPositionen);
			
			
			Aspect::joinPoint("front", $this, __METHOD__, array($fpdf, $PC, $i));
			
			$yTop = $fpdf->GetY();
			$x = $fpdf->GetX();
			$yBot = array();
			$yBezeichnung = 0;
			foreach($fpdf->orderCols AS $col){
				$width = "width$col";
				if(!$fpdf->$width)
					continue;
				
				#$fpdf->SetXY($x, $yTop);
				$fpdf->SetX($x);#, $yTop);
				$fpdf->SetFont($fpdf->fontPositionen[0], $fpdf->fontPositionen[1], $fpdf->fontPositionen[2]);
				
				if($col != "Bezeichnung"){
					$tempX = $fpdf->GetX();
					$tempY = $fpdf->GetY();
					$fpdf->SetXY($x, $yTop);
				}
				
				if($this->letterType != "Kalk"){
					if($col == "Position")
						$fpdf->Cell8($fpdf->$width, $fpdf->heightPositionenHeader, Aspect::joinPoint("alterPosition", $this, __METHOD__, array($positionsNummern[$PC->getID()], $A), $positionsNummern[$PC->getID()]), 0, 0, $fpdf->alignPosition);

					if($col == "Einheit")
						$fpdf->Cell8($fpdf->$width, $fpdf->heightPositionenHeader, $A->gebinde, 0, 0, $fpdf->alignEinheit);

					if($col == "Artikelnummer")
						$fpdf->Cell8($fpdf->$width, $fpdf->heightPositionenHeader, $A->artikelnummer, 0, 0, "L");
				}
				
				if($col == "Menge")
					$fpdf->Cell8($fpdf->$width, $fpdf->heightPositionenHeader, Util::formatNumber($fpdf->language, $A->menge * 1, $fpdf->showDezimalstellenMenge, true, false), 0, 0, $fpdf->alignMenge);
				
				if($col == "Menge2")
					$fpdf->Cell8($fpdf->$width, $fpdf->heightPositionenHeader, Aspect::joinPoint("menge2", $this, __METHOD__, array($A), $A->menge2), 0, 0, $fpdf->alignMenge2);
				
				if($col != "Bezeichnung")
					$fpdf->SetXY($tempX, $tempY);
				
			
				if($col == "Bezeichnung"){
					$fpdf->isInPostenBezeichnung = true;
					$fpdf->SetFont($fpdf->fontPositionenArtikelname[0], $fpdf->fontPositionenArtikelname[1], $fpdf->fontPositionenArtikelname[2]);
					$fpdf->MultiCell8($fpdf->$width, $fpdf->heightPositionenHeader, $fpdf->cur($A->name), 0, "L");
					$yBezeichnung = $x;
					$fpdf->isInPostenBezeichnung = false;
				}
				
				$x += $fpdf->$width;
				
				$newY = $fpdf->getY() + $fpdf->heightPositionenHeader;
				if($col == "Bezeichnung")
					$newY = $fpdf->getY();
				
				$yBot[] = $newY;
			}
			
			if($fpdf->positionPreis == "start"){
				$yaNStart2 = $fpdf->getY();
				$fpdf->setXY($x, $yTop);
				Aspect::joinPoint("tail", $this, __METHOD__, array($fpdf, $PC, $i));
				$this->printPrices($fpdf, $PC);
				$fpdf->setXY($x, $yaNStart2);
			}

			$yBot2 = $yBot;
			#$yImage = 0;
			if($A->bild != "" AND strpos($fpdf->showImagesOn, $this->letterType) !== false){
				$fpdf->ImageGD($im, $fpdf->positionPositionBild, max($yBot), $fpdf->widthPositionBild, $imHeight);
				$yBot2[] = max($yBot) + $imHeight;
				#$yImage = max($yBot) + $imHeight;
			}
			
			$startPage = $fpdf->PageNo();
			$yBeschreibung = 0;
			if($A->beschreibung != "" AND $fpdf->widthBezeichnung AND $yBezeichnung) {
				$fpdf->isInPostenBeschreibung = true;
				$fpdf->SetFont($fpdf->fontPositionenBeschreibung[0], $fpdf->fontPositionenBeschreibung[1], $fpdf->fontPositionenBeschreibung[2]);
				
				#$fpdf->SetXY($yBezeichnung, max($yBot));
				$fpdf->SetX($yBezeichnung);
				$fpdf->MultiCell8($fpdf->widthBezeichnung, $fpdf->heightPositionenBeschreibung, $fpdf->cur($A->beschreibung),0,"L",0);
				$fpdf->SetFont($fpdf->fontPositionen[0], $fpdf->fontPositionen[1], $fpdf->fontPositionen[2]);
				$yBot2[] = $fpdf->getY();
				$fpdf->isInPostenBeschreibung = false;
				$yBeschreibung = $fpdf->getY();
			}
			

			if($fpdf->positionPreis == "end"){
				#$fpdf->setXY($x, max($yBot2) - 5);
				$fpdf->SetXY($x, $fpdf->GetY() - 5);
				#$fpdf->MultiCell8(0, 5, print_r($yBot, true));
				$this->printPrices($fpdf, $PC);
			}

			if($fpdf->positionPreis == "start")
				#$fpdf->setXY($x, max($yBot2) - 5); //Fix space between articles
				$fpdf->SetXY($x, $fpdf->GetY() - 5); //Fix space between articles

			#if($this->letterType == "L")
			#	$fpdf->Cell(0,5,"WHAT!?",0,0,"R");
			
			
			
			if($this->letterType == "Kalk"){
				if($fpdf->widthEK1)
					$fpdf->Cell8($fpdf->widthEK1, 5, (!isset($userHiddenFields["EK1"]) ? $fpdf->cur($fpdf->formatCurrency($fpdf->language,$A->EK1 * 1,$fpdf->showPositionenWaehrung)) : ""), 0, 0, "R");
					
				if($fpdf->widthEK2)
					$fpdf->Cell8($fpdf->widthEK2, 5, (!isset($userHiddenFields["EK2"]) ? $fpdf->cur($fpdf->formatCurrency($fpdf->language,$A->EK2 * 1,$fpdf->showPositionenWaehrung)) : ""), 0, 0, "R");
				
				if($fpdf->widthVK)
					$fpdf->Cell8($fpdf->widthVK, 5, $fpdf->cur($fpdf->formatCurrency($fpdf->language, $A->preis  * 1, $fpdf->showPositionenWaehrung)), 0, 0, "R");
				
				$fpdf->gesamtEK1 += $A->EK1 * $A->menge;
				$fpdf->gesamtEK2 += $A->EK2 * $A->menge;
			}
			
			$next = null;
			if(isset($this->collector[$this->i]))
				$next = $this->collector[$this->i];
			

			$fpdf->ln($fpdf->abstandPositionen);
			Aspect::joinPoint("belowPosten", $this, __METHOD__, array($fpdf, $PC, $next));
			
			if($yImage != null AND $yImage > $yBeschreibung AND $fpdf->PageNo() == $startPage)
				$fpdf->SetY($yImage);
				#$fpdf->ln(10);
				

			$fpdf->currentArticle = null;
			if($fpdf->GetY() > $fpdf->h - $fpdf->marginBottom - 10){
				$fpdf->ln(2);
				$fpdf->AddPage();
			}

			$i++;
		}
		$fpdf->ln(1);
		
		if($this->numLoaded() > 0 AND $this->letterType == "R" AND $GRLBM->A("versandkosten") != 0 AND $fpdf->positionVersandkosten == "above"){

			$parsedMwSt = $GRLBM->A("versandkostenMwSt");
			$mwstFaktor = 1 + $parsedMwSt / 100;
			
			$fpdf->addPriceToSum($GRLBM->A("versandkosten"), $GRLBM->A("versandkostenMwSt"));

			$fpdf->SetDrawColor(100, 100, 100);
			$fpdf->Line($fpdf->GetMargin("L") , $fpdf->getY(), 210-$fpdf->GetMargin("R") , $fpdf->getY());
			$fpdf->SetDrawColor(0, 0, 0);
			$fpdf->Cell($x - $fpdf->getLeftMargin(), 5, "", 0, 0, "L");
			$fpdf->Cell8($fpdf->widthBezeichnung, 5, $fpdf->labelVersandkosten, 0, 0, "L");
			$fpdf->Cell8($fpdf->widthEinzelpreis, 5, $fpdf->cur($fpdf->formatCurrency($fpdf->language, $GRLBM->getA()->versandkosten * 1 * ($fpdf->showBruttoPreise ? $mwstFaktor : 1), true)), 0, 0, "R");
			$fpdf->Cell8($fpdf->widthGesamt, 5, $fpdf->cur($fpdf->formatCurrency($fpdf->language, $GRLBM->getA()->versandkosten * 1 * ($fpdf->showBruttoPreise ? $mwstFaktor : 1), true)), 0, 0, "R");
			$fpdf->ln();
		}
		
		$fpdf->ln(2);
		$fpdf->Line($fpdf->GetMargin("L") , $fpdf->getY(), 210-$fpdf->GetMargin("R") , $fpdf->getY());
		$fpdf->printGesamt($this->letterType);
	}

	private function printPrices($fpdf, $Posten){
		$A = $Posten->getA();
		$prices = $Posten->calcPrices($fpdf);

		$priceCols = $fpdf->orderColsPrice;
		
		$menge2 = $A->menge2 != 0 ? $A->menge2 : 1;
		
		#if($this->letterType == "L") return;
		if(in_array($this->letterType, $fpdf->sumHideOn))
			return;
		
		$nettoPreis = $prices["nettoPreis"];
		$bruttoPreis = $prices["bruttoPreis"];
		$rabattBetrag = $prices["rabattBetrag"];
		$mwstBetrag = $prices["mwstBetrag"];
		$mwstFaktor = $prices["mwstFaktor"];
		
		$dispEinzelpreis = $nettoPreis + $rabattBetrag;
		
		$einzelpreis = $nettoPreis;
		
		if($fpdf->showBruttoPreise){
			$einzelpreis = $bruttoPreis;
			$dispEinzelpreis = ($nettoPreis + $rabattBetrag) * $mwstFaktor;
		}

		if($this->letterType != "Kalk"){
			$fpdf->SetFont($fpdf->fontPositionenPreise[0], $fpdf->fontPositionenPreise[1], $fpdf->fontPositionenPreise[2]);

			$priceColsContent = array(
				"widthEinzelpreis" => (!(!$fpdf->showNullPreise AND $dispEinzelpreis == 0)) ? $fpdf->formatCurrency($fpdf->language, $dispEinzelpreis, $fpdf->showPositionenWaehrung, $fpdf->showDezimalstellen) : "",
				"widthEinzelpreisNetto" => (!(!$fpdf->showNullPreise AND $nettoPreis == 0)) ? $fpdf->formatCurrency($fpdf->language, $nettoPreis, $fpdf->showPositionenWaehrung, $fpdf->showDezimalstellen) : "",
				"widthRabatt" => ((isset($A->rabatt) AND $A->rabatt * 1 != 0) ? Util::formatNumber($fpdf->language, $A->rabatt * 1, 2)."%" : ""),
				"widthRabattpreis" => $fpdf->formatCurrency($fpdf->language, $einzelpreis, true),
				"widthGesamtNettoPosten" => (!(!$fpdf->showNullPreise AND $A->menge * $einzelpreis == 0)) ? $fpdf->formatCurrency($fpdf->language, $A->menge * $menge2 * $nettoPreis, $fpdf->showPositionenWaehrung, $fpdf->showDezimalstellen) : "",
				"widthMwStBetrag" => ($A->mwst * 1 != 0 ? $fpdf->formatCurrency($fpdf->language, $A->menge * $menge2 * $mwstBetrag, $fpdf->showPositionenWaehrung) : ""),
				"widthGesamt" => (!(!$fpdf->showNullPreise AND $A->menge * $einzelpreis == 0)) ? $fpdf->formatCurrency($fpdf->language,$A->menge * $menge2 * $einzelpreis, $fpdf->showPositionenWaehrung) : "",
				"widthMwSt" => $A->mwst * 1 != 0 ? Util::formatNumber($fpdf->language, $A->mwst * 1, 2)."%" : ""
			);
			
			$priceColsContent = Aspect::joinPoint("prices", $this, __METHOD__, array($fpdf, $A, $priceColsContent), $priceColsContent);
			
			foreach($priceCols AS $col){
				$col = "width$col";
					
				if($fpdf->$col)
					$fpdf->Cell8($fpdf->$col, 5, $fpdf->cur($priceColsContent[$col]), 0, 0, "R");
			}
		}
	}
}
?>