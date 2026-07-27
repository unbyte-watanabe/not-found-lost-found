'use strict';

/**
 * PEP落とし物管理システム - Lost Reports JS
 * Handles owner info reveal/mask, status changes, datetime validation,
 * and filter form behavior on lost-reports pages.
 */

/* =============================================================================
   Helpers (local references to globals from app.js)
   ============================================================================= */
const _patchJson     = (...args) => window.AppUtils.patchJson(...args);
const _showToast     = (...args) => window.AppUtils.showToast(...args);
const _confirmAction = (...args) => window.AppUtils.confirmAction(...args);
const _debounce      = (...args) => window.AppUtils.debounce(...args);

/* =============================================================================
   Masked Owner Info Reveal (Show Page)
   ============================================================================= */

/** Track reveal state for each field */
const _revealState = new Map();

/**
 * Toggles the visibility of a masked owner info field.
 *
 * @param {string} fieldKey - Unique key for the field ('name' or 'contact').
 * @param {HTMLElement} valueEl - The element displaying the masked/revealed value.
 * @param {string} realValue - The actual value to show when revealed.
 * @param {HTMLButtonElement} btn - The toggle button.
 */
function toggleMaskedField(fieldKey, valueEl, realValue, btn) {
  const isRevealed = _revealState.get(fieldKey) === true;

  if (isRevealed) {
    // Mask again
    valueEl.textContent = '●●●●●●';
    valueEl.classList.remove('revealed');
    btn.textContent = '表示';
    btn.setAttribute('aria-label', '情報を表示');
    _revealState.set(fieldKey, false);
  } else {
    // Reveal
    valueEl.textContent = realValue;
    valueEl.classList.add('revealed');
    btn.textContent = '隠す';
    btn.setAttribute('aria-label', '情報を隠す');
    _revealState.set(fieldKey, true);

    // Auto-hide after 30 seconds for security
    setTimeout(() => {
      if (_revealState.get(fieldKey) === true) {
        toggleMaskedField(fieldKey, valueEl, realValue, btn);
      }
    }, 30000);
  }
}

/**
 * Initializes all [data-reveal-field] buttons for masked owner information.
 * Expected markup:
 *   <div class="masked-info">
 *     <span class="masked-value" data-field-key="name">●●●●●●</span>
 *     <button data-reveal-field data-field-key="name" data-real-value="山田太郎">表示</button>
 *   </div>
 */
function initMaskedInfoToggles() {
  document.querySelectorAll('[data-reveal-field]').forEach((btn) => {
    const fieldKey  = btn.dataset.fieldKey;
    const realValue = btn.dataset.realValue || '';
    const valueEl   = document.querySelector(`.masked-value[data-field-key="${fieldKey}"]`);

    if (!valueEl) return;

    // Initialize state
    _revealState.set(fieldKey, false);

    btn.addEventListener('click', () => {
      toggleMaskedField(fieldKey, valueEl, realValue, btn);
    });
  });
}

/* =============================================================================
   Status Change Buttons (Show Page)
   ============================================================================= */

/**
 * Initializes status change buttons on the lost report show page.
 * Expected markup:
 *   <button data-status-url="/lost-reports/{id}/status"
 *           data-new-status="解決済"
 *           data-confirm-message="...">解決済にする</button>
 */
function initLostReportStatusButtons() {
  document.querySelectorAll('[data-lost-report-status]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const url       = btn.dataset.statusUrl;
      const newStatus = btn.dataset.newStatus;
      const confirmMsg = btn.dataset.confirmMessage
        || `ステータスを「${newStatus}」に変更しますか？`;

      if (!url || !newStatus) {
        console.error('[lost-reports.js] data-status-url or data-new-status missing', btn);
        return;
      }

      const type = newStatus === 'キャンセル' ? 'warning' : 'info';
      const confirmed = await _confirmAction(confirmMsg, {
        confirmText: '変更する',
        type,
      });
      if (!confirmed) return;

      btn.classList.add('loading');
      btn.disabled = true;

      try {
        await _patchJson(url, { status: newStatus });
        _showToast(`ステータスを「${newStatus}」に更新しました。`, 'success');
        setTimeout(() => window.location.reload(), 800);
      } catch (err) {
        _showToast(`ステータス更新に失敗しました: ${err.message}`, 'error');
        btn.classList.remove('loading');
        btn.disabled = false;
      }
    });
  });
}

/* =============================================================================
   Create Page: Datetime Validation
   ============================================================================= */

/**
 * Validates that lost_datetime_from <= lost_datetime_to on the create/edit form.
 */
function initDatetimeValidation() {
  const form    = document.querySelector('#lost-report-form');
  if (!form) return;

  const fromInput = form.querySelector('[name="lost_datetime_from"]');
  const toInput   = form.querySelector('[name="lost_datetime_to"]');

  if (!fromInput || !toInput) return;

  /**
   * Checks the from/to datetime range and shows/clears an error.
   * @returns {boolean} True if valid.
   */
  function validateRange() {
    const fromVal = fromInput.value;
    const toVal   = toInput.value;

    if (fromVal && toVal && fromVal > toVal) {
      toInput.classList.add('error');

      let errorEl = toInput.closest('.form-group')?.querySelector('.datetime-range-error');
      if (!errorEl) {
        errorEl = document.createElement('div');
        errorEl.className = 'form-error datetime-range-error';
        toInput.after(errorEl);
      }
      errorEl.textContent = '終了日時は開始日時以降を指定してください。';
      return false;
    }

    toInput.classList.remove('error');
    const errorEl = toInput.closest('.form-group')?.querySelector('.datetime-range-error');
    if (errorEl) errorEl.remove();
    return true;
  }

  fromInput.addEventListener('change', validateRange);
  toInput.addEventListener('change', validateRange);

  form.addEventListener('submit', (e) => {
    if (!validateRange()) {
      e.preventDefault();
      _showToast('日時の範囲が正しくありません。', 'error');
      toInput.focus();
    }
  });
}

/* =============================================================================
   Index Page: Filter Form Auto-Submit
   ============================================================================= */

/**
 * Initializes the filter form on the lost reports index page.
 * Auto-submits on dropdown changes.
 */
function initLostReportFilterForm() {
  const filterForm = document.querySelector('#lost-report-filter-form, .filter-form');
  if (!filterForm) return;

  const autoSubmit = _debounce(() => {
    filterForm.submit();
  }, 300);

  filterForm.querySelectorAll('select').forEach((select) => {
    select.addEventListener('change', () => autoSubmit());
  });

  // Date range inputs: validate before auto-submitting
  const fromDate = filterForm.querySelector('[name="lost_from"]');
  const toDate   = filterForm.querySelector('[name="lost_to"]');

  if (fromDate && toDate) {
    function filterDateCheck() {
      if (fromDate.value && toDate.value && fromDate.value > toDate.value) {
        _showToast('終了日は開始日以降を指定してください。', 'warning');
        toDate.focus();
        return;
      }
      autoSubmit();
    }
    fromDate.addEventListener('change', filterDateCheck);
    toDate.addEventListener('change', filterDateCheck);
  }
}

/* =============================================================================
   Owner Contact Quick Copy
   ============================================================================= */

/**
 * Enables clicking a revealed contact value to copy it to clipboard.
 */
function initContactCopy() {
  document.querySelectorAll('.masked-value').forEach((el) => {
    el.addEventListener('click', async () => {
      if (!el.classList.contains('revealed')) return;
      try {
        await navigator.clipboard.writeText(el.textContent);
        _showToast('クリップボードにコピーしました。', 'info', 2000);
      } catch (_) {
        // Clipboard access denied; fail silently
      }
    });
    el.style.cursor = 'default';
    el.title = '表示後にクリックでコピー';
  });
}

/* =============================================================================
   Initialize on DOM Ready
   ============================================================================= */

document.addEventListener('DOMContentLoaded', () => {
  initMaskedInfoToggles();
  initLostReportStatusButtons();
  initDatetimeValidation();
  initLostReportFilterForm();
  initContactCopy();
});
