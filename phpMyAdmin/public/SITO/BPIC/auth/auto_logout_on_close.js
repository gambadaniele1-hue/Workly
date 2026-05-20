(function () {
  let navigating = false;
  let logoutSent = false;

  function markNavigating() {
    navigating = true;
  }

  function sendAutoLogout() {
    if (navigating || logoutSent) {
      return;
    }
    logoutSent = true;
    navigator.sendBeacon('/SITO/BPIC/auth/auto_logout.php', new URLSearchParams({ reason: 'page_close' }));
  }

  // Segna navigating sui click ai link interni
  document.addEventListener('click', function (event) {
    const link = event.target && event.target.closest ? event.target.closest('a[href]') : null;
    if (!link) return;
    const target = (link.getAttribute('target') || '').toLowerCase();
    if (target === '_blank' || link.hasAttribute('download')) return;
    markNavigating();
  }, true);

  // Segna navigating sui submit di form
  document.addEventListener('submit', function () {
    markNavigating();
  }, true);

  // FIX: rileva F5 / Ctrl+R / Ctrl+Shift+R (refresh da tastiera).
  // Senza questo, beforeunload si attivava anche sul refresh e cancellava il cookie JWT.
  document.addEventListener('keydown', function (e) {
    if (e.key === 'F5' || (e.ctrlKey && (e.key === 'r' || e.key === 'R'))) {
      markNavigating();
    }
  }, true);

  // Usiamo solo beforeunload (rimosso pagehide: era ridondante e causava doppio invio)
  window.addEventListener('beforeunload', sendAutoLogout);
})();
