<?php
session_start();
$config = require_once('config.php');
$db = connectDatabase($config['hostname'], $config['database'], $config['username'], $config['password']);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT 
            l.fullname,
            l.organisation,
            l.sex,
            GROUP_CONCAT(DISTINCT c.country_name ORDER BY c.country_name SEPARATOR ', ') AS countries,
            l.birth_year,
            l.death_year
        FROM laureates l
        JOIN laureates_countries lc ON l.id = lc.laureates_id
        JOIN countries c ON lc.country_id = c.id
        WHERE l.id = ?
        GROUP BY l.id";

$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$laureate = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$laureate) {
    echo "Laureát nebol nájdený.";
    exit;
}

$sex = $laureate['sex'] === 'M' ? 'Muž' : ($laureate['sex'] === 'F' ? 'Žena' : '');

$sqlPrizes = "SELECT 
                p.year,
                p.category,
                p.contrib_sk,
                p.contrib_en,
                d.language_sk,
                d.language_en,
                d.genre_sk,
                d.genre_en
            FROM prizes p
            JOIN laureates_prizes lp ON p.id = lp.prize_id
            LEFT JOIN prize_details d ON p.details_id = d.id
            WHERE lp.laureates_id = ?
            ORDER BY p.year";

$stmtPrizes = $db->prepare($sqlPrizes);
$stmtPrizes->execute([$id]);
$prizes = $stmtPrizes->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Detail laureáta</title>
    <link rel="stylesheet" href="styles/bootstrap.min.css">
    <link rel="stylesheet" href="styles/styles.css">
    <style>
        .bold { font-weight: bold; }
        .card + .card { margin-top: 1rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg sticky-top navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand mb-0 h1">NOBELOVÁ CENA</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="index.php">Laureáti</a>
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

<main class="container mt-4">
    <h1 class="mb-4 text-center">Detail laureáta</h1>

    <div class="mb-5 p-4" style="border: 2px solid #001f3d; background-color: #f2d1e1; border-radius: 10px;">
        <h4 class="text-primary"><?= htmlspecialchars($laureate['fullname'] ?: $laureate['organisation']) ?></h4>
        <p class="mb-1 text-muted"><?= ($laureate['birth_year'] ?: '') . ' - ' . ($laureate['death_year'] ?: '') ?></p> <br>

        <?php if (!empty($laureate['fullname'])): ?>
            <p><span class="fw-bold">Pohlavie:</span> <?= $sex ?></p>
        <?php endif; ?>

        <p><span class="fw-bold">Krajina:</span> <?= htmlspecialchars($laureate['countries']) ?></p>
    </div>

    <h4 class="mb-3 text-center">Udelené ceny</h4>

    <?php foreach ($prizes as $prize): ?>
        <div class="card shadow-sm mb-4" style="border: 2px solid #001f3d; background-color: #f2d1e1;">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($prize['year']) ?> – <?= htmlspecialchars($prize['category']) ?></h5>
                <p class="card-text"><span class="fw-bold">Príspevok:</span> <?= nl2br(htmlspecialchars($prize['contrib_sk'])) ?></p>
                <p class="card-text"><span class="fw-bold">Contribution:</span> <?= nl2br(htmlspecialchars($prize['contrib_en'])) ?></p>

                <?php if ($prize['category'] === 'Literatúra'): ?>
                    <hr>
                    <p class="card-text"><span class="fw-bold">Jazyk:</span> <?= htmlspecialchars($prize['language_sk']) ?></p>
                    <p class="card-text"><span class="fw-bold">Language:</span> <?= htmlspecialchars($prize['language_en']) ?></p> <br>
                    <p class="card-text"><span class="fw-bold">Žáner:</span> <?= htmlspecialchars($prize['genre_sk']) ?></p>
                    <p class="card-text"><span class="fw-bold">Genre:</span> <?= htmlspecialchars($prize['genre_en']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <a href="index.php" class="btn btn-primary mt-4">← Späť na zoznam</a>
</main>
</body>
</html>
