(function (window) {
    'use strict';

    const baseWords = {
        omens: ['blutfilm', 'asche', 'stille', 'frost', 'pulsschmerz', 'pixelstaub', 'glasregen'],
        nouns: ['Echo', 'Signal', 'Schleier', 'Rauschen', 'Scherbe', 'Strom', 'Schwarm'],
        glitches: ['//stack underflow', ':::carrier lost', '[[violet drift]]', '>>> null tone', '--- silent frame']
    };

    const baseTemplates = {
        default: [
            '…{{omen}} klebt an deinem Prompt.',
            'Zwischen den Zeilen hängt {{noun}}.',
            'Die Leitung atmet {{omen}}.',
            '{{user}}, dein Terminal flimmert nach {{time}}.'
        ],
        events: [
            'Meldung @{{time}}: {{noun}} pulsiert.',
            'Channel drift · {{omen}} im Carrier.',
            'Silent packet returned {{noun}}.'
        ],
    };

    const defaults = {
        baseChanceBoot: 0.01,
        baseChanceScan: 0.01,
        baseChanceCat: 0.02,
        baseChanceSeanceListen: 0.005,
        baseChanceIdle: 0.012,
        ttlHoursMin: 2,
        ttlHoursMax: 48,
        intervalSecondsMin: 30,
        intervalSecondsMax: 180,
        cooldownHours: 12,
        minGapSeconds: 18
    };

    const spirits = {
        violet_echo: {
            id: 'violet_echo',
            name: 'Violet Echo',
            tags: ['glitch', 'omens'],
            words: {
                omens: ['violetter Staub', 'kaltes Echo', 'flimmernde Asche'],
                nouns: ['Echolunge', 'Ghost Packet', 'Zwischenraum'],
                glitches: ['[echo/404]', '<violet ping>', '///breath on the line']
            },
            templates: {
                haunt: [
                    '…{{omen}} klebt an deinem Prompt.',
                    'Ein Echo hängt zwischen den Zeilen.',
                    'Dein Terminal atmet {{noun}}.',
                    'Violet Echo lauscht. {{omen}} bleibt.',
                    '{{user}}, du sprichst in eine leere Leitung. {{noun}} antwortet.'
                ],
                events: [
                    'Carrier drifted at {{time}} · {{omen}} blieb zurück.',
                    '{{noun}} sendet einen leisen Ping.',
                    'Zwischenpaket: {{omen}} / {{noun}}'
                ],
                motd: [
                    'Violet Echo sitzt im Backlog.',
                    'MOTD überschrieben: Nur Rauschen, keine Ruhe.',
                    'Vorsicht: {{omen}} im Stack.'
                ]
            },
            haunting: {
                baseChanceBoot: 0.012,
                baseChanceScan: 0.018,
                ttlHoursMin: 4,
                ttlHoursMax: 36,
                intervalSecondsMin: 26,
                intervalSecondsMax: 150
            }
        }
    };

    window.DarknetSpirits = { spirits, defaults, baseWords, baseTemplates };
})(window);
