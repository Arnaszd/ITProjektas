<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "itprojektas";

// Connect to the database
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize message variable for errors
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if username is already taken
    $check_stmt = $conn->prepare("SELECT id FROM prisijungimas WHERE username = ?");
    $check_stmt->bind_param("s", $username);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // Username is taken
        $message = "Šis vartotojo vardas jau užimtas. Pasirinkite kitą vardą.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO prisijungimas (username, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $username, $hashed_password);

        if ($stmt->execute()) {
            header("Location: login.php");  
            echo "Registracija sėkminga!";
            exit;  
        } else {
            $message = "Klaida registruojant: " . $stmt->error;
        }
        $stmt->close();
    }
    $check_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registracija</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <link rel="stylesheet" href="stylesheet.css">
</head>

<body>
<div class="simple-login-container">
    <h2>Registracija</h2>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-warning"><?php echo $message; ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="row">
            <div class="col-md-12 form-group">
                <input type="text" name="username" class="form-control" placeholder="Įveskite vartotojo vardą" required>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 form-group">
                <input type="password" name="password" placeholder="Įveskite slaptažodį" class="form-control" required>
            </div>
        </div>
        <a href="login.php">Grįžti į prisijungimo puslapį</a>
        
        <div class="row">
            <div class="col-md-12 form-group">
                <br>
                <input type="submit" class="btn btn-block btn-login" value="Registruoti">
            </div>
        </div>
    </form>
</div>

</body>
</html>
        