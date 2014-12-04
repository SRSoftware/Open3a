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

if(count($notExecutable) > 0 AND is_executable("./system")){
	require_once "./system/basics.php";

	emoFatalError(
	"I'm sorry, but I'm unable to access some directories",
	"Please make sure that the webserver is able to access these directories and its subdirectories:<br /><br />".implode("<br />", $notExecutable)."<br /><br />Usually a good plan to achieve this, is to run the following<br />commands in the installation directory:<br /><code>chmod -R u=rw,g=r,o=r *<br />chmod -R u=rwX,g=rX,o=rX *</code>",
	"phynx");
}

require "./system/connect.php";


try {
	# Change 'localhost' to your domain name.
	$openid = new LightOpenID($_SERVER['HTTP_HOST']);
	if(!$openid->mode) {
		if(isset($_GET['openid'])){
			$openid->identity = $_GET['openid'];
			header('Location: ' . $openid->authUrl());
		} elseif(isset($_POST['openid'])) {
			$openid->identity = $_POST['openid'];
			header('Location: ' . $openid->authUrl());
		}
		?>
<form action="" method="post">
	OpenID: <input type="text" name="openid" />
	<button>Submit</button>
</form>
<?php
	} elseif($openid->mode == 'cancel') {
				emoFatalError("Login abgebrochen", "Der Loginvorgang wurde durch den Nutzer abgebrochen!", "OpenID-Login abgebrochen!");
    } else {
			if ($openid->validate()){

				T::load(Util::getRootPath()."libraries");

				if($_SESSION["S"]->checkIfUserLoggedIn() == false) $_SESSION["CurrentAppPlugins"]->scanPlugins();

				$U = new Users();
				$U = $U->getUserByOpenid($openid->identity);
				if ($U != null){
					$_SESSION["S"]->setLoggedInUser($U);
					$_SESSION["S"]->initApp('open3A');
					header('Location: .');
				}


			} else {
				emoFatalError("Login fehlgeschlagen", "Der Loginvorgang für ".$openid->identity." war nicht erfolgreich!", "OpenID-Login fehlgeschlagen!");
			}
    }
} catch(ErrorException $e) {
    echo $e->getMessage();
}
