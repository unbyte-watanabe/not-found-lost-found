'use strict';

/**
 * PEP落とし物管理システム - Global App Utilities
 * Provides CSRF helpers, fetch wrappers, toast notifications,
 * sidebar toggle, confirm dialogs, and datetime formatting.
 */

/* =============================================================================
   CSRF Token Helper
   ============================================================================= */

/**
 * Reads the CSRF token from the <meta name="csrf-token"> tag.
 * @returns {string} The CSRF token value, or empty string if not found.
 */
function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

/* =============================================================================
   Fetch Wrappers
   ============================================================================= */

/**
 * Fetch wrapper that automatically adds the CSRF token header and handles JSON.
 * Throws on non-2xx HTTP responses.
 *
 * @param {string} url - The URL to fetch.
 * @param {RequestInit} [options={}] - Standard fetch options.
 * @returns {Promise<any>} Parsed JSON response body.
 * @throws {Error} On HTTP error or network failure.
 */
async function fetchJson(url, options = {}) {
  const headers = {
    'Accept': 'application/json',
    'X-CSRF-TOKEN': getCsrfToken(),
    'X-Requested-With': 'XMLHttpRequest',
    ...(options.headers || {}),
  };

  // If body is a plain object (not FormData), JSON-encode it
  let body = options.body;
  if (body && typeof body === 'object' && !(body instanceof FormData)) {
    body = JSON.stringify(body);
    headers['Content-Type'] = 'application/json';
  }

  const response = await fetch(url, {
    ...options,
    headers,
    body,
  });

  if (!response.ok) {
    let errorMessage = `HTTP ${response.status}: ${response.statusText}`;
    try {
      const errorData = await response.json();
      if (errorData.message) {
        errorMessage = errorData.message;
      } else if (errorData.error) {
        errorMessage = errorData.error;
      }
    } catch (_) {
      // ignore JSON parse errors for error responses
    }
    const err = new Error(errorMessage);
    err.status = response.status;
    throw err;
  }

  // Handle empty responses (204 No Content, etc.)
  const contentType = response.headers.get('Content-Type') || '';
  if (response.status === 204 || !contentType.includes('application/json')) {
    return null;
  }

  return response.json();
}

/**
 * Convenience wrapper for POST requests with JSON body.
 *
 * @param {string} url - The URL to POST to.
 * @param {object} data - Data to send as JSON body.
 * @returns {Promise<any>} Parsed JSON response body.
 */
async function postJson(url, data) {
  return fetchJson(url, {
    method: 'POST',
    body: data,
  });
}

/**
 * Convenience wrapper for PATCH requests with JSON body.
 *
 * @param {string} url - The URL to PATCH.
 * @param {object} data - Data to send as JSON body.
 * @returns {Promise<any>} Parsed JSON response body.
 */
async function patchJson(url, data) {
  return fetchJson(url, {
    method: 'PATCH',
    body: data,
  });
}

/**
 * Convenience wrapper for DELETE requests.
 *
 * @param {string} url - The URL to DELETE.
 * @returns {Promise<any>} Parsed JSON response body.
 */
async function deleteJson(url) {
  return fetchJson(url, {
    method: 'DELETE',
  });
}

/* =============================================================================
   Toast Notification System
   ============================================================================= */

/** @type {HTMLElement|null} */
let _toastContainer = null;

/**
 * Gets or creates the toast container element.
 * @returns {HTMLElement}
 */
function getToastContainer() {
  if (!_toastContainer) {
    _toastContainer = document.getElementById('toast-container');
    if (!_toastContainer) {
      _toastContainer = document.createElement('div');
      _toastContainer.id = 'toast-container';
      document.body.appendChild(_toastContainer);
    }
  }
  return _toastContainer;
}

/**
 * Icon SVG for each toast type.
 * @param {string} type
 * @returns {string} SVG markup
 */
function _toastIcon(type) {
  const icons = {
    success: `<svg class="toast-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
              </svg>`,
    error:   `<svg class="toast-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>`,
    info:    `<svg class="toast-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>
              </svg>`,
    warning: `<svg class="toast-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
              </svg>`,
  };
  return icons[type] || icons.info;
}

/**
 * Removes a toast element with a slide-out animation.
 * @param {HTMLElement} toast
 */
function _dismissToast(toast) {
  if (toast.dataset.dismissed) return;
  toast.dataset.dismissed = 'true';
  toast.classList.add('hiding');
  toast.addEventListener('animationend', () => toast.remove(), { once: true });
  // Fallback removal in case animation doesn't fire
  setTimeout(() => toast.remove(), 500);
}

/**
 * Displays a toast notification.
 *
 * @param {string} message - The message to display.
 * @param {'success'|'error'|'info'|'warning'} [type='success'] - Toast type.
 * @param {number} [duration=4000] - Auto-dismiss delay in milliseconds.
 */
function showToast(message, type = 'success', duration = 4000) {
  const container = getToastContainer();

  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.setAttribute('role', 'alert');
  toast.setAttribute('aria-live', 'polite');

  toast.innerHTML = `
    ${_toastIcon(type)}
    <div class="toast-body">
      <div class="toast-message">${escapeHtml(message)}</div>
    </div>
    <button class="toast-close" aria-label="閉じる">&times;</button>
    <div class="toast-progress" style="animation-duration: ${duration}ms"></div>
  `;

  // Close on click
  toast.querySelector('.toast-close').addEventListener('click', () => _dismissToast(toast));
  toast.addEventListener('click', () => _dismissToast(toast));

  container.appendChild(toast);

  // Auto-dismiss
  if (duration > 0) {
    setTimeout(() => _dismissToast(toast), duration);
  }
}

/* =============================================================================
   HTML Escape Helper
   ============================================================================= */

/**
 * Escapes HTML special characters to prevent XSS.
 * @param {string} str
 * @returns {string}
 */
function escapeHtml(str) {
  if (typeof str !== 'string') return String(str ?? '');
  return str
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/* =============================================================================
   Sidebar Toggle (Mobile)
   ============================================================================= */

/**
 * Initializes the mobile sidebar toggle behavior.
 * Looks for [data-hamburger] button and .sidebar, .sidebar-overlay elements.
 */
function initSidebarToggle() {
  const hamburger = document.querySelector('[data-hamburger]');
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.sidebar-overlay');

  if (!hamburger || !sidebar) return;

  function openSidebar() {
    sidebar.classList.add('mobile-open');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    hamburger.setAttribute('aria-expanded', 'true');
  }

  function closeSidebar() {
    sidebar.classList.remove('mobile-open');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
    hamburger.setAttribute('aria-expanded', 'false');
  }

  hamburger.addEventListener('click', () => {
    const isOpen = sidebar.classList.contains('mobile-open');
    isOpen ? closeSidebar() : openSidebar();
  });

  if (overlay) {
    overlay.addEventListener('click', closeSidebar);
  }

  // Close on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && sidebar.classList.contains('mobile-open')) {
      closeSidebar();
    }
  });
}

/* =============================================================================
   Confirm Dialog Helper
   ============================================================================= */

/**
 * Shows a native-style confirmation dialog.
 * Returns a Promise that resolves to true if confirmed, false otherwise.
 *
 * @param {string} message - The confirmation message.
 * @param {object} [options]
 * @param {string} [options.confirmText='確認'] - Confirm button label.
 * @param {string} [options.cancelText='キャンセル'] - Cancel button label.
 * @param {'danger'|'warning'|'info'} [options.type='danger'] - Dialog type.
 * @returns {Promise<boolean>}
 */
function confirmAction(message, options = {}) {
  return new Promise((resolve) => {
    const {
      confirmText = '確認',
      cancelText = 'キャンセル',
      type = 'danger',
    } = options;

    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.innerHTML = `
      <div class="modal confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
        <div class="modal-body" style="text-align: center; padding: 32px 24px;">
          <div class="confirm-dialog-icon ${type}" style="margin-bottom: 16px;">
            ${type === 'danger'
              ? `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:26px;height:26px">
                   <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                 </svg>`
              : `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width:26px;height:26px">
                   <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                 </svg>`
            }
          </div>
          <p id="confirm-title" class="confirm-dialog-text">${escapeHtml(message)}</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary btn-md" data-action="cancel">${escapeHtml(cancelText)}</button>
          <button class="btn ${type === 'danger' ? 'btn-danger' : 'btn-primary'} btn-md" data-action="confirm">${escapeHtml(confirmText)}</button>
        </div>
      </div>
    `;

    function cleanup(result) {
      overlay.remove();
      resolve(result);
    }

    overlay.querySelector('[data-action="confirm"]').addEventListener('click', () => cleanup(true));
    overlay.querySelector('[data-action="cancel"]').addEventListener('click', () => cleanup(false));
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) cleanup(false);
    });

    document.addEventListener('keydown', function handler(e) {
      if (e.key === 'Escape') {
        cleanup(false);
        document.removeEventListener('keydown', handler);
      }
    });

    document.body.appendChild(overlay);
    overlay.querySelector('[data-action="confirm"]').focus();
  });
}

/* =============================================================================
   Datetime Formatter
   ============================================================================= */

/**
 * Formats an ISO date string to Japanese locale 'YYYY/MM/DD HH:mm' format.
 *
 * @param {string} isoString - ISO 8601 date string.
 * @returns {string} Formatted date string, or empty string if invalid.
 */
function formatDatetime(isoString) {
  if (!isoString) return '';
  try {
    const date = new Date(isoString);
    if (isNaN(date.getTime())) return '';

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}/${month}/${day} ${hours}:${minutes}`;
  } catch (_) {
    return '';
  }
}

/**
 * Formats a date-only string to 'YYYY/MM/DD'.
 *
 * @param {string} isoString - ISO 8601 date string.
 * @returns {string}
 */
function formatDate(isoString) {
  if (!isoString) return '';
  try {
    const date = new Date(isoString);
    if (isNaN(date.getTime())) return '';
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}/${month}/${day}`;
  } catch (_) {
    return '';
  }
}

/* =============================================================================
   Delete Confirmation Buttons
   ============================================================================= */

/**
 * Initializes all [data-confirm-delete] buttons with a confirmation flow.
 * The element should also have [data-delete-url] for the URL,
 * and optionally [data-confirm-message] for a custom message.
 */
function initDeleteButtons() {
  document.querySelectorAll('[data-confirm-delete]').forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();

      const url = btn.dataset.deleteUrl;
      const message = btn.dataset.confirmMessage || 'このデータを削除しますか？この操作は元に戻せません。';

      if (!url) {
        console.error('[app.js] data-delete-url is missing on delete button', btn);
        return;
      }

      const confirmed = await confirmAction(message, { confirmText: '削除する', type: 'danger' });
      if (!confirmed) return;

      btn.classList.add('loading');
      btn.disabled = true;

      try {
        await deleteJson(url);
        showToast('削除しました。', 'success');

        // If there's a redirect URL specified, navigate there
        const redirectUrl = btn.dataset.deleteRedirect;
        if (redirectUrl) {
          setTimeout(() => { window.location.href = redirectUrl; }, 800);
        } else {
          // Otherwise reload the page
          setTimeout(() => { window.location.reload(); }, 800);
        }
      } catch (err) {
        showToast(`削除に失敗しました: ${err.message}`, 'error');
        btn.classList.remove('loading');
        btn.disabled = false;
      }
    });
  });
}

/* =============================================================================
   Debounce Utility
   ============================================================================= */

/**
 * Creates a debounced version of a function.
 * @param {Function} fn - The function to debounce.
 * @param {number} delay - Delay in milliseconds.
 * @returns {Function} Debounced function.
 */
function debounce(fn, delay) {
  let timer;
  return function (...args) {
    clearTimeout(timer);
    timer = setTimeout(() => fn.apply(this, args), delay);
  };
}

/* =============================================================================
   Active Nav Link Highlighting
   ============================================================================= */

/**
 * Marks sidebar nav links as active based on the current URL path.
 */
function initActiveNav() {
  const currentPath = window.location.pathname;
  document.querySelectorAll('.sidebar-nav-link').forEach((link) => {
    const href = link.getAttribute('href');
    if (!href) return;

    // Exact match or prefix match for sub-pages
    if (currentPath === href || (href !== '/' && currentPath.startsWith(href))) {
      link.classList.add('active');
    }
  });
}

/* =============================================================================
   Flash Message to Toast
   ============================================================================= */

/**
 * Converts server-side flash messages (hidden elements) to toasts.
 * Looks for [data-flash-message] and [data-flash-type] attributes.
 */
function initFlashMessages() {
  const flashEl = document.querySelector('[data-flash-message]');
  if (flashEl) {
    const message = flashEl.dataset.flashMessage;
    const type = flashEl.dataset.flashType || 'success';
    if (message) {
      // Small delay so page renders first
      setTimeout(() => showToast(message, type), 300);
    }
  }

  // Also handle multiple flash messages
  document.querySelectorAll('[data-flash]').forEach((el) => {
    const message = el.textContent.trim();
    const type = el.dataset.flash || 'success';
    if (message) {
      setTimeout(() => showToast(message, type), 300);
    }
  });
}

/* =============================================================================
   DOMContentLoaded Initialization
   ============================================================================= */

document.addEventListener('DOMContentLoaded', () => {
  // Ensure toast container exists
  getToastContainer();

  // Initialize sidebar mobile toggle
  initSidebarToggle();

  // Initialize delete confirmation buttons
  initDeleteButtons();

  // Highlight active nav items
  initActiveNav();

  // Show flash messages as toasts
  initFlashMessages();
});

/* =============================================================================
   Expose globals for use in other scripts
   ============================================================================= */
window.AppUtils = {
  getCsrfToken,
  fetchJson,
  postJson,
  patchJson,
  deleteJson,
  showToast,
  escapeHtml,
  confirmAction,
  formatDatetime,
  formatDate,
  debounce,
};
