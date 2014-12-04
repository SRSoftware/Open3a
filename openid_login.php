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
 *  2007 - 2014, Rainer Furtmeier - Rainer@Furtmeier.IT
 */

if(!function_exists("array_replace")){
	require_once "./system/basics.php";

	emoFatalError("I'm sorry, but your PHP version is too old.", "You need at least PHP version 5.3.0 to run this program.<br />You are using ".phpversion().". Please talk to your provider about this.", "phynx");
}

$dir = new DirectoryIterator(dirname(__FILE__));
$notExecutable = array();
foreach ($dir as $file) {
	if($file->isDot()) continue;
	if(!$file->isDir()) continue;

	if($file->getFilename() == "logs")
		continue;

	if($file->isExecutable()) continue;
	$notExecutable[] = $file->getFilename();
}

#if(count($notExecutable) > 0 AND !is_executable("./system") AND stripos(getenv("OS"), "Windows") === false)
	#	die("The directory <i>system</i> is not marked executable.<br />Please resolve this issue by running the following command inside the installation directory:<br /><code>chmod u=rwX,g=rX,o=rX system</code>");

if(count($notExecutable) > 0 AND is_executable("./system")){
	require_once "./system/basics.php";

	emoFatalError(
	"I'm sorry, but I'm unable to access some directories",
	"Please make sure that the webserver is able to access these directories and its subdirectories:<br /><br />".implode("<br />", $notExecutable)."<br /><br />Usually a good plan to achieve this, is to run the following<br />commands in the installation directory:<br /><code>chmod -R u=rw,g=r,o=r *<br />chmod -R u=rwX,g=rX,o=rX *</code>",
	"phynx");
}

/*$texts = array();
 $texts["it_IT"] = array();
$texts["it_IT"]["username"] = "Username";
$texts["it_IT"]["password"] = "Password";
$texts["it_IT"]["application"] = "Applicazione";
$texts["it_IT"]["login"] = "accesso";
$texts["it_IT"]["autologin"] = "accesso automatico";
$texts["it_IT"]["save"] = "memorizzare i dati";
$texts["it_IT"]["sprache"] = "Lingua";
$texts["it_IT"]["optionsImage"] = "Visualizzare le opzioni";
$texts["it_IT"]["lostPassword"] = "Password persa?";*/

require "./system/connect.php";
#$browserLang = Session::getLanguage();



try {
    # Change 'localhost' to your domain name.
    $openid = new LightOpenID('eldorado.srsoftware.de:816');
    if(!$openid->mode) {
        if(isset($_POST['openid_identifier'])) {
            $openid->identity = $_POST['openid_identifier'];
            # The following two lines request email, full name, and a nickname
            # from the provider. Remove them if you don't need that data.
            $openid->required = array('contact/email');
            $openid->optional = array('namePerson', 'namePerson/friendly');
            header('Location: ' . $openid->authUrl());
        }
?>
<form action="" method="post">
    OpenID: <input type="text" name="openid_identifier" /> <button>Submit</button>
</form>
<?php
    } elseif($openid->mode == 'cancel') {
        echo 'User has canceled authentication!';
    } else {
			if ($openid->validate()){
				
				T::load(Util::getRootPath()."libraries");
				/*
				 $E = new Environment();
				*/
				$cssColorsDir = Environment::getS("cssColorsDir", (isset($_COOKIE["phynx_color"]) ? $_COOKIE["phynx_color"] : "standard"));
				$cssCustomFiles = Environment::getS("cssCustomFiles", null);
				/*
				 if(file_exists(Util::getRootPath()."plugins/Cloud/Cloud.class.php")){
				require_once Util::getRootPath()."plugins/Cloud/Cloud.class.php";
				require_once Util::getRootPath()."plugins/Cloud/mCloud.class.php";
				
				$E = mCloud::getEnvironment();
				}*/
				
				$build = rand(1, 9999999);
				if(Phynx::build()){
					#$xml = new SimpleXMLElement(file_get_contents(Util::getRootPath()."system/build.xml"));
				
					if(isset($_COOKIE["phynx_lastSeenBuild"]) AND $_COOKIE["phynx_lastSeenBuild"] != Phynx::build()){
						$isCloud = file_exists(Util::getRootPath()."plugins/Cloud/Cloud.class.php");
				
						header("Cache-Control: no-cache, must-revalidate");
						header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
						setcookie("phynx_lastSeenBuild", Phynx::build(), time() + 3600 * 24 * 365);
						//header("location: ".  basename(__FILE__));
				
						$button = "
			<div
				onclick=\"document.location.reload(true);\"
				onmouseover=\"this.style.backgroundColor = '#d7eac5';\"
				onmouseout=\"this.style.backgroundColor = 'transparent';\"
				style=\"width:120px;padding:10px;border:1px solid green;border-radius:5px;box-shadow:2px 2px 4px grey;margin-top:20px;cursor:pointer;font-weight:bold;\">
				
				<img src=\"./images/navi/navigation.png\" style=\"float:left;margin-top:-8px;margin-right:10px;\" />Weiter
			</div>";
				
						emoFatalError(T::_("Diese Anwendung wurde aktualisiert"), "Der Administrator dieser Anwendung hat seit Ihrem letzten Besuch eine Aktualisierung eingespielt.</p>
			".(!$isCloud ? "<p style=\"margin-left:80px;\">Bitte entscheiden Sie sich nun für eine der beiden Möglichkeiten,<br />abhängig davon, ob Sie diese Anwendung eingerichtet haben, oder eine andere Person:</p>" : "")."
			<div style=\"width:800px;\">
				".(!$isCloud ? "<div style=\"width:350px;float:right;\">
								<h2>Administrator</h2>
								<p>Wenn Sie diese Anwendung eingerichtet haben und das Admin-Passwort kennen, gehen Sie wie folgt vor, um die Aktualisierung abzuschließen:</p><ol><li>Melden Sie sich mit dem <strong>Admin-Benutzer</strong> am System an.</li><li>Aktualisieren Sie im <strong>Installation-Plugin</strong> die Tabellen mit dem Knopf <strong>\"alle Tabellen aktualisieren\"</strong>.</li><ol>
								$button
								</div>" : "")."
								<div style=\"width:350px;\">
								<h2 style=\"clear:none;\">Benutzer</h2>
								<p>Wenn Sie ein Benutzer dieser Anwendung sind und sie nicht selbst eingerichtet haben, initialisiert sich das System nach einem Klick auf den nachfolgenden Knopf neu und Sie können normal weiterarbeiten.</p>
								$button
								</div>
								<div style=\"clear:both;\"></div>
								</div>", T::_("Diese Anwendung wurde aktualisiert"), false, "ok");
					} elseif(!isset($_COOKIE["phynx_lastSeenBuild"]))
					setcookie("phynx_lastSeenBuild", Phynx::build(), time() + 3600 * 24 * 365);
				
					$build = Phynx::build();
				}
				
				$validUntil = Environment::getS("validUntil", null);
				
				
				if($_SESSION["S"]->checkIfUserLoggedIn() == false) $_SESSION["CurrentAppPlugins"]->scanPlugins();

				$U = new Users();
				$U = $U->getUserByOpenid($openid->identity);
				if ($U != null){
					$_SESSION["S"]->setLoggedInUser($U);
					$_SESSION["S"]->initApp('open3A');
					header('Location: .');
				}
				
				
			} else {
				echo $openid->identity . ' has not logged in.';
				print_r($openid->getAttributes());
			}
    }
} catch(ErrorException $e) {
    echo $e->getMessage();
}
