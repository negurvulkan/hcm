(function (window, document) {
    'use strict';

    const motdLines = [
        'NRW Noir · Darknet Terminal',
        'Status: limited uplink · ruhe bewahren',
        'Tippe "help" für Befehle.'
    ];

    const files = {
        'readme.txt': 'Willkommen im Noir-Netz. Alles ist provisorisch, nichts ist sicher.',
        'routes.map': 'MIRROR://delta · RELAY://violet · HOLLOW://ash',
        '/deep/ghost.log': { content: 'Flüstern im Off. Frames fallen ohne Carrier.', cursed: true },
        '/deep/cache.bin': { content: '01000111 01001000 01001111 01010011 01010100', cursed: true },
        'status.motd': 'MOTD: Echoes sind normal. Ignoriere sie.',
    };

    const formatStatus = (status) => {
        if (!status.active) {
            if (status.cooldown) {
                return `Keine Spukzeichen. Cooldown aktiv für ${status.cooldown}.`;
            }
            return 'Keine Spukzeichen.';
        }
        const spirit = status.spirit ? status.spirit.name : 'Unbekannt';
        return `Status: HAUNTED · Geist: ${spirit} · Intensität: ${(status.intensity || 0).toFixed(2)} · Ende in ${status.endsIn} · Nächste Störung in ${status.nextIn}.`;
    };

    const renderWhispers = (status, terminal) => {
        if (!status.whispers || status.whispers.length === 0) {
            terminal.printLine('Whispers: (leer)', 'muted');
            return;
        }
        const recent = status.whispers.slice(-5).map((entry) => entry.word).join(', ');
        terminal.printLine(`Whispers: ${recent}`, 'muted');
    };

    const renderCooldowns = (status, terminal) => {
        if (status.banishCooldown) {
            terminal.printLine(`Séance-Cooldown: ${status.banishCooldown}`, 'muted');
        }
        if (!status.active && status.cooldown) {
            terminal.printLine(`Globaler Cooldown: ${status.cooldown}`, 'muted');
        }
    };

    const registerCommands = (terminal, haunting) => {
        terminal.register('help', () => {
            terminal.printLine('Verfügbare Befehle:', 'muted');
            [
                'help               · zeigt diese Hilfe',
                'scan               · scannt Knoten, erhöht Risiko minimal',
                'cat <file>         · Datei anzeigen (cursed?)',
                'motd               · aktuelles MOTD',
                'haunt [calm|clear] · Status / Intensität senken / dev reset',
                'seance status      · Séance-Status',
                'seance listen      · wartet auf Whisper (leicht riskant)',
                'seance banish      · versucht den Geist zu vertreiben',
            ].forEach((line) => terminal.printLine(line, 'muted'));
        });

        terminal.register('motd', () => {
            const active = haunting.status();
            const spirit = active.spirit || null;
            const template = active.active ? (haunting.pickTemplate ? haunting.pickTemplate('motd', spirit) : null) : null;
            if (template) {
                terminal.printLine(haunting.renderLine ? haunting.renderLine(template) : template, 'haunt');
            } else {
                motdLines.forEach((line) => terminal.printLine(line, 'muted'));
            }
        });

        terminal.register('scan', () => {
            terminal.printLine('Scanning Relays…', 'muted');
            const spawned = haunting.maybeStart('scan');
            if (spawned) {
                terminal.printLine('Ein kaltes Paket bleibt im Carrier hängen.', 'haunt');
            } else {
                terminal.printLine('Keine Abweichungen.', 'muted');
            }
        });

        terminal.register('cat', (arg) => {
            const target = arg.trim();
            if (!target) {
                terminal.printLine('Pfad fehlt.', 'muted');
                return;
            }
            const entry = files[target];
            if (!entry) {
                terminal.printLine('404: Datei nicht gefunden.', 'muted');
                return;
            }
            const content = typeof entry === 'string' ? entry : entry.content;
            terminal.printLine(content || '(leer)', entry.cursed ? 'haunt' : 'muted');
            if (entry.cursed) {
                haunting.maybeStart('cat', { extraChance: 0.01 });
            }
        });

        terminal.register('haunt', (arg) => {
            const action = arg.trim();
            if (action === 'calm') {
                const intensity = haunting.calm();
                if (intensity !== null) {
                    terminal.printLine(`Intensität gesenkt → ${(intensity || 0).toFixed(2)}`, 'muted');
                } else {
                    terminal.printLine('Kein aktiver Spuk.', 'muted');
                }
                return;
            }
            if (action === 'clear') {
                haunting.stop('cleared');
                terminal.printLine('Haunting-Eintrag gelöscht (dev).', 'muted');
                return;
            }
            const status = haunting.status();
            terminal.printLine(formatStatus(status), status.active ? 'haunt' : 'muted');
            renderCooldowns(status, terminal);
            renderWhispers(status, terminal);
        });

        terminal.register('seance', (arg) => {
            const action = arg.trim() || 'status';
            if (action === 'status') {
                const status = haunting.status();
                terminal.printLine(formatStatus(status), status.active ? 'haunt' : 'muted');
                renderCooldowns(status, terminal);
                renderWhispers(status, terminal);
                return;
            }
            if (action === 'listen') {
                const whisper = haunting.recordWhisper();
                terminal.printLine(`Whisper empfangen: ${whisper.word}`, 'haunt');
                haunting.maybeStart('seance', { extraChance: 0.002 });
                return;
            }
            if (action === 'banish') {
                const result = haunting.banish();
                if (result.success) {
                    terminal.printLine('Séance bricht den Spuk. Es wird still.', 'haunt');
                } else if (result.reason === 'none') {
                    terminal.printLine('Kein Spuk aktiv.', 'muted');
                } else if (result.reason === 'cooldown') {
                    const retry = result.retryIn ? `${Math.round(result.retryIn / 1000)}s` : 'später';
                    terminal.printLine(`Zu früh. Die Leitung zittert noch (${retry}).`, 'muted');
                } else {
                    terminal.printLine('Bann fehlgeschlagen. Der Geist reizt die Leitung.', 'haunt');
                }
                return;
            }
            terminal.printLine('Unbekannte Séance-Aktion.', 'muted');
        });
    };

    document.addEventListener('DOMContentLoaded', () => {
        const root = document.querySelector('[data-darknet-terminal]');
        if (!root || !window.DarknetTerminal || !window.DarknetHauntingManager) {
            return;
        }
        const user = root.getAttribute('data-username') || 'guest';
        const terminal = new window.DarknetTerminal({ root, user, motd: motdLines });
        const haunting = new window.DarknetHauntingManager({ username: user, terminal });
        registerCommands(terminal, haunting);
        haunting.bootstrap();
        terminal.printLine('Boot abgeschlossen. Beobachte den Carrier.', 'muted');
    });
})(window, window.document);
