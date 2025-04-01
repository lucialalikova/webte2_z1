// Fetch API a inicializácia tabuľky
async function fetchLaureates() {
    try {
        const response = await fetch('/z1/z2/api/v0/laureates');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const data = await response.json();
        console.log("Fetched Data:", data);

        if (!Array.isArray(data)) {
            throw new Error("Invalid data format: Expected an array.");
        }

        const tableBody = $('#laureates tbody');
        tableBody.empty();

        data.forEach(laureate => {
            console.log("Processing laureate:", laureate);
            const sex = laureate.sex === 'F' ? 'Žena' : (laureate.sex === 'M' ? 'Muž' : 'ORG');
            const birth_year = laureate.birth_year != 0 ? laureate.birth_year : 'Not known';
            const death_year = laureate.death_year != 0 ? laureate.death_year : 'Still alive';

            const prizesInfo = laureate.prizes && laureate.prizes.length > 0
                ? laureate.prizes.map(p => `${p.year} (${p.category})`).join("<br>")
                : "Neznáme";

            const tableRow = `<tr style='background-color: #f2d1e1;'>
                <td>${prizesInfo}</td>
                <td>${laureate.fullname ?? laureate.organisation}</td>
                <td>${sex}</td>
                <td>${laureate.countries}</td>
                <td>${birth_year}</td>
                <td>${death_year}</td>
                <td>${laureate.prizes?.map(p => p.category).join(", ") || 'Neznáma'}</td>
            </tr>`;

            tableBody.append(tableRow);
        });

        // Znič starú tabuľku a inicializuj znova
        if ($.fn.DataTable.isDataTable('#laureates')) {
            $('#laureates').DataTable().destroy();
        }

        $('#laureates').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 15, 20], [10, 15, 20]],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/sk.json'
            }
        });

    } catch (error) {
        console.error('Error fetching laureates:', error);
    }
}

// Spusti funkciu po načítaní stránky
$(document).ready(function () {
    fetchLaureates();
});