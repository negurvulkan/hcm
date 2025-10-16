<?php
namespace App\Sync;

class ValidationReport
{
    /**
     * @var array<int, array{scope: string, id: string, code: string, message: string}>
     */
    private array $issues = [];

    public function addIssue(string $scope, string $id, string $code, string $message): void
    {
        $this->issues[] = [
            'scope' => $scope,
            'id' => $id,
            'code' => $code,
            'message' => $message,
        ];
    }

    public function isValid(): bool
    {
        return $this->issues === [];
    }

    /**
     * @return array{valid: bool, issues: array<int, array{scope: string, id: string, code: string, message: string}>}
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->isValid(),
            'issues' => $this->issues,
        ];
    }
}
