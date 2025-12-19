(function (window, document) {
    'use strict';

    function Terminal(options) {
        this.root = options.root;
        this.output = this.root.querySelector('[data-terminal-output]');
        this.form = this.root.querySelector('[data-terminal-form]');
        this.input = this.root.querySelector('[data-terminal-input]');
        this.user = options.user || 'guest';
        this.commands = {};
        this.history = [];
        this.historyIndex = -1;
        this.motd = options.motd || [];
        this.bindEvents();
        this.renderMotd();
    }

    Terminal.prototype.bindEvents = function () {
        if (!this.form || !this.input) {
            return;
        }
        this.form.addEventListener('submit', (event) => {
            event.preventDefault();
            const value = this.input.value.trim();
            if (value === '') {
                return;
            }
            this.history.push(value);
            this.historyIndex = this.history.length;
            this.printLine(`> ${value}`, 'prompt');
            this.input.value = '';
            this.execute(value);
        });
        this.input.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (this.historyIndex > 0) {
                    this.historyIndex -= 1;
                    this.input.value = this.history[this.historyIndex] || '';
                }
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (this.historyIndex < this.history.length - 1) {
                    this.historyIndex += 1;
                    this.input.value = this.history[this.historyIndex] || '';
                } else {
                    this.historyIndex = this.history.length;
                    this.input.value = '';
                }
            }
        });
    };

    Terminal.prototype.execute = function (raw) {
        const [command, ...rest] = raw.split(' ');
        const handler = this.commands[command];
        if (handler) {
            handler(rest.join(' '), raw);
        } else {
            this.printLine('Unbekannter Befehl. Tippe "help".', 'muted');
        }
    };

    Terminal.prototype.register = function (name, handler) {
        this.commands[name] = handler;
    };

    Terminal.prototype.printLine = function (text, className) {
        if (!this.output) {
            return;
        }
        const line = document.createElement('div');
        line.className = 'terminal-line' + (className ? ` terminal-line--${className}` : '');
        line.textContent = text;
        this.output.appendChild(line);
        this.output.scrollTop = this.output.scrollHeight;
    };

    Terminal.prototype.renderMotd = function () {
        if (!Array.isArray(this.motd)) {
            return;
        }
        this.motd.forEach((line) => this.printLine(line, 'muted'));
        if (this.motd.length > 0) {
            this.printLine('---', 'muted');
        }
    };

    Terminal.prototype.hauntingActivated = function (state, motd) {
        if (motd) {
            this.printLine(motd, 'haunt');
            this.printLine('---', 'muted');
        }
        if (state) {
            this.printLine(`Status: HAUNTED · ${state.name}`, 'haunt');
        }
    };

    Terminal.prototype.setMotd = function (lines) {
        this.motd = Array.isArray(lines) ? lines : [];
        this.output.innerHTML = '';
        this.renderMotd();
    };

    window.DarknetTerminal = Terminal;
})(window, window.document);
