(function () {
  const storageKey = 'fitcircle-theme';

  function applyTheme(theme) {
    if (!document.body) {
      return;
    }
    document.body.classList.toggle('dark-mode', theme === 'dark');
  }

  function getSavedTheme() {
    const saved = localStorage.getItem(storageKey);
    return saved === 'dark' ? 'dark' : 'light';
  }

  function updateToggleUI(isDark) {
    const toggle = document.getElementById('theme-toggle');
    const label = document.getElementById('theme-toggle-label');

    if (toggle) {
      toggle.checked = isDark;
    }
    if (label) {
      label.textContent = isDark ? 'Modo oscuro' : 'Modo claro';
    }
  }

  function initThemeToggle() {
    const toggle = document.getElementById('theme-toggle');
    if (!toggle) {
      return;
    }

    toggle.addEventListener('change', function () {
      const theme = toggle.checked ? 'dark' : 'light';
      localStorage.setItem(storageKey, theme);
      applyTheme(theme);
      updateToggleUI(theme === 'dark');
    });
  }

  const initialTheme = getSavedTheme();
  applyTheme(initialTheme);

  document.addEventListener('DOMContentLoaded', function () {
    const currentIsDark = document.body.classList.contains('dark-mode');
    updateToggleUI(currentIsDark);
    initThemeToggle();
  });
})();
