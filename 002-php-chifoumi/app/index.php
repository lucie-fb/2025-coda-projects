<?php

$userChoice = $_GET["player"] ?? "Pas choisi";
$whoWon = "...";
$array = ["Pierre", "Feuille", "Ciseaux"];



$html = <<<HTML
<!DOCTYPE html>
<head>
<meta charset='utf-8'>
</head>
<header>
<h1> Jeu Pierre Feuille Ciseaux </h1>
</header>
<body>
<div>
    <section>
        <h2>Vous</h2>
        <p>$userChoice</p>
    </section>
    <section>
        <h2>VS</h2>
    </section>
    <section>
        <h2>Php</h2>
        <p>$phpChoice</p>
    </section>
</div>
<div>
<section>
    <a href="http://localhost:80/?player=pierre">Pierre</a>
    <a href="http://localhost:80/?player=feuille">Feuille</a>
    <a href="http://localhost:80/?player=ciseaux">Ciseaux</a>
</section>
</div>
<div>
    <section>
        <a href="http://localhost:80/?paschoisi=reinitialiser">Réinitialiser</a>
    </section>
</div>
</body>
HTML;
echo $html;