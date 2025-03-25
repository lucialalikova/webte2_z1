<?php

require_once('parser.php');
$config = require_once('config.php');

$db = connectDatabase($config['hostname'], $config['database'], $config['username'], $config['password']);

// Funkcia pre spracovanie SQL príkazov
function processStatement($stmt) {
    if ($stmt->execute()) {
        return "New record created successfully";
    } else {
        return "Error: " . $stmt->errorInfo();
    }
}

// Funkcie na vloženie laureáta, krajiny atď.
function insertLaureate($db, $firstname, $lastname, $organisation, $sex, $birth_year, $death_year) {
    $fullname = ($firstname === null && $lastname === null) ? trim($organisation ?? '') : trim(($firstname ?? '') . ' ' . ($lastname ?? ''));

    // Ak organizácia je prázdna, nastav ju na NULL
    /*$organisation = !empty(trim($organisation)) ? trim($organisation) : null;
    $fullname = !empty(trim($fullname)) ? trim($fullname) : null;*/

    // Skontrolujeme, či už laureát existuje
    $stmt = $db->prepare("SELECT id FROM laureates WHERE fullname = :fullname AND (organisation = :organisation OR (organisation IS NULL AND :organisation IS NULL))");
    $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
    $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
    $stmt->execute();
    $laureate = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($laureate) {
        return $laureate['id'];
    } else {
        // Vložíme nového laureáta
        $stmt = $db->prepare("INSERT INTO laureates (fullname, organisation, sex, birth_year, death_year)
                              VALUES(:fullname, :organisation, :sex, :birth_year, :death_year)");

        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
        $stmt->bindParam(':sex', $sex, PDO::PARAM_STR);
        $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_INT);
        $stmt->bindParam(':death_year', $death_year, PDO::PARAM_INT);

        processStatement($stmt);
        return $db->lastInsertId();
    }
}


function insertCountry($db, $country_name) {
    $stmt = $db->prepare("SELECT id FROM countries WHERE country_name = :country_name");
    $stmt->bindParam(':country_name', $country_name, PDO::PARAM_STR);
    $stmt->execute();
    $countries = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($countries) {
        return $countries['id'];
    } else {
        $stmt = $db->prepare("INSERT INTO countries (country_name) VALUES (:country_name)");
        $stmt->bindParam(':country_name', $country_name, PDO::PARAM_STR);

        processStatement($stmt);
        return $db->lastInsertId();
    }
}

function boundCountry($db, $country_id, $laureates_id) {
    $stmt = $db->prepare("INSERT INTO laureates_countries (country_id, laureates_id) 
                          VALUES (:country_id, :laureates_id)");
    $stmt->bindParam(':country_id', $country_id, PDO::PARAM_INT);
    $stmt->bindParam(':laureates_id', $laureates_id, PDO::PARAM_INT);

    return processStatement($stmt);
}

function getLaureateWithCountry($db) {
    $stmt = $db->prepare("SELECT laureates.fullname, laureates.sex, laureates.birth_year,
    laureates.death_year, countries.country_name FROM laureates
    LEFT JOIN laureates_countries
        INNER JOIN countries
        ON laureates_countries.country_id = countries.id
    ON laureates.id = laureates_countries.laureates_id");

    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
}


function insertPrize($db, $year, $category, $contrib_sk, $contrib_en, $details_id) {
    $stmt = $db->prepare("INSERT INTO prizes (year, category, contrib_sk, contrib_en, details_id) 
                          VALUES (:year, :category, :contrib_sk, :contrib_en, :details_id)");

    $stmt->bindParam(':year', $year, PDO::PARAM_INT);
    $stmt->bindParam(':category', $category, PDO::PARAM_STR);
    $stmt->bindParam(':contrib_sk', $contrib_sk, PDO::PARAM_STR);
    $stmt->bindParam(':contrib_en', $contrib_en, PDO::PARAM_STR);
    $stmt->bindParam(':details_id', $details_id, PDO::PARAM_INT);

    processStatement($stmt);
    return $db->lastInsertId();
}

function insertPrize_details($db, $language_sk, $language_en, $genre_sk, $genre_en) {
    $stmt = $db->prepare("INSERT INTO prize_details (language_sk, language_en, genre_sk, genre_en)
                          VALUES (:language_sk, :language_en, :genre_sk, :genre_en)");

    $stmt->bindParam(':language_sk', $language_sk, PDO::PARAM_STR);
    $stmt->bindParam(':language_en', $language_en, PDO::PARAM_STR);
    $stmt->bindParam(':genre_sk', $genre_sk, PDO::PARAM_STR);
    $stmt->bindParam(':genre_en', $genre_en, PDO::PARAM_STR);

    if ($stmt->execute()) {
        return $db->lastInsertId();
    } else {
        print_r($stmt->errorInfo()); // Výpis SQL chyby
        return null;
    }
}


function boundPrize($db, $prize_id, $laureates_id) {
    $stmt = $db->prepare("INSERT INTO laureates_prizes (prize_id, laureates_id) 
                          VALUES (:prize_id, :laureates_id)");
    $stmt->bindParam(':prize_id', $prize_id, PDO::PARAM_INT);
    $stmt->bindParam(':laureates_id', $laureates_id, PDO::PARAM_INT);

    return processStatement($stmt);
}

//********************************************************************************************
// Automaticky spusti import pri načítaní stránky
$laureates = parseCSV('nobel_v5.2_LIT.csv'); // Funkcia na načítanie dát z CSV

foreach ($laureates as $laureate) {
    $year = $laureate[0];
    $firstname = $laureate[1] ?? null;
    $lastname = $laureate[2] ?? null;
    $organisation = null;
    $sex = $laureate[3] ?? null;
    $birth_year = $laureate[4] ?? null;
    $death_year = $laureate[5] ?? null;
    $country_names = explode(',', $laureate[6]); // Krajiny môžu byť oddelené čiarkami
    $contrib_sk = $laureate[7];
    $contrib_en = $laureate[8];
    $category = "Literatúra";
    if ($category === "Literatúra") {
        $language_sk = $laureate[9];
        $language_en = $laureate[10];
        $genre_sk = $laureate[11];
        $genre_en = $laureate[12];
    }


        try {
            $db->beginTransaction(); // Začiatok transakcie pre celý import

            // Vložíme laureáta
            $laureates_id = insertLaureate($db, $firstname, $lastname, $organisation, $sex, $birth_year, $death_year);

            // Pre každú krajinu vložíme prepojenie
            foreach ($country_names as $country_name) {
                $country_name = trim($country_name); // Odstránime medzery
                $country_id = insertCountry($db, $country_name);
                boundCountry($db, $country_id, $laureates_id);
            }

            // Vloženie detailov ceny (iba ak ide o kategóriu Literatúra)
            if ($category === "Literatúra") {
                echo "Vkladám prize_details: $language_sk, $language_en, $genre_sk, $genre_en\n";
                $details_id = insertPrize_details($db, $language_sk, $language_en, $genre_sk, $genre_en);
            } else {
                $details_id = null;
            }

            // Vloženie ceny a prepojenie s laureátom
            $prize_id = insertPrize($db, $year, $category, $contrib_sk, $contrib_en, $details_id);
            boundPrize($db, $prize_id, $laureates_id);

            $db->commit(); // Potvrdenie transakcie

        } catch (Exception $e) {
            $db->rollBack(); // Rollback v prípade chyby
            echo "Chyba: " . $e->getMessage(); // Výpis chyby
        }

}

// Získanie laureátov s krajiny
$result = getLaureateWithCountry($db);
echo "<pre>";
print_r($result);
echo "</pre>";


