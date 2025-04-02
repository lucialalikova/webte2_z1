<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <title>Detail laureáta</title>
    <link rel="stylesheet" href="../styles/bootstrap.min.css">
    <link rel="stylesheet" href="../styles/styles.css">
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
    <h1 class="mb-4 text-center">Detail laureáta</h1>

    <div id="laureate-details" class="mb-5 p-4" style="border: 2px solid #001f3d; background-color: #f2d1e1; border-radius: 10px;">
        <!-- Laureate details will be populated by JS -->
    </div>

    <h4 class="mb-3 text-center">Udelené ceny</h4>
    <div id="prizes" class="mb-4">
        <!-- Prizes will be populated by JS -->
    </div>

    <a href="index.php" class="btn btn-primary mt-4">← Späť na zoznam</a>
    <div class="text-center mt-5">
        <button id="delete-laureate" class="btn btn-danger">Vymazať laureáta</button>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const id = urlParams.get('id');

        if (id) {
            fetch(`/z1/z2/api/v0/laureates/${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data) {
                        const laureateDetails = $('#laureate-details');
                        const prizesContainer = $('#prizes');

                        const sex = data.sex === 'M' ? 'Muž' : (data.sex === 'F' ? 'Žena' : '');
                        const birthYear = data.birth_year ? data.birth_year : '';
                        const deathYear = data.death_year ? data.death_year : '';

                        laureateDetails.html(`
                        <h4 class="text-primary">${data.fullname || data.organisation}</h4>
                        <p class="mb-1 text-muted">${birthYear} - ${deathYear}</p>
                        ${data.fullname ? `<p><span class="fw-bold">Pohlavie:</span> ${sex}</p>` : ''}
                        <p><span class="fw-bold">Krajina:</span> ${data.countries}</p>
                    `);

                        if (data.prizes && data.prizes.length > 0) {
                            data.prizes.forEach(prize => {
                                prizesContainer.append(`
                                <div class="card shadow-sm mb-4" style="border: 2px solid #001f3d; background-color: #f2d1e1;">
                                    <div class="card-body">
                                        <h5 class="card-title">${prize.year} – ${prize.category}</h5>
                                        <p class="card-text"><span class="fw-bold">Príspevok:</span> ${prize.contrib_sk}</p>
                                        <p class="card-text"><span class="fw-bold">Contribution:</span> ${prize.contrib_en}</p>
                                        ${prize.category === 'Literatúra' ? `
                                            <hr>
                                            <p class="card-text"><span class="fw-bold">Jazyk:</span> ${prize.language_sk}</p>
                                            <p class="card-text"><span class="fw-bold">Language:</span> ${prize.language_en}</p>
                                            <p class="card-text"><span class="fw-bold">Žáner:</span> ${prize.genre_sk}</p>
                                            <p class="card-text"><span class="fw-bold">Genre:</span> ${prize.genre_en}</p>
                                        ` : ''}
                                    </div>
                                </div>
                            `);
                            });
                        } else {
                            prizesContainer.html('<p>Žiadne ceny neboli nájdené.</p>');
                        }
                    } else {
                        $('#laureate-details').html('<p>Laureát nebol nájdený.</p>');
                    }
                })
                .catch(error => {
                    console.error('Error fetching laureate details:', error);
                    $('#laureate-details').html('<p>Chyba pri načítaní údajov.</p>');
                });
        } else {
            $('#laureate-details').html('<p>Neplatné ID laureáta.</p>');
        }

        // Event listener pre vymazanie laureáta
        $('#delete-laureate').click(function() {
            if (confirm('Naozaj chcete vymazať tohto laureáta? Táto akcia je nezvratná.')) {
                fetch(`/z1/z2/api/v0/laureates/${id}`, {
                    method: 'DELETE',
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        alert('Laureát bol úspešne vymazaný.');
                        window.location.href = 'index.php'; // Presmerovanie späť na zoznam
                    })
                    .catch(error => {
                        console.error('Error deleting laureate:', error);
                        alert('Chyba pri vymazávaní laureáta.');
                    });
            }
        });
    });
</script>
</body>
</html>