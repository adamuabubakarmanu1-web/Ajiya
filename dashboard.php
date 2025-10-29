<?php
// dashboard.php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: login.html");
  exit;
}
include 'config_db.php';

// load user info from DB to display business name and user id
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT id, fullname, business FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s",$email);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
  // session invalid
  header("Location: login.html");
  exit;
}
$user = $res->fetch_assoc();
$business = htmlspecialchars($user['business'], ENT_QUOTES);
$user_id = intval($user['id']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Dashboard — Baseness Records</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
<!-- jsPDF for PDF export -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<style>
  :root{
    --bg:#f4f7fb; --card:#fff; --accent:#2563eb; --muted:#6b7280; --radius:12px; --shadow: 0 10px 30px rgba(15,23,42,0.06);
  }
  *{box-sizing:border-box;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial;}
  body{margin:0;background:var(--bg);color:#0f172a}
  .topbar{position:sticky;top:0;background:linear-gradient(90deg,#111827,#0b1220);color:#fff;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;z-index:50}
  .brand{display:flex;gap:12px;align-items:center}
  .logo{width:44px;height:44px;border-radius:10px;background:linear-gradient(90deg,#ff7a18,#7b2ff7);display:flex;align-items:center;justify-content:center;color:white;font-weight:800}
  .welcome{font-weight:700;font-size:18px}
  .logout-btn{background:transparent;border:1px solid rgba(255,255,255,0.12);color:white;padding:8px 12px;border-radius:8px;cursor:pointer}
  .container{max-width:1200px;margin:20px auto;padding:0 20px;display:grid;grid-template-columns: 1fr 350px;gap:20px}
  @media (max-width:980px){ .container{grid-template-columns:1fr} .right-col{order:2} }
  .panel{background:var(--card);border-radius:12px;padding:16px;box-shadow:var(--shadow)}
  .controls{display:flex;align-items:center;gap:12px;margin-bottom:12px}
  /* Add Note floating */
  .add-note {
    position:fixed; right:18px; bottom:18px; z-index:2000;
    background:linear-gradient(90deg,#ff7a18,#7b2ff7); color:white; padding:14px 18px;border-radius:999px;box-shadow:0 10px 30px rgba(123,47,247,0.18);cursor:pointer;display:flex;gap:10px;align-items:center;
  }
  .note-panel{position:fixed;right:18px;bottom:80px;width:360px;max-width:92vw;background:var(--card);border-radius:12px;padding:12px;box-shadow:var(--shadow);display:none;z-index:2001}
  .note-panel textarea{width:100%;min-height:90px;border:1px solid #e6edf5;padding:10px;border-radius:8px}
  .small{font-size:13px;color:var(--muted)}
  /* options */
  .options{display:flex;gap:8px;flex-wrap:wrap}
  .option-btn{padding:8px 12px;border-radius:10px;border:1px solid rgba(15,23,42,0.06);cursor:pointer;background:#fff;font-weight:700}
  .option-active{background:linear-gradient(90deg,#ff7a18,#7b2ff7);color:white}
  /* form area */
  .forms {margin-top:12px}
  .inline-form{display:none;padding:12px;border-radius:10px;background:#fff;border:1px solid #eef2f7}
  .inline-form.active{display:block}
  .inline-form label{display:block;margin-bottom:8px;font-weight:600}
  .inline-form input, .inline-form select, .inline-form textarea {width:100%;padding:10px;border-radius:8px;border:1px solid #e6edf5;margin-bottom:10px}
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:8px}
  @media (max-width:600px){ .grid-2{grid-template-columns:1fr} }
  .btn{padding:10px 14px;border-radius:10px;border:0;cursor:pointer}
  .btn.primary{background:linear-gradient(90deg,#ff7a18,#7b2ff7);color:white;font-weight:700}
  .btn.ghost{background:transparent;border:1px solid #e6edf5}
  /* tables */
  table{width:100%;border-collapse:collapse;margin-top:12px}
  th,td{padding:10px;border-bottom:1px solid #f3f4f6;text-align:left}
  .actions-col{display:flex;gap:8px;align-items:center}
  .delete-icon{cursor:pointer;color:#ef4444}
  .stats{margin-top:12px;padding:12px;border-radius:8px;background:linear-gradient(90deg,#ecfeff,#eef2ff);font-weight:700}
  /* footer area lists */
  .list-wrap{max-height:360px;overflow:auto;padding-top:8px}
  .small-muted{font-size:13px;color:var(--muted)}
  /* filter row */
  .filters{display:flex;gap:8px;align-items:center;margin-top:6px}
  .filters input, .filters select{padding:8px;border-radius:8px;border:1px solid #e6edf5}
  /* PDF button */
  .export-btn{background:#0ea5a4;color:white;padding:8px 10px;border-radius:8px;border:0;cursor:pointer}
</style>
</head>
<body>
  <div class="topbar">
    <div class="brand">
      <div class="logo">BR</div>
      <div>
        <div class="welcome">Welcome to my dashboard</div>
        <div class="small" style="opacity:0.9"><?= $business ?></div>
      </div>
    </div>

    <div>
      <button class="logout-btn" id="logoutBtn">Logout</button>
    </div>
  </div>

  <main class="container">
    <!-- left column: forms & tables -->
    <div>
      <div class="panel">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div>
            <div style="font-weight:800;font-size:18px">Records</div>
            <div class="small-muted">Add and view POS, Loan and Stock records</div>
          </div>
          <div class="filters">
            <select id="filterType">
              <option value="all">All Records</option>
              <option value="pos">POS</option>
              <option value="loan">Loan</option>
              <option value="stock">Stock</option>
            </select>
            <input type="date" id="filterDate">
            <button class="export-btn" id="exportPdfBtn">Download PDF</button>
          </div>
        </div>

        <div style="margin-top:12px" class="options" role="tablist" aria-label="record types">
          <button class="option-btn option-active" data-target="posPanel">POS Records</button>
          <button class="option-btn" data-target="loanPanel">Loan Records</button>
          <button class="option-btn" data-target="stockPanel">Stock List</button>
        </div>

        <div class="forms">
          <!-- POS inline form -->
          <div id="posPanel" class="inline-form active">
            <div style="font-weight:700;margin-bottom:8px">New POS / Transfer</div>

            <label>Mode</label>
            <select id="posMode">
              <option value="withdraw">Withdraw</option>
              <option value="transfer">Transfer</option>
            </select>

            <div id="posWithdrawFields">
              <label>Name</label>
              <input id="pos_withdraw_name" placeholder="Name (person)">
              <label>Amount</label>
              <input id="pos_withdraw_amount" type="number" placeholder="Amount">
            </div>

            <div id="posTransferFields" style="display:none">
              <label>Name</label>
              <input id="pos_transfer_name" placeholder="Recipient name">
              <label>Bank Name</label>
              <input id="pos_transfer_bank" placeholder="Bank (e.g., Opay, Zenith...)">
              <label>Amount</label>
              <input id="pos_transfer_amount" type="number" placeholder="Amount">
            </div>

            <label>Note (optional)</label>
            <input id="pos_note" placeholder="Short note">

            <div style="display:flex;gap:8px">
              <button class="btn primary" id="savePosBtn">Save POS</button>
              <button class="btn ghost" id="clearPosBtn">Clear</button>
            </div>
          </div>

          <!-- Loan inline form -->
          <div id="loanPanel" class="inline-form">
            <div style="font-weight:700;margin-bottom:8px">New Loan Record</div>

            <label>Name</label>
            <input id="loan_name" placeholder="Borrower name">

            <label>Items (describe)</label>
            <textarea id="loan_items" placeholder="Items borrowed or description" style="min-height:80px"></textarea>

            <label>Quantity (if applicable)</label>
            <input id="loan_quantity" type="number" min="1" value="1">

            <label>Total Amount</label>
            <input id="loan_total" type="number" placeholder="Total amount">

            <div style="display:flex;gap:8px">
              <button class="btn primary" id="saveLoanBtn">Save Loan</button>
              <button class="btn ghost" id="clearLoanBtn">Clear</button>
            </div>
          </div>

          <!-- Stock inline form -->
          <div id="stockPanel" class="inline-form">
            <div style="font-weight:700;margin-bottom:8px">Add Stock Item</div>

            <label>Item Name</label>
            <input id="stock_item_name" placeholder="Name of item">

            <div style="display:flex;gap:8px">
              <button class="btn primary" id="saveStockBtn">Add Item</button>
              <button class="btn ghost" id="clearStockBtn">Clear</button>
            </div>
          </div>
        </div>

        <!-- tables -->
        <div id="recordsArea" style="margin-top:16px">
          <!-- table will be injected here -->
        </div>

        <!-- stats row -->
        <div id="statsRow" class="stats" style="display:none">
          <div id="dailyTotal">Daily Total: ₦0.00</div>
          <div id="grandTotal" style="margin-top:6px">Grand Total: ₦0.00</div>
        </div>
      </div>
    </div>

    <!-- right column: notes and quick view -->
    <div class="right-col">
      <div class="panel">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div style="font-weight:800">Notes</div>
          <div class="small-muted">Recent</div>
        </div>
        <div id="notesList" class="list-wrap" style="margin-top:10px"></div>
      </div>

      <div style="height:16px"></div>

      <div class="panel">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div style="font-weight:800">Quick Filters</div>
          <div class="small-muted">Choose</div>
        </div>
        <div style="margin-top:12px;display:flex;flex-direction:column;gap:8px">
          <button class="option-btn" id="showPosTable">Show POS Records</button>
          <button class="option-btn" id="showLoanTable">Show Loan Records</button>
          <button class="option-btn" id="showStockTable">Show Stock Items</button>
        </div>
      </div>
    </div>
  </main>

  <!-- Add Note floating & panel -->
  <div class="add-note" id="openNoteBtn"><i style="font-weight:800">＋</i><div style="font-weight:700">Add Note</div></div>
  <div class="note-panel panel" id="notePanel">
    <div style="display:flex;justify-content:space-between;align-items:center">
      <div style="font-weight:800">Add Note</div>
      <button class="btn ghost" id="closeNote">Close</button>
    </div>
    <textarea id="noteText" placeholder="Type a note..."></textarea>
    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:8px">
      <button class="btn primary" id="saveNoteBtn">Save Note</button>
    </div>
  </div>

<script>
/* ---------- config ---------- */
const business = <?= json_encode($business) ?>;
const user_id = <?= json_encode($user_id) ?>;

/* ---------- UI wiring ---------- */
document.querySelectorAll('.option-btn').forEach(btn=>{
  btn.addEventListener('click', ()=> {
    document.querySelectorAll('.option-btn').forEach(x=>x.classList.remove('option-active'));
    btn.classList.add('option-active');
    const target = btn.dataset.target;
    document.querySelectorAll('.inline-form').forEach(f=>f.classList.remove('active'));
    if (target) document.getElementById(target).classList.add('active');
    // automatically show matching table
    if (target === 'posPanel') loadTable('pos');
    if (target === 'loanPanel') loadTable('loan');
    if (target === 'stockPanel') loadTable('stock');
  });
});

/* mode toggle for pos form */
const posMode = document.getElementById('posMode');
posMode.addEventListener('change', ()=> {
  const m = posMode.value;
  document.getElementById('posWithdrawFields').style.display = (m === 'withdraw') ? 'block' : 'none';
  document.getElementById('posTransferFields').style.display = (m === 'transfer') ? 'block' : 'none';
});

/* Save POS */
document.getElementById('savePosBtn').addEventListener('click', async () => {
  const mode = posMode.value;
  let payload = { user_id, business, mode, note: document.getElementById('pos_note').value };
  if (mode === 'withdraw') {
    payload.name = document.getElementById('pos_withdraw_name').value.trim();
    payload.amount = document.getElementById('pos_withdraw_amount').value.trim();
  } else {
    payload.name = document.getElementById('pos_transfer_name').value.trim();
    payload.bank_name = document.getElementById('pos_transfer_bank').value.trim();
    payload.amount = document.getElementById('pos_transfer_amount').value.trim();
  }
  // basic validation
  if (!payload.name || !payload.amount || isNaN(payload.amount) || Number(payload.amount)<=0) {
    alert('Please fill valid name and amount');
    return;
  }
  const res = await fetch('api/add_pos.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
  const json = await res.json();
  if (json.success) {
    alert('POS saved');
    clearPosForm();
    loadTable('pos');
    loadNotes();
  } else alert(json.error || 'Error saving');
});

/* Save Loan */
document.getElementById('saveLoanBtn').addEventListener('click', async () => {
  const payload = {
    user_id, business,
    name: document.getElementById('loan_name').value.trim(),
    items: document.getElementById('loan_items').value.trim(),
    quantity: document.getElementById('loan_quantity').value.trim() || 1,
    total_amount: document.getElementById('loan_total').value.trim()
  };
  if (!payload.name || !payload.items || !payload.total_amount || isNaN(payload.total_amount)) {
    alert('Please fill all loan fields correctly');
    return;
  }
  const res = await fetch('api/add_loan.php', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
  const json = await res.json();
  if (json.success) {
    alert('Loan saved');
    clearLoanForm();
    loadTable('loan');
    loadNotes();
  } else alert(json.error || 'Error saving');
});

/* Save Stock */
document.getElementById('saveStockBtn').addEventListener('click', async ()=>{
  const payload = { user_id, business, item_name: document.getElementById('stock_item_name').value.trim() };
  if (!payload.item_name) { alert('Please enter item name'); return; }
  const res = await fetch('api/add_stock.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
  const json = await res.json();
  if (json.success) {
    alert('Stock saved');
    clearStockForm();
    loadTable('stock');
    loadNotes();
  } else alert(json.error || 'Error saving');
});

/* Clear buttons */
function clearPosForm(){
  document.getElementById('pos_withdraw_name').value='';
  document.getElementById('pos_withdraw_amount').value='';
  document.getElementById('pos_transfer_name').value='';
  document.getElementById('pos_transfer_bank').value='';
  document.getElementById('pos_transfer_amount').value='';
  document.getElementById('pos_note').value='';
}
document.getElementById('clearPosBtn').addEventListener('click', clearPosForm);
document.getElementById('clearLoanBtn').addEventListener('click', ()=>{ document.getElementById('loan_name').value='';document.getElementById('loan_items').value='';document.getElementById('loan_quantity').value=1;document.getElementById('loan_total').value='';});
document.getElementById('clearStockBtn').addEventListener('click', ()=>{ document.getElementById('stock_item_name').value=''; });

/* Add note workflow */
const openNoteBtn = document.getElementById('openNoteBtn');
const notePanel = document.getElementById('notePanel');
const closeNote = document.getElementById('closeNote');
openNoteBtn.addEventListener('click', ()=> { notePanel.style.display='block'; });
closeNote.addEventListener('click', ()=> { notePanel.style.display='none'; });
document.getElementById('saveNoteBtn').addEventListener('click', async ()=>{
  const text = document.getElementById('noteText').value.trim();
  if (!text) { alert('Please enter note'); return; }
  const res = await fetch('api/add_note.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({user_id,business,text})});
  const json = await res.json();
  if (json.success) {
    document.getElementById('noteText').value='';
    notePanel.style.display='none';
    loadNotes();
  } else alert(json.error || 'Error saving note');
});

/* load notes */
async function loadNotes(){
  const res = await fetch('api/get_records.php?type=notes');
  const json = await res.json();
  const wrap = document.getElementById('notesList');
  wrap.innerHTML = '';
  if (json.data && json.data.length){
    json.data.forEach(n=>{
      const div = document.createElement('div');
      div.style.padding='8px';
      div.style.borderBottom='1px solid #f3f4f6';
      div.innerHTML = `<div style="font-weight:700">${new Date(n.created_at).toLocaleString()}</div><div class="small-muted">${n.text}</div>`;
      wrap.appendChild(div);
    });
  } else {
    wrap.innerHTML = '<div class="small-muted">No notes yet</div>';
  }
}

/* load table (pos|loan|stock) with filters */
async function loadTable(type='pos') {
  const date = document.getElementById('filterDate').value;
  const filterType = document.getElementById('filterType').value;
  // if filterType excludes this type and type param is 'all', use filterType
  const res = await fetch(`api/get_records.php?type=${type}&date=${encodeURIComponent(date||'')}`);
  const json = await res.json();
  const area = document.getElementById('recordsArea');
  area.innerHTML = '';
  if (type==='pos') renderPos(json.data || []);
  else if (type==='loan') renderLoan(json.data || []);
  else if (type==='stock') renderStock(json.data || []);
}

/* render POS table */
function renderPos(items){
  const area = document.getElementById('recordsArea');
  if (!items.length) { area.innerHTML = '<div class="small-muted">No POS records</div>'; document.getElementById('statsRow').style.display='none'; return; }
  let html = `<table aria-live="polite"><thead><tr><th>#</th><th>Mode</th><th>Name</th><th>Bank</th><th>Amount</th><th>Date</th><th>Note</th><th>Action</th></tr></thead><tbody>`;
  let dailyTotal = 0; let grandTotal = 0;
  items.forEach((r, i)=>{
    html += `<tr><td>${r.id}</td><td>${r.mode}</td><td>${r.name}</td><td>${r.bank_name||''}</td><td>₦${Number(r.amount).toLocaleString()}</td><td>${r.created_at}</td><td>${escapeHtml(r.note||'')}</td><td class="actions-col"><span class="delete-icon" data-table="pos_records" data-id="${r.id}">Delete</span></td></tr>`;
    grandTotal += Number(r.amount);
    // if it's today
    const d = new Date(r.created_at).toISOString().slice(0,10);
    if (d === new Date().toISOString().slice(0,10)) dailyTotal += Number(r.amount);
  });
  html += `</tbody></table>`;
  area.innerHTML = html;
  document.getElementById('statsRow').style.display='block';
  document.getElementById('dailyTotal').textContent = 'Daily Total: ₦' + Number(dailyTotal).toLocaleString();
  document.getElementById('grandTotal').textContent = 'Grand Total: ₦' + Number(grandTotal).toLocaleString();
  attachDeleteButtons();
}

/* render Loan table */
function renderLoan(items){
  const area = document.getElementById('recordsArea');
  if (!items.length) { area.innerHTML = '<div class="small-muted">No Loan records</div>'; document.getElementById('statsRow').style.display='none'; return; }
  let html = `<table><thead><tr><th>#</th><th>Name</th><th>Items</th><th>Qty</th><th>Total</th><th>Date</th><th>Action</th></tr></thead><tbody>`;
  let grandTotal = 0; let dailyTotal = 0;
  items.forEach((r)=>{
    html += `<tr><td>${r.id}</td><td>${r.name}</td><td>${escapeHtml(r.items)}</td><td>${r.quantity}</td><td>₦${Number(r.total_amount).toLocaleString()}</td><td>${r.created_at}</td><td class="actions-col"><span class="delete-icon" data-table="loan_records" data-id="${r.id}">Delete</span></td></tr>`;
    grandTotal += Number(r.total_amount);
    const d = new Date(r.created_at).toISOString().slice(0,10);
    if (d === new Date().toISOString().slice(0,10)) dailyTotal += Number(r.total_amount);
  });
  html += `</tbody></table>`;
  area.innerHTML = html;
  document.getElementById('statsRow').style.display='block';
  document.getElementById('dailyTotal').textContent = 'Daily Total: ₦' + Number(dailyTotal).toLocaleString();
  document.getElementById('grandTotal').textContent = 'Grand Total: ₦' + Number(grandTotal).toLocaleString();
  attachDeleteButtons();
}

/* render Stock table */
function renderStock(items){
  const area = document.getElementById('recordsArea');
  if (!items.length) { area.innerHTML = '<div class="small-muted">No stock items</div>'; document.getElementById('statsRow').style.display='none'; return; }
  let html = `<table><thead><tr><th>#</th><th>Item</th><th>Date</th><th>Action</th></tr></thead><tbody>`;
  items.forEach(r=>{
    html += `<tr><td>${r.id}</td><td>${r.item_name}</td><td>${r.created_at}</td><td class="actions-col"><span class="delete-icon" data-table="stock_items" data-id="${r.id}">Delete</span></td></tr>`;
  });
  html += `</tbody></table>`;
  area.innerHTML = html;
  document.getElementById('statsRow').style.display='none';
  attachDeleteButtons();
}

/* attach delete icons */
function attachDeleteButtons(){
  document.querySelectorAll('.delete-icon').forEach(el=>{
    el.addEventListener('click', async ()=>{
      if (!confirm('Delete this record?')) return;
      const id = el.dataset.id; const table = el.dataset.table;
      const res = await fetch('api/delete_record.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({table,id})});
      const json = await res.json();
      if (json.success) {
        alert('Deleted');
        // reload tables
        const active = document.querySelector('.option-active')?.dataset.target;
        if (active === 'posPanel') loadTable('pos');
        if (active === 'loanPanel') loadTable('loan');
        if (active === 'stockPanel') loadTable('stock');
        loadNotes();
      } else alert(json.error || 'Delete failed');
    });
  });
}

/* helper */
function escapeHtml(text){ if(!text) return ''; return text.replace(/[&<>"']/g, function(m){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]; }); }

/* export PDF of currently visible table (date filter applied) */
document.getElementById('exportPdfBtn').addEventListener('click', async ()=>{
  // fetch current visible data by reading filterType and filterDate
  const active = document.querySelector('.option-active')?.dataset.target;
  let type = 'pos';
  if (active === 'loanPanel') type='loan';
  if (active === 'stockPanel') type='stock';
  const date = document.getElementById('filterDate').value;
  const res = await fetch(`api/get_records.php?type=${type}&date=${encodeURIComponent(date||'')}`);
  const json = await res.json();
  const data = json.data || [];
  // build a simple PDF using jsPDF
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF();
  doc.setFontSize(14);
  doc.text(`Baseness Records — ${business}`, 14, 18);
  doc.setFontSize(12);
  doc.text(`${type.toUpperCase()} records ${date?('for '+date):' (all)'}`,14,26);
  let y = 36;
  doc.setFontSize(10);
  if (!data.length) {
    doc.text('No records',14,y);
  } else {
    // headers vary by type
    if (type==='pos') {
      doc.text('ID Mode Name Bank Amount Date',14,y); y+=6;
      data.forEach(r=>{
        doc.text(`${r.id} ${r.mode} ${r.name} ${r.bank_name||''} ₦${Number(r.amount).toLocaleString()} ${r.created_at}`,14,y); y+=6; if(y>270){doc.addPage();y=20}
      });
    } else if (type==='loan') {
      doc.text('ID Name Items Total Date',14,y); y+=6;
      data.forEach(r=>{
        doc.text(`${r.id} ${r.name} ${r.items.substring(0,30)} ₦${Number(r.total_amount).toLocaleString()} ${r.created_at}`,14,y); y+=6; if(y>270){doc.addPage();y=20}
      });
    } else {
      doc.text('ID Item Date',14,y); y+=6;
      data.forEach(r=>{
        doc.text(`${r.id} ${r.item_name} ${r.created_at}`,14,y); y+=6; if(y>270){doc.addPage();y=20}
      });
    }
  }
  doc.save(`${type}_records_${date||'all'}.pdf`);
});

/* quick filter buttons */
document.getElementById('showPosTable').addEventListener('click', ()=>{ document.querySelector('[data-target="posPanel"]').click(); });
document.getElementById('showLoanTable').addEventListener('click', ()=>{ document.querySelector('[data-target="loanPanel"]').click(); });
document.getElementById('showStockTable').addEventListener('click', ()=>{ document.querySelector('[data-target="stockPanel"]').click(); });

/* initial load */
loadTable('pos'); loadNotes();

/* logout */
document.getElementById('logoutBtn').addEventListener('click', async ()=>{
  await fetch('logout.php');
  window.location.href='login.html';
});
</script>
</body>
</html>
