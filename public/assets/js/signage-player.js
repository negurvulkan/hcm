(function () {
    'use strict';

    function deepClone(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function resolvePath(data, path) {
        if (!path) {
            return null;
        }
        const parts = path.split('.');
        let current = data;
        for (const part of parts) {
            if (current == null || typeof current !== 'object') {
                return null;
            }
            current = current[part];
        }
        return current;
    }

    function formatValue(value) {
        if (value == null) {
            return '';
        }
        if (Array.isArray(value)) {
            return value.join(' · ');
        }
        if (typeof value === 'object') {
            return Object.values(value).join(' ');
        }
        return String(value);
    }

    function applyElementStyles(node, style) {
        if (!style || typeof style !== 'object') {
            return;
        }
        if (style.padding != null) {
            node.style.padding = typeof style.padding === 'number' ? `${style.padding}px` : String(style.padding);
        }
        if (style.borderRadius != null) {
            node.style.borderRadius = typeof style.borderRadius === 'number' ? `${style.borderRadius}px` : String(style.borderRadius);
        }
        if (style.boxShadow) {
            node.style.boxShadow = style.boxShadow;
        }
        if (style.opacity != null) {
            node.style.opacity = String(style.opacity);
        }
    }

    function isSafeHttpUrl(value) {
        if (typeof value !== 'string') {
            return false;
        }
        const normalized = value.trim().toLowerCase();
        return normalized.startsWith('http://') || normalized.startsWith('https://');
    }

    function parseYouTubeId(url) {
        if (typeof url !== 'string') {
            return null;
        }
        const patterns = [
            /youtube\.com\/(?:watch\?v=|embed\/)([\w-]{11})/i,
            /youtu\.be\/([\w-]{11})/i,
        ];
        for (const pattern of patterns) {
            const match = url.match(pattern);
            if (match && match[1]) {
                return match[1];
            }
        }
        return null;
    }

    function buildYoutubeEmbed(id, options) {
        if (!id) {
            return null;
        }
        const params = new URLSearchParams({
            autoplay: options.autoplay ? '1' : '0',
            mute: options.muted ? '1' : '0',
            loop: options.loop ? '1' : '0',
        });
        if (options.loop) {
            params.set('playlist', id);
        }
        return `https://www.youtube.com/embed/${encodeURIComponent(id)}?${params.toString()}`;
    }

    function parseVimeoId(url) {
        if (typeof url !== 'string') {
            return null;
        }
        const match = url.match(/vimeo\.com\/(?:video\/)?(\d+)/i);
        return match && match[1] ? match[1] : null;
    }

    function buildVimeoEmbed(id, options) {
        if (!id) {
            return null;
        }
        const params = new URLSearchParams({
            autoplay: options.autoplay ? '1' : '0',
            muted: options.muted ? '1' : '0',
            loop: options.loop ? '1' : '0',
        });
        return `https://player.vimeo.com/video/${encodeURIComponent(id)}?${params.toString()}`;
    }

    function buildIframe(url, options) {
        if (!url) {
            return null;
        }
        const iframe = document.createElement('iframe');
        iframe.src = url;
        iframe.loading = 'lazy';
        iframe.allowFullscreen = true;
        iframe.setAttribute('allow', options.allow || 'autoplay; encrypted-media; picture-in-picture');
        iframe.setAttribute('frameborder', '0');
        iframe.className = 'signage-player-video-frame';
        return iframe;
    }

    function formatScore(value) {
        if (value == null) {
            return '';
        }
        const number = Number(value);
        if (Number.isFinite(number)) {
            if (Math.abs(number) >= 100) {
                return number.toFixed(1);
            }
            return number % 1 === 0 ? number.toString() : number.toFixed(2);
        }
        return String(value);
    }

    function parseClockBase(value) {
        if (!value) {
            return null;
        }
        try {
            if (typeof value === 'string' && value.match(/^\d{2}:\d{2}(:\d{2})?$/)) {
                const parts = value.split(':').map((part) => Number(part));
                const now = new Date();
                now.setHours(parts[0], parts[1], parts[2] ?? 0, 0);
                return now;
            }
            const date = new Date(value);
            return Number.isNaN(date.getTime()) ? null : date;
        } catch (error) {
            return null;
        }
    }

    function formatClockValue(date, format) {
        if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
            return '';
        }
        const normalized = (format || 'HH:mm').toLowerCase();
        const includeSeconds = normalized.includes('ss');
        const includeMinutes = normalized.includes('mm') || normalized.includes('ii') || normalized.includes(':');
        const includeTimezone = normalized.includes('z');
        const options = { hour: '2-digit' };
        if (includeMinutes) {
            options.minute = '2-digit';
        }
        if (includeSeconds) {
            options.second = '2-digit';
        }
        let formatted = date.toLocaleTimeString([], options);
        if (includeTimezone) {
            formatted = `${formatted} ${Intl.DateTimeFormat().resolvedOptions().timeZone}`;
        }
        return formatted;
    }

    function buildScheduleMeta(entry) {
        if (!entry || typeof entry !== 'object') {
            return '';
        }
        const parts = [];
        if (entry.start_time) {
            parts.push(formatTime(entry.start_time));
        }
        if (entry.arena) {
            parts.push(String(entry.arena));
        }
        if (entry.label && entry.arena && !parts.includes(String(entry.label))) {
            parts.push(String(entry.label));
        }
        return parts.join(' · ');
    }

    class SignagePlayer {
        constructor(state, token) {
            this.token = token;
            this.root = document.getElementById('signage-player-root');
            this.state = state;
            this.sceneTimer = null;
            this.pollTimer = null;
            this.currentPlaylistIndex = 0;
            this.currentSceneIndex = 0;
            this.layoutMap = new Map();
            this.sceneDurationFallback = 30;
            this.cacheKey = `signage_player_state_${token}`;
            this.clockTimer = null;
            this.clockNodes = [];

            if (!this.root) {
                return;
            }

            this.applyState(state);
            this.startPolling();
        }

        applyState(state) {
            this.state = state;
            if (state.status !== 'ok') {
                return;
            }
            if (state.cache_ttl) {
                try {
                    localStorage.setItem(this.cacheKey, JSON.stringify(state));
                } catch (error) {
                    // ignore
                }
            }
            this.buildLayoutMap();
            this.renderActiveSequence();
        }

        buildLayoutMap() {
            this.layoutMap.clear();
            const layouts = Array.isArray(this.state.layouts) ? this.state.layouts : [];
            layouts.forEach((layout) => {
                this.layoutMap.set(String(layout.id), deepClone(layout));
            });
            if (this.state.active_layout) {
                const active = deepClone(this.state.active_layout);
                if (active.id != null) {
                    this.layoutMap.set(String(active.id), active);
                } else {
                    this.layoutMap.set('active', active);
                }
            }
        }

        renderActiveSequence() {
            if (!this.root) {
                return;
            }
            this.stopTimers();
            this.root.innerHTML = '';
            const playlist = this.state.playlist;
            if (playlist && Array.isArray(playlist.items) && playlist.items.length > 0) {
                this.playlistItems = playlist.items.map((item) => {
                    const layout = this.layoutMap.get(String(item.layout_id)) || this.state.active_layout || this.layoutMap.values().next().value;
                    return {
                        layout: layout ? deepClone(layout) : null,
                        duration: Number(item.duration_seconds) || Number(playlist.rotation_seconds) || 30,
                        label: item.label || null,
                    };
                }).filter((item) => item.layout);
                if (this.playlistItems.length === 0) {
                    const fallbackLayout = this.state.active_layout || this.layoutMap.values().next().value;
                    this.playlistItems = fallbackLayout ? [{ layout: deepClone(fallbackLayout), duration: Number(playlist.rotation_seconds) || 30 }] : [];
                }
            } else {
                const layout = this.state.active_layout || this.layoutMap.values().next().value;
                this.playlistItems = layout ? [{ layout: deepClone(layout), duration: 30 }] : [];
            }
            this.currentPlaylistIndex = 0;
            this.currentSceneIndex = 0;
            this.renderPlaylistItem();
        }

        renderPlaylistItem() {
            if (!this.playlistItems || this.playlistItems.length === 0) {
                this.root.innerHTML = '<div class="signage-player-error">No layout assigned.</div>';
                return;
            }
            const item = this.playlistItems[this.currentPlaylistIndex % this.playlistItems.length];
            if (!item || !item.layout) {
                return;
            }
            this.applyTheme(item.layout);
            this.renderLayout(item.layout);
            this.playScenes(item.layout);
        }

        applyTheme(layout) {
            const theme = layout.options?.theme || {};
            if (theme.background) {
                document.documentElement.style.setProperty('--signage-bg', theme.background);
            }
            if (theme.text) {
                document.documentElement.style.setProperty('--signage-text', theme.text);
            }
            if (theme.primary) {
                document.documentElement.style.setProperty('--signage-accent', theme.primary);
            }
        }

        renderLayout(layout) {
            this.root.innerHTML = '';
            const scenes = Array.isArray(layout.timeline) && layout.timeline.length > 0 ? layout.timeline : [{
                id: 'default',
                name: 'Default',
                duration: layout.options?.defaultDuration || this.sceneDurationFallback,
                elementIds: (layout.elements || []).map((element) => element.id),
            }];
            this.currentScenes = scenes;
            const elements = Array.isArray(layout.elements) ? layout.elements : [];
            const container = document.createElement('div');
            container.className = 'signage-player-layout';
            this.clockNodes = [];
            scenes.forEach((scene) => {
                const sceneNode = document.createElement('div');
                sceneNode.className = 'signage-player-scene';
                sceneNode.dataset.sceneId = scene.id;
                const elementIds = Array.isArray(scene.elementIds) && scene.elementIds.length > 0
                    ? scene.elementIds
                    : elements.map((element) => element.id);
                elementIds.forEach((elementId) => {
                    const element = elements.find((item) => item.id === elementId);
                    if (!element) {
                        return;
                    }
                    const elementNode = this.renderElement(element, layout);
                    if (elementNode) {
                        sceneNode.appendChild(elementNode);
                    }
                });
                container.appendChild(sceneNode);
            });
            this.root.appendChild(container);
            this.sceneNodes = Array.from(container.querySelectorAll('.signage-player-scene'));
            this.startClockTicker();
        }

        renderElement(element, layout) {
            const node = document.createElement('div');
            node.className = `signage-player-element signage-player-element--${element.type || 'text'}`;
            const position = element.position || {};
            const safeX = Math.max(0, Math.min(1, position.x ?? 0));
            const safeY = Math.max(0, Math.min(1, position.y ?? 0));
            const safeWidth = Math.max(0.02, Math.min(1 - safeX, position.width ?? 0.3));
            const safeHeight = Math.max(0.02, Math.min(1 - safeY, position.height ?? 0.1));
            node.style.left = `${safeX * 100}%`;
            node.style.top = `${safeY * 100}%`;
            node.style.width = `${safeWidth * 100}%`;
            node.style.height = `${safeHeight * 100}%`;
            const style = element.style || {};
            if (style.color) {
                node.style.color = style.color;
            }
            if (style.background) {
                node.style.background = style.background;
            }
            if (style.fontSize) {
                node.style.fontSize = `${style.fontSize}px`;
            }
            if (style.textAlign) {
                node.style.textAlign = style.textAlign;
            }
            if (style.fontWeight) {
                node.style.fontWeight = style.fontWeight;
            }
            applyElementStyles(node, style);
            const bindingValue = resolvePath(this.state.data, element.binding?.path);
            const fallback = element.binding?.fallback ?? element.content?.text ?? '';
            switch (element.type) {
                case 'text':
                    node.innerHTML = `<div class="signage-player-text">${escapeHtml(formatValue(bindingValue) || fallback)}</div>`;
                    break;
                case 'ticker': {
                    const messages = Array.isArray(bindingValue) ? bindingValue : [formatValue(bindingValue) || fallback || ''];
                    const track = document.createElement('div');
                    track.className = 'signage-player-ticker-track';
                    track.innerHTML = messages.map((message) => `<span>${escapeHtml(message)}</span>`).join('');
                    const tickerDirection = element.options?.direction || style.direction;
                    if (tickerDirection && String(tickerDirection).toLowerCase() === 'right') {
                        track.style.animationDirection = 'reverse';
                    }
                    const tickerSpeed = Number(element.options?.speed ?? style.speed ?? 0);
                    if (Number.isFinite(tickerSpeed) && tickerSpeed > 0) {
                        track.style.animationDuration = `${tickerSpeed}s`;
                    }
                    node.appendChild(track);
                    break;
                }
                case 'image': {
                    const src = element.content?.src || formatValue(bindingValue) || '';
                    if (src) {
                        const img = document.createElement('img');
                        img.src = src;
                        img.alt = element.label || 'Image';
                        node.appendChild(img);
                    } else {
                        node.textContent = fallback || 'Image';
                    }
                    break;
                }
                case 'video': {
                    const rawBinding = typeof bindingValue === 'string' ? bindingValue : formatValue(bindingValue);
                    const sourceRaw = (element.content?.source || rawBinding || '').trim();
                    const options = element.options || {};
                    const poster = options.poster || element.content?.poster;
                    const autoplay = options.autoplay !== false;
                    const muted = options.muted !== false;
                    const loop = options.loop === true || options.loop === undefined;
                    const controls = options.controls === true;
                    const provider = String(options.provider || element.content?.provider || '').toLowerCase();

                    if (!sourceRaw) {
                        node.innerHTML = `<div class="signage-player-text">${escapeHtml(fallback || 'Video')}</div>`;
                        break;
                    }

                    const youtubeId = provider === 'youtube' ? (options.videoId || parseYouTubeId(sourceRaw)) : parseYouTubeId(sourceRaw);
                    if (youtubeId) {
                        const embedUrl = buildYoutubeEmbed(youtubeId, { autoplay, muted, loop });
                        const iframe = buildIframe(embedUrl, { allow: 'autoplay; encrypted-media; picture-in-picture' });
                        if (iframe) {
                            node.appendChild(iframe);
                            break;
                        }
                    }

                    const vimeoId = provider === 'vimeo' ? (options.videoId || parseVimeoId(sourceRaw)) : parseVimeoId(sourceRaw);
                    if (vimeoId) {
                        const embedUrl = buildVimeoEmbed(vimeoId, { autoplay, muted, loop });
                        const iframe = buildIframe(embedUrl, { allow: 'autoplay; fullscreen; picture-in-picture' });
                        if (iframe) {
                            node.appendChild(iframe);
                            break;
                        }
                    }

                    const gatewayTemplate = options.gatewayUrl || element.content?.gatewayUrl;
                    if ((sourceRaw.startsWith('rtsp://') || sourceRaw.startsWith('rtmp://')) && gatewayTemplate) {
                        const embedUrl = gatewayTemplate.replace('{source}', encodeURIComponent(sourceRaw));
                        const iframe = buildIframe(embedUrl, { allow: options.allow || 'autoplay; fullscreen' });
                        if (iframe) {
                            node.appendChild(iframe);
                            break;
                        }
                    }

                    if (provider === 'iframe' || provider === 'embed' || options.embedUrl || element.content?.embedUrl) {
                        const rawEmbed = options.embedUrl || element.content?.embedUrl || sourceRaw;
                        const embedUrl = gatewayTemplate ? gatewayTemplate.replace('{source}', encodeURIComponent(rawEmbed)) : rawEmbed;
                        if (isSafeHttpUrl(embedUrl)) {
                            const iframe = buildIframe(embedUrl, { allow: options.allow || 'autoplay; fullscreen; picture-in-picture' });
                            if (iframe) {
                                node.appendChild(iframe);
                                break;
                            }
                        }
                    }

                    const normalizedSource = sourceRaw.toLowerCase();
                    const isHls = provider === 'hls' || normalizedSource.includes('.m3u8');
                    const isDash = provider === 'dash' || normalizedSource.includes('.mpd');
                    const isVideoFile = normalizedSource.endsWith('.mp4') || normalizedSource.endsWith('.webm') || normalizedSource.endsWith('.ogv');

                    if (isHls || isDash || isVideoFile || isSafeHttpUrl(sourceRaw)) {
                        const video = document.createElement('video');
                        if (poster && isSafeHttpUrl(poster)) {
                            video.poster = poster;
                        }
                        video.autoplay = autoplay;
                        video.loop = loop;
                        video.muted = muted;
                        video.playsInline = true;
                        if (controls) {
                            video.controls = true;
                        }
                        if (isHls) {
                            const source = document.createElement('source');
                            source.src = sourceRaw;
                            source.type = 'application/x-mpegURL';
                            video.appendChild(source);
                        } else if (isDash) {
                            const source = document.createElement('source');
                            source.src = sourceRaw;
                            source.type = 'application/dash+xml';
                            video.appendChild(source);
                        } else {
                            video.src = sourceRaw;
                        }
                        node.appendChild(video);
                        break;
                    }

                    node.innerHTML = `<div class="signage-player-text">${escapeHtml(fallback || sourceRaw || 'Video')}</div>`;
                    break;
                }
                case 'table': {
                    const rows = Array.isArray(bindingValue) ? bindingValue : [];
                    const columns = Array.isArray(element.binding?.columns) ? element.binding.columns : [];
                    const table = document.createElement('table');
                    table.className = 'signage-player-table';
                    if (columns.length > 0) {
                        const head = document.createElement('thead');
                        head.innerHTML = `<tr>${columns.map((column) => `<th>${escapeHtml(column.label || column.key)}</th>`).join('')}</tr>`;
                        table.appendChild(head);
                    }
                    const body = document.createElement('tbody');
                    rows.forEach((row) => {
                        const tr = document.createElement('tr');
                        columns.forEach((column) => {
                            const value = row[column.key];
                            const cell = document.createElement('td');
                            cell.textContent = formatValue(value);
                            tr.appendChild(cell);
                        });
                        body.appendChild(tr);
                    });
                    table.appendChild(body);
                    node.appendChild(table);
                    break;
                }
                case 'list': {
                    const entries = Array.isArray(bindingValue) ? bindingValue : [];
                    const limit = Number(element.binding?.limit ?? entries.length);
                    const visibleEntries = entries.slice(0, limit > 0 ? limit : entries.length);
                    if (visibleEntries.length === 0) {
                        node.innerHTML = `<div class="signage-player-text">${escapeHtml(fallback || '')}</div>`;
                        break;
                    }
                    const list = document.createElement('div');
                    list.className = 'signage-player-list';
                    if (style.itemGap != null) {
                        list.style.gap = typeof style.itemGap === 'number' ? `${style.itemGap}px` : String(style.itemGap);
                    }
                    visibleEntries.forEach((entry) => {
                        const item = document.createElement('div');
                        item.className = 'signage-player-list__item';
                        const title = document.createElement('div');
                        title.className = 'signage-player-list__title';
                        title.textContent = entry.label || entry.name || '';
                        if (style.titleColor) {
                            title.style.color = style.titleColor;
                        }
                        const metaText = buildScheduleMeta(entry);
                        const meta = document.createElement('div');
                        meta.className = 'signage-player-list__meta';
                        meta.textContent = metaText;
                        if (style.metaColor) {
                            meta.style.color = style.metaColor;
                        }
                        item.appendChild(title);
                        if (metaText) {
                            item.appendChild(meta);
                        }
                        list.appendChild(item);
                    });
                    node.appendChild(list);
                    break;
                }
                case 'live': {
                    const liveData = this.state.data?.live || {};
                    const options = element.options || {};
                    const current = bindingValue && typeof bindingValue === 'object' ? bindingValue : liveData.current;
                    node.classList.add('signage-player-live');
                    if (current) {
                        const currentNode = document.createElement('div');
                        currentNode.className = 'signage-player-live__current';
                        const badge = current.start_number_display ? `<span class="signage-player-live__badge">${escapeHtml(current.start_number_display)}</span>` : '';
                        currentNode.innerHTML = `
                            <div class="signage-player-live__label">${escapeHtml(current.class_label || options.currentLabel || '')}</div>
                            <div class="signage-player-live__rider">${badge}<span>${escapeHtml(current.rider || fallback || '')}</span></div>
                            <div class="signage-player-live__horse">${escapeHtml(current.horse || '')}</div>
                        `;
                        node.appendChild(currentNode);
                    }
                    const nextEntries = Array.isArray(liveData.next) ? liveData.next.slice(0, options.nextLimit ?? 3) : [];
                    if (options.showNext !== false && nextEntries.length > 0) {
                        const nextWrapper = document.createElement('div');
                        nextWrapper.className = 'signage-player-live__next';
                        const nextTitle = document.createElement('div');
                        nextTitle.className = 'signage-player-live__section-title';
                        nextTitle.textContent = options.nextLabel || 'Nächste Starter';
                        nextWrapper.appendChild(nextTitle);
                        const nextList = document.createElement('ul');
                        nextList.className = 'signage-player-live__next-list';
                        nextEntries.forEach((entry) => {
                            const item = document.createElement('li');
                            item.className = 'signage-player-live__next-item';
                            const badge = entry.start_number_display ? `<span class="signage-player-live__badge">${escapeHtml(entry.start_number_display)}</span>` : '';
                            item.innerHTML = `
                                ${badge}
                                <div>
                                    <div class="signage-player-live__name">${escapeHtml(entry.rider || '')}</div>
                                    <div class="signage-player-live__horse">${escapeHtml(entry.horse || '')}</div>
                                </div>
                            `;
                            nextList.appendChild(item);
                        });
                        nextWrapper.appendChild(nextList);
                        node.appendChild(nextWrapper);
                    }
                    const topEntries = Array.isArray(liveData.top) ? liveData.top.slice(0, options.leaderboardLimit ?? 5) : [];
                    if (options.showLeaderboard !== false && topEntries.length > 0) {
                        const board = document.createElement('div');
                        board.className = 'signage-player-live__leaderboard';
                        const boardTitle = document.createElement('div');
                        boardTitle.className = 'signage-player-live__section-title';
                        boardTitle.textContent = options.leaderboardLabel || 'Top-Ergebnisse';
                        board.appendChild(boardTitle);
                        const boardBody = document.createElement('div');
                        boardBody.className = 'signage-player-live__leaderboard-body';
                        topEntries.forEach((entry, index) => {
                            const row = document.createElement('div');
                            row.className = 'signage-player-live__leaderboard-row';
                            const position = entry.position ?? index + 1;
                            row.innerHTML = `
                                <span class="signage-player-live__position">${escapeHtml(String(position))}</span>
                                <span class="signage-player-live__name">${escapeHtml(entry.rider || '')}</span>
                                <span class="signage-player-live__horse">${escapeHtml(entry.horse || '')}</span>
                                <span class="signage-player-live__score">${escapeHtml(formatScore(entry.total))}</span>
                            `;
                            boardBody.appendChild(row);
                        });
                        board.appendChild(boardBody);
                        node.appendChild(board);
                    }
                    if (!node.children.length) {
                        node.textContent = fallback || 'Live data';
                    }
                    break;
                }
                case 'clock': {
                    const format = element.binding?.format || 'HH:mm';
                    const baseValue = bindingValue ?? this.state.data?.clock?.iso ?? this.state.data?.clock?.time;
                    const baseDate = parseClockBase(baseValue) ?? new Date();
                    const clockText = document.createElement('div');
                    clockText.className = 'signage-player-clock';
                    clockText.textContent = formatClockValue(baseDate, format) || fallback;
                    node.appendChild(clockText);
                    this.clockNodes.push({ node: clockText, base: baseDate, format });
                    break;
                }
                default:
                    node.textContent = formatValue(bindingValue) || fallback || element.label || '';
                    break;
            }
            return node;
        }

        playScenes(layout) {
            if (!this.sceneNodes || this.sceneNodes.length === 0) {
                return;
            }
            this.sceneNodes.forEach((node) => node.classList.remove('is-active'));
            this.currentSceneIndex = 0;
            const advance = () => {
                this.sceneNodes.forEach((node) => node.classList.remove('is-active'));
                const scene = this.currentScenes[this.currentSceneIndex % this.currentScenes.length];
                const node = this.sceneNodes[this.currentSceneIndex % this.sceneNodes.length];
                if (node) {
                    node.classList.add('is-active');
                }
                const duration = Math.max(5, Number(scene.duration) || this.sceneDurationFallback);
                this.sceneTimer = window.setTimeout(() => {
                    this.currentSceneIndex = (this.currentSceneIndex + 1) % this.currentScenes.length;
                    if (this.currentSceneIndex === 0) {
                        this.currentPlaylistIndex = (this.currentPlaylistIndex + 1) % this.playlistItems.length;
                        this.renderPlaylistItem();
                    } else {
                        advance();
                    }
                }, duration * 1000);
            };
            advance();
        }

        stopTimers() {
            if (this.sceneTimer) {
                window.clearTimeout(this.sceneTimer);
                this.sceneTimer = null;
            }
            if (this.pollTimer) {
                window.clearTimeout(this.pollTimer);
                this.pollTimer = null;
            }
            if (this.clockTimer) {
                window.clearInterval(this.clockTimer);
                this.clockTimer = null;
            }
        }

        startClockTicker() {
            if (this.clockTimer) {
                window.clearInterval(this.clockTimer);
                this.clockTimer = null;
            }
            if (!Array.isArray(this.clockNodes) || this.clockNodes.length === 0) {
                return;
            }
            const references = this.clockNodes
                .map((entry) => {
                    if (!entry || !entry.node) {
                        return null;
                    }
                    const base = entry.base instanceof Date ? entry.base : parseClockBase(entry.base);
                    if (!base) {
                        return null;
                    }
                    return {
                        node: entry.node,
                        format: entry.format,
                        offset: Date.now() - base.getTime(),
                    };
                })
                .filter(Boolean);
            if (references.length === 0) {
                return;
            }
            const update = () => {
                const now = Date.now();
                references.forEach((entry) => {
                    const current = new Date(now - entry.offset);
                    entry.node.textContent = formatClockValue(current, entry.format);
                });
            };
            update();
            this.clockTimer = window.setInterval(update, 1000);
        }

        startPolling() {
            const interval = Math.max(15, Number(this.state.display?.heartbeat) || 60);
            const poll = () => {
                fetch(`signage_api.php?action=player_state&token=${encodeURIComponent(this.token)}`)
                    .then((response) => response.json())
                    .then((data) => {
                        if (!data || data.status !== 'ok') {
                            return;
                        }
                        this.applyState(data);
                    })
                    .catch(() => {
                        // ignore fetch errors, rely on cached state
                    })
                    .finally(() => {
                        this.pollTimer = window.setTimeout(poll, interval * 1000);
                    });
            };
            this.pollTimer = window.setTimeout(poll, interval * 1000);
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatTime(value) {
        try {
            const date = new Date(value);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        } catch (error) {
            return value;
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        let state = window.SIGNAGE_PLAYER_STATE;
        const token = window.SIGNAGE_PLAYER_TOKEN || '';
        if ((!state || state.status !== 'ok') && token) {
            try {
                const cached = localStorage.getItem(`signage_player_state_${token}`);
                if (cached) {
                    const parsed = JSON.parse(cached);
                    if (parsed && parsed.status === 'ok') {
                        state = parsed;
                    }
                }
            } catch (error) {
                // ignore
            }
        }
        if (!state || !token) {
            return;
        }
        new SignagePlayer(state, token);
    });
})();
