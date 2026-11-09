(() => {
    const root = document.documentElement;
    const toggle = document.querySelector('[data-theme-toggle]');
    const colorScheme = window.matchMedia('(prefers-color-scheme: dark)');

    const getSavedTheme = () => {
        try {
            return localStorage.getItem('forum-theme');
        } catch {
            return null;
        }
    };

    if (!toggle) {
        return;
    }

    const updateToggle = (theme) => {
        const isDark = theme === 'dark';
        toggle.setAttribute('aria-pressed', String(isDark));
        toggle.setAttribute(
            'aria-label',
            isDark ? 'Activer le mode clair' : 'Activer le mode sombre'
        );
        document.querySelector('meta[name="theme-color"]')?.setAttribute(
            'content',
            isDark ? '#171717' : '#f5f5f7'
        );
    };

    const savedTheme = getSavedTheme();
    const initialTheme = savedTheme || (colorScheme.matches ? 'dark' : 'light');
    root.dataset.theme = initialTheme;
    updateToggle(initialTheme);

    toggle.addEventListener('click', () => {
        const theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
        root.dataset.theme = theme;
        try {
            localStorage.setItem('forum-theme', theme);
        } catch {
            updateToggle(theme);
        }
        updateToggle(theme);
    });

    colorScheme.addEventListener('change', (event) => {
        if (!getSavedTheme()) {
            const theme = event.matches ? 'dark' : 'light';
            root.dataset.theme = theme;
            updateToggle(theme);
        }
    });
})();
