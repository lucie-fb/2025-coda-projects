<?php


require_once 'inc/page.inc.php';
require_once 'inc/database.inc.php';

try {
    //On se connecte à la base de données
    $db = new DatabaseManager(
        dsn: 'mysql:host=mysql;dbname=lowify;charset=utf8mb4',
        username: 'lowify',
        password: 'lowifypassword'
    );
} catch (PDOException $ex) {
    echo "Erreur lors de la connexion à la base de données : " . $ex->getMessage();
    exit;
}

$albumId = (int)($_GET["id"] ?? 0);

$albumResult = [];

if (sizeof($albumResult) == 0){
    require_once 'error.php';
}



echo (new HTMLPage(title: "Album - Lowify"))
    ->setupNavigationTransition()
    ->addContent($html)
    ->render();