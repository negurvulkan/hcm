<?php

require_once __DIR__ . '/audit.php';

use App\StartNumber\StartNumberService;

function getStartNumberRule(array $context): array
{
    return StartNumberService::instance()->getRule($context);
}

function assignStartNumber(array $context, array $subject): array
{
    return StartNumberService::instance()->assign($context, $subject);
}

function releaseStartNumber(array|int $startNumber, string $reason): void
{
    StartNumberService::instance()->release($startNumber, $reason);
}

function formatStartNumber(array|int $startNumber, array $context): string
{
    return StartNumberService::instance()->format($startNumber, $context);
}

function validateStartNumber(array|int $startNumber, array $context): array
{
    return StartNumberService::instance()->validate($startNumber, $context);
}

function resolveNumberBinding(string $printed): ?array
{
    return StartNumberService::instance()->resolveBinding($printed);
}

function lockStartNumber(array|int $startNumber, string $trigger): void
{
    StartNumberService::instance()->lock($startNumber, $trigger);
}

function simulateStartNumbers(array $context, int $count = 20): array
{
    return StartNumberService::instance()->preview($context, $count);
}
