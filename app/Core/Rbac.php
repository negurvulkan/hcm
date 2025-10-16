<?php
namespace App\Core;

class Rbac
{
    public const ROLES = [
        'admin',
        'meldestelle',
        'richter',
        'parcours',
        'helfer',
        'moderation',
        'teilnehmer',
    ];

    public static function menuFor(string $role): array
    {
        $menu = [
            'dashboard' => ['label' => 'Dashboard', 'roles' => self::ROLES],
            'stammdaten' => ['label' => 'Stammdaten', 'roles' => ['admin', 'meldestelle']],
            'pruefungen' => ['label' => 'Prüfungen', 'roles' => ['admin', 'meldestelle', 'parcours']],
            'nennungen' => ['label' => 'Nennungen', 'roles' => ['admin', 'meldestelle']],
            'zeitplan' => ['label' => 'Zeitplan', 'roles' => ['admin', 'parcours']],
            'helfer' => ['label' => 'Helferkoordination', 'roles' => ['admin', 'helfer']],
            'moderation' => ['label' => 'Moderation', 'roles' => ['admin', 'moderation']],
            'druck' => ['label' => 'Druck & PDFs', 'roles' => ['admin', 'meldestelle', 'parcours']],
        ];

        return array_filter($menu, static fn ($item) => in_array($role, $item['roles'], true));
    }
}
