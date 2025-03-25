<?php

// Funkcia na spracovanie CSV súboru
function parseCSV($filename) {
    $handle = fopen($filename, "r");
    $data = array();

    while (($row = fgetcsv($handle, 0, ";")) !== FALSE) {
        $data[] = array_map('trim', $row); // Odstránenie medzier
    }

    fclose($handle);

    unset($data[0]); // Odstrániť hlavičku (prvý riadok)
    return $data;
}

/*// Načítanie dát zo súboru
$laureates = parseCSV("nobel_v5.2_FYZ.csv");

echo "<pre>";
print_r($laureates);
echo "</pre>";*/

?>