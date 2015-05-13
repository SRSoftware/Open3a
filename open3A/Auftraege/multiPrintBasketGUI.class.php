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
class multiPrintBasketGUI extends UnpersistentClass implements iGUIHTML2 {

	public $hasRechnungsversand = false;
	public $hasStandardversand = false;
	
	public function __construct() {

		$this->customize();
	}

	public function addToList() {
		$_SESSION["BPS"]->setActualClass(get_class($this));
		$bps = $_SESSION["BPS"]->getAllProperties();
		
		$pSpecData = mUserdata::getPluginSpecificData("Auftraege");
		
		if($bps == -1 OR !isset($bps["add"])) return;
		else $what = $bps["add"];
			
		$bps = $_SESSION["BPS"]->getAllProperties();
		
		$gs = anyC::get("GRLBM");
		if($what != "Kalk") $gs->addAssocV3("is$what","=","1");
		else $gs->addAssocV3("isWhat","=","$what");
		$gs->addAssocV3("isPrinted","=","0");
		if($what != "M") $gs->addJoinV3("Auftrag","AuftragID","=","AuftragID");
		if($what != "M") $gs->addAssocV3("auftragDatum","IS","NOT NULL");

		if($what == "R") $gs->addAssocV3("isPayed","!=","2"); //keine stornierten Rechnungen

		if(isset($pSpecData["pluginSpecificCanOnlyEditOwn"])) $gs->addAssocV3("UserID","= ",$_SESSION["S"]->getCurrentUser()->getID());
		
		Aspect::joinPoint("alterQuery", $this, __METHOD__, array($gs));
		
		$newIds = array();
		while(($t = $gs->getNextEntry())){
			if(isset($bps["ids"]) AND strstr($bps["ids"],",,".$t->getID().",,")) continue;
			$newIds[] = $t->getID();
		}
		$_SESSION["BPS"]->setACProperty("add","");
		$_SESSION["BPS"]->setACProperty("ids",(isset($bps["ids"]) ? $bps["ids"] : ",,").implode(",,",$newIds).(count($newIds) > 0 ? ",," : ""));
	}

	public function emptyList() {
		//has to do nothing, the list will be emptied via BPS
	}
	
	public static function addBeleg($GRLBMID){
		$ids = BPS::getProperty("multiPrintBasketGUI", "ids", ",,");
		BPS::setProperty("multiPrintBasketGUI", "ids", $ids.$GRLBMID.",,");
		
		#$_SESSION["BPS"]->setACProperty("ids",(isset($bps["ids"]) ? $bps["ids"] : ",,").implode(",,",$newIds).(count($newIds) > 0 ? ",," : ""));
		
	}
	
	/**
	 * @return mGRLBMGUI
	 */
	public function getGRLBMS(){
		$gs = new anyC();
		$gs->setCollectionOf("GRLBM");
		
		$_SESSION["BPS"]->setActualClass("multiPrintBasketGUI");
		$bps = $_SESSION["BPS"]->getAllProperties();

		if(!isset($bps["ids"])) {
			$bps = array();
			$bps["ids"] = ",,";
		}
		$ids = explode(",,",$bps["ids"]);
		$gs->addAssocV3("GRLBMID","=","-1","OR");
		
		for($i=1;$i<count($ids)-1;$i++)
			$gs->addAssocV3("GRLBMID","=",$ids[$i],"OR");

		$gs->addOrderV3("isA", "DESC");
		$gs->addOrderV3("isL", "DESC");
		$gs->addOrderV3("isR", "DESC");
		$gs->addOrderV3("isG", "DESC");
		$gs->addOrderV3("isM", "DESC");
		$gs->addOrderV3("nummer", "ASC");

		return $gs;
	}
	
	function getHTML($id){
		$bps = $this->getMyBPSData();
		if($bps != -1 AND isset($bps["add"]) AND $bps["add"] != "") $this->addToList();
			

		$pSpecData = mUserdata::getPluginSpecificData("Auftraege");

		$BE = new Button("Liste\nleeren","clear");
		$BE->className("backgroundColor2");
		#$BE->rmePCR("multiPrintBasket", "", "emptyList", "", "",'multiPrintBasketGUI;-');
		$BE->onclick("contentManager.loadFrame('editDetailsContentmultiPrintBasket','multiPrintBasket','',0,'multiPrintBasketGUI;-');");
		#rme('multiPrintBasket', '', 'emptyList', '', "loadFrameV2('contentLeft','multiPrintBasket');",'multiPrintBasketGUI;-');

		#$BK = new Button("Neue Kalkulationen hinzufügen","kalkulation");
		#$BK->type("icon");
		#$BK->style("float:right;margin-right:3px;");
		#$BK->onclick("contentManager.loadFrame('editDetailsContentmultiPrintBasket','multiPrintBasket','',0,'_multiPrintBasketGUI;add:Kalk');");

		$BM = new Button("Neue Mahnungen hinzufügen", "mahnung", "icon");
		$BM->style("float:right;margin-right:3px;");
		$BM->onclick("contentManager.loadFrame('editDetailsContentmultiPrintBasket','multiPrintBasket','',0,'_multiPrintBasketGUI;add:M');");

		$BG = new Button("Neue Gutschriften hinzufügen", "gutschrift", "icon");
		$BG->style("float:right;margin-right:3px;");
		$BG->onclick("contentManager.loadFrame('editDetailsContentmultiPrintBasket','multiPrintBasket','',0,'_multiPrintBasketGUI;add:G');");

		$BR = new Button("Neue Rechnungen hinzufügen","rechnung", "icon");
		$BR->style("float:right;margin-right:3px;");
		$BR->onclick("contentManager.loadFrame('editDetailsContentmultiPrintBasket','multiPrintBasket','',0,'_multiPrintBasketGUI;add:R');");

		Aspect::joinPoint("buttonRechnungen", $this, __METHOD__, array($BR));
		
		$BL = new Button("Neue Lieferscheine hinzufügen", "lieferschein", "icon");
		$BL->style("float:right;margin-right:3px;");
		$BL->onclick("contentManager.loadFrame('editDetailsContentmultiPrintBasket','multiPrintBasket','',0,'_multiPrintBasketGUI;add:L');");

		$BA = new Button("Neue Angebote hinzufügen","angebot", "icon");
		$BA->style("float:right;margin-right:3px;");
		$BA->onclick("contentManager.loadFrame('editDetailsContentmultiPrintBasket','multiPrintBasket','',0,'_multiPrintBasketGUI;add:A');");

		#<input type=\"button\" class=\"bigButton backgroundColor2\" value=\"\" style=\"background-image:url(./images/navi/clear.png);\" onclick=\"\" />
		$html = "
		<!--<div class=\"backgroundColor1 Tab\"><p>multiDruck Liste:</p></div>-->
		<table>
			<colgroup>
				<col class=\"backgroundColor3\" />
			</colgroup>
			<tr>
				<td>
					".($_SESSION["applications"]->getActiveApplication() == "open3A" ? "
					
					".(!isset($pSpecData["pluginSpecificCanOnlySeeKalk"]) ? "
					$BM$BG$BR$BL$BA" : "")." " : "")."
					$BE
					</td>
			</tr>
		</table>";

		$htmlB = "<table style=\"margin-top:10px;\">
			<colgroup>
				<col class=\"backgroundColor3\" />
				<col class=\"backgroundColor2\" style=\"width:25px;\" />
				<col class=\"backgroundColor3\" style=\"width:25px;\" />
				<col class=\"backgroundColor0\" style=\"width:20px;\" />
			</colgroup>";

		$gs = $this->getGRLBMS();
	
		$BPL = "";
		if($_SESSION["S"]->checkForPlugin("PL")){
			$BPL = new Button("mit PixelLetter\nverschicken","./open3A/PixelLetter/pl.png", "bigButton");
			$BPL->contextMenu("PL", "sendViaMultiPrint", "Versenden via", "right", "up");
			$BPL->loading();
			$BPL->className("backgroundColor2");
			$BPL->style("float:right;");
			#$BPL->onclick("if(confirm('Rechnungen jetzt mit PixelLetter signieren und verschicken?')) ");
			#$BPL->popup("mpbLog", "PL-Versand Logbuch", "multiPrintBasket", "", "sendViaPL");
			#$BPL->rme("multiPrintBasket", "", "sendViaPL","","if(checkResponse(transport)) { Popup.create(12346, \'mpbLog\', \'PL-Versand Logbuch\'); Popup.update(transport, 12346, \'mpbLog\'); }");
		}


		while(($t = $gs->getNextEntry())){
			$M = "";
			if($t->getMyPrefix() == "M"){
				$GRLBM = new GRLBM($t->A("AuftragID"));
				$M = $GRLBM->A("nummer")."/";
			}

			if($_SESSION["S"]->checkForPlugin("PL") AND $t->getMyPrefix() != "R")
				$BPL->disabled(true);


			$subFromMultiPrint = "contentManager.loadFrame('editDetailsContentmultiPrintBasket','multiPrintBasket',0,0,'_multiPrintBasketGUI;-ids:".$t->getID()."'); if($('subID".$t->getID()."')) $('subID".$t->getID()."').style.display = 'none'; if($('addID".$t->getID()."')) $('addID".$t->getID()."').style.display = 'block';";

			$OK = "";
			if($t->A("isPrinted") == "0"){
				$OK = new Button("Als erledigt markieren","./images/i2/printeds.png");
				$OK->type("icon");
				$OK->rmePCR("multiPrintBasket", "", "setPrinted", $t->getID(), $subFromMultiPrint);#"subFromMultiPrint(\'".$t->getID()."\');"
			}


			$BUP = new Button("Als unerledigt markieren","./images/i2/okCatch.png");
			$BUP->rmePCR("GRLBM", $t->getID(), "markMeAsUnprinted", "", "contentManager.loadFrame('editDetailsContentmultiPrintBasket','multiPrintBasket');");
			$BUP->type("icon");
			#<img id=\"printedSymbol".$t->getID()."\" class=\"mouseoverFade\" onclick=\"rme('GRLBM','".$t->getID()."','markMeAsUnprinted','','$(\'printedSymbol".$t->getID()."\').style.display=\'none\';');\" src=\"./images/i2/printeds.png\" title=\"bereits gedruckt (klicken Sie, um den gedruckt-Status wieder aufzuheben)\" />

			$htmlB .= "
			<tr>
				<td>".str_replace("Kalk","K",$t->getMyPrefix()).$M.$t->getA()->nummer."</td>
				<td>$OK</td>
				<td>".($t->getA()->isPrinted == 1 ? $BUP : "")."</td>
				<td><img src=\"./images/i2/delete.gif\" title=\"Von Liste entfernen\" onclick=\"$subFromMultiPrint\" class=\"mouseoverFade\" /></td>
			</tr>";
		}

		$BM = new Button("per E-Mail\nverschicken","mail");
		$BM->loading();
		$BM->className("backgroundColor2");
		$BM->onclick("if(confirm('Belege jetzt per E-Mail verschicken?')) ");
		$BM->popup("mpbLog", "E-Mail-Versand Logbuch", "multiPrintBasket", "", "sendVia", "Email");
		#$BM->rme("multiPrintBasket", "", "sendVia","Email","if(checkResponse(transport)) { Popup.create(12345, \'mpbLog\', \'E-Mail-Versand Logbuch\'); Popup.update(transport, 12345, \'mpbLog\'); }");

		$BP = new Button("Liste als erledigt\nmarkieren","printed");
		$BP->className("backgroundColor2");
		$BP->rmePCR("multiPrintBasket", "", "markListAsPrinted","","contentManager.loadFrame('editDetailsContentmultiPrintBasket','multiPrintBasket');");

		$BE = "";
		if(Session::isPluginLoaded("mEtikette")){
			$BE = Etikette::getButton("multiPrintBasketGUI", "-1");
			$BE->style("float:right;");
			$BE->className("backgroundColor2");
		}
		
		$Tab = new HTMLTable(1);

		if($gs->numLoaded() > 0) {
				
			$Tab->addRow("
				<input type=\"button\" class=\"bigButton backgroundColor2\" value=\"Kopie\" style=\"float:right;background-image:url(./images/navi/pdf.png);\" onclick=\"windowWithRme('multiPrintBasket', '', 'getFPDF', 'true');\" />
				<img src=\"./images/i2/settings.png\" style=\"float:right;\" class=\"mouseoverFade\" onclick=\"phynxContextMenu.start(this, 'GRLBM','1','Kopie:');\">
				<input type=\"button\" style=\"background-image:url(./images/navi/pdf.png);\" class=\"bigButton backgroundColor2\" value=\"Original\" onclick=\"windowWithRme('multiPrintBasket', '', 'getFPDF', 'false');\" />");
			$Tab->addRowClass("backgroundColor3");

			if(!$this->hasStandardversand){
				$Tab->addRow($BPL.$BM);
				$Tab->addRowClass("backgroundColor3");
			}
			
			if($this->hasStandardversand){
				$BD = new Button("Standardversand\nverwenden","daytime");
				$BD->className("backgroundColor2");
				$BD->rme("multiPrintBasket", "", "sendVia","Default","if(checkResponse(transport)) { Popup.create(12345, \'mpbLog\', \'Standard-Versand Logbuch\'); Popup.update(transport, 12345, \'mpbLog\'); }");
				$BD->style("float:left;");
				
				$BDS = $BD->settings("CustomizerStandardversand", "1");
				$BDS->style("margin-left:2px;");
				$BDS->onclick("new Effect.BlindToggle('CustomizerStandardversandSettings');");
				
				$Tab->addRow($BD.  CustomizerStandardversandGUI::getSettings());
				$Tab->addRowClass("backgroundColor3");
			}

			$Tab->addRow($BE.$BP);
			$Tab->addRowClass("backgroundColor3");
		}


		else {
			$htmlB .= "
		<tr>
			<td colspan=\"4\">keine Einträge</td>
		</tr>";
		}

		$htmlB .= "</table>";

		$html .= "
		</table>";

		$html = "<div style=\"overflow:auto;max-height:500px;\">$html$Tab$htmlB</div>";

		return $html;
	}

	public function setPrinted($GRLBMID){
		$G = new GRLBM($GRLBMID);
		$G->changeA("isPrinted", "1");
		$G->saveMe();
	}

	public function markListAsPrinted(){
		$gs = $this->getGRLBMS();
		while(($t = $gs->getNextEntry()))
			$t->markMeAsPrinted();
			
		echo $gs->numLoaded();
	}
	
	public function sendVia($mode){
		$U = new Util();
		$gs = $this->getGRLBMS();

		$defaults = array();
		$defaults["A"] = mUserdata::getUDValueS("CustomVersandA", "none");
		$defaults["L"] = mUserdata::getUDValueS("CustomVersandL", "none");
		$defaults["R"] = mUserdata::getUDValueS("CustomVersandR", "none");
		$defaults["G"] = mUserdata::getUDValueS("CustomVersandG", "none");
		$defaults["M1"] = mUserdata::getUDValueS("CustomVersandM1", "none");
		$defaults["M2"] = mUserdata::getUDValueS("CustomVersandM2", "none");
		$defaults["M3"] = mUserdata::getUDValueS("CustomVersandM3", "none");
		
		while($GRLBM = $gs->getNextEntry()){
			
			$AuftragID = $GRLBM->getA()->AuftragID;
			$M = "";
			if($GRLBM->getMyPrefix() == "M") {
				$G = new GRLBM($GRLBM->A("AuftragID"));
				$AuftragID = $G->A("AuftragID");
				$M = $G->A("nummer")."/";
			}
			
			$A = new AuftragGUI($AuftragID);
			switch($mode){
				case "Email":
					$U->logStatusMessages(array($GRLBM->getMyPrefix().$M.$GRLBM->A("nummer")), $A, "sendViaEmail", array($GRLBM->getID(), "", "", "", false));
				break;

				case "Default":
					$prefix = $GRLBM->getMyPrefix();
					if($prefix == "M")
						$prefix .= $GRLBM->A("nummer");
					
					if($defaults[$prefix] != "none")
						$U->logStatusMessages(array($GRLBM->getMyPrefix().$M.$GRLBM->A("nummer")), $A, "sendGRLBMToCustomer", array($GRLBM->getID(), $defaults[$prefix], false));
					else
						$U->logStatusMessages(array($GRLBM->getMyPrefix().$M.$GRLBM->A("nummer")), $this, "noDefault", array());
					
				break;
			}
		}
		$tab = $U->getStatusMessagesLog(2);
		$tab->setColWidth(1, 80);
		echo "<div style=\"overflow:auto;max-height:500px;\">".$tab."</div>";
	}
	
	public function noDefault(){
		throw new Exception("Kein Standardversand");
	}
	
	public function sendViaPL($mode){
		echo "<p class=\"prettyTitle\">PixelLetter-Versand</p>";
		$U = new Util();
		$gs = $this->getGRLBMS();

		while($GRLBM = $gs->getNextEntry()){
			if($GRLBM->getMyPrefix() != "R") continue;
			
			$AuftragID = $GRLBM->getA()->AuftragID;
			$_SESSION["BPS"]->registerClass("Brief");
			$_SESSION["BPS"]->setProperty("Brief","GRLBMID",$GRLBM->getID());
			
			
			if($mode == "Brief")
				$U->logStatusMessages(array($GRLBM->getMyPrefix().$GRLBM->A("nummer")), new Auftrag($AuftragID), "sendViaMail", array($GRLBM->getID(), false, ""));
			
			if($mode == "Einschreiben" OR $mode == "Einschreiben,Rückschein")
				$U->logStatusMessages(array($GRLBM->getMyPrefix().$GRLBM->A("nummer")), new Auftrag($AuftragID), "sendViaMail", array($GRLBM->getID(), false, $mode));
			
			if($mode == "EMail")
				$U->logStatusMessages(array($GRLBM->getMyPrefix().$GRLBM->A("nummer")), new Auftrag($AuftragID), "signLetter", array($GRLBM->getID(), "", "", "", false));
		}
		
		echo $U->getStatusMessagesLog(2);
	}
	
	public function getFPDF($copy){
		
		$brief = new Brief();
		$brief->isCopy = $copy == "true" ? true : false;
		$brief->setStammdaten(Stammdaten::getActiveStammdaten());
		$pdf = $brief->PDFObjectFactory();
		$gs = $this->getGRLBMS();
		$i = 0;
		while(($t = $gs->getNextEntry())){
			if($i++ != 0)
				$pdf->AddPage('', true);
			
			#$_SESSION["BPS"]->registerClass("Brief");
			#$_SESSION["BPS"]->setProperty("Brief","GRLBMID",$t->getID());

			$AuftragID = $t->getA()->AuftragID;
			if($t->getMyPrefix() == "M") {
				$GRLBM = new GRLBM($t->A("AuftragID"));
				$AuftragID = $GRLBM->A("AuftragID");
			}
			
			$pdf->nbTag = "{nb$i}";
			$A = new AuftragGUI($AuftragID);
			$A->getGRLBMPDF($copy, $pdf, $t->getID());
			$pdf->addReplacement("{nb$i}", $pdf->PageNo());
		}
		
		$filename = $brief->getMultiDruckOutput(true);
		Util::PDFViewer($filename);
	}
	
	public function getEtiketten(){
		$array = array();
		$AC = $this->getGRLBMS();
		
		while($G = $AC->getNextEntry()){
			$a = $G->getEtiketten();
			$array[] = $a[0];
		}

		return $array;
	}
}
?>