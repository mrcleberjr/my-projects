(() => {
    // Configurações
    const CONFIG = {
        endpoints: {
            auth: 'login.php'
        },
        messages: {
            login: {
                success: 'Login realizado com sucesso!',
                error: 'Credenciais inválidas. Por favor, tente novamente.'
            },
            register: {
                success: 'Conta criada com sucesso! Redirecionando...',
                error: 'Não foi possível completar o registro. Verifique os dados.'
            },
            generic: {
                error: 'Ops! Algo deu errado. Tente novamente em instantes.'
            }
        }
    };

    // Gerenciador de tema
    class ThemeManager {
        constructor() {
            this.themeKey = 'theme';
            this.darkClass = 'dark-theme';
            this.initialize();
        }

        initialize() {
            this.applyTheme();
            this.setupEventListeners();
        }

        applyTheme() {
            const theme = localStorage.getItem(this.themeKey);
            document.body.classList.toggle(this.darkClass, theme === 'dark');
            this.updateThemeIcon(theme === 'dark');
        }

        setupEventListeners() {
            const toggleButton = document.getElementById('toggle-theme');
            if (toggleButton) {
                toggleButton.addEventListener('click', () => this.toggleTheme());
            }
        }

        toggleTheme() {
            const isDark = document.body.classList.toggle(this.darkClass);
            localStorage.setItem(this.themeKey, isDark ? 'dark' : 'light');
            this.updateThemeIcon(isDark);
        }

        updateThemeIcon(isDark) {
            const button = document.getElementById('toggle-theme');
            if (button) {
                button.innerHTML = isDark ? '☀️' : '🌙';
                button.setAttribute('aria-label', `Mudar para tema ${isDark ? 'claro' : 'escuro'}`);
            }
        }
    }

    // Gerenciador de formulários
    class FormManager {
        constructor() {
            this.initialize();
        }

        initialize() {
            this.setupForms();
            this.setupInputValidation();
        }

        setupForms() {
            this.setupForm('register-form', 'register');
            this.setupForm('login-form', 'login');
        }

        setupForm(formId, actionType) {
            const form = document.getElementById(formId);
            if (!form) return;

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                await this.handleSubmit(form, actionType);
            });
        }

        setupInputValidation() {
            const inputs = document.querySelectorAll('input[required]');
            inputs.forEach(input => {
                input.addEventListener('input', () => this.validateInput(input));
                input.addEventListener('blur', () => this.validateInput(input));
            });
        }

        validateInput(input) {
            const isValid = input.checkValidity();
            input.classList.toggle('invalid', !isValid);
            
            if (input.type === 'password') {
                this.validatePassword(input);
            }
        }

        validatePassword(input) {
            const password = input.value;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /\d/.test(password),
                special: /[!@#$%^&*]/.test(password)
            };

            const isValid = Object.values(requirements).every(req => req);
            input.setCustomValidity(isValid ? '' : 'A senha deve conter pelo menos 8 caracteres, incluindo maiúsculas, minúsculas, números e caracteres especiais');
        }

        async handleSubmit(form, actionType) {
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            
            try {
                this.setLoadingState(submitButton, true);
                const response = await this.submitForm(form, actionType);
                
                if (response === 'success') {
                    this.showSuccess(CONFIG.messages[actionType].success);
                    setTimeout(() => window.location.href = 'index.html', 1500);
                } else {
                    this.showError(CONFIG.messages[actionType].error);
                }
            } catch (error) {
                console.error('Erro:', error);
                this.showError(CONFIG.messages.generic.error);
            } finally {
                this.setLoadingState(submitButton, false, originalText);
            }
        }

        setLoadingState(button, isLoading, originalText = '') {
            button.disabled = isLoading;
            button.innerHTML = isLoading ? '<span class="spinner"></span>' : originalText;
        }

        async submitForm(form, actionType) {
            const formData = new FormData(form);
            formData.append('action', actionType);

            const response = await fetch(CONFIG.endpoints.auth, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.text();
        }

        showSuccess(message) {
            this.showNotification(message, 'success');
        }

        showError(message) {
            this.showNotification(message, 'error');
        }

        showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
    }

    // Gerenciador de tabs
    class TabManager {
        constructor() {
            this.initialize();
        }

        initialize() {
            window.showTab = this.showTab.bind(this);
        }

        showTab(tabId) {
            const tabs = document.querySelectorAll('.tab-content');
            const buttons = document.querySelectorAll('.tab-button');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            buttons.forEach(btn => btn.classList.remove('active'));

            document.getElementById(tabId)?.classList.add('active');
            document.querySelector(`.tab-button[onclick="showTab('${tabId}')"]`)?.classList.add('active');
        }
    }

    // Inicialização
    document.addEventListener('DOMContentLoaded', () => {
        new ThemeManager();
        new FormManager();
        new TabManager();
    });
})();
