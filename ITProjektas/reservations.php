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

// ID ir role
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

// atsiliepimai
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['owner_id'])) {
    $owner_id = $_POST['owner_id'];
    $rating = $_POST['rating'];
    $feedback = $_POST['feedback'];

    // Iterpti atsiliepimus
    $feedback_sql = "INSERT INTO feedback (user_id, owner_id, rating, feedback) VALUES (?, ?, ?, ?)";
    $feedback_stmt = $conn->prepare($feedback_sql);
    $feedback_stmt->bind_param("iiis", $user_id, $owner_id, $rating, $feedback);

    if ($feedback_stmt->execute()) {
        $message = "Atsiliepimas pateiktas sėkmingai!";
    } else {
        $message = "Klaida teikiant atsiliepimą.";
    }
    $feedback_stmt->close();
}

// Prisijungusio vartotojo rezervacijos
$sql = "SELECT 
            reservations.id AS reservation_id, 
            items.name AS item_name, 
            reservations.reservation_date, 
            reservations.rental_duration,
            DATE_ADD(reservations.reservation_date, INTERVAL reservations.rental_duration DAY) AS return_date,
            owner.username AS owner_username,
            owner.id AS owner_id
        FROM 
            reservations
        JOIN 
            items ON reservations.item_id = items.id
        JOIN 
            prisijungimas AS owner ON reservations.owner_id = owner.id
        WHERE 
            reservations.user_id = ?"; // atrenka prisijungusi

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// unikalus nuomuotojai is kuriu buvo nuomuojamasi
$owners_stmt = $conn->prepare("SELECT DISTINCT owner.id, owner.username FROM reservations 
                               JOIN prisijungimas AS owner ON reservations.owner_id = owner.id 
                               WHERE reservations.user_id = ?");
$owners_stmt->bind_param("i", $user_id);
$owners_stmt->execute();
$owners_result = $owners_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jūsų nuomos</title>
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
        <a class="nav-link" href="index.php">Pagrindinis puslapis</a>
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
        <a class="nav-link" href="reservations.php">Peržiūrėti rezervacijas / Pateikti atsiliepimą <span class="sr-only">(current)</span> </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="feedback.php">Nuomuotojų atsiliepimai</a>
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
    <h2 class="text-center mb-4">Jūsų rezervacijos</h2>

    <!-- Reservation Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Rezervacijos numeris</th>
                <th>Nuomuojamas daiktas</th>
                <th>Rezervacijos data</th>
                <th>Nuomos trukmė (dienomis)</th>
                <th>Grąžinimo data</th>
                <th>Nuomotojas</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>" . htmlspecialchars($row['reservation_id']) . "</td>
                        <td>" . htmlspecialchars($row['item_name']) . "</td>
                        <td>" . htmlspecialchars($row['reservation_date']) . "</td>
                        <td>" . htmlspecialchars($row['rental_duration']) . "</td>
                        <td>" . htmlspecialchars($row['return_date']) . "</td>
                        <td>" . htmlspecialchars($row['owner_username']) . "</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='6' class='text-center'>Nėra jokių rezervacijų</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Feedback Form -->
    <h3 class="mt-5">Pateikite atsiliepimą nuomotojui</h3>
    <?php if (isset($message)): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <form action="reservations.php" method="POST">
        <div class="form-group">
            <label for="owner_id">Pasirinkite nuomotoją iš kurio nuomavotes:</label>
            <select class="form-control" id="owner_id" name="owner_id" required>
                <?php
                while ($owner_row = $owners_result->fetch_assoc()) {
                    echo "<option value='" . $owner_row['id'] . "'>" . htmlspecialchars($owner_row['username']) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label for="rating">Įvertinimas (1-5):</label>
            <input type="number" id="rating" name="rating" class="form-control" min="1" max="5" required>
        </div>
        <div class="form-group">
            <label for="feedback">Atsiliepimas:</label>
            <textarea id="feedback" name="feedback" class="form-control" rows="4" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Pateikti atsiliepimą</button>
    </form>
</div>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
