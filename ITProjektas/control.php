<?php
// Pradėti sesiją
session_start();

// Patikrinti ar prisijunges
if (!isset($_SESSION['login_user'])) {
    die("Pirmiausia prisijunkite");
}


$logged_in_user = $_SESSION['login_user'];

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "itprojektas";

// Prisijungimas
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

// Žmogaus pareiga
$user_role = null;
$stmt = $conn->prepare("SELECT pareiga FROM prisijungimas WHERE username = ?");
$stmt->bind_param("s", $logged_in_user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $user_role = $row['pareiga'];
}
$stmt->close();


//Admin gali pasiekti šį puslapį
if ($user_role !== "admin") {
    die("Tik administratorius gali pasiekti šį puslapį.");
}

// istrinti atsiliepimus
if (isset($_POST['delete_feedback_id'])) {
    $feedback_id = $_POST['delete_feedback_id'];
    $delete_feedback_sql = "DELETE FROM feedback WHERE id = ?";
    $delete_feedback_stmt = $conn->prepare($delete_feedback_sql);
    $delete_feedback_stmt->bind_param("i", $feedback_id);
    $delete_feedback_stmt->execute();
    $delete_feedback_stmt->close();
}

// istrinti foto ir nustatyti reiksme i defaulta
if (isset($_POST['remove_image_item_id'])) {
    $item_id = $_POST['remove_image_item_id'];
    $update_image_sql = "UPDATE items SET image = NULL WHERE id = ?";
    $update_image_stmt = $conn->prepare($update_image_sql);
    $update_image_stmt->bind_param("i", $item_id);
    $update_image_stmt->execute();
    $update_image_stmt->close();
}

// isvesti visus atsiliepimus, kas juos sukure
$feedback_sql = "SELECT 
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
$feedback_result = $conn->query($feedback_sql);

// Visi duomenys apie daiktus
$items_sql = "SELECT id, name, image, price, description, created_by, created_at FROM items";
$items_result = $conn->query($items_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control Panel</title>
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
      <li class="nav-item">
        <a class="nav-link" href="create.php">Pateikti skelbimą</a>
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
        <a class="nav-link" href="feedback.php">Nuomuotojų atsiliepimai</a>
      </li>
      <!-- Leidžia prieigą prie kontrolieriaus jeigu esi administratorius -->
      <?php if ($user_role === "admin"): ?>
      <li class="nav-item">
        <a class="nav-link" href="control.php">Kontrolierius <span class="sr-only">(current)</span> </a>
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
                <th>Atsiliepimo ID</th>
                <th>Atsiliepėjas</th>
                <th>Nuomotojas</th>
                <th>Įvertinimas</th>
                <th>Atsiliepimas</th>
                <th>Atsiliepimo data</th>
                <th>Veiksmai</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($feedback_result && $feedback_result->num_rows > 0) {
                while($row = $feedback_result->fetch_assoc()) {
                    echo "<tr>
                        <td>" . htmlspecialchars($row['feedback_id']) . "</td>
                        <td>" . htmlspecialchars($row['reviewer_username']) . "</td>
                        <td>" . htmlspecialchars($row['owner_username']) . "</td>
                        <td>" . htmlspecialchars($row['rating']) . "</td>
                        <td>" . htmlspecialchars($row['feedback']) . "</td>
                        <td>" . htmlspecialchars($row['feedback_date']) . "</td>
                        <td>
                            <form method='POST' style='display:inline;'>
                                <input type='hidden' name='delete_feedback_id' value='" . $row['feedback_id'] . "'>
                                <button type='submit' class='btn btn-danger btn-sm'>Ištrinti</button>
                            </form>
                        </td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='7' class='text-center'>Nėra atsiliepimų</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <h2 class="text-center mt-5 mb-4">Visi Nuomojami Daiktai</h2>

    <!-- Items Table -->
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Daikto ID</th>
                <th>Pavadinimas</th>
                <th>Nuotrauka</th>
                <th>Kaina</th>
                <th>Aprašymas</th>
                <th>Sukūrė</th>
                <th>Sukūrimo data</th>
                <th>Veiksmai</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($items_result && $items_result->num_rows > 0) {
                while($item_row = $items_result->fetch_assoc()) {
                    echo "<tr>
                        <td>" . htmlspecialchars($item_row['id']) . "</td>
                        <td>" . htmlspecialchars($item_row['name']) . "</td>
                        <td>";
                    
                    if (!empty($item_row['image'])) {
                        echo "<img src='" . htmlspecialchars($item_row['image']) . "' alt='" . htmlspecialchars($item_row['name']) . "' width='100'>";
                    } else {
                        echo "Nėra nuotraukos";
                    }
                    
                    echo "</td>
                        <td>€" . htmlspecialchars($item_row['price']) . "</td>
                        <td>" . htmlspecialchars($item_row['description']) . "</td>
                        <td>" . htmlspecialchars($item_row['created_by']) . "</td>
                        <td>" . htmlspecialchars($item_row['created_at']) . "</td>
                        <td>";
                    
                    if (!empty($item_row['image'])) {
                        echo "<form method='POST' style='display:inline;'>
                                <input type='hidden' name='remove_image_item_id' value='" . $item_row['id'] . "'>
                                <button type='submit' class='btn btn-warning btn-sm'>Pašalinti nuotrauką</button>
                              </form>";
                    }

                    echo "</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='8' class='text-center'>Nėra nuomojamų daiktų</td></tr>";
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
