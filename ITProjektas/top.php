<?php
include('session.php');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "itprojektas";


$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Negalima pasiekti duomenų bazės: " . $conn->connect_error);
}

$user_role = null;
$login_user = $_SESSION['login_user'] ?? null;

if ($login_user) {
    $stmt = $conn->prepare("SELECT pareiga FROM prisijungimas WHERE username = ?");
    $stmt->bind_param("s", $login_user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $user_role = $row['pareiga'];
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagrindinis puslapis</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="textsheet.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="index.php">ITProjektas</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarText" aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarText">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item active">
                <a class="nav-link" href="index.php">Pagrindinis puslapis <span class="sr-only">(current)</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="canoes.php">Baidarių nuoma</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="vehicles.php">Mašinų nuoma</a>
            </li>
            <?php if ($user_role === "nuom" || $user_role === "admin"): ?>
                <li class="nav-item">
                    <a class="nav-link" href="create.php">Pateikti skelbimą</a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="reservations.php">Peržiūrėti rezervacijas / Pateikti atsiliepimą</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="feedback.php">Nuomuotojų atsiliepimai</a>
            </li>
            <?php if ($user_role === "admin"): ?>
                <li class="nav-item">
                    <a class="nav-link" href="control.php">Kontrolierius</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
</body>
</html>
