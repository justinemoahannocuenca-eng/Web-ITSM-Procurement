  /* ---------- Init ---------- */
  if(typeof initRowActionButtons === 'function') initRowActionButtons();
  if(typeof refreshTabCounts === 'function') refreshTabCounts();
  // initDonut() targets the Approvals page's SVG donut (#donut-center-val etc.)
  // — only call it there, otherwise it throws on the missing IDs and aborts
  // this script before animateDashboard() below ever draws the dashboard donut.
  if(typeof initDonut === 'function' && document.getElementById('donut-center')) initDonut();
  if(typeof animateDashboard === 'function') animateDashboard();
