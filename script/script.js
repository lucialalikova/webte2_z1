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

        const table = $('#laureates').DataTable();
        table.clear().draw();  // Vyčistí tabuľku

        data.forEach(laureate => {
            console.log("Processing laureate:", laureate);
            const sex = laureate.sex === 'F' ? 'Žena' : (laureate.sex === 'M' ? 'Muž' : '-');
            const birth_year = laureate.birth_year != 0 ? laureate.birth_year : '-';
            const death_year = laureate.death_year != 0 ? laureate.death_year : '-';
            const laureateName = laureate.fullname ?? laureate.organisation;
            const laureateLink = `<a href='/z1/z2/detail.php?id=${laureate.id}'>${laureateName}</a>`;

            if (laureate.prizes && laureate.prizes.length > 0) {
                laureate.prizes.forEach(prize => {
                    table.row.add([
                        prize.year ?? "Neznámy",
                        laureateLink,
                        sex,
                        laureate.countries ?? "Neznáma krajina",
                        birth_year,
                        death_year,
                        prize.category ?? "Neznáma"
                    ]).draw();
                });
            } else {
                table.row.add([
                    "Neznámy",
                    laureateLink,
                    sex,
                    laureate.countries ?? "Neznáma krajina",
                    birth_year,
                    death_year,
                    "Neznáma"
                ]).draw();
            }
        });

    } catch (error) {
        console.error('Error fetching laureates:', error);
    }
}

$(document).ready(function () {
    $('#laureates').DataTable({
        pageLength: 10,
        lengthMenu: [[10, 15, 20], [10, 15, 20]],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/sk.json'
        }
    });

    fetchLaureates();
});
