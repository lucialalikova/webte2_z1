<?php

session_start();

// Check if the user is already logged in, if yes then redirect him to restricted page.
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: restricted.php");
    exit;
}

if (isset($_SESSION["login_required_msg"])) {
    $login_msg = $_SESSION["login_required_msg"];
    unset($_SESSION["login_required_msg"]); // odstránime, aby sa nezobrazovala znova
}

$config = require_once('config.php');
$db = connectDatabase($config['hostname'], $config['database'], $config['username'], $config['password']);
require_once 'vendor/autoload.php';
require_once 'utilities.php';

use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;

// Redirect users to outh2call.php which redirects users to Google OAuth 2.0
$redirect_uri = "https://node74.webte.fei.stuba.sk/z1/oauth2callback.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // TODO: Implement login credentials verification.
    // TODO: Implement a mechanism to save login information - user_id, login_type, email, fullname - to database.

    $sql = "SELECT id, fullname, email, password, 2fa_code, created_at FROM users WHERE email = :email";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(":email", $_POST["email"], PDO::PARAM_STR);
    $errors = "";

    if ($stmt->execute()) {
        if ($stmt->rowCount() == 1) {
            // User exists, check password.
            $row = $stmt->fetch();
            $hashed_password = $row["password"];

            if (password_verify($_POST['password'], $hashed_password)) {
                // Password is correct.
                $tfa = new TwoFactorAuth(new EndroidQrCodeProvider());
                if ($tfa->verifyCode($row["2fa_code"], $_POST['2fa'], 2)) {
                    // Password and code are correct, user authenticated.

                    // Save user data to session.
                    $_SESSION["loggedin"] = true;
                    $_SESSION["fullname"] = $row['fullname'];
                    $_SESSION["email"] = $row['email'];
                    $_SESSION["created_at"] = $row['created_at'];

                    // Získame user_id z DB
                    $user_id = $row['id'];
                    $login_type = 'local'; // vlastné prihlásenie
                    $email = $row['email'];
                    $fullname = $row['fullname'];
                    $login_time = date('Y-m-d H:i:s');

                    $insertSql = "INSERT INTO users_login (user_id, login_type, email, fullname, login_time) 
              VALUES (:user_id, :login_type, :email, :fullname, :login_time)";
                    $insertStmt = $db->prepare($insertSql);
                    $insertStmt->execute([
                        ':user_id' => $user_id,
                        ':login_type' => $login_type,
                        ':email' => $email,
                        ':fullname' => $fullname,
                        ':login_time' => $login_time
                    ]);

// Presmerovanie
                    header("location: restricted.php");
                    exit;
                }
                else {
                    $errors = "Neplatný kod 2FA.";
                }
            } else {
                $errors = "Nesprávny email alebo heslo.";
            }
        } else {
            $errors = "Nesprávny email alebo heslo.";
        }
    } else {
        $errors = "Nastala chyba pri prihlasovaní.";
    }

    unset($stmt);
    unset($pdo);
}

?>

<!doctype html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="styles/bootstrap.min.css">
    <link rel="stylesheet" href="styles/styles.css">
    <title>Prihlásenie</title>

  <!--  <style>
        html {
            max-width: 70ch;
            padding: 3em 1em;
            margin: auto;
            line-height: 1.75;
            font-size: 1.25em;
        }

        h1,h2,h3,h4,h5,h6 {
            margin: 3em 0 1em;
        }

        p,ul,ol {
            margin-bottom: 2em;
            color: #1d1d1d;
            font-family: sans-serif;
        }
    </style>-->
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Laureáti Nobelovej Ceny</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup"
                aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="index.php">Domov</a>
            </div>
            <div class="d-flex ms-auto"></div>
        </div>
    </div>
</nav>
<main>
    <div class="container mt-5" style="max-width: 500px;">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errors) ?></div>
        <?php endif; ?>

        <?php if (!empty($login_msg)): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($login_msg) ?></div>
        <?php endif; ?>

        <h2 class="mb-4 text-center">Prihlásenie do účtu</h2>
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="mb-3">
                <label for="email" class="form-label">E-Mail:</label>
                <input type="text" name="email" id="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Heslo:</label>
                <input type="password" name="password" id="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="2fa" class="form-label">2FA kód:</label>
                <input type="number" name="2fa" id="2fa" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Prihlásiť sa</button>

            <p class="mt-4 text-center">Nemáte účet? <a href="register.php">Zaregistrujte sa tu</a></p>

            <p class="mt-3 text-center">
                Alebo sa prihláste pomocou <a href="<?= htmlspecialchars($redirect_uri) ?>">Google konta</a>
            </p>
        </form>
    </div>
</main>
<script src="script/bootstrap.bundle.min.js"></script>
</body>
</html>