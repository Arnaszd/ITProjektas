<?php
// Pradėti sesija
session_start();

// Patikrinti ar prisijungęs
if (!isset($_SESSION['login_user'])) {
    die("Pirmiausia prisijunkite");
}


$logged_in_user = $_SESSION['login_user'];


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "itprojektas";

// Prisijungimas prie DB
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_role = null;

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

// Rezervacijos sistema
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['item_id']) && isset($_POST['rental_duration'])) {
    $item_id = $_POST['item_id'];
    $rental_duration = $_POST['rental_duration'];

    // Naudotojo ID
    $user_sql = "SELECT id FROM prisijungimas WHERE username = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("s", $logged_in_user);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows == 1) {
        $user_row = $user_result->fetch_assoc();
        $user_id = $user_row['id'];

        // Kas sukūrė skelbimą (username)
        $owner_sql = "SELECT created_by FROM items WHERE id = ?";
        $owner_stmt = $conn->prepare($owner_sql);
        $owner_stmt->bind_param("i", $item_id);
        $owner_stmt->execute();
        $owner_result = $owner_stmt->get_result();

        if ($owner_result->num_rows == 1) {
            $owner_row = $owner_result->fetch_assoc();
            $owner_username = $owner_row['created_by'];

            // Kas sukūrė (ID)
            $owner_id_sql = "SELECT id FROM prisijungimas WHERE username = ?";
            $owner_id_stmt = $conn->prepare($owner_id_sql);
            $owner_id_stmt->bind_param("s", $owner_username);
            $owner_id_stmt->execute();
            $owner_id_result = $owner_id_stmt->get_result();
            
            if ($owner_id_result->num_rows == 1) {
                $owner_id_row = $owner_id_result->fetch_assoc();
                $owner_id = $owner_id_row['id'];

                // Patikrina ar jau yra išsinuomavęs
                $check_reservation_sql = "SELECT * FROM reservations WHERE user_id = ? AND item_id = ?";
                $check_reservation_stmt = $conn->prepare($check_reservation_sql);
                $check_reservation_stmt->bind_param("ii", $user_id, $item_id);
                $check_reservation_stmt->execute();
                $check_reservation_result = $check_reservation_stmt->get_result();

                if ($check_reservation_result->num_rows > 0) {
                    $message = "Jūs jau esate iššinuomavęs šią baidarę.";
                } else {
                    // Insert reservation into the reservations table
                    $reservation_sql = "INSERT INTO reservations (user_id, item_id, owner_id, rental_duration) VALUES (?, ?, ?, ?)";
                    $reservation_stmt = $conn->prepare($reservation_sql);
                    $reservation_stmt->bind_param("iiii", $user_id, $item_id, $owner_id, $rental_duration);
                    if ($reservation_stmt->execute()) {
                        $message = "Nuoma sėkminga!";
                    } else {
                        $message = "Klaida atliekant nuomą.";
                    }
                    $reservation_stmt->close();
                }
                $check_reservation_stmt->close();
            }
            $owner_id_stmt->close();
        }
        $owner_stmt->close();
    }
    $user_stmt->close();
}

// Search
$search_query = "";
if (isset($_GET['search'])) {
    $search_query = $_GET['search'];
}

// Baidares išmeta
$sql = "SELECT id, name, image, price, description, created_by FROM items WHERE type = 'Baidares'";
if (!empty($search_query)) {
    $sql .= " AND name LIKE ?";
}

$stmt = $conn->prepare($sql);
if (!empty($search_query)) {
    $like_query = "%" . $search_query . "%";
    $stmt->bind_param("s", $like_query);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Baidarių Nuoma</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="watermark.css"> 
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
        <a class="nav-link" href="canoes.php">Baidarių nuoma <span class="sr-only">(current)</span> </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="vehicles.php">Mašinų nuoma</a>
      </li>
      <!-- Leidžią prieigą admin ir nuomuotojui -->
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
        <a class="nav-link" href="control.php">Kontrolierius</a>
      </li>
      <?php endif; ?>
    </ul>
  </div>
</nav>

<div class="container">
    <h2 class="text-center my-4">Baidarių Nuoma</h2>
    
    <!-- Search Forma -->
    <form action="canoes.php" method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Ieškoti baidarės pagal pavadinimą..." value="<?php echo htmlspecialchars($search_query); ?>">
            <div class="input-group-append">
                <button class="btn btn-primary" type="submit">Ieškoti</button>
            </div>
        </div>
    </form>

    <!-- Išveda pranešimą -->
    <?php if (isset($message)): ?>
        <div class="alert alert-info text-center"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="row">
        <?php
        // Check if there are results and display them
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $item_id = $row['id'];
                $name = htmlspecialchars($row['name']);
                $image = !empty($row['image']) ? htmlspecialchars($row['image']) : 'uploads/image.png';
                $price = htmlspecialchars($row['price']);
                $created_by = htmlspecialchars($row['created_by']);
                $description = htmlspecialchars($row['description']);
                
                echo '
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="image-container">
                            <img src="' . $image . '" alt="' . $name . '">
                            <div class="watermark">nuoma</div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">' . $name . '</h5>
                            <p class="card-text">' . $description . '</p>
                            <p class="card-text"><strong>Kaina: €' . $price . '</strong></p>
                            <p class="card-text"><strong>Nuomuotojo slapyvardis: ' . $created_by . '</strong></p>
                            
                            <!-- Reservation Form with Rental Duration -->
                            <form action="canoes.php" method="POST">
                                <input type="hidden" name="item_id" value="' . $item_id . '">
                                <div class="form-group">
                                    <label for="rental_duration">Nuomos trukmė (dienomis):</label>
                                    <select name="rental_duration" class="form-control" required>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                        <option value="6">6</option>
                                        <option value="7">7</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Rezervuoti</button>
                            </form>
                        </div>
                    </div>
                </div>';
            }
        } else {
            echo '<p class="text-center col-12">Nėra baidarių skelbimų.</p>';
        }
        

        $conn->close();
        ?>
    </div>
</div>

</body>
</html>
