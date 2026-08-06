<style>
  :root{
    --ink:#101828; --ink-soft:#5B6478; --bg:#EEF1F8; --surface:#FFFFFF; --surface-alt:#F5F7FC;
    --border:#DFE4F2; --primary:#2952E0; --primary-dark:#122C7A; --primary-light:#E3EAFE;
    --amber:#FF8A1E; --amber-dark:#D9690A; --green:#16A34A; --red:#DC2626; --violet:#8B5CF6;
    --shadow:0 10px 28px -14px rgba(16,22,60,.3); --shadow-lg:0 20px 44px -18px rgba(16,22,60,.35);
    --font:'Vazirmatn','IRANYekanX','IRANSansX','Yekan','Tahoma','Segoe UI',Arial,sans-serif;
  }
  *{box-sizing:border-box;}
  html{overflow-x:hidden;}
  body{margin:0;overflow-x:hidden;max-width:100vw;background:radial-gradient(140% 100% at 100% 0%, #F3F0FF 0%, var(--bg) 45%);color:var(--ink);font-family:var(--font);font-size:16px;line-height:1.75;}
  a{color:inherit;text-decoration:none;}
  .wrap{max-width:1280px;margin:0 auto;padding:0 20px;}
  .num-font{font-variant-numeric:tabular-nums;}
  @keyframes fadeUp{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
  main .card, main .kpi-card{animation:fadeUp .35s ease both;}
  main .kpi-grid .kpi-card:nth-child(1){animation-delay:.02s;}
  main .kpi-grid .kpi-card:nth-child(2){animation-delay:.06s;}
  main .kpi-grid .kpi-card:nth-child(3){animation-delay:.1s;}
  main .kpi-grid .kpi-card:nth-child(4){animation-delay:.14s;}
  main .kpi-grid .kpi-card:nth-child(5){animation-delay:.18s;}

  .toast-wrap{position:fixed;bottom:20px;left:50%;transform:translateX(-50%);z-index:200;display:flex;flex-direction:column;gap:8px;align-items:center;}
  .toast{background:var(--primary-dark);color:#fff;padding:12px 20px;border-radius:12px;font-size:.86rem;font-weight:700;box-shadow:var(--shadow-lg);animation:toastIn .25s ease;}
  .toast.ok{background:var(--green);}
  .toast.err{background:var(--red);}
  @keyframes toastIn{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}

  header.admin-hd{background:linear-gradient(120deg,var(--primary-dark),var(--primary) 70%);color:#fff;padding:14px 0;box-shadow:0 6px 22px -8px rgba(18,44,122,.45);position:sticky;top:0;z-index:50;}
  .admin-hd-row{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;}
  .admin-brand{display:flex;align-items:center;gap:10px;font-weight:900;font-size:1.15rem;}
  .admin-brand .badge{background:var(--amber);color:#1A1200;font-size:.68rem;font-weight:800;padding:3px 9px;border-radius:999px;}
  nav.admin-nav{display:flex;gap:6px;flex-wrap:wrap;}
  nav.admin-nav a{padding:9px 14px;border-radius:10px;font-size:.86rem;font-weight:700;color:#D6DEFB;transition:.15s;}
  nav.admin-nav a:hover{background:rgba(255,255,255,.12);color:#fff;}
  nav.admin-nav a.active{background:var(--amber);color:#1A1200;}
  .logout-link{display:flex;align-items:center;gap:5px;font-size:.82rem;font-weight:700;color:#FFD8B0;}
  .logout-link svg{width:15px;height:15px;}

  main{padding:26px 0 60px;}
  h1.page-title{font-size:1.4rem;font-weight:900;color:var(--primary-dark);margin:0 0 4px;display:flex;align-items:center;gap:10px;}
  h1.page-title svg{width:24px;height:24px;color:var(--amber-dark);}
  p.page-sub{color:var(--ink-soft);font-size:.92rem;margin:0 0 22px;}

  .kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;}
  @media(max-width:900px){.kpi-grid{grid-template-columns:repeat(2,1fr);}}
  @media(max-width:480px){.kpi-grid{grid-template-columns:1fr;}}
  .kpi-card{background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:19px 21px;box-shadow:var(--shadow);transition:.2s transform,.2s box-shadow;}
  .kpi-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-lg);}
  .kpi-card .kpi-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
  .kpi-card .kpi-icon{width:38px;height:38px;border-radius:10px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;}
  .kpi-card .kpi-icon svg{width:20px;height:20px;}
  .kpi-card .kpi-label{font-size:.78rem;color:var(--ink-soft);font-weight:700;}
  .kpi-card .kpi-val{font-size:1.6rem;font-weight:900;color:var(--primary-dark);}
  .kpi-card .kpi-note{font-size:.72rem;color:var(--ink-soft);margin-top:4px;}

  .card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:22px;box-shadow:var(--shadow);margin-bottom:20px;transition:.2s box-shadow;}
  .contact-card{background:linear-gradient(150deg,#0B4A2E,#0A0F24) !important;border:none !important;color:#fff;}
  .contact-card h2{color:#fff;}
  .card h2{margin:0 0 14px;font-size:1.05rem;font-weight:800;color:var(--primary-dark);display:flex;align-items:center;gap:8px;}
  .card h2 svg{width:19px;height:19px;color:var(--amber-dark);}
  .two-col{display:grid;grid-template-columns:1.3fr .7fr;gap:20px;align-items:start;}
  @media(max-width:960px){.two-col{grid-template-columns:1fr;}}

  table.data-table{width:100%;border-collapse:collapse;font-size:.88rem;}
  table.data-table th{text-align:right;padding:10px 10px;background:var(--surface-alt);color:var(--ink-soft);font-weight:800;font-size:.76rem;border-bottom:2px solid var(--border);white-space:nowrap;}
  table.data-table td{padding:11px 10px;border-bottom:1px solid var(--border);vertical-align:middle;}
  table.data-table tr{transition:.15s background;}
  table.data-table tr:hover td{background:var(--surface-alt);}
  table.data-table td.amt{font-weight:800;color:var(--primary-dark);white-space:nowrap;}
  .tbl-wrap{overflow-x:auto;}
  .pill{display:inline-block;padding:3px 10px;border-radius:999px;font-size:.72rem;font-weight:700;background:var(--primary-light);color:var(--primary-dark);}
  .pill.ok{background:#DCFCE7;color:#166534;}
  .pill.no{background:#FEE2E2;color:#991B1B;}
  .btn{display:inline-flex;align-items:center;gap:6px;background:var(--primary);color:#fff;border:none;padding:10px 17px;border-radius:11px;font-family:inherit;font-weight:700;font-size:.83rem;cursor:pointer;transition:.15s transform,.15s background,.15s box-shadow;}
  .btn:hover{background:var(--primary-dark);transform:translateY(-1px);box-shadow:0 8px 16px -8px rgba(18,44,122,.5);}
  .btn:active{transform:translateY(0);}
  .btn.amber{background:linear-gradient(150deg,var(--amber),var(--amber-dark));color:#1A1200;}
  .btn.amber:hover{background:linear-gradient(150deg,#FFA240,var(--amber-dark));box-shadow:0 8px 16px -8px rgba(217,105,10,.5);}
  .btn svg{width:15px;height:15px;}

  .filter-row{display:flex;gap:10px;flex-wrap:wrap;align-items:end;margin-bottom:18px;}
  .filter-row .field{display:flex;flex-direction:column;gap:5px;}
  .filter-row label{font-size:.76rem;font-weight:700;color:var(--ink-soft);}
  .filter-row input,.filter-row select{padding:9px 12px;border-radius:9px;border:1.5px solid var(--border);font-family:var(--font);font-size:.86rem;background:var(--surface-alt);}
  .filter-row input:focus,.filter-row select:focus{outline:none;border-color:var(--primary);background:#fff;}

  .empty-state{text-align:center;padding:40px 20px;color:var(--ink-soft);}
  .empty-state svg{width:44px;height:44px;color:var(--border);margin-bottom:10px;}

  .pagination{display:flex;gap:6px;justify-content:center;margin-top:16px;flex-wrap:wrap;}
  .pagination a,.pagination span{padding:7px 12px;border-radius:8px;font-size:.82rem;font-weight:700;border:1px solid var(--border);}
  .pagination a:hover{background:var(--primary-light);}
  .pagination .current{background:var(--primary);color:#fff;border-color:var(--primary);}

  .chart-box{background:var(--surface-alt);border-radius:14px;padding:16px;}
  .bar-rows{display:flex;flex-direction:column;gap:10px;}
  .bar-row .bar-top{display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:4px;}
  .bar-row .bar-top .bname{color:var(--ink-soft);font-weight:700;}
  .bar-row .bar-top .bval{font-weight:800;color:var(--ink);}
  .bar-track{height:11px;border-radius:7px;background:var(--border);overflow:hidden;}
  .bar-fill{height:100%;border-radius:7px;}
  .legend{width:100%;display:flex;flex-direction:column;gap:7px;margin-top:12px;}
  .legend-row{display:flex;align-items:center;gap:8px;font-size:.78rem;}
  .legend-dot{width:11px;height:11px;border-radius:3px;flex-shrink:0;}
  .legend-name{flex:1;color:var(--ink-soft);}
  .legend-val{font-weight:700;}

  .detail-box{background:var(--surface-alt);border-radius:12px;padding:14px 16px;font-size:.84rem;margin-top:8px;}
  .detail-box table{width:100%;border-collapse:collapse;margin-top:8px;}
  .detail-box td{padding:5px 4px;border-bottom:1px dashed var(--border);}

  @media(max-width:640px){
    table.data-table thead{display:none;}
    table.data-table, table.data-table tbody{display:block;width:100%;}
    table.data-table tr{display:block;margin-bottom:10px;border:1px solid var(--border);border-radius:12px;padding:4px 12px;background:var(--surface-alt);}
    table.data-table td{display:flex;justify-content:space-between;gap:10px;border-bottom:1px dashed var(--border);padding:8px 0;}
    table.data-table tr td:last-child{border-bottom:none;}
    table.data-table td::before{content:attr(data-label);font-weight:700;color:var(--ink-soft);font-size:.72rem;}
  }
</style>
