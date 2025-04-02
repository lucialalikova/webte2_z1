<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $_SESSION["login_required_msg"] = "Pre zobrazenie profilu sa musíte najprv prihlásiť.";
    header("Location: login.php");
    exit;
}

$config = require_once('config.php');
$db = connectDatabase($config['hostname'], $config['database'], $config['username'], $config['password']);
require_once 'vendor/autoload.php';

use Google\Client;

$client = new Client();
// Required, call the setAuthConfig function to load authorization credentials from
// client_secret.json file. The file can be downloaded from Google Cloud Console.
$client->setAuthConfig('../../client_secret.json');

// When a user logs in locally, set their session ID
// When a user logs in locally, set their session ID
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // Fetch user ID from the database using session email
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$_SESSION['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['id'] = $user['id'];
    }
}


// Google Login section
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
    $client->setAccessToken($_SESSION['access_token']);
    $oauth = new Google\Service\Oauth2($client);
    $account_info = $oauth->userinfo->get();

    $_SESSION['fullname'] = $account_info->name;
    $_SESSION['gid'] = $account_info->id;
    $_SESSION['email'] = $account_info->email;

    // Fetch user ID from your database, using Google ID (gid) to find the user
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$_SESSION['gid']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['id'] = $user['id']; // Set the user ID in session
    }
}

// Ensure $_SESSION['id'] is set before using it
if (!isset($_SESSION['id'])) {
    // Handle case where ID is not set (this could be an error or a redirect to login)
    $_SESSION['login_required_msg'] = "Pre zobrazenie profilu sa musíte najprv prihlásiť.";
    header("Location: login.php");
    exit;
}


// Kontrola, či je formulár na zmenu údajov odoslaný
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Zmena mena
    if (!empty($_POST['fullname'])) {
        $fullname = $_POST['fullname'];
        $stmt = $db->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $stmt->execute([$fullname, $_SESSION['id']]);
        $_SESSION['fullname'] = $fullname; // Aktualizujte meno v session
    }

    // Zmena hesla
    if (!empty($_POST['new_password']) && !empty($_POST['old_password'])) {
        $old_password = $_POST['old_password'];
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

        // Overenie starého hesla
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($old_password, $user['password'])) {
            // Ak je staré heslo správne, aktualizujte nové heslo
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_password, $_SESSION['id']]);
            $password_message = "Heslo bolo úspešne zmenené."; // Success message
        } else {
            $password_message = "Staré heslo je nesprávne."; // Error message for incorrect old password
        }
    }
}

// História prihlásení
$stmt = $db->prepare("SELECT * FROM users_login WHERE user_id = ? ORDER BY login_time DESC");
$stmt->execute([$_SESSION['id']]);
$login_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html lang="sk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="styles/bootstrap.min.css">
    <link rel="stylesheet" href="styles/styles.css">
    <title>Môj profil</title>
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

<div id="cookieConsent" class="position-fixed bottom-0 w-100 bg-dark text-white p-3 d-none" style="z-index: 1050;">
    <div class="container d-flex justify-content-between align-items-center flex-wrap">
        <span>Používame cookies na zlepšenie vašej skúsenosti na stránke.</span>
        <button id="acceptCookies" class="btn btn-sm btn-light ms-3 mt-2 mt-sm-0">Rozumiem</button>
    </div>
</div>


<body>
<nav class="navbar navbar-expand-lg sticky-top navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand mb-0 h1">NOBELOVÁ CENA</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="z2/index.php">Laureáti</a>
            </div>

            <div class="d-flex ms-auto">
                <?php if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true): ?>
                    <a class="btn btn-outline-light me-2" href="login.php">Prihlásiť sa</a>
                    <a class="btn btn-light" href="register.php">Zaregistrovať sa</a>
                <?php else: ?>
                    <a class="btn btn-outline-light me-2" href="restricted.php">Môj profil</a>
                    <a class="btn btn-light" href="logout.php">Odhlásiť sa</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main>
    <div class="container py-5 mt-5 mb-5" style="background-color: #f2d1e1; border-radius: 10px; border: 2px solid #001f3d; max-width: 800px;">
        <h3 class="mb-4" style="color: #001f3d;">Vitaj, <?= htmlspecialchars($_SESSION['fullname']) ?></h3>

        <?php if (isset($_SESSION['gid'])): ?>
            <p><strong>Si prihlásený cez Google účet, ID:</strong> <?= htmlspecialchars($_SESSION['gid']) ?></p>
        <?php else: ?>
            <p><strong>Si prihlásený cez lokálne konto.</strong></p>
        <?php endif; ?>

        <p><strong>E-mail:</strong> <?= htmlspecialchars($_SESSION['email']) ?></p>

        <?php if (isset($message)): ?>
            <div class="alert alert-info mt-3" style="background-color: #d1e7dd; border-color: #badbcc; color: #0f5132;">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <hr style="border-color: #001f3d;">

        <div class="row g-4">
            <div class="col-md-6">
                <h4 style="color: #001f3d;">Zmeniť meno</h4>
                <form method="POST">
                    <div class="mb-3">
                        <label for="fullname" class="form-label" style="color: #001f3d;">Meno</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" value="<?= htmlspecialchars($_SESSION['fullname']) ?>" required style="border-color: #001f3d;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="background-color: #001f3d; border-color: #001f3d;">Zmeniť meno</button>
                </form>
            </div>


            <?php if (!isset($_SESSION['gid'])): // Skrytí sekcie zmeny hesla, ak je používateľ prihlásený cez Google ?>
                <div class="col-md-6">
                    <h4 style="color: #001f3d;">Zmeniť heslo</h4>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="old_password" class="form-label" style="color: #001f3d;">Staré heslo</label>
                            <input type="password" class="form-control" id="old_password" name="old_password" required style="border-color: #001f3d;">
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label" style="color: #001f3d;">Nové heslo</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required style="border-color: #001f3d;">
                        </div>
                        <button type="submit" class="btn btn-primary" style="background-color: #001f3d; border-color: #001f3d">Zmeniť heslo</button>
                    </form>

                    <?php if (isset($password_message)): ?>
                        <div class="alert alert-danger mt-3" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb;">
                            <?= htmlspecialchars($password_message) ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>


            <hr style="border-color: #001f3d;">

        <h4 class="mt-4" style="color: #001f3d;">História prihlásení</h4>
        <div class="table-responsive mt-3">
            <table id="loginTable" class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                <tr>
                    <th>Čas prihlásenia</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($login_history as $login): ?>
                    <tr>
                        <td class="text-center"><?= htmlspecialchars($login['login_time']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>


        <a href="z2/index.php" class="btn btn-secondary mt-4 " style="background-color: #001f3d; border-color: #001f3d; color: white;">Späť na úvodnú stránku</a>
    </div>
</main>

<script src="script/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="script/restricted.js"></script>
</body>

</html>
