<?php
/**
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

define("PHYNX_USE_TCPDF", true);

// <editor-fold defaultstate="collapsed" desc="weiche">
if(PHYNX_USE_TCPDF AND Session::isPluginLoaded("mTCPDF")){
	class phynxPDF extends FPDI {
		protected $fakePage = 0;
		
		protected $heightPDFHeader = 15;
		protected $usePDFA = false;
		protected $replaceKeyword = array();
		protected $replaceWith = array();
		#protected $fakePage = 0;
		
		function _putpages() {
			$nb = $this->page;
			#if (!empty($this->AliasNbPages)) {
				//Replace number of pages
				#for ($n = 1; $n <= $nb; $n++)
				#	$this->pages[$n] = str_replace($this->nbTag, $this->fakePage, $this->pages[$n]);
			#}
		
			if (count($this->replaceKeyword) > 0) {
				for ($n = 1; $n <= $nb; $n++)
					for ($i = 0; $i < count($this->replaceKeyword); $i++)
						$this->pages[$n] = str_replace($this->replaceKeyword[$i], $this->replaceWith[$i], $this->pages[$n]);
			}
			#print_r($this->pages);
			#die();
		
			parent::_putpages();
		}
		
		function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false) {
			parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);
			$this->marginTop = $this->positionPosten2teSeite[1] + $this->heightPDFHeader;
			TCPDF_STATIC::$alias_tot_pages = "{nb}";
		}
		
		function MultiCell8($w, $h, $txt, $border = 0, $align = 'L', $fill = false, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = false, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false) {
			parent::MultiCell($w, $h, $txt, $border, $align, $fill, $ln, $x, $y, $reseth, $stretch, $ishtml, $autopadding, $maxh, $valign, $fitcell);
		}

		function Cell8($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M') {
			parent::Cell($w, $h, $txt, $border, $ln, $align, $fill, $link, $stretch, $ignore_min_height, $calign, $valign);
		}
		
		function AliasNbPages(){
			//TODO: TCPDF
		}
		
		function AddPage($orientation = '', $resetFakePageCounter = false, $keepmargins = false, $tocpage = false) {
			parent::AddPage($orientation, '', $keepmargins, $tocpage);
			
			if ($resetFakePageCounter)
				$this->fakePage = 1;
		}
		
		function GetMargin($where) {
			switch (strtoupper($where)) {
				case "R":
					return $this->rMargin;
				break;
				case "L":
					return $this->lMargin;
				break;
				case "T":
					return $this->tMargin;
				break;
				case "B":
					return $this->bMargin;
				break;
			}
		}
		
		function stackFont(){
			
		}
		
		function _beginpage($orientation = '', $format = '') {
			$this->fakePage++;
			
			return parent::_beginpage($orientation, $format);
		}
		
		public function cur($text){
			return $text;
		}
		
		protected function setHeader() {
			parent::setHeader();
			
			
			#if($this->PageNo() > 1)
			#	$this->setXY($this->positionPosten2teSeite[0], $this->positionPosten2teSeite[1]);
			
		}
		
		protected function _putEmbeddedFiles() {
			$this->_putEmbeddedFilesPhynx();
			parent::_putEmbeddedFiles();
		}

		protected function _putEmbeddedFilesPhynx(){
			reset($this->embeddedfiles);
			foreach ($this->embeddedfiles as $filename => $filedata) {
				#print_r($filedata);
				if(!isset($filedata['data']))
					continue;

				// update name tree
				$this->efnames[$filename] = $filedata['f'].' 0 R';
				// embedded file specification object
				$out = $this->_getobj($filedata['f'])."\n";
				$out .= '<</Type /Filespec /F '.$this->_datastring($filename, $filedata['f']).' /UF <feff005a005500470046006500520044002d0069006e0076006f006900630065002e0078006d006c> /AFRelationship /Alternative /Desc (ZUGFeRD Invoice) /EF <</F '.$filedata['n'].' 0 R>> >>';
				$out .= "\n".'endobj';
				$this->_out($out);
				// embedded file object
				$data = $filedata['data'];
				$filter = '';
				$rawsize = strlen($data);
				if ($this->compress) {
					$data = gzcompress($data);
					$filter = ' /Filter /FlateDecode';
				}
				$stream = $this->_getrawstream($data, $filedata['n']);
				$out = $this->_getobj($filedata['n'])."\n";
				$out .= '<< /Type /EmbeddedFile'.$filter.' /Length '.strlen($stream).' /Subtype /text#2fxml /Params <</Size '.$rawsize.' /ModDate (D:'.TCPDF_STATIC::getFormattedDate(time()).') >> >>';
				$out .= ' stream'."\n".$stream."\n".'endstream';
				$out .= "\n".'endobj';
				$this->_out($out);

				unset($this->embeddedfiles[$filename]);
			}
		}
		
	
		function embedDataAsFile($data, $fileName){
			if ((!isset($this->embeddedfiles[basename($fileName)]))) 
				$this->embeddedfiles[basename($fileName)] = array('f' => ++$this->n, 'n' => ++$this->n, 'file' => $fileName, 'data' => $data);
		}
		
		protected function custom_xmp_description(){
			return '		<rdf:Description rdf:about="" xmlns:zf="urn:ferd:pdfa:invoice:rc#">
			<zf:DocumentType>INVOICE</zf:DocumentType>
			<zf:DocumentFileName>ZUGFeRD-invoice.xml</zf:DocumentFileName>
			<zf:Version>RC</zf:Version>
			<zf:ConformanceLevel>BASIC</zf:ConformanceLevel>
		</rdf:Description>
		<rdf:Description rdf:about=""
			xmlns:pdfaExtension="http://www.aiim.org/pdfa/ns/extension/"
			xmlns:pdfaSchema="http://www.aiim.org/pdfa/ns/schema#"
			xmlns:pdfaProperty="http://www.aiim.org/pdfa/ns/property#">
			<pdfaExtension:schemas>
				<rdf:Bag>
					<rdf:li rdf:parseType="Resource">
						<pdfaSchema:schema>ZUGFeRD PDFA Extension Schema</pdfaSchema:schema>
						<pdfaSchema:namespaceURI>urn:ferd:pdfa:invoice:rc#</pdfaSchema:namespaceURI>
						<pdfaSchema:prefix>zf</pdfaSchema:prefix>
						<pdfaSchema:property>
						   <rdf:Seq>
							  <rdf:li rdf:parseType="Resource">
								 <pdfaProperty:name>DocumentFileName</pdfaProperty:name>
								 <pdfaProperty:valueType>Text</pdfaProperty:valueType>
								 <pdfaProperty:category>external</pdfaProperty:category>
								 <pdfaProperty:description>name of the embedded XML invoice file</pdfaProperty:description>
							  </rdf:li>
							  <rdf:li rdf:parseType="Resource">
								 <pdfaProperty:name>DocumentType</pdfaProperty:name>
								 <pdfaProperty:valueType>Text</pdfaProperty:valueType>
								 <pdfaProperty:category>external</pdfaProperty:category>
								 <pdfaProperty:description>INVOICE</pdfaProperty:description>
							  </rdf:li>
							  <rdf:li rdf:parseType="Resource">
								 <pdfaProperty:name>Version</pdfaProperty:name>
								 <pdfaProperty:valueType>Text</pdfaProperty:valueType>
								 <pdfaProperty:category>external</pdfaProperty:category>
								 <pdfaProperty:description>The actual version of the ZUGFeRD data</pdfaProperty:description>
							  </rdf:li>
							  <rdf:li rdf:parseType="Resource">
								 <pdfaProperty:name>ConformanceLevel</pdfaProperty:name>
								 <pdfaProperty:valueType>Text</pdfaProperty:valueType>
								 <pdfaProperty:category>external</pdfaProperty:category>
								 <pdfaProperty:description>The conformance level of the ZUGFeRD data</pdfaProperty:description>
							  </rdf:li>
						   </rdf:Seq>
						</pdfaSchema:property>
					</rdf:li>
				</rdf:Bag>
			</pdfaExtension:schemas>
      </rdf:Description>';
		}
	
		function ImageGD($im, $x, $y, $w = 0, $h = 0, $link = ''){
			ob_start();
			imagepng($im);
			$data = ob_get_contents();
			ob_end_clean();
			
			$this->Image("@".$data, $x, $y, $w, $h, "png", $link);
		}
		
		public function getAliasNbPages(){
			return $this->nbTag;
		}
		
		public function getAliasNumPage(){
			return $this->PageNo();#$this->fakePage;
		}

		function AddReplacement($keyword, $with) {
			$this->replaceKeyword[] = $keyword;
			$this->replaceWith[] = $with;
		}
	}
} else {
	class phynxPDF extends FormattedTextPDF {
		public function GetStringWidth($s) {
			return parent::GetStringWidth(utf8_decode($s));
		}
		
		public function embedDataAsFile(){
			
		}
		
		public function getAliasNbPages(){
			return $this->nbTag;
		}
		
		public function getAliasNumPage(){
			return $this->PageNo();
		}
		
		public function cur($text){
			return Util::conv_euro8($text);
		}
		
		public function SetTextColorArray($c){
			$this->SetTextColor($c[0], $c[1], $c[2]);
		}

		/*function Cell8($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=0, $link='') {
			$this->Cell($w, $h, utf8_decode($txt), $border, $ln, $align, $fill, $link);
		}

		function MultiCell8($w, $h, $txt, $border=0, $align='J', $fill=0) {
			$this->MultiCell($w, $h, utf8_decode($txt), $border, $align, $fill);
		}*/
	}
}
// </editor-fold>


class PDFBrief extends phynxPDF {
	protected $stammdaten;
	public $VarsEmpfaengerAdresse;
	public $VarsEmpfaengerAnsprechpartner;
	public $VarsGRLBM;
	public $VarsAuftrag;
	public $brief;
	
	public $gesamt_netto = array();
	public $artikelsteuern = array();
	public $gesamt_brutto;
	public $gesamtEK1 = 0;
	public $gesamtEK2 = 0;
	public $rabatt = "";
	public $leasingrate = "";
	public $rabattInW = "";
	
	const DEFAULT_FONT = 'Helvetica';
	
	/**
	 * @label Standard-Sprache
	 * @editor true
	 * @values de_DE, de_DE_EUR, de_CH, de_CH_CHF, en_US, en_GB, en_NO, en_DK, en_SE
	 */
	public $language = "de_DE";
	
	
	/**
	 * @label Standard-Währung
	 * @editor true
	 * @values EUR, CHF, GBP, NOK, DKK, SEK
	 */
	public $currency = "EUR";
	
	/**
	 * @label Währungs-Symbol
	 * @editor true
	 */
	public $currencyUseSymbol = true;
	/**
	 * @group Belege
	 * @label Rechnung
	 * @editor true
	 */
	public $labelRechnung = "Rechnung";
	/**
	 * @label Lieferschein
	 * @editor true
	 */
	public $labelLieferschein = "Lieferschein";
	/**
	 * @label Gutschrift
	 * @editor true
	 */
	public $labelGutschrift = "Gutschrift";
	/**
	 * @label Angebot
	 * @editor true
	 */
	public $labelAngebot = "Angebot";
	/**
	 * @label Kalkulation
	 * @editor true
	 */
	public $labelKalkulation = "Kalkulation";
	/**
	 * @label Bestellung
	 * @editor true
	 */
	public $labelBestellung = "Bestellung";
	/**
	 * @label Preisanfrage
	 * @editor true
	 */
	public $labelPreisanfrage = "Preisanfrage";
	
	/**
	 * @label Dokument
	 * @editor true
	 */
	public $labelDokument = "Dokument";
	
	/**
	 * @label Mahnung
	 * @editor true
	 */
	public $labelMahnung = "Mahnung";
	/**
	 * @label Zahlungser.
	 * @editor true
	 */
	public $labelZahlungserinnerung = "Zahlungserinnerung";
	/**
	 * @label Auftragsbest.
	 * @editor true
	 */
	public $labelBestaetigung = "Auftragsbestätigung";
	
	/**
	 * @group Kontakt
	 * @label Telefon
	 * @editor true
	 */
	public $labelTelefon = "Telefon";
	/**
	 * @label Fax
	 * @editor true
	 */
	public $labelFax = "Fax";
	/**
	 * @label Fax Lieferant
	 * @optional true
	 * @editor true
	 */
	public $labelFaxLieferant = "Fax Lieferant";
	/**
	 * @label Lieferantennummer
	 * @optional true
	 * @editor true
	 */
	public $labelLieferantennummer = "Lieferantennummer";
	
	/**
	 * @label Bestellnummer
	 * @optional true
	 * @editor true
	 */
	public $labelBestellnummer = "Bestellnummer";
	/**
	 * @label Bestelldatum
	 * @optional true
	 * @editor true
	 */
	public $labelBestelldatum = "Bestelldatum";
	
	/**
	 * @label Handy
	 * @editor true
	 */
	public $labelHandy = "Handy";
	/**
	 * @label E-Mail
	 * @editor true
	 */
	public $labelEMail = "E-Mail";
	/**
	 * @label Internet
	 * @editor true
	 */
	public $labelInternet = "Internet";
	
	/**
	 * @group Belegdetails
	 * @label Datum
	 * @optional true
	 * @editor true
	 */
	public $labelDatum = "Datum";
	
	/**
	 * @label Ihr Zeichen
	 * @optional true
	 * @editor true
	 */
	public $labelIhrZeichen = "Ihr Zeichen";
	
	/**
	 * @label Zu Lieferschein
	 * @optional true
	 * @editor true
	 */
	public $labelZuLieferschein = "Zu Lieferschein";
	
	/**
	 * @label Lieferdatum
	 * @optional true
	 * @editor true
	 */
	public $labelLieferdatum = "Lieferdatum";
	/**
	 * @label Ust-IdNr
	 * @editor true
	 * @optional true
	 */
	public $labelUstID = "Ust-IdNr";
	/**
	 * @label Steuernummer
	 * @editor true
	 */
	public $labelStNr = "Steuernummer";
	/**
	 * @label Kundennummer
	 * @editor true
	 * @optional true
	 */
	public $labelKundennummer = "Kundennummer";
	/**
	 * @label Kunden-Ust-IdNr
	 * @editor true
	 */
	public $labelKundeUstID = "Kunden-Ust-IdNr";
	/**
	 * @label Kunde Ansprechpartner
	 * @editor true
	 * @optional false
	 */
	public $labelKundeAnsprechpartner = null;
	/**
	 * @label Kunde Telefon
	 * @editor true
	 * @optional false
	 */
	public $labelKundeTelefon = null;
	/**
	 * @label Kunde E-Mail
	 * @editor true
	 * @optional false
	 */
	public $labelKundeEMail = null;
	
	/**
	 * @label Filiale
	 * @optional true
	 * @editor true
	 */
	public $labelFiliale = "Filiale";
	
	/**
	 * @label Kunden-Steuernummer
	 * @editor true
	 */
	public $labelKundeStNr = "Kunden-Steuernummer";
	/**
	 * @label Kopie
	 * @editor true
	 */
	public $labelKopie = "Kopie";
	/**
	 * @label Teilrechnungen
	 * @editor true
	 */
	public $labelTeilrechnungen = "Teilrechnungen";
	/**
	 * @label Teilrechnung
	 * @editor true
	 */
	public $labelTeilrechnung = "Teilrechnung";
	/**
	 * @label Abschlagsre.n
	 * @editor true
	 */
	public $labelAbschlagsrechnungen = "Abschlagsrechnungen";
	/**
	 * @label Abschlagsre.
	 * @editor true
	 */
	public $labelAbschlagsrechnung = "Abschlagsrechnung";
	/**
	 * @label Abschlussre.
	 * @editor true
	 */
	public $labelAbschlussrechnung = "Abschlussrechnung";
	/**
	 * @label Fortsetzung
	 * @editor true
	 */
	public $labelFortsetzung = "Fortsetzung auf Seite";
	/**
	 * @label Übertrag
	 * @editor true
	 * @optional true
	 */
	public $labelUebertrag = "Übertrag";
	
	/**
	 * @group Postenüberschrift
	 * @label Position
	 * @editor true
	 */
	public $labelPosition = "Pos";
	/**
	 * @label Menge
	 * @editor true
	 */
	public $labelMenge = "Menge";
	public $labelMenge2 = "Menge 2";
	/**
	 * @label Einheit
	 * @editor true
	 */
	public $labelEinheit = "Einheit";
	/**
	 * @label Artikelnummer
	 * @editor true
	 */
	public $labelArtikelnummer = "Art.Nr.";
	/**
	 * @label Bezeichnung
	 * @editor true
	 */
	public $labelBezeichnung = "Bezeichnung";
	/**
	 * @group 
	 * @label Einzelpreis Brutto/Netto
	 * @editor true
	 */
	public $labelEinzelpreis = "Einzelpreis";
	/**
	 * @label Einzelpreis Netto
	 * @editor true
	 */
	public $labelEinzelpreisNetto = "Einzelpreis";
	/**
	 * @group 
	 * @label Rabatt Prozent
	 * @editor true
	 */
	public $labelRabatt = "Rabatt";
	/**
	 * @label Rabatt Preis
	 * @editor true
	 */
	public $labelRabattpreis = "Preis";
	/**
	 * @group 
	 * @label Gesamt Netto
	 * @editor true
	 */
	public $labelGesamtNettoPosten = "Gesamt Netto";
	/**
	 * @label MwSt Betrag
	 * @editor true
	 */
	public $labelMwStBetrag = "MwSt";
	/**
	 * @label Gesamt Brutto/Netto
	 * @editor true
	 */
	public $labelGesamt = "Gesamt";
	/**
	 * @label MwSt Prozent
	 * @editor true
	 */
	public $labelMwSt = "MwSt";
	
	/**
	 * @group Summe
	 * @label Gesamt Netto
	 * @editor true
	 */
	public $labelGesamtNetto = "Gesamt Netto";
	/**
	 * @label Umsatzsteuer
	 * @editor true
	 */
	public $labelUmsatzsteuer = "Umsatzsteuer";
	/**
	 * @label Artikelsteuer
	 * @editor true
	 */
	public $labelArtikelsteuer = "Artikelsteuer";
	/**
	 * @label Betrag Rech.
	 * @editor true
	 */
	public $labelRechnungsbetrag = "Rechnungsbetrag";
	/**
	 * @label Betrag Gutsch.
	 * @editor true
	 */
	public $labelGutschriftsbetrag = "Gutschriftsbetrag";
	/**
	 * @label Gesamt Brutto
	 * @editor true
	 */
	public $labelGesamtBrutto = "Gesamt Brutto";
	
	public $labelGes = "ges.";
	public $labelVK = "VK";
	public $labelEK1 = "EK1";
	public $labelEK2 = "EK2";
	public $labelLeasingrate = "Leasingrate";
	
	/**
	 * @group Fußzeile
	 * @label Amtsgericht
	 * @editor true
	 */
	public $labelAmtsgericht = "Amtsgericht";
	/**
	 * @label Handelsregister
	 * @editor true
	 */
	public $labelHandelsregister = "Handelsregister Nr.";
	/**
	 * @label Seite
	 * @editor true
	 * @optional true
	 */
	public $labelSeite = "Seite";
	
	/**
	 * @label Inhaber
	 * @editor true
	 */
	public $labelInhaber = "Inhaber";
	
	/**
	 * @label Geschäftsführer
	 * @editor true
	 */
	public $labelGeschaeftsfuehrer = "Geschäftsführer";
	
	/**
	 * @label Bankverbindung
	 * @editor true
	 */
	public $labelBankverbindung = "Bankverbindung";
	
	/**
	 * @label BIC
	 * @editor true
	 */
	public $labelBIC = "BIC";
	
	/**
	 * @label IBAN
	 * @editor true
	 */
	public $labelIBAN = "IBAN";
	
	/**
	 * @editor false
	 */
	public $labelVersandkosten = "Versandkosten";
	/**
	 * @editor false
	 */
	public $labelVersandkostenGesamtbetrag = "Gesamtbetrag";

	//Es stehen bis zu 3 frei wählbare Felder zur Verfügung
	
	/**
	 * @group Benutzerfelder oben
	 * @label Feld 1
	 * @editor true
	 * @optional false
	 */
	public $labelCustomField1 = null;
	/**
	 * @label Feld 2
	 * @editor true
	 * @optional false
	 */
	public $labelCustomField2 = null;
	/**
	 * @label Feld 3
	 * @editor true
	 * @optional false
	 */
	public $labelCustomField3 = null;
	
	/**
	 * @group Benutzerfelder unten
	 * @label Feld 1
	 * @editor true
	 * @optional false
	 */
	public $labelCustomField11 = null;
	/**
	 * @label Feld 2
	 * @editor true
	 * @optional false
	 */
	public $labelCustomField12 = null;
	/**
	 * @label Feld 3
	 * @editor true
	 * @optional false
	 */
	public $labelCustomField13 = null;

	
	/**
	 * @group Seitenränder
	 * @label Links
	 * @editor true
	 */
	public $marginLeft = 20;
	/**
	 * @label Oben
	 * @editor true
	 */
	public $marginTop = 20;
	/**
	 * @label Rechts
	 * @editor true
	 */
	public $marginRight = 20;
	/**
	 * @label Unten
	 * @editor true
	 */
	public $marginBottom = 40;
	
	//Angaben in Millimeter von der Ecke links oben
	//Zum Ausblenden null setzen
	/**
	 * @group Empfängeradresse
	 * @label Absenderzeile
	 * @editor true
	 * @optional true
	 */
	public $positionAbsenderZeile = array(20, 50);
	/**
	 * @label Absenderzeile Linie
	 * @editor true
	 * @optional true
	 */
	public $positionAbsenderZeileLinie = array(20, 54);
	/**
	 * @label Empfängeradr.
	 * @editor true
	 * @optional true
	 */
	public $positionEmpfaengerAdresse = array(20, 55);
	/**
	 * @group Kontaktdaten
	 * @label Adresse
	 * @editor true
	 * @optional true
	 */
	public $positionDetailsAdresse = array(140, 30);
	/**
	 * @label Weitere
	 * @editor true
	 * @optional true
	 */
	public $positionDetails = array(130, 40);
	/**
	 * @group Beleginformationen
	 * @label 1. Seite
	 * @editor true
	 * @optional true
	 */
	public $positionRechnungsInfo = array(130, 59);
	/**
	 * @label Folgeseiten
	 * @editor true
	 * @optional true
	 */
	public $positionRechnungsInfo2teSeite = array(130, 30);
	
	/**
	 * @group Kopie-Vermerk
	 * @label 1. Seite
	 * @editor true
	 * @optional true
	 */
	public $positionKopieLabel = array(90, 59);
	/**
	 * @label Folgeseiten
	 * @editor true
	 * @optional true
	 */
	public $positionKopieLabel2teSeite = array(90, 30);
	
	/**
	 * @group Positionen
	 * @label ab 2.Seite
	 * @editor true
	 */
	public $positionPosten2teSeite = array(20, 60);
	
	/**
	 * @group Allgemein
	 * @label Textbaustein oben
	 * @editor true
	 */
	public $positionTextbausteinOben = array(20, 95);
	/**
	 * @label Fußzeile
	 * @editor true
	 * @optional true
	 */
	public $positionFooter = array(20, 275);
	
	#public $positionLogo = array(140, 10);
	public $positionPreis = "end"; //OR start
	public $positionVersandkosten = "above"; //OR below
	public $positionTeilrechnungen = "above"; //or inline

	/**
	 * @label Dateiname
	 * @editor true
	 */
	public $backgroundFileName = "";
	/**
	 * @group Hintergrund Position
	 * @label Position
	 * @editor true
	 */
	public $backgroundPosition = array(0, 0);
	
	
	/**
	 * @label Breite
	 * @editor true
	 */
	public $backgroundWidth = 210;
	
	/**
	 * @label Dateiname
	 * @description Falls eine andere Datei verwendet werden soll
	 * @editor true
	 */
	public $backgroundFileNameSecondPage = "";
	
	
	/**
	 * @label Dateiname
	 * @editor true
	 */
	public $logoFileName = "";
	/**
	 * @group Logo Position
	 * @label Position
	 * @editor true
	 */
	public $logoPosition = array(140, 10);
	
	
	/**
	 * @label Breite
	 * @editor true
	 */
	public $logoWidth = 50;
	
	
	/**
	 * @group Firmenschriftzug
	 * @label Position
	 * @assign logo
	 * @editor true
	 * @optional true
	 */
	public $positionFirmaSchriftzug = array(20, 12);
	
	/**
	 * @label Schrift
	 * @editor true
	 * @assign logo
	 */
	public $fontFirmaSchriftzug = array(self::DEFAULT_FONT, 'B', 28);
	
	/**
	 * @group Slogan
	 * @label Beschriftung
	 * @assign logo
	 * @editor true
	 */
	public $labelSlogan = "";
	/**
	 * @label Position
	 * @assign logo
	 * @editor true
	 * @optional true
	 */
	public $positionSlogan = array(20, 20);
	/**
	 * @label Schrift
	 * @assign logo
	 * @editor true
	 */
	public $fontSlogan = array(self::DEFAULT_FONT, '', 11);
	
	public $positionPositionBild = 39;
	
	#public $widthLogo = 50;
	
	/**
	 * @group Kontaktdaten
	 * @label Gesamt
	 * @editor true
	 */
	public $widthDetails = 60;
	
	/**
	 * @label Beschriftung
	 * @editor true
	 */
	public $widthDetailsLabel = 30;
	
	/**
	 * @group Beleginformationen
	 * @label Gesamt
	 * @editor true
	 */
	public $widthRechnungsInfo = 60;
	
	/**
	 * @group Textbausteine
	 * @label Breite
	 * @editor true
	 * @description Bei 0 wird die ganze Seitenbreite genutzt.
	 */
	public $widthTextbaustein = 0;
	
	/**
	 * @group Empfängeradresse
	 * @label Breite
	 * @editor true
	 */
	public $widthEmpfaengerAdresse = 80;
	
	/**
	 * @group Spalten
	 * @label Position
	 * @editor true
	 * @optional false
	 */
	public $widthPosition = null;
	
	public $widthPositionBild = 28;
	
	/**
	 * @label Menge
	 * @editor true
	 * @optional true
	 */
	public $widthMenge = 19;
	public $widthMenge2 = null;
	/**
	 * @label Einheit
	 * @editor true
	 * @optional true
	 */
	public $widthEinheit = 28;
	/**
	 * @label Artikelnummer
	 * @editor true
	 * @optional false
	 */
	public $widthArtikelnummer = null;
	/**
	 * @label Bezeichnung
	 * @editor true
	 * @optional true
	 */
	public $widthBezeichnung = 55;
	/**
	 * @group 
	 * @label Einzelpreis Brutto/Netto
	 * @editor true
	 * @optional true
	 */
	public $widthEinzelpreis = 35;
	/**
	 * @label Einzelpreis Netto
	 * @editor true
	 * @optional false
	 */
	public $widthEinzelpreisNetto = null;
	/**
	 * 
	 * @group 
	 * @label Rabatt Prozent
	 * @editor true
	 * @optional false
	 */
	public $widthRabatt = null; //Funktioniert nur mit dem Rabatt-Customizer NICHT @requires CustomizerRabatt VERWENDEN!
	
	/**
	 * @label Rabatt Preis
	 * @editor true
	 * @optional false
	 */
	public $widthRabattpreis = null; //Funktioniert nur mit dem Rabatt-Customizer NICHT @requires CustomizerRabatt VERWENDEN!
	/**
	 * @group 
	 * @label Gesamt Netto
	 * @editor true
	 * @optional false
	 */
	public $widthGesamtNettoPosten = null;
	
	/**
	 * @label MwSt Betrag
	 * @editor true
	 * @optional false
	 */
	public $widthMwStBetrag = null;
	/**
	 * @label Gesamt Brutto/Netto
	 * @editor true
	 * @optional true
	 */
	public $widthGesamt = 33;
	/**
	 * @label MwSt Prozent
	 * @editor true
	 * @optional false
	 */
	public $widthMwSt = null;
	
	public $widthEK1 = 35;
	public $widthEK2 = 30;
	public $widthVK = 31;
	
	/**
	 * @label Falzmarken
	 * @editor true
	 */
	public $showFalzmarken = true;
	/**
	 * @label Anrede in Empfänger
	 * @editor true
	 */
	public $showAnredeInEmpfaenger = false;

	/**
	 * @label Brutto-Preise
	 * @editor true
	 */
	public $showBruttoPreise = false;
	/**
	 * @label 0€-Preise
	 * @editor true
	 */
	public $showNullPreise = true;
	
	/**
	 * @label Nachkommast. für Preise
	 * @editor true
	 */
	public $showDezimalstellen = null;
	/**
	 * @label Nachkommast. für Menge
	 * @editor true
	 */
	public $showDezimalstellenMenge = 2;
	
	/**
	 * @label Währung in Positionen
	 * @editor true
	 */
	public $showPositionenWaehrung = true;
	/**
	 * @label Gebührentabelle in Mahnung
	 * @editor true
	 */
	public $showMahnungTable = false;
	/**
	 * @label 0% MwSt
	 * @editor true
	 */
	public $show0ProzentMwSt = false;
	
	/**
	 *
	 * @label Artikelbilder auf
	 * @editor true
	 * @values G, R, L, B, A, P, O; Tragen Sie mehrere Werte getrennt von einem Leerzeichen ein: A R
	 * @type string
	 */
	public $showImagesOn = "A";
	
	public $showPositionen = true;
	protected $showHeader = true;
	
	/**
	 * @group Seite
	 * @assign show
	 * @label Format
	 * @editor true
	 * @values A4, A3
	 */
	protected $pageFormat = "A4";
	
	/**
	 * @assign show
	 * @label Orientierung
	 * @editor true
	 * @values P, L
	 */
	protected $pageOrientation = "P";
	
	/**
	 * @group Positionen-Tabelle
	 * @label Menge
	 * @editor true
	 * @values R, L, C
	 */
	public $alignMenge = "R";
	
	/**
	 * @label Einheit
	 * @editor true
	 * @values R, L, C
	 */
	public $alignEinheit = "L";
	
	/**
	 * @label Menge 2
	 * @editor true
	 * @values R, L, C
	 */
	public $alignMenge2 = "R";
	
	/**
	 * @group Details
	 * @label Adresse
	 * @editor true
	 * @values R, L, C
	 */
	public $alignDetailsAdresse = "R";
	
	/**
	 * @label Kontaktdaten
	 * @editor true
	 * @values R, L, C
	 */
	public $alignDetails = "R";
	public $alignPosition = "R";
	
	
	public $nbTag = "{nb}";
	public $labelLong = array();

	/**
	 * @group Positionen
	 * @label Amtsgericht
	 * @editor true
	 * @optional true
	 */
	public $footerAmtsgerichtPosition = 120;
	/**
	 * @label Details
	 * @editor true
	 * @optional true
	 */
	public $footerDetailsPosition = 20;
	/**
	 * @label Seite
	 * @editor true
	 */
	public $footerSeitePosition = 175;
	/**
	 * @group Allgemein
	 * @label Linie
	 * @editor true
	 */
	public $footerShowLine = true;
	
	
	/**
	 * @group Allgemein
	 * @label Fußzeile
	 * @editor true
	 */
	public $fontFooter = array(self::DEFAULT_FONT, '', 8);
	/**
	 * @label Textbausteine
	 * @editor true
	 */
	public $fontTextbausteine = array(self::DEFAULT_FONT,'',10);
	/**
	 * @group Empfängeradresse
	 * @label Absenderzeile
	 * @editor true
	 */
	public $fontAbsenderZeile = array(self::DEFAULT_FONT, '', 8);
	/**
	 * @label Empfängeradr.
	 * @editor true
	 */
	public $fontEmpfaengerAdresse = array(self::DEFAULT_FONT, '', 11);
	
	/**
	 * @group Allgemein
	 * @label Kontaktdaten
	 * @editor true
	 */
	public $fontDetails = array(self::DEFAULT_FONT, '', 8);
	
	/**
	 * @label Beleginfo
	 * @editor true
	 */
	public $fontRechnungsInfo = array(self::DEFAULT_FONT, '', 9);
	/**
	 * @label Kopie-Vermerk
	 * @editor true
	 */
	public $fontKopieLabel = array(self::DEFAULT_FONT, 'B', 24);
	/**
	 * @group Positionen
	 * @label Allgemein
	 * @editor true
	 */
	public $fontPositionen = array(self::DEFAULT_FONT, '', 9);
	/**
	 * @label Preise
	 * @editor true
	 */
	public $fontPositionenPreise = array(self::DEFAULT_FONT, '', 9);
	/**
	 * @label Überschrift
	 * @editor true
	 */
	public $fontPositionenHeader = array(self::DEFAULT_FONT, 'BI', 9);
	/**
	 * @label Beschreibung
	 * @editor true
	 */
	public $fontPositionenBeschreibung = array(self::DEFAULT_FONT, '', 7);
	/**
	 * @label Artikelname
	 * @editor true
	 */
	public $fontPositionenArtikelname = array(self::DEFAULT_FONT, 'B', 9);
	
	/**
	 * @group Zahlung
	 * @label QR-Code verwenden
	 * @editor true
	 */
	public $paymentShowQR = false;
	
	/**
	 * @group Summe
	 * @label Überschrift
	 * @editor true
	 */
	public $fontSumHeaders = array(self::DEFAULT_FONT, 'B', 9);
	/**
	 * @label Nettopreis
	 * @editor true
	 */
	public $fontSumNetto = array(self::DEFAULT_FONT, '', 9);
	/**
	 * @label Umsatzsteuer
	 * @editor true
	 */
	public $fontSumUmsatzsteuer = array(self::DEFAULT_FONT, '', 9);
	/**
	 * @label Betrag
	 * @editor true
	 */
	public $fontSumBetrag = array(self::DEFAULT_FONT, 'B', 9);
	
	/**
	 *
	 * @label Preise verstecken auf
	 * @editor true
	 * @values G, R, L, B, A, P, O; Tragen Sie mehrere Werte getrennt von einem Leerzeichen ein: L P
	 * @type string
	 */
	public $sumHideOn = "L P";
	
	/**
	 * @label Ausrichtung
	 * @editor true
	 * @values horizontal oder vertical
	 * @type string
	 */
	public $sumAlignment = "horizontal";
	
	/**
	 * @group Positionen
	 * @label Gesamt Netto
	 * @editor true
	 */
	public $sumGesamtNettoPosition = 72;
	/**
	 * @label Umsatzsteuer
	 * @editor true
	 */
	public $sumUmsatzsteuerPosition = 119;
	
	/**
	 * @label Artikelsteuer
	 * @editor true
	 * @requires CustomizerArtikelSteuern
	 */
	public $sumArtikelsteuerPosition = 122;
	
	/**
	 * @label Betrag
	 * @editor true
	 */
	public $sumBetragPosition = 157;
	/**
	 * @group Breiten
	 * @label Gesamt Netto
	 * @editor true
	 */
	public $sumGesamtNettoWidth = 50;
	/**
	 * @label Umsatzsteuer
	 * @editor true
	 */
	public $sumUmsatzsteuerWidth = 38;
	/**
	 * @label Artikelsteuer
	 * @editor true
	 * @requires CustomizerArtikelSteuern
	 */
	public $sumArtikelsteuerWidth = 35;
	/**
	 * @label Betrag
	 * @editor true
	 */
	public $sumBetragWidth = 33;
	
	/**
	 * @group Optionen
	 * @label Alle Nettopreise
	 * @editor true
	 */
	public $sumShowAlleNettopreise = false;
	
	/**
	 * @label gesamt Netto
	 * @editor true
	 */
	public $sumShowGesamtNetto = true;
	
	/**
	 * @label Umsatzsteuer
	 * @editor true
	 */
	public $sumShowUmsatzsteuer = true;
	
	/**
	 * @label Artikelsteuer
	 * @editor true
	 * @requires CustomizerArtikelSteuern
	 */
	public $sumShowArtikelsteuer = false;
	
	/**
	 * @label gesamt Brutto
	 * @editor true
	 */
	public $sumShowBetrag = true;
	
	public $abstandPositionen = 5;
	
	/**
	 * @group Anhang
	 * @label Datei
	 * @editor true
	 */
	public $appendPDFFile = null; //relativ zu specifics-Verzeichnis
	/**
	 * @label Seite(n)
	 * @editor true
	 * @values Tragen Sie mehrere Seiten getrennt von einem Leerzeichen ein: 1 3 4
	 */
	public $appendPDFPages = 1; //Derzeit nur eine Seite 1, 2, 3...
	
	/**
	 * @label Belegarten
	 * @editor true
	 * @values all, G, R, L, B, A; Tragen Sie mehrere Werte getrennt von einem Leerzeichen ein: A R
	 */
	public $appendPDFBelegarten = "all";
	
	public $currentArticle = null;
	public $isInPosten = false;
	public $isInPostenBeschreibung = false;
	public $isInPostenBezeichnung = false;
	public $absenderZeileTrennzeichen = ",";

	public $heightPositionenBeschreibung = 3;
	public $heightRechnungsInfo = 4;
	public $heightDetailsAdresse = 3.5;
	public $heightEmpfaengerAdresse = 5;
	public $heightDetails = 3.5;
	public $heightPositionenHeader = 5;
	
	/**
	 * @label Kopfzeile
	 * @editor true
	 */
	public $colorHeader = array(0, 0, 0);
	/**
	 * @label Fußzeile
	 * @editor true
	 */
	public $colorFooter = array(0, 0, 0);
	/**
	 * @label Empfänger-Adresse
	 * @editor true
	 */
	public $colorEmpfaengerAdresse = array(0, 0, 0);
	/**
	 * @label Details
	 * @editor true
	 */
	public $colorDetails = array(0, 0, 0);
	/**
	 * @label Absender-Zeile
	 * @editor true
	 */
	public $colorAbsenderZeile = array(0, 0, 0);
	/**
	 * @label Textbausteine
	 * @editor true
	 */
	public $colorTextbausteine = array(0, 0, 0);
	/**
	 * @label Allgemein
	 * @group Positionen
	 * @editor true
	 */
	public $colorPositionen = array(0, 0, 0);
	/**
	 * @label Kopfzeile
	 * @editor true
	 */
	public $colorPositionenHeader = array(0, 0, 0);
	
	/**
	 * @label Summe
	 * @editor true
	 */
	public $colorSum = array(0, 0, 0);
	/**
	 * @label Kopfzeile Alternativ
	 * @editor true
	 * @requires CustomizerPostenOptionalGUI
	 */
	public $colorPositionenAlternativHeader = array(100, 100, 100);
	/**
	 * @label Position Alternativ
	 * @editor true
	 * @requires CustomizerPostenOptionalGUI
	 */
	public $colorPositionenAlternativ = array(80, 80, 80);
	
	/**
	 * @label Spalten
	 * @editor true
	 */
	public $orderCols = array(
			"Position",
			"Menge",
			"Einheit",
			"Menge2",
			"Artikelnummer",
			"Bezeichnung"
		);
	
	/**
	 * @label Spalten Preise
	 * @editor true
	 */
	public $orderColsPrice = array(
			"Einzelpreis",
			"EinzelpreisNetto",
			"Rabatt",
			"Rabattpreis",
			"GesamtNettoPosten",
			"MwStBetrag",
			"Gesamt",
			"MwSt"
		);
	
	/**
	 * @label Beleginformationen
	 * @editor true
	 */
	public $orderRechnungsInfo = array(
		"belegnummer",
		"ZuLieferschein",
		"Datum",
		"Lieferdatum",
		"custom1",
		"UstID",
		"leer",
		"Kundennummer",
		"KundeUstID",
		"KundeTelefon",
		"KundeAnsprechpartner",
		"KundeEMail",
		"Filiale",
		"FaxLieferant",
		"Lieferantennummer",
		"Bestellnummer",
		"Bestelldatum",
		"custom2"
	);
	
	public $waehrungFaktor = 1;
	private $waehrungFaktorSet = false;
	
	private $newFonts = array();
	
	public $translation = array("en_GB" => array(
		"labelRechnung" => "Invoice",
		"labelLieferschein" => "Delivery note",
		"labelGutschrift" => "Credit note",
		"labelAngebot" => "Offer",
		"labelKalkulation" => "Calculation",
		"labelBestellung" => "Order",
		"labelPreisanfrage" => "Price inquiry",
		"labelDokument" => "Document",
		"labelMahnung" => "Reminder",
		"labelZahlungserinnerung" => "Payment reminder",
		"labelBestaetigung" => "Confirmation of your order",
		"labelTelefon" => "Phone",
		"labelFaxLieferant" => "Fax supplier",
		"labelHandy" => "Mobile",
		"labelEMail" => "E-mail",
		"labelDatum" => "Date",
		"labelIhrZeichen" => "Your sign",
		"labelZuLieferschein" => "Delivery note",
		"labelLieferdatum" => "Delivery date",
		"labelStNr" => "Tax-ID",
		"labelKundennummer" => "Customer number",
		"labelKundeUstID" => "Your Tax-ID",
		"labelKundeStNr" => "Your Tax-ID",
		"labelUstID" => "Tax-ID",
		"labelKopie" => "Copy",
		"labelTeilrechnungen" => "Billings",
		"labelTeilrechnung" => "Billing",
		"labelAbschlagsrechnungen" => "Payment invoices",
		"labelAbschlagsrechnung" => "Payment invoice",
		"labelAbschlussrechnung" => "Your balance",
		"labelFortsetzung" => "Continuation",
		"labelMenge" => "Quantity",
		"labelEinheit" => "Unit",
		"labelArtikelnummer" => "Article no",
		"labelBezeichnung" => "Description",
		"labelEinzelpreis" => "Gross price",
		"labelEinzelpreisNetto" => "Net price",
		"labelRabatt" => "Discount",
		"labelRabattpreis" => "Price discount",
		"labelGesamtNettoPosten" => "Amount",
		"labelMwStBetrag" => "Tax amount",
		"labelGesamt" => "Total",
		"labelMwSt" => "Tax %",
		"labelGesamtNetto" => "Amount",
		"labelUmsatzsteuer" => "Tax amount",
		"labelArtikelsteuer" => "Pos. tax",
		"labelRechnungsbetrag" => "Total",
		"labelGutschriftsbetrag" => "Total",
		"labelGesamtBrutto" => "Total",
		"labelHandelsregister" => "Commercial register",
		"labelSeite" => "Page",
		"labelInhaber" => "Owner",
		"labelGeschaeftsfuehrer" => "Managing director",
		"labelBankverbindung" => "Bank account"
	));
	
	/**
	 * @editor false
	 */
	public $GRLBMNextLine;
	
	private $contentRechnungsInfo = array();
	
	/**
	 * @editor false
	 */
	protected $TextbausteinNummer = 1;
	// <editor-fold defaultstate="collapsed" desc="PageNo">
	function PageNo(){
		return $this->fakePage;
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="getFilename">
	function getFilename(Brief $brief){
		return (isset($brief->GRLBM) ? $brief->GRLBM->A("prefix") : $brief->type).$brief->nummer."-".$brief->datum."-".$brief->kunde.($brief->isCopy ? "-K" : "");
	}
	// </editor-fold>
	
	function __construct($S, $SpracheID = null){
		$pdfa = Session::isPluginLoaded("mFeRD");
		
		$this->translation["en_US"] = $this->translation["en_GB"];
		
		if(Session::isPluginLoaded("mSprache") AND $SpracheID != null){#$Adresse->A("AdresseSpracheID") != "0"){
			$L = new Sprache($SpracheID);
			$this->language = $L->A("SpracheSprache")."_".$L->A("SpracheLand");#.($L->A("SpracheWaehrungUseSymbol") == "1" ? "" : "_".$L->A("SpracheWaehrung"));
			
			$this->currency = $L->A("SpracheWaehrung");
			$this->currencyUseSymbol = $L->A("SpracheWaehrungUseSymbol");
			
			if(isset($this->translation[$L->A("SpracheSprache")."_".$L->A("SpracheLand")]))
				foreach($this->translation[$L->A("SpracheSprache")."_".$L->A("SpracheLand")] AS $k => $v)
					if($this->$k != null AND $this->$k != "")
						$this->$k = $v;
			
			
			if($L->A("SpracheWaehrungFaktor") != "0" AND $L->A("SpracheWaehrungFaktor") != "" AND !$this->waehrungFaktorSet)
				$this->waehrungFaktor = $L->A("SpracheWaehrungFaktor");
		}
		
		$this->labelLong = array(
			"R" => $this->labelRechnung,
			"L" => $this->labelLieferschein,
			"G" => $this->labelGutschrift,
			"A" => $this->labelAngebot,
			"M" => $this->labelMahnung,
			"B" => $this->labelBestaetigung,
			"Kalk" => $this->labelKalkulation,
			"O" => $this->labelBestellung,
			"P" => $this->labelPreisanfrage,
			"D" => $this->labelDokument);
		
		parent::__construct($this->pageOrientation, 'mm', $this->pageFormat, true, 'UTF-8', false, $pdfa);
		
		foreach($this->newFonts as $font)
			parent::AddFont($font[0], trim($font[1]), $font[2]);
		
		
		
		if($S != null) $this->sd = $S->getA();
		$this->stammdaten = $S;
		$this->setMargins($this->marginLeft, $this->marginTop, $this->marginRight);
		$this->SetAutoPageBreak(true, $this->marginBottom);

		if($S != null) $this->AddPage();
		
		$this->encrypted = false;
		
		$this->sumHideOn = explode(" ", trim($this->sumHideOn));
		
		#if($_SESSION["S"]->checkForPlugin("mBrutto"))
		#	$this->showBruttoPreise = true;
		
		
		Aspect::joinPoint("after", __CLASS__, __METHOD__, array($this, $S));
	}

	/**
	 * DONT CHANGE SIGANTURE!!
	 */
	function formatCurrency($language, $number, $withSymbol = false, $dezimalstellen = null){
		return Util::formatByCurrency($this->currency, $number * $this->waehrungFaktor, $this->currencyUseSymbol, $dezimalstellen);
	}
	
	function AddFont($family, $style = '', $file = '', $subset = "default"){
		if($this instanceof TCPDF)
			return parent::AddFont($family, $style, $file, $subset);
		else
			$this->newFonts[] = array($family, trim($style), $file);
	}
	
	function SetFont($family, $style = '', $size = NULL, $fontfile = '', $subset = 'default', $out = true){
		if(is_array($family))
			return parent::SetFont($family[0], trim($family[1]), $family[2], $fontfile, $subset, $out);
		
		return parent::SetFont($family, trim($style), $size, $fontfile, $subset, $out);
	}
	
	// <editor-fold defaultstate="collapsed" desc="SetDash">
    function SetDash($black = false, $white = false){
        if($black and $white)
            $s=sprintf('[%.3f %.3f] 0 d', $black*$this->k, $white*$this->k);
        else
            $s='[] 0 d';
        $this->_out($s);
    }
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="LCell">
	function LCell($marginRight, $height, $string, $border=0, $ln=0, $align='', $fill=0, $link=''){
		$this->Cell8($this->GetStringWidth($string)+$marginRight , $height , $string, $border, $ln, $align, $fill, $link);
	}
	// </editor-fold>

	function printContact(){
		$this->SetFont($this->fontDetails[0], $this->fontDetails[1], $this->fontDetails[2]);
		
		$x = $this->positionDetails[0];
		$y = $this->positionDetails[1];
		if($this->sd->telefon != ""){
			$this->setXY($x,$y);
			$this->Cell8($this->widthDetailsLabel,$this->heightDetails,$this->labelTelefon);
			$this->Cell8($this->widthDetails - $this->widthDetailsLabel,$this->heightDetails,$this->sd->telefon,"",0, $this->alignDetails);
			$y += $this->heightDetails;
		}

		if($this->sd->fax != ""){
			$this->setXY($x,$y);
			$this->Cell8($this->widthDetailsLabel,$this->heightDetails,$this->labelFax);
			$this->Cell8($this->widthDetails - $this->widthDetailsLabel,$this->heightDetails,$this->sd->fax,"",0, $this->alignDetails);
			$y += $this->heightDetails;
		}

		if($this->sd->mobil != ""){
			$this->setXY($x,$y);
			$this->Cell8($this->widthDetailsLabel,$this->heightDetails,$this->labelHandy);
			$this->Cell8($this->widthDetails - $this->widthDetailsLabel,$this->heightDetails,$this->sd->mobil,"",0, $this->alignDetails);
			$y += $this->heightDetails;
		}

		if($this->sd->email != ""){
			$this->setXY($x,$y);
			$this->Cell8($this->widthDetailsLabel,$this->heightDetails,$this->labelEMail);
			$this->Cell8($this->widthDetails - $this->widthDetailsLabel,$this->heightDetails,$this->sd->email,"",0, $this->alignDetails);
			$y += $this->heightDetails;
		}

		if($this->sd->internet != ""){
			$this->setXY($x,$y);
			$this->Cell8($this->widthDetailsLabel,$this->heightDetails,$this->labelInternet);
			$this->Cell8($this->widthDetails - $this->widthDetailsLabel,$this->heightDetails,$this->sd->internet,"",0, $this->alignDetails);
			$y += $this->heightDetails;
		}
		
	}
	
	// <editor-fold defaultstate="collapsed" desc="Header">
	function Header() {
		if($this->logoFileName != "")
			$this->Image(FileStorage::getFilesDir()."/".$this->logoFileName, $this->logoPosition[0], $this->logoPosition[1], $this->logoWidth);
		#var_dump($this->backgroundFileNameSecondPage);
		#die();
		if($this->backgroundFileName != "" AND (!$this->backgroundFileNameSecondPage OR $this->PageNo() == 1)){
			$this->setSourceFile(FileStorage::getFilesDir()."/".$this->backgroundFileName);
			$tplIdx = $this->importPage(1, "/MediaBox");
			$this->useTemplate($tplIdx, $this->backgroundPosition[0], $this->backgroundPosition[1], $this->backgroundWidth);
		}
		
		if($this->backgroundFileNameSecondPage != "" AND $this->PageNo() > 1){
			$this->setSourceFile(FileStorage::getFilesDir()."/".$this->backgroundFileNameSecondPage);
			$tplIdx = $this->importPage(1, "/MediaBox");
			$this->useTemplate($tplIdx, $this->backgroundPosition[0], $this->backgroundPosition[1], $this->backgroundWidth);
		}
		
		if(!$this->showHeader)
			return;
		
		#$startx = $this->getX();
		#$starty = $this->getY();
		
		$this->Ln(10);
		if($this->PageNo() == 1){
			$this->SetFont($this->fontDetails[0], $this->fontDetails[1], $this->fontDetails[2]);
			$this->SetTextColorArray($this->colorDetails);
			#-------------------- Adressen
			if($this->positionDetailsAdresse != null){
				$this->SetXY($this->positionDetailsAdresse[0], $this->positionDetailsAdresse[1]);
				$this->MultiCell8(0 , $this->heightDetailsAdresse , $this->sd->strasse." ".$this->sd->nr."\n".$this->sd->plz." ".$this->sd->ort,0, $this->alignDetailsAdresse);
			}
			
			if($this->positionDetails != null)
				$this->printContact();
			
			#-------------------- Adressen
			if($this->positionAbsenderZeile != null){
				$this->SetFont($this->fontAbsenderZeile[0], $this->fontAbsenderZeile[1], $this->fontAbsenderZeile[2]);
				$this->SetTextColorArray($this->colorAbsenderZeile);
				$this->SetXY($this->positionAbsenderZeile[0], $this->positionAbsenderZeile[1]);
				$text = ($this->sd->firmaKurz != "" ? $this->sd->firmaKurz : $this->sd->vorname." ".$this->sd->nachname).$this->absenderZeileTrennzeichen." ".$this->sd->strasse." ".$this->sd->nr.$this->absenderZeileTrennzeichen." ".$this->sd->plz." ".$this->sd->ort;
				$this->Cell8(100 , 5 , $text,"",1);
				if($this->positionAbsenderZeileLinie != null)
					$this->Line($this->positionAbsenderZeileLinie[0], $this->positionAbsenderZeileLinie[1], $this->GetStringWidth($text) + 22, $this->positionAbsenderZeileLinie[1]);
			}
		}
		
		if($this->showFalzmarken){
			$this->Line(7 , 105, 11 , 105);
			$this->Line(7 , 148, 11 , 148);
			$this->Line(7 , 210, 11 , 210);
		}
		
		$this->SetTextColorArray($this->colorHeader);
		if($this->positionFirmaSchriftzug != null) {
			$this->SetXY($this->positionFirmaSchriftzug[0], $this->positionFirmaSchriftzug[1]);
			
			$this->SetFont($this->fontFirmaSchriftzug[0], $this->fontFirmaSchriftzug[1], $this->fontFirmaSchriftzug[2]);
			
			$this->LCell(2 , 10 , $this->sd->firmaLang);
		}
		
		if($this->positionSlogan != null AND $this->labelSlogan != "") {
			$this->SetXY($this->positionSlogan[0], $this->positionSlogan[1]);
			
			$this->SetFont($this->fontSlogan[0], $this->fontSlogan[1], $this->fontSlogan[2]);
			
			#$this->Cell8(0, 10, $this->labelSlogan);
			$this->SetAutoPageBreak(false);
			$this->LCell(2 , 10 , $this->labelSlogan);
			$this->SetAutoPageBreak(true, $this->marginBottom);
		}
		
		if($this->PageNo() > 1 AND $this->VarsGRLBM != null){
			$this->printGRLBM($this->VarsGRLBM);
			
			$this->setXY($this->positionPosten2teSeite[0], $this->positionPosten2teSeite[1] + 200);
			$this->setXY($this->positionPosten2teSeite[0], $this->positionPosten2teSeite[1]);
		}
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="AddPage">
	function AddPage($orientation='', $resetFakePageCounter = false, $keepmargins=false, $tocpage=false){
		if($this->isInPosten AND $this->brief->type != "M") {
			$this->Line($this->marginLeft , $this->getY(), 210-$this->marginRight , $this->getY());
			$this->SetX($this->marginLeft);
			#parent::AddPage($orientation, $resetFakePageCounter, $keepmargins, $tocpage);
			$this->SetAutoPageBreak(false);
			$this->SetFont($this->fontPositionen[0], $this->fontPositionen[1], $this->fontPositionen[2]);
			$this->Cell8($this->w - $this->marginRight - $this->marginLeft,5,$this->labelFortsetzung." ".($this->PageNo()+1),0,0,"R");
			$this->SetAutoPageBreak(true, $this->marginBottom);
		}
		
		parent::AddPage($orientation, $resetFakePageCounter, false, $tocpage);
		
		#if($this->PageNo() > 1)
		#	$this->setXY($this->positionPosten2teSeite[0], $this->positionPosten2teSeite[1] + 100);
		
		if($this->isInPosten AND $this->brief->type != "M")
			$this->printPDFHeader();
		
		
		if(property_exists($this, "inHTML") AND $this->inHTML !== false)
			$this->SetFont($this->inHTML[0], $this->inHTML[1], $this->inHTML[2]);
		
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="Footer">
	function Footer() {
		if($this->positionFooter == null) return;
		
		$this->SetTextColorArray($this->colorFooter);
		
	    if($this->footerShowLine) $this->Line($this->positionFooter[0] , $this->positionFooter[1], 210 - $this->positionFooter[0] , $this->positionFooter[1]);
		$this->SetY($this->positionFooter[1] + 1);
		
		$this->SetFont($this->fontFooter[0], $this->fontFooter[1], $this->fontFooter[2]);
		if($this->footerDetailsPosition != null){
			$this->SetXY($this->footerDetailsPosition, $this->positionFooter[1] + 1);
			$this->MultiCell8(0 , 4 , $this->sd->firmaLang.($this->sd->inhaber != "" ? ($this->sd->firmaLang != "" ? ", " : "")."$this->labelInhaber: ".$this->sd->inhaber : "").($this->sd->geschaeftsfuehrer != "" ? ($this->sd->firmaLang != "" ? ", " : "")."$this->labelGeschaeftsfuehrer: ".$this->sd->geschaeftsfuehrer : "")."\n"./*(($this->sd->inhaber != "" OR $this->sd->geschaeftsfuehrer != "") ? "\n" : "").*/($this->sd->bank != "" ? "$this->labelBankverbindung: ".$this->sd->bank.($this->sd->blz != "" ? ", BLZ: ".$this->sd->blz : "").($this->sd->ktonr != "" ? ", Kto.-Nr: " : "").$this->sd->ktonr.(($this->sd->IBAN != "" OR $this->sd->SWIFTBIC != "") ? "\n" : "").($this->sd->IBAN != "" ? "$this->labelIBAN: ".$this->sd->IBAN : "").($this->sd->SWIFTBIC != "" ? ", $this->labelBIC: ".$this->sd->SWIFTBIC : "") : ""),0,"L");
		}
		
		if(($this->sd->amtsgericht != "" OR $this->sd->handelsregister != "") AND $this->footerAmtsgerichtPosition != null){
			$this->SetXY($this->footerAmtsgerichtPosition, $this->positionFooter[1] + 1);
			if($this->sd->amtsgericht != "")
				$this->MultiCell8(0,4, "$this->labelAmtsgericht\n".$this->sd->amtsgericht."\n".$this->sd->handelsregister);
			else $this->MultiCell8(0,4, "$this->labelHandelsregister\n".$this->sd->handelsregister);
		}
		
		if($this->labelSeite != null){
		    $this->SetXY($this->footerSeitePosition, $this->positionFooter[1] + 1);
			$this->SetFont($this->fontFooter[0], "I", $this->fontFooter[2]);
		    $this->Cell8(0,4,$this->labelSeite.' '.($this->getAliasNumPage())."/".$this->getAliasNbPages(),0,0,'L');
		}
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="setBrief">
	public function setBrief(Brief $brief){
		$this->brief = $brief;
	}
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="setBrief">
	public function setBeleg(GRLBM $beleg){
		$this->VarsGRLBM = $beleg;
		if($beleg AND $beleg->A("GRLBMWaehrungFaktor") != "0"){
			$this->waehrungFaktor = $beleg->A("GRLBMWaehrungFaktor");
			$this->waehrungFaktorSet = true;
		}
	}
	// </editor-fold>

	public function setAuftrag(Auftrag $Auftrag){
		$this->VarsAuftrag = $Auftrag;
	}
	
	// <editor-fold defaultstate="collapsed" desc="printContent">
	public function printContent($content){
		$this->setXY($this->positionTextbausteinOben[0], $this->positionTextbausteinOben[1]);
		$this->writeHTML($content);
	}
	// </editor-fold>

	function makeAbschlagsrechnung(GRLBM $GRLBM){
		$AC = anyC::get("GRLBM", "AuftragID", $GRLBM->A("AuftragID"));
		$AC->addAssocV3("isB", "=", "1");
		$B = $AC->getNextEntry();
		
		$this->showPositionen = false;
		$this->sumAlignment = "vertical";
		$this->sumBetragPosition -= 60;
		$this->labelTeilrechnung = $this->labelAbschlagsrechnung;
		
		if($this->labelTeilrechnung != null){
			$this->SetFont($this->fontPositionenHeader[0], "B", $this->fontPositionenHeader[2] + 2);
			$this->Cell(0, 5, $this->labelTeilrechnung.($B != null ? " zu ".$B->A("prefix").$B->A("nummer") : ""), 0, 1);
		}
	}
	
	function makeAbschlussrechnung(GRLBM $GRLBM){
		$aC = new anyC();
		$aC->setCollectionOf("GRLBM");
		$aC->addAssocV3("AuftragID","=", $GRLBM->A("AuftragID"));
		$aC->addAssocV3("isAbschlussrechnung","=", "0");
		$aC->addAssocV3("isR","=", "1");
		$aC->addAssocV3("isAbschlagsrechnung", "=", "1");
		$aC->lCV3();
		
		if($aC->numLoaded() > 0)
			$this->labelTeilrechnungen = $this->labelAbschlagsrechnungen;
	}
	
	// <editor-fold defaultstate="collapsed" desc="printTeilrechnungen">
	function printTeilrechnungen(GRLBM $GRLBM, mPosten $Posten){
		
		$aC = new anyC();
		$aC->setCollectionOf("GRLBM");
		$aC->addAssocV3("AuftragID","=", $GRLBM->A("AuftragID"));
		$aC->addAssocV3("isAbschlussrechnung","=", "0");
		$aC->addAssocV3("isR","=", "1");
		
		if($this->positionTeilrechnungen == "nowhere"){
			$re_netto = 0;
			$re_brutto = 0;
			$desc = "Rechnungen ";

			$lastMwSt = "";

			while($a = $aC->getNextEntry()){
				list($ges_netto, $ges_mwst, $ges_brutto, $mwsts) = $a->getSumOfPosten(true);

				if($lastMwSt == "" AND count($mwsts) > 0) $lastMwSt = $mwsts[0];

				if(count($mwsts) > 1 OR count($mwsts) == 0 OR $lastMwSt != $mwsts[0])
					die(Util::getBasicHTMLError("<b>Achtung:</b><br /><br />Die Teilrechnungen dürfen nicht mehrere unterschiedliche MwSt-Sätze verwenden!<br />Eine Teilrechnung ohne Posten erzeugt diesen Fehler auch (MwSt-Satz 0%)!".print_r($mwsts, true),"open3A-Fehler"));

				$desc .= ($desc != "Rechnungen " ? ", " : "").$a->A("nummer");
				
				$re_netto += $ges_netto;
				$re_brutto += $ges_brutto;
			}
			
			$Posten->addVirtualPosten(1, "", $this->labelTeilrechnungen, $desc, -$re_netto, "$mwsts[0]", "1", -$re_brutto);
		}
		
		if($this->positionTeilrechnungen == "inline"){
			$lastMwSt = "";
			while($a = $aC->getNextEntry()){
				list($ges_netto, $ges_mwst, $ges_brutto, $mwsts) = $a->getSumOfPosten(true);
				
				if($lastMwSt == "" AND count($mwsts) > 0) $lastMwSt = $mwsts[0];
				
				if(count($mwsts) > 1 OR count($mwsts) == 0 OR $lastMwSt != $mwsts[0])
					die(Util::getBasicHTMLError("<b>Achtung:</b><br /><br />Die Teilrechnungen dürfen nicht mehrere unterschiedliche MwSt-Sätze verwenden!<br />Eine Teilrechnung ohne Posten erzeugt diesen Fehler auch (MwSt-Satz 0%)!".print_r($mwsts, true),"open3A-Fehler"));

				#echo "$ges_netto;";
				$Posten->addVirtualPosten(1, "", $a->A("prefix").$a->A("nummer"), "Teilrechnung vom ".Util::formatDate($this->language, $a->A("datum")), -$ges_netto, "$mwsts[0]", "1", -$ges_brutto);
			}
		}
		
		if($this->positionTeilrechnungen == "above"){
			$widthRechnungsnummer = 30;
			$widthDatum = 30;
			$widthNetto = 40;
			$widthMwSt = 37;
			$widthBrutto = 33;

			$this->SetFont($this->fontPositionenHeader[0], "B", $this->fontPositionenHeader[2] + 2);

			if($this->labelTeilrechnungen != null){
				$this->Cell(0, 5, $this->labelTeilrechnungen, 0, 1);
				$this->ln(2);
			}
			
			$this->SetFont($this->fontPositionenHeader[0], $this->fontPositionenHeader[1], $this->fontPositionenHeader[2]);
			$this->Cell($widthRechnungsnummer, 5, "Rechnungsnr.", 0, 0, "R");
			$this->Cell($widthDatum, 5, $this->labelDatum, 0 , 0);
			$this->Cell($widthNetto, 5, $this->sumShowGesamtNetto ? "Netto" : "", 0 , 0, "R");
			$this->Cell($widthMwSt, 5, $this->sumShowUmsatzsteuer ? "Umsatzsteuer" : "", 0 , 0, "R");
			$this->Cell($widthBrutto, 5, "Brutto", 0 , 1, "R");

			$this->Line($this->marginLeft , $this->getY(), 210-$this->marginRight , $this->getY());



			$this->SetFont($this->fontPositionen[0], $this->fontPositionen[1], $this->fontPositionen[2]);

			$re_netto = 0;
			$re_brutto = 0;
			$desc = "Rechnungen ";

			$lastMwSt = "";

			while($a = $aC->getNextEntry()){
				list($ges_netto, $ges_mwst, $ges_brutto, $mwsts) = $a->getSumOfPosten(true);

				if($lastMwSt == "" AND count($mwsts) > 0) $lastMwSt = $mwsts[0];

				if(count($mwsts) > 1 OR count($mwsts) == 0 OR $lastMwSt != $mwsts[0])
					die(Util::getBasicHTMLError("<b>Achtung:</b><br /><br />Die Teilrechnungen dürfen nicht mehrere unterschiedliche MwSt-Sätze verwenden!<br />Eine Teilrechnung ohne Posten erzeugt diesen Fehler auch (MwSt-Satz 0%)!".print_r($mwsts, true),"open3A-Fehler"));

				$this->Cell($widthRechnungsnummer, 5, $a->getA()->nummer, 0, 0, "R");
				$this->Cell($widthDatum, 5, Util::formatDate($this->language, $a->getA()->datum), 0, 0);
				$this->Cell8($widthNetto, 5, $this->sumShowGesamtNetto ? $this->cur($this->formatCurrency($this->language, $ges_netto, true)) : "", 0, 0, "R");
				$this->Cell(17, 5, $this->sumShowUmsatzsteuer ? Util::formatNumber("de_DE", $mwsts[0]*1, 2, true, false)."%" : "", 0, 0, "R");
				$this->Cell8($widthMwSt - 17, 5, $this->sumShowUmsatzsteuer ? $this->cur($this->formatCurrency($this->language, $ges_mwst, true)) : "", 0, 0, "R");
				$this->Cell8($widthBrutto, 5, $this->cur($this->formatCurrency($this->language, $ges_brutto, true)), 0, 1, "R");

				$desc .= ($desc != "Rechnungen " ? ", " : "").$a->getA()->nummer;
				#echo $ges_brutto."<br />";
				$re_netto += $ges_netto;
				$re_brutto += $ges_brutto;
			}

			$this->Line($this->marginLeft , $this->getY(), 210-$this->marginRight , $this->getY());

			$this->ln(10);
			$this->SetFont($this->fontPositionenHeader[0], "B", $this->fontPositionenHeader[2] + 2);
			
			if($this->labelAbschlussrechnung != null){
				$this->Cell(0, 5, $this->labelAbschlussrechnung, 0, 1);
				$this->ln(2);
			}
			
			$Posten->addVirtualPosten(1, "", $this->labelTeilrechnungen, $desc, -$re_netto, "$mwsts[0]", "1", -$re_brutto);
		}
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="printAdresse">
	function printAdresse(Adresse $Adresse){
			
		$this->VarsEmpfaengerAdresse = $Adresse;
		
		if($this->positionEmpfaengerAdresse == null) return;
		
		$this->SetTextColorArray($this->colorEmpfaengerAdresse);
		
		$this->SetXY($this->positionEmpfaengerAdresse[0], $this->positionEmpfaengerAdresse[1]);
		$this->SetFont($this->fontEmpfaengerAdresse[0], $this->fontEmpfaengerAdresse[1], $this->fontEmpfaengerAdresse[2]);

		if(Session::isPluginLoaded("mAnsprechpartner") AND isset($this->brief->Auftrag) AND $this->brief->Auftrag->A("kundennummer") > 0){
			$AP = Ansprechpartner::getAnsprechpartner("Adresse", $this->brief->Auftrag->A("kundennummer"), $this->brief->type);
			
			if($this->VarsGRLBM->A("GRLBMAnsprechpartnerID") != "0")
				$AP = new Ansprechpartner($this->VarsGRLBM->A("GRLBMAnsprechpartnerID"));
			
			if($AP != null){
				$this->VarsEmpfaengerAnsprechpartner = $AP;
				$Adresse->changeA("anrede", $AP->A("AnsprechpartnerAnrede"));
				$Adresse->changeA("vorname", $AP->A("AnsprechpartnerVorname"));
				$Adresse->changeA("nachname", $AP->A("AnsprechpartnerNachname"));
			}
		}
		
		$this->MultiCell8($this->widthEmpfaengerAdresse , $this->heightEmpfaengerAdresse , $Adresse->getFormattedAddress($this->showAnredeInEmpfaenger, $this->language, $this->stammdaten),0,"L");
	}
	// </editor-fold>

	public function printPaymentQR(Stammdaten $S, GRLBM $G){
		if(!$this->paymentShowQR)
			return;
		
		if(!Session::isPluginLoaded("mBezahlCode") OR $G == null OR $G->getMyPrefix() != "R")
			return;
		
		if(Session::isPluginLoaded("mBezahlCode")){
			require_once Util::getRootPath()."/open3A/BezahlCode/lib/src/Type/AbstractType.php";
			require_once Util::getRootPath()."/open3A/BezahlCode/lib/src/Type/SepaTransfer.php";
			require_once Util::getRootPath()."/open3A/BezahlCode/lib/phpqrcode.php";
			$bezahlCode = new MarcusJaschen\BezahlCode\Type\SepaTransfer();

			$bezahlCode->setTransferData(
				$S->A("firmaLang"),
				$S->A("IBAN"),
				$S->A("SWIFTBIC"),
				$G->A("bruttobetrag"),
				$G->A("prefix")."".$G->A("nummer")." vom ".$G->A("datum")
			);
			
			$temp = Util::getTempFilename("QR", "png");
			$bezahlCode->saveBezahlCode($temp);
			$im = imagecreatefromstring(file_get_contents($temp));
			unlink($temp);
		}
		
		$this->ImageGD($im, $this->GetMargin("L"), $this->GetY(), 25);
		$this->SetX($this->GetMargin("L") + 25);
		
		$this->SetFont($this->fontTextbausteine[0], $this->fontTextbausteine[1], $this->fontTextbausteine[2]);
		$this->MultiCell8(0, 5, "Erfassen Sie den QR-Code mit Ihrem Handy,\num diese Rechnung einfach und schnell zu bezahlen.", 0, "L");
		$this->Ln(20);
	}
	
	// <editor-fold defaultstate="collapsed" desc="printGRLBM">
	public function printGRLBM(GRLBM $GRLBM){
		// <editor-fold defaultstate="collapsed" desc="Aspect:jP">
		try {
			$MArgs = func_get_args();
			return Aspect::joinPoint("around", $this, __METHOD__, $MArgs);
		} catch (AOPNoAdviceException $e) {}
		Aspect::joinPoint("before", $this, __METHOD__, $MArgs);
		// </editor-fold>
		
		
		if($GRLBM->A("isM") != "1")
			$Auftrag = new Auftrag($GRLBM->A("AuftragID"));
		else {
			$G = new GRLBM($GRLBM->A("AuftragID"));
			$Auftrag = new Auftrag($G->A("AuftragID"));
			
			if($GRLBM->A("nummer") == "1" AND $this->labelZahlungserinnerung)
				$this->labelLong["M"] = $this->labelZahlungserinnerung;
		}
		
		if($this->positionRechnungsInfo == null)
			return;
		
		$ud = new mUserdata();
		$cv = $ud->getUDValue("activePDFCopyVermerk");
		$S = $this->stammdaten;
		if(!$S)
			$S = Stammdaten::getActiveStammdaten();

		$y = $this->positionRechnungsInfo[1];
		$x = $this->positionRechnungsInfo[0];
	
		if($this->PageNo() > 1){
			$y = $this->positionRechnungsInfo2teSeite[1];
			$x = $this->positionRechnungsInfo2teSeite[0];
			
			if($this->positionRechnungsInfo2teSeite == null)
				return;
		}
		
	
		if($GRLBM->isCopy AND ($cv == null OR $cv == "true") AND $this->positionKopieLabel != null){
			$this->SetTextColor(170);
			$this->SetFont($this->fontKopieLabel[0], $this->fontKopieLabel[1], $this->fontKopieLabel[2]);
			
			if($this->PageNo() == 1) {
				$this->SetXY($this->positionKopieLabel[0], $this->positionKopieLabel[1]);
				$this->Cell8(40, 10, $this->labelKopie,0,0,"L");
			}
			
			if($this->PageNo() > 1 AND $this->positionKopieLabel2teSeite != null) { 
				$this->SetXY($this->positionKopieLabel2teSeite[0], $this->positionKopieLabel2teSeite[1]);
				$this->Cell8(40, 10, $this->labelKopie,0,0,"L");
			}
			
			$this->SetTextColor(0);
		}
		
		
		if(isset($this->labelLong[$GRLBM->getMyPrefix(true)])){
			$storedPrefix = $GRLBM->A("prefix");
			if($storedPrefix == "A" AND $GRLBM->A("printAB") == "1") $storedPrefix = $S->getPrefix($GRLBM->getMyPrefix(true)); //Legacy

			$prefix = ($GRLBM->A("prefix") != "" ? $storedPrefix : $S->getPrefix($GRLBM->getMyPrefix()));

			if($GRLBM->getMyPrefix() != "M")
				$this->contentRechnungsInfo["belegnummer"] = array($this->labelLong[$GRLBM->getMyPrefix(true)], Aspect::joinPoint("belegnummer", $this, __METHOD__, array($prefix.$GRLBM->A("nummer")), $prefix.$GRLBM->A("nummer")), array($this->fontRechnungsInfo[0], "B", $this->fontRechnungsInfo[2]));
			else {
				$oldGRLBM = new GRLBM($GRLBM->A("AuftragID"));
				$this->contentRechnungsInfo["belegnummer"] = array($this->labelLong[$GRLBM->getMyPrefix(true)], $prefix.$oldGRLBM->A("nummer")."/".$GRLBM->A("nummer"), array($this->fontRechnungsInfo[0], "B", $this->fontRechnungsInfo[2]));
			}
		}
		
		
		if($this->labelZuLieferschein != null AND $GRLBM->getMyPrefix() == "R"){
			
			$AC = anyC::get("GRLBM", "AuftragID", $GRLBM->A("AuftragID"));
			$AC->addAssocV3("isR", "=", "1");
			$AC->addAssocV3("datum", "<", $GRLBM->hasParsers ? Util::CLDateParser($GRLBM->A("datum"), "store") : $GRLBM->A("datum"));
			$AC->addOrderV3("datum", "DESC");
			$AC->setFieldsV3(array("datum"));
			$lastR = $AC->getNextEntry();
			
			$AC = anyC::get("GRLBM", "AuftragID", $GRLBM->A("AuftragID"));
			$AC->addAssocV3("isL", "=", "1");
			$AC->addAssocV3("datum", "<=", $GRLBM->hasParsers ? Util::CLDateParser($GRLBM->A("datum"), "store") : $GRLBM->A("datum"));
			if($lastR != null)
				$AC->addAssocV3("datum", ">", $lastR->A("datum"));
			$AC->setLimitV3("3");
			
			$i = 0;
			while($L = $AC->getNextEntry()){
				if(!isset($this->contentRechnungsInfo["ZuLieferschein"]))
					$this->contentRechnungsInfo["ZuLieferschein"] = array();
				
				if(isset($this->contentRechnungsInfo["ZuLieferschein"][$L->A("nummer")]))
					continue;
				
				$this->contentRechnungsInfo["ZuLieferschein"][$L->A("nummer")] = array($i == 0 ? $this->labelZuLieferschein : "", $L->A("prefix").$L->A("nummer"));
				$i++;
			}
		}
		
		
		
		if($this->labelDatum != null)
			$this->contentRechnungsInfo["Datum"] = array($this->labelDatum, $GRLBM->A("datum") * 1 < 100000000 ? $GRLBM->A("datum")  : Util::formatDate($this->language, $GRLBM->A("datum")));
		
		
		if(($GRLBM->getMyPrefix() == "R" OR $GRLBM->getMyPrefix() == "A" OR $GRLBM->getMyPrefix() == "B") AND $this->labelLieferdatum != null){
			if(trim($GRLBM->A("lieferDatumText")) != "")
				$this->contentRechnungsInfo["Lieferdatum"] = array($this->labelLieferdatum, $GRLBM->A("lieferDatumText"));
			else
				$this->contentRechnungsInfo["Lieferdatum"] = array($this->labelLieferdatum, $GRLBM->A("lieferDatum") * 1 < 100000000 ? $GRLBM->A("lieferDatum") : Util::CLDateParserE($GRLBM->A("lieferDatum")));
		}

		
		if($S->A("ustidnr") != "" AND $this->PageNo() == 1 AND $this->labelUstID != null)
			$this->contentRechnungsInfo["UstID"] = array(preg_match("/(^[A-Za-z]{2})/", $S->A("ustidnr")) ? $this->labelUstID : $this->labelStNr, $S->A("ustidnr"));
		
		
		if($Auftrag->A("kundennummer") != "" AND $Auftrag->A("kundennummer") != "-2" AND $this->labelKundennummer != null)
			$this->contentRechnungsInfo["Kundennummer"] = array($this->labelKundennummer, Aspect::joinPoint("kundennummer", $this, __METHOD__, array($S, $Auftrag), $S->getPrefix("K").$Auftrag->A("kundennummer")));

	
		if($Auftrag->A("UStIdNr") != "" AND $this->PageNo() == 1)
			$this->contentRechnungsInfo["KundeUstID"] = array(preg_match("/(^[A-Za-z]{2})/",$Auftrag->A("UStIdNr")) ? $this->labelKundeUstID : $this->labelKundeStNr, $Auftrag->A("UStIdNr"));
		
		
		if($Auftrag->A("AuftragAdresseNiederlassungID") > 0 AND $this->PageNo() == 1 AND $this->labelFiliale){
			$filiale = json_decode($Auftrag->A("AuftragAdresseNiederlassungData"));
			$this->contentRechnungsInfo["Filiale"] = array($this->labelFiliale, $filiale->AdresseNiederlassungOrt);
		}
	
		
		if($Auftrag->A("lieferantennummer") != "" AND $this->PageNo() == 1 AND $this->labelFaxLieferant)
			$this->contentRechnungsInfo["FaxLieferant"] = array($this->labelFaxLieferant, $this->VarsEmpfaengerAdresse->A("fax"));
		
		
		if($this->VarsEmpfaengerAdresse->A("lieferantennr") != "" AND $this->labelLieferantennummer)
			$this->contentRechnungsInfo["Lieferantennummer"] = array($this->labelLieferantennummer, $this->VarsEmpfaengerAdresse->A("lieferantennr"));
		
		
		if($this->VarsEmpfaengerAdresse->A("tel") != "" AND $this->labelKundeTelefon)
			$this->contentRechnungsInfo["KundeTelefon"] = array($this->labelKundeTelefon, $this->VarsEmpfaengerAdresse->A("tel"));
		
		if($this->VarsEmpfaengerAdresse->A("nachname") != "" AND $this->labelKundeAnsprechpartner){
			$TA = clone $this->VarsEmpfaengerAdresse;
			$TA->changeA("vorname", "");
			
			$this->contentRechnungsInfo["KundeAnsprechpartner"] = array($this->labelKundeAnsprechpartner, Util::CLFormatAnrede($TA, true)." ".$TA->A("nachname"));
		}
		
		if($this->VarsEmpfaengerAdresse->A("email") != "" AND $this->labelKundeEMail)
			$this->contentRechnungsInfo["KundeEMail"] = array($this->labelKundeEMail, $this->VarsEmpfaengerAdresse->A("email"));
		
		
		if($this->VarsEmpfaengerAnsprechpartner != null AND $this->VarsEmpfaengerAnsprechpartner->A("AnsprechpartnerTel") != "" AND $this->labelKundeTelefon)
			$this->contentRechnungsInfo["KundeTelefon"] = array($this->labelKundeTelefon, $this->VarsEmpfaengerAnsprechpartner->A("AnsprechpartnerTel"));
		
		if($this->VarsEmpfaengerAnsprechpartner != null AND $this->VarsEmpfaengerAnsprechpartner->A("AnsprechpartnerNachname") != "" AND $this->labelKundeAnsprechpartner){
			$TA = clone $this->VarsEmpfaengerAdresse;
			$TA->changeA("vorname", "");
			$TA->changeA("anrede", $this->VarsEmpfaengerAnsprechpartner->A("AnsprechpartnerAnrede"));
			
			$this->contentRechnungsInfo["KundeAnsprechpartner"] = array($this->labelKundeAnsprechpartner, Util::CLFormatAnrede($TA, true)." ".$this->VarsEmpfaengerAnsprechpartner->A("AnsprechpartnerNachname"));
		}
		
		if($this->VarsEmpfaengerAnsprechpartner != null AND $this->VarsEmpfaengerAnsprechpartner->A("AnsprechpartnerEmail") != "" AND $this->labelKundeEMail)
			$this->contentRechnungsInfo["KundeEMail"] = array($this->labelKundeEMail, $this->VarsEmpfaengerAnsprechpartner->A("AnsprechpartnerEmail"));
		
		
		if(Session::isPluginLoaded("mProjekt") AND $this->VarsAuftrag != null AND $this->VarsAuftrag->A("ProjektID") != "0"){
			$Projekt = new Projekt($Auftrag->A("ProjektID"));

			if($this->labelBestellnummer AND $Projekt->A("ProjektBestellnummer") != "")
				$this->contentRechnungsInfo["Bestellnummer"] = array($this->labelBestellnummer, $Projekt->A("ProjektBestellnummer"));
			
			
			if($this->labelBestelldatum AND $Projekt->A("ProjektBestelldatum") != "")
				$this->contentRechnungsInfo["Bestelldatum"] = array($this->labelBestelldatum, $Projekt->A("ProjektBestelldatum"));
		}

		
		foreach($this->orderRechnungsInfo AS $col){
			if($col == "custom1"){
				for($i = 1;$i < 10; $i++){
					$labelName = "labelCustomField$i";
					$fieldName = "GRLBMCustomField$i";
					if(isset($this->$labelName) AND $this->$labelName != null AND $GRLBM->A($fieldName) != null AND $GRLBM->A($fieldName) != ""){
						$y += $this->heightRechnungsInfo;
						$this->SetXY($x,$y);
						
						$this->printInfoCell($this->$labelName, $GRLBM->A($fieldName));
					}
				}
				continue;
			}
			
			if($col == "custom2"){
				for($i = 11;$i < 20; $i++){
					$labelName = "labelCustomField$i";
					$fieldName = "GRLBMCustomField$i";
					if(isset($this->$labelName) AND $this->$labelName != null AND $GRLBM->A($fieldName) != null AND $GRLBM->A($fieldName) != ""){
						$y += $this->heightRechnungsInfo;
						$this->SetXY($x,$y);
						
						$this->printInfoCell($this->$labelName, $GRLBM->A($fieldName));
					}
				}
				
				continue;
			}
			
			if($col == "leer"){
				$y += $this->heightRechnungsInfo;
				
				continue;
			}
			
			if($col == "ZuLieferschein"){
				if(!isset($this->contentRechnungsInfo[$col]))
					continue;

				foreach($this->contentRechnungsInfo[$col] AS $v){
					$y += $this->heightRechnungsInfo;
					$this->SetXY($x,$y);

					$this->printInfoCell($v[0], $v[1], isset($v[2]) ? $v[2] : null);
				}
				
				continue;
			}
			
			if(!isset($this->contentRechnungsInfo[$col]))
				continue;
			
			if(!is_array($this->contentRechnungsInfo[$col]))
				$this->contentRechnungsInfo[$col] = array($this->contentRechnungsInfo[$col]);
			
			$y += $this->heightRechnungsInfo;
			$this->SetXY($x,$y);
			
			$this->printInfoCell($this->contentRechnungsInfo[$col][0], $this->contentRechnungsInfo[$col][1], isset($this->contentRechnungsInfo[$col][2]) ? $this->contentRechnungsInfo[$col][2] : null);
		}
		

		$this->GRLBMNextLine = array($x, $y + 4);
		
		Aspect::joinPoint("after", $this, __METHOD__, $MArgs);
		
		if($this->PageNo() == 1)
			$this->setXY($this->positionTextbausteinOben[0], $this->positionTextbausteinOben[1]);
		#else $this->setXY($this->positionTextbausteinOben[0], $this->positionTextbausteinOben[1]);
	}
	// </editor-fold>

	public function replaceVariables(Textbaustein $T, $toText = false){
		$TBText = $this->cur($T->A("text"));
		
		$date = Util::CLDateParser($this->VarsGRLBM->A("datum"), "store");
		if($date == -1) $date = $this->VarsGRLBM->A("datum");
		
		if($this->VarsEmpfaengerAdresse != null)
			$TBText = str_replace("{Anrede}", Util::formatAnrede($this->language, $this->VarsEmpfaengerAdresse), $TBText);

		$format = Util::getLangNumbersFormat();
		if($format[0] == ".")
			$format[0] = "\.";
		
		if(preg_match_all("/{Rabatt:([0-9$format[0]]*)%}/", $TBText, $regs)){
			foreach($regs[1] AS $mv){
				$rabatt = $this->formatCurrency($this->language, Util::kRound($this->gesamt_brutto / 100 * (100 - Util::CLNumberParser($mv, "store")),2), true);
				$TBText = str_replace("{Rabatt:{$mv}%}", $this->cur($rabatt), $TBText);
			}
		}
		
		if(preg_match_all("/{\+([0-9]+)Tage}/", $TBText, $regs)){
			foreach($regs[1] AS $mv){
				#$rabatt = $this->formatCurrency($this->language, Util::kRound($this->gesamt_brutto / 100 * (100 - Util::CLNumberParser($mv, "store")),2), true);
				$TBText = str_replace("{+{$mv}Tage}", Util::CLDateParser($date + $mv * 3600 * 24), $TBText);
			}
		}

		$D = new Datum($date);
		$D->subWeek();
		$TBText = str_replace("{+1Woche}", Util::CLDateParser($date + 7 * 3600 * 24), $TBText);
		$TBText = str_replace("{+2Wochen}", Util::CLDateParser($date + 14 * 3600 * 24), $TBText);
		$TBText = str_replace("{+3Wochen}", Util::CLDateParser($date + 21 * 3600 * 24), $TBText);
		$TBText = str_replace("{+6Wochen}", Util::CLDateParser($date + 42 * 3600 * 24), $TBText);
		$TBText = str_replace("{Kalenderwoche}", date("W", $date), $TBText);
		$TBText = str_replace("{Kalenderwoche-1}", date("W", $D->time()), $TBText);

		if($this->VarsAuftrag != null){
			$U = new User($this->VarsAuftrag->A("UserID"));
			$TBText = str_replace("{Benutzername}", $U->A("name"), $TBText);
		} else
			$TBText = str_replace("{Benutzername}", "", $TBText);
		
		if(strpos($TBText, "{+1Monat}") !== false) {
			$date = new Datum($date);
			$date->addMonth();
			$TBText = str_replace("{+1Monat}",Util::CLDateParser($date->time()),$TBText);
		}
		
		if(strpos($TBText, "{+3Monate}") !== false) {
			$date = new Datum($date);
			$date->addMonth();
			$date->addMonth();
			$date->addMonth();
			$TBText = str_replace("{+3Monate}",Util::CLDateParser($date->time()),$TBText);
		}
			
		#$TBText = str_replace("{Gesamtsumme}", $this->formatCurrency($this->language, $this->gesamt_brutto, true), $TBText);
		$TBText = str_replace("{Gesamtsumme}", $this->cur($this->formatCurrency($this->language, $this->gesamt_brutto, true)), $TBText);
		
		$TBText = Aspect::joinPoint("alter", $this, __METHOD__, array($TBText, $T, $this->VarsGRLBM), $TBText);
		
		if($toText){
			$TBText = str_replace("</p>", "</p>\n", $TBText);
			$TBText = trim(strip_tags($TBText));
		}
		
		return $TBText;
	}
	
	// <editor-fold defaultstate="collapsed" desc="printTextbaustein">
	function printTextbaustein(Textbaustein $T){
		Aspect::joinPoint("above", $this, __METHOD__, array($this->TextbausteinNummer, $T));
		#$T->loadMe();
		$TBText = $this->replaceVariables($T);
		
		$this->SetFont($this->fontTextbausteine[0], $this->fontTextbausteine[1], $this->fontTextbausteine[2]);
		$this->SetTextColorArray($this->colorTextbausteine);
		
		if($TBText != "" AND substr($TBText, 0, 1) != "<")
			$this->MultiCell8($this->widthTextbaustein , 5 , $TBText,0,"L");
		
		if($TBText != "" AND $TBText != "<p></p>" AND substr($TBText, 0, 1) == "<"){
			$this->stackFont($this->fontTextbausteine);
			$this->paragraph = 0; //reset for margin above paragraph
			$this->WriteHTML($TBText);
			$this->ln(3);
		}
		
		Aspect::joinPoint("appendToTextbaustein", $this, __METHOD__, array($this->TextbausteinNummer, $T));

		$this->ln();
		$this->TextbausteinNummer++;
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="printPosten">
	function printPosten(mPosten $P){
		try {
			$MArgs = func_get_args();
			return Aspect::joinPoint("around", $this, __METHOD__, $MArgs);
		} catch (AOPNoAdviceException $e) {}
		Aspect::joinPoint("before", $this, __METHOD__, $MArgs);
		
		while($EP = $P->getNextEntry()){
			if($EP->A("rabatt") != null AND $EP->A("rabatt") > 0){
				
				$fpdf->widthRabatt = 15;
				$fpdf->widthGesamt -= 5;
				$fpdf->widthBezeichnung -= 10;
				
				break;
			}
		}
		
		$P->resetPointer();
		
		$this->isInPosten = true;
		$P->getFPDF($this, $this->VarsGRLBM);
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="printGesamt">
	function printGesamt($type){
		$this->SetAutoPageBreak(false);
		$this->isInPosten = false;
		
		$this->gesamt_brutto = Aspect::joinPoint("alterGesBrutto", $this, __METHOD__, array($this->gesamt_brutto), $this->gesamt_brutto);
		
		if(in_array($type, $this->sumHideOn)) return;

		#die($this->GetY().";".($this->h - $this->marginBottom).";".$this->h.";".$this->marginBottom);

		if($this->GetY() > $this->h - $this->marginBottom)
			$this->AddPage();

		$this->SetFont($this->fontSumHeaders[0], $this->fontSumHeaders[1], $this->fontSumHeaders[2]);
		$this->SetTextColorArray($this->colorSum);
		if($type != "Kalk"){# OR $type == "A" OR $type == "G" OR $type == "B"){
			#print_r($this->gesamt_brutto);
			$sum_mwst = 0;
			$sum_netto = 0;
			foreach($this->gesamt_netto as $key => $value){
				$sum_mwst += Util::kRound($value * ($key / 100),2);
				#$sum_netto += $value;
			}
			
			foreach($this->artikelsteuern AS $key => $value)
				$this->artikelsteuern[$key] = Util::kRound ($value, 2);
			
			$sum_ges = $this->gesamt_brutto;# =  = Util::kRound($sum_netto)+Util::kRound($sum_mwst);
			$sum_netto = Util::kRound($sum_ges,2) - $sum_mwst;
			$sum_ges += array_sum($this->artikelsteuern);
			
			#$sum_netto += $this->VarsGRLBM->A("versandkosten");
			#print_r($this->gesamt_netto);
			#print_r($sum_netto);
			#if(abs($sum_netto - array_sum($this->gesamt_netto)) > 0.1)
			#	die("Die Netto-Beträge stimmen nicht überein!<pre>Aus Posten berechneter Netto-Betrag: ".array_sum($this->gesamt_netto)."\nAus Brutto-Betrag berechneter Netto-Betrag: $sum_netto</pre>Bitte melden Sie diesen Fehler zusammen mit einer Beispielrechnung an Support@phynx.de<br />Vielen Dank");

			switch($this->sumAlignment){
				case "horizontal":
					$firstLineY = $this->GetY();

					if($this->sumShowGesamtNetto){
						$this->SetXY($this->sumGesamtNettoPosition, $firstLineY);
						$this->Cell8($this->sumGesamtNettoWidth, 5, $this->labelGesamtNetto, 0, 0, "R");
					}
					
					if($this->sumShowArtikelsteuer){
						$this->SetXY($this->sumArtikelsteuerPosition, $firstLineY);
						$this->Cell8($this->sumArtikelsteuerWidth, 5, $this->labelArtikelsteuer, 0, 0, "R");
					}
					
					if($this->sumShowUmsatzsteuer){
						$this->SetXY($this->sumUmsatzsteuerPosition, $firstLineY);
						$this->Cell8($this->sumUmsatzsteuerWidth, 5, $this->labelUmsatzsteuer, 0, 0, "R");
					}
					if($this->sumShowBetrag){
						$this->SetXY($this->sumBetragPosition, $firstLineY);
						if($type == "R") $this->Cell8($this->sumBetragWidth, 5, $this->labelRechnungsbetrag, 0, 0, "R");
						elseif($type == "G") $this->Cell8($this->sumBetragWidth, 5, $this->labelGutschriftsbetrag, 0, 0, "R");
						else $this->Cell8($this->sumBetragWidth, 5, $this->labelGesamtBrutto, 0, 0, "R");
					}

					$this->Ln();
					$secondLineY = $this->GetY();

					$this->SetFont($this->fontSumNetto[0], $this->fontSumNetto[1], $this->fontSumNetto[2]);

					if($this->sumShowGesamtNetto){
						if(!$this->sumShowAlleNettopreise){
							$this->SetXY($this->sumGesamtNettoPosition, $secondLineY);
							$this->Cell8($this->sumGesamtNettoWidth, 5, $this->cur($this->formatCurrency($this->language, $sum_netto, true)), 0, 0, "R");
						} else {
							$i = 0;
							foreach($this->gesamt_netto as $key => $value){
								$this->SetXY($this->sumGesamtNettoPosition, $secondLineY + $i * 5);
								#$this->Cell(15, 5, Util::formatNumber("de_DE", $key*1, 2, true, false)."%", 0, 0, "R");
								$this->Cell8($this->sumGesamtNettoWidth, 5, $this->cur($this->formatCurrency($this->language, $value, true)), 0, 2, "R");
								$i++;
							}
						}
					}

					$this->SetFont($this->fontSumUmsatzsteuer[0], $this->fontSumUmsatzsteuer[1], $this->fontSumUmsatzsteuer[2]);

					$i = 0;
					foreach($this->artikelsteuern as $key => $value){
						if($key == "0.00" AND !$this->show0ProzentMwSt) continue;

						if($this->sumShowArtikelsteuer){
							$this->SetXY($this->sumArtikelsteuerPosition, $secondLineY + $i * 5);
							$this->Cell(15, 5, Util::formatNumber($this->language, $key*1, 2, true, false)."%", 0, 0, "R");
							$this->Cell8($this->sumArtikelsteuerWidth - 15, 5, $this->cur($this->formatCurrency($this->language, $value, true)), 0, 2, "R");
						}
						$i++;
					}
					
					$i = 0;
					foreach($this->gesamt_netto as $key => $value){
						if($key == "0.00" AND !$this->show0ProzentMwSt) continue;

						if($this->sumShowUmsatzsteuer){
							$this->SetXY($this->sumUmsatzsteuerPosition, $secondLineY + $i * 5);
							$this->Cell(15, 5, Util::formatNumber($this->language, $key*1, 2, true, false)."%", 0, 0, "R");
							$this->Cell8($this->sumUmsatzsteuerWidth - 15, 5, $this->cur($this->formatCurrency($this->language, $value * ($key / 100), true)), 0, 2, "R");
						}
						$i++;
					}

					//Fixes mispaced Rechnungsbetrag
					if($i == 0) $i++;

					if(!$this->sumShowUmsatzsteuer) $this->ln();

					#$this->SetFont($this->fontPositionen[0], 'B', $this->fontPositionen[2]);
					if(count($this->gesamt_netto) > 0)
						$this->SetY($this->GetY() - 5);

					if($this->sumShowBetrag){
						$this->SetFont($this->fontSumBetrag[0], $this->fontSumBetrag[1], $this->fontSumBetrag[2]);

						$this->SetXY($this->sumBetragPosition, $secondLineY + ($i - 1) * 5);
						$this->Cell8($this->sumBetragWidth, 5, $this->cur($this->formatCurrency($this->language, $sum_ges, true)), 0, 0, "R");
					}

				break;
				case "vertical":
					$firstLineY = $this->GetY();
					$labelsRight = $this->sumBetragPosition - 50;
					$i = 0;
					if($this->sumShowGesamtNetto AND !$this->sumShowAlleNettopreise){
						$this->SetFont($this->fontSumHeaders[0], $this->fontSumHeaders[1], $this->fontSumHeaders[2]);
						$this->SetXY($labelsRight, $firstLineY);
						$this->Cell8(50, 5, $this->labelGesamtNetto, 0, 0, "R");
						$this->SetFont($this->fontSumBetrag[0], $this->fontSumBetrag[1], $this->fontSumBetrag[2]);

						#if(!$this->sumShowAlleNettopreise){
							$this->Cell8($this->sumBetragWidth, 5, $this->cur($this->formatCurrency($this->language, $sum_netto, true)), 0, 0, "R");
							$i++;
						/*} else {
							foreach($this->gesamt_netto as $key => $value){
								$this->SetXY($this->sumBetragPosition, $firstLineY + $i * 5);
								$this->Cell(15, 5, Util::formatNumber($this->language, $key*1, 2, true, false)."%", 0, 0, "R");
								$this->Cell8($this->sumBetragWidth - 15, 5, $this->cur($this->formatCurrency($this->language, $value, true)), 0, 0, "R");

								$i++;
							}
						}*/
					}
					if($this->sumShowUmsatzsteuer AND !$this->sumShowAlleNettopreise){
						$this->SetFont($this->fontSumHeaders[0], $this->fontSumHeaders[1], $this->fontSumHeaders[2]);
						$this->SetXY($labelsRight, $firstLineY + $i * 5);
						$this->Cell8(50, 5, $this->labelUmsatzsteuer, 0, 0, "R");

						$this->SetFont($this->fontSumBetrag[0], $this->fontSumBetrag[1], $this->fontSumBetrag[2]);

						foreach($this->gesamt_netto as $key => $value){
							$this->SetXY($this->sumBetragPosition, $firstLineY + $i * 5);
							$this->SetFont($this->fontSumHeaders[0], $this->fontSumHeaders[1], $this->fontSumHeaders[2]);
							$this->Cell(15, 5, Util::formatNumber($this->language, $key*1, 2, true, false)."%", 0, 0, "R");
							$this->SetFont($this->fontSumBetrag[0], $this->fontSumBetrag[1], $this->fontSumBetrag[2]);
							$this->Cell8($this->sumBetragWidth - 15, 5, $this->cur($this->formatCurrency($this->language, $value * ($key / 100), true)), 0, 0, "R");

							$i++;
						}
					}

					if($this->sumShowAlleNettopreise){
						$this->SetXY($labelsRight, $firstLineY);
						$this->Cell8(50, 5, $this->labelGesamtNetto, 0, 0, "R");
						$this->SetFont($this->fontSumUmsatzsteuer[0], $this->fontSumUmsatzsteuer[1], $this->fontSumUmsatzsteuer[2]);

						$this->Cell8($this->sumBetragWidth, 5, $this->cur($this->formatCurrency($this->language, $sum_netto, true)), 0, 0, "R");
						$i++;
						
						foreach($this->gesamt_netto as $key => $value){
							$this->SetXY($labelsRight, $firstLineY + $i * 5);
							$this->Cell8(25, 5, Util::formatNumber($this->language, $key*1, 2, true, false)."% ".$this->labelUmsatzsteuer.":", 0, 0, "R");
							$this->Cell8(25, 5, $this->cur($this->formatCurrency($this->language, $value, true)), 0, 0, "R");
							$this->Cell8($this->sumBetragWidth, 5, $this->cur($this->formatCurrency($this->language, $value * ($key / 100), true)), 0, 0, "R");

							$i++;
						}
					}

					if($this->sumShowBetrag){
						$this->SetFont($this->fontSumHeaders[0], $this->fontSumHeaders[1], $this->fontSumHeaders[2]);
						$this->SetXY($labelsRight, $firstLineY + $i * 5);
						if($type == "R") $this->Cell8(50, 5, $this->labelRechnungsbetrag, 0, 0, "R");
						if($type == "G") $this->Cell8(50, 5, $this->labelGutschriftsbetrag, 0, 0, "R");
						if($type == "A" OR $type == "B") $this->Cell8(50, 5, $this->labelGesamtBrutto, 0, 0, "R");
						
						$this->SetFont($this->fontSumBetrag[0], $this->fontSumBetrag[1], $this->fontSumBetrag[2]);

						$this->Cell8($this->sumBetragWidth, 5, $this->cur($this->formatCurrency($this->language, $sum_ges, true)), 0, 0, "R");

					}
				break;
			}

			$this->Line($this->marginLeft , $this->getY()+5, 210-$this->marginRight , $this->getY()+5);
			$this->ln(10);

			/**
			 * VERSANDKOSTEN
			 */
			if($this->VarsGRLBM->A("versandkosten") != 0 AND $this->positionVersandkosten == "below") {

				$this->addPriceToSum($this->VarsGRLBM->A("versandkosten"), $this->VarsGRLBM->A("versandkostenMwSt"));

				$this->SetFont($this->fontSumHeaders[0], $this->fontSumHeaders[1], $this->fontSumHeaders[2]);

				$Versandkosten = Util::kRound($this->VarsGRLBM->A("versandkosten") * ((100 + $this->VarsGRLBM->A("versandkostenMwSt")) / 100));

				$this->SetXY($this->sumUmsatzsteuerPosition, $this->GetY() - 4);
				$this->Cell($this->sumUmsatzsteuerWidth, 5, $this->labelVersandkosten, 0, 0, "R");

				$this->SetFont($this->fontSumNetto[0], $this->fontSumNetto[1], $this->fontSumNetto[2]);
				$this->SetXY($this->sumBetragPosition, $this->GetY());
				$this->Cell8($this->sumBetragWidth, 5, $this->cur(Util::CLFormatCurrency($Versandkosten, true)), 0, 0, "R");
				$this->ln();

				$this->SetFont($this->fontSumHeaders[0], $this->fontSumHeaders[1], $this->fontSumHeaders[2]);
				$this->SetXY($this->sumUmsatzsteuerPosition, $this->GetY());
				$this->Cell($this->sumUmsatzsteuerWidth, 5, $this->labelVersandkostenGesamtbetrag, 0, 0, "R");

				$this->SetFont($this->fontSumBetrag[0], $this->fontSumBetrag[1], $this->fontSumBetrag[2]);
				$this->SetXY($this->sumBetragPosition, $this->GetY());
				$this->Cell8($this->sumBetragWidth, 5, $this->cur(Util::CLFormatCurrency($this->gesamt_brutto, true)), 0, 0, "R");
				$this->ln();

				$this->Line($this->sumUmsatzsteuerPosition , $this->GetY(), 210-$this->marginRight , $this->GetY());
				$this->ln(10);
			}

			$this->SetFont($this->fontPositionen[0], $this->fontPositionen[1], $this->fontPositionen[2]);
		}
		if($type == "Kalk"){

			$G = $this->VarsGRLBM;

			$this->widthEK1 += 10;
			$this->widthEK2 += 10;
			$this->widthVK += 10;

			$userLabels = mUserdata::getRelabels("Artikel");
			$userHiddenFields = mUserdata::getHides("Artikel");
			$rightShift = $this->widthMenge + $this->widthBezeichnung - 30;

			$this->SetFont($this->fontPositionen[0], "B", $this->fontPositionen[2]);
			$this->Cell($rightShift, 5, "", 0, 0);
			$this->Cell8($this->widthEK1,5,(!isset($userHiddenFields["EK1"]) ? $this->labelGes." ".(isset($userLabels["EK1"]) ? $userLabels["EK1"] : $this->labelEK1) : ""),0,0,"R");
			$this->Cell8($this->widthEK2,5,(!isset($userHiddenFields["EK2"]) ? $this->labelGes." ".(isset($userLabels["EK2"]) ? $userLabels["EK2"] : $this->labelEK2) : ""),0,0,"R");
			$this->Cell8($this->widthVK,5,$this->labelGes." ".$this->labelVK,0,0,"R");
			$this->ln();

			$gesamt = array_sum($this->gesamt_netto);
			$this->SetFont($this->fontPositionen[0], $this->fontPositionen[1], $this->fontPositionen[2]);
			$this->Cell($rightShift, 5, "", 0, 0);
			$this->Cell8($this->widthEK1,5,(!isset($userHiddenFields["EK1"]) ? $this->cur($this->formatCurrency($this->language, $this->gesamtEK1, true)) : ""),0,0,"R");
			$this->Cell8($this->widthEK2,5,(!isset($userHiddenFields["EK2"]) ? $this->cur($this->formatCurrency($this->language, $this->gesamtEK2, true)) : ""),0,0,"R");
			$this->Cell8($this->widthVK,5,$this->cur($this->formatCurrency($this->language, $gesamt, true)),0,0,"R");
			$this->ln();
			
			$this->Line($rightShift + $this->marginLeft , $this->getY(), 210-$this->marginRight , $this->getY());

			$this->SetFont($this->fontPositionen[0], 'B', $this->fontPositionen[2]);
			$this->Cell($rightShift + $this->widthEK1, 5, "", 0, 0);
			$this->Cell8($this->widthEK2,5,$this->labelRabatt,0,0,"R");
			$this->Cell8($this->widthVK,5,$this->labelGes." ".$this->labelVK,0,0,"R");
			$this->ln();
			
			$this->rabatt = str_replace(",",".",$this->rabatt);
			$this->rabattInW = str_replace(",",".",$this->rabattInW);
			$this->leasingrate = str_replace(",",".",$this->leasingrate);
			$rabatt = $gesamt * $this->rabatt / 100;
			if($this->rabattInW != 0.00) $rabatt = $this->rabattInW;
			$this->SetFont($this->fontPositionen[0], $this->fontPositionen[1], $this->fontPositionen[2]);
			$this->Cell($rightShift + $this->widthEK1, 5, "", 0, 0);

			if($this->rabattInW != 0.00) {
				$this->Cell8($this->widthEK2,5,$this->cur($this->formatCurrency($this->language, $rabatt * 1, true)),0,0,"R");
				$this->Cell8($this->widthVK,5,$this->cur($this->formatCurrency($this->language, $gesamt - $rabatt, true)),0,0,"R");
			} else {
				$this->Cell8($this->widthEK2,5,Util::formatNumber($this->language,$this->rabatt*1)."%: ".$this->cur($this->formatCurrency($this->language, $rabatt, true)),0,0,"R");
				$this->Cell8($this->widthVK,5,$this->cur($this->formatCurrency($this->language, $gesamt - $rabatt, true)),0,0,"R");
			}

			$this->ln();
			$this->Line($rightShift + $this->marginLeft , $this->getY(), 210-$this->marginRight , $this->getY());
			$gesamt -= $rabatt;

			$this->SetFont($this->fontPositionen[0], 'B', $this->fontPositionen[2]);
			$this->Cell($rightShift, 5, "", 0, 0);
			$this->Cell8($this->widthEK1,5,(!isset($userHiddenFields["EK1"]) ? $this->labelVK." - ".(isset($userLabels["EK1"]) ? $userLabels["EK1"] : $this->labelEK1) : ""),0,0,"R");
			$this->Cell8($this->widthEK2,5,(!isset($userHiddenFields["EK2"]) ? $this->labelVK." - ".(isset($userLabels["EK2"]) ? $userLabels["EK2"] : $this->labelEK2) : ""),0,0,"R");
			$this->Cell8($this->widthVK,5,$this->labelLeasingrate,0,0,"R");
			$this->ln();

			$this->SetFont($this->fontPositionen[0], $this->fontPositionen[1], $this->fontPositionen[2]);
			$this->Cell($rightShift, 5, "", 0, 0);
			$this->Cell8($this->widthEK1,5,(!isset($userHiddenFields["EK1"]) ? $this->cur($this->formatCurrency($this->language, $gesamt - $this->gesamtEK1, true)) : ""),0,0,"R");
			$this->Cell8($this->widthEK2,5,(!isset($userHiddenFields["EK2"]) ? $this->cur($this->formatCurrency($this->language, $gesamt - $this->gesamtEK2, true)) : ""),0,0,"R");
			$this->Cell8($this->widthVK,5,Util::formatNumber($this->language, $this->leasingrate * 1, 2, true,false)."%: ".$this->cur($this->formatCurrency($this->language, $gesamt * $this->leasingrate / 100, true)),0,0,"R");

			$this->ln();
			$this->Line($rightShift + $this->marginLeft , $this->getY(), 210-$this->marginRight , $this->getY());

			if(isset($G->getA()->servicepolice1)){
				$this->SetFont($this->fontPositionen[0], 'B', $this->fontPositionen[2]);
			$this->Cell($rightShift, 5, "", 0, 0);
				$this->Cell8($this->widthEK1,5,"Servicepolice 1",0,0,"R");
				$this->Cell8($this->widthEK2,5,"Servicepolice 2",0,0,"R");
				$this->Cell8($this->widthVK,5,"Servicepolice 3",0,0,"R");
				$this->ln();

				$s1 = Util::CLNumberParser($G->getA()->servicepolice1, "store");
				$s2 = Util::CLNumberParser($G->getA()->servicepolice2, "store");
				$s3 = Util::CLNumberParser($G->getA()->servicepolice3, "store");

				$this->SetFont($this->fontPositionen[0], $this->fontPositionen[1], $this->fontPositionen[2]);
			$this->Cell($rightShift, 5, "", 0, 0);
				$this->Cell8($this->widthEK1,5,Util::formatNumber($this->language, $s1, 5, true,false)."%: ".$this->cur($this->formatCurrency($this->language, $this->gesamtEK2 * $s1 / 100, true)),0,0,"R");
				$this->Cell8($this->widthEK2,5,Util::formatNumber($this->language, $s2, 5, true,false)."%: ".$this->cur($this->formatCurrency($this->language, $this->gesamtEK2 * $s2 / 100, true)),0,0,"R");
				$this->Cell8($this->widthVK,5,Util::formatNumber($this->language, $s3, 5, true,false)."%: ".$this->cur($this->formatCurrency($this->language, $this->gesamtEK2 * $s3 / 100, true)),0,0,"R");

				$this->ln();
				$this->Line($rightShift + $this->marginLeft , $this->getY(), 210-$this->marginRight , $this->getY());
			}

			$this->ln(10);
		}
		
		//SHOULD NOT BE NECESSARY! 2014.03.22
		/*if($this->VarsGRLBM->A("isPayed") == "0"){
			
			$A = $this->VarsGRLBM->getA();
			for($i = 4 ; $i <= 10; $i++){
				$custom = "GRLBMCustomField$i";
				if(isset($A->$custom))
					unset($A->$custom);
			}
			$this->VarsGRLBM->changeA("nettobetrag",$sum_netto);
			$this->VarsGRLBM->changeA("bruttobetrag",$sum_ges);
			$this->VarsGRLBM->changeA("steuern",$sum_mwst);
			$this->VarsGRLBM->saveMe();
		#print_r($this->VarsGRLBM);
		}*/
		
		$this->SetAutoPageBreak(true, $this->marginBottom);
	}
	// </editor-fold>

	// <editor-fold defaultstate="collapsed" desc="printPDFHeader">
	function printPDFHeader(){
		#if($this->PageNo() > 1)
		#	$this->setXY($this->positionPosten2teSeite[0], $this->positionPosten2teSeite[1]);
		if($this->isInPosten AND $this->PageNo() > 1){
			$this->SetFont($this->fontPositionen[0], $this->fontPositionen[1], $this->fontPositionen[2]);
			if($this->labelUebertrag != null AND !in_array($this->VarsGRLBM->getMyPrefix(), $this->sumHideOn))
				$this->Cell8($this->w - $this->marginRight - $this->marginLeft,5,$this->labelUebertrag.": ".$this->cur($this->formatCurrency($this->language, array_sum($this->gesamt_netto) * 1,$this->showPositionenWaehrung)),0,0,"R");
			$this->Ln();
		}
		
		$userLabels = mUserdata::getRelabels("Artikel");
		$userHiddenFields = mUserdata::getHides("Artikel");
		
		$priceCols = $this->orderColsPrice;
		
		$type = $this->VarsGRLBM->getMyPrefix();
		
		$this->SetX($this->marginLeft);
		$this->SetFont($this->fontPositionenHeader[0], $this->fontPositionenHeader[1], $this->fontPositionenHeader[2]);
		$this->SetTextColorArray($this->colorPositionenHeader);
		#$fpdf->SetFont('Helvetica','BI',9);
		
		Aspect::joinPoint("front", $this, __METHOD__);
		

		if($type != "Kalk"){
			foreach($this->orderCols AS $col){
				if($col == "Position" AND $this->widthPosition)
					$this->Cell8($this->widthPosition, 5, $this->labelPosition, 0, 0, $this->alignPosition);

				if($col == "Menge" AND $this->widthMenge)
					$this->Cell8($this->widthMenge, 5, $this->labelMenge, 0, 0, "R");

				if($col == "Einheit" AND $this->widthEinheit)
					$this->Cell8($this->widthEinheit, 5, $this->labelEinheit, 0, 0, $this->alignEinheit);

				if($col == "Menge2" AND $this->widthMenge2)
					$this->Cell8($this->widthMenge2, 5, $this->labelMenge2, 0, 0, $this->alignMenge2);

				if($col == "Artikelnummer" AND $this->widthArtikelnummer)
					$this->Cell8($this->widthArtikelnummer, 5, $this->labelArtikelnummer, 0, 0, "L");

				if($col == "Bezeichnung" AND $this->widthBezeichnung)
					$this->Cell8($this->widthBezeichnung, 5, $this->labelBezeichnung, 0, 0, "L");
			}
			
			Aspect::joinPoint("tail", $this, __METHOD__);
				
			if(!in_array($type, $this->sumHideOn)){
				foreach($priceCols AS $col){
					$col = "width$col";
					$label = str_replace("width", "label", $col);
					
					if($this->$col)
						$this->Cell8($this->$col,5,$this->$label, 0, 0, "R");
				}
			}
		} else {
			if($this->widthMenge)
				$this->Cell8($this->widthMenge, 5, $this->labelMenge, 0, 0, "R");
			
			#$fpdf->Cell(28,5,"Einheit",0,0,"L");
			if($this->widthBezeichnung)
				$this->Cell8($this->widthBezeichnung, 5, $this->labelBezeichnung, 0, 0, "L");
				
			if($this->widthEK1)
				$this->Cell8($this->widthEK1, 5, (!isset($userHiddenFields["EK1"]) ? (isset($userLabels["EK1"]) ? $userLabels["EK1"] : $this->labelEK1) : ""), 0, 0, "R");

			if($this->widthEK2)
				$this->Cell8($this->widthEK2, 5, (!isset($userHiddenFields["EK2"]) ? (isset($userLabels["EK2"]) ? $userLabels["EK2"] : $this->labelEK2) : ""), 0, 0, "R");

			if($this->widthVK)
				$this->Cell8($this->widthVK, 5, $this->labelVK, 0, 0, "R");
		}
		$this->Ln();
		#$this->SetFont('Helvetica','',9);
		
		$lineY = $this->getY();
		$this->Line($this->marginLeft , $lineY, $this->w - $this->marginRight , $lineY);
		
		if($this->currentArticle != null){
			$this->SetX($this->marginLeft);
			$this->SetFont($this->fontPositionenArtikelname[0], $this->fontPositionenArtikelname[1], $this->fontPositionenArtikelname[2]);
			$this->Cell8(0, 5, mb_substr("Fortsetzung ".$this->currentArticle->A("name"), 0, 85).(mb_strlen("Fortsetzung ".$this->currentArticle->A("name")) > 85 ? "..." : ""));
			$this->ln(5);
		}
		if($this->isInPostenBeschreibung)
			$this->SetFont($this->fontPositionenBeschreibung[0], $this->fontPositionenBeschreibung[1], $this->fontPositionenBeschreibung[2]);
	}
	// </editor-fold>

	function printInfoDokument(){
		$S = Stammdaten::getActiveStammdaten();
		$y = $this->positionRechnungsInfo[1];
		$x = $this->positionRechnungsInfo[0];
	
		if($this->PageNo() > 1){
			$y = $this->positionRechnungsInfo2teSeite[1];
			$x = $this->positionRechnungsInfo2teSeite[0];
			
			if($this->positionRechnungsInfo2teSeite == null) return;
		}
		
		if($this->Dokument->A("DokumentNummer") != "" AND $this->PageNo() == 1){
			$this->SetXY($x,$y);
			$this->printInfoCell($this->labelLong["D"], $this->Dokument->A("DokumentPrefix").$this->Dokument->A("DokumentNummer"), array($this->fontRechnungsInfo[0], "B", $this->fontRechnungsInfo[2]));
			$y += $this->heightRechnungsInfo;
		}
		

		if($this->labelDatum != null){
			$this->SetXY($x,$y);
			$this->printInfoCell($this->labelDatum, $this->Dokument->A("DokumentDatum"));
			$y += $this->heightRechnungsInfo;
		}
		
		if($S->getA()->ustidnr != "" AND $this->PageNo() == 1 AND $this->labelUstID != null){
			$this->SetXY($x,$y);
			$this->printInfoCell(preg_match("/(^[A-Za-z]{2})/", $S->getA()->ustidnr) ? $this->labelUstID : $this->labelStNr, $S->A("ustidnr"));
			$y += $this->heightRechnungsInfo;
		}
		
		$y += $this->heightRechnungsInfo;
		if($this->labelIhrZeichen AND $this->Dokument->A("DokumentIhrZeichen") != "" AND $this->PageNo() == 1){
			$this->SetXY($x,$y);
			$this->printInfoCell($this->labelIhrZeichen, $this->Dokument->A("DokumentIhrZeichen"));
			$y += $this->heightRechnungsInfo;
		}
	}
	
	function printInfoCell($label, $content, $useFont = null){
		if($useFont == null)
			$this->SetFont($this->fontRechnungsInfo[0], $this->fontRechnungsInfo[1], $this->fontRechnungsInfo[2]);
		else
			$this->SetFont($useFont[0], $useFont[1], $useFont[2]);
		
		$this->Cell8(30 , $this->heightRechnungsInfo , $label, "", 0, "L");
		$this->Cell8($this->widthRechnungsInfo - 30 , $this->heightRechnungsInfo , $content, "", 0, "R");
	}
	
	function printMahnungTable(GRLBM $GRLBM){
		if(!$this->showMahnungTable)
			return;
		
		$widthMahnungBelegnummer = 40;
		$widthMahnungDatum = 30;
		$widthMahnungWaehrung = 20;
		$widthMahnungBetrag = 25;
		$widthMahnungFaelligkeit = 30;
		$widthMahnungZinsen = 25;
		
		$labelMahnungBelegnummer = "Belegnummer";
		$labelMahnungDatum = "Datum";
		$labelMahnungWaehrung = "Währung";
		$labelMahnungBetrag = "Betrag";
		$labelMahnungFaelligkeit = "Fälligkeit";
		$labelMahnungZinsen = "Zinsen";
		
		$Rechnung = new GRLBM($GRLBM->A("AuftragID"));
		$Rechnung->resetParsers();
		
		$AC = anyC::get("GRLBM", "AuftragID", $GRLBM->A("AuftragID"));
		$AC->addAssocV3("isM", "=", "1");
		$AC->addAssocV3("nummer", "=", "1");
		$Mahnung1 = $AC->getNextEntry();
		
		$AC = anyC::get("GRLBM", "AuftragID", $GRLBM->A("AuftragID"));
		$AC->addAssocV3("isM", "=", "1");
		$AC->addAssocV3("nummer", "=", "2");
		$Mahnung2 = $AC->getNextEntry();
		
		$AC = anyC::get("GRLBM", "AuftragID", $GRLBM->A("AuftragID"));
		$AC->addAssocV3("isM", "=", "1");
		$AC->addAssocV3("nummer", "=", "3");
		$Mahnung3 = $AC->getNextEntry();
		
		
		$this->SetFont($this->fontPositionenHeader[0], $this->fontPositionenHeader[1], $this->fontPositionenHeader[2]);
		
		$this->Cell8($widthMahnungBelegnummer, 5, $labelMahnungBelegnummer);
		$this->Cell8($widthMahnungDatum, 5, $labelMahnungDatum);
		$this->Cell8($widthMahnungWaehrung, 5, $labelMahnungWaehrung);
		$this->Cell8($widthMahnungFaelligkeit, 5, $labelMahnungFaelligkeit);
		$this->Cell8($widthMahnungZinsen, 5, $labelMahnungZinsen, 0, 0, "R");
		$this->Cell8($widthMahnungBetrag, 5, $labelMahnungBetrag, 0, 0, "R");
		
		
		$this->Ln();
		$lineY = $this->getY();
		$this->Line($this->marginLeft , $lineY, $this->w - $this->marginRight , $lineY);
		
		$total = 0;
		$this->SetFont($this->fontPositionen[0], $this->fontPositionen[1], $this->fontPositionen[2]);
		
		$this->Cell8($widthMahnungBelegnummer, 5, $Rechnung->A("prefix").$Rechnung->A("nummer"));
		$this->Cell8($widthMahnungDatum, 5, Util::formatDate($this->language, $Rechnung->A("datum")));
		$this->Cell8($widthMahnungWaehrung, 5, "EUR");
		$this->Cell8($widthMahnungFaelligkeit, 5, Util::formatDate($this->language, $Mahnung1->A("datum")));
		$this->Cell8($widthMahnungZinsen, 5, $this->cur($this->formatCurrency($this->language, 0, true)), 0, 0, "R");
		$this->Cell8($widthMahnungBetrag, 5, $this->cur($this->formatCurrency($this->language, $Rechnung->A("bruttobetrag")*1, true)), 0, 1, "R");
		$total += $Rechnung->A("bruttobetrag");
		
		$this->Cell8($widthMahnungBelegnummer, 5, $Mahnung1->A("prefix").$Rechnung->A("nummer")."/".$Mahnung1->A("nummer"));
		$this->Cell8($widthMahnungDatum, 5, Util::formatDate($this->language, $Mahnung1->A("datum")));
		$this->Cell8($widthMahnungWaehrung, 5, "EUR");
		$this->Cell8($widthMahnungFaelligkeit, 5, "");
		$this->Cell8($widthMahnungZinsen, 5, $this->cur($this->formatCurrency($this->language, 0, true)), 0, 0, "R");
		$this->Cell8($widthMahnungBetrag, 5, $this->cur($this->formatCurrency($this->language, $Mahnung1->A("gebuehren")*1, true)), 0, 1, "R");
		$total += $Mahnung1->A("gebuehren");
		
		if($Mahnung2 != null){
			$this->Cell8($widthMahnungBelegnummer, 5, $Mahnung2->A("prefix").$Rechnung->A("nummer")."/".$Mahnung2->A("nummer"));
			$this->Cell8($widthMahnungDatum, 5, Util::formatDate($this->language, $Mahnung2->A("datum")));
			$this->Cell8($widthMahnungWaehrung, 5, "EUR");
			$this->Cell8($widthMahnungFaelligkeit, 5, "");
			$this->Cell8($widthMahnungZinsen, 5, $this->cur($this->formatCurrency($this->language, 0, true)), 0, 0, "R");
			$this->Cell8($widthMahnungBetrag, 5, $this->cur($this->formatCurrency($this->language, $Mahnung2->A("gebuehren")*1, true)), 0, 1, "R");
			$total += $Mahnung2->A("gebuehren");
		}
		
		if($Mahnung3 != null){
			$this->Cell8($widthMahnungBelegnummer, 5, $Mahnung3->A("prefix").$Rechnung->A("nummer")."/".$Mahnung3->A("nummer"));
			$this->Cell8($widthMahnungDatum, 5, Util::formatDate($this->language, $Mahnung3->A("datum")));
			$this->Cell8($widthMahnungWaehrung, 5, "EUR");
			$this->Cell8($widthMahnungFaelligkeit, 5, "");
			$this->Cell8($widthMahnungZinsen, 5, $this->cur($this->formatCurrency($this->language, 0, true)), 0, 0, "R");
			$this->Cell8($widthMahnungBetrag, 5, $this->cur($this->formatCurrency($this->language, $Mahnung3->A("gebuehren")*1, true)), 0, 1, "R");
			$total += $Mahnung3->A("gebuehren");
		}
		
		$this->ln(2);
		$this->Line($this->GetMargin("L") , $this->getY(), 210-$this->GetMargin("R") , $this->getY());
		
		$this->SetFont($this->fontSumHeaders[0], $this->fontSumHeaders[1], $this->fontSumHeaders[2]);
		
		$this->Cell8($widthMahnungBelegnummer, 5, "");
		$this->Cell8($widthMahnungDatum, 5, "");
		$this->Cell8($widthMahnungWaehrung, 5, "");
		$this->Cell8($widthMahnungFaelligkeit, 5, "");
		$this->Cell8($widthMahnungZinsen, 5, "Gesamt", 0, 0, "R");
		$this->Cell8($widthMahnungBetrag, 5, $this->cur($this->formatCurrency($this->language, $total, true)), 0, 1, "R");
	}
	
	function addPriceToSum($price, $mwst){
		$price *= 1;
		$parsedMwSt = $mwst."";

		#$mwstFaktor = 1 + $parsedMwSt / 100;
		if(!isset($this->gesamt_netto[$parsedMwSt]))
			$this->gesamt_netto[$parsedMwSt] = 0;

		$this->gesamt_netto[$parsedMwSt] += $price;

		$this->gesamt_brutto += $price * (1 + $mwst / 100);
	}

	function appendPDF(){
		if($this->appendPDFFile == null)
			return;
		
		if(!is_object($this->VarsGRLBM))
			return;
		
		$type = $this->VarsGRLBM->getMyPrefix();
		$showOn = $this->appendPDFBelegarten;
		if(trim($showOn) != "all" AND strpos($showOn, $type) === false)
			return;
			
		$this->SetAutoPageBreak(false);
		$this->Footer();
		
		$this->setSourceFile(FileStorage::getFilesDir().$this->appendPDFFile);
		
		$this->backgroundFileName = null;
		$this->backgroundFileNameSecondPage = null;
		$pages = explode(" ", $this->appendPDFPages);
		foreach($pages AS $page){
			$page = trim($page);
			$tplidx = $this->importPage($page);

			$this->positionFooter = null;
			$this->showHeader = false;
			$this->AddPage();
			$this->useTemplate($tplidx, 0, 0, 210);
		}
	}
	
	// <editor-fold defaultstate="collapsed" desc="Output">
	function Output($filename = "",$mode = ""){
		$this->appendPDF();
		
		$this->AliasNbPages("{nb}");
		$this->brief = null;
		parent::Output($filename,$mode);
	}
	// </editor-fold>
	
	// <editor-fold defaultstate="collapsed" desc="getLeftMargin">
	public function getLeftMargin(){
		return $this->lMargin;
	}
	// </editor-fold>
	
	public function A($attribute){
		if(isset($this->$attribute))
			return $this->$attribute;
		
		return null;
	}
	
	public function changeA($attribute, $newValue){
		$this->$attribute = $newValue;
	}
}
?>
