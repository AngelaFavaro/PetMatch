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

    /* ==========================================================================
       VALIDAZIONE FORM AGGIUNGI Animali
       ========================================================================== */
    
    const formAdd = document.getElementById('form-add-animal');

   if (formAdd) {
        formAdd.querySelectorAll('.error-form').forEach(p => {
            if (p.textContent.trim() === "") {
                p.style.display = 'none'; 
            } else {
                p.style.display = 'block'; // Se il PHP ha scritto qualcosa, mostralo!
            }
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
            const validatedGroups = new Set();

            fieldsToValidate.forEach(input => {
                const name = input.name;
                if (input.type === 'radio') {
                    if (validatedGroups.has(name)) return;
                    validatedGroups.add(name);
                }

                const msg = validateField(input);
                if (msg) {
                    setError(input, msg);
                    if (!firstErrorField) firstErrorField = input;
                }
            });

            if (firstErrorField) {
                // COMMENTA LA RIGA SOTTO PER NON BLOCCARE IL PHP
                // e.preventDefault(); 
                
                console.log("JS ha trovato errori, ma lascio inviare al PHP...");
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    // ========== DRAG & DROP PER FOTO ==========
    const fileInput = document.getElementById('foto');
    const fileLabel = document.querySelector('.file-upload-label');
    const fileNameDisplay = document.querySelector('.file-name-display');

    if (fileInput && fileLabel) {
        // Previeni comportamento default del browser
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileLabel.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        // Evidenzia area quando drag
        ['dragenter', 'dragover'].forEach(eventName => {
            fileLabel.addEventListener(eventName, () => {
                fileLabel.classList.add('drag-active');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileLabel.addEventListener(eventName, () => {
                fileLabel.classList.remove('drag-active');
            }, false);
        });

        // Gestisci il drop
        fileLabel.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileName(files[0].name);
                // Trigger validation
                setError(fileInput, validateField(fileInput));
            }
        }, false);

        // Mostra nome file quando scelto normalmente
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                updateFileName(fileInput.files[0].name);
            }
        });

        function updateFileName(name) {
            fileNameDisplay.textContent = `✓ ${name}`;
            const infoDiv = document.querySelector('.foto-caricata-info');
            if (infoDiv) {
                infoDiv.textContent = `File selezionato: ${name}`;
            }
        }
    }
});

//mette il numeri di caratteri inseriti 
function contaCaratteri(campo, idContatore) {
    var lunghezzaAttuale = campo.value.length;
    var contatore = document.getElementById(idContatore);
        
    contatore.innerText = lunghezzaAttuale+'/255';
}

//mette il limite quando si ricarica la pagina con i dati già inseriti
document.addEventListener("DOMContentLoaded", function() {
    var campo_desc = document.getElementById('desc-event');
    var campo_cond = document.getElementById('condMediche');
    var campo_car = document.getElementById('carattere');
    if(campo_desc) {
        contaCaratteri(campo_desc, "conta-corrente-evento");
    }
    if(campo_cond) {
        contaCaratteri(campo_cond, "conta-corrente-condMediche");
    }
    if(campo_car) {
        contaCaratteri(campo_car, "conta-corrente-carattere");
    }
});