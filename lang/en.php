<?php
return [
    'app' => [
        'title_suffix' => '{name}',
        'footer_notice' => 'Tournament Management V2 · Works offline · Vendors bundled locally.',
    ],
    'layout' => [
        'default_title' => 'Tournament Management',
        'nav' => [
            'toggle' => 'Toggle navigation',
        ],
        'role_label' => 'Role',
        'read_only' => 'Read-only',
        'writeable' => 'Writable',
        'peer' => [
            'default' => 'Peer',
        ],
    ],
    'nav' => [
        'dashboard' => 'Dashboard',
        'persons' => 'Persons',
        'horses' => 'Horses',
        'clubs' => 'Clubs',
        'events' => 'Events',
        'classes' => 'Classes',
        'entries' => 'Entries',
        'startlists' => 'Start lists',
        'schedule' => 'Schedule',
        'display' => 'Display',
        'judge' => 'Judge UI',
        'results' => 'Results',
        'helpers' => 'Helpers',
        'print' => 'Print',
        'export' => 'Export',
        'instance' => 'Instance & Mode',
        'sync' => 'Sync',
    ],
    'pages' => [
        'dashboard' => ['title' => 'Dashboard'],
        'persons' => ['title' => 'Persons'],
        'horses' => ['title' => 'Horses'],
        'clubs' => ['title' => 'Clubs'],
        'events' => ['title' => 'Events'],
        'classes' => ['title' => 'Classes'],
        'entries' => ['title' => 'Entries'],
        'startlist' => ['title' => 'Start lists'],
        'schedule' => ['title' => 'Schedule'],
        'display' => ['title' => 'Display'],
        'judge' => ['title' => 'Judge UI'],
        'results' => ['title' => 'Results'],
        'helpers' => ['title' => 'Helpers'],
        'print' => ['title' => 'Print'],
        'export' => ['title' => 'Export'],
        'instance' => ['title' => 'Instance & Mode'],
        'sync' => ['title' => 'Sync'],
    ],
    'auth' => [
        'change_password' => 'Change password',
        'logout' => 'Log out',
        'login' => [
            'title' => 'Login',
            'heading' => 'Sign in',
            'hint' => 'Use your local credentials. Vendor assets are served offline.',
            'email' => 'Email',
            'password' => 'Password',
            'submit' => 'Sign in',
            'footer' => 'Works offline · Bootstrap/jQuery must be provided locally.',
        ],
        'change' => [
            'title' => 'Change password',
            'heading' => 'Change password',
            'user_info' => 'Signed in as {email}.',
            'current' => 'Current password',
            'new' => 'New password',
            'confirm' => 'Confirmation',
            'back' => 'Back',
            'submit' => 'Save',
            'footer' => 'Changes take effect immediately.',
        ],
    ],
    'locale' => [
        'name' => [
            'de' => 'German',
            'en' => 'English',
        ],
    ],
    'forms' => [
        'date' => 'Date',
        'time' => 'Time',
    ],
    'dashboard' => [
        'title' => 'Dashboard',
        'tiles' => [
            'default_note' => 'View details',
            'peer_connection' => [
                'title' => 'Peer connection',
                'connected' => '● Connected',
                'error' => '● Error',
                'pending' => '● Pending',
                'note_missing' => 'Not checked yet',
            ],
            'write_state' => [
                'title' => 'Write status',
            ],
            'sync' => [
                'title' => 'Last sync',
                'empty' => 'No data',
                'note' => 'Local {local} · Peer {remote}',
            ],
            'office' => [
                'open_entries' => 'Open entries',
                'paid_entries' => 'Paid entries',
            ],
            'steward' => [
                'today_schedule' => 'Today’s schedule',
                'live_position' => 'Live starter',
            ],
            'helpers' => [
                'checkins' => 'Check-ins',
            ],
            'judge' => [
                'queue' => 'Starters in queue',
            ],
        ],
        'sections' => [
            'today_schedule' => [
                'title' => 'Today’s schedule',
                'empty' => 'No starts scheduled today.',
            ],
            'live_status' => [
                'title' => 'Live status',
                'current' => 'Current',
                'start_number' => 'Start no. {number}',
                'none' => 'No start running',
                'horse' => 'Horse',
                'class' => 'Class',
                'upcoming' => 'Next starters',
                'position' => 'No. {position}',
                'no_shifts' => 'No shifts.',
            ],
        ],
    ],
    'tests' => [
        'items' => [
            'one' => '{count} item',
            'other' => '{count} items',
        ],
    ],
];
