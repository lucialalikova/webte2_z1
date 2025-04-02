<?php

class Laureate {

    private $db;
    // TODO: Implement class field according to the database table.

    public function __construct($db) {
        $this->db = $db;
    }

    public function getLaureateDetails($id) {
        $sql = "SELECT
            l.id,
            l.fullname,
            l.organisation,
            l.sex,
            GROUP_CONCAT(DISTINCT c.country_name ORDER BY c.country_name SEPARATOR ', ') AS countries,
            l.birth_year,
            l.death_year
        FROM laureates l
        LEFT JOIN laureates_countries lc ON l.id = lc.laureates_id
        LEFT JOIN countries c ON lc.country_id = c.id
        WHERE l.id = ?
        GROUP BY l.id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $laureate = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($laureate) {
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

            $stmtPrizes = $this->db->prepare($sqlPrizes);
            $stmtPrizes->execute([$id]);
            $prizes = $stmtPrizes->fetchAll(PDO::FETCH_ASSOC);

            $laureate['prizes'] = $prizes;
        }

        return $laureate;
    }

    // Get all records
    public function index() {
        $sql = "SELECT
            l.id,
            l.fullname,
            l.organisation,
            l.sex,
            GROUP_CONCAT(DISTINCT COALESCE(c.country_name, 'Neznáma krajina') ORDER BY c.country_name SEPARATOR ', ') AS countries,
            l.birth_year,
            l.death_year
        FROM
            laureates l
        JOIN
            laureates_countries lc ON l.id = lc.laureates_id
        JOIN
            countries c ON lc.country_id = c.id
        GROUP BY
            l.id, l.fullname, l.organisation, l.sex, l.birth_year, l.death_year";

        $stmt = $this->db->prepare($sql);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }

        $laureates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group prizes by laureate
        $result = [];
        foreach ($laureates as $laureate) {
            $id = $laureate['id'];
            if (!isset($result[$id])) {
                $result[$id] = [
                    'id' => $laureate['id'],
                    'fullname' => $laureate['fullname'],
                    'organisation' => $laureate['organisation'],
                    'sex' => $laureate['sex'],
                    'birth_year' => $laureate['birth_year'],
                    'death_year' => $laureate['death_year'],
                    'countries' => $laureate['countries'],
                    'prizes' => []
                ];
            }

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

            $stmtPrizes = $this->db->prepare($sqlPrizes);
            $stmtPrizes->execute([$id]);
            $prizes = $stmtPrizes->fetchAll(PDO::FETCH_ASSOC);

            foreach ($prizes as $prize) {
                $result[$id]['prizes'][] = $prize;
            }
        }

        return array_values($result);
    }

    // Get one record
    public function show($id) {
        // TODO: Implement Where caluse by fullname, organisation...
        $stmt = $this->db->prepare("SELECT * FROM laureates WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Create a new record
    public function store($sex, $birth_year, $death_year, $fullname = null, $organisation = null, $countries = null, $prizes = []) {
        $stmt = $this->db->prepare("INSERT INTO laureates (fullname, organisation, sex, birth_year, death_year) VALUES (:fullname, :organisation, :sex, :birth_year, :death_year)");

        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
        $stmt->bindParam(':sex', $sex, PDO::PARAM_STR);
        $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_INT);
        $stmt->bindParam(':death_year', $death_year, PDO::PARAM_INT);

        try {
            $stmt->execute();
            $laureateId = $this->db->lastInsertId();

            // Insert countries
            if ($countries) {
                $countryList = explode(',', $countries);
                foreach ($countryList as $country) {
                    $country = trim($country);

                    // Najprv skúsime nájsť ID krajiny
                    $stmtCountry = $this->db->prepare("SELECT id FROM countries WHERE country_name = :country_name");
                    $stmtCountry->bindParam(':country_name', $country, PDO::PARAM_STR);
                    $stmtCountry->execute();
                    $countryId = $stmtCountry->fetchColumn();

                    if (!$countryId) {
                        $stmtCountryInsert = $this->db->prepare("INSERT INTO countries (country_name) VALUES (:country_name)");
                        $stmtCountryInsert->bindParam(':country_name', $country, PDO::PARAM_STR);
                        $stmtCountryInsert->execute();
                        $countryId = $this->db->lastInsertId();
                    }

                    // Prepojíme laureáta s krajinou
                    $stmtLaureateCountry = $this->db->prepare("INSERT INTO laureates_countries (laureates_id, country_id) VALUES (:laureates_id, :country_id)");
                    $stmtLaureateCountry->bindParam(':laureates_id', $laureateId, PDO::PARAM_INT);
                    $stmtLaureateCountry->bindParam(':country_id', $countryId, PDO::PARAM_INT);
                    $stmtLaureateCountry->execute();
                }
            }
            // Insert prizes
            foreach ($prizes as $prize) {
                // Najprv skontrolujeme, či cena už existuje
                $stmtCheckPrize = $this->db->prepare("SELECT id FROM prizes WHERE year = :year AND category = :category");
                $stmtCheckPrize->bindParam(':year', $prize['year'], PDO::PARAM_STR);
                $stmtCheckPrize->bindParam(':category', $prize['category'], PDO::PARAM_STR);
                $stmtCheckPrize->execute();
                $prizeId = $stmtCheckPrize->fetchColumn();

                // Ak cena neexistuje, vložíme ju
                if (!$prizeId) {
                    if ($prize['language_sk'] || $prize['language_en'] || $prize['genre_sk'] || $prize['genre_en']) {
                        $stmtDetails = $this->db->prepare("INSERT INTO prize_details (language_sk, language_en, genre_sk, genre_en) VALUES (:language_sk, :language_en, :genre_sk, :genre_en)");
                        $stmtDetails->bindParam(':language_sk', $prize['language_sk'], PDO::PARAM_STR);
                        $stmtDetails->bindParam(':language_en', $prize['language_en'], PDO::PARAM_STR);
                        $stmtDetails->bindParam(':genre_sk', $prize['genre_sk'], PDO::PARAM_STR);
                        $stmtDetails->bindParam(':genre_en', $prize['genre_en'], PDO::PARAM_STR);
                        $stmtDetails->execute();
                        $detailsId = $this->db->lastInsertId();
                    } else {
                        $detailsId = null;
                    }

                    $stmtPrize = $this->db->prepare("INSERT INTO prizes (year, category, contrib_sk, contrib_en, details_id) VALUES (:year, :category, :contrib_sk, :contrib_en, :details_id)");
                    $stmtPrize->bindParam(':year', $prize['year'], PDO::PARAM_STR);
                    $stmtPrize->bindParam(':category', $prize['category'], PDO::PARAM_STR);
                    $stmtPrize->bindParam(':contrib_sk', $prize['contrib_sk'], PDO::PARAM_STR);
                    $stmtPrize->bindParam(':contrib_en', $prize['contrib_en'], PDO::PARAM_STR);
                    $stmtPrize->bindParam(':details_id', $detailsId, PDO::PARAM_INT);
                    $stmtPrize->execute();
                    $prizeId = $this->db->lastInsertId();
                }

                // Prepojíme laureáta s cenou
                $stmtLaureatePrize = $this->db->prepare("INSERT INTO laureates_prizes (laureates_id, prize_id) VALUES (:laureates_id, :prize_id)");
                $stmtLaureatePrize->bindParam(':laureates_id', $laureateId, PDO::PARAM_INT);
                $stmtLaureatePrize->bindParam(':prize_id', $prizeId, PDO::PARAM_INT);
                $stmtLaureatePrize->execute();
            }
            return $laureateId;
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    }

    // Update a record
    public function update($id, $sex, $birth_year, $death_year, $fullname = null, $organisation = null) {
        $stmt = $this->db->prepare("UPDATE laureates SET fullname = :fullname, organisation = :organisation, sex = :sex, birth_year = :birth_year, death_year = :death_year WHERE id = :id");

        // TODO: Where clause by fullname or organisation

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
        $stmt->bindParam(':sex', $sex, PDO::PARAM_STR);
        $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_INT);
        $stmt->bindParam(':death_year', $death_year, PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
        return 0;
    }

    // Delete a record
    public function destroy($id) {
        try {
            // Begin transaction
            $this->db->beginTransaction();

            // Delete related records in laureates_countries
            $stmtCountries = $this->db->prepare("DELETE FROM laureates_countries WHERE laureates_id = :id");
            $stmtCountries->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtCountries->execute();

            // Get all prize IDs associated with the laureate
            $stmtPrizeIds = $this->db->prepare("SELECT prize_id FROM laureates_prizes WHERE laureates_id = :id");
            $stmtPrizeIds->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtPrizeIds->execute();
            $prizeIds = $stmtPrizeIds->fetchAll(PDO::FETCH_COLUMN, 0);

            // Delete related records in laureates_prizes
            $stmtPrizes = $this->db->prepare("DELETE FROM laureates_prizes WHERE laureates_id = :id");
            $stmtPrizes->bindParam(':id', $id, PDO::PARAM_INT);
            $stmtPrizes->execute();

            // Delete the laureate
            $stmt = $this->db->prepare("DELETE FROM laureates WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            // Check if any of the prizes are not associated with any other laureate and delete them
            foreach ($prizeIds as $prizeId) {
                $stmtCheckPrize = $this->db->prepare("SELECT COUNT(*) FROM laureates_prizes WHERE prize_id = :prize_id");
                $stmtCheckPrize->bindParam(':prize_id', $prizeId, PDO::PARAM_INT);
                $stmtCheckPrize->execute();
                $count = $stmtCheckPrize->fetchColumn();

                if ($count == 0) {
                    // Check if the prize category is "Literatúra"
                    $stmtPrizeCategory = $this->db->prepare("SELECT category, details_id FROM prizes WHERE id = :prize_id");
                    $stmtPrizeCategory->bindParam(':prize_id', $prizeId, PDO::PARAM_INT);
                    $stmtPrizeCategory->execute();
                    $prize = $stmtPrizeCategory->fetch(PDO::FETCH_ASSOC);

                    if ($prize['category'] === 'Literatúra' && $prize['details_id']) {
                        // Delete the prize
                        $stmtDeletePrize = $this->db->prepare("DELETE FROM prizes WHERE id = :prize_id");
                        $stmtDeletePrize->bindParam(':prize_id', $prizeId, PDO::PARAM_INT);
                        $stmtDeletePrize->execute();

                        // Delete prize details
                        $stmtDeleteDetails = $this->db->prepare("DELETE FROM prize_details WHERE id = :details_id");
                        $stmtDeleteDetails->bindParam(':details_id', $prize['details_id'], PDO::PARAM_INT);
                        $stmtDeleteDetails->execute();
                    } else {
                        // Delete the prize
                        $stmtDeletePrize = $this->db->prepare("DELETE FROM prizes WHERE id = :prize_id");
                        $stmtDeletePrize->bindParam(':prize_id', $prizeId, PDO::PARAM_INT);
                        $stmtDeletePrize->execute();
                    }
                }
            }

            // Commit transaction
            $this->db->commit();
        } catch (PDOException $e) {
            // Rollback transaction in case of error
            $this->db->rollBack();
            return "Error: " . $e->getMessage();
        }

        return 0;
    }

    // TODO: Implement method inserting more than one laureate.
    // TODO: Implement method for inserting laureate with Prize.
}
