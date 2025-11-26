<?php
function generateOptions(int $maxTaille = 42){
    $options = "";
    for($i = 8; $i <= $maxTaille; $i++){
        $options .= "<option value=\"$i\">$i</option>\n";
    }
    return $options;
}

function takeRandom(string $subject): string {
    $index = random_int(0, strlen($subject) - 1);
    $randomChar = $subject[$index];

    return $randomChar;
}
function generatePassword(
    int $size,
    bool $useAlphaMin,
    bool $useAlphaMaj,
    bool $useNum,
    bool $useSymbols
): string{
    $password = "";
    // on déclare les chaines possibles pour chaque cas
    $mins = "abcdefghijklmnopqrstuvwxyz";
    $maxs = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $numbs = "0123456789";
    $symbols = "!@#$%^&*";
    $chars = "";
    // si un case est coché, on ajoute la chaine correspondante à $chars
    if ($useAlphaMin) $chars .= $mins;
    // pour chaque case coché, on force AU MOINS UN caractère de ce type
    if ($useAlphaMin) $password .= takeRandom($mins);;
    // comme on a déjà mis des caractères dans notre mot de passe
    // si on boucle sur $size en tant que limite, on va avoir une chaine
    // de taille $size + le nombre de types sélectionnés
    // donc on calcule le nombre de caractères restant à ajouter
    // strlen = taille de la chaine
    $loopLimit = $size - strlen($password);

    if ($useAlphaMin) $chars .= $mins;
    if ($useAlphaMaj) $chars .= $maxs;
    if ($useNum) $chars .= $numbs;
    if ($useSymbols) $chars .= $symbols;

    $loopLimit = $size - strlen($password);

    for ($i = 0; $i < $size; $i++) {
        $password .= takeRandom($chars);
    }
    return $password;
}

$generator = "";
$size = 12;
$useAlphaMin = true;
$useAlphaMaj = true;
$useNum = true;
$useSymbols = true;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $size = $_POST['size'] ?? 12;
        $useAlphaMin = $_POST['use-alpha-min'] ?? 0;
        $useAlphaMaj = $_POST['use-alpha-maj'] ?? 0;
        $useNum = $_POST['use-num'] ?? 0;
        $useSymbols = $_POST['use-symbols'] ?? 0;

        $generated = generatePassword($size, $useAlphaMin, $useAlphaMaj, $useNum, $useSymbols);
    } else {
        $useAlphaMin = 1;
        $useAlphaMaj = 1;
        $useNum = 1;
        $useSymbols = 1;
    }

    $options = generateOptions();

    $userAlphaminchecked = $useAlphaMin ? "checked" : "";
    $userAlphamaxchecked = $useAlphaMaj ? "checked" : "";
    $usernumchecked = $useNum ? "checked" : "";
    $usersymbols = $useSymbols ? "checked" : "";

$html = <<< HTML
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Générateur de mots de passe</title>
</head>
<style>
    h1{
        text-align: center;
    }
    p{
        border : 1px solid black;
        border-radius: 10px;
        font-size: 20px;
        padding: 10px;
        text-align: center;
    }
    button:hover{
        background-color : purple;
        color : white;
    }
</style>
<body>
    <h1>Générateur de mots de passe</h1>
    <section>
        <p>Mot de passe généré : <strong>$generated</strong></p>
    </section>
    <form method="post" action="">
    <div>
        <label for="size" class="form-label">Taille du mot de passe</label>
        <select class="form-select" aria-label="Default select example" name="size">
            $options
        </select>
    </div>
    <span>
        <input type="checkbox" value="1" id="use-alpha-min" name="use-alpha-min" $userAlphaminchecked>
        <label for="use-alpha-min">Utiliser les lettres minuscules (a-z)</label><br>
        <input type="checkbox" value="1" id="use-alpha-maj" name="use-alpha-maj" $userAlphamaxchecked>
        <label for="use-alpha-maj">Utiliser les lettres majuscules (A-Z)</label><br>
        <input type="checkbox" value="1" id="use-num" name="use-num" $usernumchecked>
        <label for="use-num">Utiliser les chiffres (0-9)</label><br>
        <input type="checkbox" value="1" id="use-symbols" name="use-symbols" $usersymbols>
        <label for="use-symbols">Utiliser les symboles (!@#$%^&*())</label><br>
    </span>
    <section><br>
        <button type="submit">Générer un mot de passe</button>
    </section>
    </form>
</body>
HTML;

echo $html;