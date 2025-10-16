<?php
namespace App\Sync;

class ImportReport
{
    /**
     * @var array<string, array<int, array{id: string, message: string}>>
     */
    private array $accepted = [];

    /**
     * @var array<string, array<int, array{id: string, reason: string, message: string}>>
     */
    private array $rejected = [];

    /**
     * @var array<int, array{code: string, message: string}>
     */
    private array $errors = [];

    public function addAccepted(string $scope, string $id, string $message = 'applied'): void
    {
        $this->accepted[$scope][] = ['id' => $id, 'message' => $message];
    }

    public function addRejected(string $scope, string $id, string $reason, string $message): void
    {
        $this->rejected[$scope][] = ['id' => $id, 'reason' => $reason, 'message' => $message];
    }

    public function addError(string $code, string $message): void
    {
        $this->errors[] = ['code' => $code, 'message' => $message];
    }

    /**
     * @return array{accepted: array<string, array<int, array{id: string, message: string}>>, rejected: array<string, array<int, array{id: string, reason: string, message: string}>>, errors: array<int, array{code: string, message: string}>}
     */
    public function toArray(): array
    {
        return [
            'accepted' => $this->accepted,
            'rejected' => $this->rejected,
            'errors' => $this->errors,
        ];
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
