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

// Définition de la fonction de conversion ---
function conversion($listeners)
{
    if ($listeners >= 1000000) {
        $value = $listeners / 1000000;
        return number_format($value, 1, '.', '') . " M";
    }
    else if ($listeners >= 1000) {
        $value = $listeners / 1000;
        return number_format($value, 1, '.', '') . " k";
    }
    else {
        return number_format($listeners, 0);
    }
}

$albumId = (int)($_GET["id"] ?? 0);
$allArtists = [];
$topAlbums = []; // Variable pour le Top Albums (trié par date de sortie)
$topAlbumsBySongNote = []; // NOUVEAU: Variable pour le Top Albums (trié par note moyenne)
$albumHeaderHTML = ""; // Contenu pour l'Album du moment

try {
    // Récupération du Top 5 des artistes (pour la grille 'Top trending')
    $allArtists = $db->executeQuery(<<<SQL
    SELECT 
        id,
        name,
        cover,
        monthly_listeners
    FROM artist
    ORDER BY monthly_listeners DESC
    LIMIT 5
SQL);

    // Récupération du Top 5 des albums par date (pour la grille 'Top albums')
    $topAlbums = $db->executeQuery(<<<SQL
        SELECT 
            a.id,
            a.name,
            a.cover,
            a.release_date,
            ar.name AS artist_name
        FROM album a
        INNER JOIN artist ar ON a.artist_id = ar.id 
        ORDER BY a.release_date DESC 
        LIMIT 5
    SQL);

    // Récupération des 5 albums les mieux notés via la moyenne des chansons (pour 'Top sorties')
    $topAlbumsBySongNote = $db->executeQuery(<<<SQL
        SELECT 
            a.id,
            a.name,
            a.cover,
            a.release_date,
            ar.name AS artist_name,
            (SELECT AVG(s.note) FROM song s WHERE s.album_id = a.id) AS average_note
        FROM album a
        INNER JOIN artist ar ON a.artist_id = ar.id 
        ORDER BY average_note DESC 
        LIMIT 5
    SQL);


    // Récupération des détails d'un Album spécifique si un ID est fourni
    if ($albumId !== 0) {
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

        // Construction du bandeau de l'album si les données existent
        if (sizeof($albumData) > 0) {
            $album = $albumData[0]; // On prend le premier (et unique) résultat

            $albumTitle = $album['album_name'];
            $artistName = $album['artist_name'];
            $artistId = $album['artist_id'];
            $albumCover = $album['album_cover'];
            $releaseDate = $album['album_release_date']; // La date est maintenant disponible

            $albumHeaderHTML = <<<HTML
            <div class="album-header">
                <img src="{$albumCover}" alt="Pochette de l'album {$albumTitle}" class="album-cover-display">
                <div class="album-info-display">
                    <p class="type-label">ALBUM</p>
                    <h1 class="album-name-lg">{$albumTitle}</h1>
                    <p class="artist-link">Artiste: <a href="artist.php?id={$artistId}">{$artistName}</a></p>
                    <p class="release-date">Date de sortie: {$releaseDate}</p>
                </div>
            </div>
            HTML;
        } else {
            $albumHeaderHTML = "<p>Aucun album trouvé pour cet ID.</p>";
        }
    } else {
        $albumHeaderHTML = ""; // Laisse vide si aucun ID n'est fourni
    }

} catch (PDOException $ex) {
    echo "Erreur lors de la requête en base de données : " . $ex->getMessage();
    exit;
}


// Construction du HTML de la liste des artistes ('Top trending')
$artistsAsHTML = "";

if (!empty($allArtists)) {
    foreach ($allArtists as $artistData) {
        $artistName = $artistData['name'];
        $artistCover = $artistData['cover'];
        $artistId = $artistData['id'];
        $artistMonthlyListeners = $artistData['monthly_listeners'];
        $formatListeners = conversion($artistMonthlyListeners);

        $artistsAsHTML .= <<<HTML
            <a href="artist.php?id={$artistId}" class="album-card"> 
                <img src="$artistCover" class="album-cover" alt="Photo de $artistName">
                <p class="album-name">$artistName</p>
                <p class="listeners-count-small">$formatListeners auditeurs</p>
            </a>
HTML;
    }
}

//  Construction du HTML de la liste des top albums par date ('Top albums')
$albumsAsHTML = "";

if (!empty($topAlbums)) {
    foreach ($topAlbums as $albumData) {
        $albumId = $albumData['id'];
        $albumName = $albumData['name'];
        $albumCover = $albumData['cover'];
        $artistName = $albumData['artist_name'];
        $releaseDate = $albumData['release_date'];

        // Utilisation de .album-card, avec un style temporaire pour le carré
        $albumsAsHTML .= <<<HTML
            <a href="album.php?id={$albumId}" class="album-card"> 
                <img src="$albumCover" class="album-cover album-cover-squared" alt="Pochette de $albumName">
                <p class="album-name">$albumName</p>
                <p class="listeners-count-small">$artistName</p>
                <p class="listeners-count-small">Sortie: $releaseDate</p> </a>
HTML;
    }
}


//  Construction du HTML de la liste des top albums par note moyenne ('Top sorties')
$albumsnotesAsHTML = "";

if (!empty($topAlbumsBySongNote)) {
    foreach ($topAlbumsBySongNote as $albumData) {
        $albumId = $albumData['id'];
        $albumName = $albumData['name'];
        $albumCover = $albumData['cover'];
        $artistName = $albumData['artist_name'];
        $releaseDate = $albumData['release_date'];

        // Extraction et formatage de la note moyenne
        $averageNote = round($albumData['average_note'], 1);

        $albumsnotesAsHTML .= <<<HTML
            <a href="album.php?id={$albumId}" class="album-card"> 
                <img src="$albumCover" class="album-cover album-cover-squared" alt="Pochette de $albumName">
                <p class="album-name">$albumName</p>
                <p class="listeners-count-small">$artistName</p>
                <span class="album-note">⭐ $averageNote/5</span>
            </a>
HTML;
    }
}

// Construction du HTML Final
$html = <<<HTML
 <style>
        /* 🎨 Variables de Thème (Thème Sombre Moderne) */
        :root {
            --dark-bg: #1A1A1A; 
            --card-bg: #212121;
            --primary-text: #FFFFFF;
            --secondary-text: #B0B0B0;
            --accent-color: #00ADB5; 
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
        
        /* --- TITRES DE SECTION --- */
        .page-title {
            font-size: 3em;
            margin-top: 0;
            font-weight: 900;
        }
        
        .section-title {
            font-size: 1.8em;
            margin: 30px 0 20px 0;
            font-weight: 700;
            color: var(--primary-text);
            border-bottom: 2px solid var(--card-bg);
            padding-bottom: 10px;
        }

        /* --- GRILLE ARTISTES/ALBUMS --- */
        .album-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 25px;
            padding-bottom: 50px;
        }

        .album-card {
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: var(--primary-text);
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 10px;
            transition: background-color 0.2s, transform 0.2s;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .album-card:hover {
            background-color: var(--hover-bg);
            transform: translateY(-5px); 
        }
        
        /* Style général pour les images de carte (rond par défaut pour les artistes) */
        .album-cover {
            width: 100%;
            height: auto;
            border-radius: 50%; 
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            margin-bottom: 15px;
        }
        
        /* Surcharge pour rendre les pochettes d'albums carrées */
        .album-cover-squared {
            border-radius: 4px;
        }


        .album-name {
            margin: 5px 0;
            font-weight: bold;
            font-size: 1.1em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis; 
            text-align: center;
        }
        
        .listeners-count-small {
            margin: 0;
            font-size: 0.9em;
            color: var(--secondary-text);
            text-align: center;
        }
        
        /* NOUVEAU: Style pour la note de l'album */
        .album-note {
            display: block;
            margin: 5px 0;
            font-size: 1em;
            font-weight: bold;
            color: gold; 
            text-align: center;
        }
        
        /* Styles pour l'affichage de l'album (bandeau) */
        .album-header {
            display: flex;
            gap: 20px;
            align-items: center;
            padding: 20px;
            background-color: var(--card-bg);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .album-cover-display {
            width: 80px;
            height: 80px;
            border-radius: 4px;
        }
        .album-name-lg {
            font-size: 1.5em;
            margin: 0;
        }
        .artist-link, .release-date {
            font-size: 0.9em;
            color: var(--secondary-text);
        }
    </style>
<div class="container">
    <h1 class="page-title">Accueil</h1>
    
    <h2 class="section-title">Top trending</h2>
    <div class="album-grid"> {$artistsAsHTML} </div>
    
    <h2 class="section-title">Top albums</h2>
    <div class="album-grid"> {$albumsAsHTML} </div>
    
    <h2 class="section-title">Top sorties</h2>
    <div class="album-grid"> {$albumsnotesAsHTML} </div>
</div>
HTML;


// On utilise le style et le contenu HTML
echo (new HTMLPage(title: "Accueil - Lowify"))
    ->setupNavigationTransition()
    ->addContent($html)
    ->render();
