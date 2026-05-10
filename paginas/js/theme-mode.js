/**
 * ============================================================
 * MODO OSCURO / CLARO — paginas/js/theme-mode.js
 * ============================================================
 * Gestiona el tema visual (oscuro/claro) de toda la aplicación.
 * La preferencia se guarda en localStorage con la clave 'fitcircle-theme'.
 * Se aplica automáticamente al cargar cada página.
 *
 * Elementos HTML esperados:
 *   #theme-toggle       → Checkbox que actúa como interruptor
 *   #theme-toggle-label → Etiqueta de texto del modo actual
 * ============================================================
 */

// IIFE (función autoinvocada) para evitar contaminar el ámbito global
(function () {
  // Clave de almacenamiento en localStorage
  const storageKey = 'fitcircle-theme';

  /**
   * Aplica la clase 'dark-mode' al body según el tema elegido.
   * Se llama al cargar y al cambiar el interruptor.
   */
  function applyTheme(theme) {
    if (!document.body) {
      return; // Protección si el body aún no existe en el DOM
    }
    document.body.classList.toggle('dark-mode', theme === 'dark');
  }

  /**
   * Lee el tema guardado en localStorage.
   * Si no hay nada guardado devuelve 'light' (valor por defecto).
   */
  function getSavedTheme() {
    const saved = localStorage.getItem(storageKey);
    return saved === 'dark' ? 'dark' : 'light';
  }

  /**
   * Actualiza el estado visual del interruptor y su etiqueta de texto.
   * isDark: true si el modo oscuro está activo.
   */
  function updateToggleUI(isDark) {
    const toggle = document.getElementById('theme-toggle');
    const label  = document.getElementById('theme-toggle-label');

    if (toggle) {
      toggle.checked = isDark; // El checkbox queda marcado en modo oscuro
    }
    if (label) {
      label.textContent = isDark ? 'Modo oscuro' : 'Modo claro';
    }
  }

  /**
   * Vincula el evento 'change' del checkbox para cambiar el tema al hacer clic.
   * Guarda la nueva preferencia en localStorage y la aplica al instante.
   */
  function initThemeToggle() {
    const toggle = document.getElementById('theme-toggle');
    if (!toggle) {
      return; // El interruptor no existe en esta página (algunas no lo tienen)
    }

    toggle.addEventListener('change', function () {
      const theme = toggle.checked ? 'dark' : 'light';
      localStorage.setItem(storageKey, theme); // Persiste la elección
      applyTheme(theme);
      updateToggleUI(theme === 'dark');
    });
  }

  // Aplica el tema guardado ANTES del renderizado para evitar parpadeo
  const initialTheme = getSavedTheme();
  applyTheme(initialTheme);

  // Cuando el DOM esté listo, sincroniza la UI del interruptor
  document.addEventListener('DOMContentLoaded', function () {
    const currentIsDark = document.body.classList.contains('dark-mode');
    updateToggleUI(currentIsDark);
    initThemeToggle();
  });
})();
