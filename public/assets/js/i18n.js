(function (window) {
    'use strict';

    const flatten = (input, prefix = '') => {
        const result = {};
        Object.keys(input || {}).forEach((key) => {
            const value = input[key];
            const path = prefix ? `${prefix}.${key}` : key;
            if (value && typeof value === 'object' && !Array.isArray(value)) {
                Object.assign(result, flatten(value, path));
            } else {
                result[path] = value;
            }
        });
        return result;
    };

    const pluralKey = (locale, count) => {
        const normalized = (locale || 'en').slice(0, 2).toLowerCase();
        switch (normalized) {
            case 'de':
            case 'en':
                return count === 1 ? 'one' : 'other';
            default:
                return count === 1 ? 'one' : 'other';
        }
    };

    const translations = flatten(window.APP_TRANSLATIONS || {});
    const locale = window.APP_LOCALE || 'de';

    const interpolate = (template, params) => {
        if (!params) {
            return template;
        }
        return Object.keys(params).reduce((carry, key) => {
            return carry.replace(new RegExp(`{${key}}`, 'g'), String(params[key]));
        }, template);
    };

    const lookup = (key) => {
        if (Object.prototype.hasOwnProperty.call(translations, key)) {
            return translations[key];
        }
        console.warn('[i18n] Missing translation key:', key);
        return `[[${key}]]`;
    };

    const T = (key, params) => {
        const value = lookup(key);
        return interpolate(value, params);
    };

    const TN = (key, count, params) => {
        const base = window.APP_TRANSLATIONS || {};
        const segments = key.split('.');
        let context = base;
        for (let i = 0; i < segments.length; i += 1) {
            context = context[segments[i]];
            if (!context) {
                console.warn('[i18n] Missing plural context for key:', key);
                return `[[${key}]]`;
            }
        }
        const form = pluralKey(locale, count);
        const value = context[form] || context.other;
        if (!value) {
            console.warn('[i18n] Missing plural form for key:', key, form);
            return `[[${key}]]`;
        }
        return interpolate(value, { ...params, count });
    };

    window.I18n = {
        t: T,
        tn: TN,
        locale,
        locales: window.APP_LOCALES || [locale]
    };
})(window);
