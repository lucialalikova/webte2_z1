<!doctype html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Laureáti Nobelovej Ceny</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="../styles/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/styles.css">
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
                <a class="nav-link active">Laureáti</a>
            </div>
            <div class="d-flex ms-auto">
                <?php if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true): ?>
                    <a class="btn btn-outline-light me-2" href="../login.php">Prihlásiť sa</a>
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
<main class="container mt-4">
    <h2 class="text-center mb-4 text-uppercase">Zoznam laureátov Nobelovej ceny</h2>
    <div class="table-responsive">
        <table id="laureates" class="display table table-striped table-bordered" style="border: 2px solid #001f3d; background-color: #f2d1e1;">
            <thead>
            <tr style="background-color: #001f3d; color: white;">
                <th>Rok udelenia ceny</th>
                <th>Meno / Organizácia</th>
                <th>Pohlavie</th>
                <th>Krajina</th>
                <th>Rok narodenia</th>
                <th>Rok úmrtia</th>
                <th>Kategória</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
    <a href="add_laureate.php" class="btn btn-primary mt-4">Pridať laureáta</a>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.min.js"></script>
<script src="../script/bootstrap.bundle.min.js"></script>
<script src="../script/script.js"></script>
</body>
</html>