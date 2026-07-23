<!DOCTYPE html>
<html lang="en">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: { colors: { navy: {900:'#0b1e3b',800:'#132b52',700:'#17325f',600:'#1c3a6e'}, muted:'#9bb0d1' } } } }
</script>
<head>
<meta charset="UTF-8">
<title>Overview</title>
<style>
.main-wrapper { opacity: 0; animation: showPage .8s ease forwards; }
@keyframes showPage { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.spin-once { animation: spin .6s ease; }
.dd-menu { animation: ddIn .12s ease forwards; }
@keyframes ddIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
@keyframes spin { from { transform: rotate(0deg);} to { transform: rotate(360deg);} }
</style>
</head>
<body class="bg-navy-900 text-white font-sans min-h-screen">

<div class="main-wrapper max-w-[1400px] mx-auto p-6 space-y-5">

  <div class="bg-navy-800 rounded-xl p-6">
    <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-8">

      <div class="space-y-5">
        <div class="flex items-center justify-between">
          <h1 class="text-xl font-semibold">Overview</h1>
        </div>

        <div>
          <p class="text-muted text-xs tracking-wide">TOTAL EXPENSES THIS MONTH</p>
          <div class="flex items-center gap-2 mt-1">
            <span id="totalExpenses" class="text-3xl font-bold">â‚±0</span>
            <span id="totalChangeBadge" class="text-xs font-semibold rounded-full px-2 py-0.5 bg-emerald-500/20 text-emerald-400">â†‘0%</span>
          </div>
          <p id="totalChangeSub" class="text-muted text-xs mt-1"></p>
        </div>

        <div>
          <div class="flex items-center justify-between mb-1">
            <p class="text-muted text-xs tracking-wide">BUDGET USED</p>
          </div>
          <div class="w-full h-2 bg-navy-700 rounded-full overflow-hidden">
            <div id="budgetBar" class="h-full bg-gradient-to-r from-blue-400 to-blue-500 rounded-full transition-all duration-500" style="width:0%"></div>
          </div>
          <div class="flex items-center justify-between mt-1 text-xs text-muted">
            <span id="budgetLabel">â‚±0 of â‚±0</span>
            <span id="budgetPct">0%</span>
          </div>
        </div>
      </div>

      
      <div>
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
          
          <div class="relative">
            <button onclick="event.stopPropagation(); toggleRangeMenu()" class="dd-toggle flex items-center gap-2 text-xs text-muted hover:text-white bg-navy-800 border border-navy-600 rounded-lg px-3 py-1.5 transition tracking-wide">
              <span id="rangeBtnLabel">
              @if($range == 'week')
                  LAST WEEK
              @elseif($range == 'month')
                  LAST MONTH
              @elseif($range == 'year')
                  LAST YEAR
              @else
                  LAST 6 MONTHS
              @endif
              </span>
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6"/></svg>
            </button>
            <div id="rangeMenu" class="dd-menu hidden absolute left-0 mt-1 bg-navy-800 border border-navy-600 rounded-lg shadow-lg text-xs w-40 overflow-hidden z-20"></div>
          </div>

          <div id="chartLegend" class="flex flex-wrap items-center gap-4 text-sm"></div>
        </div>
        <svg id="trendChart" viewBox="0 0 640 210" class="w-full h-64"></svg>
      </div>

    </div>
  </div>
  <div id="categoryCards" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5"></div>

</div>

<script>

const categories = [
  { key:"manufacturing", label:"Manufacturing", color:"#4ca6ff", capacity:11000, value:5500, prevValue:5360, trend:[3800,4200,4600,4900,5150,5500] },
  {
    key:"procurement",
    label:"Procurement",
    color:"#2ecc71",

    capacity: {{ $overallExpenses }},
    value: {{ $procurementTotal }},
    prevValue: {{ $procurementTotal }},
    trend: @json($totals),
  },
  { key:"inventory",     label:"Inventory",     color:"#ef476f", capacity:5932,  value:2254, prevValue:2170, trend:[2600,2300,2700,2500,2100,2254] },
  { key:"orderFulfillment", label:"Order Fulfillment", color:"#f5a623", capacity:5625, value:1800, prevValue:1850, trend:[1400,1600,1500,1750,1600,1800] },
];
const rangeOptions = ["LAST 6 MONTHS", "LAST WEEK", "LAST MONTH", "LAST YEAR"];
let rangeIndex = 0;
let months = @json($labels);
const budgetCap = 20000;

function getLabelsForRange(label){
  if (label === "LAST WEEK") return ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"];
  if (label === "LAST MONTH") return ["Week 1","Week 2","Week 3","Week 4"];
  if (label === "LAST YEAR") return ["Aug","Sep","Oct","Nov","Dec","Jan","Feb","Mar","Apr","May","Jun","Jul"];
  return ["February","March","April","May","June","July"];
}

function generateTrend(length, endValue){
  const points = [endValue];
  let v = endValue;
  for (let i = 1; i < length; i++) {
    v = Math.max(200, Math.round(v * (1 + ((Math.random() * 0.3) - 0.15))));
    points.unshift(v);
  }
  return points;
}

function fmtPeso(n){ return "â‚±" + Math.round(n).toLocaleString(); }

function renderLegend(){
  document.getElementById("chartLegend").innerHTML = categories.map(c => `
    <span class="flex items-center gap-1.5">
      <span class="w-2.5 h-2.5 rounded-full inline-block" style="background:${c.color}"></span>
      <span class="text-muted">${c.label}</span>
    </span>`).join("");
}

function renderTotals(){
  const total = categories.reduce((s,c) => s + c.value, 0);
  const prevTotal = categories.reduce((s,c) => s + c.prevValue, 0);
  const diff = total - prevTotal;
  const pct = prevTotal ? (diff / prevTotal) * 100 : 0;
  const up = diff >= 0;

  document.getElementById("totalExpenses").textContent = fmtPeso(total);

  const badge = document.getElementById("totalChangeBadge");
  badge.textContent = `${up ? "â†‘" : "â†“"}${Math.abs(pct).toFixed(1)}%`;
  badge.className = `text-xs font-semibold rounded-full px-2 py-0.5 ${up ? "bg-emerald-500/20 text-emerald-400" : "bg-red-500/20 text-red-400"}`;

  document.getElementById("totalChangeSub").textContent =
    `${fmtPeso(Math.abs(diff))} ${up ? "higher" : "lower"} than last month`;

  const usedPct = Math.min(100, (total / budgetCap) * 100);
  document.getElementById("budgetBar").style.width = usedPct + "%";
  document.getElementById("budgetLabel").textContent = `${fmtPeso(total)} of ${fmtPeso(budgetCap)}`;
  document.getElementById("budgetPct").textContent = Math.round(usedPct) + "%";
}

function renderCategoryCards(){
  const wrap = document.getElementById("categoryCards");
  wrap.innerHTML = categories.map(c => {
    const pct = Math.min(100, Math.round((c.value / c.capacity) * 100));
    const dash = (pct / 100) * 282.7;
    const diff = c.value - c.prevValue;
    const diffK = diff / 1000;
    const pctChange = c.prevValue ? (diff / c.prevValue) * 100 : 0;
    const up = diff >= 0;

    return `
    <div class="bg-navy-800 rounded-xl p-5">
      <h3 class="text-base font-semibold">${c.label}</h3>
      <p class="text-muted text-xs mb-4">.........</p>
      <div class="relative w-28 h-28 mx-auto">
        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
          <circle cx="50" cy="50" r="45" fill="none" stroke="${c.color}33" stroke-width="9"/>
          <circle cx="50" cy="50" r="45" fill="none" stroke="${c.color}" stroke-width="9"
                  stroke-linecap="round" stroke-dasharray="${dash} 282.7"/>
        </svg>
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="w-[70px] h-[70px] rounded-full flex items-center justify-center font-bold text-sm"
               style="background:${c.color}">${pct}%</div>
        </div>
      </div>
      <div class="text-center mt-4">
    <p class="text-lg font-semibold">
        â‚±${Number(c.value).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })}
    </p>

    <p class="text-xs text-muted">
        ${pct}% of total expenses
    </p>
</div>
    </div>`;
  }).join("");
}
function renderTrendChart(){
  const svg = document.getElementById("trendChart");
  const rect = svg.getBoundingClientRect();
  const w = Math.round(Math.max(rect.width, 200));
  const h = Math.round(Math.max(rect.height, 120));
  svg.setAttribute("viewBox", `0 0 ${w} ${h}`);
  svg.innerHTML = "";
  const padX = 4, padTop = 15, padBottom = 24;
  const allValues = categories.flatMap(c => c.trend);
  const maxVal = Math.max(...allValues) * 1.1;
  const minVal = Math.min(...allValues) * 0.9;

  const yFor = (v) => padTop + (1 - (v - minVal) / (maxVal - minVal)) * (h - padTop - padBottom);
  const xFor = (i) => padX + (i * (w - padX * 2) / (months.length - 1));

  const base = document.createElementNS("http://www.w3.org/2000/svg","line");
  base.setAttribute("x1", padX); base.setAttribute("x2", w - padX);
  base.setAttribute("y1", h - padBottom); base.setAttribute("y2", h - padBottom);
  base.setAttribute("stroke", "#1c3a6e"); base.setAttribute("stroke-width", "1");
  svg.appendChild(base);

  categories.forEach(c => {
    const points = c.trend.map((v,i) => `${xFor(i)},${yFor(v)}`).join(" ");
    const poly = document.createElementNS("http://www.w3.org/2000/svg","polyline");
    poly.setAttribute("points", points);
    poly.setAttribute("fill", "none");
    poly.setAttribute("stroke", c.color);
    poly.setAttribute("stroke-width", "2.5");
    poly.setAttribute("stroke-linecap", "round");
    poly.setAttribute("stroke-linejoin", "round");
    svg.appendChild(poly);
  });

  months.forEach((m, i) => {
    const x = xFor(i);
    const anchor = i === 0 ? "start" : (i === months.length - 1 ? "end" : "middle");
    const label = document.createElementNS("http://www.w3.org/2000/svg","text");
    label.setAttribute("x", x);
    label.setAttribute("y", h - 6);
    label.setAttribute("fill", "#9bb0d1");
    label.setAttribute("font-size", "11");
    label.setAttribute("text-anchor", anchor);
    label.textContent = m;
    svg.appendChild(label);
  });
}
function renderRangeMenu(){
  document.getElementById("rangeMenu").innerHTML = rangeOptions.map(opt => `
    <button onclick="selectRange('${opt}')" class="w-full text-left px-3 py-2 hover:bg-navy-700 ${opt === rangeOptions[rangeIndex] ? 'text-blue-400' : ''}">${opt}</button>
  `).join("");
}
function toggleRangeMenu(){
  const el = document.getElementById("rangeMenu");
  const wasHidden = el.classList.contains("hidden");
  closeAllMenus();
  if (wasHidden) el.classList.remove("hidden");
}
function closeAllMenus(){
  document.querySelectorAll(".dd-menu").forEach(m => m.classList.add("hidden"));
}
document.addEventListener("click", (e) => {
  if (!e.target.closest(".dd-menu") && !e.target.closest(".dd-toggle")) closeAllMenus();
});

function selectRange(label) {
    closeAllMenus();

    const ranges = {
        "LAST WEEK": "week",
        "LAST MONTH": "month",
        "LAST 6 MONTHS": "6months",
        "LAST YEAR": "year"
    };

    // Update the dropdown label immediately
    document.getElementById("rangeBtnLabel").textContent = label;

    // Redirect to the same page with the selected range
    window.location.href = "{{ route('finance.expenses') }}?range=" + ranges[label];
}
 
function renderAll(){
  renderLegend();
  renderTotals();
  renderCategoryCards();
  renderTrendChart();
}
requestAnimationFrame(renderAll);
renderRangeMenu();
window.addEventListener("resize", () => renderTrendChart());
</script>

</body>
</html>

