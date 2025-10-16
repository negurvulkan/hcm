<?php
return [
    'app' => [
        'title_suffix' => '{name}',
        'footer_notice' => 'Turniermanagement V2 · Kein Internet notwendig · Vendors lokal einbinden.',
    ],
    'layout' => [
        'default_title' => 'Turniermanagement',
        'nav' => [
            'toggle' => 'Navigation umschalten',
        ],
        'role_label' => 'Rolle',
        'read_only' => 'Read-only',
        'writeable' => 'Schreibend',
        'peer' => [
            'default' => 'Peer',
        ],
    ],
    'nav' => [
        'dashboard' => 'Dashboard',
        'persons' => 'Personen',
        'horses' => 'Pferde',
        'clubs' => 'Vereine',
        'events' => 'Turniere',
        'classes' => 'Prüfungen',
        'entries' => 'Nennungen',
        'startlists' => 'Startlisten',
        'schedule' => 'Zeitplan',
        'display' => 'Anzeige',
        'judge' => 'Richten',
        'results' => 'Ergebnisse',
        'helpers' => 'Helfer',
        'print' => 'Druck',
        'export' => 'Export',
        'instance' => 'Instanz & Modus',
        'sync' => 'Sync',
    ],
    'pages' => [
        'dashboard' => ['title' => 'Dashboard'],
        'persons' => ['title' => 'Personen'],
        'horses' => ['title' => 'Pferde'],
        'clubs' => ['title' => 'Vereine'],
        'events' => ['title' => 'Turniere'],
        'classes' => ['title' => 'Prüfungen'],
        'entries' => ['title' => 'Nennungen'],
        'startlist' => ['title' => 'Startlisten'],
        'schedule' => ['title' => 'Zeitplan'],
        'display' => ['title' => 'Anzeige'],
        'judge' => ['title' => 'Richten'],
        'results' => ['title' => 'Ergebnisse'],
        'helpers' => ['title' => 'Helfer'],
        'print' => ['title' => 'Druck'],
        'export' => ['title' => 'Export'],
        'instance' => ['title' => 'Instanz & Modus'],
        'sync' => ['title' => 'Sync'],
    ],
    'auth' => [
        'change_password' => 'Passwort ändern',
        'logout' => 'Abmelden',
        'login' => [
            'title' => 'Login',
            'heading' => 'Anmeldung',
            'hint' => 'Bitte lokale Zugangsdaten eingeben. Vendors liegen lokal vor.',
            'email' => 'E-Mail',
            'password' => 'Passwort',
            'submit' => 'Login',
            'footer' => 'Kein Internet nötig · Bootstrap/jQuery lokal referenzieren.',
        ],
        'change' => [
            'title' => 'Passwort ändern',
            'heading' => 'Passwort ändern',
            'user_info' => 'Angemeldet als {email}.',
            'current' => 'Aktuelles Passwort',
            'new' => 'Neues Passwort',
            'confirm' => 'Bestätigung',
            'back' => 'Zurück',
            'submit' => 'Speichern',
            'footer' => 'Änderungen werden sofort aktiv.',
        ],
    ],
    'locale' => [
        'name' => [
            'de' => 'Deutsch',
            'en' => 'English',
        ],
    ],
    'forms' => [
        'date' => 'Datum',
        'time' => 'Uhrzeit',
    ],
    'dashboard' => [
        'title' => 'Dashboard',
        'tiles' => [
            'default_note' => 'Zur Übersicht',
            'peer_connection' => [
                'title' => 'Peer-Verbindung',
                'connected' => '● Verbunden',
                'error' => '● Fehler',
                'pending' => '● Offen',
                'note_missing' => 'Noch nicht geprüft',
            ],
            'write_state' => [
                'title' => 'Schreibstatus',
            ],
            'sync' => [
                'title' => 'Letzter Sync',
                'empty' => 'Keine Daten',
                'note' => 'Lokal {local} · Peer {remote}',
            ],
            'office' => [
                'open_entries' => 'Offene Nennungen',
                'paid_entries' => 'Bezahlte Nennungen',
            ],
            'steward' => [
                'today_schedule' => 'Heutiger Ablauf',
                'live_position' => 'Live-Starter',
            ],
            'helpers' => [
                'checkins' => 'Check-ins',
            ],
            'judge' => [
                'queue' => 'Starter in Queue',
            ],
        ],
        'sections' => [
            'today_schedule' => [
                'title' => 'Heutiger Zeitplan',
                'empty' => 'Heute sind keine Starts geplant.',
            ],
            'live_status' => [
                'title' => 'Live-Status',
                'current' => 'Aktuell',
                'start_number' => 'Startnr. {number}',
                'none' => 'Noch kein Start',
                'horse' => 'Pferd',
                'class' => 'Prüfung',
                'upcoming' => 'Nächste Starter',
                'position' => 'Nr. {position}',
                'no_shifts' => 'Keine Verschiebungen.',
            ],
        ],
    ],
    'tests' => [
        'items' => [
            'one' => '{count} Element',
            'other' => '{count} Elemente',
        ],
    ],
];
