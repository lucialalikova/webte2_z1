<?php
session_start();
$config = require_once('config.php');
$db = connectDatabase($config['hostname'], $config['database'], $config['username'], $config['password']);

// Filters from GET
$filter_year = isset($_GET['year']) ? $_GET['year'] : '';
$filter_category = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$order = (isset($_GET['order']) && strtolower($_GET['order']) === 'desc') ? 'DESC' : 'ASC';

// Get unique years and categories for filters
$years = $db->query("SELECT DISTINCT year FROM prizes ORDER BY year")->fetchAll(PDO::FETCH_COLUMN);
$categories = $db->query("SELECT DISTINCT category FROM prizes ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);

// Base query
$sql = "SELECT 
            l.id,
            p.year, 
            l.fullname, 
            l.organisation, 
            l.sex, 
            GROUP_CONCAT(c.country_name ORDER BY c.country_name SEPARATOR ', ') AS countries,
            l.birth_year, 
            l.death_year, 
            p.category,
            p.contrib_sk,
            p.contrib_en
        FROM 
            laureates l 
        JOIN 
            laureates_prizes lp ON l.id = lp.laureates_id 
        JOIN 
            prizes p ON lp.prize_id = p.id
        JOIN 
            laureates_countries lc ON l.id = lc.laureates_id
        JOIN 
            countries c ON lc.country_id = c.id
        WHERE 1";

$params = [];

if (!empty($filter_year)) {
    $sql .= " AND p.year = ?";
    $params[] = $filter_year;
}

if (!empty($filter_category)) {
    $sql .= " AND p.category = ?";
    $params[] = $filter_category;
}

$sql .= " GROUP BY l.id, p.year, l.fullname, l.organisation, l.sex, l.birth_year, l.death_year, p.category, p.contrib_sk, p.contrib_en";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper to generate sortable links
function sort_link($column, $label) {
    $current_sort = $_GET['sort'] ?? '';
    $current_order = $_GET['order'] ?? 'asc';
    $new_order = ($current_sort === $column && $current_order === 'asc') ? 'desc' : 'asc';

    $query = $_GET;
    $query['sort'] = $column;
    $query['order'] = $new_order;
    $url = '?' . http_build_query($query);

    return "<a href=\"$url\">$label</a>";
}
?>

<!doctype html>
<html lang="sk">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Laureáti Nobelovej Ceny</title>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="styles/bootstrap.min.css">
    <link rel="stylesheet" href="styles/styles.css">
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

<main class="container mt-4">
    <h2 class="text-center mb-4 text-uppercase">Zoznam laureátov Nobelovej ceny</h2>

    <!-- FILTER FORM -->
    <form method="GET" class="row align-items-start" style="background-color: #f2d1e1; border: 2px solid #001f3d; border-radius: 10px; padding: 20px;">
        <div class="col m-3">
            <label for="year" class="form-label text-dark">Filtrovať podľa roku:</label>
            <select name="year" id="year" class="form-select" onchange="this.form.submit()">
                <option value="">-- všetky --</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?= $year ?>" <?= $year == $filter_year ? 'selected' : '' ?>><?= $year ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col m-3">
            <label for="category" class="form-label text-dark">Filtrovať podľa kategórie:</label>
            <select name="category" id="category" class="form-select" onchange="this.form.submit()">
                <option value="">-- všetky --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat ?>" <?= $cat == $filter_category ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <!-- TABLE -->
    <div class="table-responsive">
        <table id="laureates" class="display table table-striped table-bordered" style="border: 2px solid #001f3d; background-color: #f2d1e1;">
            <thead>
            <tr style="background-color: #001f3d; color: white;">
                <?php if (empty($filter_year)): ?>
                    <th>Rok udelenia ceny</th>
                <?php endif; ?>
                <th>Meno / Organizácia</th>
                <th>Pohlavie</th>
                <th>Krajina</th>
                <th>Rok narodenia</th>
                <th>Rok úmrtia</th>
                <?php if (empty($filter_category)): ?>
                    <th>Kategória</th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($rows as $row) {
                $sex = ($row['sex'] === 'F') ? 'Žena' : (($row['sex'] === 'M') ? 'Muž' : '');

                $birthYear = ($row['birth_year'] != 0) ? $row['birth_year'] : '';
                $deathYear = ($row['death_year'] != 0) ? $row['death_year'] : '';

                echo "<tr style='background-color: #f2d1e1;'>";
                if (empty($filter_year)) {
                    echo "<td>{$row['year']}</td>";
                }

                echo "<td>";
                if (!empty($row['fullname'])) {
                    echo "<a href='detail.php?id={$row['id']}' style='color: #001f3d;'>" . htmlspecialchars($row['fullname']) . "</a>";
                } else {
                    echo "<a href='detail.php?id={$row['id']}' style='color: #001f3d;'>" . htmlspecialchars($row['organisation']) . "</a>";
                }
                echo "</td>";

                echo "<td>{$sex}</td>";
                echo "<td>{$row['countries']}</td>";
                echo "<td>{$birthYear}</td>";
                echo "<td>{$deathYear}</td>";

                if (empty($filter_category)) {
                    echo "<td>{$row['category']}</td>";
                }

                echo "</tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</main>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/2.2.2/js/dataTables.min.js"></script>
<script src="script/bootstrap.bundle.min.js"></script>
<script src="script/script.js"></script>
</body>
</html>
