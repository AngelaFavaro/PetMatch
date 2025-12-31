/* Toggle mobile menu */

const menuBtn = document.getElementById('mobile-menu');
const menu = document.getElementById('menu-admin');

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


/* per le annotazioni nella pagina dettagli-richiesta  */

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
            note: nuovoTesto
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

        // Se il testo è ancora il placeholder [stato], non fare nulla e riprova tra poco
        if (statoTesto === '[stato]') return;

        if (statoTesto === 'Respinta' || statoTesto === 'Annullata') {
            const pulsante1= document.querySelector('#animal-container .orange-button');
            const pulsante2 = document.querySelector('#details-container .orange-button');

            if (!pulsante1 || !pulsante2) return; 

            pulsante1.classList.add('respinta');
            pulsante2.classList.add('respinta');
            pulsante1.style.pointerEvents = 'none'; // disabilita il click
            pulsante2.style.pointerEvents = 'none'; // disabilita il click
            console.log("Classi applicate con successo.");
        }
    };

    // Esegui subito
    verificaStato();

});

/* per la modifica della data di arrivo nella pagina dettagli-richiesta  */

let isAlertActive = false;

const btnEdit = document.getElementById('btn-attiva-modifica');
const dateText2 = document.getElementById('data-text');
const formDate = document.getElementById('form-data');
const inputDate = document.getElementById('input-data');
const endEvaluationDateItalianFormat = document.getElementById('data-fine-valutazione');

btnEdit.addEventListener('click', () => {
    mostraEditor();
});


function mostraEditor() {
    dateText2.classList.add('hidden');
    formDate.classList.remove('hidden');
    
    // Apre automaticamente il calendario sui browser moderni
    if (typeof inputDate.showPicker === 'function') {
        inputDate.showPicker();
    }
    inputDate.focus();
}


inputDate.addEventListener('blur', (e) => {
    if (document.activeElement !== inputDate) {
        sendData();
    }
});
// submit del form ha classe sr-only quindi un utente fisico non vede il bottone ma uno screen reader sì (per essere accessibile deve esserci un pulsante)
formDate.addEventListener('submit', (e) => {
    e.preventDefault();
    sendData();
});


function sendData() {
    if (isAlertActive) return;

    const newDate = inputDate.value;
    
    if (!newDate) {
        dateText2.classList.remove('hidden');
        formDate.classList.add('hidden');
        return;
    }

    if (endEvaluationDateItalianFormat) {
        const dateString = endEvaluationDateItalianFormat.innerText.trim();

        if (dateString !== '') {
            const dataAppoggio = dateString.split('/');

            const endEvalDate = new Date(dataAppoggio[2], dataAppoggio[1] - 1, dataAppoggio[0]);
            const newArrDate = new Date(newDate);

            if (newArrDate < endEvalDate) {
                isAlertActive = true;
                
                alert("Errore: La data di arrivo non può essere precedente alla data di fine valutazione (" + dateString + ").");
                
                setTimeout(() => {
                    isAlertActive = false;
                    if (typeof inputDate.showPicker === 'function') {
                        inputDate.showPicker();
                    }
                    inputDate.focus();
                }, 100);
                
                return; 
            }
        }
    }

    dateText2.classList.remove('hidden');
    formDate.classList.add('hidden');

    // per formattare la data in gg/mm/aaaa (lo fa anche il php ma richiede il reload della pagina, quindi per sopperire a questo lo faccio ache in js)
    const partiDisplay = newDate.split('-');
    dateText2.innerText = `${partiDisplay[2]}/${partiDisplay[1]}/${partiDisplay[0]}`;

    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            salva_data_arrivo: 1,
            data_arrivo: newDate
        })
    });
}