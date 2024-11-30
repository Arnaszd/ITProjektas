<?php

session_start();


$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "itprojektas";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // **Change 1: Modified SQL Query to Include `pareiga`**
    $stmt = $conn->prepare("SELECT password, pareiga FROM prisijungimas WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if username exists in the database
    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
    
        // Verify password
        if (password_verify($password, $row['password'])) {
            // Set session variable for the logged-in user
            $_SESSION['login_user'] = $username;
    
            // Redirect all users to index.php
            header("location: index.php");
            exit;
        } else {
            $error = "Klaida prisijungiant.";
        }
    } else {
        $error = "Klaida prisijungiant.";
    }

    // Close the statement
    $stmt->close();
}

// Close the connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <link rel="stylesheet" href="stylesheet.css">
</head>

<body>
<div class="simple-login-container">
    <h2>Prisijungimas</h2>
    <form action="login.php" method="POST">
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
        <a href="register.php">Registruoti paskyrą</a>
        
        <div class="row">
            <div class="col-md-12 form-group">
                <br>
                <input type="submit" class="btn btn-block btn-login" value="Prisijungti">
            </div>
        </div>
    </form>
    <?php if (isset($error)) { echo "<p>$error</p>"; } ?>
</div>
</body>
</html>
