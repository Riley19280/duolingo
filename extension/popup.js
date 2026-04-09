// Duolingo Scraper - Popup Script

const dot         = document.getElementById('dot');
const statusLabel = document.getElementById('statusLabel');
const countBadge  = document.getElementById('countBadge');
const toggleBtn   = document.getElementById('toggleBtn');
const exportBtn   = document.getElementById('exportBtn');
const clearBtn    = document.getElementById('clearBtn');

let currentCapturing = false;

function updateUI({ isCapturing, count }) {
  currentCapturing = isCapturing;

  // Status dot
  dot.classList.toggle('bg-gray-300',  !isCapturing);
  dot.classList.toggle('bg-[#58cc02]',  isCapturing);

  statusLabel.textContent = isCapturing ? 'Capturing…' : 'Idle';

  // Toggle button color: green → red
  toggleBtn.textContent = isCapturing ? 'Stop Capturing' : 'Start Capturing';
  toggleBtn.classList.toggle('bg-[#58cc02]',             !isCapturing);
  toggleBtn.classList.toggle('shadow-[0_4px_0_#46a302]', !isCapturing);
  toggleBtn.classList.toggle('bg-[#ff4b4b]',              isCapturing);
  toggleBtn.classList.toggle('shadow-[0_4px_0_#cc2e2e]',  isCapturing);

  // Count badge
  const label = `${count} request${count !== 1 ? 's' : ''}`;
  countBadge.textContent = label;
  countBadge.classList.toggle('bg-gray-200',   count === 0);
  countBadge.classList.toggle('text-gray-500', count === 0);
  countBadge.classList.toggle('bg-green-100',  count > 0);
  countBadge.classList.toggle('text-green-700', count > 0);

  exportBtn.disabled = count === 0;
}

// Initialize
chrome.runtime.sendMessage({ type: 'GET_STATE' }, (res) => {
  if (res) updateUI(res);
});

// Toggle capture
toggleBtn.addEventListener('click', () => {
  chrome.runtime.sendMessage({ type: 'TOGGLE_CAPTURE' }, (res) => {
    if (res) updateUI(res);
  });
});

// Export to JSON file
exportBtn.addEventListener('click', () => {
  chrome.runtime.sendMessage({ type: 'GET_REQUESTS' }, ({ requests }) => {
    if (!requests || requests.length === 0) return;

    const ts       = new Date().toISOString().slice(0, 19).replace(/[:.]/g, '-');
    const filename = `duolingo-requests-${ts}.json`;
    const blob     = new Blob([JSON.stringify(requests, null, 2)], { type: 'application/json' });
    const url      = URL.createObjectURL(blob);

    const a    = document.createElement('a');
    a.href     = url;
    a.download = filename;
    a.click();
    URL.revokeObjectURL(url);
  });
});

// Clear all captured requests
clearBtn.addEventListener('click', () => {
  chrome.runtime.sendMessage({ type: 'CLEAR_REQUESTS' }, () => {
    updateUI({ isCapturing: currentCapturing, count: 0 });
  });
});

// Poll for live count updates while popup is open
setInterval(() => {
  chrome.runtime.sendMessage({ type: 'GET_STATE' }, (res) => {
    if (res) updateUI(res);
  });
}, 1500);
