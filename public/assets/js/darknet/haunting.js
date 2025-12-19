(function (window, document) {
    'use strict';

    const STORAGE_PREFIX = 'darknetz-haunting:';
    const COOLDOWN_KEY = 'darknetz-haunt-cooldown-until';
    const WHISPER_KEY = 'darknetz-whispers:';

    const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
    const randomBetween = (min, max) => min + (Math.random() * (max - min));
    const pick = (items) => {
        if (!Array.isArray(items) || items.length === 0) {
            return null;
        }
        const index = Math.floor(Math.random() * items.length);
        return items[index];
    };
    const now = () => Date.now();

    const fillTemplate = (template, tokens) => {
        if (!template) {
            return '';
        }
        return template.replace(/{{(.*?)}}/g, (_, key) => {
            const value = tokens[key.trim()];
            return value === undefined || value === null ? '' : String(value);
        });
    };

    const renderDuration = (ms) => {
        if (!Number.isFinite(ms) || ms <= 0) {
            return '0m';
        }
        const totalMinutes = Math.round(ms / 60000);
        const hours = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        if (hours <= 0) {
            return `${minutes}m`;
        }
        return `${hours}h ${minutes}m`;
    };

    const resolveSpirit = (id) => {
        const { spirits, defaults } = window.DarknetSpirits || {};
        const spirit = spirits && spirits[id];
        if (spirit) {
            return spirit;
        }
        const first = spirits ? Object.values(spirits)[0] : null;
        if (first) {
            return first;
        }
        return {
            id: 'default',
            name: 'Ghost Packet',
            tags: ['glitch'],
            templates: {},
            haunting: {},
            words: {},
            defaults,
        };
    };

    function HauntingManager(options) {
        this.username = (options && options.username) || 'guest';
        this.terminal = options && options.terminal;
        this.spirits = (window.DarknetSpirits && window.DarknetSpirits.spirits) || {};
        this.defaults = (window.DarknetSpirits && window.DarknetSpirits.defaults) || {};
        this.baseWords = (window.DarknetSpirits && window.DarknetSpirits.baseWords) || {};
        this.baseTemplates = (window.DarknetSpirits && window.DarknetSpirits.baseTemplates) || {};
        this.state = null;
        this.tickTimer = null;
        this.whispers = this.loadWhispers();
        this.banishCooldownUntil = 0;
    }

    HauntingManager.prototype.storageKey = function () {
        return STORAGE_PREFIX + this.username;
    };

    HauntingManager.prototype.loadWhispers = function () {
        const raw = window.localStorage.getItem(WHISPER_KEY + this.username);
        if (!raw) {
            return [];
        }
        try {
            const parsed = JSON.parse(raw);
            if (Array.isArray(parsed)) {
                return parsed;
            }
        } catch (error) {
            console.warn('[haunting] Failed to parse whispers', error);
        }
        return [];
    };

    HauntingManager.prototype.saveWhispers = function () {
        window.localStorage.setItem(WHISPER_KEY + this.username, JSON.stringify(this.whispers.slice(-12)));
    };

    HauntingManager.prototype.recordWhisper = function (word) {
        const entry = {
            at: now(),
            word: word || pick(['blood', 'ash', 'silence', 'echo', 'frost']),
        };
        this.whispers.push(entry);
        this.saveWhispers();
        return entry;
    };

    HauntingManager.prototype.cooldownActive = function () {
        const raw = window.localStorage.getItem(COOLDOWN_KEY);
        const until = raw ? Number(raw) : 0;
        return Number.isFinite(until) && until > now();
    };

    HauntingManager.prototype.setCooldown = function (hours) {
        const duration = Math.max(1, hours || this.defaults.cooldownHours || 12) * 3600000;
        window.localStorage.setItem(COOLDOWN_KEY, String(now() + duration));
    };

    HauntingManager.prototype.load = function () {
        const raw = window.localStorage.getItem(this.storageKey());
        if (!raw) {
            this.state = null;
            return null;
        }
        try {
            const parsed = JSON.parse(raw);
            if (parsed && parsed.endsAt && parsed.endsAt < now()) {
                this.clear('expired');
                return null;
            }
            this.state = parsed;
            return parsed;
        } catch (error) {
            console.warn('[haunting] Parse failed', error);
            this.state = null;
            return null;
        }
    };

    HauntingManager.prototype.save = function () {
        if (!this.state) {
            return;
        }
        window.localStorage.setItem(this.storageKey(), JSON.stringify(this.state));
    };

    HauntingManager.prototype.clear = function (reason) {
        if (this.tickTimer) {
            window.clearTimeout(this.tickTimer);
            this.tickTimer = null;
        }
        window.localStorage.removeItem(this.storageKey());
        this.state = null;
        if (reason === 'banished' || reason === 'ended') {
            this.setCooldown(this.defaults.cooldownHours);
        }
    };

    HauntingManager.prototype.bootstrap = function () {
        const active = this.load();
        if (active) {
            this.scheduleTick(true);
            if (this.terminal) {
                this.terminal.hauntingActivated(active);
            }
            return active;
        }
        this.maybeStart('boot');
        return null;
    };

    HauntingManager.prototype.resolveChance = function (reason) {
        const overrides = this.state && this.state.haunting ? this.state.haunting : null;
        const spirit = this.state ? resolveSpirit(this.state.spiritId) : null;
        const fromSpirit = spirit && spirit.haunting ? spirit.haunting : {};
        const base = {
            boot: (fromSpirit.baseChanceBoot ?? overrides?.baseChanceBoot) ?? this.defaults.baseChanceBoot ?? 0.01,
            scan: (fromSpirit.baseChanceScan ?? overrides?.baseChanceScan) ?? this.defaults.baseChanceScan ?? 0.01,
            cat: (fromSpirit.baseChanceCat ?? overrides?.baseChanceCat) ?? this.defaults.baseChanceCat ?? 0.02,
            seance: (fromSpirit.baseChanceSeanceListen ?? overrides?.baseChanceSeanceListen) ?? this.defaults.baseChanceSeanceListen ?? 0.005,
            idle: (fromSpirit.baseChanceIdle ?? overrides?.baseChanceIdle) ?? this.defaults.baseChanceIdle ?? 0.012,
        };
        switch (reason) {
            case 'boot':
                return base.boot;
            case 'scan':
                return base.scan;
            case 'cat':
                return base.cat;
            case 'seance':
                return base.seance;
            default:
                return base.idle;
        }
    };

    HauntingManager.prototype.maybeStart = function (reason, options) {
        if (this.state || this.cooldownActive()) {
            return false;
        }
        const spirit = pick(Object.keys(this.spirits));
        if (!spirit) {
            return false;
        }
        const chance = this.resolveChance(reason) + (options && options.extraChance ? options.extraChance : 0);
        if (Math.random() <= chance) {
            this.start(spirit, options || {});
            return true;
        }
        return false;
    };

    HauntingManager.prototype.start = function (spiritId, options) {
        const spirit = resolveSpirit(spiritId);
        const config = spirit.haunting || {};
        const ttlMin = (config.ttlHoursMin ?? this.defaults.ttlHoursMin ?? 2) * 3600000;
        const ttlMax = (config.ttlHoursMax ?? this.defaults.ttlHoursMax ?? 48) * 3600000;
        const startedAt = now();
        const endsAt = startedAt + randomBetween(ttlMin, ttlMax);
        const intensity = clamp(options.intensity ?? randomBetween(0.25, 0.6), 0.05, 1);
        const intervalMin = (config.intervalSecondsMin ?? this.defaults.intervalSecondsMin ?? 30) * 1000;
        const intervalMax = (config.intervalSecondsMax ?? this.defaults.intervalSecondsMax ?? 180) * 1000;
        const nextHauntAt = startedAt + randomBetween(intervalMin, intervalMax);
        this.state = {
            id: 'H-' + Math.random().toString(16).slice(2, 8),
            spiritId: spirit.id,
            name: spirit.name || 'Ghost',
            startedAt,
            endsAt,
            intensity,
            nextHauntAt,
            lastMessageAt: null,
            tags: spirit.tags || [],
            banished: false,
            haunting: config,
        };
        this.save();
        this.scheduleTick(true);
        if (this.terminal) {
            const motd = this.pickTemplate('motd', spirit) || this.pickTemplate('events', spirit) || this.pickTemplate('default', spirit);
            if (motd) {
                this.terminal.hauntingActivated(this.state, this.renderLine(motd));
            }
        }
    };

    HauntingManager.prototype.banish = function () {
        if (!this.state) {
            return { success: false, reason: 'none' };
        }
        if (this.banishCooldownUntil && this.banishCooldownUntil > now()) {
            return { success: false, reason: 'cooldown', retryIn: this.banishCooldownUntil - now() };
        }
        const intensity = this.state.intensity || 0.35;
        const tokens = this.whispers.map((entry) => entry.word);
        const matching = tokens.filter((word) => ['blood', 'ash', 'silence'].includes(word)).length;
        const patienceBonus = clamp(tokens.length * 0.04, 0, 0.3);
        const baseChance = 0.35 + (matching * 0.12) + patienceBonus + clamp((1 - intensity) * 0.35, 0, 0.45);
        const successChance = clamp(baseChance, 0.12, 0.95);
        const succeeded = Math.random() <= successChance;
        if (succeeded) {
            this.state.banished = true;
            this.save();
            this.clear('banished');
            return { success: true, chance: successChance };
        }
        this.state.intensity = clamp(intensity + 0.08, 0.05, 1);
        this.state.nextHauntAt = now() + randomBetween(25000, 65000);
        this.save();
        this.scheduleTick(true);
        this.banishCooldownUntil = now() + 120000;
        return { success: false, chance: successChance, reason: 'resisted' };
    };

    HauntingManager.prototype.calm = function () {
        if (!this.state) {
            return null;
        }
        this.state.intensity = clamp((this.state.intensity || 0.2) - 0.05, 0.03, 1);
        this.save();
        return this.state.intensity;
    };

    HauntingManager.prototype.stop = function (reason) {
        if (!this.state) {
            return;
        }
        this.clear(reason || 'ended');
    };

    HauntingManager.prototype.scheduleTick = function (reset) {
        if (!this.state) {
            return;
        }
        if (this.tickTimer) {
            window.clearTimeout(this.tickTimer);
            this.tickTimer = null;
        }
        const gapMin = (this.defaults.minGapSeconds || 18) * 1000;
        const target = Math.max(this.state.nextHauntAt || (now() + gapMin), now() + gapMin);
        const delay = reset ? Math.max(500, target - now()) : Math.max(1000, target - now());
        this.tickTimer = window.setTimeout(() => this.tick(), delay);
    };

    HauntingManager.prototype.tick = function () {
        if (!this.state) {
            return;
        }
        const current = now();
        if (this.state.endsAt && current >= this.state.endsAt) {
            this.stop('ended');
            return;
        }
        if (current >= (this.state.nextHauntAt || 0)) {
            this.emitHauntLine();
        }
        this.scheduleTick(false);
    };

    HauntingManager.prototype.renderLine = function (template) {
        const spirit = resolveSpirit(this.state ? this.state.spiritId : null);
        const words = {
            omens: (spirit.words && spirit.words.omens) || this.baseWords.omens || [],
            nouns: (spirit.words && spirit.words.nouns) || this.baseWords.nouns || [],
            glitches: (spirit.words && spirit.words.glitches) || this.baseWords.glitches || [],
        };
        const tokens = {
            user: this.username,
            time: new Date().toLocaleTimeString(),
            omen: pick(words.omens) || 'Asche',
            noun: pick(words.nouns) || 'Echo',
            glitch: pick(words.glitches) || 'carrier lost',
        };
        return fillTemplate(template, tokens);
    };

    HauntingManager.prototype.pickTemplate = function (key, spirit) {
        const s = spirit || resolveSpirit(this.state ? this.state.spiritId : null);
        const templates = (s.templates && s.templates[key]) || (this.baseTemplates && this.baseTemplates[key]) || null;
        if (templates) {
            return pick(templates);
        }
        if (this.baseTemplates && this.baseTemplates.default) {
            return pick(this.baseTemplates.default);
        }
        return null;
    };

    HauntingManager.prototype.emitHauntLine = function () {
        if (!this.state) {
            return;
        }
        const spirit = resolveSpirit(this.state.spiritId);
        const template = this.pickTemplate('haunt', spirit) || this.pickTemplate('events', spirit) || this.pickTemplate('default', spirit);
        if (template && this.terminal) {
            this.terminal.printLine(this.renderLine(template), 'haunt');
        }
        const config = spirit.haunting || {};
        const intervalMin = (config.intervalSecondsMin ?? this.defaults.intervalSecondsMin ?? 30) * 1000;
        const intervalMax = (config.intervalSecondsMax ?? this.defaults.intervalSecondsMax ?? 180) * 1000;
        this.state.lastMessageAt = now();
        this.state.nextHauntAt = now() + randomBetween(intervalMin, intervalMax) * (1.2 - clamp(this.state.intensity, 0, 1));
        this.save();
    };

    HauntingManager.prototype.status = function () {
        if (!this.state) {
            const cooldownRaw = window.localStorage.getItem(COOLDOWN_KEY);
            const cooldownUntil = cooldownRaw ? Number(cooldownRaw) : 0;
            const remaining = cooldownUntil > now() ? renderDuration(cooldownUntil - now()) : null;
            return {
                active: false,
                cooldown: remaining,
                whispers: this.whispers,
            };
        }
        const banishCooldown = this.banishCooldownUntil > now() ? renderDuration(this.banishCooldownUntil - now()) : null;
        return {
            active: true,
            spirit: resolveSpirit(this.state.spiritId),
            intensity: this.state.intensity,
            endsIn: renderDuration(this.state.endsAt - now()),
            nextIn: renderDuration((this.state.nextHauntAt || now()) - now()),
            startedAt: this.state.startedAt,
            tags: this.state.tags || [],
            banished: !!this.state.banished,
            whispers: this.whispers,
            banishCooldown,
        };
    };

    window.DarknetHauntingManager = HauntingManager;
})(window, window.document);
