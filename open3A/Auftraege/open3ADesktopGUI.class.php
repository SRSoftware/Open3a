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
class open3ADesktopGUI extends ADesktopGUI implements iGUIHTML2 {
	
	public $currentVersion = null;
	
	public function getHTML($id){
		// <editor-fold defaultstate="collapsed" desc="Aspect:jP">
		try {
			$MArgs = func_get_args();
			return Aspect::joinPoint("around", $this, __METHOD__, $MArgs);
		} catch (AOPNoAdviceException $e) {}
		Aspect::joinPoint("before", $this, __METHOD__, $MArgs);
		// </editor-fold>
		
		if($_SESSION["S"]->isUserAdmin()) return parent::getHTML($id);
		
		switch($id){
			case "1":
				$hiddenPlugins = mUserdata::getHiddenPlugins();
				
				$buttons = array();
				$buttons[] = "Aufträge<br />anzeigen;auftrag.png;contentManager.loadPlugin('contentRight','Auftraege', 'AuftraegeGUI#-');Auftraege;Auftrag";
				$buttons[] = "Artikel<br />anzeigen;artikel.png;contentManager.loadPlugin('contentRight','mArtikel', 'mArtikelGUI#-');mArtikel;Artikel";
				$buttons[] = "Adressen<br />anzeigen;adresse.png;contentManager.loadPlugin('contentRight','Adressen', 'AdressenGUI#-');Adressen;Adresse";
				
				$html = "";
				foreach($buttons AS $k => $v) {
					$s = explode(";",$v);
					if(isset($hiddenPlugins[$s[3]])) continue;
					#if(!mUserdata::isDisallowedTo("cantCreate$s[4]")) continue;
					
					$html .= "
						<div onclick=\"".str_replace("#",";",$s[2])."\" class=\"desktopButton\">
							<img style=\"float:left;margin-right:30px;\" src=\"./images/big/$s[1]\" />
							
							<p style=\"font-size:2.0em;font-weight:bold;color:#999999;\">$s[0]</p>
							
						</div>";
				}

				return $html;
			break;
			
			case "2":
				if(Environment::getS("desktopLeftShow", "1") == "0")
					return "";
		
				$this->currentVersion = $_SESSION["applications"]->getRunningVersion();
				
				$hpVersion = null;
				$html = "";
				if(strpos($_SERVER["SCRIPT_FILENAME"],"demo") === false AND strpos($_SERVER["SCRIPT_FILENAME"],"demo_all") === false){
					$version = Util::httpTestAndLoad("http://www.furtmeier.it/open3AcurrentVersion.php", 2);
					
					$XML = new XMLC();
					$XML->setXML($version["_response"]);
					try {
						$XML->lCV3();
						$t = $XML->getNextEntry();
						if($t != null)
							$hpVersion = $t->getA()->Version;
					} catch (Exception $e){}
				}

				$F = new File(Util::getRootPath()."system/Backup");

				$UD = new mUserdata();
				$noBM = mUserdata::getGlobalSettingValue("disableBackupManager", $UD->getUDValue("noBackupManager", false));

				if(!$F->A("FileIsWritable") AND !$noBM) $html .= "
						<div class=\"desktopButton\" onclick=\"contentManager.rmePCR('BackupManager', '', 'getWindow', '', 'Popup.displayNamed(\'BackupManagerGUI\',\'Backup-Manager\',transport);');\">
							<img style=\"float:right;margin-left:30px;\" src=\"./images/big/warnung.png\" />
							<p style=\"font-size:1.2em;font-weight:bold;color:#999999;\">open3A kann keine Sicherungen Ihrer Datenbank erstellen! <br />Klicken Sie hier für weitere Informationen.</p>
						</div>";
				
				$backedUp = BackupManagerGUI::checkForTodaysBackup();
				if($F->A("FileIsWritable") AND !$noBM AND !$backedUp)
					$html .= "
						<div class=\"desktopButton\" onclick=\"contentManager.rmePCR('BackupManager', '', 'getWindow', '', 'Popup.displayNamed(\'BackupManagerGUI\',\'Backup-Manager\',transport);');\">
							<img style=\"float:right;margin-left:30px;\" src=\"./images/big/notice.png\" />
							<p style=\"font-size:1.2em;font-weight:bold;color:#999999;\">Klicken Sie hier, um das tägliche Backup der Datenbank anzulegen.</p>
						</div>";

				$html .= "
						<div class=\"desktopButton\" onclick=\"window.open('http://www.open3a.de/page-Plugins/', '_blank');\">
							<img style=\"float:right;margin-left:30px;\" src=\"./images/big/internet.png\" />
							<p style=\"font-size:1.2em;font-weight:bold;color:#999999;\">Besuchen Sie open3A.de für<br />neue Versionen,<br />mehr Plugins und<br />mehr Funktionen.</p>
						</div>";
				$html .= "
						<div class=\"desktopButton\" onclick=\"window.open('https://www.open3a.de/page-Registrierung', '_blank');\">
							<img style=\"float:right;margin-left:30px;\" src=\"./images/big/newsletter.png\" />
							<p style=\"font-size:1.2em;font-weight:bold;color:#999999;\">Registrieren Sie sich noch heute kostenlos auf open3A.de und Sie erhalten die aktuellsten News zu open3A in Ihr Postfach.</p>
						</div>";
						
				$html .= "
						<div class=\"desktopButton\" onclick=\"window.open('http://forum.furtmeier.it/', '_blank');\">
							<img style=\"float:right;margin-left:30px;\" src=\"./images/big/help.png\" />
							<p style=\"font-size:1.2em;font-weight:bold;color:#999999;\">Im open3A.de Support-Forum erhalten Sie kompetente Unterstützung bei Fragen und Problemen.</p>
						</div>";
				
				if ($F->A("FileIsWritable") AND !$noBM AND $backedUp)
					$html .= "
						<div class=\"desktopButton\" style=\"height:auto;min-height:0px;\" onclick=\"contentManager.rmePCR('BackupManager', '', 'getWindow', '1', 'Popup.displayNamed(\'BackupManagerGUI\',\'Backup-Manager\',transport);');\">
							<p style=\"font-size:1.2em;font-weight:bold;color:#999999;\">Ein neues Backup der Datenbank anlegen.</p>
						</div>";
				
				if(strpos($_SERVER["SCRIPT_FILENAME"],"demo") === false AND strpos($_SERVER["SCRIPT_FILENAME"],"demo_all") === false){
						
					if($hpVersion != null AND (Util::versionCheck($hpVersion, $this->currentVersion) OR Util::versionCheck($hpVersion, $this->currentVersion, "<"))) 
						$html .= "
							<div class=\"desktopButton\" onclick=\"window.open('http://www.open3a.de/', '_blank');\" style=\"height:auto;min-height:0px;\">
								<p>".(Util::versionCheck($hpVersion, $this->currentVersion) ? "Auf der Homepage steht eine neue Version von open3A zur Verfügung ($hpVersion). Sie benutzen Version $this->currentVersion." : (Util::versionCheck($hpVersion, $this->currentVersion, "==") ? "Ihre open3A-Version ist auf dem aktuellen Stand." : "Ihr open3A ist aktueller als die Version auf der Homepage!"))."</p>
							</div>";
				}
				return $html;
			break;
			
			case "3":
				return $this->getOffice3aRSS();
			break;
		}

	}
}
?>
