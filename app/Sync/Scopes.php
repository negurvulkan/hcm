<?php
namespace App\Sync;

class Scopes
{
    public const ALL = [
        'persons',
        'horses',
        'clubs',
        'events',
        'classes',
        'entries',
        'starts',
        'schedules',
        'scores',
        'results',
        'announcements',
        'helpers',
        'helper_shifts',
        'payments',
        'audit',
    ];

    /**
     * @var string[]
     */
    private array $scopes;

    /**
     * @param string[]|null $scopes
     */
    public function __construct(?array $scopes = null)
    {
        if ($scopes === null || $scopes === []) {
            $this->scopes = self::ALL;
            return;
        }

        $normalized = [];
        foreach ($scopes as $scope) {
            $scope = strtolower(trim((string) $scope));
            if ($scope === '' || !in_array($scope, self::ALL, true)) {
                continue;
            }
            if (!in_array($scope, $normalized, true)) {
                $normalized[] = $scope;
            }
        }

        if ($normalized === []) {
            throw new SyncException('INVALID_SCOPE', \t('sync.api.errors.no_valid_scopes'));
        }

        $this->scopes = $normalized;
    }

    /**
     * @return string[]
     */
    public function toArray(): array
    {
        return $this->scopes;
    }

    public function contains(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    public static function all(): self
    {
        return new self(self::ALL);
    }
}
