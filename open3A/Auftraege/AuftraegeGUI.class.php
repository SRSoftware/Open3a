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
class AuftraegeGUI extends Auftraege implements iGUIHTMLMP2, iAutoCompleteHTML, icontextMenu, iSearchFilter, iCategoryFilter {
	public static $users = array("" => "unbekannt");
	public $searchFields = array("firma",/*"nachname",*/"kundennummer", "CONCAT(vorname, ' ', nachname)");
	public static $BelegArten;
	
	function  __construct() {
		parent::__construct();
		
		$this->customize();
		T::load(dirname(__FILE__), "Auftrag");
	}
	
	function getHTML($id, $page){
		$gui = new HTMLGUI2();
		$gui->VersionCheck("Auftraege");
		$this->addAssocV3("lieferantennummer", "=", "");
		
		$pSpecData = mUserdata::getPluginSpecificData("Auftraege");
		
		if(isset($pSpecData["pluginSpecificCanOnlyEditOwn"]))
			$this->addAssocV3("t1.UserID","=",$_SESSION["S"]->getCurrentUser()->getID(), "AND", "2");
		$i = 0;
		foreach($pSpecData as $key => $value) 
			if(strstr($key,"pluginSpecificCanSeeAuftraegeFrom"))
				$this->addAssocV3("t1.UserID","=",str_replace("pluginSpecificCanSeeAuftraegeFrom","",$key),$i++ == 0 ? "AND" : "OR", "2");
			
			
		$this->addJoinV3("Adresse","AdresseID","=","AdresseID");
		if(Session::isPluginLoaded("mProjekt"))
			$this->addJoinV3("Projekt", "ProjektID", "=", "ProjektID");
		
		if(Applications::activeApplication() == "lightCRM"){
			$this->addAssocV3 ("status", "=", "open", "AND", "3");
			$this->addAssocV3 ("status", "=", "confirmed", "OR", "3");
		}
		
		if(Applications::activeApplication() == "upFab")
			$this->addAssocV3 ("status", "=", "delivered", "AND", "3");
		
		$Users = Users::getUsers();
		while($U = $Users->getNextEntry())
			self::$users[$U->getID()] = $U->A("name");

		$gui->showFilteredCategoriesWarning($this->filterCategories(),$this->getClearClass());
		
		$gesamt = $this->loadMultiPageMode($id, $page, 0);

		$oldType = ($_SESSION["BPS"]->isPropertySet("mGRLBMGUI","type") ? $_SESSION["BPS"]->getProperty("mGRLBMGUI","type") : -1);
		$_SESSION["BPS"]->setProperty("mGRLBMGUI","type","-1");
		
		$basketBPS = BPS::getAllProperties("multiPrintBasketGUI");
		
		$this->addOrderV3("auftragDatum","DESC");
		if(isset($pSpecData["pluginSpecificCanOnlyEditOwn"])) 
			$this->addAssocV3("t1.UserID","=",$_SESSION["S"]->getCurrentUser()->getID(), "AND", "2");
		$i = 0;
		foreach($pSpecData as $key => $value) 
			if(strstr($key,"pluginSpecificCanSeeAuftraegeFrom"))
				$this->addAssocV3("t1.UserID","=",str_replace("pluginSpecificCanSeeAuftraegeFrom","",$key),$i++ == 0 ? "AND" : "OR", "2");
		
		if(Applications::activeApplication() == "lightCRM"){
			$this->addAssocV3 ("status", "=", "open", "AND", "3");
			$this->addAssocV3 ("status", "=", "confirmed", "OR", "3");
		}
		
		if(Applications::activeApplication() == "upFab")
			$this->addAssocV3 ("status", "=", "delivered", "AND", "3");
		
		
		$this->lCV3($id);

		/*if($id == -1){
			$gui->addRowAfter("1","multiPrint");
			$gui->setParser("multiPrint","AuftraegeGUI::multiPrint");
		*/
		$gui->isQuickSearchable(get_parent_class($this));
		
		$gui->setObject($this);
		$gui->tip();
		$gui->setMultiPageMode($gesamt, $page, 0, "contentRight", str_replace("GUI","",get_class($this)));
		
		#$gui->setName("Bitte Auftrag auswählen");
		$gui->setCollectionOf("Auftrag", "Auftrag");
		$gui->setShowAttributes(array("firma","bezahlt"));
		

		#$gui->setParser("auftragDatum","Datum::parseGerDate");
		$gui->setParser("bezahlt","AuftraegeGUI::getPaidImg", array("\$aid",(isset($pSpecData["pluginSpecificCanOnlySeeKalk"]) ? "true" : "false"),(isset($basketBPS["ids"]) ? $basketBPS["ids"] : "")));
		$gui->setParser("firma","AuftraegeGUI::firmaParser",array("\$sc->nachname","asd","\$sc->vorname", "\$sc->UserID", "\$ProjektID", "\$ProjektName", "\$AuftragAdresseNiederlassungID", "\$AuftragAdresseNiederlassungData"));


		$gui->setIsDisplayMode(true);
		$gui->setDeleteInDisplayMode(true);
		$gui->setEditInDisplayMode(true, "contentLeft");


		$tab = new HTMLSideTable("left");

		
		if(Applications::activeApplication() == "lightCRM"){
			
			$BN = $tab->addButton("Neues\nAngebot","angebot");
			#$BN->customSelect("contentRight", "-1", "mWAngebot", "Auftrag.createOffer");
			$BN->onclick("Auftrag.createEmpty(function(){Auftrag.selectAdresse();}, 'A');");
			
			$tab->addRow(array("&nbsp;"));
		} elseif(Applications::activeApplication() == "upFab"){
			
			$BN = $tab->addButton("Neuer\nLieferschein","lieferschein");
			#$BN->customSelect("contentRight", "-1", "mWAngebot", "Auftrag.createOffer");
			$BN->onclick("Auftrag.createEmpty(function(){Auftrag.selectAdresse();}, 'L');");
			
			$tab->addRow(array("&nbsp;"));
		} else {
			$BA1x = $tab->addButton("Neuen Auftrag\nerstellen", "new");
			$BA1x->onclick("Auftrag.createEmpty(function(){Auftrag.selectAdresse();});");

			$tab->addRowClass("backgroundColor0");
			$tab->setRowID("AddAdresseButton");

			$tab->addRow(array("&nbsp;"));


			$BA1x = $tab->addButton("Auftrag mit\n1x-Adresse", "1xAddress");
			$BA1x->className("backgroundColor0");
			$BA1x->onclick("Auftrag.createEmpty(function(){ contentManager.editInPopup('AuftragAdresse', -1, '1x-Adresse erstellen', 'AuftragAdresseGUI;AuftragID:'+Auftrag.newestID+';displayMode:auftragsAdresse');});");
			$tab->addRowClass("backgroundColor0");
			$tab->setRowID("Add1xAdresseButton");
		}
		
		
		$BP = $tab->addButton("multiDruck\nListe anzeigen","liste");
		$BP->onclick("contentManager.editInPopup('multiPrintBasket','','multiDruck-Liste');");
		$BP->id("showMultiPrintList");
		$tab->addRowClass("backgroundColor0");

		$currentFilter = mUserdata::getUDValueS("filteredCategoriesInHTMLGUIAuftraege", "");
		
		$statusButtons = "<div style=\"margin-bottom:5px;\"><small style=\"color:grey;\">Filtern nach Auftragsstatus:</small></div>";
		$status = self::getStatus();
		$statusIcons = self::getStatusIcons();
		$i = 1;
		foreach($status AS $k => $v){
			$statusSet = "";
			foreach(self::getStatus() AS $ks => $vs)
				$statusSet .= ($statusSet != "" ? ";" : "")."$ks:".($ks == $k ? "1" : "0");
			
			
			$BO = new Button($v, $statusIcons[$k], "iconicG");
			$BO->rmePCR("HTML", "", "saveContextMenu", array("'filterCategories'", "'Auftraege--;$statusSet'"), "contentManager.reloadFrame('contentRight');");
			$BO->style($i < count($status) ? "margin-right:12px;" : "");
			if(strpos($currentFilter, $k) !== false)
				$BO->style("color:#222;".($i < count($status) ? "margin-right:12px;" : ""));
			
			$statusButtons .= $BO;
			$i++;
		}
		
		if(Applications::activeApplication() != "lightCRM" AND Applications::activeApplication() != "upFab")
			$tab->addRow(array($statusButtons));
		
		#$B = new Button("Kundennummern\nzuordnen", "new");
		#$B->className("backgroundColor0");
		#$B->popup("", "Kundennummern zuordnen", "Auftraege", "-1", "findCustomerNumbers");
		
		#$tab->addRow($B);
		
		Aspect::joinPoint("sideTable", $this, __METHOD__, $tab);
		
		$gui->customize($this->customizer);

		$html = $gui->getBrowserHTML($id);
		$_SESSION["BPS"]->setProperty("mGRLBMGUI","type","$oldType");
		return ($id == -1 ? $tab : "").$html;
	}

	public function findCustomerNumbers(){
		$T = new HTMLTable(3);
		$AC = anyC::get("Auftrag");
		$AC->addAssocV3("kundennummer", "<", "0");
		while($A = $AC->n()){
			$kdnr = "kA";
			$user = "kA";
			
			$Adresse = new Adresse($A->A("AdresseID"));
			
			$ACA = anyC::get("Adresse");
			$ACA->addAssocV3("AuftragID", "=", "-1");
			$ACA->addAssocV3("firma", "=", $Adresse->A("firma"));
			$ACA->addAssocV3("plz", "=", $Adresse->A("plz"));
			$ACA->addAssocV3("strasse", "=", $Adresse->A("strasse"));
			$ACA->addAssocV3("nr", "=", $Adresse->A("nr"));
			
			$AdOrig = $ACA->n();
			
			if($AdOrig != null){
				$K = Kappendix::getKappendixToAdresse($AdOrig->getID());
				if($K != null){
					$A->changeA("kundennummer", $K->A("kundennummer"));
					$A->changeA("UserID", $K->A("KappendixAuftragUserID"));
					
					$kdnr = $K->A("kundennummer");
					$user = $K->A("KappendixAuftragUserID");
					$A->saveMe();
				}
			}
			
			$T->addRow(array($A->getID(), $Adresse->getShortAddress(), $kdnr."/".$user));
		}
		
		echo $T;
	}
	
	protected static $GRLBMAttributes;

	public static function getPaidImg($w, $l, $p){
		if(!is_array($p))
			$s = HTMLGUI::getArrayFromParametersString($p);
		else
			$s = $p;
		
		if(self::$GRLBMAttributes == null){
			$GRLBM = new GRLBM(-1);
			self::$GRLBMAttributes = PMReflector::getAttributesArray($GRLBM->newAttributes());

			if(self::$BelegArten == null)
				self::$BelegArten = Auftrag::getBelegArten();
			if(!Auftrag::getBelegArten("B"))
				self::$BelegArten = array_merge(array_slice(self::$BelegArten, 0, array_search("A", self::$BelegArten) + 1), array("B"), array_slice(self::$BelegArten, array_search("A", self::$BelegArten) + 1));
		}
		
		$GRLBMs = anyC::get("GRLBM");
		$GRLBMs->addAssocV3("AuftragID","=",$s[0]);
		$GRLBMs->addAssocV3("isM","=","0");
		$GRLBMs->setFieldsV3(self::$GRLBMAttributes);

		foreach(self::$BelegArten AS $B)
			if(in_array("is".$B, self::$GRLBMAttributes))
				$GRLBMs->addFieldV3("GROUP_CONCAT(is$B) as is{$B}C");
		
		$GRLBMs->addFieldV3("GROUP_CONCAT(isWhat) as isWhatC");
		
		$GRLBMs->addGroupV3("AuftragID");
		if($s[1] == "true") $GRLBMs->addAssocV3("isWhat","=","Kalk");
		
		$t = $GRLBMs->getNextEntry();

		if($t == null) return "keine Belege";

		$g = array();
		foreach(self::$BelegArten AS $B){
			if(!isset($g[$B])) $g[$B] = 0;
			
			if(in_array("is".$B, self::$GRLBMAttributes))
				$g[$B] += substr_count($t->A("is{$B}C"),"1");
			else
				$g[$B] += substr_count($t->A("isWhatC"),$B);
		}
		
		if(array_sum($g) == 1){
			if($s[2] == ",,") $s[2] = "";
			$imgs = "<img id=\"addID".$t->getID()."\" style=\"".((!strstr($s[2],",".$t->getID().",") OR $s[2] == "") ? "display:block;":"display:none;")."float:left;\" title=\"zu multiDruck-Liste hinzufügen\" class=\"mouseoverFade\" src=\"./images/i2/notok.gif\" onclick=\"Auftrag.addToMultiPrint('".$t->getID()."');\" />
	<img id=\"subID".$t->getID()."\" style=\"".((!strstr($s[2],",".$t->getID().",") OR $s[2] == "") ? "display:none;":"display:block;")."float:left;\" class=\"mouseoverFade\" title=\"von multiDruck-Liste entfernen\" src=\"./images/i2/ok.gif\" onclick=\"Auftrag.subFromMultiPrint('".$t->getID()."');\" />";

			return "$imgs<strong>".substr($t->getMyPrefix(), 0, 1).$t->A("nummer")."</strong><br /><small>vom ".Util::CLDateParser($t->A("datum"))."</small>";
		}


		$Tab = new HTMLTable(count(self::$BelegArten));
		$Tab->setTableStyle("width:100%;border:0px;");

		foreach(self::$BelegArten AS $k => $v){
			$Tab->setColClass($k + 1, "");

			$Tab->setColWidth($k + 1, round(100 / count(self::$BelegArten))."%");

			self::$BelegArten[$k] = substr($v, 0, 1);
			if($g[$v] == 0) $g[$v] = "";
		}

		$Tab->addRow(self::$BelegArten);
		if(Applications::activeApplication() == "lightCRM" OR Applications::activeApplication() == "upFab")
			$Tab->addRowStyle("font-weight:bold;");
		else
			$Tab->addRowStyle("font-weight:bold;text-align:right;");


		$Tab->addRow(array_values($g));
		if(Applications::activeApplication() != "lightCRM" AND Applications::activeApplication() != "upFab")
			$Tab->addRowStyle("text-align:right;");

		return $Tab;
			
	}

	public function getAvailableCategories(){
		return self::getStatus();
	}

	public function getCategoryFieldName(){
		return "status";
	}

	
	public static function firmaParser($w,$l,$p){
		$s = HTMLGUI::getArrayFromParametersString($p);
		
		$name = ($w == "" ? stripslashes($s[2]).($s[2] != "" ? " " : "").stripslashes($s[0]) : stripslashes($w));
		
		if($s[6] > 0){
			$data = json_decode($s[7]);
			$name .= "<br><small style=\"color:grey;\">".$data->AdresseNiederlassungOrt."</small>";
		}
		
		$name .= ((isset($s[3]) AND isset(self::$users[$s[3]])) ? "<br><small style=\"color:grey;\">von ".self::$users[$s[3]]."</small>" : "");
		
		
		if($s[4] > 0)
			$name .= "<br><small style=\"color:grey;\">$s[5]</small>";
		
		return $name;
	}
	
	public static function parseRnr($w,$l,$p){
		if($w != "") return $p."$w";
	}
	
	public static $prefixes = array();
	public function getACHTML($attributeName, $query){
		$Users = Users::getUsers();
		while($U = $Users->getNextEntry())
			self::$users[$U->getID()] = $U->A("name");
		
		
		$ASH = new AuftraegeSearchHelper();
		
		$searchByCID = false;
		if(is_numeric($query) AND in_array("kundennummer", $this->searchFields))
			$searchByCID = true;
		
		$gui = new HTMLGUI();
		$searchByNumber = false;
		if($query{0} == "#" AND isset($query{1}) AND (strpos("RLKAGBW", strtoupper($query[1])) !== false)){
			$searchByNumber = true;
			$isWhat = 1;
			$query = str_replace("#","", $query);
			$what = "is".$query{0};
			if($query{0} == "K"){
				$isWhat = "Kalk";
				$what = "isWhat";
			}
			if($query{0} == "W"){
				$isWhat = "W";
				$what = "isWhat";
			}
			$ASH->addJoinV3("GRLBM",$what,"=",$isWhat);
			
			$gui->setParser("nummer", "AuftraegeGUI::parseRnr", array($query{0}));
		}
		
		$_SESSION["BPS"]->registerClass("HTMLGUI");
		$_SESSION["BPS"]->setACProperty("targetFrame","contentLeft");
		$_SESSION["BPS"]->setACProperty("targetPlugin","Auftrag");
					
		$gui->setShowAttributes(array("firma","nummer"));

		$gui->setParser("firma","AuftraegeGUI::firmaParser", array("\$sc->nachname","asd","\$sc->vorname", "\$sc->UserID", "\$ProjektID", "\$ProjektName", "\$AuftragAdresseNiederlassungID", "\$AuftragAdresseNiederlassungData"));
		$gui->setParser("nummer","AuftraegeGUI::nummerParser", array("\$sc->datum", "\$sc->GRLBMID"));

		
		$searchByReference = false;
		if($query{0} == "?"){
			$query = str_replace("?","", $query);
			$searchByReference = true;
		}
		
		$searchByProjekt = false;
		if($query{0} == "*" AND Session::isPluginLoaded("mProjekt")){
			$query = str_replace("*","", $query);
			$searchByProjekt = true;
		}
		
		$searchByDate = false;
		if(Util::CLDateParser($query, "store") != -1){
			$searchByDate = true;
		}
		
		
		
		#$ASH->addJoinV3("User","UserID","=","UserID");
		$ASH->addAssocV3("isM","=","0");
			
		switch($attributeName){
			case "quickSearchAuftraege":
				$Users = Users::getUsers();
				while($U = $Users->getNextEntry())
					self::$users[$U->getID()] = $U->A("name");
		
				$pSpecData = mUserdata::getPluginSpecificData("Auftraege");
				$ud = new mUserdata();
				$d = $ud->getUDValueCached("auftraegeMinDate");
				$d2 = $ud->getUDValueCached("auftraegeMaxDate");
				
				if(isset($pSpecData["pluginSpecificCanOnlyEditOwn"]))
					$ASH->addAssocV3("t1.UserID","=", Session::currentUser()->getID());
				
				foreach($pSpecData as $key => $value) 
					if(strstr($key,"pluginSpecificCanSeeAuftraegeFrom")) $ASH->addAssocV3("t1.UserID","=",str_replace("pluginSpecificCanSeeAuftraegeFrom","",$key),"OR");
		
				$html = "";
				if(!$searchByDate AND !$searchByNumber AND !$searchByCID AND !$searchByReference AND ($d != null OR $d2 != null)) {
					$D = new Datum(time());
					$datum = $D->parseGerDate($d);
					
					$D2 = new Datum(time());
					$datum2 = $D2->parseGerDate($d2);
					
					if($datum != -1 OR $datum2 != -1) {
						if($datum != -1) $ASH->addAssocV3("datum",">=", $datum);
						if($datum2 != -1) $ASH->addAssocV3("datum","<=", $datum2);
						
						$html = "<p style=\"padding:3px;\"><img src=\"./images/i2/note.png\" style=\"float:left;margin-right:3px;\" /> Es werden nur Belege angezeigt, die ".($datum != -1 ? "<strong>ab</strong> dem $d" : "").(($datum != -1 AND $datum2 != -1) ? " und " : "").($datum2 != -1 ? "<strong>vor</strong> dem $d2" : "")." erstellt wurden.</p>";
					}
				}
				
				
				if(!$searchByDate AND !$searchByNumber AND !$searchByCID AND !$searchByReference AND $d == null){
					$html = "<p style=\"padding:3px;\"><img src=\"./images/i2/note.png\" style=\"float:left;margin-right:3px;\" /><small>Aus Gründen der Performance werden nur die Aufträge der letzten 3 Monate durchsucht. Bitte stellen Sie manuell ein Datum ein, um dies zu überschreiben.</small></p>";
					$ASH->addAssocV3("datum",">=", time() - 3 * 4 * 7 * 24 * 3600);
				}
				
				if($searchByNumber)
					$ASH->addAssocV3("nummer","LIKE","%".substr($query,1)."%","AND","1");
				
				
				elseif($searchByCID){
					$ASH->addAssocV3("kundennummer", "LIKE", "%$query%", $k == 0 ? "AND" : "OR", "1");
					$ASH->addGroupV3("AuftragID");
				}
				elseif($searchByProjekt){ //Findet auch Aufträge ohne Beleg
					$AC = anyC::get("Projekt");
					$AC->addJoinV3("Auftrag", "ProjektID", "=", "ProjektID");
					$AC->addJoinV3("Adresse","t2.AdresseID","=","AdresseID");

					$AC->setLimitV3("10");
					$AC->addOrderV3("auftragDatum","DESC");
					
					$fields = array("nachname", "auftragDatum AS datum", "t2.AuftragID", "t1.ProjektID", "ProjektName","firma","vorname", "t2.UserID", "AuftragAdresseNiederlassungID", "AuftragAdresseNiederlassungData");
					$AC->setFieldsV3($fields);
					
					$AC->addAssocV3("ProjektName", "LIKE", "%$query%", "AND", "1");
					$AC->lCV3();
					
					$gui->setObject($AC);
					$gui->customize($this->customizer);

					die($gui->getACHTMLBrowser("quickSearchLoadFrame", true, null, "AuftragID"));
				}
				elseif($searchByReference)
					$ASH->addAssocV3("GRLBMReferenznummer", "=", $query, "AND", "1");
				
				elseif($searchByDate)
					$ASH->addAssocV3("t3.datum", "=", Util::CLDateParser($query, "store"), "AND", "1");
				
				
				else {
					foreach($this->searchFields AS $k => $field)
						$ASH->addAssocV3($field, "LIKE", "%$query%", $k == 0 ? "AND" : "OR", "1");
					
					$ASH->addGroupV3("AuftragID");
				}
					#$q2 = DBStorage::findNonUft8($query); // REMOVED 31.10.2012
					#foreach($this->searchFields AS $k => $field)
					#	$ASH->addAssocV3($field, "LIKE", "%$q2%", "OR", "1");
					
					
				$ASH->addOrderV3("datum","DESC");
				$ASH->lCV3();
				
				if($ASH->numLoaded() > 0){
					$AC = anyC::get("GRLBM");
					while($A = $ASH->getNextEntry())
						$AC->addAssocV3("GRLBMID", "=", $A->A("GRLBMID"), "OR");


					while($GRLBM = $AC->getNextEntry())
						self::$prefixes[$GRLBM->getID()] = $GRLBM->getMyPrefix();
				}
				
				$ASH->resetPointer();
				
				$gui->setObject($ASH);
				$gui->customize($this->customizer);

				echo $html.$gui->getACHTMLBrowser("quickSearchLoadFrame");
			break;
		}
	}
	
	public static function nummerParser($w, $l, $p){
		$s = HTMLGUI::getArrayFromParametersString($p);
		return self::$prefixes[$s[1]].$w."<br /><small>".date("d.m.Y",$s[0]+0)."</small>";
	}
	
	public function getContextMenuHTML($identifier){
		$ud = new mUserdata();
		$d = $ud->getUDValueCached("auftraegeMinDate");
		$d2 = $ud->getUDValueCached("auftraegeMaxDate");
		echo "
		<p style=\"padding:5px;\">
			Nur Einträge <strong>ab</strong> Datum: <input id=\"auftraegeMinDate\" value=\"$d\" type=\"text\" style=\"width:100px;\" /> 
			<input src=\"./images/i2/save.gif\" style=\"width:18px;border:0px;\" onclick=\"rme('mUserdata','','setUserdata',new Array('auftraegeMinDate',$('auftraegeMinDate').value),'showMessage(\'gespeichert\')');\" type=\"image\" />
			<input src=\"./images/i2/delete.gif\" style=\"width:18px;border:0px;\" onclick=\"rme('mUserdata','','delUserdata',new Array('auftraegeMinDate'),'showMessage(\'gelöscht\');$(\'auftraegeMinDate\').value = \'\';');\" type=\"image\" />
		</p>
		<p style=\"padding:5px;\">
			Nur Einträge <strong>bis</strong> Datum: <input id=\"auftraegeMaxDate\" value=\"$d2\" type=\"text\" style=\"width:100px;\" /> 
			<input src=\"./images/i2/save.gif\" style=\"width:18px;border:0px;\" onclick=\"rme('mUserdata','','setUserdata',new Array('auftraegeMaxDate',$('auftraegeMaxDate').value),'showMessage(\'gespeichert\')');\" type=\"image\" />
			<input src=\"./images/i2/delete.gif\" style=\"width:18px;border:0px;\" onclick=\"rme('mUserdata','','delUserdata',new Array('auftraegeMaxDate'),'showMessage(\'gelöscht\');$(\'auftraegeMaxDate\').value = \'\';');\" type=\"image\" />
		</p>
		<p style=\"margin-top:10px;padding:5px;\">Es werden folgende Felder durchsucht:<br><br>Firma<br>Kundennummer<br>Vorname und Nachname<br><br>Um eine Rechnung zu finden, suchen Sie z.B. nach '#R070010'.<br />Lieferschein: '#L...',<br />Gutschein: '#G...',<br />Angebot: '#A...',<br />Bestätigung: '#B...' und<br />Kalkulation: '#K...'</p>
		
		<p style=\"margin-top:10px;padding:5px;\">Um nach einer Referenznummer zu suchen, beginnen Sie die Suche mit einem '?'. Also zum Beispiel '?300' findet den Beleg mit der Referenznummer 300.</p>";
		
		if(Session::isPluginLoaded("mProjekt"))
			echo "<p>Eine Projektnummer finden Sie mit vorangestelltem *.";

		echo "<p><img src=\"./images/i2/searchFilter.png\" style=\"float:left;margin-right:5px;\" /> Das Ergebnis der Filterung nach einem Suchbegriff weicht vom Ergebnis der Schnellsuche ab.";

	}
	
	public function getSearchedFields(){
		return array("firma", "CONCAT(vorname, ' ', nachname)", "kundennummer");
	}
	
	public function saveContextMenu($identifier, $key){}

	public function createEmpty($addBeleg = null, $AdresseID = null){
		echo parent::createEmpty($addBeleg, $AdresseID);
	}
}
?>
