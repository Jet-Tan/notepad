<?php
session_start();

include "config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $identifier = $_SESSION['identifier'];
    $password_input = $_POST['password'];
    $identifier = mysqli_real_escape_string($conn, $identifier);

    $sql = "SELECT passwords FROM notes WHERE identifier = '$identifier'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if ($row && md5($password_input) == $row['passwords']) { // Using md5() for password comparison
        setcookie('authenticated', $identifier, time() + (86400 * 30), "/");
        header("Location: https://rionotes.com/$identifier");
        exit();
    } else {
        $error = "Incorrect password. Please try again.";
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f4f4;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            max-width: 400px;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h3 class="text-center">Please Enter Password</h3>
        <?php if (isset($error)) { ?>
            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
        <?php } ?>
        <form method="post" action="">
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
    </div>
</body>

</html>