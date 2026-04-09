// Duolingo Scraper - Content Script (Isolated World)
// Injects the interceptor into the page's main world and bridges
// messages between the page and the background service worker.

(function () {
  // Inject interceptor.js into the main world so it can override fetch/XHR
  const script = document.createElement('script');
  script.src = chrome.runtime.getURL('interceptor.js');
  script.onload = () => script.remove();
  (document.head || document.documentElement).appendChild(script);

  // Bridge messages from the interceptor (main world) to the background worker
  window.addEventListener('message', (event) => {
    if (event.source !== window) return;
    if (
      event.data &&
      (event.data.type === 'FETCH_CAPTURED' || event.data.type === 'XHR_CAPTURED')
    ) {
      chrome.runtime.sendMessage(event.data);
    }
  });
})();
