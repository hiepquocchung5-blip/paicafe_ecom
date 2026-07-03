window.PaicafeTheme = (function () {
  const storageKey = 'paicafe-theme';
  const legacyKey = 'darkMode';

  function getStoredMode() {
    const stored = localStorage.getItem(storageKey);
    if (stored === 'dark' || stored === 'light') {
      return stored;
    }

    const legacy = localStorage.getItem(legacyKey);
    if (legacy === 'true' || legacy === 'false') {
      return legacy === 'true' ? 'dark' : 'light';
    }

    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function isDark() {
    return document.documentElement.classList.contains('dark');
  }

  function syncToggleIcons() {
    const dark = isDark();
    document.querySelectorAll('.theme-toggle').forEach((button) => {
      button.setAttribute('aria-pressed', dark ? 'true' : 'false');
      button.querySelectorAll('.theme-icon-moon').forEach((icon) => icon.classList.toggle('hidden', dark));
      button.querySelectorAll('.theme-icon-sun').forEach((icon) => icon.classList.toggle('hidden', !dark));
    });
  }

  function set(useDark) {
    document.documentElement.classList.toggle('dark', useDark);
    document.documentElement.classList.toggle('light', !useDark);
    document.documentElement.dataset.theme = useDark ? 'dark' : 'light';
    localStorage.setItem(storageKey, useDark ? 'dark' : 'light');
    localStorage.setItem(legacyKey, useDark ? 'true' : 'false');
    syncToggleIcons();
    window.dispatchEvent(new CustomEvent('paicafe-theme-change', { detail: { dark: useDark } }));
  }

  function init() {
    set(getStoredMode() === 'dark');
    document.querySelectorAll('.theme-toggle').forEach((button) => {
      button.addEventListener('click', () => set(!isDark()));
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  return { init, isDark, set };
})();

function animateCartPop() {
    const cartLink = document.getElementById('mobile-cart-link');
    if (!cartLink) {
      return;
    }
    cartLink.classList.add('cart-pop-animation');
    cartLink.addEventListener('animationend', () => {
      cartLink.classList.remove('cart-pop-animation');
    }, { once: true });
  }
