<?php

/**
 *  This is an openid login addition written to complement open3a.
 *  
 *  Stephan Richter
 *  
 */

/* add your company website for coorporate login here: */

$companies = array();

$companies[] = array('ah-dienste','http://ah-dienste.de/sites/ah-dienste.de/themes/ahd/images/banner_farben.png','http://ah-dienste.de');
$companies[] = array('Autocentrum Elliger','http://auto-elliger.de/sites/auto-elliger.de/files/logo_0.png','http://auto-elliger.de/');
$companies[] = array('SRSoftware GbR','https://srsoftware.de/sites/srsoftware.de/files/srsoftware.de_logo.gif','http://srsoftware.de');
$companies[] = array('UGN-Umwelttechnik GmbH','http://www.ugn-umwelttechnik.de/sites/ugn-umwelttechnik.de/themes/UGN_2014/logo.png','http://www.ugn-umwelttechnik.de');


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
<html>
  <head><meta charset="utf-8" />
  <link rel="stylesheet" type="text/css" href="styles/openid_login.css"/>
  </head>
  <body>
  <div class="openid_login">
    <form action="" method="post">
	  OpenID: <input type="text" name="openid" />
	  <button type="submit">Login</button>
    </form>

<?php

  foreach ($companies as $company){ ?>
    <div class="company">
      <a href="openid_login?openid=<?php echo $company[2]; ?>"><img src="<?php echo $company[1];?>" alt="<?php echo $company[0]; ?>" /></a>
    </div>
<?php } ?>
  </div>
  </body>
</html>
<?php 
	} elseif($openid->mode == 'cancel') {
				emoFatalError("Login abgebrochen", "Der Loginvorgang wurde durch den Nutzer abgebrochen!", "OpenID-Login abgebrochen!");
    } else {
			if ($openid->validate()){

				T::load(Util::getRootPath()."libraries");

				if($_SESSION["S"]->checkIfUserLoggedIn() == false) $_SESSION["CurrentAppPlugins"]->scanPlugins();

				$U = new Users();
				$U->doLogout();
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
