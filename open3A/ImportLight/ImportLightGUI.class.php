<?php
/**
 *  This file is part of phynx.

 *  phynx is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.

 *  phynx is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  2007, 2008, 2009, 2010, Rainer Furtmeier - Rainer@Furtmeier.de
 */
class ImportLightGUI extends ImportLight implements iGUIHTML2 {

	function  __construct($ID) {
		parent::__construct($ID);

	}

	function getHTML($id){
		$this->loadMeOrEmpty();
		$field="Dateiname";

		$file = $this->A($field);
		if ($file != null && !empty($file)){
			$F = new HTMLForm("Dateiupload", array($field),"importierte Datei");
			$F->setValue($field, $file);
			$F->setType($field, 'readonly');
		} else {
			$F = new HTMLForm("Dateiupload", array($field,"upload"),"neue Datei importieren");
			$F->setType($field, "hidden");
			$F->setValue($field, "");
			$F->setType("upload", "file");
			$F->addJSEvent("upload", "onChange", "\$j('#Dateiupload input[name=$field]').val(fileName);");
			$F->setSaveJSON("Import starten", "", "ImportLight", $this->getID(), "processImport", OnEvent::frame("Right", 'Adressen').OnEvent::frame("Left", 'mImportLight'));
		}

		echo $F;
	}

	public function processImport($data){
		$data=json_decode($data);
		$obj=reset($data);
		if ($obj->name == "Dateiname"){
			$fileName=$obj->value;
			if ($fileName == ''){
				Red::alertD("Es wurde keine Datei hochgeladen!");
			}
			$tempDir = Util::getTempDir();
			$tempFile = $tempDir.$fileName.".tmp";
			if (file_exists($tempFile)){
				$this->importFrom($tempFile);
				copy($tempFile,FileStorage::getFilesDir().$fileName);
				unlink($tempFile);
				$this->changeA($obj->name, $obj->value);
				$this->newMe(true,true);
			} else {
				Red::alertD("Datei wurde nicht korrekt hochgeladen!");
			}
		} else {
			Red::alertD("Es wurden ungültige Daten übermittelt!");
		}
	}

	public static function getSeparator($string){
		$count=count_chars(trim($string),0);
		$semicolon=$count[ord(';')];
		$comma=$count[ord(',')];
		if ($semicolon > $comma){
			return ';';
		}
		if ($comma < 1){
			Red::alertD("Dies schein keine Datei mit komma-separierten Daten zu sein!");
		}
		return ',';
	}

	public function removeEnclosing(&$value, $key){
		if (substr($value, 0,1) == '"' && substr($value, -1) == '"'){
			$value = substr($value, 1,-1);
		} elseif (substr($value, 0,1) == '\'' && substr($value, -1) == '\''){
			$value = substr($value, 1,-1);
		}
	}

	public function explode($delimiter,$line){
		$arr=explode($delimiter, trim($line));
		array_walk($arr, array($this,'removeEnclosing'));
		return $arr;

	}

	public function getType($data){
		$data=strtolower($data);
		if (strpos($data, 'kundennummer') !== false) return 'adresse';
		if (strpos($data, 'artikelnummer')!== false) return 'artikel';
		Red::alertD('Konnte nicht entscheiden, ob diese CSV-Datei Adressen oder Artikel enthält!');
	}

	public function resolve(&$name,$key,$type){
		$orig=$name;
		$name=strtolower($name);
		if ($type == 'adresse'){
			if ($name=='anrede') {
			} elseif ($name=='ansprechpartner') {
				$name=null;
			} elseif ($name=='bankbezeichnung') {
				$name=null;
			} elseif ($name=='bankkonto') {
				$name='+KappendixKontonummer';
			} elseif ($name=='bankleitzahl') {
				$name='+KappendixBLZ';
			} elseif ($name=='bdsg datum') {
				$name=null;
			} elseif ($name=='bdsg herkunft kundendaten') {
				$name=null;
			} elseif ($name=='bdsg status') {
				$name=null;
			} elseif ($name=='bemerkung') {
			} elseif ($name=='bic') {
				$name='+KappendixSWIFTBIC';
			} elseif ($name=='debitorenkonto') {
				$name=null;
			} elseif ($name=='eg id') {
				$name=null;
			} elseif ($name=='einzug') {
				$name='+KappendixEinzugsermaechtigung';
			} elseif ($name=='email') {
			} elseif ($name=='firma') {
			} elseif ($name=='freifeld 1') {
				$name=null;
			} elseif ($name=='freifeld 2') {
				$name=null;
			} elseif ($name=='freifeld 3') {
				$name=null;
			} elseif ($name=='iban') {
				$name='+KappendixIBAN';
			} elseif ($name=='kundennummer'){
				$name = '+kundennummer';
			} elseif ($name=='inaktiv') {
				$name=null;
			} elseif ($name=='kredit') {
				$name=null;
			} elseif ($name=='kreditbetrag') {
				$name=null;
			} elseif ($name=='land') {
			} elseif ($name=='länderschlüssel') {
				$name=null;
			} elseif ($name=='länderschlüssel lieferanschrift') {
				$name=null;
			} elseif ($name=='lieferart') {
				$name=null;
			} elseif ($name=='lieferstopp') {
				$name=null;
			} elseif ($name=='lief-nr beim kunden') {
				$name='lieferantennr';
			} elseif ($name=='liefer.ansprechpartner') {
				$name=null;
			} elseif ($name=='liefer.land') {
				$name=null;
			} elseif ($name=='liefer. ort') {
				$name=null;
			} elseif ($name=='liefer. plz') {
				$name=null;
			} elseif ($name=='liefer. strasse') {
				$name=null;
			} elseif ($name=='liefer. telefon') {
				$name=null;
			} elseif ($name=='liefer. zusatz') {
				$name=null;
			} elseif ($name=='matchcode') {
				$name=null;
			} elseif ($name=='name') {
				$name='nachname';
			} elseif ($name=='ort') {
			} elseif ($name=='postleitzahl') {
				$name='plz';
			} elseif ($name=='preisgruppe') {
				$name='+KappendixPreisgruppe';
			} elseif ($name=='rabatt') {
				$name=null;
			} elseif ($name=='sammelkonto') {
				$name='+KappendixSameKontoinhaber';
			} elseif ($name=='skontotage') {
				$name=null;
			} elseif ($name=='skontotage-rechng') {
				$name=null;
			} elseif ($name=='skontobetrag-rechng') {
				$name=null;
			} elseif ($name=='skontobetrag') {
				$name=null;
			} elseif ($name=='steuerbare umsätze') {
				$name=null;
			} elseif ($name=='straße') {
				$name='strasse';
			} elseif ($name=='telefax') {
				$name='fax';
			} elseif ($name=='telefon') {
				$name='tel';
			} elseif ($name=='telefon 2') {
				$name='mobil';
			} elseif ($name=='vorname') {
			} elseif ($name=='währung') {
				$name=null;
			} elseif ($name=='zahlart') {
				$name=null;
			} elseif ($name=='zahlungsbedingung') {
				$name=null;
			} elseif ($name=='zahlungsbedingung rechnung') {
				$name=null;
			} elseif ($name=='zahlungsziel') {
				$name=null;
			} elseif ($name=='zahlungsziel-rechng') {
				$name=null;
			} elseif ($name=='zusatz') {
				$name='zusatz1';
			} elseif ($name=='§13b ustg') {
				$name=null;
			} else {
				/* $name=null; /*/
				Red::alertD("Ich habe keine Ahnung, wie das Feld $orig zuzuordnen ist!"); // */
			}
		}
	}

	public function importFrom($file){
		$target_encoding='UTF-8';
		$data = file_get_contents($file);
		if (mb_detect_encoding($data,'UTF-8',true) === false){
			$data = utf8_encode($data);
		}
		$data = explode("\n", $data);
		$separator=$this->getSeparator(reset($data));
		$type=$this->getType(reset($data));
		$keys=null;
		foreach ($data as $line){
			$line=trim($line);
			if (empty($line)) continue;
			if ($keys == null){
				$keys=$this->explode($separator, $line);
				array_walk($keys, array($this,'resolve'),$type);
			} else {
				$values=$this->explode($separator, $line);
				if ($type == 'adresse'){
					$object=new Adresse(-1);
				} else {
					return; // TODO
				}
				foreach ($values as $key => $value){
					$field=$keys[$key];
					if (empty($value)){
						if ($field=='anrede'){
							$value=3; // keine Anrede
							$object->changeA($field, $value);
						}
					}	elseif ($field!=null && substr($field,0,1)!='+'){

						if ($field == 'anrede'){
							if (strtolower($value)=='herr'){
								$value=2;
							}
							if (strtolower($value)=='frau'){
								$value=1;
							}
							if (strtolower($value)=='familie'){
								$value=4;
							}
							if (strtolower($value)=='firma'){
								$value=3;
							}
						}

						$object->changeA($field, $value);
					}
				}
				$id = $object->newMe();
				if ($type == 'adresse'){
					$object=new Kappendix(-1);
					$object->changeA('AdresseID', $id);

					foreach ($values as $key => $value){
						$field=$keys[$key];						
						if (!empty($value) && $field!=null && !empty($field) && substr($field,0,1)=='+'){
							$field=substr($field,1);
							$object->changeA($field, $value);
						}
					}
					$object->newMe();
				}
			}
		}
	}
}
?>