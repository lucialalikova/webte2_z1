<?php

$config = require_once('config.php');

$db = connectDatabase($config['hostname'], $config['database'], $config['username'], $config['password']);

function processStatement($stmt) {
    if ($stmt->execute()) {
        return "New record created successfully";
    } else {
        return "Error: " . $stmt->errorInfo();
    }
}

function insertLaureate($db, $name, $surname, $organisation, $sex, $birth_year, $death_year) {
    $stmt = $db->prepare("INSERT INTO laureates (fullname, organisation, sex, birth_year, death_year) VALUES (:fullname, :organisation, :sex, :birth_year, :death_year)");

    $fullname = $name . " " . $surname;

    $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
    $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
    $stmt->bindParam(':sex', $sex, PDO::PARAM_STR);
    $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_STR);
    $stmt->bindParam(':death_year', $death_year, PDO::PARAM_STR);

    return processStatement($stmt);
}

function insertCountry($db, $country_name) {
    $stmt = $db->prepare("INSERT INTO countries (country_name) VALUES (:country_name)");

    $stmt->bindParam(':country_name', $country_name, PDO::PARAM_STR);

    return processStatement($stmt);
}

function boundCountry($db, $country_id, $laureate_id) {
    $stmt = $db->prepare("INSERT INTO laureates_countries (country_id, laureate_id) VALUES (:country_id, :laureate_id)");

    $stmt->bindParam(':country_id', $country_id, PDO::PARAM_INT);
    $stmt->bindParam(':laureate_id', $laureate_id, PDO::PARAM_INT);

    return processStatement($stmt);
}

function getLaureatesWithCountry($db) {
    $stmt = $db->prepare("
    SELECT laureates.fullname, laureates.sex, laureates.birth_year, laureates.death_year, countries.country_name 
    FROM laureates 
    LEFT JOIN laureates_countries 
        INNER JOIN countries
        ON laureates_countries.country_id = countries.id
    ON laureates.id = laureates_countries.laureate_id");

    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return $result;
}

function insertPrize($db, $year, $category, $contrib_sk, $contrib_en) {
    $stmt = $db->prepare("INSERT INTO prisez (year, category, contrib_sk, contrib_en) VALUES (:year, :category, :contrib_sk, :contrib_en)");

    $stmt->bindParam(':year', $year, PDO::PARAM_INT);
    $stmt->bindParam(':category', $category, PDO::PARAM_STR);
    $stmt->bindParam(':contrib_sk', $contrib_sk, PDO::PARAM_STR);
    $stmt->bindParam(':contrib_en', $contrib_en, PDO::PARAM_STR);

    if ($stmt->execute()) {
        return $db->lastInsertId();  // Vracia ID vloženej ceny
    } else {
        return "Error: " . $stmt->errorInfo();
    }
}

function insertLaureateWithCountry($db, $name, $surname, $organisation, $sex, $birth_year, $death_year, $country_name) {
    $db->beginTransaction();

    $status = insertLaureate($db, $name, $surname, $organisation, $sex, $birth_year, $death_year);

    if (strpos($status, "Error") !== false) {
        $db->rollBack();
        return $status;
    }

    $laureate_id = $db->lastInsertId();

    // Check if the country already exists
    $stmt = $db->prepare("SELECT id FROM countries WHERE country_name = :country_name");
    $stmt->bindParam(':country_name', $country_name, PDO::PARAM_STR);
    $stmt->execute();
    $country = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($country) {
        $country_id = $country['id'];
    } else {
        $status = insertCountry($db, $country_name);

        if (strpos($status, "Error") !== false) {
            $db->rollBack();
            return $status;
        }

        $country_id = $db->lastInsertId();
    }

    $status = boundCountry($db, $country_id, $laureate_id);

    if (strpos($status, "Error") !== false) {
        $db->rollBack();
        return $status;
    }

    $db->commit();

    return $status;
}

$status = insertLaureateWithCountry($db, "Peter", "Doe", NULL, "M", "1977", "2022", "Germany");

$result = getLaureatesWithCountry($db);
echo "<pre>";
print_r($result);
echo "</pre>";

//$status = insertLaureate($db, "Susane", "Doe", NULL, "F", "1988", "2022");
//$status = insertCountry($db, 'Norway');
//$status = boundCountry($db, 1, 10);
//echo $status;