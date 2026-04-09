// Duolingo Scraper - Page Interceptor (Main World)
// Overrides window.fetch and XMLHttpRequest to capture full request/response
// data, then posts it to the content script via window.postMessage.

(function () {
  function tryParseJSON(text) {
    try {
      return JSON.parse(text);
    } catch {
      return text;
    }
  }

  function headersToObject(headers) {
    const obj = {};
    if (headers && typeof headers.forEach === 'function') {
      headers.forEach((value, key) => { obj[key] = value; });
    } else if (headers && typeof headers === 'object') {
      Object.assign(obj, headers);
    }
    return obj;
  }

  function emit(type, data) {
    window.postMessage({ type, data }, '*');
  }

  // ---- Fetch override ----
  const _fetch = window.fetch;
  window.fetch = async function (...args) {
    const input = args[0];
    const init = args[1] || {};

    const url = input instanceof Request ? input.url : String(input);
    const method = (init.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
    const requestHeaders = headersToObject(init.headers || (input instanceof Request ? input.headers : {}));

    let requestBody = null;
    if (init.body) {
      requestBody = tryParseJSON(
        typeof init.body === 'string' ? init.body : '[non-string body]'
      );
    }

    const timestamp = new Date().toISOString();
    let response;

    try {
      response = await _fetch.apply(this, args);
    } catch (err) {
      emit('FETCH_CAPTURED', {
        url, method, requestHeaders, requestBody,
        error: err.message, timestamp
      });
      throw err;
    }

    // Clone before reading so the caller still gets a usable response
    response.clone().text().then((text) => {
      emit('FETCH_CAPTURED', {
        url,
        method,
        requestHeaders,
        requestBody,
        responseStatus: response.status,
        responseStatusText: response.statusText,
        responseHeaders: headersToObject(response.headers),
        responseBody: tryParseJSON(text),
        timestamp
      });
    }).catch(() => {
      emit('FETCH_CAPTURED', {
        url, method, requestHeaders, requestBody,
        responseStatus: response.status, timestamp
      });
    });

    return response;
  };

  // ---- XMLHttpRequest override ----
  const _XHR = window.XMLHttpRequest;

  class ScraperXHR extends _XHR {
    constructor() {
      super();
      this._scraper = { method: 'GET', url: '', body: null };

      this.addEventListener('load', () => {
        let responseBody = null;
        try {
          responseBody = tryParseJSON(this.responseText);
        } catch { /* binary or non-text */ }

        emit('XHR_CAPTURED', {
          url: this._scraper.url,
          method: this._scraper.method,
          requestBody: this._scraper.body,
          responseStatus: this.status,
          responseStatusText: this.statusText,
          responseBody,
          timestamp: new Date().toISOString()
        });
      });
    }

    open(method, url, ...rest) {
      this._scraper.method = (method || 'GET').toUpperCase();
      this._scraper.url = String(url);
      return super.open(method, url, ...rest);
    }

    send(body) {
      if (body) {
        this._scraper.body =
          typeof body === 'string' ? tryParseJSON(body) : '[non-string body]';
      }
      return super.send(body);
    }
  }

  window.XMLHttpRequest = ScraperXHR;
})();
