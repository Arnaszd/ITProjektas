<?php
// Pradeti sesija
session_start();

// Database configuration
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

// Kur issaugoti fotkes
$upload_dir = "uploads/";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $type = $_POST['type'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $filename = basename($_FILES["image"]["name"]);
    $target_file = $upload_dir . $filename;
    $name = $_POST['name'];
    $created_by = $_SESSION['login_user']; 

    // Perkelti nuotraukas i aplankala
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        // Iterpti duomenis i duomenu baze
        $stmt = $conn->prepare("INSERT INTO items (type, image, price, description, name, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsss", $type, $target_file, $price, $description, $name, $created_by);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo "<div class='alert alert-success text-center'>Skelbimas sėkmingai patalpintas</div>";
        } else {
            echo "<div class='alert alert-danger text-center'>Klaida talpinant skelbimą</div>";
        }
        $stmt->close();
    } else {
        echo "<div class='alert alert-danger text-center'>Klaida įkeliant nuotrauką</div>";
    }
}

// 
$conn->close();

if ($user_role !== "admin" && $user_role !=="nuom")  {
  die("Tik administratorius arba nuomininkas gali pasiekti šį puslapį.");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Skelbimo sukūrimas</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
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

<div class="container mt-5">
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-header text-center">
            <h5>Skelbimo sukūrimas</h5>
        </div>
        <div class="card-body">
            <form action="create.php" method="POST" enctype="multipart/form-data">
                
                <!-- Dropdown for type selection -->
                <div class="form-group">
                    <label for="type">Nuomos tipas</label>
                    <select class="form-control" name="type" id="type" required>
                        <option value="Baidares">Baidarės</option>
                        <option value="Automobiliai">Automobiliai</option>
                    </select>
                </div>
                
     
                <div class="form-group">
                    <label for="name">Pavadinimas</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                
       
                <div class="form-group">
                    <label for="price">Kaina</label>
                    <input type="number" step="0.01" name="price" id="price" class="form-control" required>
                </div>
                
             
                <div class="form-group">
                    <label for="description">Aprašas</label>
                    <textarea name="description" id="description" rows="4" class="form-control" required></textarea>
                </div>
            
                <div class="form-group">
                    <label for="image">Nuotraukos įkėlimas</label>
                    <input type="file" name="image" id="image" class="form-control-file" required>
                </div>
                
              
                <button type="submit" class="btn btn-primary btn-block">Pateikti</button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
