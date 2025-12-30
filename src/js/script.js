const menuBtn = document.getElementById('mobile-menu');
const menu = document.getElementById('menu-admin');
// const mainContent = document.querySelector('body#admin-body main');

menuBtn.addEventListener('click', () => {
    menu.classList.toggle('active');
});



function openTab(evt, tabName) {
  // Declare all variables
  var i, tabcontent, tablinks;

  // Get all elements with class="tabcontent" and hide them
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }

  // Get all elements with class="tablinks" and remove the class "active"
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }

  // Show the current tab, and add an "active" class to the button that opened the tab
  document.getElementById(tabName).style.display = "block";
  evt.currentTarget.className += " active";
}


const editBtn = document.getElementById('edit-note');
const note = document.getElementById('note-text');

let originalText = '';

editBtn.addEventListener('click', e => {
    e.preventDefault();
    originalText = note.innerText;
    note.contentEditable = 'true';
    note.classList.add('editing');
    note.focus();
});

note.addEventListener('blur', () => {
    note.contentEditable = 'false';
    note.classList.remove('editing');

    const nuovoTesto = note.innerText.trim();
    if (nuovoTesto === originalText) return;

    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            salva_note: 1,
            note: nuovoTesto,
            email: note.dataset.email,
            id_animale: note.dataset.idAnimale
        })
    });
});
document.addEventListener('DOMContentLoaded', () => {
    // Funzione per controllare lo stato
    const verificaStato = () => {
        // Seleziona il paragrafo che contiene "Stato richiesta"
        const paragrafi = document.querySelectorAll('#Richiesta p');
        let statoTesto = "";

        paragrafi.forEach(p => {
            if (p.textContent.includes('Stato richiesta:')) {
                // Prende tutto il testo del paragrafo e rimuove l'etichetta
                statoTesto = p.textContent.replace('Stato richiesta:', '').trim();
            }
        });

        console.log("Stato rilevato:", statoTesto);

        // Se il testo è ancora il placeholder [stato], non fare nulla e riprova tra poco
        if (statoTesto === '[stato]') return;

        if (statoTesto === 'Respinta' || statoTesto === 'Annullata') {
            const pulsante1= document.querySelector('#animal-container .orange-button');
            const pulsante2 = document.querySelector('#details-container .orange-button');

            if (!pulsante1 || !pulsante2) return; 

            pulsante1.classList.add('respinta');
            pulsante2.classList.add('respinta');
            pulsante1.style.pointerEvents = 'none'; // disabilita il click
            // pulsante1.style.opacity = '0.6'; // aspetto visivo di disabilitato
            pulsante2.style.pointerEvents = 'none'; // disabilita il click
            // pulsante2.style.opacity = '0.6'; // aspetto visivo di disabilitato 
            console.log("Classi applicate con successo.");
        }
    };

    // Esegui subito
    verificaStato();

    // Se i dati vengono caricati via PHP o AJAX dopo il caricamento della pagina,
    // usiamo un piccolo timeout o MutationObserver per essere sicuri.
    setTimeout(verificaStato, 500); 
});


const editDataBtn = document.getElementById('edit-data');
const dataText = document.getElementById('data-arrivo-text');

let originalData = '';

if (editDataBtn && dataText) {
    editDataBtn.addEventListener('click', e => {
        e.preventDefault();
        originalData = dataText.innerText.trim();
        dataText.contentEditable = 'true';
        dataText.classList.add('editing');
        dataText.focus();
    });

    dataText.addEventListener('blur', () => {
        dataText.contentEditable = 'false';
        dataText.classList.remove('editing');

        const nuovaData = dataText.innerText.trim();
        if (nuovaData === originalData) return;

        // Invio dei dati al server
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                salva_data_arrivo: 1, // Parametro per distinguere l'azione in PHP
                data_arrivo: nuovaData,
                email: dataText.dataset.email,
                id_animale: dataText.dataset.idAnimale
            })
        })
        .then(response => {
            if (!response.ok) alert("Errore durante il salvataggio");
        });
    });
}