'use strict';

/**
 * PEP落とし物管理システム - Matches JS
 * Handles score gauge rendering, client-side filtering/searching,
 * and sorting for the match candidates page.
 */

/* =============================================================================
   Score Gauge Rendering
   ============================================================================= */

/**
 * Returns a CSS class based on the score value.
 *
 * @param {number} score - Score from 0 to 100.
 * @returns {'high'|'medium'|'low'}
 */
function getScoreClass(score) {
  if (score >= 70) return 'high';
  if (score >= 50) return 'medium';
  return 'low';
}

/**
 * Renders a score gauge bar element.
 * Finds all [data-score] elements and fills the corresponding gauge bar.
 */
function renderScoreGauges() {
  document.querySelectorAll('[data-score]').forEach((container) => {
    const score = parseInt(container.dataset.score, 10);
    if (isNaN(score)) return;

    const cls = getScoreClass(score);

    // Fill gauge bar if present
    const fill = container.querySelector('.score-gauge-fill, .score-bar-fill');
    if (fill) {
      fill.style.width = `${Math.min(score, 100)}%`;
      fill.classList.remove('high', 'medium', 'low');
      fill.classList.add(cls);
    }

    // Update score value display if present
    const valueEl = container.querySelector('.score-gauge-value, .score-number');
    if (valueEl) {
      valueEl.classList.remove('high', 'medium', 'low');
      valueEl.classList.add(cls);
      if (valueEl.textContent.trim() === '') {
        valueEl.textContent = `${score}`;
      }
    }

    // Update score circle if present (match-score-circle)
    const circle = container.querySelector('.match-score-circle');
    if (circle) {
      circle.classList.remove('high', 'medium', 'low');
      circle.classList.add(cls);
    }
  });

  // Also handle standalone score bars (not inside [data-score] wrapper)
  document.querySelectorAll('.score-bar-fill[data-score-value]').forEach((fill) => {
    const score = parseInt(fill.dataset.scoreValue, 10);
    if (isNaN(score)) return;
    const cls = getScoreClass(score);
    fill.style.width = `${Math.min(score, 100)}%`;
    fill.classList.remove('high', 'medium', 'low');
    fill.classList.add(cls);
  });
}

/* =============================================================================
   Client-Side Filtering
   ============================================================================= */

/** @type {string} Current search query */
let _currentQuery = '';

/** @type {number} Minimum score filter */
let _minScore = 0;

/** @type {HTMLElement[]} All match card elements */
let _allCards = [];

/**
 * Filters match cards based on the current search query and minimum score.
 */
function applyFilters() {
  const query = _currentQuery.toLowerCase().trim();
  let visibleCount = 0;

  _allCards.forEach((card) => {
    const score     = parseInt(card.dataset.score || '0', 10);
    const text      = (card.dataset.searchText || card.textContent).toLowerCase();
    const scoreOk   = score >= _minScore;
    const queryOk   = !query || text.includes(query);

    const visible = scoreOk && queryOk;
    card.style.display = visible ? '' : 'none';
    if (visible) visibleCount++;
  });

  updateFilterResultCount(visibleCount);
}

/**
 * Updates the "showing X results" count display.
 *
 * @param {number} count - Number of visible items.
 */
function updateFilterResultCount(count) {
  const countEl = document.querySelector('#match-result-count');
  if (countEl) {
    countEl.textContent = `${count}件の候補`;
  }

  const emptyState = document.querySelector('#match-empty-state');
  if (emptyState) {
    emptyState.style.display = count === 0 ? 'flex' : 'none';
  }
}

/**
 * Initializes the client-side search/filter controls.
 */
function initMatchFilter() {
  const searchInput = document.querySelector('#match-search');
  const scoreFilter = document.querySelector('#match-min-score');

  if (!searchInput && !scoreFilter) return;

  // Collect all match cards once
  _allCards = Array.from(document.querySelectorAll('[data-score][data-match-card]'));

  if (searchInput) {
    searchInput.addEventListener('input', () => {
      _currentQuery = searchInput.value;
      applyFilters();
    });
  }

  if (scoreFilter) {
    scoreFilter.addEventListener('change', () => {
      _minScore = parseInt(scoreFilter.value, 10) || 0;
      applyFilters();
    });

    // Update the score label when slider moves
    const scoreLabel = document.querySelector('#match-min-score-label');
    if (scoreLabel) {
      scoreFilter.addEventListener('input', () => {
        scoreLabel.textContent = `${scoreFilter.value}点以上`;
        _minScore = parseInt(scoreFilter.value, 10) || 0;
        applyFilters();
      });
    }
  }
}

/* =============================================================================
   Client-Side Sorting
   ============================================================================= */

/** @type {'desc'|'asc'} Current sort direction */
let _sortDirection = 'desc';

/**
 * Sorts match cards by their score.
 *
 * @param {'asc'|'desc'} direction - Sort direction.
 */
function sortCardsByScore(direction) {
  const container = document.querySelector('#match-cards-container, .match-cards-list');
  if (!container || !_allCards.length) return;

  const sorted = [..._allCards].sort((a, b) => {
    const scoreA = parseInt(a.dataset.score || '0', 10);
    const scoreB = parseInt(b.dataset.score || '0', 10);
    return direction === 'desc' ? scoreB - scoreA : scoreA - scoreB;
  });

  sorted.forEach((card) => container.appendChild(card));
}

/**
 * Initializes the sort-by-score button.
 */
function initSortButton() {
  const sortBtn = document.querySelector('#sort-by-score');
  if (!sortBtn) return;

  sortBtn.addEventListener('click', () => {
    _sortDirection = _sortDirection === 'desc' ? 'asc' : 'desc';
    sortCardsByScore(_sortDirection);

    const label = sortBtn.querySelector('.sort-label');
    if (label) {
      label.textContent = _sortDirection === 'desc' ? 'スコア高い順' : 'スコア低い順';
    }

    const icon = sortBtn.querySelector('.sort-icon');
    if (icon) {
      icon.textContent = _sortDirection === 'desc' ? '↓' : '↑';
    }

    sortBtn.setAttribute('aria-label',
      _sortDirection === 'desc' ? 'スコアの低い順に並べ替え' : 'スコアの高い順に並べ替え'
    );
  });
}

/* =============================================================================
   Score Badge Initialization
   ============================================================================= */

/**
 * Adds score class styling to any raw score number badges.
 * Looks for elements with [data-score-badge] attribute.
 */
function initScoreBadges() {
  document.querySelectorAll('[data-score-badge]').forEach((el) => {
    const score = parseInt(el.dataset.scoreBadge, 10);
    if (isNaN(score)) return;
    const cls = getScoreClass(score);
    el.classList.add(`score-${cls}`);
  });
}

/* =============================================================================
   Animate Gauge Bars on Load
   ============================================================================= */

/**
 * Animates gauge fill bars from 0 to their target width.
 * Uses Intersection Observer for performance.
 */
function animateGaugeBars() {
  const fills = document.querySelectorAll('.score-gauge-fill, .score-bar-fill');
  if (!fills.length) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      const fill = entry.target;
      const targetWidth = fill.style.width;

      // Animate from 0 to target
      fill.style.width = '0%';
      fill.style.transition = 'width 0.6s ease';

      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          fill.style.width = targetWidth;
        });
      });

      observer.unobserve(fill);
    });
  }, { threshold: 0.1 });

  fills.forEach((fill) => observer.observe(fill));
}

/* =============================================================================
   Initialize on DOM Ready
   ============================================================================= */

document.addEventListener('DOMContentLoaded', () => {
  // First render score data into gauge elements
  renderScoreGauges();

  // Animate gauge bars
  animateGaugeBars();

  // Initialize score badges
  initScoreBadges();

  // Initialize client-side filtering
  initMatchFilter();

  // Initialize sort button
  initSortButton();
});
