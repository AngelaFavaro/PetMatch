const menuBtn = document.getElementById('mobile-menu');
const menu = document.getElementById('menu-admin');
const polaroids = document.querySelectorAll('#events-list .polaroid');

// const mainContent = document.querySelector('body#admin-body main');

// menuBtn.addEventListener('click', () => {
//     menu.classList.toggle('active');
// });

//funzione per far svolazzare le polaroid quando ci si passa sorpa
polaroids.forEach(card => {
    card.addEventListener('mouseenter', () => { //evento che scatta quando passo sopra con il mouse

        var now = Date.now(); //segno quand'è partita l'animazione

        if(card.classList.contains('is-swinging')){ //controllo se sta già svolazzando
            var lastStart = parseInt(card.dataset.animStart || 0); //recupera il momento in qui era partita 
            // l'animazione per quella specifica polaroid, se ancora non ci si era passati con il mouse, allora 
            // non possiede il valore e restituirebbe undefined, quindi utilizzo OR 0

            var timeSpended = now - lastStart; //so da quanto è partita l'animazione

            if(timeSpended<700){ //controllo che l'animazione sia partita da almeno 1 secondo
                return;//altrimenti esco
            }
            card.classList.remove('is-swinging'); //elimino la funzione e la faccio ripartire
        }

        card.dataset.animStart = now;
        
        var style = window.getComputedStyle(card); //serve per leggere la rotazione attuale della polaroid
        var matrix = new DOMMatrixReadOnly(style.transform); //estrae l'angolo dalla matrice di trasformazione
        var currentRotation = Math.round(Math.atan2(matrix.b, matrix.a) * (180/Math.PI));
        card.style.setProperty('--base-rot', currentRotation + 'deg'); //modifico i gradi
        
        card.classList.add('is-swinging'); //chiama is-swinging nel css che fa partire swingGeneric
    });

    card.addEventListener('animationend', () => { //scatta quando un animazione css con @keyframe finisce il ciclo
        card.classList.remove('is-swinging');
    });
});

//chiude il menu da telefono se non lo si fa manualmente e si passa oltre 

document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const menuCheckbox = document.getElementById('menu-toggle-checkbox');

    if (themeToggle && menuCheckbox) {
        
        themeToggle.addEventListener('focus', () => {
            menuCheckbox.checked = false;
        });
    }
});

//quando si passa sopra ai details della timeline, si aprono da soli. Mi serve per lasciarli chiusi quando si passa con il focus e 
//permettere allo screen reader di leggere i titoli prima di aprirli
document.addEventListener('DOMContentLoaded', () => {
    
    const detailsList = document.querySelectorAll('#timeline details');

    detailsList.forEach(detail => {

        if (window.innerWidth > 850 && !detail.open) { 
            
            detail.addEventListener('mouseenter', () => {
                if (!detail.open) {
                    detail.open = true;
                }
            });
    
            detail.addEventListener('mouseleave', () => {
                if (detail.open) {
                    detail.open = false;
                }
            });

        }
        
    });

    const navLinks = document.querySelectorAll('#lavora-con-noi .footer-submenu a');
    navLinks.forEach(link => {
        link.addEventListener('click', e => {
            if (window.location.pathname.endsWith('lavora-con-noi.html')) {
                e.preventDefault(); // blocca il reload
            }
        });
    });
});

//nascondi password e mostra password, cambia il type da password a text e viceversa
const toggleIcons = document.querySelectorAll('.password-container i');

toggleIcons.forEach(icon => {
    icon.addEventListener('click', function (e) {

        //this è l'icona cliccat, vado a cercare il fratello precedente 
        // (cioè) l'input dato che nell'html ho messo quest'ordine
        const passwordInput = this.previousElementSibling; 
        
        // Controllo di sicurezza: procedi solo se l'input esiste
        if (passwordInput) {
            //se ora è password, imposto text, se invece non lo è, imposto password
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            
            passwordInput.setAttribute('type', type);
            
            this.classList.toggle('fa-eye-slash'); //cambia l'icona, è presa da FontAwesome (link incluso nell'head)
        }
    });
});


//al caricamento della pagina mi blocca lo scroll per permettermi di tornare al form appena inviato istantaneamente
document.documentElement.style.scrollBehavior = 'auto';
setTimeout(function() { document.documentElement.style.scrollBehavior = 'smooth'; }, 500);

/* ==========================================================================
   FUNZIONI GLOBALI (Sempre disponibili)
   ========================================================================== */


document.addEventListener('DOMContentLoaded', () => {

    /* --- per il toggle menu del mobile --- */
    const menuBtn = document.getElementById('mobile-menu');
    const menu = document.getElementById('menu-admin');
    if (menuBtn && menu) {
        menuBtn.addEventListener('click', () => {
            menu.classList.toggle('active');
        });
    }

    const editNoteBtn = document.getElementById('edit-note');
    const noteText = document.getElementById('note-text');
    if (editNoteBtn && noteText) {
        let originalText = '';
        editNoteBtn.addEventListener('click', e => {
            e.preventDefault();
            originalText = noteText.innerText;
            noteText.contentEditable = 'true';
            noteText.classList.add('editing');
            noteText.focus();
        });

        noteText.addEventListener('blur', () => {
            noteText.contentEditable = 'false';
            noteText.classList.remove('editing');
            const nuovoTesto = noteText.innerText.trim();
            if (nuovoTesto !== originalText) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ salva_note: 1, note: nuovoTesto })
                });
            }
        });
    }

    /* --- colorazione pulsanti a seconda dello stato della richiesta*/
    const verificaStato = () => {
        const paragrafi = document.querySelectorAll('#Richiesta p');
        let statoTesto = "";
        paragrafi.forEach(p => {
            if (p.textContent.includes('Stato richiesta:')) {
                statoTesto = p.textContent.replace('Stato richiesta:', '').trim();
            }
        });

        if (statoTesto !== "" && statoTesto !== '[stato]') {
            if (statoTesto === 'Respinta' || statoTesto === 'Annullata') {
                const p1 = document.querySelector('#animal-container .orange-button');
                const p2 = document.querySelector('#details-container .orange-button');
                if (p1) { p1.classList.add('respinta'); p1.style.pointerEvents = 'none'; }
                if (p2) { p2.classList.add('respinta'); p2.style.pointerEvents = 'none'; }
            }
        }
    };
    verificaStato();

    /* --- modifica la data di arrivo TO DO DA MODIFICARE--- */
    const btnEditDate = document.getElementById('btn-attiva-modifica');
    const dateText = document.getElementById('data-text');
    const formDate = document.getElementById('form-data');
    const inputDate = document.getElementById('input-data');

    if (btnEditDate && dateText && formDate && inputDate) {
        btnEditDate.addEventListener('click', () => {
            dateText.classList.add('hidden');
            formDate.classList.remove('hidden');
            if (typeof inputDate.showPicker === 'function') inputDate.showPicker();
            inputDate.focus();
        });

        formDate.addEventListener('submit', (e) => {
            e.preventDefault();
            // Qui chiameresti la tua funzione sendData()
        });
}
});