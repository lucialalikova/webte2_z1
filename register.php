<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if the user is already logged in, if yes then redirect him to welcome page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php");
    exit;
}

$config = require_once('config.php');
try {
    $db = connectDatabase($config['hostname'], $config['database'], $config['username'], $config['password']);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
require_once 'vendor/autoload.php';
require_once 'utilities.php';

use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = "";

    // Validate email
    if (isEmpty($_POST['email']) === true) {
        $errors .= "Nevyplnený e-mail.\n";
    }

    // TODO: validate if user entered correct e-mail format

    // Validate user existence
    if (userExist($db, $_POST['email']) === true) {
        $reg_status = "Účet s týmto e-mailom už existuje.";
    } else {
        // Vaidate name and surname
        if (isEmpty($_POST['firstname']) === true) {
            $errors .= "Nevyplnené meno.\n";
        } elseif (isEmpty($_POST['lastname']) === true) {
            $errors .= "Nevyplnené priezvisko.\n";
        }

        // TODO: Implement name and surname length validation based on the database column length.
        // TODO: Implement name and surname allowed characters validation.


        // Validate password
        if (isEmpty($_POST['password']) === true) {
            $errors .= "Nevyplnené heslo.\n";
        }

        // TODO: Implement repeat password validation.
        // TODO: Sanitize and validate all user inputs.

        if (empty($errors)) {
            $sql = "INSERT INTO users (fullname, email, password, 2fa_code) VALUES (:fullname, :email, :password, :2fa_code)";

            $fullname = $_POST['firstname'] . ' ' . $_POST['lastname'];
            $email = $_POST['email'];
            $pw_hash = password_hash($_POST['password'], PASSWORD_ARGON2ID);

            // Generate a secret key for 2FA
            $tfa = new TwoFactorAuth(new EndroidQrCodeProvider());
            $user_secret = $tfa->createSecret();
            $qr_code = $tfa->getQRCodeImageAsDataUri('Nobel Prizes', $user_secret);

            // Bind parameters to SQL
            $stmt = $db->prepare($sql);

            $stmt->bindParam(":fullname", $fullname, PDO::PARAM_STR);
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $stmt->bindParam(":password", $pw_hash, PDO::PARAM_STR);
            $stmt->bindParam(":2fa_code", $user_secret, PDO::PARAM_STR);

            if ($stmt->execute()) {
                $reg_status = "Registracia prebehla uspesne.";
            } else {
                $reg_status = "Ups. Nieco sa pokazilo...";
            }

            unset($stmt);
        }
    }
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
    <title>Registrácia</title>
    <link rel="stylesheet" href="styles/bootstrap.min.css">
    <link rel="stylesheet" href="styles/styles.css">

<!--    <style>
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
        span, .err {
            color: red;
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
            <div class="navbar-nav">
                <a class="nav-link" href="index.php">Domov</a>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-5" style="max-width: 500px;">
    <h2 class="mb-4 text-center">Vytvorenie nového používateľského konta</h2>

    <?php if (isset($reg_status)): ?>
        <div class="alert alert-info"><?= htmlspecialchars($reg_status) ?></div>
    <?php endif; ?>

    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
        <div class="mb-3">
            <label for="firstname" class="form-label">Meno:</label>
            <input type="text" name="firstname" id="firstname" class="form-control" placeholder="napr. John" required>
        </div>

        <div class="mb-3">
            <label for="lastname" class="form-label">Priezvisko:</label>
            <input type="text" name="lastname" id="lastname" class="form-control" placeholder="napr. Doe" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">E-mail:</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="napr. johndoe@example.com" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Heslo:</label>
            <input type="password" name="password" id="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Vytvoriť konto</button>
    </form>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mt-3">
            <strong>Chyby:</strong><br>
            <?= nl2br(htmlspecialchars($errors)); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($qr_code)): ?>
        <div class="alert alert-success mt-3">
            <p>Zadajte kód: <?= htmlspecialchars($user_secret); ?> do aplikácie pre 2FA</p>
            <p>alebo naskenujte QR kód:</p>
            <img src="<?= htmlspecialchars($qr_code); ?>" alt="QR kód pre aplikáciu authenticator" class="img-fluid">
        </div>
    <?php endif; ?>

    <p class="mt-4 text-center">Už máte vytvorené konto? <a href="login.php">Prihláste sa tu.</a></p>
</div>

<script src="script/bootstrap.bundle.min.js"></script>
</body>

</html>