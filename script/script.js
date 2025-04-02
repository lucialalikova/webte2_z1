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
            const sex = laureate.sex === 'F' ? 'Žena' : (laureate.sex === 'M' ? 'Muž' : '-');
            const birth_year = laureate.birth_year != 0 ? laureate.birth_year : '-';
            const death_year = laureate.death_year != 0 ? laureate.death_year : '-';
            const laureateName = laureate.fullname ?? laureate.organisation;
            const laureateLink = `<a href='/z1/z2/detail.php?id=${laureate.id}'>${laureateName}</a>`;

            // Ak laureát má viacero cien, vytvoríme viac riadkov
            if (laureate.prizes && laureate.prizes.length > 0) {
                laureate.prizes.forEach(prize => {
                    const tableRow = `<tr style='background-color: #f2d1e1;'>
                        <td>${prize.year}</td>
                        <td>${laureateLink}</td>
                        <td>${sex}</td>
                        <td>${laureate.countries}</td>
                        <td>${birth_year}</td>
                        <td>${death_year}</td>
                        <td>${prize.category}</td>
                    </tr>`;
                    tableBody.append(tableRow);
                });
            } else {
                // Ak laureát nemá cenu (nemalo by sa stať, ale pre istotu)
                const tableRow = `<tr style='background-color: #f2d1e1;'>
                    <td>Neznámy</td>
                    <td>${laureateLink}</td>
                    <td>${sex}</td>
                    <td>${laureate.countries}</td>
                    <td>${birth_year}</td>
                    <td>${death_year}</td>
                    <td>Neznáma</td>
                </tr>`;
                tableBody.append(tableRow);
            }
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