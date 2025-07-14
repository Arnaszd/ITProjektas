<?php

session_start();


if (!isset($_SESSION['login_user'])) {
    die("Pirmiausia prisijunkite");
}


$logged_in_user = $_SESSION['login_user'];


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "itprojektas";


$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_SESSION['login_user'])) {
    $login_user = $_SESSION['login_user'];
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

// Gauti prisijungusio ID ir pareiga
$user_id = null;
$user_role = null;
if (isset($_SESSION['login_user'])) {
    $stmt = $conn->prepare("SELECT id, pareiga FROM prisijungimas WHERE username = ?");
    $stmt->bind_param("s", $logged_in_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $user_id = $row['id'];
        $user_role = $row['pareiga'];
    }
    $stmt->close();
}

// Paimti visus atsiliepimus
$sql = "SELECT 
            feedback.id AS feedback_id,
            reviewer.username AS reviewer_username,
            owner.username AS owner_username,
            feedback.rating,
            feedback.feedback,
            feedback.feedback_date
        FROM 
            feedback
        JOIN 
            prisijungimas AS reviewer ON feedback.user_id = reviewer.id
        JOIN 
            prisijungimas AS owner ON feedback.owner_id = owner.id
        ORDER BY 
            feedback.feedback_date DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visi Atsiliepimai</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
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
        <a class="nav-link" href="index.php">Pagrindinis puslapis</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="canoes.php">Baidarių nuoma</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="vehicles.php">Mašinų nuoma</a>
      </li>
      <?php if ($user_role === "nuom" && $user_role === "admin"): ?>
        <li class="nav-item">
      <li class="nav-item">
        <a class="nav-link" href="create.php">Pateikti skelbimą</a>
      </li>
      <?php endif; ?>
      <li class="nav-item">
        <a class="nav-link" href="reservations.php">Peržiūrėti rezervacijas / Pateikti atsiliepimą</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="feedback.php">Nuomuotojų atsiliepimai <span class="sr-only">(current)</span> </a>
      </li>
      <!-- Leidžia prieigą prie kontrolieriaus jeigu esi administratorius -->
      <?php if ($user_role === "admin"): ?>
      <li class="nav-item">
        <a class="nav-link" href="control.php">Kontrolierius</a>
      </li>
      <?php endif; ?>
    </ul>
  </div>
</nav>

<div class="container mt-5">
    <h2 class="text-center mb-4">Visi Atsiliepimai</h2>

    <!-- Feedback Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Atsiliepimo nr.</th>
                <th>Kas parašė atsiliepimą</th>
                <th>Kam skirtas atsiliepimas</th>
                <th>Įvertinimas</th>
                <th>Atsiliepimas</th>
                <th>Atsiliepimo data</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>" . htmlspecialchars($row['feedback_id']) . "</td>
                        <td>" . htmlspecialchars($row['reviewer_username']) . "</td>
                        <td>" . htmlspecialchars($row['owner_username']) . "</td>
                        <td>" . htmlspecialchars($row['rating']) . "</td>
                        <td>" . htmlspecialchars($row['feedback']) . "</td>
                        <td>" . htmlspecialchars($row['feedback_date']) . "</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='text-center'>Nėra atsiliepimų</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>

<?php

$conn->close();
?>
