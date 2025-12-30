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
    if (nuovoTesto === originalText) return; // niente POST inutile

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
    // prendo lo stato della richiesta
    const statoP = document.querySelector('#Richiesta p:first-child');
    if (!statoP) return;

    const statoTesto = statoP.innerText.replace('Stato richiesta:', '').trim();

    // prendo il pulsante
    const pulsante1 = document.querySelector('#top-container .orange-button');
    const pulsante2 = document.querySelector('#animal-container .orange-button');
    const pulsante3 = document.querySelector('#details-container .orange-button');
    if (!pulsante1 || !pulsante2 || !pulsante3) return;

    // se è respinta, aggiungo classe e disabilito click
    if (statoTesto === 'Respinta') {
        pulsante1.classList.add('respinta');
        pulsante2.classList.add('respinta');
        pulsante3.classList.add('respinta');
        pulsante1.style.pointerEvents = 'none'; // disabilita il click
        pulsante1.style.opacity = '0.6';        // aspetto visivo di disabilitato
        pulsante2.style.pointerEvents = 'none'; // disabilita il click
        pulsante2.style.opacity = '0.6';        // aspetto visivo di disabilitato
        pulsante3.style.pointerEvents = 'none'; // disabilita il click
        pulsante3.style.opacity = '0.6';        // aspetto visivo di disabilitato
    }
});

