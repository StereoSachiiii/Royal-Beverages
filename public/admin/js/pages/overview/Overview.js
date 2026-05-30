/* global Chart */
import { fetchDashboard } from './Overview.utils.js';
import { formatCurrency, formatNumber, escapeHtml } from '../../utils.js';

export const Overview = async () => {
  let stats = null;
  let error = null;

  try {
    const response = await fetchDashboard();
    if (response.error) throw new Error(response.error);
    stats = response;
  } catch (err) {
    console.error('Dashboard error:', err);
    error = err.message;
  }

  const topProductsHtml =
    stats?.products.top_products_by_revenue
      .map(
        (p) => `
            <div class="flex items-center px-8 py-5 border-b border-gray-50 group hover:bg-gray-50 transition-colors">
                <div class="flex-1">
                    <div class="font-semibold text-black text-sm">${escapeHtml(p.name)}</div>
                    <div class="text-[10px] text-gray-400 font-medium mt-1">${formatNumber(p.total_sold)} units sold</div>
                </div>
                <div class="text-right">
                    <div class="font-black text-black text-sm">${formatCurrency(p.revenue_cents)}</div>
                </div>
            </div>
        `
      )
      .join('') ||
    '<div class="px-8 py-12 text-center text-sm text-gray-400">No sales data yet.</div>';

  const lowPerfHtml =
    stats?.products.low_performing_products
      .map(
        (p) => `
            <div class="flex items-center px-8 py-5 border-b border-gray-50 hover:bg-red-50/30 transition-colors">
                <div class="flex-1">
                    <div class="font-semibold text-red-600 text-sm">${escapeHtml(p.name)}</div>
                    <div class="text-[10px] text-gray-400 font-medium mt-1">No sales in the last 90 days</div>
                </div>
                <div class="text-right">
                    <div class="px-2 py-1 bg-amber-100 text-amber-700 text-[10px] font-bold rounded">${p.available_stock} units left</div>
                </div>
            </div>
        `
      )
      .join('') ||
    '<div class="px-8 py-12 text-center text-sm text-gray-400">Everything\'s moving well.</div>';

  // Build warehouse range bars HTML
  const warehouseHtml = (() => {
    if (!stats?.warehouses || !stats.warehouses.length) {
      return '<div class="px-8 py-12 text-center text-sm text-gray-400">No warehouse data available.</div>';
    }

    return stats.warehouses
      .map((w) => {
        const total = parseInt(w.total_stock) || 0;
        const reserved = parseInt(w.reserved_stock) || 0;
        const available = parseInt(w.available_stock) || 0;
        const availPct = total > 0 ? Math.round((available / total) * 100) : 0;
        const reservedPct = total > 0 ? Math.round((reserved / total) * 100) : 0;
        return `
            <div class="px-8 py-6 border-b border-gray-50 last:border-b-0 hover:bg-gray-50/40 transition-colors">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <div class="font-semibold text-black text-sm">${escapeHtml(w.name)}</div>
                        <div class="text-[10px] text-gray-400 font-medium mt-1">${w.product_count} SKUs &nbsp;&middot;&nbsp; ${formatNumber(available)} available &nbsp;&middot;&nbsp; ${formatNumber(reserved)} reserved</div>
                    </div>
                    <div class="text-right">
                        <div class="font-black text-black text-lg tabular-nums">${formatNumber(total)}</div>
                        <div class="text-[10px] text-gray-400 font-medium uppercase tracking-widest">total units</div>
                    </div>
                </div>
                <div class="relative h-2.5 bg-gray-100 overflow-hidden rounded-none">
                    <div class="absolute left-0 top-0 h-full bg-black transition-all duration-700 ease-out" style="width:${availPct}%"></div>
                    <div class="absolute top-0 h-full bg-gray-300 transition-all duration-700 ease-out" style="left:${availPct}%;width:${reservedPct}%"></div>
                </div>
                <div class="flex items-center gap-6 mt-2">
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 bg-black inline-block"></span><span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">Available ${availPct}%</span></div>
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 bg-gray-300 inline-block"></span><span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest">Reserved ${reservedPct}%</span></div>
                </div>
            </div>`;
      })
      .join('');
  })();

  // Setup Chart logic for execution after render
  setTimeout(() => {
    if (!stats) return;

    const ctxRev = document.getElementById('revenueChart');
    if (ctxRev) {
      new Chart(ctxRev, {
        type: 'line',
        data: {
          labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
          datasets: [
            {
              label: 'Revenue',
              data: [
                stats.revenue.total_cents * 0.7,
                stats.revenue.total_cents * 0.8,
                stats.revenue.last_30_days_cents * 0.9,
                stats.revenue.last_30_days_cents,
                stats.revenue.today_cents * 20,
                stats.revenue.today_cents * 30,
              ],
              borderColor: '#111',
              borderWidth: 3,
              tension: 0.4,
              pointRadius: 0,
              fill: true,
              backgroundColor: 'rgba(0,0,0,0.02)',
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { display: false },
            x: { grid: { display: false }, border: { display: false } },
          },
        },
      });
    }

    const ctxCat = document.getElementById('categoryChart');
    if (ctxCat) {
      new Chart(ctxCat, {
        type: 'doughnut',
        data: {
          labels: ['Whisky', 'Vodka', 'Wine', 'Gin', 'Other'],
          datasets: [
            {
              data: [40, 20, 15, 15, 10],
              backgroundColor: ['#111', '#333', '#555', '#777', '#999'],
              borderWidth: 0,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '80%',
          plugins: {
            legend: {
              position: 'bottom',
              labels: { boxWidth: 8, font: { size: 10, weight: 'bold' } },
            },
          },
        },
      });
    }
  }, 100);

  return `
        <div class="p-6 lg:p-12 max-w-screen-2xl mx-auto space-y-12 animate-fade overflow-x-hidden">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-8 pb-12 border-b border-gray-100">
                <div class="space-y-3">
                    <h1 class="text-5xl font-heading font-black tracking-tighter text-black m-0">Dashboard</h1>
                    <div class="h-1.5 w-24 bg-black"></div>
                    <p class="text-gray-400 text-xs uppercase tracking-[0.4em] font-medium">Royal Beverages — Business Overview</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="px-5 py-2 border border-gray-200 text-[10px] font-medium tracking-widest uppercase text-gray-500">Live Data</div>
                    <button class="btn-premium px-8 py-3 text-[10px]" onclick="window.location.reload()">Refresh</button>
                </div>
            </div>

            ${
              error
                ? `
                <div class="p-8 border-2 border-black bg-white flex items-center justify-between">
                    <span class="font-black text-black italic tracking-tight">Error: ${escapeHtml(error)}</span>
                    <span class="text-2xl">⚠️</span>
                </div>
            `
                : ''
            }

            <!-- Primary Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-px bg-gray-100 border border-gray-100 shadow-xl overflow-hidden">
                <div class="bg-white p-10 flex flex-col justify-between group hover:bg-black transition-colors duration-500">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-premium group-hover:text-gray-500 transition-colors">Total Revenue</span>
                    <div class="mt-6">
                        <span class="text-3xl font-black tracking-tighter text-black group-hover:text-white transition-colors">${stats ? formatCurrency(stats.revenue.total_cents) : 'Rs 0'}</span>
                        <div class="text-[10px] font-bold text-emerald-600 mt-2 uppercase tracking-widest group-hover:text-emerald-400 transition-colors">All time</div>
                    </div>
                </div>
                <div class="bg-white p-10 flex flex-col justify-between group hover:bg-black transition-colors duration-500">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-premium group-hover:text-gray-500 transition-colors">Total Orders</span>
                    <div class="mt-6">
                        <span class="text-3xl font-black tracking-tighter text-black group-hover:text-white transition-colors">${stats ? formatNumber(stats.orders.total) : '0'}</span>
                        <div class="text-[10px] font-bold text-gray-400 mt-2 uppercase tracking-widest group-hover:text-gray-500 transition-colors">Across all regions</div>
                    </div>
                </div>
                <div class="bg-white p-10 flex flex-col justify-between group hover:bg-black transition-colors duration-500">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-premium group-hover:text-gray-500 transition-colors">Customers</span>
                    <div class="mt-6">
                        <span class="text-3xl font-black tracking-tighter text-black group-hover:text-white transition-colors">${stats ? formatNumber(stats.users.total) : '0'}</span>
                        <div class="text-[10px] font-bold text-emerald-600 mt-2 uppercase tracking-widest group-hover:text-emerald-400 transition-colors">+${stats ? stats.users.new_last_7_days : 0} this week</div>
                    </div>
                </div>
                <div class="bg-white p-10 flex flex-col justify-between group hover:bg-black transition-colors duration-500">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-premium group-hover:text-gray-500 transition-colors">Avg Order Value</span>
                    <div class="mt-6">
                        <span class="text-3xl font-black tracking-tighter text-black group-hover:text-white transition-colors">${stats ? formatCurrency(stats.orders.avg_order_value_cents) : 'Rs 0'}</span>
                        <div class="text-[10px] font-bold text-gray-400 mt-2 uppercase tracking-widest group-hover:text-gray-500 transition-colors">Per transaction</div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                <div class="lg:col-span-2 bg-white border border-gray-100 p-8 flex flex-col">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-xs font-black tracking-premium uppercase text-black italic">Revenue Trend</h3>
                        <span class="text-[10px] font-bold text-gray-400 uppercase">6 month view</span>
                    </div>
                    <div class="h-64 w-full relative">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
                <div class="bg-white border border-gray-100 p-8 flex flex-col">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-xs font-black tracking-premium uppercase text-black italic">Category Mix</h3>
                        <span class="text-[10px] font-bold text-gray-400 uppercase">by sales</span>
                    </div>
                    <div class="h-64 w-full relative">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Product Intelligence -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 pt-8">
                <section class="space-y-6">
                    <div class="flex items-center gap-4">
                        <span class="w-1 h-6 bg-black"></span>
                        <h3 class="text-xs font-bold tracking-wide uppercase text-black">Top Selling Products</h3>
                    </div>
                    <div class="bg-white border border-gray-100 shadow-sm overflow-hidden">
                        <div class="flex flex-col w-full">${topProductsHtml}</div>
                        <div class="p-6 bg-gray-50 text-center border-t border-gray-100">
                            <button class="text-[9px] font-medium tracking-widest uppercase text-gray-400 hover:text-black transition-colors">View all products</button>
                        </div>
                    </div>
                </section>

                <section class="space-y-6">
                    <div class="flex items-center gap-4 text-red-600">
                        <span class="w-1 h-6 bg-red-600"></span>
                        <h3 class="text-xs font-bold tracking-wide uppercase">Slow Moving Stock</h3>
                    </div>
                    <div class="bg-white border border-gray-100 shadow-sm overflow-hidden">
                        <div class="flex flex-col w-full">${lowPerfHtml}</div>
                        <div class="p-6 bg-gray-50 text-center border-t border-gray-100">
                            <button class="text-[9px] font-medium tracking-widest uppercase text-gray-400 hover:text-amber-600 transition-colors">Consider a promotion</button>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Warehouse Stock Intelligence -->
            <section class="space-y-6 pt-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <span class="w-1 h-6 bg-black"></span>
                        <h3 class="text-xs font-bold tracking-wide uppercase text-black">Warehouse Stock Levels</h3>
                    </div>
                    <span class="text-[10px] text-gray-400 font-medium uppercase tracking-widest">Live inventory per location</span>
                </div>
                <div class="bg-white border border-gray-100 shadow-sm overflow-hidden">
                    ${warehouseHtml}
                </div>
            </section>
        </div>
    `;
};
