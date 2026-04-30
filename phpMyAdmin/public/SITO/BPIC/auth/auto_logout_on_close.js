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
    const payload = new URLSearchParams({ reason: 'page_close' });
    navigator.sendBeacon('/SITO/BPIC/auth/auto_logout.php', payload);
  }

  document.addEventListener(
    'click',
    function (event) {
      const link = event.target && event.target.closest ? event.target.closest('a[href]') : null;
      if (!link) {
        return;
      }

      const target = (link.getAttribute('target') || '').toLowerCase();
      if (target === '_blank' || link.hasAttribute('download')) {
        return;
      }

      markNavigating();
    },
    true
  );

  document.addEventListener(
    'submit',
    function () {
      markNavigating();
    },
    true
  );

  window.addEventListener('beforeunload', sendAutoLogout);
  window.addEventListener('pagehide', sendAutoLogout);
})();
