<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Pridať laureáta</title>
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
                <a class="nav-link" href="index.php">Laureáti</a>
            </div>

            <div class="d-flex ms-auto">
                <?php if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true): ?>
                    <a class="btn btn-outline-light me-2" href="../login.php">Prihlásiť sa</a>
                    <a class="btn btn-light" href="../register.php">Zaregistrovať sa</a>
                <?php else: ?>
                    <a class="btn btn-outline-light me-2" href="../restricted.php">Môj profil</a>
                    <a class="btn btn-light" href="../logout.php">Odhlásiť sa</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main class="container mt-4">
    <h1 class="mb-4 text-center">Pridať laureáta</h1>
    <form id="add-laureate-form">
        <div class="mb-3">
            <label for="fullname" class="form-label">Celé meno</label>
            <input type="text" class="form-control" id="fullname" name="fullname">
        </div>
        <div class="mb-3">
            <label for="organisation" class="form-label">Organizácia</label>
            <input type="text" class="form-control" id="organisation" name="organisation">
        </div>
        <div class="mb-3">
            <label for="sex" class="form-label">Pohlavie</label>
            <select class="form-control" id="sex" name="sex">
                <option value="M">Muž</option>
                <option value="F">Žena</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="birth_year" class="form-label">Rok narodenia</label>
            <input type="number" class="form-control" id="birth_year" name="birth_year">
        </div>
        <div class="mb-3">
            <label for="death_year" class="form-label">Rok úmrtia</label>
            <input type="number" class="form-control" id="death_year" name="death_year">
        </div>
        <div class="mb-3">
            <label for="countries" class="form-label">Krajiny</label>
            <input type="text" class="form-control" id="countries" name="countries">
        </div>
        <div id="prizes-container">
            <h4>Cena</h4>
            <div class="mb-3">
                <label for="prize_year" class="form-label">Rok udelenia ceny</label>
                <input type="number" class="form-control" id="prize_year" name="prize_year">
            </div>
            <div class="mb-3">
                <label for="prize_category" class="form-label">Kategória</label>
                <select class="form-control" id="prize_category" name="prize_category">
                    <option value="Mier">Mier</option>
                    <option value="Fyzika">Fyzika</option>
                    <option value="Chémia">Chémia</option>
                    <option value="Literatúra">Literatúra</option>
                    <option value="Medicína">Medicína</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="contrib_sk" class="form-label">Príspevok (SK)</label>
                <textarea class="form-control" id="contrib_sk" name="contrib_sk"></textarea>
            </div>
            <div class="mb-3">
                <label for="contrib_en" class="form-label">Contribution (EN)</label>
                <textarea class="form-control" id="contrib_en" name="contrib_en"></textarea>
            </div>
            <div id="literature-details" style="display: none;">
                <div class="mb-3">
                    <label for="language_sk" class="form-label">Jazyk (SK)</label>
                    <input type="text" class="form-control" id="language_sk" name="language_sk">
                </div>
                <div class="mb-3">
                    <label for="language_en" class="form-label">Language (EN)</label>
                    <input type="text" class="form-control" id="language_en" name="language_en">
                </div>
                <div class="mb-3">
                    <label for="genre_sk" class="form-label">Žáner (SK)</label>
                    <input type="text" class="form-control" id="genre_sk" name="genre_sk">
                </div>
                <div class="mb-3">
                    <label for="genre_en" class="form-label">Genre (EN)</label>
                    <input type="text" class="form-control" id="genre_en" name="genre_en">
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary">Pridať laureáta</button>
    </form>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#prize_category').on('change', function() {
            if ($(this).val() === 'Literatúra') {
                $('#literature-details').show();
            } else {
                $('#literature-details').hide();
            }
        });

        $('#add-laureate-form').on('submit', function(event) {
            event.preventDefault();
            const formData = {
                fullname: $('#fullname').val(),
                organisation: $('#organisation').val(),
                sex: $('#sex').val(),
                birth_year: $('#birth_year').val(),
                death_year: $('#death_year').val(),
                countries: $('#countries').val(),
                prizes: [{
                    year: $('#prize_year').val(),
                    category: $('#prize_category').val(),
                    contrib_sk: $('#contrib_sk').val(),
                    contrib_en: $('#contrib_en').val(),
                    language_sk: $('#language_sk').val(),
                    language_en: $('#language_en').val(),
                    genre_sk: $('#genre_sk').val(),
                    genre_en: $('#genre_en').val()
                }]
            };

            fetch('/z1/z2/api/v0/laureates', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.message === "Created successfully") {
                        alert('Laureát bol úspešne pridaný.');
                        window.location.href = 'index.php';
                    } else {
                        alert('Chyba pri pridávaní laureáta: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Chyba pri pridávaní laureáta.');
                });
        });
    });
</script>
</body>
</html>