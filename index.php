<?php
session_start();
?>
<!doctype html>
<html lang="sk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login/register s 2FA</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="styles/bootstrap.min.css">
    <link rel="stylesheet" href="styles/styles.css">

    <!--<style>
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
        <a class="navbar-brand mb-0 h1">Laureáti Nobelovej Ceny</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup"
                aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav me-auto">

            </div>


            <div class="d-flex ms-auto">
                <?php if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true): ?>
                    <a class="btn btn-outline-light me-2" href="login.php">Prihlásiť sa</a>
                    <a class="btn btn-light" href="register.php">Zaregistrovať sa</a>
                <?php else: ?>
                    <span class="navbar-text text-white me-3">Vitaj <?= htmlspecialchars($_SESSION['fullname']) ?></span>
                    <a class="btn btn-outline-light me-2" href="restricted.php">Môj profil</a>
                    <a class="btn btn-light" href="logout.php">Odhlásiť sa</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>


<main>

    <?php

    session_start();

    $config = require_once('config.php');
    $db = connectDatabase($config['hostname'], $config['database'], $config['username'], $config['password']);

    $sql = "SELECT l.fullname, l.organisation, l.birth_year, l.death_year, p.year, p.category 
        FROM laureates l 
        JOIN laureates_prizes lp ON l.id = lp.laureates_id 
        JOIN prizes p ON lp.prize_id = p.id";

    $result = $db->query($sql);
    ?>

    <table id="laureates" class="display">
        <thead>
        <tr>
            <th>Meno</th>
            <th>Organizácia</th>
            <th>Rok narodenia</th>
            <th>Rok úmrtia</th>
            <th>Rok ceny</th>
            <th>Kategória</th>
        </tr>
        </thead>
        <tbody>
        <?php
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>{$row['fullname']}</td>";
            echo "<td>{$row['organisation']}</td>";
            echo "<td>{$row['birth_year']}</td>";
            echo "<td>{$row['death_year']}</td>";
            echo "<td>{$row['year']}</td>";
            echo "<td>{$row['category']}</td>";
            echo "</tr>";
        }
        ?>
        </tbody>
    </table>
</main>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.min.js"></script>
<script src="script/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function () {
        $('#laureates').DataTable({
            pageLength: 20,
            lengthMenu: [ [20, 50, 100, -1], [20, 50, 100, "Všetko"] ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/sk.json' // slovenský preklad
            }
        });
    });
</script>

</body>
<footer>

</footer>

</html>