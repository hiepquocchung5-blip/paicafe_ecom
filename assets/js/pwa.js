if ('serviceWorker' in navigator) {
  window.addEventListener('load', async () => {
    try {
      const registration = await navigator.serviceWorker.register('/sw.js');
      registration.addEventListener('updatefound', () => {
        const worker = registration.installing;
        if (!worker) return;
        worker.addEventListener('statechange', () => {
          if (worker.state === 'installed' && navigator.serviceWorker.controller && confirm('A new Pai Cafe version is ready. Update now?')) location.reload();
        });
      });
    } catch (error) { console.warn('PWA registration unavailable', error); }
  });
}
