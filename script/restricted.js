$(document).ready(function() {
    $('#loginTable').DataTable({
        pageLength: 10,
        searching: false,
        lengthChange: false,
        info: false,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/sk.json'
        },
        columnDefs: [
            {
                targets: 0,  // Cieľový stĺpec (v tomto prípade 0 znamená prvý stĺpec)
                className: 'text-center'  // Pridá triedu na vycentrovanie
            }
        ],
        responsive: true, // Pridanie responzívnosti pre lepšiu zobrazenie na mobilných zariadeniach
        ordering: false, // Vypnutie zoradenia stĺpcov
    });
});

function setCookie(name, value, days) {
    const d = new Date();
    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
    document.cookie = `${name}=${value};expires=${d.toUTCString()};path=/`;
}

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
}

document.addEventListener("DOMContentLoaded", function () {
    if (!getCookie("cookie_consent")) {
        const banner = document.getElementById("cookieConsent");
        banner.classList.remove("d-none");

        document.getElementById("acceptCookies").addEventListener("click", function () {
            setCookie("cookie_consent", "accepted", 365);
            banner.classList.add("d-none");
        });
    }
});