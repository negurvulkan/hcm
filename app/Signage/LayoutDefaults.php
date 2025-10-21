<?php

namespace App\Signage;

use DateTimeImmutable;

class LayoutDefaults
{
    public static function blueprint(string $name = ''): array
    {
        $timestamp = (new DateTimeImmutable('now'))->format('c');
        $idPrefix = substr(bin2hex(random_bytes(6)), 0, 8);

        $elements = [
            [
                'id' => $idPrefix . '_event_title',
                'type' => 'text',
                'label' => 'Eventtitel',
                'layer' => 10,
                'position' => ['x' => 0.04, 'y' => 0.04, 'width' => 0.52, 'height' => 0.09],
                'style' => [
                    'fontFamily' => 'Inter, sans-serif',
                    'fontSize' => 48,
                    'fontWeight' => 700,
                    'color' => '#ffffff',
                    'textAlign' => 'left',
                    'shadow' => ['enabled' => true, 'blur' => 8, 'color' => 'rgba(0,0,0,0.4)'],
                ],
                'content' => [
                    'text' => 'Turnierübersicht',
                ],
                'binding' => [
                    'path' => 'event.title',
                    'fallback' => 'Turnierübersicht',
                ],
                'animations' => [
                    'in' => ['type' => 'fade', 'duration' => 400],
                    'out' => ['type' => 'fade', 'duration' => 400],
                ],
            ],
            [
                'id' => $idPrefix . '_live_rider',
                'type' => 'text',
                'label' => 'Aktueller Starter',
                'layer' => 18,
                'position' => ['x' => 0.04, 'y' => 0.16, 'width' => 0.58, 'height' => 0.09],
                'style' => [
                    'fontFamily' => 'Inter, sans-serif',
                    'fontSize' => 40,
                    'fontWeight' => 600,
                    'color' => '#ffcb2f',
                    'textAlign' => 'left',
                ],
                'content' => [
                    'text' => 'Aktueller Starter',
                ],
                'binding' => [
                    'path' => 'live.current.rider',
                    'fallback' => 'Noch kein Starter',
                ],
            ],
            [
                'id' => $idPrefix . '_live_horse',
                'type' => 'text',
                'label' => 'Pferd',
                'layer' => 18,
                'position' => ['x' => 0.04, 'y' => 0.24, 'width' => 0.58, 'height' => 0.07],
                'style' => [
                    'fontFamily' => 'Inter, sans-serif',
                    'fontSize' => 28,
                    'fontWeight' => 400,
                    'color' => '#ffffff',
                    'textAlign' => 'left',
                ],
                'binding' => [
                    'path' => 'live.current.horse',
                    'fallback' => '',
                ],
            ],
            [
                'id' => $idPrefix . '_score_table',
                'type' => 'table',
                'label' => 'Top-Ergebnisse',
                'layer' => 15,
                'position' => ['x' => 0.04, 'y' => 0.34, 'width' => 0.42, 'height' => 0.5],
                'style' => [
                    'header' => ['background' => 'rgba(255,255,255,0.08)', 'color' => '#ffffff'],
                    'row' => ['background' => 'rgba(0,0,0,0.35)', 'color' => '#ffffff'],
                    'borderRadius' => 12,
                ],
                'binding' => [
                    'path' => 'live.top',
                    'columns' => [
                        ['key' => 'position', 'label' => '#'],
                        ['key' => 'rider', 'label' => 'Reiter'],
                        ['key' => 'horse', 'label' => 'Pferd'],
                        ['key' => 'total', 'label' => 'Score'],
                    ],
                ],
            ],
            [
                'id' => $idPrefix . '_schedule',
                'type' => 'list',
                'label' => 'Zeitplan',
                'layer' => 12,
                'position' => ['x' => 0.48, 'y' => 0.34, 'width' => 0.48, 'height' => 0.5],
                'binding' => [
                    'path' => 'schedule.upcoming',
                    'limit' => 6,
                ],
                'style' => [
                    'background' => 'rgba(0, 0, 0, 0.25)',
                    'borderRadius' => 12,
                    'padding' => 16,
                    'itemGap' => 12,
                    'titleColor' => '#ffffff',
                    'metaColor' => '#9fb7ff',
                ],
            ],
            [
                'id' => $idPrefix . '_sponsor_strip',
                'type' => 'ticker',
                'label' => 'Sponsorenticker',
                'layer' => 30,
                'position' => ['x' => 0, 'y' => 0.92, 'width' => 1, 'height' => 0.08],
                'binding' => [
                    'path' => 'sponsors.messages',
                ],
                'style' => [
                    'background' => 'rgba(0, 0, 0, 0.8)',
                    'color' => '#ffffff',
                    'fontSize' => 24,
                    'fontWeight' => 600,
                    'direction' => 'left',
                ],
            ],
            [
                'id' => $idPrefix . '_clock',
                'type' => 'clock',
                'label' => 'Uhrzeit',
                'layer' => 20,
                'position' => ['x' => 0.82, 'y' => 0.04, 'width' => 0.14, 'height' => 0.08],
                'style' => [
                    'fontFamily' => 'Inter, sans-serif',
                    'fontSize' => 36,
                    'fontWeight' => 500,
                    'color' => '#ffffff',
                    'textAlign' => 'right',
                ],
                'binding' => [
                    'path' => 'clock.time',
                    'format' => 'H:i',
                ],
            ],
        ];

        return [
            'name' => $name,
            'canvas' => [
                'width' => 1920,
                'height' => 1080,
                'background' => '#101418',
                'grid' => ['columns' => 24, 'rows' => 14, 'snap' => true],
                'guides' => ['enabled' => true],
            ],
            'elements' => $elements,
            'timeline' => [
                [
                    'id' => $idPrefix . '_scene_live',
                    'name' => 'Live-Szene',
                    'duration' => 25,
                    'elementIds' => array_column($elements, 'id'),
                    'transitions' => ['in' => 'fade', 'out' => 'fade'],
                ],
                [
                    'id' => $idPrefix . '_scene_sponsors',
                    'name' => 'Sponsoren',
                    'duration' => 15,
                    'elementIds' => [
                        $elements[0]['id'],
                        $elements[4]['id'],
                        $elements[5]['id'],
                    ],
                    'transitions' => ['in' => 'slide-up', 'out' => 'fade'],
                ],
            ],
            'dataSources' => [
                [
                    'key' => 'event',
                    'type' => 'event_meta',
                    'refresh_interval' => 300,
                ],
                [
                    'key' => 'live',
                    'type' => 'live_scores',
                    'refresh_interval' => 10,
                ],
                [
                    'key' => 'schedule',
                    'type' => 'event_schedule',
                    'refresh_interval' => 60,
                ],
                [
                    'key' => 'sponsors',
                    'type' => 'notification_channel',
                    'options' => ['channel' => 'sponsor'],
                    'refresh_interval' => 120,
                ],
                [
                    'key' => 'clock',
                    'type' => 'clock',
                    'refresh_interval' => 1,
                ],
            ],
            'options' => [
                'theme' => [
                    'primary' => '#2f6bff',
                    'accent' => '#ffcb2f',
                    'background' => '#101418',
                    'text' => '#ffffff',
                ],
                'fonts' => [
                    'primary' => 'Inter',
                    'secondary' => 'Barlow Condensed',
                    'useGoogleFonts' => true,
                ],
                'branding' => [
                    'watermark' => [
                        'enabled' => false,
                        'opacity' => 0.18,
                        'path' => null,
                    ],
                ],
                'created_at' => $timestamp,
            ],
            'metadata' => [
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
                'version' => 1,
            ],
        ];
    }
}
