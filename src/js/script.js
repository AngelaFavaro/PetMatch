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
    // VERIFICA STATO RICHISTA ADOZIONE
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

    // VALIDAZIONE FORM AGGIUNGI ANIMALE
    
    const formAdd = document.getElementById('form-add-animal');

    if (formAdd) {
        formAdd.querySelectorAll('.error-form');

        const setError = (input, message) => {
            const container = input.closest('div') || input.closest('fieldset') || input.parentElement;            
            const errorElement = container.querySelector('.error-form');

            if (errorElement) {
                errorElement.textContent = message;
            }
        };

        // inizio i controlli dinamici sui campi
        const validateField = (field) => {
            const val = field.value.trim();
            const name = field.name;

            if (['nome', 'razza', 'colore'].includes(name)) {
                if (val.length < 2) return "Inserisci minimo 2 caratteri";
            }

            if (name === 'carattere' || name === 'famiglia') {
                if (val.length < 10) return "La descrizione deve essere di almeno 10 caratteri";
            }
            
            if (name === 'dataNascita') {
                if (val === "") return "Data obbligatoria";
                const dataInserita = new Date(val);
                const oggi = new Date();
                const limite = new Date();
                limite.setFullYear(oggi.getFullYear() - 18);

                if (dataInserita > oggi) return "La data non può essere futura";
                if (dataInserita < limite) return "L'animale non può avere più di 18 anni";
            }

            if (name === 'taglia' || name === 'pelo') {
                if (!val) return "Seleziona un'opzione";            
            }

            if (name === 'tipologia' || name === 'sesso') {
                const radioGroup = document.getElementsByName(name);
                const isChecked = Array.from(radioGroup).some(r => r.checked);
                if (!isChecked) return "Selezione obbligatoria";
            }

            if (name === 'foto') {
                const hiddenFoto = document.querySelector('input[type="hidden"][name="foto"]');
                if (field.files.length === 0 && !hiddenFoto) {
                    return "La foto è obbligatoria";
                }
            }
            
            return "";
        };

        formAdd.querySelectorAll('input, textarea, select').forEach(input => {
            const eventType = (input.type === 'radio' || input.tagName === 'SELECT') ? 'change' : 'blur';
            
            input.addEventListener(eventType, () => {
                setError(input, validateField(input));
            });

            // Rimuovi errore mentre l'utente corregge
            input.addEventListener('input', () => {
                const container = input.closest('div') || input.closest('fieldset') || input.parentElement;
                const err = container.querySelector('.error-form');
                if (err) {
                    if (!validateField(input)) setError(input, "");
                }
            });
        });

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
    } 

    // ========== VALIDAZIONE FORM NUOVO EVENTO ==========
    const formEvento = document.getElementById('new-event');
    
    if (formEvento) {
        formEvento.querySelectorAll('.error-form').forEach(p => {
            p.style.display = p.textContent.trim() === "" ? 'none' : 'block';
        });

        const setErrorEvento = (input, message) => {
            const container = input.closest('div') || input.closest('fieldset') || input.parentElement;
            const errorElement = container.querySelector('.error-form');
            if (errorElement) {
                errorElement.textContent = message;
                errorElement.style.display = message ? 'block' : 'none';
            }
        };

        const validateFieldEvento = (field) => {
            const val = field.value.trim();
            const name = field.name;

            if (name === 'title-event') {
                if (val === "") return "Inserisci un titolo.";
                if (val.length < 2) return "Il titolo è troppo corto.";
                if (val.length > 40) return "Il titolo è troppo lungo.";
            }

            if (name === 'desc-event') {
                if (val === "") return "Inserisci una descrizione.";
                if (val.length < 5) return "La descrizione è troppo corta.";
                if (val.length > 255) return "La descrizione è troppo lunga.";
            }

            if (name === 'address-event') {
                if (val === "") return "Inserisci la via.";
                if (val.length < 3) return "La via è troppo corta.";
                if (val.length > 255) return "La via è troppo lunga.";
                const regexIndirizzo = /^[a-zA-Z.']{3,}\s+.+\s+(?:n\.?\s?)?\d+[a-zA-Z]?$/;
                if (!regexIndirizzo.test(val)) return "La via non è valida.";
            }

            if (name === 'city-event') {
                if (val === "") return "Inserisci la città.";
                if (val.length < 2) return "La città è troppo corta.";
                if (val.length > 100) return "La città è troppo lunga.";
                const regexCitta = /^[\p{L}\s.']{2,}$/u;
                if (!regexCitta.test(val)) return "La città non è valida.";
            }

            if (name === 'day-event') {
                if (val === "") return "Inserisci il giorno.";
                const dataInserita = new Date(val);
                const oggi = new Date();
                oggi.setHours(0, 0, 0, 0);
                if (dataInserita < oggi) return "L'evento non può essere nel passato.";
            }

            if (name === 'foto') {
                const hiddenFoto = formEvento.querySelector('input[type="hidden"][name="old-foto"]');
                if (field.files.length === 0 && !hiddenFoto) {
                    return "Inserisci una foto.";
                }
            }

            return "";
        };

        formEvento.querySelectorAll('input, textarea').forEach(input => {
            console.log('Aggiungo listener a:', input.name, 'tipo:', input.type);
            
            const eventType = (input.type === 'file' || input.type === 'date') ? 'change' : 'blur';
            
            input.addEventListener(eventType, () => {
                console.log('Evento triggerato su:', input.name, 'valore:', input.value);
                const errorMsg = validateFieldEvento(input);
                console.log('Errore trovato:', errorMsg);
                setErrorEvento(input, errorMsg);
            });

            input.addEventListener('input', () => {
                const container = input.closest('div') || input.closest('fieldset') || input.parentElement;
                const err = container.querySelector('.error-form');
                if (err && err.style.display === 'block') {
                    if (!validateFieldEvento(input)) setErrorEvento(input, "");
                }
            });
        });

        // ========== DRAG & DROP FOTO EVENTO ==========
        const fileInputEvento = formEvento.querySelector('#foto');
        const fileLabelEvento = formEvento.querySelector('.file-upload-label');
        const fileNameDisplayEvento = formEvento.querySelector('.file-name-display');

        if (fileInputEvento && fileLabelEvento) {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                fileLabelEvento.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                fileLabelEvento.addEventListener(eventName, () => {
                    fileLabelEvento.classList.add('drag-active');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                fileLabelEvento.addEventListener(eventName, () => {
                    fileLabelEvento.classList.remove('drag-active');
                }, false);
            });

            fileLabelEvento.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInputEvento.files = files;
                    updateFileNameEvento(files[0].name);
                    setErrorEvento(fileInputEvento, validateFieldEvento(fileInputEvento));
                }
            }, false);

            fileInputEvento.addEventListener('change', () => {
                if (fileInputEvento.files.length > 0) {
                    updateFileNameEvento(fileInputEvento.files[0].name);
                }
            });

            function updateFileNameEvento(name) {
                fileNameDisplayEvento.textContent = `✓ ${name}`;
                const infoDiv = formEvento.querySelector('.foto-caricata-info');
                if (infoDiv) {
                    infoDiv.textContent = `File selezionato: ${name}`;
                }
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

function contaCaratterilimit40(campo, idContatore) {
    var lunghezzaAttuale = campo.value.length;
    var contatore = document.getElementById(idContatore);
        
    contatore.innerText = lunghezzaAttuale+'/40';
}

function contaCaratterilimit100(campo, idContatore) {
    var lunghezzaAttuale = campo.value.length;
    var contatore = document.getElementById(idContatore);
        
    contatore.innerText = lunghezzaAttuale+'/100';
}


//mette il limite quando si ricarica la pagina con i dati già inseriti
document.addEventListener("DOMContentLoaded", function() {
    var campo_desc = document.getElementById('desc-event');
    var campo_titleEvent = document.getElementById('title-event');
    var campo_city = document.getElementById('city-event');
    var campo_address = document.getElementById('address-event');
    var campo_nome = document.getElementById('nome');
    var campo_colore = document.getElementById('colore');
    var campo_razza = document.getElementById('razza');
    if(campo_desc) {
        contaCaratteri(campo_desc, "conta-corrente-evento");
    }
    if(campo_city) {
        contaCaratterilimit100(campo_city, "conta-corrente-citta");
    }
    if(campo_address) {
        contaCaratteri(campo_address, "conta-corrente-address");
    }
    if(campo_nome) {
        contaCaratterilimit100(campo_nome, "conta-corrente-nome");
    }
    if(campo_titleEvent) {
        contaCaratterilimit40(campo_titleEvent, "conta-corrente-titoloevento");
    }
    if(campo_razza) {
        contaCaratterilimit100(campo_razza, "conta-corrente-razza");
    }
    if(campo_colore) {
        contaCaratterilimit100(campo_colore, "conta-corrente-colore");
    }
});

document.addEventListener("DOMContentLoaded", function() {
    var campo_nome = document.getElementById('nome');
    if(campo_nome) {
        contaCaratterilimit100(campo_nome, "conta-corrente-nome");
        
        campo_nome.addEventListener('input', function() {
            contaCaratterilimit100(this, "conta-corrente-nome");
        });
    }

    var campo_razza = document.getElementById('razza');
    if(campo_razza) {
        contaCaratterilimit100(campo_razza, "conta-corrente-razza");
        
        campo_razza.addEventListener('input', function() {
            contaCaratterilimit100(this, "conta-corrente-razza");
        });
    }

    var campo_colore = document.getElementById('colore');
    if(campo_colore) {
        contaCaratterilimit100(campo_colore, "conta-corrente-colore");
        
        campo_colore.addEventListener('input', function() {
            contaCaratterilimit100(this, "conta-corrente-colore");
        });
    }

    var campo_city = document.getElementById('city-event');
    if(campo_city) {
        contaCaratterilimit100(campo_city, "conta-corrente-citta");
        
        campo_city.addEventListener('input', function() {
            contaCaratterilimit100(this, "conta-corrente-citta");
        });
    }

    var campo_nome = document.getElementById('address-event');
    if(campo_nome) {
        contaCaratteri(campo_nome, "conta-corrente-address");
        
        campo_nome.addEventListener('input', function() {
            contaCaratteri(this, "conta-corrente-address");
        });
    }

    var campo_descr = document.getElementById('desc-event');
    if(campo_descr) {
        contaCaratteri(campo_descr, "conta-corrente-evento");
        
        campo_descr.addEventListener('input', function() {
            contaCaratteri(this, "conta-corrente-evento");
        });
    }

    var campo_titolo = document.getElementById('title-event');
    if(campo_titolo) {
        contaCaratterilimit40(campo_titolo, "conta-corrente-titoloevento");
        
        campo_titolo.addEventListener('input', function() {
            contaCaratterilimit40(this, "conta-corrente-titoloevento");
        });
    }
});


// NON RICARICA LA PAGINA QUANTO PREMI LA CHECK
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('theme-toggle');

    toggle.addEventListener('change', function() {
        const newTheme = this.checked ? 'dark' : 'light';
        fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ toggle_theme: newTheme })
        })
        .then(response => {
            console.log('Tema salvato:', newTheme);
        });
    });
});


// FERMA IL TOGGLE DEL TEMA PRIMA DELLO SMALL (altrimenti si legge male)
document.addEventListener('scroll', function() {
    const themeSwitch = document.querySelector('#theme-switch');
    const footer = document.querySelector('small');
    if (!themeSwitch || !footer) return;

    if (window.innerWidth > 800) {
        themeSwitch.style.bottom = ''; 
        return; 
    }

    const footerRect = footer.getBoundingClientRect();
    const windowHeight = window.innerHeight;
    
    const defaultBottom = 20; 

    if (footerRect.top < windowHeight) {
        const overlap = windowHeight - footerRect.top;
        themeSwitch.style.bottom = (defaultBottom + overlap) + 'px';
    } else {
        themeSwitch.style.bottom = defaultBottom + 'px';
    }
});

// animazione apertura e chiusura del form richiesta di adozione

document.addEventListener('DOMContentLoaded', () => {
    const details = document.getElementById('compila-form-adozione');
    const content = document.getElementById('richiesta-adozione');

    if (!details || !content) return;

    const summary = details.querySelector('summary');

    const animOptions = { duration: 400, easing: 'ease-out' };

    summary.addEventListener('click', (e) => {
        e.preventDefault();

        if (details.hasAttribute('open')) {
            content.classList.remove('bg-active');
            const animation = content.animate([
                { height: content.offsetHeight + 'px', opacity: 1, padding: '1.5em 2em' },
                { height: '0px', opacity: 0, padding: '0 2em' }
            ], animOptions);

            animation.onfinish = () => {
                details.removeAttribute('open');
            };

        } else {
            details.setAttribute('open', '');
            const targetHeight = content.scrollHeight; 
            const animation = content.animate([
                { height: '0px', opacity: 0, padding: '0 2em' },
                { height: targetHeight + 'px', opacity: 1, padding: '1.5em 2em' }
            ], animOptions);
            animation.onfinish = () => {
                content.classList.add('bg-active');
            };
        }
    });
});



// aggiorna subuto l'immagine profilo
document.addEventListener('DOMContentLoaded', function() {
    
    const fileInput = document.getElementById('new-pic');
    const imgPreview = document.getElementById('foto-profilo');
    const deleteCheckbox = document.getElementById('delete-pic');

    if(fileInput && imgPreview) {
        
        fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];

            if (file) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    imgPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);

                //se metto un nuovo file la spunta era checkata allora la toglie
                if(deleteCheckbox) {
                    deleteCheckbox.checked = false;
                }
            }
        });
    }
});

// PUNTINI PER GLI EVENTI
document.addEventListener("DOMContentLoaded", function() {
    const descrizioni = document.querySelectorAll('.descrizione-evento p');

    function checkTruncation() {
        descrizioni.forEach(container => {
            container.classList.remove('is-truncated');
            
            if (container.scrollHeight > container.offsetHeight) {
                container.classList.add('is-truncated');
            }
        });
    }

    checkTruncation();
    window.addEventListener('resize', checkTruncation);
});


//per non far uscire lo screen reader dal dialog
window.addEventListener('DOMContentLoaded', () => {
    const dialog = document.querySelector('dialog[open]');
    if (dialog) {
        // Rimuoviamo l'attributo 'open' di PHP e apriamolo come modale nativa
        dialog.removeAttribute('open'); 
        dialog.showModal();
    }
});