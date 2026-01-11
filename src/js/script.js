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
});

//al ricaricamento della pagina mi blocca lo scrool smooth per permettermi di tornare al form appena inviato istantaneamente
document.documentElement.style.scrollBehavior = 'auto';
setTimeout(function() { document.documentElement.style.scrollBehavior = 'smooth'; }, 500);

/* ==========================================================================
   FUNZIONI GLOBALI (Sempre disponibili)
   ========================================================================== */

function openTab(evt, tabName) {
    let i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    const targetTab = document.getElementById(tabName);
    if (targetTab) {
        targetTab.style.display = "block";
        evt.currentTarget.className += " active";
    }
}



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
        
        // Aggiungi qui l'eventuale listener blur per l'input data
    }

    const btnEditAdmin = document.getElementById('edit-admin-info');
    const saveAdminBtn = document.getElementById('submit-edit');
    let isEditing = false;

    if (btnEditAdmin) {
        btnEditAdmin.addEventListener('click', (e) => {
            console.log(isEditing);
            isEditing = !isEditing;
            e.preventDefault();
            const inputs = document.querySelectorAll('.generic-info-container input');
            console.log(inputs);
            inputs.forEach(input => {
                //tutti gli input che non sono di tipo type= file
                if (!isEditing) {
                    if(input.type !== 'file') {
                        input.setAttribute('readonly', 'true');
                    }else{
                        input.setAttribute('disabled','true');
                    }
                } else {
                    if(input.type === 'file') {
                        input.removeAttribute('disabled');
                    }else{
                        input.removeAttribute('readonly');
                    }
                }

            });
            if (isEditing && saveAdminBtn) {
                saveAdminBtn.removeAttribute('hidden');
            } else if (saveAdminBtn) {
                saveAdminBtn.setAttribute('hidden', 'true');
            }

        });
    }

    /* ==========================================================================
       VALIDAZIONE FORM AGGIUNGI ANIMALE
       ========================================================================== */
    const formAdd = document.getElementById('form-add-animal');

    if (formAdd) {
        formAdd.querySelectorAll('.error-form').forEach(p => {
            p.style.display = 'none'; 
        });

        const setError = (input, message) => {
            const container = input.closest('div') || input.closest('fieldset');
            if (!container) return;
            
            const errorElement = container.querySelector('.error-form');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = message ? 'block' : 'none';
            }
        };

        const validateField = (field) => {
            const val = field.value.trim();
            const name = field.name;

            if (name === 'nome' || name === 'razza' || name === 'colore') {
                if (val.length < 2) return "Minimo 2 caratteri";
                if (!/^[a-zA-ZÀ-ÿ\s']+$/.test(val)) return "Usa solo lettere";
            }
            
            if (name === 'dataNascita') {
                if (val === "") return "Data obbligatoria";
                if (new Date(val) > new Date()) return "La data non può essere futura";
            }

            if (name === 'taglia' || name === 'pelo') {
                if (val === "" || val === null) return "Seleziona un'opzione";
            }

            if (name === 'condMediche' || name === 'carattere' || name === 'famiglia') {
                if (val.length > 0 && val.length < 10) return "Descrizione troppo breve (min 10 car.)";
            }

            if (name === 'tipologia' || name === 'sesso') {
                const radioGroup = document.getElementsByName(name);
                const isChecked = Array.from(radioGroup).some(r => r.checked);
                if (!isChecked) return "Selezione obbligatoria";
            }

            if (name === 'foto' && field.files.length > 0) {
                const file = field.files[0];
                if (file.size > 2 * 1024 * 1024) return "Immagine troppo pesante (max 2MB)";
            }

            return ""; // Nessun errore
        };

        formAdd.querySelectorAll('input, textarea, select').forEach(input => {
            const type = (input.type === 'radio' || input.tagName === 'SELECT') ? 'change' : 'blur';
            
            input.addEventListener(type, () => {
                setError(input, validateField(input));
            });

            input.addEventListener('input', () => {
                const container = input.closest('div') || input.closest('fieldset');
                const errorDisplay = container.querySelector('.error-form');
                if (errorDisplay && errorDisplay.style.display === 'block') {
                    if (!validateField(input)) setError(input, "");
                }
            });
        });

        formAdd.addEventListener('submit', (e) => {
            let firstErrorField = null;
            
            const fieldsToValidate = formAdd.querySelectorAll('input, textarea, select');
            
            fieldsToValidate.forEach(input => {
                const msg = validateField(input);
                if (msg) {
                    setError(input, msg);
                    if (!firstErrorField) firstErrorField = input;
                }
            });

            if (firstErrorField) {
                e.preventDefault(); // Blocca l'invio
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstErrorField.focus();
            }
        });
    }
});