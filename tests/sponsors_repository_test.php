<?php
declare(strict_types=1);

require __DIR__ . '/../app/Sponsors/SponsorRepository.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');
$pdo->exec('CREATE TABLE sponsors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    display_name TEXT NULL,
    type TEXT NOT NULL,
    status TEXT NOT NULL,
    contact_person TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    address TEXT NOT NULL,
    tier TEXT NOT NULL,
    value NUMERIC NULL,
    value_type TEXT NOT NULL,
    contract_start TEXT NULL,
    contract_end TEXT NULL,
    invoice_required INTEGER NOT NULL DEFAULT 0,
    invoice_number TEXT NULL,
    logo_path TEXT NULL,
    website TEXT NULL,
    description_short TEXT NULL,
    description_long TEXT NULL,
    priority INTEGER NOT NULL DEFAULT 0,
    color_primary TEXT NULL,
    tagline TEXT NULL,
    show_on_website INTEGER NOT NULL DEFAULT 1,
    show_on_signage INTEGER NOT NULL DEFAULT 1,
    show_in_program INTEGER NOT NULL DEFAULT 1,
    overlay_template TEXT NULL,
    display_duration INTEGER NULL,
    display_frequency INTEGER NULL,
    linked_event_id INTEGER NULL,
    contract_file TEXT NULL,
    logo_variants TEXT NOT NULL DEFAULT "[]",
    media_package TEXT NOT NULL DEFAULT "[]",
    notes_internal TEXT NULL,
    documents TEXT NOT NULL DEFAULT "[]",
    sponsorship_history TEXT NOT NULL DEFAULT "[]",
    display_stats TEXT NOT NULL DEFAULT "{}",
    last_contacted TEXT NULL,
    follow_up_date TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)');

function t(string $key): string
{
    return $key;
}

function assertSame(mixed $expected, mixed $actual, string $message = ''): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(($message ? $message . ': ' : '') . 'expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
    }
}

function assertTrue(bool $condition, string $message = ''): void
{
    if (!$condition) {
        throw new RuntimeException($message ?: 'Assertion failed');
    }
}

$repository = new App\Sponsors\SponsorRepository($pdo);

$firstId = $repository->createSponsor([
    'name' => 'Sunrise Media',
    'display_name' => 'Sunrise Media',
    'type' => 'media_partner',
    'status' => 'active',
    'contact_person' => 'Laura Light',
    'email' => 'laura@sunrise.test',
    'phone' => '+49 555 1234',
    'address' => 'Media Alley 5, 98765 City',
    'tier' => 'gold',
    'value' => '2500',
    'value_type' => 'service',
    'contract_start' => '2024-02-01',
    'contract_end' => '2024-12-01',
    'invoice_required' => false,
    'invoice_number' => '',
    'logo_path' => '/logos/sunrise.png',
    'website' => 'https://sunrise.test',
    'description_short' => 'Streaming partner',
    'description_long' => 'Provides streaming coverage and video editing.',
    'priority' => 5,
    'color_primary' => '#FF8800',
    'tagline' => 'Lighting up the arena',
    'show_on_website' => true,
    'show_on_signage' => true,
    'show_in_program' => true,
    'display_duration' => 20,
    'display_frequency' => 4,
    'overlay_template' => 'media',
    'linked_event_id' => null,
    'contract_file' => '/contracts/sunrise.pdf',
    'notes_internal' => 'Needs logo credit on live stream',
    'logo_variants' => ['dark' => '/logos/sunrise_dark.png'],
    'media_package' => ['ticker', 'video spot'],
    'documents' => ['/docs/sunrise_brand.pdf'],
    'sponsorship_history' => [],
    'display_stats' => ['impressions' => 0],
    'last_contacted' => '2024-05-10',
    'follow_up_date' => '2024-08-15',
]);
assertTrue($firstId > 0, 'Sponsor should be created.');

$found = $repository->findSponsor($firstId);
assertSame('Sunrise Media', $found['display_name'], 'Display name should match.');
assertSame(true, $found['show_on_signage'], 'Signage flag should be true.');

$repository->updateSponsor($firstId, array_merge($found, [
    'status' => 'inactive',
    'show_on_signage' => false,
    'tagline' => 'Still shining',
]));
$updated = $repository->findSponsor($firstId);
assertSame('inactive', $updated['status'], 'Status should update.');
assertSame(false, $updated['show_on_signage'], 'Signage flag should update.');
assertSame('Still shining', $updated['tagline'], 'Tagline should update.');

$secondId = $repository->createSponsor([
    'name' => 'EquiFoods',
    'display_name' => 'EquiFoods',
    'type' => 'company',
    'status' => 'active',
    'contact_person' => 'Marco Green',
    'email' => 'marco@equifoods.test',
    'phone' => '+49 555 9876',
    'address' => 'Stable Road 12, 54321 Village',
    'tier' => 'platinum',
    'value' => '8000',
    'value_type' => 'in_kind',
    'contract_start' => '2024-03-01',
    'contract_end' => '2024-10-31',
    'invoice_required' => true,
    'invoice_number' => 'EQ-2024-01',
    'logo_path' => '/logos/equifoods.png',
    'website' => 'https://equifoods.test',
    'description_short' => 'Nutrition partner',
    'description_long' => 'Provides feed and supplements for horses and staff.',
    'priority' => 15,
    'color_primary' => '#006633',
    'tagline' => 'Fuel for champions',
    'show_on_website' => true,
    'show_on_signage' => true,
    'show_in_program' => true,
    'display_duration' => 25,
    'display_frequency' => 2,
    'overlay_template' => 'sponsor-spot',
    'linked_event_id' => 42,
    'contract_file' => '/contracts/equifoods.pdf',
    'notes_internal' => 'VIP lounge catering',
    'logo_variants' => ['mono' => '/logos/equifoods_mono.png'],
    'media_package' => ['arena banner'],
    'documents' => [],
    'sponsorship_history' => [['year' => 2022, 'tier' => 'silver']],
    'display_stats' => ['impressions' => 100],
    'last_contacted' => '2024-07-02',
    'follow_up_date' => '2024-08-01',
]);

$activeSponsors = $repository->listSponsors('active', null);
assertSame(1, count($activeSponsors), 'There should be exactly one active sponsor.');
assertSame('EquiFoods', $activeSponsors[0]['display_name'], 'Active sponsor should be EquiFoods.');

$signageEntries = $repository->signageEntries(42);
assertSame(1, count($signageEntries), 'Signage entries filtered by event should contain one sponsor.');
assertSame('Fuel for champions', $signageEntries[0]['tagline'], 'Signage entry should expose tagline.');

$tickerMessages = $repository->tickerMessages(42);
assertSame('Platinum · EquiFoods · Fuel for champions', $tickerMessages[0], 'Ticker message should contain tier, name and tagline.');

$tickerAll = $repository->tickerMessages(null);
assertSame(1, count($tickerAll), 'Inactive sponsors should not appear in ticker.');

echo "Sponsor repository tests passed\n";
