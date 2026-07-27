'use strict';

/**
 * PEP落とし物管理システム - Found Items JS
 * Handles image upload zone, AI analysis, status updates,
 * filter form behavior, and delete actions on found-items pages.
 */

/* =============================================================================
   Helpers (local references to globals from app.js)
   ============================================================================= */
const _getCsrfToken = () => (window.AppUtils ? window.AppUtils.getCsrfToken() : '');
const _fetchJson    = (...args) => window.AppUtils.fetchJson(...args);
const _postJson     = (...args) => window.AppUtils.postJson(...args);
const _patchJson    = (...args) => window.AppUtils.patchJson(...args);
const _deleteJson   = (...args) => window.AppUtils.deleteJson(...args);
const _showToast    = (...args) => window.AppUtils.showToast(...args);
const _confirmAction = (...args) => window.AppUtils.confirmAction(...args);
const _debounce     = (...args) => window.AppUtils.debounce(...args);
const _escapeHtml   = (...args) => window.AppUtils.escapeHtml(...args);

/* =============================================================================
   Image Upload Zone
   ============================================================================= */

const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024; // 10MB
const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

/**
 * Validates a File object for MIME type and size constraints.
 *
 * @param {File} file - The file to validate.
 * @returns {{valid: boolean, error: string|null}}
 */
function validateImageFile(file) {
  if (!ALLOWED_MIME_TYPES.includes(file.type)) {
    return {
      valid: false,
      error: `ファイル形式が無効です。JPEG、PNG、GIF、WebPのみ対応しています。(受信: ${file.type || '不明'})`,
    };
  }
  if (file.size > MAX_FILE_SIZE_BYTES) {
    const sizeMb = (file.size / (1024 * 1024)).toFixed(1);
    return {
      valid: false,
      error: `ファイルサイズが大きすぎます（${sizeMb}MB）。最大10MBまでアップロードできます。`,
    };
  }
  return { valid: true, error: null };
}

/**
 * Shows a local preview of the selected image inside the upload zone.
 *
 * @param {File} file - The image file to preview.
 * @param {HTMLElement} zone - The upload zone element.
 */
function showImagePreview(file, zone) {
  const reader = new FileReader();
  reader.onload = (e) => {
    const existing = zone.querySelector('.upload-preview');
    if (existing) existing.remove();

    const preview = document.createElement('div');
    preview.className = 'upload-preview';
    preview.innerHTML = `
      <img src="${_escapeHtml(e.target.result)}" alt="プレビュー">
      <button type="button" class="upload-preview-remove" aria-label="画像を削除">&times;</button>
    `;

    preview.querySelector('.upload-preview-remove').addEventListener('click', (ev) => {
      ev.stopPropagation();
      clearImageUpload(zone);
    });

    zone.classList.add('has-image');
    zone.querySelector('.upload-zone-content')?.classList.add('hidden');
    zone.appendChild(preview);
  };
  reader.readAsDataURL(file);
}

/**
 * Clears the current image from the upload zone.
 *
 * @param {HTMLElement} zone - The upload zone element.
 */
function clearImageUpload(zone) {
  zone.querySelector('.upload-preview')?.remove();
  zone.classList.remove('has-image');
  zone.querySelector('.upload-zone-content')?.classList.remove('hidden');

  // Clear the hidden URL input and file input
  const urlInput = document.querySelector('#image-url-input');
  if (urlInput) urlInput.value = '';

  const fileInput = zone.querySelector('input[type="file"]');
  if (fileInput) fileInput.value = '';

  // Hide AI button
  const aiBtn = document.querySelector('#ai-analyze-btn');
  if (aiBtn) aiBtn.classList.add('hidden');
}

/**
 * Uploads an image file to the server and stores the returned URL.
 *
 * @param {File} file - The image file to upload.
 * @param {HTMLElement} zone - The upload zone (for spinner display).
 */
async function uploadImage(file, zone) {
  const spinner = zone.querySelector('.upload-spinner');
  if (spinner) spinner.classList.remove('hidden');

  const formData = new FormData();
  formData.append('image', file);

  try {
    const data = await _fetchJson('/upload/image', {
      method: 'POST',
      body: formData,
      // Don't set Content-Type; browser sets multipart boundary automatically
      headers: {
        'X-CSRF-TOKEN': _getCsrfToken(),
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    const urlInput = document.querySelector('#image-url-input');
    if (urlInput && data && data.url) {
      urlInput.value = data.url;
    }

    // Show AI analyze button
    const aiBtn = document.querySelector('#ai-analyze-btn');
    if (aiBtn) {
      aiBtn.classList.remove('hidden');
      aiBtn.dataset.imageUrl = data?.url || '';
    }

    _showToast('画像をアップロードしました。', 'success');
  } catch (err) {
    _showToast(`画像のアップロードに失敗しました: ${err.message}`, 'error');
    clearImageUpload(zone);
  } finally {
    if (spinner) spinner.classList.add('hidden');
  }
}

/**
 * Handles a file being selected/dropped in the upload zone.
 *
 * @param {File} file
 * @param {HTMLElement} zone
 */
function handleFileSelected(file, zone) {
  const { valid, error } = validateImageFile(file);
  if (!valid) {
    _showToast(error, 'error');
    return;
  }
  showImagePreview(file, zone);
  uploadImage(file, zone);
}

/**
 * Initializes the image upload zone (drag & drop, click, file input).
 */
function initImageUploadZone() {
  const zone = document.querySelector('.upload-zone');
  if (!zone) return;

  const fileInput = zone.querySelector('input[type="file"]');
  if (!fileInput) return;

  // Drag & Drop events
  zone.addEventListener('dragover', (e) => {
    e.preventDefault();
    e.stopPropagation();
    zone.classList.add('drag-over');
  });

  zone.addEventListener('dragleave', (e) => {
    e.preventDefault();
    e.stopPropagation();
    zone.classList.remove('drag-over');
  });

  zone.addEventListener('drop', (e) => {
    e.preventDefault();
    e.stopPropagation();
    zone.classList.remove('drag-over');

    const files = e.dataTransfer?.files;
    if (files && files.length > 0) {
      handleFileSelected(files[0], zone);
    }
  });

  // Click to open file picker (but not when clicking on the file input itself)
  zone.addEventListener('click', (e) => {
    if (e.target === fileInput) return;
    if (zone.classList.contains('has-image')) return;
    fileInput.click();
  });

  // File input change
  fileInput.addEventListener('change', (e) => {
    const files = e.target.files;
    if (files && files.length > 0) {
      handleFileSelected(files[0], zone);
    }
  });
}

/* =============================================================================
   AI Image Analysis Button
   ============================================================================= */

/**
 * Initializes the AI analyze button behavior.
 * On click, POSTs the image URL to /analyze-image and fills form fields.
 */
function initAiAnalyzeButton() {
  const aiBtn = document.querySelector('#ai-analyze-btn');
  if (!aiBtn) return;

  aiBtn.addEventListener('click', async () => {
    const imageUrl = aiBtn.dataset.imageUrl
      || document.querySelector('#image-url-input')?.value;

    if (!imageUrl) {
      _showToast('先に画像をアップロードしてください。', 'warning');
      return;
    }

    aiBtn.classList.add('loading');
    aiBtn.disabled = true;

    try {
      const data = await _postJson('/analyze-image', { imageUrl });

      // Fill form fields with AI suggestions
      if (data) {
        const categorySelect = document.querySelector('[name="category"]');
        if (categorySelect && data.category) {
          // Find the matching option
          const option = Array.from(categorySelect.options)
            .find(o => o.value === data.category || o.text === data.category);
          if (option) categorySelect.value = option.value;
        }

        const subCategoryInput = document.querySelector('[name="sub_category"]');
        if (subCategoryInput && data.sub_category) {
          subCategoryInput.value = data.sub_category;
        }

        const featuresTextarea = document.querySelector('[name="features"]');
        if (featuresTextarea && data.features) {
          featuresTextarea.value = data.features;
        }

        _showToast('AI分析完了。フォームに自動入力しました。', 'success');
      }
    } catch (err) {
      _showToast(`AI分析に失敗しました: ${err.message}`, 'error');
    } finally {
      aiBtn.classList.remove('loading');
      aiBtn.disabled = false;
    }
  });
}

/* =============================================================================
   Search / Filter Form
   ============================================================================= */

/**
 * Initializes filter form: auto-submit on dropdown changes (debounced),
 * and date range validation.
 */
function initFilterForm() {
  const filterForm = document.querySelector('#filter-form, .filter-form');
  if (!filterForm) return;

  const autoSubmit = _debounce(() => {
    filterForm.submit();
  }, 300);

  // Auto-submit on select changes
  filterForm.querySelectorAll('select').forEach((select) => {
    select.addEventListener('change', () => autoSubmit());
  });

  // Date range validation
  const dateFrom = filterForm.querySelector('[name="date_from"], [name="found_from"]');
  const dateTo   = filterForm.querySelector('[name="date_to"],   [name="found_to"]');

  if (dateFrom && dateTo) {
    function validateDateRange() {
      if (dateFrom.value && dateTo.value && dateFrom.value > dateTo.value) {
        dateTo.classList.add('error');
        const hint = dateTo.closest('.form-group, .filter-group')?.querySelector('.form-error, .date-error');
        if (hint) hint.textContent = '終了日は開始日以降を指定してください。';
        return false;
      }
      dateTo.classList.remove('error');
      const hint = dateTo.closest('.form-group, .filter-group')?.querySelector('.form-error, .date-error');
      if (hint) hint.textContent = '';
      return true;
    }

    dateFrom.addEventListener('change', validateDateRange);
    dateTo.addEventListener('change', validateDateRange);
  }
}

/* =============================================================================
   Status Update (Show Page)
   ============================================================================= */

/**
 * Initializes the [返還処理] toggle for the inline return form.
 */
function initReturnFormToggle() {
  const toggleBtn = document.querySelector('#toggle-return-form');
  const returnForm = document.querySelector('#return-form');

  if (!toggleBtn || !returnForm) return;

  toggleBtn.addEventListener('click', () => {
    const isVisible = returnForm.classList.contains('visible');
    returnForm.classList.toggle('visible', !isVisible);
    toggleBtn.textContent = isVisible ? '返還処理' : '閉じる';
  });
}

/**
 * Initializes PATCH-based status change buttons (警察提出, 期間満了処分).
 * Expects: [data-status-url] and [data-new-status] attributes.
 */
function initStatusChangeButtons() {
  document.querySelectorAll('[data-new-status]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const url = btn.dataset.statusUrl;
      const newStatus = btn.dataset.newStatus;
      const confirmMsg = btn.dataset.confirmMessage
        || `ステータスを「${newStatus}」に変更しますか？`;

      if (!url) return;

      const confirmed = await _confirmAction(confirmMsg, {
        confirmText: '変更する',
        type: 'warning',
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

/**
 * Initializes delete buttons with [data-delete-url] attribute.
 * These are specific to found-items (redirects to list after deletion).
 */
function initFoundItemDeleteButtons() {
  document.querySelectorAll('[data-delete-url]').forEach((btn) => {
    // Skip buttons that already have data-confirm-delete (handled by app.js)
    if (btn.dataset.confirmDelete !== undefined) return;

    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const url = btn.dataset.deleteUrl;
      if (!url) return;

      const confirmed = await _confirmAction(
        '拾得物を削除しますか？この操作は元に戻せません。',
        { confirmText: '削除する', type: 'danger' }
      );
      if (!confirmed) return;

      btn.classList.add('loading');
      btn.disabled = true;

      try {
        await _deleteJson(url);
        _showToast('拾得物を削除しました。', 'success');
        const redirectTo = btn.dataset.deleteRedirect || '/found-items';
        setTimeout(() => { window.location.href = redirectTo; }, 800);
      } catch (err) {
        _showToast(`削除に失敗しました: ${err.message}`, 'error');
        btn.classList.remove('loading');
        btn.disabled = false;
      }
    });
  });
}

/* =============================================================================
   Create/Edit Form Enhancements
   ============================================================================= */

/**
 * Initializes date validation on the create/edit form.
 */
function initCreateEditForm() {
  const form = document.querySelector('#found-item-form');
  if (!form) return;

  // Prevent submission if date from > to (if both present)
  form.addEventListener('submit', (e) => {
    const foundDatetime = form.querySelector('[name="found_datetime"]');
    if (foundDatetime && !foundDatetime.value) {
      e.preventDefault();
      foundDatetime.classList.add('error');
      _showToast('拾得日時は必須です。', 'error');
      foundDatetime.focus();
    }
  });
}

/* =============================================================================
   Initialize on DOM Ready
   ============================================================================= */

document.addEventListener('DOMContentLoaded', () => {
  initImageUploadZone();
  initAiAnalyzeButton();
  initFilterForm();
  initReturnFormToggle();
  initStatusChangeButtons();
  initFoundItemDeleteButtons();
  initCreateEditForm();
});
