<?php

// -- importation des librairies à l'aide de require_once
require_once 'inc/page.inc.php';
require_once 'inc/database.inc.php';

// -- initialisation de la connexion à la base de données
try {
    $db = new DatabaseManager(
        dsn: 'mysql:host=mysql;dbname=lowify;charset=utf8mb4',
        username: 'lowify',
        password: 'lowifypassword'
    );
} catch (PDOException $ex) {
    echo "Erreur lors de la connexion à la base de données : " . $ex->getMessage();
    exit;
}

// -- on récupère les infos de tout les artistes depuis la base de données
$allArtists = [];

try {
    // version multi-ligne
    $allArtists = $db->executeQuery(<<<SQL
    SELECT 
        id,
        name,
        cover
    FROM artist
SQL);

} catch (PDOException $ex) {
    echo "Erreur lors de la requête en base de données : " . $ex->getMessage();
    exit;
}

// -- on crée une variable pour contenir le HTML qui représentera la liste des artistes
$artistsAsHTML = "";

// -- pour chaque artiste récupéré depuis la base de donnée
foreach ($allArtists as $artist) {
    // on pré-réserve des variables pour injecter le nom, l'id et la cover de l'artiste dans le HTML
    $artistName = $artist['name'];
    $artistId = $artist['id'];
    $artistCover = $artist['cover'];

    // -- on ajoute une carte HTML représentant l'artiste courant
    $artistsAsHTML .= <<<HTML
        <a href="artist.php?id=$artistId" class="artist-card-link">
            <div class="artist-card">
                <img src="$artistCover" class="artist-cover" alt="Image de l'artiste $artistName">
                <div class="artist-name-container">
                    <h5 class="artist-name">$artistName</h5>
                    <p class="artist-type">Artiste</p>
                </div>
            </div>
        </a>
HTML;
}

// -- on crée la structure HTML de notre page
// en injectant le HTML correspondant à la liste des artistes
$html = <<<HTML
<style>
    :root {
        --primary-color: #1DB954; /* Vert Spotify */
        --background-dark: #121212;
        --card-bg: #181818;
        --text-white: #FFFFFF;
        --text-light: #B3B3B3;
        --hover-bg: #282828;
    }

    body {
        background-color: var(--background-dark);
        color: var(--text-white);
        font-family: 'Helvetica Neue', Arial, sans-serif;
        margin: 0;
        padding: 0;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .back-link {
        color: var(--text-light);
        text-decoration: none;
        margin-bottom: 20px;
        display: inline-block;
        transition: color 0.2s;
    }
    .back-link:hover {
        color: var(--text-white);
    }
    
    .page-title {
        font-size: 3em;
        margin: 20px 0 30px 0;
        font-weight: 700;
    }

    /* --- ARTIST GRID --- */
    .artist-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 25px;
        padding-bottom: 50px;
        margin-top: 20px;
    }
    
    .artist-card-link {
        text-decoration: none;
        color: inherit;
    }

    .artist-card {
        display: flex;
        flex-direction: column;
        align-items: center; 
        background-color: var(--card-bg);
        padding: 20px;
        border-radius: 8px;
        transition: background-color 0.2s, transform 0.2s;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.5);
        
        /* AJOUT CLÉ 1: Alignement du contenu de la carte à gauche */
        text-align: left; 
        height: 100%;
    }

    .artist-card:hover {
        background-color: var(--hover-bg);
        transform: translateY(-5px);
    }

    .artist-cover {
        width: 150px;
        height: 150px;
        object-fit: cover;
        border-radius: 50%;
        margin-bottom: 15px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.8);
    }

    .artist-name-container {
        /* AJOUT CLÉ 2: Assure que le conteneur de texte prend toute la largeur */
        width: 100%; 
        padding-left: 5px; /* Petite marge pour le style */
    }

    .artist-name {
        margin: 0;
        font-weight: bold;
        font-size: 1.2em;
        color: var(--text-white);
        
        /* Propriétés pour éviter le débordement (déjà bonnes) */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        /* max-width: 100% est maintenant relatif à .artist-name-container (100% de la carte) */
        max-width: 100%;
    }
    
    .artist-type {
        margin: 5px 0 0 0;
        color: var(--text-light);
        font-size: 0.9em;
    }

</style>
<div class="container">
    <a href="index.php" class="back-link"> < Retour à l'accueil</a>

    <h1 class="page-title">Artistes</h1>
    
    <div class="artist-grid">
    {$artistsAsHTML}
    </div>
</div>
HTML;

// -- on génère et on affiche la page
echo (new HTMLPage(title: "Artistes - Lowify"))
    ->setupNavigationTransition()
    ->addContent($html)
    ->render();