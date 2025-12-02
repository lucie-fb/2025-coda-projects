<?php

require_once 'inc/page.inc.php';
require_once 'inc/database.inc.php';

try {
    // Connexion à la base de données
    $db = new DatabaseManager(
        dsn: 'mysql:host=mysql;dbname=lowify;charset=utf8mb4',
        username: 'lowify',
        password: 'lowifypassword'
    );
} catch (PDOException $ex) {
    echo "Erreur lors de la connexion à la base de données : " . $ex->getMessage();
    exit;
}

// Récupération de l'ID de l'album depuis l'URL (le paramètre doit s'appeler 'id')
$albumId = (int)($_GET["id"] ?? 0);

// Vérification de l'ID
if ($albumId === 0) {
    require_once 'error.php';
    exit;
}

$albumData = [];
$songResults = [];

try {
    // Récupérer les détails de l'album et de son artiste
    $albumData = $db->executeQuery(<<<SQL
        SELECT 
            a.id AS album_id,
            a.name AS album_name,
            a.cover AS album_cover,
            a.release_date AS album_release_date,
            ar.id AS artist_id,
            ar.name AS artist_name
        FROM album a
        INNER JOIN artist ar ON a.artist_id = ar.id 
        WHERE a.id = :album_id
    SQL, [':album_id' => $albumId]);

    // Vérifier si l'album existe
    if (sizeof($albumData) === 0) {
        require_once 'error.php';
        exit;
    }

    // Récupérer la liste des chansons appartenant à cet album
    $songResults = $db->executeQuery(<<<SQL
        SELECT 
            s.id AS song_id,
            s.name AS song_name, 
            s.duration AS song_duration, 
            s.note AS song_note
        FROM song s
        WHERE s.album_id = :album_id 
        ORDER BY s.note DESC
    SQL, [':album_id' => $albumId]);

} catch (PDOException $ex) {
    echo "Erreur lors de la requête en base de données : " . $ex->getMessage();
    exit;
}

// Extraction des données de l'album pour un accès facile
$album = $albumData[0];

// Variables pour le rendu HTML
$albumTitle = $album['album_name'];
$artistName = $album['artist_name'];
$artistId = $album['artist_id'];
$albumCover = $album['album_cover'];

// --- Construction de l'en-tête de l'album
$albumHeaderHTML = <<<HTML
<div class="album-header">
    <img src="{$albumCover}" alt="Pochette de l'album {$albumTitle}" class="album-cover-display">
    <div class="album-info-display">
        <p class="type-label">ALBUM</p>
        <h1 class="album-name-lg">{$albumTitle}</h1>
        <p class="artist-link">Artiste: <a href="artist.php?id={$artistId}">{$artistName}</a></p>
        <p class="release-date">Date de sortie: {$album['album_release_date']}</p>
    </div>
</div>
HTML;


// --- Construction de la liste des chansons
$SongsHTML = "";
foreach ($songResults as $index => $song) {
    $songName = $song['song_name'];
    $songNote = $song['song_note'];
    // Formatage de la durée (suppose que c'est en secondes)
    $songDuration = gmdate("i:s", $song['song_duration']);
    $ranking = $index + 1;

    $SongsHTML .= <<<HTML
            <div class="song-item">
                <span class="song-ranking">$ranking</span>   
                <div class="song-details">
                    <p class="song-name">$songName</p>
                </div>
                <span class="song-duration">$songDuration</span>
            </div>
HTML;
}

// --- Construction du HTML final 
$html = <<<HTML
<style>
    /* 🎨 Variables de Thème (Thème Sombre Moderne) */
    :root {
        --dark-bg: #1A1A1A; 
        --light-bg: #2C2C2C; 
        --card-bg: #212121;
        --primary-text: #FFFFFF;
        --secondary-text: #B0B0B0;
        --accent-color: #00ADB5; /* Turquoise Moderne */
        --hover-bg: #3A3A3A;
    }

    body {
        background-color: var(--dark-bg);
        color: var(--primary-text);
        font-family: 'Helvetica Neue', Arial, sans-serif;
        margin: 0;
        padding: 0;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    /* --- LIEN DE RETOUR --- */
    .back-link {
        color: var(--secondary-text);
        text-decoration: none;
        margin-bottom: 25px;
        display: inline-block;
        transition: color 0.2s;
    }
    .back-link:hover {
        color: var(--accent-color);
    }


    /* --- EN-TÊTE DE L'ALBUM (Adapter le style simple) --- */
    .album-header {
        background: linear-gradient(to bottom, var(--light-bg), var(--dark-bg));
        display: flex;
        align-items: flex-end; 
        padding: 40px 30px; 
        gap: 30px;
        color: var(--primary-text);
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3); 
        margin-bottom: 30px;
    }

    .album-cover-display {
        width: 200px;
        height: 200px;
        object-fit: cover;
        border-radius: 6px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
    }

    .album-info-display {
        padding-bottom: 10px;
    }
    
    .type-label {
        font-size: 0.9em;
        color: var(--secondary-text);
        margin: 0 0 5px 0;
        font-weight: 500;
    }

    .album-name-lg {
        font-size: 3.5em;
        font-weight: 800;
        margin: 0 0 10px 0;
        line-height: 1.1;
    }

    .artist-link, .release-date {
        font-size: 1.1em;
        color: var(--secondary-text);
        margin-bottom: 5px;
    }
    
    .artist-link a {
        color: var(--primary-text);
        text-decoration: none;
        font-weight: 600;
    }
    .artist-link a:hover {
        color: var(--accent-color);
    }
    
    .section-title {
        font-size: 1.8em;
        margin: 30px 0 15px 0;
        font-weight: 700;
        border-bottom: 2px solid var(--card-bg);
        padding-bottom: 10px;
    }
    

    /* --- LISTE DES CHANSONS --- */
    .song-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .song-item {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        background-color: var(--card-bg);
        border-radius: 8px;
        transition: background-color 0.2s;
        cursor: pointer;
    }
    .song-item:hover {
        background-color: var(--hover-bg);
    }
    
    .song-ranking {
        width: 30px;
        text-align: center;
        font-weight: bold;
        color: var(--secondary-text);
    }

    .song-details {
        flex-grow: 1;
        margin-left: 15px;
    }

    .song-name {
        margin: 0;
        font-weight: bold;
        font-size: 1em;
    }

    .song-duration {
        /* Pousse à droite */
        margin-left: auto;
        color: var(--secondary-text);
        font-size: 0.9em;
        width: 40px; 
        text-align: right;
    }
    
    .song-note {
        /* Affiche la note avant la durée */
        margin-right: 25px;
        font-weight: bold;
        color: var(--accent-color);
        font-size: 1em;
    }
</style>
<div class="container">
    <a href="artists.php" class="back-link">&larr; Retour à l'accueil</a>

    {$albumHeaderHTML}
    
    <h2 class="section-title">Chansons de l'album</h2>
    
    <div class="song-list">
        {$SongsHTML}
    </div>
</div>
HTML;


echo (new HTMLPage(title: "{$albumTitle} - Lowify"))
    ->setupNavigationTransition()
    ->addContent($html)
    ->render();
