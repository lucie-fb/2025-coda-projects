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

$messageerror = $_GET["id"] ?? "Oups, cette page n'existe pas !";

$html = <<<HTML
<div class="container">
    <a href="artists.php" class="back-link"> < Retour à l'accueil</a>
    <h1>$messageerror</h1>
</div>
HTML;

echo (new HTMLPage(title: "Artist - Lowify"))
    ->setupNavigationTransition()
    ->addContent($html)
    ->render();