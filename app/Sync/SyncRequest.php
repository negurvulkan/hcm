<?php
namespace App\Sync;

class SyncRequest
{
    private string $operation;
    private string $method;
    private bool $write;

    /**
     * @var string[]
     */
    private array $scopes;

    /**
     * @param string[] $scopes
     */
    public function __construct(string $operation, string $method = 'GET', bool $write = false, array $scopes = [])
    {
        $this->operation = strtolower($operation);
        $this->method = strtoupper($method);
        $this->write = $write;
        $this->scopes = array_values(array_unique(array_map('strtolower', $scopes)));
    }

    public function operation(): string
    {
        return $this->operation;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isWrite(): bool
    {
        return $this->write;
    }

    /**
     * @return string[]
     */
    public function scopes(): array
    {
        return $this->scopes;
    }
}
