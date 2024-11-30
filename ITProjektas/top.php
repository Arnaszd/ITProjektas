<?php
include('session.php');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "itprojektas";

$user_role = null;
$login_user = $_SESSION['login_user'] ?? null;

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Negalima pasiekti duomenų bazės: " . $conn->connect_error);
}

// Fetch top 10 landlords with average ratings
$landlords_query = "
    SELECT 
        owner.username AS landlord_username,
        ROUND(AVG(feedback.rating), 2) AS avg_rating
    FROM 
        feedback
    JOIN 
        prisijungimas AS owner ON feedback.owner_id = owner.id
    GROUP BY 
        feedback.owner_id
    ORDER BY 
        avg_rating DESC
    LIMIT 10;
";
$landlords_result = $conn->query($landlords_query);

// Fetch top 10 users with score calculation
$users_query = "
    SELECT 
        reviewer.username AS user_username,
        SUM(items.price * feedback.rating) AS total_score
    FROM 
        feedback
    JOIN 
        prisijungimas AS reviewer ON feedback.user_id = reviewer.id
    JOIN 
        items ON feedback.owner_id = items.created_by
    GROUP BY 
        feedback.user_id
    ORDER BY 
        total_score DESC
    LIMIT 10;
";
$users_result = $conn->query($users_query);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TOP 10 Nuomotojų ir Vartotojų</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
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
            <li class="nav-item">
                <a class="nav-link" href="top.php">Geriausi</a>
            </li>
            <?php if ($user_role === "admin"): ?>
                <li class="nav-item">
                    <a class="nav-link" href="control.php">Kontrolierius</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<div class="container mt-5">
    <h2 class="text-center mb-4">TOP 10 Nuomotojų</h2>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Nuomotojo Vardas</th>
                <th>Vidutinis Įvertinimas</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($landlords_result && $landlords_result->num_rows > 0) {
                while ($row = $landlords_result->fetch_assoc()) {
                    echo "<tr>
                        <td>" . htmlspecialchars($row['landlord_username']) . "</td>
                        <td>" . htmlspecialchars($row['avg_rating']) . "</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='2' class='text-center'>Nėra duomenų</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <h2 class="text-center mt-5 mb-4">TOP 10 Vartotojų</h2>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Vartotojo Vardas</th>
                <th>Bendras Taškų Skaičius</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($users_result && $users_result->num_rows > 0) {
                while ($row = $users_result->fetch_assoc()) {
                    echo "<tr>
                        <td>" . htmlspecialchars($row['user_username']) . "</td>
                        <td>" . htmlspecialchars($row['total_score']) . "</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='2' class='text-center'>Nėra duomenų</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>
</body>
</html>
