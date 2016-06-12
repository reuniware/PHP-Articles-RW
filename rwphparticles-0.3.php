<?php
// Improved and clarified management of credentials.
// Warning : still some advanced functions with no need for credentials (ip log delete for example).

session_start();

define("MYSQL_SERVER", "");
define("MYSQL_USER", "");
define("MYSQL_PASSWORD", "");
define("MYSQL_DB", "RWPHPARTICLES");
define("MAIN_TABLE_NAME", "articles");
define("LOG_IP", false); // to enable ip logging of users
define("DELETE_ALL_LOGGED_IP", false); // to enable emptying of ip logs
define("CREATE_DB_IF_NOT_EXISTS", true);
define("CREATE_TABLES_IF_NOT_EXIST", true);
define("DEFAULT_TITLE","RW-PHPArticles v1.0"); // default website name. can be changed in parameters table
define("DEFAULT_TITLE2","Contenu"); // default title of the website. can be changed in parameters table
define("DEFAULT_SUBTITLE","Page des news et articles NTIC"); // defaults subtitle of the website. can be changed in parameters table.

// if no admin account in db, create admin account with defined password (after hashing it)
if (isset($_POST['password']) && isset($_POST['confirmpassword']) ) {
	$password = $_POST['password'];
	$confirmpassword = $_POST['confirmpassword'];
	
	if (trim($password) == '' || trim($confirmpassword) == ''){
		echo 'Password of Confirm password is empty.<br/>';
		exit;
	}
	
	if ($password != $confirmpassword){
		echo 'Cannot create admin account because password do not match.<br/>';
		exit;
	}
	if ($password == $confirmpassword){
		$hashed = md5($password);
		$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
		if ($db->connect_errno) {
			$db->close();
		    exit;
		}
		$r = mysqli_query($db, "select * from " . MAIN_TABLE_NAME . "_" . "user where trim(lower(username))='admin'");
		if ($r->num_rows != 0){
			echo 'Admin account already exists.<br/>';
			$db->close();
			exit;
		}

		$r = mysqli_query($db, "insert into " . MAIN_TABLE_NAME . "_" . "user (username, password) values ('admin','" . $hashed . "')");
		echo 'Admin account created OK.<br/>';
		$db->close();
	}
}


if (isset($_POST['login']) && isset($_POST['password'])){

	$login = trim(strtolower($_POST['login']));
	$passwordhash = md5($_POST['password']);

	echo 'login and password received in POST : ' . $login . " ; " . $passwordhash . '<br/>';

	$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	if ($db->connect_errno) {
		$db->close();
		exit;
	}
	$r = mysqli_query($db, "select * from " . MAIN_TABLE_NAME . "_" . "user where username='" . $login . "' and password='" . $passwordhash . "'");
	if ($r->num_rows > 0){
		echo 'Login attempt OK. Creating session.<br/>';
		$_SESSION['passwordhash'] = $passwordhash;
		$_SESSION['login'] = trim(strtolower($login));
	} else {
		echo 'Login attempt NOT OK.<br/>';
	}
	$db->close();
}

// gerer allowed ici uniquement 
if ( isset($_SESSION['passwordhash']) && isset($_SESSION['login']) ){
	$passwordhash = $_SESSION['passwordhash'];
	$login = $_SESSION['login'];
	
	$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	if ($db->connect_errno) {
		$db->close();
		exit;
	}
	$r = mysqli_query($db, "select * from " . MAIN_TABLE_NAME . "_" . "user where username='" . $login . "' and password='" . $passwordhash . "'");
	if ($r->num_rows > 0){
		echo 'Session existing and credentials OK.<br/>';
		$allowed = true;
	} else {
		echo 'Session existing and credentials NOT OK.<br/>';
		echo 'Credentials received : login = ' . $login . ' and passwordhash = ' . $passwordhash . "<br/>";
		$allowed = false;
	}
	
	$db->close();
}

if ($allowed == true){
	if (isset($_POST['adminfunction'])){
		$adminfunction = $_POST['adminfunction'];
		echo '003:allowed is true ; adminfunction<br/>';
		echo 'adminfunction = ' . $adminfunction .'<br/>';
		$array = explode("?delete_article&id=", $adminfunction);
		if (!is_null($array) && count($array)>0){
			$id = $array[1];

			$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
			if ($db->connect_errno) {
				echo 'error.<br/>';
			    exit;
			}
			
			$str = "delete from " . MAIN_TABLE_NAME . " where id = " . $id;
			echo 'request=[' . $str . ']<br/>';

			$r = mysqli_query($db, "delete from " . MAIN_TABLE_NAME . " where id = " . $id);
			$db->close();
		}
	}
}

if (isset($_GET['disconnect'])){
	if (trim(strtolower($_GET['disconnect'])) == "true"){
		$_SESSION['login'] = '';
		$_SESSION['passwordhash'] = '';
		echo 'Ctredentials in session have been reset.<br/>';
		$allowed = false;
		echo 'allowed is becoming FALSE.<br/>';
	}
}


// supprimer toutes les données de la table annuaire si paramètre get delete_data = true
$delete_data = false;
if (isset($_GET['delete_data'])){
	if (trim(strtolower($_GET['delete_data'])) == "true"){
		$delete_data = true;
	}
}

if ($delete_data == true){
	// DELETE ALL DATA
	$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	if ($db->connect_errno) {
	    exit;
	}
	$sql = "DELETE FROM `" . MAIN_TABLE_NAME . "`";
	$r = mysqli_query($db, $sql);
	$db->close();
	echo 'data deleted.<br/>';
}

// supprimer toutes les données de la table ip_address_log si paramètre get delete_ip = true
$delete_ip = false;
if (isset($_GET['delete_ip'])){
	if (trim(strtolower($_GET['delete_ip'])) == "true"){
		$delete_ip = true;
	}
}

if ($delete_ip == true){
	// DELETE ALL IP
	$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	if ($db->connect_errno) {
	    exit;
	}
	$r = mysqli_query($db, "delete from " . MAIN_TABLE_NAME . "_" . "ip_address_log");
	$db->close();
	echo 'ip deleted.<br/>';
}

// si le paramètre CREATE_DB_IF_NOT_EXISTS est défini à true alors tenter de créer la base de données dans paramètre MYSQL_DB
if (CREATE_DB_IF_NOT_EXISTS == true){
	// CREATE DB IF NOT EXISTS
	$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD);
	if ($db->connect_errno) {
	    exit;
	}
	$r = mysqli_query($db, "create database if not exists " . MYSQL_DB);
	$db->close();
}

// si le paramètre existe alors tenter de créer les tables annuaire et ip_address_log
if (CREATE_TABLES_IF_NOT_EXIST == true){
	// CREATE TABLE IF NOT EXISTS
	$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	if ($db->connect_errno) {
	    exit;
	}
	$sql = "CREATE TABLE IF NOT EXISTS `" . MAIN_TABLE_NAME . "` (`id` bigint(20) NOT NULL AUTO_INCREMENT, `titre` varchar(256) COLLATE latin1_general_ci NOT NULL, `contenu` varchar(8192) COLLATE latin1_general_ci NOT NULL, PRIMARY KEY (`id`) ) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci";
	$r = mysqli_query($db, $sql);
	
	$sql = "CREATE TABLE IF NOT EXISTS `" . MAIN_TABLE_NAME . "_" . "ip_address_log` (`id` bigint(20) NOT NULL AUTO_INCREMENT, `access_date_time` datetime NOT NULL, `ip_address` varchar(32) COLLATE latin1_general_ci NOT NULL, `url` varchar(255) COLLATE latin1_general_ci DEFAULT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci";
	$r = mysqli_query($db, $sql);

    $sql = "CREATE TABLE IF NOT EXISTS `" . MAIN_TABLE_NAME . "_" . "user` (`id` BIGINT NOT NULL AUTO_INCREMENT , `username` VARCHAR( 64 ) NOT NULL , `password` VARCHAR( 256 ) NOT NULL , PRIMARY KEY ( `id` )) ENGINE = MYISAM";
	$r = mysqli_query($db, $sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . MAIN_TABLE_NAME . "_" . "parameters` (`id` bigint(20) NOT NULL AUTO_INCREMENT, `name` varchar(128) COLLATE latin1_general_ci NOT NULL, `value` varchar(512) COLLATE latin1_general_ci NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci";
	$r = mysqli_query($db, $sql);

	$db->close();
}


// LOG IP si paramètre LOG_IP = true
if (LOG_IP==true){
	$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	if ($db->connect_errno) {
	    exit;
	}
	$client_ip = $_SERVER['REMOTE_ADDR'];
	$url = $_SERVER['PHP_SELF'];
	$r = mysqli_query($db, "insert into " . MAIN_TABLE_NAME . "_" . "ip_address_log(ip_address, access_date_time, url) values ('" . $client_ip . "',NOW(),'" . $url . "')");
	$db->close();
}

// Sauvegarde du nom de cette page pour définition des url de post
if (!isset($_POST['current_file_name']))
{
	$_POST['current_file_name'] = getCurrentFileName();
	//echo "current file name = " . $_POST['current_file_name'];
}

if ( isset($_GET['delete_article']) && isset($_GET['id']) ){
	$id=$_GET['id'];
	
	$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	if ($db->connect_errno) {
		echo 'error.<br/>';
	    exit;
	}
	   
	$r = mysqli_query($db, "delete from " . MAIN_TABLE_NAME . " where id = " . $id);
	$db->close();
}

// AJOUT SITE
if ( isset($_POST['titre']) && isset($_POST['contenu']) ){
	$titre = $_POST['titre'];
	$contenu = $_POST['contenu'];
	
	echo 'titre et contenu reçus en POST.<br/>';
    echo "titre = " . $titre . " et contenu = " . $contenu . "<br/>";

   if ( (trim($titre)!='') && (trim($contenu) !='') )
   {
	   $db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
	   if ($db->connect_errno) {
	       echo 'error.<br/>';
	       exit;
	   }
	   
	   $r = mysqli_query($db, "select * from " . MAIN_TABLE_NAME . " where titre = '" . addslashes($titre) . "'");
	   if ($r->num_rows>0){
	   		$r = mysqli_query($db, "delete from " . MAIN_TABLE_NAME . " where titre = '" . $titre . "'");
	   }
	
	   $r = mysqli_query($db, "insert into " . MAIN_TABLE_NAME . " (titre, contenu) values ( '" . $titre . "', '" . $contenu . "')");
	   $db->close();
	
	   $_POST['titre'] = "";
	   $_POST['contenu'] = "";
   }
}


function getCurrentFileName()
{
	if (!isset($_SERVER['REQUEST_URI'])) {
		return "";
	}
	$uri = $_SERVER['REQUEST_URI'];
	$array = explode("/", $uri);
	if (!is_null($array))
	{
		if (count($array)>0)
		{
			$nb = count($array);
			return $array[$nb-1];
		}
	}
}


// Obtention du titre paramétré dans la table parameters champs name=title
$title = "";
$title2 = "";
$subtitle = "";

$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
if ($db->connect_errno) {
	$db->close();
    exit;
}

$r = mysqli_query($db, "select * from " . MAIN_TABLE_NAME . "_" . "parameters where name='title'");
if ($r->num_rows>0){
	$row = $r->fetch_assoc();
	$title = $row["value"];
} else {
	// si le paramètre title n'existe pas alors on le créée avec la valeur par défaut
	$s = mysqli_query($db, "insert into " . MAIN_TABLE_NAME . "_" . "parameters(name, value) values('title','" . DEFAULT_TITLE . "')");
}
$r = mysqli_query($db, "select * from " . MAIN_TABLE_NAME . "_" . "parameters where name='title2'");
if ($r->num_rows>0){
	$row = $r->fetch_assoc();
	$title2 = $row["value"];
} else {
		// si le paramètre title2 n'existe pas alors on le créée avec la valeur par défaut
	$s = mysqli_query($db, "insert into " . MAIN_TABLE_NAME . "_" . "parameters(name, value) values('title2','" . DEFAULT_TITLE2 . "')");
}
$r = mysqli_query($db, "select * from " . MAIN_TABLE_NAME . "_" . "parameters where name='subtitle'");
if ($r->num_rows>0){
	$row = $r->fetch_assoc();
	$subtitle = $row["value"];
} else {
	// si le paramètre subtitle n'existe pas alors on le créée avec la valeur par défaut
	$s = mysqli_query($db, "insert into " . MAIN_TABLE_NAME . "_" . "parameters(name, value) values('subtitle','" . DEFAULT_SUBTITLE . "')");
}

$db->close();


?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <title><?php echo ((trim($title)!='')?addslashes($title):addslashes(TITLE)); ?></title>
  <meta charset="ISO-8859-1"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>
  <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
  
  <!--<script src="//code.jquery.com/jquery-1.10.2.js"></script>-->
  <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>

</head>
<body>

<div class="container">
  <div class="jumbotron">
  	<center>
    <h3><?php echo ((trim($title)!='')?addslashes($title):addslashes(DEFAULT_TITLE)); ?><br/><?php echo ((trim($title2)!='')?addslashes($title2):addslashes(DEFAULT_TITLE2)); ?></h3>
    <p><?php echo ((trim($subtitle)!='')?addslashes($subtitle):addslashes(DEFAULT_SUBTITLE)); ?></p> 
    </center>
  </div>

<?php

$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
if ($db->connect_errno) {
	$db->close();
    exit;
}

$r = mysqli_query($db, "select * from " . MAIN_TABLE_NAME . " order by id desc");

if ($allowed == true){
	echo '<center>Il y a actuellement ' . $r->num_rows . ' articles enregistrés dans notre base.</center><br/>';
}

echo '<center>';

echo '<div id="contenu" style="background-color:#e4e5e5;width:50%;overflow:auto">';

if ($r->num_rows > 0) {
    while($row = $r->fetch_assoc()) {
    	echo '<br/>';
	    //echo '(' . $row["id"]. ') ' . '<a href="' . $row["url"] . '">' . $row["titre"] . '</a> - ' . $row["description"] . '<br/>';
		if ($allowed==true){
			echo 'Titre : ';
		}
	    echo '<b>' . $row["titre"] . '</b><br/>';
	    echo $row["contenu"] . '<br/>';
	    if ($allowed){
	    	echo ' - ' . $row["id"];
	    	echo ' <a href="?delete_article&id=' . $row["id"] . '">delete</a>';
	    	echo '<br/>';
	    }
	    echo '<br/>';
    }
} else {
    //echo "0 results";
}

echo '</div>';

echo '</center>';


//echo $site['url'];

$db->close();

?>

<br/><br/><br/>

<?php

if ($allowed == true){
	
	echo '<center><b>Ajoutez votre article :</b><br/><br/>';
	echo "<form action='" . $_POST['current_file_name'] . "' method='post'>";
	echo ' Titre de l\'article: <input type="text" id="titre" name="titre" value="" size=64><br/>';
	echo ' Contenu de l\'article: <input type="text" id="contenu" name="contenu" value="" size=64>';
	echo ' <input type="hidden" type="text" name="login" value="' . $_SESSION['login'] . '">';
	echo ' <input type="hidden" type="text" name="passwordhash" value="' . $_SESSION['passwordhash'] . '">';
	echo '<br/>';
	echo '  <input id="btnsubmitarticle" type="submit" value="Submit">';
	echo '</form></center>'; 
	echo '<br/><br/>';

}

if ($allowed == false){

	echo '<center><b>Authentification :</b><br/><br/>';
	echo "<form action='" . $_POST['current_file_name'] . "' method='post'>";
	echo ' Login: <input type="text" id="login" name="login" value="" size=16><br/>';
	echo ' Password: <input type="text" id="password" name="password" value="" size=16>';
	echo ' <input type="submit" value="Connect">';
	echo '</form></center>'; 
	echo '<br/><br/>';
	
}

$db = new mysqli(MYSQL_SERVER, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
if ($db->connect_errno) {
	$db->close();
    exit;
}
$r = mysqli_query($db, "select * from " . MAIN_TABLE_NAME . "_" . "user where trim(lower(username))='admin'");
if ($r->num_rows == 0) {
	//echo 'r num_rows = ' . $r->num_rows;
	echo '<center><b>First connection - Create admin account</b><br/><br/>';
	echo "<form action='" . $_POST['current_file_name'] . "' method='post'>";
	echo ' Set Admin Password: <input type="text" id="password" name="password" value="" size=16>';
	echo ' Confirm Admin Password: <input type="text" id="confirmpassword" name="confirmpassword" value="" size=16>';
	echo ' <input type="submit" value="Connect">';
	echo '</form></center>'; 
	echo '<br/><br/>';
}

$db->close();


?>


<div id="footer" style="background-color:#acacac;height:125px;width:100%;overflow:auto"></div>

<br/>

<?php
if ($allowed == true){
?>

<br/><br/><br/><br/><br/><br/>

<?php
}
?>


<script>

$( document ).ready(functiocn() {
	$( "a" ).click(function( event ) {
	  event.preventDefault();

		var href = $(this).attr('href');
		console.log(href);
		
		if (href.indexOf('delete_article') != -1)Il
		{
			var login="<?php echo $_POST["login"] ?>";
			var password="<?php echo $_POST["password"] ?>";
			
			console.log(login);
			console.log(password);
			
			$.post
			( 	"",
				{ adminfunction : href,
				  login : login,
				  password : password
				},
				function(data) 
				{
					console.log( data);
					$("#titre").val("");
					$("#contenu").val("");
					
					$("#btnsubmitarticle" ).click();
				}
			);
		}

	});
});



</script>

</body>
</html>

