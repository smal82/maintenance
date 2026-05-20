# 🔧 Maintenance Pro

**Sistema di Gestione della Manutenzione (CMMS)** — Applicazione web PHP per la gestione completa di asset aziendali, interventi di manutenzione, ricambi e pianificazione degli interventi programmati.

---

## 📋 Indice

- [Panoramica](#-panoramica)
- [Funzionalità](#-funzionalità)
- [Stack Tecnologico](#-stack-tecnologico)
- [Requisiti di Sistema](#-requisiti-di-sistema)
- [Installazione](#-installazione)
- [Configurazione](#-configurazione)
- [Struttura del Progetto](#-struttura-del-progetto)
- [Moduli](#-moduli)
- [Sistema di Plugin](#-sistema-di-plugin)
- [API](#-api)
- [Sicurezza](#-sicurezza)
- [Contribuire](#-contribuire)
- [Licenza](#-licenza)

---

## 🌐 Panoramica

**Maintenance Pro** è un CMMS (Computerized Maintenance Management System) open source sviluppato in PHP. Permette ad aziende, impianti industriali e strutture di qualsiasi dimensione di gestire in modo centralizzato:

- Il **censimento degli asset fisici** (macchinari, impianti, veicoli, attrezzature)
- Gli **interventi di manutenzione** ordinari e straordinari
- La **pianificazione** degli interventi futuri con calendario integrato
- Il **magazzino ricambi** e la relativa movimentazione
- I **tecnici** assegnati ai lavori, con gestione ruoli e profili

La dashboard principale fornisce una visione immediata dello stato operativo con KPI in tempo reale, grafici mensili delle manutenzioni e una timeline degli ultimi interventi.

---

## ✨ Funzionalità

### 📊 Dashboard
- KPI in tempo reale: asset totali, manutenzioni attive, in scadenza entro 7 giorni, priorità critica
- Grafico manutenzioni per mese (ultimi 6 mesi)
- Grafico distribuzione asset per categoria
- Timeline degli ultimi 10 interventi con stato e tecnico assegnato

### 🏭 Gestione Asset
- Anagrafica completa degli asset con codice univoco, nome, categoria
- Mappa geografica degli asset (`asset-map.php`)
- Upload allegati (immagini, PDF, documenti Office) fino a 10 MB
- Generazione automatica **QR Code** per ogni asset (dimensione configurabile)
- Stati dell'asset: attivo, in manutenzione, dismesso
- Categorizzazione con colori personalizzabili

### 🔧 Gestione Manutenzioni
- Creazione e modifica interventi con: titolo, descrizione, priorità, stato, data programmata
- **4 livelli di priorità**: Bassa, Media, Alta, Critica
- **4 stati**: Programmata, In Corso, Completata, Annullata
- Durata stimata in minuti
- Assegnazione tecnico responsabile
- Note aggiuntive e campo commenti
- Tipologie di manutenzione personalizzabili con colore identificativo

### 📅 Manutenzioni Programmate
- Pianificazione interventi ricorrenti
- Vista calendario integrata
- Notifiche scadenza (prossimi 7 giorni)

### 🔩 Gestione Ricambi
- Anagrafica completa dei pezzi di ricambio
- Associazione ricambi agli asset
- Storico utilizzo

### 👥 Gestione Utenti
- Sistema di autenticazione con sessioni sicure
- **Ruoli**: Admin, Technician
- Profilo utente con avatar (upload immagine)
- Attivazione / disattivazione account

### ⚙️ Impostazioni
- Configurazione generale dell'applicazione
- Gestione categorie asset
- Gestione tipologie manutenzione

### 🧩 Plugin
- Sistema di plugin abilitabile/disabilitabile da interfaccia
- Plugin di esempio incluso come base per sviluppi personalizzati

---

## 🛠 Stack Tecnologico

| Componente       | Tecnologia                          |
|------------------|-------------------------------------|
| Backend          | PHP 8.x                             |
| Database         | MySQL / MariaDB (charset `utf8mb4`) |
| Frontend         | HTML5, CSS3, JavaScript (ES6+)      |
| Grafici          | Chart.js                            |
| Icone            | Font Awesome                        |
| QR Code          | Libreria PHP per QR Code            |
| Autenticazione   | Session-based con CSRF token        |

---

## 📦 Requisiti di Sistema

- **PHP** >= 8.0 con estensioni: `pdo`, `pdo_mysql`, `session`, `json`, `fileinfo`
- **MySQL** >= 5.7 o **MariaDB** >= 10.3
- **Web server**: Apache (con `mod_rewrite`) o Nginx
- **Spazio disco**: minimo 50 MB (esclusi upload)
- Accesso in scrittura alle cartelle `uploads/` e `uploads/qrcodes/`

---

## 🚀 Installazione

### 1. Clona il repository

```bash
git clone https://github.com/smal82/maintenance.git
cd maintenance
```

### 2. Crea il database

```sql
CREATE DATABASE maintenance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'maintenance_user'@'localhost' IDENTIFIED BY 'password_sicura';
GRANT ALL PRIVILEGES ON maintenance.* TO 'maintenance_user'@'localhost';
FLUSH PRIVILEGES;
```

Importa lo schema SQL (se disponibile):

```bash
mysql -u maintenance_user -p maintenance < database/schema.sql
```

### 3. Configura l'applicazione

Copia il file di configurazione di esempio e modifica i parametri:

```bash
cp config.sample.php config.php
```

Modifica `config.php` con le tue credenziali (vedi sezione [Configurazione](#-configurazione)).

### 4. Permessi cartelle

```bash
chmod 755 uploads/
chmod 755 uploads/avatars/
chmod 755 uploads/qrcodes/
```

### 5. Configura il web server

**Apache** — assicurati che `mod_rewrite` sia abilitato e che `AllowOverride All` sia impostato per la directory del progetto.

**Nginx** — esempio di configurazione:

```nginx
server {
    listen 80;
    server_name tuodominio.it;
    root /var/www/maintenance;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    }
}
```

### 6. Accedi all'applicazione

Naviga all'URL configurato. Al primo accesso usa le credenziali di default (da modificare immediatamente):

```
Username: admin
Password: (vedi documentazione di setup iniziale)
```

---

## ⚙️ Configurazione

Il file `config.php` contiene tutte le impostazioni dell'applicazione:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'nome_database');
define('DB_USER', 'utente_db');
define('DB_PASS', 'password_db');
define('DB_CHARSET', 'utf8mb4');

// Applicazione
define('SITE_NAME', 'Maintenance Pro');
define('BASE_URL', 'https://tuodominio.it');
define('TIMEZONE', 'Europe/Rome');

// Upload
define('MAX_FILE_SIZE', 10485760); // 10 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx']);

// QR Code
define('QR_CODE_SIZE', 300);

// Errori (in produzione impostare a 0)
error_reporting(0);
ini_set('display_errors', 0);
```

Aggiungi al `.gitignore`:

```gitignore
config.php
uploads/*
!uploads/.gitkeep
!uploads/avatars/.gitkeep
!uploads/qrcodes/.gitkeep
```

---

## 📁 Struttura del Progetto

```
maintenance/
├── api/                        # Endpoint REST API
├── assets/                     # Risorse statiche
│   ├── css/                    # Fogli di stile
│   ├── js/                     # Script JavaScript
│   └── img/                    # Immagini dell'applicazione
├── classes/                    # Classi PHP (Auth, Database, ecc.)
├── includes/                   # Header, footer e componenti condivisi
├── plugins/                    # Sistema di plugin
│   └── example-plugin/         # Plugin di esempio
├── uploads/                    # File caricati dagli utenti
│   ├── avatars/                # Avatar degli utenti
│   └── qrcodes/                # QR Code generati
│
├── index.php                   # Dashboard principale
├── login.php                   # Pagina di login
├── logout.php                  # Logout
├── config.php                  # Configurazione
│
├── assets.php                  # Lista asset
├── asset-form.php              # Creazione / modifica asset
├── asset-delete.php            # Eliminazione asset
├── asset-map.php               # Mappa degli asset
│
├── maintenances.php            # Lista manutenzioni
├── maintenance-form.php        # Creazione / modifica manutenzione
├── maintenance-delete.php      # Eliminazione manutenzione
│
├── maintenance-types.php       # Lista tipologie manutenzione
├── maintenance-type-form.php   # Creazione / modifica tipologia
├── maintenance-type-delete.php # Eliminazione tipologia
│
├── scheduled-maintenances.php  # Manutenzioni programmate
├── scheduled-maintenance-form.php
├── scheduled-maintenance-delete.php
├── calendar.php                # Vista calendario
│
├── spare-parts.php             # Lista ricambi
├── spare-part-form.php         # Creazione / modifica ricambio
├── spare-part-delete.php       # Eliminazione ricambio
│
├── categories.php              # Categorie asset
├── category-form.php
├── category-delete.php
│
├── users.php                   # Gestione utenti
├── user-form.php
├── user-delete.php
├── profile.php                 # Profilo utente corrente
│
├── plugins.php                 # Gestione plugin
├── plugin-toggle.php           # Attiva / disattiva plugin
├── add_comments.php            # Aggiunta commenti
└── settings.php                # Impostazioni generali
```

---

## 📦 Moduli

### Modulo Asset
Ogni asset ha: codice univoco, nome, categoria, stato, posizione geografica, allegati e QR code. Gli asset possono essere filtrati, cercati e visualizzati su mappa.

### Modulo Manutenzioni
Gli interventi sono collegati a un asset e a un tecnico. Ogni intervento ha titolo, descrizione, tipo, priorità (low / medium / high / critical), stato (scheduled / in_progress / completed / cancelled), data programmata, durata stimata e note. È possibile aggiungere commenti agli interventi.

### Modulo Manutenzioni Programmate
Permette di definire interventi ricorrenti (es. revisione mensile, tagliando annuale). Gli interventi vengono visualizzati nel calendario e generano alert quando si avvicinano alla scadenza.

### Modulo Ricambi
Anagrafica dei pezzi di ricambio con codice, descrizione e associazione agli asset di pertinenza. Traccia l'utilizzo dei ricambi negli interventi.

### Modulo Utenti
Supporta due ruoli: `admin` (accesso completo) e `technician` (accesso operativo). Ogni utente ha un profilo con avatar e può essere attivato o disattivato.

---

## 🧩 Sistema di Plugin

L'applicazione supporta un sistema di plugin per estendere le funzionalità senza modificare il core.

Ogni plugin risiede in una propria sottocartella di `plugins/` e viene caricato automaticamente se abilitato. Usa `plugin-toggle.php` per attivare o disattivare i plugin dall'interfaccia di amministrazione.

Per creare un plugin personalizzato, copia e adatta la struttura in `plugins/example-plugin/`.

---

## 🔌 API

La cartella `api/` espone endpoint per l'integrazione con sistemi esterni. Le richieste richiedono autenticazione.

> La documentazione dettagliata delle API è in fase di completamento. Per l'elenco degli endpoint disponibili consulta i file nella cartella `api/`.

---

## 🔒 Sicurezza

L'applicazione implementa le seguenti misure di sicurezza:

- **Autenticazione**: session-based con nome sessione personalizzato (`MAINTENANCE_SESSION`)
- **CSRF Protection**: token univoco per ogni form (`csrf_token`)
- **Password hashing**: utilizzo di salt configurabile
- **Input sanitization**: `htmlspecialchars()` su tutti gli output, query parametrizzate con PDO
- **Upload validation**: controllo estensione e dimensione massima file
- **Accesso protetto**: tutte le pagine richiedono login (`$auth->requireLogin()`)

---

## 🤝 Contribuire

I contributi sono benvenuti! Per contribuire:

1. Fai il fork del repository
2. Crea un branch per la tua feature: `git checkout -b feature/nome-feature`
3. Committa le modifiche: `git commit -m 'feat: aggiunge nuova funzionalità'`
4. Pusha il branch: `git push origin feature/nome-feature`
5. Apri una Pull Request

Per bug e richieste di funzionalità, apri una [Issue](https://github.com/smal82/maintenance/issues).

---

## 📄 Licenza

Questo progetto è distribuito sotto licenza **MIT**. Consulta il file [LICENSE](LICENSE) per i dettagli.

---

<p align="center">
  Sviluppato con ❤️ da <a href="https://github.com/smal82">smal82</a>
</p>
