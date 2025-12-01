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

$artistResult= [];
$Artistsong = [];
$Artistalbum = [];

$artistId = (int)($_GET["id"] ?? 0);

//si l'artiste n'existe pas la page d'erreur s'ouvre
if (sizeof($artistResult) == 0){
    require_once 'error.php';
}

// c'est une opération dangereuse, donc on utilise try/catch
// et on affiche le message d'erreur si une erreur survient
try {
    // version multi-ligne
    $artistResult = $db->executeQuery(<<<SQL
    SELECT 
        id,
        name,
        biography,
        cover,
        monthly_listeners
    FROM artist
    WHERE id = ?
SQL, [$artistId]);

} catch (PDOException $ex) {
    echo "Erreur lors de la requête en base de données : " . $ex->getMessage();
    exit;
}
//on accède à la base de données pour récupérer les 5 titres les plus écoutés
try {
    // version multi-ligne
    $Artistsong = $db->executeQuery(<<<SQL
    SELECT 
        s.id as song_id,
        s.name as song_name, 
        s.duration as song_duration, 
        s.note as song_note, 
        a.cover as album_cover, 
        a.name as album_name
    FROM song s
    INNER JOIN album  a ON s.album_id = a.id
    WHERE s.artist_id = :artist_id
    ORDER BY s.note DESC
    LIMIT 5
SQL, [':artist_id' => $artistId]);

    // Affichage d'un message d'erreur si la connexion avec la bas de donnée à échouée
} catch (PDOException $ex) {
    echo "Erreur lors de la requête en base de données : " . $ex->getMessage();
    exit;
}

try {
    // version multi-ligne
    $Artistalbum = $db->executeQuery(<<<SQL
    SELECT 
        a.cover as album_cover, 
        a.name as album_name,
        a.release_date as album_release_date,
        a.id as album_id
    FROM album a
    WHERE a.artist_id = :artist_id
    ORDER BY release_date DESC
SQL, [':artist_id' => $artistId]);

    // Affichage d'un message d'erreur si la connexion avec la bas de donnée à échouée
} catch (PDOException $ex) {
    echo "Erreur lors de la requête en base de données : " . $ex->getMessage();
    exit;
}


// -- on crée une variable pour contenir le HTML qui représentera la liste des artistes
$artistHeaderHTML = "";
$topSongsHTML = "";
$albumsHTML = "";
$artistTitle = "Artiste non trouvé";

// -- pour chaque artiste récupéré depuis la base de données
foreach ($artistResult as $artistData) {
    // on pré-réserve des variables pour injecter le nom, l'id et la cover de l'artiste dans le HTML
    $artistTitle = $artistData['name'];
    $artistCover = $artistData['cover'];
    $artistBiography = $artistData['biography'];
    $artistMonthlyListeners = $artistData['monthly_listeners'];

    function conversion($artistMonthlyListeners)
    {
        if ($artistMonthlyListeners >= 100000) {
            $value = $artistMonthlyListeners / 1000000;
            $format = number_format($value, 1, '.', '');
            return $format . "M";
        }
        else if ($artistMonthlyListeners >= 1000) {
            $value = $artistMonthlyListeners / 1000;
            $format = number_format($value, 1, '.', '');
            return $format . "k";
        }
        else {
            return number_format($artistMonthlyListeners, 0);
        }
    }

    $formatlisteners = conversion($artistData['monthly_listeners']);

    // -- on ajoute les informations de l'artiste principal
    $artistHeaderHTML .= <<<HTML
        <div class="artist-header">
            <img src="$artistCover" class="artist-cover" alt="Couverture de l'artiste $artistTitle">
            <div class="artist-info">
                <h1 class="artist-name">$artistTitle</h1>
                <p class="listeners-count">Auditeurs mensuels : $formatlisteners</p>
                <p class="artist-biography">$artistBiography</p>
            </div>
        </div>
HTML;
}

// -- Construction du HTML pour les 5 meilleurs chansons
if (!empty($Artistsong)) {
    $topSongsHTML .= '<h2 class="section-title">Top 5 des chansons</h2>';
    $topSongsHTML .= '<div class="song-list">';
    foreach ($Artistsong as $index => $song) {
        // on pré-réserve des variables pour injecter le nom du son, la cover du son, la durée du son et le nom de l'album dans le HTML
        $songName = $song['song_name'];
        $songDuration = gmdate("i:s", $song['song_duration']);
        $songNote = round($song['song_note'], 1);
        $albumCover = $song['album_cover'];
        $albumName = $song['album_name'];
        $ranking = $index + 1;

        $topSongsHTML .= <<<HTML
            <div class="song-item">
                <span class="song-ranking">$ranking</span>
                <img src="$albumCover" class="song-album-cover" alt="Album de $songName">
                <div class="song-details">
                    <p class="song-name">$songName</p>
                    <p class="album-name">$albumName</p>
                </div>
                <span class="song-duration">$songDuration</span>
                <span class="song-note">⭐ $songNote/5</span>
            </div>
HTML;
    }
    $topSongsHTML .= '</div>';
}

// -- Construction du HTML pour les albums
if (!empty($Artistalbum)) {
    $albumsHTML .= '<h2 class="section-title">Albums</h2>';
    $albumsHTML .= '<div class="album-grid">';
    foreach ($Artistalbum as $album) {
        // on pré-réserve des variables pour injecter le nom et la cover de l'album dans le HTML
        $albumCover = $album['album_cover'];
        $albumName = $album['album_name'];
        $albumReleaseDate = $album['album_release_date'];
        $albumId = $album['album_id'];

        $albumsHTML .= <<<HTML
            <a href="album.php?id=$albumId" class="album-card">
                <img src="$albumCover" class="album-cover" alt="Album $albumName">
                <p class="album-name">$albumName</p>
                <p>$albumReleaseDate</p>
            </a>
HTML;
    }
    $albumsHTML .= '</div>';
}


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

    /* --- ARTIST HEADER --- */
    .artist-header {
        display: flex;
        align-items: flex-end;
        gap: 30px;
        padding: 40px 0;
        background: linear-gradient(to bottom, #404040, var(--background-dark));
        margin-bottom: 30px;
        border-radius: 8px;
    }

    .artist-cover {
        width: 250px;
        height: 250px;
        object-fit: cover;
        border-radius: 50%;
        box-shadow: 0 4px 60px rgba(0, 0, 0, 0.5);
        margin-left: 20px;
    }

    .artist-info {
        padding-bottom: 20px;
    }

    .artist-name {
        font-size: 6em;
        margin: 0;
        font-weight: 900;
        line-height: 1;
    }

    .listeners-count {
        color: var(--text-light);
        font-size: 1.1em;
        margin-top: 10px;
    }

    .artist-biography {
        font-size: 1em;
        color: var(--text-light);
        max-width: 800px;
        margin-top: 15px;
    }
    
    .section-title {
        font-size: 2em;
        margin: 40px 0 20px 0;
        font-weight: 700;
        border-bottom: 2px solid var(--card-bg);
        padding-bottom: 10px;
    }

    /* --- TOP SONGS LIST --- */
    .song-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .song-item {
        display: flex;
        align-items: center;
        padding: 10px;
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
        color: var(--text-light);
    }

    .song-album-cover {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        margin: 0 15px;
    }

    .song-details {
        flex-grow: 1;
    }

    .song-name {
        margin: 0;
        font-weight: bold;
    }

    .album-name {
        margin: 0;
        font-size: 0.9em;
        color: var(--text-light);
    }

    .song-duration, .song-note {
        margin-left: 20px;
        color: var(--text-light);
        font-size: 0.9em;
    }
    
    .song-note {
        font-weight: bold;
        color: var(--primary-color);
    }

    /* --- ALBUM GRID --- */
    .album-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 20px;
        padding-bottom: 50px;
    }

    .album-card {
        display: flex;
        flex-direction: column;
        text-decoration: none;
        color: var(--text-white);
        background-color: var(--card-bg);
        padding: 15px;
        border-radius: 8px;
        transition: background-color 0.2s, transform 0.2s;
    }

    .album-card:hover {
        background-color: var(--hover-bg);
        transform: translateY(-5px);
    }

    .album-cover {
        width: 100%;
        height: auto;
        border-radius: 4px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        margin-bottom: 10px;
    }

    .album-name {
        margin: 0;
        font-weight: bold;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
<div class="container">
    <a href="artists.php" class="back-link"> < Retour à l'accueil</a>

    {$artistHeaderHTML}
    
    {$topSongsHTML}
    
    {$albumsHTML}
</div>
HTML;

echo (new HTMLPage(title: "Artist - Lowify"))
    ->setupNavigationTransition()
    ->addContent($html)
    ->render();