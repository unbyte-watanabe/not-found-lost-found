'use strict';

/**
 * PEP落とし物管理システム - Dashboard JS
 * Handles Chart.js weekly trend chart and stat card count-up animations.
 * Data source: window.dashboardData (set by Blade) or fetched from API.
 */

/* =============================================================================
   Helpers
   ============================================================================= */
const _fetchJson  = (...args) => window.AppUtils.fetchJson(...args);
const _showToast  = (...args) => window.AppUtils.showToast(...args);

/* =============================================================================
   Stat Card Count-Up Animation
   ============================================================================= */

/**
 * Animates a numeric stat card value from 0 to its target value.
 *
 * @param {HTMLElement} el - The element containing the target number.
 * @param {number} targetValue - The final value to count up to.
 * @param {number} [duration=600] - Animation duration in milliseconds.
 */
function animateCountUp(el, targetValue, duration = 600) {
  const startTime = performance.now();
  const startValue = 0;

  function update(currentTime) {
    const elapsed = currentTime - startTime;
    const progress = Math.min(elapsed / duration, 1);

    // Ease-out cubic
    const eased = 1 - Math.pow(1 - progress, 3);
    const current = Math.round(startValue + (targetValue - startValue) * eased);

    el.textContent = current.toLocaleString('ja-JP');

    if (progress < 1) {
      requestAnimationFrame(update);
    } else {
      el.textContent = targetValue.toLocaleString('ja-JP');
    }
  }

  requestAnimationFrame(update);
}

/**
 * Initializes count-up animations for all [data-count-up] elements.
 * Uses Intersection Observer so animation triggers when visible.
 */
function initStatCountUps() {
  const elements = document.querySelectorAll('[data-count-up]');
  if (!elements.length) return;

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const el = entry.target;
        const target = parseInt(el.dataset.countUp || el.textContent.replace(/,/g, ''), 10);
        if (!isNaN(target)) {
          animateCountUp(el, target, 600);
        }
        observer.unobserve(el);
      }
    });
  }, { threshold: 0.2 });

  elements.forEach((el) => observer.observe(el));
}

/* =============================================================================
   Chart.js Weekly Trend Chart
   ============================================================================= */

/** @type {Chart|null} */
let _weeklyChart = null;

/**
 * Renders the weekly trend line chart using Chart.js.
 *
 * @param {string[]} labels - Array of date labels (e.g. ['2025/01/01', ...]).
 * @param {number[]} foundCounts - Daily found item counts.
 * @param {number[]} returnedCounts - Daily returned item counts.
 */
function renderWeeklyChart(labels, foundCounts, returnedCounts) {
  const canvas = document.getElementById('weekly-trend-chart');
  if (!canvas) return;

  // Destroy existing chart if re-rendering
  if (_weeklyChart) {
    _weeklyChart.destroy();
    _weeklyChart = null;
  }

  const ctx = canvas.getContext('2d');

  _weeklyChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: '拾得数',
          data: foundCounts,
          borderColor: '#e07b39',
          backgroundColor: 'rgba(224, 123, 57, 0.1)',
          borderWidth: 2.5,
          pointBackgroundColor: '#e07b39',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          tension: 0.35,
          fill: true,
        },
        {
          label: '返還数',
          data: returnedCounts,
          borderColor: '#2d7a4f',
          backgroundColor: 'rgba(45, 122, 79, 0.08)',
          borderWidth: 2.5,
          pointBackgroundColor: '#2d7a4f',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6,
          tension: 0.35,
          fill: true,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        intersect: false,
        mode: 'index',
      },
      plugins: {
        legend: {
          position: 'top',
          align: 'end',
          labels: {
            usePointStyle: true,
            pointStyleWidth: 10,
            padding: 16,
            font: {
              size: 12,
              family: "-apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans JP', sans-serif",
            },
            color: '#2d2a26',
          },
        },
        tooltip: {
          backgroundColor: '#ffffff',
          titleColor: '#2d2a26',
          bodyColor: '#8a7f75',
          borderColor: '#e8e0d5',
          borderWidth: 1,
          padding: 12,
          cornerRadius: 8,
          boxPadding: 4,
          callbacks: {
            label: (context) => {
              return ` ${context.dataset.label}: ${context.parsed.y}件`;
            },
          },
        },
      },
      scales: {
        x: {
          grid: {
            color: '#e8e0d5',
            drawTicks: false,
          },
          border: {
            dash: [4, 4],
          },
          ticks: {
            color: '#8a7f75',
            font: { size: 11 },
            maxRotation: 0,
            maxTicksLimit: 7,
          },
        },
        y: {
          beginAtZero: true,
          grid: {
            color: '#e8e0d5',
            drawTicks: false,
          },
          border: {
            dash: [4, 4],
            display: false,
          },
          ticks: {
            color: '#8a7f75',
            font: { size: 11 },
            stepSize: 1,
            precision: 0,
            callback: (value) => Math.round(value),
          },
        },
      },
    },
  });
}

/* =============================================================================
   Data Loading
   ============================================================================= */

/**
 * Loads dashboard statistics from the API.
 * @returns {Promise<object>}
 */
async function fetchDashboardStats() {
  return _fetchJson('/api/dashboard/stats');
}

/**
 * Loads weekly trend data from the API.
 * @returns {Promise<object>}
 */
async function fetchWeeklyTrend() {
  return _fetchJson('/api/dashboard/weekly-trend');
}

/**
 * Updates stat card elements with fetched data.
 *
 * @param {object} stats - Stats object with keys matching [data-stat-key] attrs.
 */
function updateStatCards(stats) {
  Object.entries(stats).forEach(([key, value]) => {
    const el = document.querySelector(`[data-stat-key="${key}"] [data-count-up]`);
    if (el && typeof value === 'number') {
      el.dataset.countUp = value;
      animateCountUp(el, value, 600);
    }
  });
}

/**
 * Formats a date string for chart labels.
 * @param {string} dateStr - ISO date string or 'YYYY-MM-DD'.
 * @returns {string} 'MM/DD' format.
 */
function formatChartDate(dateStr) {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return dateStr;
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day   = String(date.getDate()).padStart(2, '0');
  return `${month}/${day}`;
}

/* =============================================================================
   Main Initialization
   ============================================================================= */

/**
 * Initializes the dashboard page.
 * Uses window.dashboardData if available, otherwise fetches from API.
 */
async function initDashboard() {
  // --- Stat Cards ---
  initStatCountUps();

  // --- Weekly Chart ---
  const chartCanvas = document.getElementById('weekly-trend-chart');
  if (!chartCanvas) return;

  // Check if Chart.js is loaded
  if (typeof Chart === 'undefined') {
    console.warn('[dashboard.js] Chart.js not loaded. Skipping chart initialization.');
    return;
  }

  // Check for pre-rendered Blade data first
  if (window.dashboardData && window.dashboardData.weeklyTrend) {
    const trend = window.dashboardData.weeklyTrend;
    const labels   = trend.map(d => formatChartDate(d.date));
    const found    = trend.map(d => d.found    || 0);
    const returned = trend.map(d => d.returned || 0);
    renderWeeklyChart(labels, found, returned);

    // Also update stat cards if data provided
    if (window.dashboardData.stats) {
      updateStatCards(window.dashboardData.stats);
    }
    return;
  }

  // Otherwise fetch from API
  try {
    const [stats, trendData] = await Promise.allSettled([
      fetchDashboardStats(),
      fetchWeeklyTrend(),
    ]);

    if (stats.status === 'fulfilled' && stats.value) {
      updateStatCards(stats.value);
    }

    if (trendData.status === 'fulfilled' && trendData.value) {
      const trend = Array.isArray(trendData.value)
        ? trendData.value
        : trendData.value.data || [];

      const labels   = trend.map(d => formatChartDate(d.date));
      const found    = trend.map(d => d.found    || 0);
      const returned = trend.map(d => d.returned || 0);
      renderWeeklyChart(labels, found, returned);
    }

    if (stats.status === 'rejected') {
      console.warn('[dashboard.js] Failed to fetch stats:', stats.reason);
    }
    if (trendData.status === 'rejected') {
      console.warn('[dashboard.js] Failed to fetch weekly trend:', trendData.reason);
      showChartError(chartCanvas);
    }
  } catch (err) {
    console.error('[dashboard.js] Dashboard initialization error:', err);
    _showToast('ダッシュボードデータの読み込みに失敗しました。', 'error');
  }
}

/**
 * Shows a placeholder message in the chart area on error.
 * @param {HTMLCanvasElement} canvas
 */
function showChartError(canvas) {
  const container = canvas.closest('.chart-container');
  if (!container) return;
  canvas.style.display = 'none';
  const msg = document.createElement('div');
  msg.className = 'page-loading';
  msg.innerHTML = `
    <svg width="32" height="32" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
    </svg>
    <span>データの読み込みに失敗しました</span>
  `;
  container.appendChild(msg);
}

/* =============================================================================
   Initialize on DOM Ready
   ============================================================================= */

document.addEventListener('DOMContentLoaded', () => {
  initDashboard().catch((err) => {
    console.error('[dashboard.js] Unhandled error in initDashboard:', err);
  });
});
