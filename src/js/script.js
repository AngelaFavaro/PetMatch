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



//evita di ricaricare la pagina quando di mettono i like
document.addEventListener('DOMContentLoaded', () => {
    
    const likeForms = document.querySelectorAll('.preferiti-form');

    likeForms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault(); // Blocca il refresh

            // Recupera gli elementi
            const btn = form.querySelector('button');
            const imgNormal = btn.querySelector('.heart-normal');
            const imgHover = btn.querySelector('.heart-hover');
            
            // Per accessibilità: recupera il nome dell'animale dalla card
            const cardContent = form.closest('.card-content');
            const nomeAnimale = cardContent ? cardContent.querySelector('.nome').innerText : 'animale';

            const formData = new FormData(form);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    console.log("Risposta Server:", data);

                    if (data.status === 'success') {
                        
                        //scambio delle due immagini
                        const tempSrc = imgNormal.src;
                        imgNormal.src = imgHover.src;
                        imgHover.src = tempSrc;

                        // --- AGGIORNA ACCESSIBILITÀ ---
                        if (data.azione === 'aggiunto') {
                            btn.classList.remove('not-favorite');
                            btn.classList.add('is-favorite');
                            btn.setAttribute('aria-label', `Rimuovi ${nomeAnimale} dai preferiti`);
                        } else {
                            btn.classList.remove('is-favorite');
                            btn.classList.add('not-favorite');
                            btn.setAttribute('aria-label', `Aggiungi ${nomeAnimale} ai preferiti`);
                        }
                    }
                } else {
                    console.error("Errore server:", response.status);
                }
            } catch (error) {
                console.error('Errore durante la fetch:', error);
            }
        });
    });
});



/**cambia il colore dei pulsanti per abbellimento: rende più visibile lo stato della richiesta */
document.addEventListener('DOMContentLoaded', () => {
    const verificaStato = () => {
        const termini = document.querySelectorAll('#Richiesta dt'); //cerca tutti i dt dentro l'article#Richiesta (che ha lo stato)
        let statoTesto = "";

        termini.forEach(dt => {
            if (dt.textContent.trim() === 'Stato richiesta') { //cerca il dt che contiene "Stato richiesta"
                const ddValue = dt.nextElementSibling; //prende il dd successivo per estrerre il valore
                if (ddValue) {
                    statoTesto = ddValue.textContent.trim();
                }
            }
        });

        if (statoTesto !== "" && statoTesto !== '[stato]') {
            const statiNegativi = ['Respinta', 'Annullata'];
            
            if (statiNegativi.includes(statoTesto)) {
                const p1 = document.querySelector('#animal-container .orange-button');
                const p2 = document.querySelector('#details-container .orange-button');
                
                [p1, p2].forEach(p => {
                    if (p) {
                        p.classList.add('respinta');
                        p.setAttribute('aria-disabled', 'true'); // per accessibilità, indica che il pulsante è disabilitato (così è comprensibile anche ad uno screen reader)
                    }
                });
            }
        }
    };
    
    verificaStato();
});