# Digital Signage Module

The digital signage module delivers a browser-based designer and player for event displays. Layouts, playlists and displays can be managed from the back office and rendered on any screen that loads the player URL.

## Key Components

- **Designer (`signage.php`)** – drag & drop editor with timeline, layer control, playlist manager and display assignment.
- **Player (`signage_player.php`)** – fullscreen runtime that downloads the assigned layout/playlist, renders layers via HTML/CSS/Canvas and polls for live data.
- **API (`signage_api.php`)** – REST-style endpoint backed by `App\Signage\SignageRepository` for CRUD operations, playlist validation and live data aggregation.

## Supported Content Elements

| Element | Highlights |
| --- | --- |
| Text & ticker | Custom font size, alignment, live bindings |
| Image | Any remote/local URL, automatic sizing |
| Video & Streams | MP4, WebM, HLS/DASH playlists, YouTube/Vimeo iframes, and tunneled RTSP/RTMP streams |
| Live widgets | Current rider, next starters, leaderboard snippets, countdown clocks |
| Lists & tables | Configurable columns for schedules or sponsor rotations |

## Streaming Notes

- Drop MP4/WebM URLs directly into a video element.
- Use `.m3u8`/`.mpd` sources for HLS/DASH streams – the player injects the correct `<source type>` automatically.
- YouTube or Vimeo links are embedded in an iframe with autoplay/mute/loop controls driven by the layout element options.
- RTSP/RTMP/WebRTC streams can be proxied by defining `gatewayUrl` in the element options, using `{source}` as a placeholder that the player replaces with the original stream URL.

## Display Management

- Displays register via API tokens stored in `signage_displays` and are grouped for bulk assignments.
- Playlists rotate scenes per display group, include drag-and-drop ordering, scheduling fields and enable switches.
- Remote player heartbeat keeps `last_seen_at` updated and allows kiosk monitoring from the dashboard.

## Offline & Caching

The player persists the last successful payload per display token in `localStorage`. When offline, the cached layout is reused until the next successful heartbeat.

## Data Integration

Live overlays read from aggregated event data in `SignageRepository::fetchLiveData`. JSON payloads are delivered through the API so displays can react in near real time.

## Sponsor Directory

The sponsor module (`sponsors.php`) stores tiers, logos and visibility rules for each partner. `SignageRepository` now reads the ticker from this directory (falling back to legacy notifications when no sponsors are published), ensuring the digital signage loop, public display and print exports share the same curated sponsor information.

Run `php setup.php` to execute the migrations `20241101000000__digital_signage_module.php` and `20241105000000__sponsors_module.php` before first use so that the signage scenes and sponsor directory tables are available.
