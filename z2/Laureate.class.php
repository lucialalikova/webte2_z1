<?php

class Laureate {

    private $db;
    // TODO: Implement class field according to the database table.

    public function __construct($db) {
        $this->db = $db;
    }

    // Get all records
    public function index() {
        $sql = "SELECT
            l.id,
            l.fullname,
            l.organisation,
            l.sex,
            GROUP_CONCAT(DISTINCT c.country_name ORDER BY c.country_name SEPARATOR ', ') AS countries,
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
    public function store($sex, $birth_year, $death_year, $fullname = null, $organisation = null) {
        $stmt = $this->db->prepare("INSERT INTO laureates (fullname, organisation, sex, birth_year, death_year) VALUES (:fullname, :organisation, :sex, :birth_year, :death_year)");

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

        return $this->db->lastInsertId();
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
        // TODO: Check if there are any prizes only for this laureate, if so, delete them too
        // TODO: Check if exist
        $stmt = $this->db->prepare("DELETE FROM laureates WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }

        return 0;
    }

    // TODO: Implement method inserting more than one laureate.
    // TODO: Implement method for inserting laureate with Prize.
}
