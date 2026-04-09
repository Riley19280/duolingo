// Duolingo Scraper - Background Service Worker
// Manages capture state, stores requests, handles popup/content script messages.

const MAX_REQUESTS = 2000;

let capturedRequests = [];
let isCapturing = false;

// Restore persisted state on service worker startup
async function loadState() {
  const data = await chrome.storage.local.get(['capturedRequests', 'isCapturing']);
  capturedRequests = data.capturedRequests || [];
  isCapturing = data.isCapturing || false;
}

async function persistState() {
  await chrome.storage.local.set({ capturedRequests, isCapturing });
}

function addRequest(entry) {

  console.log(entry)
  const isApiRequest =
    entry.url.includes('2017-06-30/alphabets/course') && entry.responseBody && entry.responseBody.sections

  if (!isApiRequest) return;


  capturedRequests.push(entry);
  if (capturedRequests.length > MAX_REQUESTS) {
    capturedRequests = capturedRequests.slice(-MAX_REQUESTS);
  }
  persistState();
}

loadState();

// Handle messages from popup and content scripts
chrome.runtime.onMessage.addListener((message, _sender, sendResponse) => {
  switch (message.type) {
    case 'FETCH_CAPTURED':
    case 'XHR_CAPTURED': {
      if (isCapturing) {
        addRequest({
          ...message.data,
          source: message.type === 'FETCH_CAPTURED' ? 'fetch' : 'xhr',
          capturedAt: new Date().toISOString()
        });
      }
      break;
    }

    case 'GET_STATE': {
      sendResponse({ isCapturing, count: capturedRequests.length });
      break;
    }

    case 'TOGGLE_CAPTURE': {
      isCapturing = !isCapturing;
      persistState();
      sendResponse({ isCapturing, count: capturedRequests.length });
      break;
    }

    case 'GET_REQUESTS': {
      sendResponse({ requests: capturedRequests });
      break;
    }

    case 'CLEAR_REQUESTS': {
      capturedRequests = [];
      persistState();
      sendResponse({ count: 0 });
      break;
    }

    case 'IS_CAPTURING': {
      sendResponse({ isCapturing });
      break;
    }
  }

  // Return true to keep the message channel open for async responses
  return true;
});

// webRequest observer — captures network-level metadata for requests the
// fetch/XHR interceptor may miss (workers, prefetch, etc.)
chrome.webRequest.onCompleted.addListener(
  (details) => {
    if (!isCapturing) return;

    addRequest({
      url: details.url,
      method: details.method,
      statusCode: details.statusCode,
      requestType: details.type,
      timestamp: new Date(details.timeStamp).toISOString(),
      source: 'webRequest',
      capturedAt: new Date().toISOString()
    });
  },
  { urls: ['*://*.duolingo.com/*'] }
);
