@php
    $contactIranTel = str_replace(' ', '', $contactIran);
    $contactUaeTel = str_replace(' ', '', $contactUae);
    $contactTehranTel = str_replace(' ', '', $contactTehran);
@endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>ناوراکار | محاسبه‌گر هزینه واردات خودرو</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap');
  :root{
    --ink:#1A1730; --ink-soft:#6B6584; --bg:#F7F5F1; --surface:#FFFFFF; --surface-alt:#F1EDE6;
    --border:#E4DFD3; --primary:#2D2657; --primary-dark:#171433; --primary-light:#EDE9F7;
    --amber:#C9A227; --amber-dark:#9C7D14; --amber-light:#F7EFD2; --violet:#8B5CF6; --green:#16A34A;
    --dash-1:#12102A; --dash-2:#221F45;
    --shadow:0 10px 28px -14px rgba(23,20,51,.35); --shadow-lg:0 18px 40px -16px rgba(23,20,51,.4);
    --radius:18px; --font:'Vazirmatn','Vazir','IRANYekanX','IRANSansX','Yekan','Noto Sans Arabic','Geeza Pro','Tahoma','Segoe UI',Arial,sans-serif;
  }
  *{box-sizing:border-box;}
  html{scroll-behavior:smooth;-webkit-text-size-adjust:100%;overflow-x:hidden;}
  body{margin:0;overflow-x:hidden;max-width:100vw;background:var(--bg);color:var(--ink);font-family:var(--font);line-height:1.85;font-size:17px;-webkit-font-smoothing:antialiased;text-rendering:optimizeLegibility;}
  ::selection{background:var(--amber);color:#fff;}
  .wrap{max-width:1200px;margin:0 auto;padding:0 18px;}
  .num-font{font-variant-numeric:tabular-nums;letter-spacing:.2px;}

  header.site{background:linear-gradient(120deg,var(--primary-dark),var(--primary) 75%,#3D3470);color:#fff;padding:16px 0;position:sticky;top:0;z-index:60;box-shadow:0 6px 22px -8px rgba(23,20,51,.5);}
  .header-row{display:flex;align-items:center;justify-content:space-between;gap:12px;}
  .brand{display:flex;align-items:center;gap:12px;min-width:0;}
  .brand-mark{width:48px;height:48px;border-radius:14px;flex-shrink:0;background:linear-gradient(160deg,var(--amber),var(--amber-dark));display:flex;align-items:center;justify-content:center;box-shadow:0 6px 14px -4px rgba(217,105,10,.6);}
  .brand-mark svg{width:25px;height:25px;}
  .brand-text{min-width:0;}
  .brand-text .name{font-weight:900;font-size:1.3rem;letter-spacing:.3px;}
  .brand-text .tag{font-size:.78rem;color:#C9D6FF;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  .print-btn{background:var(--amber);color:#1A1200;border:none;padding:11px 18px;border-radius:999px;font-family:inherit;font-weight:800;font-size:.88rem;cursor:pointer;display:flex;align-items:center;gap:6px;transition:.2s transform,.2s background;flex-shrink:0;box-shadow:0 6px 16px -6px rgba(217,105,10,.6);}
  .print-btn:hover{background:#FFA240;transform:translateY(-1px);}
  .print-btn svg{width:16px;height:16px;}
  .print-btn .lbl-full{display:inline;}
  @media(max-width:420px){.print-btn .lbl-full{display:none;}}

  .hero-band{background:radial-gradient(130% 160% at 12% -20%, #35306e 0%, var(--primary-dark) 55%, #0F0D24 100%);padding:44px 0 76px;position:relative;overflow:hidden;border-radius:0 0 32px 32px;box-shadow:0 24px 54px -26px rgba(23,20,51,.55);}
  .hero-band::before{content:"";position:absolute;width:340px;height:340px;border-radius:50%;background:radial-gradient(circle, rgba(201,162,39,.28), transparent 70%);top:-140px;left:-80px;}
  .hero-band::after{content:"";position:absolute;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle, rgba(139,92,246,.22), transparent 70%);bottom:-120px;right:-60px;}
  .hero{text-align:center;position:relative;z-index:1;}
  .hero-emblem{width:60px;height:60px;border-radius:18px;margin:0 auto 18px;background:linear-gradient(160deg,var(--amber),var(--amber-dark));display:flex;align-items:center;justify-content:center;box-shadow:0 12px 26px -8px rgba(156,125,20,.6);}
  .hero-emblem svg{width:30px;height:30px;color:#1A1200;}
  .hero h1{font-size:clamp(1.4rem,3.6vw,2.3rem);margin:0 0 12px;font-weight:900;color:#fff;line-height:1.5;}
  .hero p{margin:0 auto;max-width:640px;color:#D6CFF0;font-size:1rem;padding:0 8px;}
  .hero-disclaimer-wrap{position:relative;z-index:2;margin-top:-42px;padding:0 18px;}
  .hero-disclaimer-wrap .disclaimer-box{max-width:640px;margin:0 auto;background:var(--surface);box-shadow:0 16px 34px -18px rgba(23,20,51,.35);}

  .process-strip{display:flex;align-items:flex-start;justify-content:center;gap:0;padding:22px 0 4px;flex-wrap:wrap;row-gap:18px;}
  .process-item{display:flex;flex-direction:column;align-items:center;gap:8px;width:88px;text-align:center;flex-shrink:0;}
  .process-item span{font-size:.78rem;font-weight:700;color:var(--ink-soft);line-height:1.4;}
  .process-icon{width:50px;height:50px;border-radius:15px;background:var(--surface);border:2px solid var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow);flex-shrink:0;}
  .process-icon svg{width:22px;height:22px;}
  .process-line{width:42px;height:3px;margin:23px 2px 0;border-radius:3px;background:repeating-linear-gradient(90deg,var(--primary-light) 0 7px,transparent 7px 14px);flex-shrink:0;}
  @media(max-width:640px){
    .process-strip{gap:14px;row-gap:14px;}
    .process-line{display:none;}
    .process-item{width:72px;}
    .process-icon{width:44px;height:44px;}
    .process-icon svg{width:19px;height:19px;}
    .process-item span{font-size:.72rem;}
  }
  .process-strip.wiz-hidden{display:none;}
  .wiz-hidden{display:none;}

  /* ===== Wizard shell ===== */
  .wiz-wrap{padding:14px 0 110px;scroll-margin-top:90px;}
  .wiz-progress{display:flex;justify-content:center;align-items:center;gap:6px;padding:8px 0 20px;flex-wrap:wrap;}
  .wiz-dot{width:30px;height:30px;border-radius:50%;background:var(--surface);border:2px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:.76rem;font-weight:800;color:var(--ink-soft);flex-shrink:0;}
  .wiz-dot.done{background:var(--primary);border-color:var(--primary);color:#fff;}
  .wiz-dot.current{background:var(--amber);border-color:var(--amber-dark);color:#1A1200;box-shadow:0 0 0 4px var(--primary-light);}
  .wiz-dot-line{width:20px;height:2px;background:var(--border);flex-shrink:0;}
  .wiz-dot-line.done{background:var(--primary);}
  .wiz-step{display:none;}
  .wiz-step.active{display:block;animation:wizFade .25s ease;}
  @keyframes wizFade{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
  .wiz-step-title{font-size:1.1rem;font-weight:900;color:var(--primary-dark);margin:0 0 4px;text-align:center;}
  .wiz-step-sub{font-size:.86rem;color:var(--ink-soft);text-align:center;margin:0 0 20px;}

  .start-options{display:flex;flex-direction:column;gap:12px;}
  .start-opt{
    display:flex;align-items:center;gap:14px;background:var(--surface);border:2px solid var(--border);
    border-inline-start:5px solid var(--border);
    border-radius:16px;padding:16px 18px;cursor:pointer;font-family:inherit;text-align:right;transition:.15s all;
    position:relative;
  }
  .start-opt:hover{border-color:var(--primary);border-inline-start-color:var(--primary);transform:translateY(-2px);box-shadow:var(--shadow-lg,var(--shadow));}
  .start-opt .so-icon{width:48px;height:48px;border-radius:13px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
  .start-opt .so-icon svg{width:24px;height:24px;}
  .start-opt[data-mode="vin"]{border-inline-start-color:#3E6BFF;}
  .start-opt[data-mode="vin"] .so-icon{background:#E3EAFE;color:#3E6BFF;}
  .start-opt[data-mode="model"]{border-inline-start-color:var(--amber);}
  .start-opt[data-mode="model"] .so-icon{background:var(--amber-light,#F7EFD2);color:var(--amber-dark);}
  .start-opt[data-mode="cc"]{border-inline-start-color:var(--violet);}
  .start-opt[data-mode="cc"] .so-icon{background:#EDE4FF;color:var(--violet);}
  .start-opt.contact-opt{border-inline-start-color:var(--green);}
  .start-opt.contact-opt .so-icon{background:#DCFCE7;color:var(--green);}
  .start-opt .so-badge{position:absolute;top:-9px;left:16px;background:var(--amber);color:#1A1200;font-size:.66rem;font-weight:800;padding:3px 10px;border-radius:999px;box-shadow:0 4px 10px -3px rgba(156,125,20,.5);}
  .start-opt .so-title{font-size:.98rem;font-weight:800;color:var(--ink);}
  .start-opt .so-sub{font-size:.78rem;color:var(--ink-soft);margin-top:2px;}
  .start-opt .so-arrow{margin-right:auto;color:var(--border);flex-shrink:0;}
  .start-opt .so-arrow svg{width:20px;height:20px;}

  .wiz-nav{
    position:fixed;bottom:0;left:0;right:0;z-index:70;background:var(--surface);
    border-top:1px solid var(--border);padding:12px 18px;display:flex;justify-content:space-between;
    align-items:center;gap:10px;box-shadow:0 -8px 22px -10px rgba(0,0,0,.15);
  }
  .wiz-nav.hidden{display:none;}
  .wiz-nav-btn{border:none;border-radius:12px;padding:13px 20px;font-family:inherit;font-weight:800;font-size:.9rem;cursor:pointer;display:flex;align-items:center;gap:6px;}
  .wiz-nav-btn svg{width:17px;height:17px;}
  .wiz-prev{background:var(--surface-alt);color:var(--ink-soft);}
  .wiz-prev:disabled{opacity:0;pointer-events:none;}
  .wiz-next{background:linear-gradient(150deg,var(--amber),var(--amber-dark));color:#1A1200;box-shadow:0 8px 18px -6px rgba(217,105,10,.55);flex:1;justify-content:center;max-width:280px;}
  .wiz-mid-info{font-size:.78rem;color:var(--ink-soft);text-align:center;flex:1;}

  .card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:22px;box-shadow:var(--shadow);margin-bottom:18px;}
  @media(max-width:520px){.card{padding:17px;border-radius:16px;}}
  .card h2{margin:0 0 5px;font-size:1.06rem;font-weight:800;color:var(--primary-dark);display:flex;align-items:center;gap:9px;}
  .card h2 .num{width:34px;height:34px;border-radius:10px;background:linear-gradient(160deg,var(--primary-light),#fff);color:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
  .card h2 .num svg{width:19px;height:19px;}
  .card h2 > svg{width:19px;height:19px;flex-shrink:0;color:var(--amber-dark);}
  .card .sub{font-size:.85rem;color:var(--ink-soft);margin:0 0 14px;}

  .disclaimer-box{display:flex;align-items:flex-start;gap:10px;background:#FFF7ED;border:1px solid #FCD9A8;color:#9C5B0E;border-radius:12px;padding:12px 14px;font-size:.8rem;margin:12px 0 4px;line-height:1.7;}
  .disclaimer-box svg{width:19px;height:19px;flex-shrink:0;color:var(--amber-dark);margin-top:1px;}

  .field-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
  @media(max-width:520px){.field-grid{grid-template-columns:1fr;}}
  .field{display:flex;flex-direction:column;gap:7px;}
  .field label{font-size:.88rem;font-weight:700;color:var(--ink);display:flex;align-items:center;gap:7px;flex-wrap:wrap;}
  .field label svg.flbl{width:16px;height:16px;color:var(--primary);flex-shrink:0;}
  .field .hint{font-size:.72rem;color:var(--ink-soft);font-weight:500;}
  .field .input-wrap{position:relative;}
  .field input{width:100%;padding:13px 54px 13px 14px;border-radius:12px;border:1.5px solid var(--border);font-family:var(--font);font-size:1.05rem;font-weight:600;background:var(--surface-alt);color:var(--ink);transition:.15s border-color,.15s background;}
  .field input:focus{outline:none;border-color:var(--primary);background:#fff;box-shadow:0 0 0 4px var(--primary-light);}
  .field .unit{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:.74rem;color:var(--ink-soft);font-weight:800;background:var(--surface-alt);pointer-events:none;}
  .field .unit-toggle{pointer-events:auto;cursor:pointer;border:1px solid var(--border);border-radius:999px;padding:2px 10px;font-family:var(--font);transition:.15s background,.15s color;}
  .field .unit-toggle:hover{background:var(--primary-light);color:var(--primary);}
  .field select{width:100%;padding:13px 14px;border-radius:12px;border:1.5px solid var(--border);font-family:var(--font);font-size:1rem;font-weight:600;background:var(--surface-alt);color:var(--ink);transition:.15s border-color,.15s background;appearance:none;background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%235B6478' stroke-width='2'><path d='M6 9l6 6 6-6'/></svg>");background-repeat:no-repeat;background-position:left 12px center;background-size:16px;}
  .field select:focus{outline:none;border-color:var(--primary);background-color:#fff;box-shadow:0 0 0 4px var(--primary-light);}
  .field select:disabled{opacity:.55;cursor:not-allowed;}

  details.adv{border-top:1px dashed var(--border);margin-top:16px;padding-top:14px;}
  details.adv summary{cursor:pointer;font-weight:700;font-size:.86rem;color:var(--primary);list-style:none;display:flex;align-items:center;gap:6px;padding:6px 0;}
  details.adv summary::-webkit-details-marker{display:none;}
  details.adv summary::before{content:"⚙";}
  .rate-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px;}
  @media(max-width:520px){.rate-grid{grid-template-columns:1fr;}}
  .rate-row{display:flex;align-items:center;justify-content:space-between;gap:8px;background:var(--surface-alt);border-radius:10px;padding:10px 12px;}
  .rate-row span{font-size:.8rem;color:var(--ink-soft);font-weight:600;}
  .rate-row input{width:66px;text-align:center;padding:6px 4px;border-radius:7px;border:1px solid var(--border);font-family:var(--font);font-size:.84rem;background:#fff;font-weight:700;}

  .manual-cc-row{display:flex;gap:12px;align-items:end;flex-wrap:wrap;margin-top:14px;}
  .manual-cc-row .field{flex:1;min-width:180px;}
  .manual-cc-btn{background:var(--primary);color:#fff;border:none;padding:13px 18px;border-radius:12px;font-family:inherit;font-weight:800;font-size:.86rem;cursor:pointer;white-space:nowrap;transition:.15s background;}
  .manual-cc-btn:hover{background:var(--primary-dark);}

  .search-wrap{position:relative;}
  .search-list{position:absolute;top:100%;right:0;left:0;background:#fff;border:1.5px solid var(--border);border-radius:12px;margin-top:6px;max-height:260px;overflow-y:auto;box-shadow:var(--shadow-lg);z-index:35;display:none;}
  .search-item{padding:13px 15px;font-size:.94rem;font-weight:600;cursor:pointer;border-bottom:1px solid var(--surface-alt);}
  .search-item:last-child{border-bottom:none;}
  .search-item:hover,.search-item:active{background:var(--primary-light);}
  .search-empty{padding:14px;text-align:center;color:var(--ink-soft);font-size:.86rem;}
  .picked-tag{display:inline-flex;align-items:center;gap:6px;background:var(--primary-light);color:var(--primary-dark);font-weight:800;font-size:.82rem;padding:6px 12px;border-radius:999px;margin-top:8px;}
  .variant-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;}
  .variant-chip{padding:11px 15px;border-radius:11px;border:2px solid var(--border);background:#fff;font-family:inherit;font-size:.88rem;font-weight:700;cursor:pointer;color:var(--ink);}
  .variant-chip.active{border-color:var(--primary);background:var(--primary-light);color:var(--primary-dark);}
  .car-match-list{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;margin-top:10px;}
  .car-match-item{display:block;border:2px solid var(--border);border-radius:14px;overflow:hidden;text-decoration:none;color:var(--ink);background:#fff;transition:border-color .15s;}
  .car-match-item:hover{border-color:var(--primary);}
  .car-match-item .thumb{aspect-ratio:4/3;background:var(--surface-alt);overflow:hidden;}
  .car-match-item .thumb img{width:100%;height:100%;object-fit:cover;display:block;}
  .car-match-item .info{padding:8px 10px;}
  .car-match-item .title{font-size:.78rem;font-weight:800;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
  .car-match-item .price{margin-top:4px;font-size:.82rem;font-weight:900;color:var(--primary);}
  .fuel-radio-row{display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;}
  .fuel-radio{flex:1;min-width:120px;position:relative;}
  .fuel-radio input{position:absolute;opacity:0;inset:0;cursor:pointer;margin:0;}
  .fuel-radio .frb{display:block;text-align:center;padding:13px 10px;border-radius:12px;border:2px solid var(--border);font-weight:700;font-size:.88rem;color:var(--ink-soft);}
  .fuel-radio input:checked + .frb{border-color:var(--primary);background:var(--primary-light);color:var(--primary-dark);}

  .manual-hybrid-check{display:flex;align-items:center;gap:9px;margin-top:12px;font-size:.86rem;font-weight:600;color:var(--ink);cursor:pointer;}
  .manual-hybrid-check input{width:18px;height:18px;accent-color:var(--primary);flex-shrink:0;}

  .vin-only-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;}
  .vin-only-actions button{flex:1;min-width:160px;}
  .vin-msg{padding:11px 14px;border-radius:10px;font-size:.85rem;font-weight:700;margin-bottom:8px;}
  .vin-msg.err{background:#FEE2E2;color:#991B1B;}
  .vin-msg.ok{background:#DCFCE7;color:#166534;}
  .vin-msg.warn{background:#FEF3C7;color:#92400E;}
  .vin-info{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;background:var(--surface-alt);border-radius:10px;padding:12px 14px;font-size:.84rem;margin-bottom:8px;}
  @media(max-width:480px){.vin-info{grid-template-columns:1fr;}}
  .vin-disclaimer{font-size:.72rem;color:var(--ink-soft);margin-top:6px;}

  .cat-confirm{display:flex;align-items:center;gap:12px;background:var(--primary-light);border-radius:14px;padding:14px 16px;margin-bottom:6px;}
  .cat-confirm .cc-icon{width:44px;height:44px;border-radius:50%;background:#fff;color:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;}
  .cat-confirm .cc-icon svg{width:22px;height:22px;}
  .cat-confirm .cc-label{font-weight:800;color:var(--primary-dark);font-size:.98rem;}
  .cat-confirm .cc-coef{font-size:.78rem;color:var(--ink-soft);margin-top:2px;}

  .cat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;}
  @media(max-width:520px){.cat-grid{grid-template-columns:repeat(2,1fr);}}
  .cat-btn{background:var(--surface);border:2px solid var(--border);border-radius:16px;padding:15px 6px 13px;text-align:center;cursor:pointer;font-family:inherit;transition:.15s all;display:flex;flex-direction:column;align-items:center;gap:7px;position:relative;min-height:104px;justify-content:center;-webkit-tap-highlight-color:transparent;}
  .cat-btn .icon{width:40px;height:40px;border-radius:50%;background:linear-gradient(160deg,var(--primary-light),#fff);display:flex;align-items:center;justify-content:center;color:var(--primary);transition:.15s all;}
  .cat-btn .icon svg{width:21px;height:21px;}
  .cat-btn .lbl{font-size:.86rem;font-weight:800;color:var(--ink);}
  .cat-btn .coef{font-size:.74rem;color:var(--ink-soft);font-weight:600;}
  .cat-btn:hover{border-color:var(--primary);transform:translateY(-3px);}
  .cat-btn.active{border-color:transparent;background:linear-gradient(150deg,var(--primary),var(--primary-dark));box-shadow:var(--shadow-lg);}
  .cat-btn.active .icon{background:linear-gradient(160deg,var(--amber),var(--amber-dark));color:#1A1200;}
  .cat-btn.active .lbl,.cat-btn.active .coef{color:#fff;}
  .cat-btn.active::after{content:"✓";position:absolute;top:8px;left:9px;width:18px;height:18px;border-radius:50%;background:var(--amber);color:#1A1200;font-size:.62rem;display:flex;align-items:center;justify-content:center;font-weight:900;}

  .dash-panel{background:radial-gradient(120% 140% at 20% 0%, #1C2A5E 0%, var(--dash-1) 60%);border-radius:22px;padding:26px 22px 22px;color:#fff;box-shadow:var(--shadow-lg);border:1px solid #26305F;position:relative;overflow:hidden;}
  .dash-panel::before{content:"";position:absolute;inset:0;pointer-events:none;opacity:.5;background-image:radial-gradient(rgba(255,255,255,.06) 1px, transparent 1px);background-size:16px 16px;}
  .dash-top{display:flex;align-items:center;justify-content:space-between;gap:8px;position:relative;}
  .dash-title{font-size:.86rem;font-weight:800;color:#AFC0FF;display:flex;align-items:center;gap:7px;}
  .dash-title svg{width:16px;height:16px;color:var(--amber);}
  .dash-live{font-size:.68rem;font-weight:700;color:#7CFFB2;display:flex;align-items:center;gap:5px;}
  .dash-live::before{content:"";width:7px;height:7px;border-radius:50%;background:#3CE676;box-shadow:0 0 8px #3CE676;animation:pulse 1.6s infinite;}
  @keyframes pulse{0%,100%{opacity:1;}50%{opacity:.35;}}
  .dash-display{text-align:center;padding:20px 4px 6px;position:relative;}
  .dash-num{font-size:clamp(2.1rem,7vw,2.7rem);font-weight:900;color:#fff;text-shadow:0 0 18px rgba(255,138,30,.55),0 0 3px rgba(255,255,255,.4);letter-spacing:.5px;line-height:1.1;}
  .dash-unit{font-size:.85rem;color:var(--amber);font-weight:800;margin-top:2px;letter-spacing:1px;}
  .dash-sub{font-size:.78rem;color:#93A2D9;margin-top:6px;}
  .dash-mini-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:18px 0 6px;position:relative;}
  @media(max-width:420px){.dash-mini-grid{grid-template-columns:repeat(2,1fr);}}
  .mini-gauge-item{display:flex;flex-direction:column;align-items:center;gap:5px;text-align:center;}
  .mini-gauge{width:44px;height:44px;border-radius:50%;position:relative;background:conic-gradient(var(--ring-color,#fff) calc(var(--pct,0)*1%), rgba(255,255,255,.14) 0);}
  .mini-gauge::before{content:"";position:absolute;inset:5px;border-radius:50%;background:#111A3B;}
  .mini-gauge .mg-icon{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;}
  .mini-gauge .mg-icon svg{width:14px;height:14px;}
  .mini-gauge-item .mg-label{font-size:.7rem;color:#9FADE0;font-weight:700;}
  .mini-gauge-item .mg-val{font-size:.76rem;color:#fff;font-weight:800;}
  .dash-lines{margin-top:16px;border-top:1px solid rgba(255,255,255,.12);padding-top:6px;position:relative;}
  .dash-line{display:flex;justify-content:space-between;align-items:center;padding:11px 2px;border-bottom:1px solid rgba(255,255,255,.08);font-size:.9rem;gap:10px;}
  .dash-line:last-child{border-bottom:none;}
  .dl-lbl{display:flex;align-items:center;gap:8px;color:#D6DEFB;}
  .dl-lbl svg{width:17px;height:17px;color:var(--amber);flex-shrink:0;}
  .dash-line .amt{font-weight:800;color:#fff;}
  .dash-line.total{background:rgba(255,255,255,.08);border-radius:12px;padding:15px;margin-top:8px;border-bottom:none;}
  .dash-line.total .dl-lbl{color:#fff;font-weight:800;font-size:.98rem;}
  .dash-line.total .amt{color:var(--amber);font-size:1.25rem;}

  .table-wrap{overflow-x:auto;}
  .cost-table{width:100%;border-collapse:collapse;font-size:.92rem;}
  .cost-table th{text-align:right;padding:10px 8px;background:var(--surface-alt);color:var(--ink-soft);font-weight:800;font-size:.78rem;border-bottom:2px solid var(--border);white-space:nowrap;}
  .cost-table td{padding:11px 8px;border-bottom:1px solid var(--border);vertical-align:top;}
  .cost-table tr:last-child td{border-bottom:none;}
  .cost-table td.amt{font-weight:800;white-space:nowrap;text-align:left;color:var(--primary-dark);}
  .cost-table td.idx{color:var(--ink-soft);width:26px;font-weight:700;}
  .cost-table td.rate{color:var(--ink-soft);font-size:.8rem;}
  .cost-table tfoot td{font-weight:900;background:var(--primary-light);color:var(--primary-dark);border-bottom:none;font-size:.95rem;}
  @media(max-width:640px){
    .cost-table thead{display:none;}
    .cost-table tbody, .cost-table tfoot{display:block;width:100%;}
    .cost-table tr{display:block;margin-bottom:11px;border:1px solid var(--border);border-radius:14px;padding:5px 14px;background:var(--surface-alt);}
    .cost-table tfoot tr{background:var(--primary-light);}
    .cost-table td{display:flex;justify-content:space-between;align-items:center;gap:10px;border-bottom:1px dashed var(--border);padding:9px 0;}
    .cost-table tr td:last-child{border-bottom:none;}
    .cost-table td.idx{display:none;}
    .cost-table td::before{content:attr(data-label);font-weight:700;color:var(--ink-soft);font-size:.74rem;flex-shrink:0;}
    .cost-table td.rate{flex-direction:column;align-items:flex-start;gap:2px;}
  }

  .chart-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
  @media(max-width:820px){.chart-grid{grid-template-columns:1fr;}}
  .chart-box{background:var(--surface-alt);border-radius:16px;padding:18px;}
  .chart-box h3{margin:0 0 14px;font-size:.9rem;font-weight:800;color:var(--primary-dark);text-align:center;}
  .donut-wrap{display:flex;flex-direction:column;align-items:center;gap:16px;}
  .donut-svg{width:180px;height:180px;}
  .legend{width:100%;display:flex;flex-direction:column;gap:8px;}
  .legend-row{display:flex;align-items:center;gap:9px;font-size:.82rem;}
  .legend-dot{width:12px;height:12px;border-radius:4px;flex-shrink:0;}
  .legend-name{flex:1;color:var(--ink-soft);font-weight:600;}
  .legend-val{font-weight:800;color:var(--ink);}
  .bar-rows{display:flex;flex-direction:column;gap:16px;}
  .bar-row .bar-top{display:flex;justify-content:space-between;font-size:.84rem;margin-bottom:6px;}
  .bar-row .bar-top .bname{color:var(--ink-soft);font-weight:700;}
  .bar-row .bar-top .bval{font-weight:800;color:var(--ink);}
  .bar-track{height:14px;border-radius:9px;background:var(--border);overflow:hidden;}
  .bar-fill{height:100%;border-radius:9px;transition:width .35s ease;}

  .quote-form{margin-top:6px;}
  .qf-sub{font-size:.82rem;color:var(--ink-soft);margin:0 0 14px;}
  .qf-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
  @media(max-width:480px){.qf-grid{grid-template-columns:1fr;}}
  .qf-grid textarea{grid-column:1/-1;resize:vertical;font-family:var(--font);}
  .qf-grid input,.qf-grid textarea{border:1.5px solid var(--border);color:var(--ink);border-radius:10px;padding:12px 13px;font-family:var(--font);font-size:.95rem;font-weight:600;background:var(--surface-alt);}
  .qf-grid input:focus,.qf-grid textarea:focus{outline:none;border-color:var(--primary);background:#fff;box-shadow:0 0 0 4px var(--primary-light);}
  #qSubmitBtn{width:100%;margin-top:12px;background:linear-gradient(150deg,var(--primary),var(--primary-dark));color:#fff;border:none;padding:15px;border-radius:12px;font-family:inherit;font-weight:900;font-size:.98rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 8px 18px -6px rgba(18,44,122,.5);transition:.15s transform;}
  #qSubmitBtn svg{width:17px;height:17px;}
  #qSubmitBtn:hover{transform:translateY(-1px);}
  #qSubmitBtn:disabled{opacity:.6;cursor:not-allowed;transform:none;}
  .qf-status{margin-top:10px;font-size:.85rem;font-weight:700;min-height:1.2em;}
  .qf-status.ok{color:var(--green);}
  .qf-status.err{color:#DC2626;}

  .contact-card{background:linear-gradient(150deg,#0B4A2E,#0A0F24);border:none;color:#fff;}
  .contact-card h2{color:#fff;}
  .contact-card .sub{color:#B7E8CE;}
  .contact-rows{display:flex;flex-direction:column;gap:10px;margin-top:12px;}
  .contact-row{display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:14px;padding:12px 14px;}
  .cr-flag{font-size:1.05rem;line-height:1;flex-shrink:0;}
  .cr-info{flex:1;min-width:0;}
  .cr-num{font-weight:900;font-size:.94rem;font-family:monospace;letter-spacing:.3px;}
  .cr-label{font-size:.68rem;color:#93A2D9;font-weight:600;margin-top:1px;}
  .cr-actions{display:flex;gap:6px;flex-shrink:0;}
  .cr-btn{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;text-decoration:none;transition:.15s transform;border:none;cursor:pointer;}
  .cr-btn:hover{transform:translateY(-2px);}
  .cr-btn svg{width:14px;height:14px;}
  .cr-btn.call{background:var(--amber);color:#1A1200;}
  .cr-btn.whatsapp{background:#25D366;color:#08341C;}
  .cr-btn.telegram{background:#29A9EA;color:#062A3D;}
  .contact-note{font-size:.78rem;color:#93A2D9;margin-top:14px;line-height:1.7;}
  .reset-link{display:inline-flex;align-items:center;gap:6px;color:#FFD8B0;font-size:.82rem;font-weight:700;margin-top:16px;cursor:pointer;background:none;border:none;font-family:inherit;}
  .reset-link svg{width:14px;height:14px;}

  footer{padding:26px 0 40px;text-align:center;}
  footer p{font-size:.8rem;color:var(--ink-soft);max-width:660px;margin:0 auto;padding:0 12px;}
  footer .brand2{font-weight:800;color:var(--primary-dark);}

  .print-only{display:none;}
  .ps-header{background:linear-gradient(120deg,var(--primary-dark),var(--primary));color:#fff;padding:26px 30px;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:14px;border-radius:14px 14px 0 0;}
  .ps-brand{font-size:1.5rem;font-weight:900;}
  .ps-brand span{display:block;font-size:.82rem;font-weight:500;color:#D6CFF0;margin-top:3px;}
  .ps-meta{text-align:left;font-size:.86rem;line-height:1.9;}
  .ps-meta b{color:var(--amber);}
  .ps-body{background:#fff;padding:26px 30px;border:1px solid var(--border);border-top:none;border-radius:0 0 14px 14px;}
  .ps-car-box{background:var(--primary-light);border-radius:12px;padding:16px 18px;margin-bottom:20px;font-size:.92rem;}
  .ps-car-box .ps-car-title{font-weight:900;color:var(--primary-dark);font-size:1rem;margin-bottom:8px;}
  .ps-car-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 20px;}
  .ps-car-grid div span{display:block;color:var(--ink-soft);font-size:.74rem;margin-bottom:2px;}
  .ps-h3{font-size:.98rem;font-weight:800;color:var(--primary-dark);margin:22px 0 8px;border-bottom:2px solid var(--primary-light);padding-bottom:6px;}
  .ps-table{width:100%;border-collapse:collapse;font-size:.86rem;}
  .ps-table th{background:var(--surface-alt);text-align:right;padding:9px 10px;font-size:.74rem;color:var(--ink-soft);border-bottom:2px solid var(--border);}
  .ps-table td{padding:9px 10px;border-bottom:1px solid var(--border);}
  .ps-table td.amt{text-align:left;font-weight:700;white-space:nowrap;}
  .ps-table tr.total td{font-weight:900;background:var(--primary-light);color:var(--primary-dark);font-size:1rem;}
  .ps-disclaimer{font-size:.76rem;color:var(--ink-soft);line-height:1.9;margin-top:24px;border-top:1px solid var(--border);padding-top:14px;}
  .ps-sign{display:flex;justify-content:space-between;margin-top:40px;padding-top:16px;border-top:1px dashed var(--border);font-size:.82rem;color:var(--ink-soft);}
  .ps-contact{background:var(--surface-alt);border-radius:12px;padding:16px 18px;margin-top:22px;font-size:.85rem;line-height:2.1;}
  .ps-contact-title{font-weight:800;color:var(--primary-dark);margin-bottom:6px;}

  @media print{
    body{background:#fff;padding-bottom:0;}
    header.site, .hero, .process-strip, .wiz-wrap, .wiz-nav, footer{display:none !important;}
    .print-only{display:block !important;max-width:800px;margin:0 auto;}
  }
</style>
</head>
<body>
<header class="site">
  <div class="wrap header-row">
    <a href="{{ route('public.home') }}" class="brand" style="text-decoration:none;color:inherit;">
      <div class="brand-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="#1A1200" stroke-width="1.9"><path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13"/><rect x="2.5" y="13" width="19" height="5" rx="1.5"/><circle cx="7" cy="18.5" r="1.6" fill="#1A1200" stroke="none"/><circle cx="17" cy="18.5" r="1.6" fill="#1A1200" stroke="none"/></svg>
      </div>
      <div class="brand-text">
        <div class="name">ناوراکار</div>
        <div class="tag">محاسبه‌گر رسمی هزینه واردات خودرو</div>
      </div>
    </a>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <a href="{{ route('public.car-prices.index') }}" class="print-btn" style="background:rgba(255,255,255,.14);color:#fff;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13"/><rect x="2.5" y="13" width="19" height="5" rx="1.5"/></svg>
        <span class="lbl-full">قیمت خودروها</span>
      </a>
      @foreach ($menuItems as $item)
        <a href="{{ $item->url }}" @if($item->opens_new_tab) target="_blank" rel="noopener" @endif class="print-btn" style="background:rgba(255,255,255,.14);color:#fff;">
          <span class="lbl-full">{{ $item->label }}</span>
        </a>
      @endforeach
      <a href="{{ route('public.home') }}" class="print-btn" style="background:rgba(255,255,255,.14);color:#fff;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20Z"/></svg>
        <span class="lbl-full">سایت اصلی</span>
      </a>
      <button class="print-btn" onclick="printReport()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><rect x="6" y="14" width="12" height="8"/><path d="M6 18H4a1 1 0 0 1-1-1v-6a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v6a1 1 0 0 1-1 1h-2"/></svg>
        <span class="lbl-full">چاپ گزارش</span>
      </button>
    </div>
  </div>
</header>

<div class="hero-band">
  <div class="hero wrap">
    <div class="hero-emblem">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13"/><rect x="2.5" y="13" width="19" height="5" rx="1.5"/><circle cx="7" cy="18.5" r="1.6" fill="currentColor" stroke="none"/><circle cx="17" cy="18.5" r="1.6" fill="currentColor" stroke="none"/></svg>
    </div>
    <h1>هزینه دقیق ترخیص، حقوق و عوارض واردات خودرو خود را همین حالا محاسبه کنید</h1>
    <p>مرحله‌به‌مرحله جلو بروید: خودروی خود را معرفی کنید، قیمت و نرخ ارز را وارد کنید و گزارش کامل را ببینید. <b style="color:#fff;">این ابزار یک برآورد اولیه است</b> — برای تعیین قطعی حتماً با کارشناسان ناوراکار تماس بگیرید.</p>
  </div>
</div>
<div class="hero-disclaimer-wrap">
  <div class="disclaimer-box">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
    <span>این طرح واردات برای <b>ایرانیان دارای مجوز اقامت خارج از کشور</b> امکان صدور دارد. برای سایر متقاضیان، شرکت با بررسی شرایط می‌تواند نسبت به اخذ مجوز اقدام کند — برای اطلاع دقیق با کارشناسان تماس بگیرید.</span>
  </div>
</div>

<div class="process-strip wrap">
  <div class="process-item">
    <div class="process-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="12" rx="2"/><path d="M3 11h18"/><path d="M7 15h.01M11 15h4"/></svg></div>
    <span>خرید خودرو</span>
  </div>
  <div class="process-line"></div>
  <div class="process-item">
    <div class="process-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V10l8-6 8 6v11"/><path d="M9 21v-6h6v6"/><path d="M4 10h16"/></svg></div>
    <span>ترخیص گمرکی</span>
  </div>
  <div class="process-line"></div>
  <div class="process-item">
    <div class="process-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M8.5 12.5l2 2 5-5"/></svg></div>
    <span>محاسبه عوارض و مالیات</span>
  </div>
  <div class="process-line"></div>
  <div class="process-item">
    <div class="process-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 15h4M15 10h2M15 14h2"/></svg></div>
    <span>صدور پلاک انتظامی</span>
  </div>
</div>

<div class="wiz-wrap wrap">
  <div class="wiz-progress" id="wizProgress"></div>

  <!-- STEP: start -->
  <div class="wiz-step active" data-step="start">
    <div class="card">
      <div class="wiz-step-title">چطور می‌خواهید شروع کنید؟</div>
      <div class="wiz-step-sub">یکی از روش‌های زیر را انتخاب کنید</div>
      <div class="start-options">
        <button type="button" class="start-opt" data-mode="vin">
          <div class="so-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M9 4v16"/></svg></div>
          <div><div class="so-title">بررسی مجاز بودن با شماره شاسی (VIN)</div><div class="so-sub">فقط برای تشخیص برند/کشور سازنده و مجاز بودن خودرو — برای محاسبه هزینه، در ادامه باید مسیر دیگری طی کنید</div></div>
          <div class="so-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg></div>
        </button>
        <button type="button" class="start-opt" data-mode="model">
          <span class="so-badge">پیشنهادی</span>
          <div class="so-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13"/><rect x="2.5" y="13" width="19" height="5" rx="1.5"/><circle cx="7" cy="18.5" r="1.6"/><circle cx="17" cy="18.5" r="1.6"/></svg></div>
          <div><div class="so-title">محاسبه هزینه با انتخاب برند و مدل</div><div class="so-sub">از فهرست خودروهای مجاز طرح انتخاب کنید و گزارش کامل هزینه را ببینید</div></div>
          <div class="so-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg></div>
        </button>
        <button type="button" class="start-opt" data-mode="cc">
          <div class="so-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 15a8 8 0 1 1 16 0"/><path d="M12 15L15.3,9.4"/><circle cx="12" cy="15" r="1.3" fill="currentColor" stroke="none"/></svg></div>
          <div><div class="so-title">محاسبه هزینه فقط بر اساس حجم موتور</div><div class="so-sub">مدل خودرو را نمی‌دانید؟ فقط حجم موتور را وارد کنید</div></div>
          <div class="so-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg></div>
        </button>
        <button type="button" class="start-opt contact-opt" data-mode="contact">
          <div class="so-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></div>
          <div><div class="so-title">ارتباط مستقیم با کارشناسان</div><div class="so-sub">تماس تلفنی یا واتساپ — بدون نیاز به محاسبه آنلاین</div></div>
          <div class="so-arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg></div>
        </button>
      </div>
    </div>
  </div>

  <!-- STEP: details -->
  <div class="wiz-step" data-step="details">
    <div class="wiz-step-title">اطلاعات خودرو</div>
    <div class="wiz-step-sub">حجم موتور و دسته خودرو مشخص می‌شود؛ در صورت نیاز می‌توانید دستی هم اصلاح کنید</div>

    <div class="card" id="detailsVinCard">
      <h2><span class="num"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M9 4v16"/></svg></span> بررسی شماره شاسی (VIN)</h2>
      <p class="sub">این بررسی فقط برای تعیین مجاز بودن خودرو است، نه محاسبه هزینه.</p>
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <div class="field" style="flex:1;min-width:220px;">
          <label>شماره شاسی (VIN)</label>
          <div class="input-wrap"><input type="text" id="vinInput" maxlength="17" placeholder="مثلاً JTMHY7AJ0F4046XXX" style="text-transform:uppercase;"></div>
        </div>
        <button type="button" class="manual-cc-btn" id="vinCheckBtn">استعلام شماره شاسی</button>
      </div>
      <div id="vinResult"></div>
      <div class="vin-only-actions">
        <button type="button" class="manual-cc-btn" style="background:var(--surface-alt);color:var(--ink-soft);" onclick="goToStep('start')">بازگشت به شروع</button>
        <button type="button" class="manual-cc-btn" onclick="switchModeTo('model')">ادامه برای محاسبه هزینه (انتخاب مدل)</button>
      </div>
      <div class="disclaimer-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
        <span>استعلام VIN از پایگاه داده عمومی NHTSA انجام می‌شود. برای خودروهای غیرآمریکایی که در این پایگاه ثبت کامل ندارند، کشور سازنده از روی کد شاسی تخمین زده می‌شود. این نتیجه صرفاً یک برآورد اولیه است.</span>
      </div>
    </div>

    <div class="card" id="detailsModelCard" style="display:none;">
      <h2><span class="num"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13"/><rect x="2.5" y="13" width="19" height="5" rx="1.5"/><circle cx="7" cy="18.5" r="1.6"/><circle cx="17" cy="18.5" r="1.6"/></svg></span> خودروی خود را انتخاب کنید</h2>
      <p class="sub">لیست زیر محدود به برندها و مدل‌های مجاز طرح واردات ناوراکار است. نام برند یا مدل را فارسی یا انگلیسی تایپ کنید (مثلاً «لکسوس» یا LX).</p>

      <div class="field search-wrap">
        <label>جستجوی خودرو (برند یا مدل)</label>
        <div class="input-wrap"><input type="text" id="carSearch" placeholder="مثلاً Toyota Camry یا لکسوس" autocomplete="off"></div>
        <div class="search-list" id="carSearchList"></div>
      </div>
      <div class="picked-tag" id="carPickedTag" style="display:none;"></div>

      <div id="carVariantWrap" style="display:none;margin-top:14px;">
        <label style="font-size:.9rem;font-weight:700;">نسخه / حجم موتور</label>
        <div class="variant-chips" id="carVariantChips"></div>
        <div class="picked-tag" id="carVariantAuto" style="display:none;"></div>
      </div>

      <div id="carListingsMatch" style="display:none;margin-top:16px;">
        <label style="font-size:.9rem;font-weight:700;">آگهی‌های آماده این برند در ناوراکار</label>
        <p class="sub" style="margin:4px 0 0;">این خودروها همین الان در سایت ناوراکار موجودند — می‌توانید مستقیم صفحه‌شان را ببینید.</p>
        <div class="car-match-list" id="carListingsMatchList"></div>
      </div>

      <div class="field" style="margin-top:14px;">
        <label>سال ساخت</label>
        <select id="carYear"></select>
      </div>

      <details class="adv">
        <summary>خودروی من در لیست نیست — انتخاب دستی حجم موتور</summary>
        <div class="manual-cc-row">
          <div class="field"><label>حجم موتور (سی‌سی) را وارد کنید <span class="hint">(فقط برای برندهای مجاز طرح)</span></label>
            <div class="input-wrap"><input type="text" inputmode="decimal" id="manualCC" placeholder="مثلاً 2,000"><span class="unit">cc</span></div>
          </div>
        </div>
        <label class="manual-hybrid-check">
          <input type="checkbox" id="manualIsHybrid">
          <span>این خودرو هیبرید یا برقی است (حجم موتور را نادیده بگیر)</span>
        </label>
        <button type="button" class="manual-cc-btn" id="manualCCBtn" style="margin-top:12px;">تعیین خودکار دسته</button>
      </details>
    </div>

    <div class="card" id="detailsCcCard" style="display:none;">
      <div class="disclaimer-box" style="margin:0;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
        <span>مدل خودرو را نمی‌دانید؟ کافیست دسته حجم موتور را از کارت زیر انتخاب کنید — نیازی به تایپ عدد نیست.</span>
      </div>
    </div>

    <div class="card">
      <h2><span class="num"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 18l6-6-6-6"/></svg></span> دسته خودرو</h2>
      <p class="sub" id="catCardSub">به‌صورت خودکار از روی انتخاب شما تعیین می‌شود.</p>
      <div class="cat-confirm" id="catConfirm"></div>
      <details class="adv" id="catEditDetails">
        <summary id="catEditSummary">دسته درست نیست؟ دستی انتخاب کنید</summary>
        <div class="cat-grid" id="catGrid" style="margin-top:12px;"></div>
      </details>
      <div class="disclaimer-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
        <span>این یک برآورد اولیه است. برای تعیین قطعی دسته خودرو و مجاز بودن آن، با کارشناسان ناوراکار تماس بگیرید.</span>
      </div>
    </div>
  </div>

  <!-- STEP: pricing -->
  <div class="wiz-step" data-step="pricing">
    <div class="wiz-step-title">قیمت و نرخ ارز</div>
    <div class="wiz-step-sub">مقادیر پیش‌فرض قابل ویرایش هستند</div>
    <div class="card">
      <h2><span class="num"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="6" width="19" height="12" rx="2.5"/><circle cx="12" cy="12" r="2.6"/><path d="M6 9v.01M18 15v.01"/></svg></span> اطلاعات قیمت و ارز</h2>
      <div class="field-grid">
        <div class="field"><label><svg class="flbl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13"/><rect x="2.5" y="13" width="19" height="5" rx="1.5"/></svg>قیمت واقعی خودرو</label><div class="input-wrap"><input type="text" inputmode="decimal" id="realPriceAED" value="400,000"><button type="button" class="unit unit-toggle" id="realPriceAEDUnit" onclick="togglePriceCurrency('realPriceAED')">درهم</button></div></div>
        <div class="field"><label><svg class="flbl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 21V10l8-6 8 6v11"/><path d="M4 10h16"/></svg>قیمت گمرکی خودرو</label><div class="input-wrap"><input type="text" inputmode="decimal" id="customsPriceAED" value="400,000"><button type="button" class="unit unit-toggle" id="customsPriceAEDUnit" onclick="togglePriceCurrency('customsPriceAED')">درهم</button></div></div>
        <div class="field"><label><svg class="flbl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 2l4 4-4 4M3 12v-1a4 4 0 0 1 4-4h14M7 22l-4-4 4-4M21 12v1a4 4 0 0 1-4 4H3"/></svg>نرخ ارز آزاد</label><div class="input-wrap"><input type="text" inputmode="decimal" id="freeRate" value="51,000"><span class="unit">تومان</span></div></div>
        <div class="field"><label><svg class="flbl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 2l4 4-4 4M3 12v-1a4 4 0 0 1 4-4h14M7 22l-4-4 4-4M21 12v1a4 4 0 0 1-4 4H3"/></svg>نرخ ارز گمرک</label><div class="input-wrap"><input type="text" inputmode="decimal" id="customsRate" value="35,688"><span class="unit">تومان</span></div></div>
        <div class="field"><label><svg class="flbl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 20c2 1 4 1 6 0s4-1 6 0 4 1 6 0M5 20l1-9 4-6h4l4 6 1 9"/></svg>حمل دریایی</label><div class="input-wrap"><input type="text" inputmode="decimal" id="seaFreightAED" value="1,500"><span class="unit">درهم</span></div></div>
        <div class="field"><label><svg class="flbl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/></svg>صدور مجوزها</label><div class="input-wrap"><input type="text" inputmode="decimal" id="permitsAED" value="60,000"><span class="unit">درهم</span></div></div>
        <div class="field" style="grid-column:1/-1;"><label><svg class="flbl" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-6h6v6"/></svg>انبارداری، دموراژ و THC <span class="hint">(متغیر)</span></label><div class="input-wrap"><input type="text" inputmode="decimal" id="storage" value="0"><span class="unit">تومان</span></div></div>
      </div>
      <details class="adv">
        <summary>تنظیمات پیشرفته نرخ‌ها و عوارض</summary>
        <div class="rate-grid">
          <div class="rate-row"><span>حقوق گمرکی ثابت</span><input type="number" step="0.1" id="r_customsFixed" value="4"></div>
          <div class="rate-row"><span>عوارض بنزین‌سوز</span><input type="number" step="0.1" id="r_gasoline" value="10"></div>
          <div class="rate-row"><span>عوارض ۵٪ فوب</span><input type="number" step="0.1" id="r_fob" value="5"></div>
          <div class="rate-row"><span>مالیات ارزش افزوده</span><input type="number" step="0.1" id="r_vat" value="10"></div>
          <div class="rate-row"><span>مالیات علی‌الحساب</span><input type="number" step="0.1" id="r_advanceTax" value="2"></div>
          <div class="rate-row"><span>عوارض هلال احمر</span><input type="number" step="0.1" id="r_redCrescent" value="1"></div>
          <div class="rate-row"><span>حق نظارت کارشناسان</span><input type="number" step="0.05" id="r_supervision" value="0.5"></div>
          <div class="rate-row"><span>عوارض پسماند کالا</span><input type="number" step="0.01" id="r_waste" value="0.05"></div>
          <div class="rate-row"><span>هزینه استاندارد</span><input type="number" step="0.1" id="r_standard" value="0.8"></div>
          <div class="rate-row"><span>گواهی اسقاط</span><input type="number" step="0.1" id="r_scrapCert" value="1.5"></div>
          <div class="rate-row"><span>عوارض شماره‌گذاری</span><input type="number" step="0.1" id="r_plateReg" value="10"></div>
          <div class="rate-row"><span>مالیات نقل و انتقال</span><input type="number" step="0.1" id="r_transferTax" value="3"></div>
          <div class="rate-row"><span>عوارض سالانه شهرداری</span><input type="number" step="0.1" id="r_municipal" value="1"></div>
          <div class="rate-row"><span>عوارض شخص حقیقی</span><input type="number" step="0.1" id="r_individual" value="5"></div>
          <div class="rate-row"><span>کارمزد ترخیص‌کار و کارگزار (ناوراکار)</span><input type="number" step="0.1" id="r_serviceProfit" value="10"></div>
        </div>
      </details>
    </div>
  </div>

  <!-- STEP: result -->
  <div class="wiz-step" data-step="result">
    <div class="wiz-step-title">نتیجه محاسبه</div>
    <div class="wiz-step-sub">برآورد اولیه هزینه واردات خودروی شما</div>

    <div class="dash-panel" id="summaryCard">
      <div class="dash-top">
        <div class="dash-title"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l2.4 1.7 2.9-.4 1.1 2.7 2.7 1.1-.4 2.9L22.4 12l-1.7 2.4.4 2.9-2.7 1.1-1.1 2.7-2.9-.4L12 22l-2.4-1.7-2.9.4-1.1-2.7-2.7-1.1.4-2.9L1.6 12l1.7-2.4-.4-2.9 2.7-1.1L6.7 3.3l2.9.4Z"/></svg>جمع کل هزینه واردات</div>
        <div class="dash-live">آنی</div>
      </div>
      <div class="dash-display">
        <div class="dash-num num-font" id="stampVal">0</div>
        <div class="dash-unit">تومان — جمع کل با سود</div>
        <div class="dash-sub">این عدد یک برآورد اولیه است — برای تعیین قطعی با کارشناسان تماس بگیرید.</div>
      </div>
      <div class="dash-mini-grid" id="miniGaugeGrid"></div>
      <div class="dash-lines">
        <div class="dash-line"><span class="dl-lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h10"/></svg>جمع کل بدون کارمزد</span><span class="amt num-font" id="s_noProfit">۰</span></div>
        <div class="dash-line"><span class="dl-lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/></svg>کارمزد ترخیص‌کار و کارگزار (ناوراکار)</span><span class="amt num-font" id="s_profit">۰</span></div>
        <div class="dash-line total"><span class="dl-lbl"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 12.5l2 2 5-5"/><circle cx="12" cy="12" r="9"/></svg>جمع کل نهایی</span><span class="amt num-font" id="s_total">۰</span></div>
      </div>
    </div>

    <div class="card" style="margin-top:18px;">
      <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V10l8-6 8 6v11"/><path d="M9 21v-6h6v6"/><path d="M4 10h16"/></svg> تفکیک هزینه‌های ترخیص گمرکی</h2>
      <div class="table-wrap"><table class="cost-table"><thead><tr><th>ردیف</th><th>شرح هزینه</th><th>نرخ</th><th>مبلغ</th></tr></thead><tbody id="tblCustoms"></tbody><tfoot><tr><td colspan="3">جمع هزینه‌های ترخیص گمرکی</td><td class="amt num-font" id="sumCustomsCell"></td></tr></tfoot></table></div>
    </div>

    <div class="card">
      <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 15h4M15 10h2M15 14h2"/></svg> تفکیک هزینه‌های پلاک انتظامی</h2>
      <div class="table-wrap"><table class="cost-table"><thead><tr><th>ردیف</th><th>شرح هزینه</th><th>نرخ</th><th>مبلغ</th></tr></thead><tbody id="tblPlate"></tbody><tfoot><tr><td colspan="3">جمع هزینه‌های پلاک انتظامی</td><td class="amt num-font" id="sumPlateCell"></td></tr></tfoot></table></div>
    </div>

    <div class="card">
      <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 1 0 9 9h-9V3Z"/><path d="M21 12A9 9 0 0 0 12 3v9h9Z" opacity=".45"/></svg> نمودار تفکیک هزینه‌ها</h2>
      <div class="chart-grid">
        <div class="chart-box"><h3>سهم هر بخش از هزینه کل</h3><div class="donut-wrap" id="donutWrap"></div></div>
        <div class="chart-box"><h3>مقایسه گروه‌های اصلی هزینه</h3><div class="bar-rows" id="barRows"></div></div>
      </div>
    </div>
  </div>

  <!-- STEP: final -->
  <div class="wiz-step" data-step="final">
    <div class="wiz-step-title">درخواست نهایی</div>
    <div class="wiz-step-sub">یکی از دو روش زیر را برای دریافت استعلام دقیق انتخاب کنید</div>

    <div class="card">
      <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7Z"/></svg> درخواست استعلام دقیق (فرم آنلاین)</h2>
      <p class="qf-sub">این گزارش یک برآورد اولیه است. با ارسال، اطلاعات تماس شما برای تعیین قیمت قطعی به کارشناسان ناوراکار می‌رسد.</p>
      <div class="quote-form">
        <input type="text" id="qWebsite" name="website" autocomplete="off" tabindex="-1" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;" aria-hidden="true">
        <div class="qf-grid">
          <input id="qName" placeholder="نام و نام خانوادگی">
          <input id="qPhone" placeholder="شماره تماس" inputmode="tel">
          <input id="qEmail" placeholder="ایمیل شما (اختیاری)" type="email">
          <textarea id="qNotes" placeholder="توضیحات (اختیاری)" rows="2"></textarea>
        </div>
        <button type="button" id="qSubmitBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4 20-7Z"/></svg>
          ارسال درخواست و دریافت گزارش
        </button>
        <div id="qStatus" class="qf-status"></div>
      </div>
    </div>

    <div class="card contact-card">
      <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg> ارتباط مستقیم با کارشناسان</h2>
      <p class="sub" style="color:#B7E8CE;">همین حالا با کارشناسان ناوراکار تماس بگیرید — مشخصاتی که بالا وارد کردید هم به‌همراه پیام ارسال می‌شود.</p>
      <div class="contact-rows">
        <div class="contact-row">
          <span class="cr-flag">🇮🇷</span>
          <div class="cr-info"><div class="cr-num">{{ $contactIran }}</div><div class="cr-label">واتساپ · بله · تلگرام</div></div>
          <div class="cr-actions">
            <a href="tel:{{ $contactIranTel }}" class="cr-btn call" title="تماس"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></a>
            <a href="#" target="_blank" id="waIranFinal" class="cr-btn whatsapp" title="واتساپ"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.27-1.38a9.9 9.9 0 0 0 4.72 1.2h.01c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2zm5.8 14.06c-.24.68-1.4 1.3-1.93 1.38-.5.08-1.12.11-1.8-.11-.42-.13-.96-.31-1.65-.6-2.9-1.25-4.8-4.17-4.94-4.36-.14-.19-1.19-1.58-1.19-3.02 0-1.43.75-2.14 1.02-2.43.27-.29.58-.36.78-.36h.55c.18 0 .42-.07.65.5.24.58.82 2.02.9 2.16.07.15.12.32.02.51-.1.19-.15.31-.29.47-.15.17-.31.37-.44.5-.15.15-.3.31-.13.61.17.29.76 1.25 1.63 2.02 1.12.99 2.06 1.3 2.36 1.44.29.15.46.13.63-.08.17-.2.71-.83.9-1.11.19-.29.38-.24.63-.15.26.1 1.65.78 1.93.92.29.15.48.22.55.34.07.13.07.72-.17 1.39z"/></svg></a>
            <a href="https://t.me/{{ $contactIranTel }}" target="_blank" class="cr-btn telegram" title="تلگرام"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.5 2.5 2.7 10.1c-1 .4-1 1.8.1 2.1l4.8 1.5 1.8 5.6c.3 1 1.6 1.2 2.2.3l2.5-3.6 4.7 3.5c.9.6 2.1.1 2.3-1L23 3.6c.2-1-.7-1.8-1.5-1.1z"/></svg></a>
          </div>
        </div>
        <div class="contact-row">
          <span class="cr-flag">🇦🇪</span>
          <div class="cr-info"><div class="cr-num">{{ $contactUae }}</div><div class="cr-label">واتساپ · تلگرام</div></div>
          <div class="cr-actions">
            <a href="tel:{{ $contactUaeTel }}" class="cr-btn call" title="تماس"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></a>
            <a href="#" target="_blank" id="whatsappBtnFinal" class="cr-btn whatsapp" title="واتساپ"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.27-1.38a9.9 9.9 0 0 0 4.72 1.2h.01c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2zm5.8 14.06c-.24.68-1.4 1.3-1.93 1.38-.5.08-1.12.11-1.8-.11-.42-.13-.96-.31-1.65-.6-2.9-1.25-4.8-4.17-4.94-4.36-.14-.19-1.19-1.58-1.19-3.02 0-1.43.75-2.14 1.02-2.43.27-.29.58-.36.78-.36h.55c.18 0 .42-.07.65.5.24.58.82 2.02.9 2.16.07.15.12.32.02.51-.1.19-.15.31-.29.47-.15.17-.31.37-.44.5-.15.15-.3.31-.13.61.17.29.76 1.25 1.63 2.02 1.12.99 2.06 1.3 2.36 1.44.29.15.46.13.63-.08.17-.2.71-.83.9-1.11.19-.29.38-.24.63-.15.26.1 1.65.78 1.93.92.29.15.48.22.55.34.07.13.07.72-.17 1.39z"/></svg></a>
            <a href="https://t.me/{{ $contactUaeTel }}" target="_blank" class="cr-btn telegram" title="تلگرام"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.5 2.5 2.7 10.1c-1 .4-1 1.8.1 2.1l4.8 1.5 1.8 5.6c.3 1 1.6 1.2 2.2.3l2.5-3.6 4.7 3.5c.9.6 2.1.1 2.3-1L23 3.6c.2-1-.7-1.8-1.5-1.1z"/></svg></a>
          </div>
        </div>
        <div class="contact-row">
          <span class="cr-flag">☎️</span>
          <div class="cr-info"><div class="cr-num">{{ $contactTehran }}</div><div class="cr-label">دفتر تهران</div></div>
          <div class="cr-actions">
            <a href="tel:{{ $contactTehranTel }}" class="cr-btn call" title="تماس با دفتر"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></a>
          </div>
        </div>
      </div>
      <div class="contact-note">این محاسبه یک برآورد اولیه است. برای تعیین قطعی قیمت و بررسی دقیق مجاز بودن خودرو، حتماً با کارشناسان ناوراکار صحبت کنید.</div>
    </div>

    <button type="button" class="reset-link" onclick="resetWizard()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
      محاسبه جدید
    </button>
  </div>

  <!-- STEP: contact (terminal branch) -->
  <div class="wiz-step" data-step="contact">
    <div class="wiz-step-title">ارتباط مستقیم با کارشناسان</div>
    <div class="wiz-step-sub">برای دریافت مشاوره و استعلام قیمت، مستقیم در تماس باشید</div>
    <div class="card contact-card">
      <h2><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg> کارشناسان ناوراکار</h2>
      <p class="sub" style="color:#B7E8CE;">می‌توانید مستقیم تماس بگیرید یا پیام بفرستید.</p>
      <div class="contact-rows">
        <div class="contact-row">
          <span class="cr-flag">🇮🇷</span>
          <div class="cr-info"><div class="cr-num">{{ $contactIran }}</div><div class="cr-label">واتساپ · بله · تلگرام</div></div>
          <div class="cr-actions">
            <a href="tel:{{ $contactIranTel }}" class="cr-btn call" title="تماس"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></a>
            <a href="#" target="_blank" id="waIranContact" class="cr-btn whatsapp" title="واتساپ"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.27-1.38a9.9 9.9 0 0 0 4.72 1.2h.01c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2zm5.8 14.06c-.24.68-1.4 1.3-1.93 1.38-.5.08-1.12.11-1.8-.11-.42-.13-.96-.31-1.65-.6-2.9-1.25-4.8-4.17-4.94-4.36-.14-.19-1.19-1.58-1.19-3.02 0-1.43.75-2.14 1.02-2.43.27-.29.58-.36.78-.36h.55c.18 0 .42-.07.65.5.24.58.82 2.02.9 2.16.07.15.12.32.02.51-.1.19-.15.31-.29.47-.15.17-.31.37-.44.5-.15.15-.3.31-.13.61.17.29.76 1.25 1.63 2.02 1.12.99 2.06 1.3 2.36 1.44.29.15.46.13.63-.08.17-.2.71-.83.9-1.11.19-.29.38-.24.63-.15.26.1 1.65.78 1.93.92.29.15.48.22.55.34.07.13.07.72-.17 1.39z"/></svg></a>
            <a href="https://t.me/{{ $contactIranTel }}" target="_blank" class="cr-btn telegram" title="تلگرام"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.5 2.5 2.7 10.1c-1 .4-1 1.8.1 2.1l4.8 1.5 1.8 5.6c.3 1 1.6 1.2 2.2.3l2.5-3.6 4.7 3.5c.9.6 2.1.1 2.3-1L23 3.6c.2-1-.7-1.8-1.5-1.1z"/></svg></a>
          </div>
        </div>
        <div class="contact-row">
          <span class="cr-flag">🇦🇪</span>
          <div class="cr-info"><div class="cr-num">{{ $contactUae }}</div><div class="cr-label">واتساپ · تلگرام</div></div>
          <div class="cr-actions">
            <a href="tel:{{ $contactUaeTel }}" class="cr-btn call" title="تماس"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></a>
            <a href="#" target="_blank" id="whatsappBtnContact" class="cr-btn whatsapp" title="واتساپ"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.27-1.38a9.9 9.9 0 0 0 4.72 1.2h.01c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2zm5.8 14.06c-.24.68-1.4 1.3-1.93 1.38-.5.08-1.12.11-1.8-.11-.42-.13-.96-.31-1.65-.6-2.9-1.25-4.8-4.17-4.94-4.36-.14-.19-1.19-1.58-1.19-3.02 0-1.43.75-2.14 1.02-2.43.27-.29.58-.36.78-.36h.55c.18 0 .42-.07.65.5.24.58.82 2.02.9 2.16.07.15.12.32.02.51-.1.19-.15.31-.29.47-.15.17-.31.37-.44.5-.15.15-.3.31-.13.61.17.29.76 1.25 1.63 2.02 1.12.99 2.06 1.3 2.36 1.44.29.15.46.13.63-.08.17-.2.71-.83.9-1.11.19-.29.38-.24.63-.15.26.1 1.65.78 1.93.92.29.15.48.22.55.34.07.13.07.72-.17 1.39z"/></svg></a>
            <a href="https://t.me/{{ $contactUaeTel }}" target="_blank" class="cr-btn telegram" title="تلگرام"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.5 2.5 2.7 10.1c-1 .4-1 1.8.1 2.1l4.8 1.5 1.8 5.6c.3 1 1.6 1.2 2.2.3l2.5-3.6 4.7 3.5c.9.6 2.1.1 2.3-1L23 3.6c.2-1-.7-1.8-1.5-1.1z"/></svg></a>
          </div>
        </div>
        <div class="contact-row">
          <span class="cr-flag">☎️</span>
          <div class="cr-info"><div class="cr-num">{{ $contactTehran }}</div><div class="cr-label">دفتر تهران</div></div>
          <div class="cr-actions">
            <a href="tel:{{ $contactTehranTel }}" class="cr-btn call" title="تماس با دفتر"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></a>
          </div>
        </div>
      </div>
    </div>
    <button type="button" class="reset-link" onclick="resetWizard()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
      بازگشت به شروع
    </button>
  </div>
</div>

<div class="wiz-nav" id="wizNav">
  <button type="button" class="wiz-nav-btn wiz-prev" id="wizPrevBtn">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
    قبلی
  </button>
  <button type="button" class="wiz-nav-btn wiz-next" id="wizNextBtn">
    بعدی
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
  </button>
</div>

<div id="printSheet" class="print-only">
  <div class="ps-header">
    <div class="ps-brand">ناوراکار<span>گزارش برآورد هزینه واردات خودرو</span></div>
    <div class="ps-meta" id="psMeta"></div>
  </div>
  <div class="ps-body">
    <div class="ps-car-box" id="psCarBox"></div>
    <h3 class="ps-h3">تفکیک هزینه‌های ترخیص گمرکی</h3>
    <table class="ps-table" id="psCustomsTable"></table>
    <h3 class="ps-h3">تفکیک هزینه‌های پلاک انتظامی</h3>
    <table class="ps-table" id="psPlateTable"></table>
    <h3 class="ps-h3">جمع‌بندی</h3>
    <table class="ps-table" id="psTotalsTable"></table>
    <div class="ps-disclaimer">
      این گزارش صرفاً یک <b>برآورد اولیه</b> بر اساس نرخ‌های ثبت‌شده در سیستم ناوراکار در تاریخ صدور است و ممکن است با تغییر مقررات گمرکی یا نرخ ارز به‌روزرسانی شود.
      برای تعیین قطعی هزینه و بررسی مجاز بودن دقیق خودرو، حتماً پیش از تصمیم نهایی با کارشناسان ناوراکار تماس بگیرید.
      این طرح برای ایرانیان دارای مجوز اقامت خارج از کشور امکان صدور دارد؛ برای سایر متقاضیان، شرکت با بررسی شرایط می‌تواند نسبت به اخذ مجوز اقدام کند.
    </div>
    <div class="ps-contact">
      <div class="ps-contact-title">📞 ارتباط با ما</div>
      🇮🇷 {{ $contactIran }} (واتس‌اپ | بله | تلگرام)<br>
      🇦🇪 {{ $contactUae }} (واتس‌اپ | تلگرام)<br>
      ☎️ {{ $contactTehran }} (دفتر تهران)<br>
      🌐 navracar.com
    </div>
    <div class="ps-sign"><div>مهر و امضای ناوراکار</div><div>navracar.com</div></div>
  </div>
</div>

<footer>
  <div class="wrap">
    <p><b>این محاسبه‌گر صرفاً یک برآورد اولیه است</b> و بر اساس آخرین مقررات و نرخ‌های ثبت‌شده در سیستم <span class="brand2">ناوراکار</span> کار می‌کند؛ ممکن است با تغییر بخشنامه‌های گمرکی به‌روزرسانی شود. لیست خودروهای قابل انتخاب نیز محدود به خودروهای مجاز طرح است. برای تعیین قطعی قیمت و بررسی مجاز بودن دقیق خودرو، همیشه پیش از تصمیم نهایی از کارشناسان ناوراکار استعلام بگیرید.</p>
  </div>
</footer>
<script>
const CSRF_TOKEN = '{{ csrf_token() }}';
const CALC_LOG_URL = '{{ route('public.calculation-logs.store') }}';
const VIN_LOG_URL = '{{ route('public.vin-checks.store') }}';
const QUOTE_URL = '{{ route('public.quote-requests.store') }}';
const CAR_LISTINGS = @js($carListings);
const CONTACT_IRAN = @js($contactIran);
const CONTACT_UAE = @js($contactUae);
const CONTACT_TEHRAN = @js($contactTehran);
const USD_TO_AED_RATE = @js($usdToAedRate);
const priceCurrencyState = { realPriceAED: 'aed', customsPriceAED: 'aed' };
function getPriceAED(id){
  const raw = num(id);
  return priceCurrencyState[id] === 'usd' ? raw * USD_TO_AED_RATE : raw;
}
function togglePriceCurrency(id){
  const input = document.getElementById(id);
  const btn = document.getElementById(id + 'Unit');
  const current = num(id);
  if(priceCurrencyState[id] === 'aed'){
    priceCurrencyState[id] = 'usd';
    input.value = (Math.round((current / USD_TO_AED_RATE) * 100) / 100).toLocaleString('en-US');
    btn.textContent = 'دلار';
  } else {
    priceCurrencyState[id] = 'aed';
    input.value = Math.round(current * USD_TO_AED_RATE).toLocaleString('en-US');
    btn.textContent = 'درهم';
  }
  calc();
}
const waDigits = n => n.replace(/[\s+]/g, '');
window.__pageLoadedAt = Date.now();
const gaugeIcon = tip => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 15a8 8 0 1 1 16 0"/><path d="M12 15L${tip}"/><circle cx="12" cy="15" r="1.3" fill="currentColor" stroke="none"/><path d="M4 15h1.4M18.6 15H20" stroke-width="1.4" opacity=".6"/></svg>`;
const leafIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 19c8 0 14-6 14-14-8 0-14 6-14 14Z"/><path d="M5 19c2-4 5-7 9-9"/><path d="M15 4.2v3M20.2 9h-3"/></svg>`;

const categories = [
  {id:'ev',   label:'هیبرید / برقی', coef:1.00, icon:leafIcon},
  {id:'c1500',label:'زیر ۱۵۰۰ سی‌سی', coef:1.10, icon:gaugeIcon('6.4,11.7')},
  {id:'c2000',label:'۱۵۰۰ تا ۲۰۰۰ سی‌سی', coef:1.20, icon:gaugeIcon('8.7,9.4')},
  {id:'c2500',label:'۲۰۰۰ تا ۲۵۰۰ سی‌سی', coef:1.30, icon:gaugeIcon('12,8.5')},
  {id:'c3000',label:'۲۵۰۰ تا ۳۰۰۰ سی‌سی', coef:1.45, icon:gaugeIcon('15.3,9.4')},
  {id:'c3001',label:'بالای ۳۰۰۰ سی‌سی', coef:1.65, icon:gaugeIcon('17.6,11.7')},
];
let activeCat = categories[1];
const catButtons = {};

const catGrid = document.getElementById('catGrid');
categories.forEach(cat=>{
  const btn = document.createElement('button');
  btn.className = 'cat-btn' + (cat.id===activeCat.id ? ' active':'');
  btn.type = 'button';
  btn.innerHTML = `<div class="icon">${cat.icon}</div><div class="lbl">${cat.label}</div><div class="coef">ضریب ${cat.coef.toFixed(2)}</div>`;
  btn.addEventListener('click', ()=>{ selectCategoryById(cat.id); });
  catGrid.appendChild(btn);
  catButtons[cat.id] = btn;
});
renderCatConfirm();

function selectCategoryById(id){
  const cat = categories.find(c=>c.id===id);
  if(!cat) return;
  activeCat = cat;
  document.querySelectorAll('.cat-btn').forEach(b=>b.classList.remove('active'));
  catButtons[id].classList.add('active');
  renderCatConfirm();
  calc();
}

function renderCatConfirm(){
  const el = document.getElementById('catConfirm');
  if(!el) return;
  el.innerHTML = `
    <div class="cc-icon">${activeCat.icon}</div>
    <div>
      <div class="cc-label">${activeCat.label}</div>
      <div class="cc-coef">ضریب سود بازرگانی: ${activeCat.coef.toFixed(2)}</div>
    </div>
  `;
}

/* ---- Vehicle database: STRICTLY limited to the brands/models on the official
   NAVRA Limousine approved-import list (license 1559276, 1404) — plus Jaguar and
   Land Rover, per explicit instruction. No other brand is offered anywhere in this tool.
   A few brand sections in the source list had duplicated/mismatched model names
   (Tank, Maextro, ORA all showed another brand's model list) — for those three,
   the real known lineup of that brand was used instead so the tool stays useful;
   everything else is transcribed exactly as licensed. cc/fuel type are engineering
   estimates for category detection only — always confirm the exact trim with an expert. */
const carDB = [
["Mercedes-Benz","A Class","1.3L Turbo",1300,"petrol"],["Mercedes-Benz","B Class","1.3L Turbo",1300,"petrol"],
["Mercedes-Benz","C Class","2.0L Turbo",2000,"petrol"],["Mercedes-Benz","E Class","2.0L Turbo",2000,"petrol"],
["Mercedes-Benz","S Class","3.0L Turbo I6",3000,"petrol"],["Mercedes-Benz","CLA","2.0L Turbo",2000,"petrol"],
["Mercedes-Benz","GLA","2.0L Turbo",2000,"petrol"],["Mercedes-Benz","GLB","2.0L Turbo",2000,"petrol"],
["Mercedes-Benz","GLC","2.0L Turbo",2000,"petrol"],["Mercedes-Benz","GLE","3.0L Turbo I6",3000,"petrol"],
["Mercedes-Benz","GLS","3.0L Turbo I6",3000,"petrol"],["Mercedes-Benz","EQE","EV",0,"electric"],
["Mercedes-Benz","EQE SUV","EV",0,"electric"],["Mercedes-Benz","EQS","EV",0,"electric"],
["Mercedes-Benz","EQS SUV","EV",0,"electric"],["Mercedes-Benz","EQA","EV",0,"electric"],

["BMW","Series 1","2.0L Turbo",2000,"petrol"],["BMW","Series 2","2.0L Turbo",2000,"petrol"],
["BMW","Series 3","2.0L Turbo",2000,"petrol"],["BMW","Series 4","2.0L Turbo",2000,"petrol"],
["BMW","Series 5","2.0L Turbo",2000,"petrol"],["BMW","Series 7","3.0L Turbo I6",3000,"petrol"],
["BMW","Series 8","3.0L Turbo I6",3000,"petrol"],["BMW","iX1","EV",0,"electric"],["BMW","iX2","EV",0,"electric"],
["BMW","iX3","EV",0,"electric"],["BMW","X1","2.0L Turbo",2000,"petrol"],["BMW","X2","2.0L Turbo",2000,"petrol"],
["BMW","X3","2.0L Turbo",2000,"petrol"],["BMW","X4","2.0L Turbo",2000,"petrol"],["BMW","X5","3.0L Turbo I6",3000,"petrol"],
["BMW","X6","3.0L Turbo I6",3000,"petrol"],["BMW","X7","3.0L Turbo I6",3000,"petrol"],["BMW","iX","EV",0,"electric"],
["BMW","z4","2.0L Turbo",2000,"petrol"],["BMW","i3","EV",0,"electric"],["BMW","i4","EV",0,"electric"],["BMW","i5","EV",0,"electric"],

["Acura","Integra","1.5L Turbo",1500,"petrol"],

["Volkswagen","Golf","1.4L Turbo",1400,"petrol"],["Volkswagen","Jetta","1.4L Turbo",1400,"petrol"],
["Volkswagen","Arteon","2.0L Turbo",2000,"petrol"],["Volkswagen","Passat","1.4L Turbo",1400,"petrol"],
["Volkswagen","Atlas/Teramont","2.0L Turbo",2000,"petrol"],["Volkswagen","Tiguan","2.0L Turbo",2000,"petrol"],
["Volkswagen","ID.3","EV",0,"electric"],["Volkswagen","ID.4","EV",0,"electric"],["Volkswagen","ID.5","EV",0,"electric"],["Volkswagen","ID.UNYX","EV",0,"electric"],

["Audi","A3","1.4L Turbo",1400,"petrol"],["Audi","A5","2.0L Turbo",2000,"petrol"],["Audi","A7","3.0L Turbo V6",3000,"petrol"],
["Audi","A8","3.0L Turbo V6",3000,"petrol"],["Audi","A6 e-tron","EV",0,"electric"],["Audi","e-tron GT","EV",0,"electric"],
["Audi","Q3","2.0L Turbo",2000,"petrol"],["Audi","Q4 e-tron","EV",0,"electric"],["Audi","Q5","2.0L Turbo",2000,"petrol"],
["Audi","Q7","3.0L Turbo V6",3000,"petrol"],["Audi","Q8","3.0L Turbo V6",3000,"petrol"],["Audi","Q8 e-Tron","EV",0,"electric"],["Audi","Q5 e-tron","EV",0,"electric"],

["Toyota","Avalon","3.5L V6",3500,"petrol"],["Toyota","bZ3","EV",0,"electric"],["Toyota","bZ2X","EV",0,"electric"],
["Toyota","bZ4X","EV",0,"electric"],["Toyota","Camry","2.5L",2500,"petrol"],["Toyota","C-HR","2.0L",2000,"petrol"],
["Toyota","Corolla","1.8L",1800,"petrol"],["Toyota","Crown","2.5L Hybrid",2500,"hybrid"],["Toyota","Highlander","2.5L Hybrid",2500,"hybrid"],
["Toyota","Mirai","Hydrogen Fuel Cell",0,"electric"],["Toyota","Prius","1.8L Hybrid",1800,"hybrid"],["Toyota","RAV4","2.5L",2500,"petrol"],
["Toyota","Sequoia","3.5L Hybrid",3500,"hybrid"],["Toyota","Sienna","2.5L Hybrid",2500,"hybrid"],["Toyota","Supra","3.0L Turbo",3000,"petrol"],
["Toyota","Tundra","3.5L Twin-Turbo",3500,"petrol"],["Toyota","Yaris","1.5L",1500,"petrol"],
["Toyota","Land Cruiser Prado/250","2.7L",2700,"petrol"],["Toyota","Land Cruiser 70","4.0L",4000,"petrol"],
["Toyota","Land Cruiser 300","3.5L Twin-Turbo V6",3500,"petrol"],["Toyota","Levin","1.2L Turbo",1200,"petrol"],
["Toyota","bZ3X","EV",0,"electric"],["Toyota","bZ5","EV",0,"electric"],["Toyota","Corolla Cross","2.0L",2000,"petrol"],
["Toyota","Venza","2.5L Hybrid",2500,"hybrid"],["Toyota","Wildlander","2.0L",2000,"petrol"],["Toyota","GR86","2.4L",2400,"petrol"],

["Kia","Ceed","1.4L Turbo",1400,"petrol"],["Kia","K3","2.0L",2000,"petrol"],["Kia","K4","2.0L",2000,"petrol"],
["Kia","K5","1.6L Turbo",1600,"petrol"],["Kia","K8","3.5L V6",3500,"petrol"],["Kia","K9","3.8L V6",3800,"petrol"],
["Kia","EV6","EV",0,"electric"],["Kia","EV9","EV",0,"electric"],["Kia","Seltos","2.0L",2000,"petrol"],
["Kia","Sorento","2.5L",2500,"petrol"],["Kia","telluride","3.8L V6",3800,"petrol"],["Kia","Tasman","2.2L Diesel",2200,"petrol"],["Kia","Carnival","3.5L V6",3500,"petrol"],

["Honda","Civic","1.5L Turbo",1500,"petrol"],["Honda","CR-V","1.5L Turbo",1500,"petrol"],["Honda","HR-V","1.5L Turbo",1500,"petrol"],
["Honda","Passport","3.5L V6",3500,"petrol"],["Honda","Odyssey","3.5L V6",3500,"petrol"],["Honda","Accord","1.5L Turbo",1500,"petrol"],
["Honda","S7","EV",0,"electric"],["Honda","P7","EV",0,"electric"],["Honda","Civic Type R","2.0L Turbo",2000,"petrol"],

["Peugeot","508","1.6L Turbo",1600,"petrol"],["Peugeot","408","1.6L Turbo",1600,"petrol"],["Peugeot","308","1.2L Turbo",1200,"petrol"],
["Peugeot","2008","1.2L Turbo",1200,"petrol"],["Peugeot","208","1.2L Turbo",1200,"petrol"],

["Nissan","Altima","2.5L",2500,"petrol"],["Nissan","N7","EV",0,"electric"],["Nissan","Ariya","EV",0,"electric"],
["Nissan","Sentra","2.0L",2000,"petrol"],["Nissan","Juke","1.0L Turbo",1000,"petrol"],["Nissan","Kicks","1.6L",1600,"petrol"],
["Nissan","Leaf","EV",0,"electric"],["Nissan","Navara","2.3L Diesel",2300,"petrol"],["Nissan","Note","1.2L",1200,"petrol"],
["Nissan","Pathfinder","3.5L V6",3500,"petrol"],["Nissan","Patrol","5.6L V8",5600,"petrol"],["Nissan","sylphy","1.6L",1600,"petrol"],
["Nissan","Armada","5.6L V8",5600,"petrol"],["Nissan","Qashqai","1.3L Turbo",1300,"petrol"],["Nissan","Rogue","1.5L Turbo",1500,"petrol"],
["Nissan","X-trail","1.5L Turbo",1500,"petrol"],["Nissan","Terra","2.3L Diesel",2300,"petrol"],["Nissan","Z","3.0L Twin-Turbo",3000,"petrol"],
["Nissan","Sunny","1.5L",1500,"petrol"],["Nissan","Tilda","1.6L",1600,"petrol"],

["Hyundai","Accent","1.6L",1600,"petrol"],["Hyundai","Elantra","2.0L",2000,"petrol"],["Hyundai","Ioniq 5","EV",0,"electric"],
["Hyundai","Kona","2.0L",2000,"petrol"],["Hyundai","Palisade","3.8L V6",3800,"petrol"],["Hyundai","Santa Fe","2.5L",2500,"petrol"],
["Hyundai","Sonata","2.5L",2500,"petrol"],["Hyundai","Staria","2.2L Diesel",2200,"petrol"],["Hyundai","Tucson","2.5L",2500,"petrol"],
["Hyundai","Santa Cruz","2.5L",2500,"petrol"],["Hyundai","Casper","1.0L Turbo",1000,"petrol"],["Hyundai","Venue","1.6L",1600,"petrol"],["Hyundai","Bayon","1.0L Turbo",1000,"petrol"],

["Citroen","C3","1.2L Turbo",1200,"petrol"],["Citroen","C4","1.2L Turbo",1200,"petrol"],["Citroen","C5x","1.6L Turbo",1600,"petrol"],
["Citroen","C3 Aircross","1.2L Turbo",1200,"petrol"],["Citroen","C5 Aircross","1.6L Turbo",1600,"petrol"],

["Volvo","S60","2.0L Turbo",2000,"petrol"],["Volvo","S90","2.0L Turbo",2000,"petrol"],["Volvo","XC40","2.0L Turbo",2000,"petrol"],
["Volvo","XC60","2.0L Turbo",2000,"petrol"],["Volvo","XC90","2.0L Turbo",2000,"petrol"],["Volvo","EX30","EV",0,"electric"],
["Volvo","EX40","EV",0,"electric"],["Volvo","EX90","EV",0,"electric"],["Volvo","Es9","EV",0,"electric"],

["Cupra","Leon","1.5L Turbo",1500,"petrol"],["Cupra","Tavascan","EV",0,"electric"],

["Lexus","ES","2.5L",2500,"petrol"],["Lexus","ES","2.5L Hybrid",2500,"hybrid"],["Lexus","IS","2.0L Turbo",2000,"petrol"],["Lexus","LC","3.5L V6",3500,"petrol"],["Lexus","LC","3.5L V6 Hybrid",3500,"hybrid"],
["Lexus","LX","3.5L Twin-Turbo V6",3500,"petrol"],["Lexus","LX","LX700h Hybrid",3400,"hybrid"],["Lexus","LS","3.5L Twin-Turbo V6",3500,"petrol"],["Lexus","LS","3.5L Twin-Turbo V6 Hybrid",3500,"hybrid"],
["Lexus","NX","2.5L Hybrid",2500,"hybrid"],["Lexus","NX","2.4L Turbo",2400,"petrol"],["Lexus","RC","2.0L Turbo",2000,"petrol"],["Lexus","RZ","EV",0,"electric"],
["Lexus","GX","2.4L Turbo",2400,"petrol"],["Lexus","UX","2.0L",2000,"petrol"],["Lexus","UX","2.0L Hybrid",2000,"hybrid"],["Lexus","RX","2.4L Turbo",2400,"petrol"],["Lexus","RX","2.5L Hybrid",2500,"hybrid"],["Lexus","LM","2.5L Hybrid",2500,"hybrid"],

["Suzuki","Swift","1.2L",1200,"petrol"],["Suzuki","Baleno","1.2L",1200,"petrol"],["Suzuki","Jimny","1.5L",1500,"petrol"],
["Suzuki","Grand Vitara","1.5L Hybrid",1500,"hybrid"],["Suzuki","Fronx","1.2L Turbo",1200,"petrol"],

["Mazda","CX-30","2.0L",2000,"petrol"],["Mazda","CX-5","2.5L",2500,"petrol"],["Mazda","CX-70","3.3L Turbo",3300,"petrol"],
["Mazda","CX-50","2.5L",2500,"petrol"],["Mazda","EZ-6/6e","EV",0,"electric"],["Mazda","MAZDA6","2.5L",2500,"petrol"],
["Mazda","3","2.0L",2000,"petrol"],["Mazda","MX-5/Roadster","2.0L",2000,"petrol"],

["Renault","Arkana","1.3L Turbo",1300,"petrol"],["Renault","Captur","1.3L Turbo",1300,"petrol"],["Renault","Duster","1.6L",1600,"petrol"],
["Renault","Koleos","2.5L",2500,"petrol"],["Renault","Rafale","1.2L Turbo Hybrid",1200,"hybrid"],["Renault","Megane E-Tech","EV",0,"electric"],
["Renault","Clio","1.0L Turbo",1000,"petrol"],["Renault","5 E-Tech","EV",0,"electric"],["Renault","Bareal","1.3L Turbo",1300,"petrol"],["Renault","Ausral","1.3L Turbo",1300,"petrol"],

["Mitsubishi","ASX","1.0L Turbo",1000,"petrol"],["Mitsubishi","Outlander","2.5L",2500,"petrol"],["Mitsubishi","Mirage","1.2L",1200,"petrol"],
["Mitsubishi","Xforce/Outlander Sport","1.5L",1500,"petrol"],["Mitsubishi","Destinator","1.5L Turbo",1500,"petrol"],

["Skoda","Octavia","1.5L Turbo",1500,"petrol"],["Skoda","Superb","1.5L Turbo",1500,"petrol"],["Skoda","Kodiaq","1.5L Turbo",1500,"petrol"],
["Skoda","Enyaq","EV",0,"electric"],["Skoda","Kamiq","1.0L Turbo",1000,"petrol"],["Skoda","Kushaq","1.0L Turbo",1000,"petrol"],

["Subaru","BRZ","2.4L",2400,"petrol"],["Subaru","WRX","2.4L Turbo",2400,"petrol"],["Subaru","Outback","2.5L",2500,"petrol"],["Subaru","Crosstrek","2.0L",2000,"petrol"],

["Mini","Cooper","1.5L Turbo",1500,"petrol"],["Mini","Countryman","2.0L Turbo",2000,"petrol"],["Mini","Aceman","EV",0,"electric"],

["Dacia","Bigester","1.2L Turbo Hybrid",1200,"hybrid"],["Dacia","Duster","1.6L",1600,"petrol"],["Dacia","Jogger","1.0L Turbo",1000,"petrol"],
["Dacia","Logan","1.0L Turbo",1000,"petrol"],["Dacia","Lodgy","1.5L Diesel",1500,"petrol"],["Dacia","Sunder","1.0L Turbo",1000,"petrol"],["Dacia","Spring","EV",0,"electric"],

["BYD","Seal","EV",0,"electric"],["BYD","Qin L","PHEV",1500,"hybrid"],["BYD","Song Plus","PHEV",1500,"hybrid"],
["BYD","Tang","EV",0,"electric"],["BYD","Qin Plus","PHEV",1500,"hybrid"],["BYD","Shark","PHEV Pickup",1500,"hybrid"],
["BYD","Han","EV",0,"electric"],["BYD","Destroyer 05","PHEV",1500,"hybrid"],["BYD","Sealion","EV",0,"electric"],

["Fangchengbao","Bao 8","PHEV",2000,"hybrid"],

["MG","MG3","1.5L",1500,"petrol"],["MG","MG4 EV","EV",0,"electric"],["MG","MG5 GT","1.5L",1500,"petrol"],
["MG","MG6","1.5L Turbo",1500,"petrol"],["MG","MG7","1.5L Turbo",1500,"petrol"],["MG","RX5","1.5L Turbo",1500,"petrol"],
["MG","RX9","2.0L Turbo",2000,"petrol"],["MG","ZS","1.5L",1500,"petrol"],["MG","HS","1.5L Turbo",1500,"petrol"],["MG","Cyberster","EV",0,"electric"],

["Changan","G318","2.0L Turbo",2000,"petrol"],["Changan","Qiyuan","EV",0,"electric"],["Changan","UNI-V","1.5L Turbo",1500,"petrol"],
["Changan","UNI-T","1.5L Turbo",1500,"petrol"],["Changan","UNI-K","2.0L Turbo",2000,"petrol"],["Changan","SL03","EV",0,"electric"],
["Changan","CS75 Plus","1.5L Turbo",1500,"petrol"],["Changan","EADO","1.4L Turbo",1400,"petrol"],

["Haval","H5","2.0L Turbo",2000,"petrol"],["Haval","H6","1.5L Turbo",1500,"petrol"],["Haval","H9","2.0L Turbo",2000,"petrol"],
["Haval","Jolion","1.5L Turbo",1500,"petrol"],["Haval","Raptor","2.0L Turbo",2000,"petrol"],["Haval","Big dog","2.0L Turbo",2000,"petrol"],

["NIO","ET5","EV",0,"electric"],["NIO","ET7","EV",0,"electric"],["NIO","ET9","EV",0,"electric"],["NIO","ES8","EV",0,"electric"],
["NIO","ES6","EV",0,"electric"],["NIO","ES7","EV",0,"electric"],["NIO","EC7","EV",0,"electric"],["NIO","Ec6","EV",0,"electric"],

["Tank","300","2.0L Turbo",2000,"petrol"],["Tank","400","2.0L Turbo Hybrid",2000,"hybrid"],["Tank","500","2.0L Turbo",2000,"petrol"],["Tank","700","3.0L Turbo",3000,"petrol"],

["Voyah","Free","EV",0,"electric"],["Voyah","Dream","EV",0,"electric"],["Voyah","Passion","EV",0,"electric"],

["Dongfeng","Shine Max","1.5L Turbo",1500,"petrol"],["Dongfeng","M-Hero 917","EV",0,"electric"],["Dongfeng","Aeolus S7","1.5L Turbo",1500,"petrol"],

["Xpeng","G6","EV",0,"electric"],["Xpeng","G9","EV",0,"electric"],["Xpeng","P7","EV",0,"electric"],

["Alfa Romeo","Giulia","2.0L Turbo",2000,"petrol"],["Alfa Romeo","Stelvio","2.0L Turbo",2000,"petrol"],
["Alfa Romeo","Tonale","1.5L Turbo Hybrid",1500,"hybrid"],["Alfa Romeo","33 Stradale","3.0L Twin-Turbo V6",3000,"petrol"],["Alfa Romeo","Junior","1.2L Turbo Hybrid",1200,"hybrid"],

["Infiniti","QX55","2.0L Turbo",2000,"petrol"],["Infiniti","QX60","3.5L V6",3500,"petrol"],["Infiniti","Qx80","3.5L Twin-Turbo V6",3500,"petrol"],

["Avatar","6","EV",0,"electric"],["Avatar","7","EV",0,"electric"],["Avatar","12","EV",0,"electric"],["Avatar","11","EV",0,"electric"],

["Xiaomi","SU7","EV",0,"electric"],["Xiaomi","Yu7","EV",0,"electric"],

["Genesis","GV60","EV",0,"electric"],["Genesis","GV70","2.5L Turbo",2500,"petrol"],["Genesis","GV80","3.5L Turbo V6",3500,"petrol"],
["Genesis","G70","2.0L Turbo",2000,"petrol"],["Genesis","G80","2.5L Turbo",2500,"petrol"],["Genesis","G90","3.5L Turbo V6",3500,"petrol"],

["Opel","Corsa","1.2L Turbo",1200,"petrol"],["Opel","Mokka","1.2L Turbo",1200,"petrol"],["Opel","Astra","1.2L Turbo",1200,"petrol"],

["Geely","Xingyuan","EV",0,"electric"],["Geely","BoYue","1.5L Turbo",1500,"petrol"],["Geely","Emgrand","1.5L",1500,"petrol"],
["Geely","Galaxy L6","PHEV",1500,"hybrid"],["Geely","Galaxy E8","EV",0,"electric"],["Geely","Galaxy E5","EV",0,"electric"],["Geely","Galaxy Starshine 8","PHEV",1500,"hybrid"],

["SsangYong","Actyon","1.5L Turbo",1500,"petrol"],["SsangYong","Korando","1.5L Turbo",1500,"petrol"],
["SsangYong","Musso/Musso Grand Khan","2.2L Diesel",2200,"petrol"],["SsangYong","Rexton","2.2L Diesel",2200,"petrol"],
["SsangYong","Torres","1.5L Turbo",1500,"petrol"],["SsangYong","Tivoli","1.5L Turbo",1500,"petrol"],

["LiAuto","L6","EREV Hybrid",1500,"hybrid"],["LiAuto","L7","EREV Hybrid",1500,"hybrid"],["LiAuto","L8","EREV Hybrid",1500,"hybrid"],

["Yangwang","U8","EV",0,"electric"],["Yangwang","U9","EV",0,"electric"],

["Fiat","Tipo","1.4L",1400,"petrol"],["Fiat","puls","1.3L Turbo",1300,"petrol"],["Fiat","600","1.2L Turbo",1200,"petrol"],["Fiat","500","EV",0,"electric"],

["Maextro","S800","EV",0,"electric"],

["M-Hero","M817","EV",0,"electric"],["M-Hero","917","EV",0,"electric"],

["ORA","Good Cat","EV",0,"electric"],["ORA","Funky Cat","EV",0,"electric"],["ORA","Lightning Cat","EV",0,"electric"],["ORA","Ballet Cat","EV",0,"electric"],

["Denza","Z9","EV",0,"electric"],

["Jaguar","F-Pace","2.0L Turbo",2000,"petrol"],["Jaguar","E-Pace","2.0L Turbo",2000,"petrol"],
["Jaguar","F-Type","3.0L Turbo V6",3000,"petrol"],["Jaguar","I-Pace","EV",0,"electric"],["Jaguar","XF","2.0L Turbo",2000,"petrol"],

["Land Rover","Range Rover","3.0L Turbo I6",3000,"petrol"],["Land Rover","Range Rover Sport","3.0L Turbo I6",3000,"petrol"],
["Land Rover","Range Rover Velar","2.0L Turbo",2000,"petrol"],["Land Rover","Range Rover Evoque","2.0L Turbo",2000,"petrol"],
["Land Rover","Discovery","3.0L Turbo I6",3000,"petrol"],["Land Rover","Discovery Sport","2.0L Turbo",2000,"petrol"],["Land Rover","Defender","3.0L Turbo I6",3000,"petrol"],

/* ---- Supplementary variants: many models above are commonly cross-shopped between a
   base petrol trim and a hybrid/alternate-engine trim (e.g. Lexus LX, Land Cruiser,
   Tucson/Sportage, Range Rover) — the licensed list only names the model, so these extra
   rows add the realistic engine/fuel spread within that same approved model. ---- */
/* Lexus LX has no real hybrid variant — removed. Added a real Prado diesel trim below instead. */
["Toyota","Land Cruiser 300","3.3L Turbo Diesel V6",3300,"petrol"],
["Toyota","Land Cruiser Prado/250","2.8L Turbo Diesel",2800,"petrol"],
["Toyota","Land Cruiser Prado/250","2.4L Turbo Hybrid",2400,"hybrid"],
["Toyota","RAV4","2.0L Hybrid",2000,"hybrid"],
["Toyota","Corolla Cross","1.8L Hybrid",1800,"hybrid"],
["Hyundai","Tucson","1.6L Turbo Hybrid",1600,"hybrid"],
["Hyundai","Santa Fe","1.6L Turbo Hybrid",1600,"hybrid"],
["Hyundai","Sonata","2.0L Turbo Hybrid",2000,"hybrid"],
["Kia","Sportage","1.6L Turbo Hybrid",1600,"hybrid"],
["Kia","Sorento","1.6L Turbo Hybrid",1600,"hybrid"],
["Kia","K5/Optima","2.0L Turbo Hybrid",2000,"hybrid"],
["Land Rover","Range Rover","3.0L Turbo I6 Plug-in Hybrid",3000,"hybrid"],
["Land Rover","Range Rover Sport","3.0L Turbo I6 Plug-in Hybrid",3000,"hybrid"],
["Land Rover","Defender","3.0L Turbo I6 Plug-in Hybrid",3000,"hybrid"],
["Land Rover","Range Rover Evoque","1.5L Turbo Hybrid",1500,"hybrid"],
["BMW","X5","3.0L Turbo I6 Plug-in Hybrid",3000,"hybrid"],
["BMW","Series 5","2.0L Turbo Hybrid",2000,"hybrid"],
["Mercedes-Benz","GLE","3.0L Turbo I6 Plug-in Hybrid",3000,"hybrid"],
["Mercedes-Benz","C Class","2.0L Turbo Hybrid",2000,"hybrid"],
["Volvo","XC60","2.0L Turbo Plug-in Hybrid",2000,"hybrid"],
["Volvo","XC90","2.0L Turbo Plug-in Hybrid",2000,"hybrid"],
["Mitsubishi","Outlander","2.4L Plug-in Hybrid",2400,"hybrid"],
["Honda","CR-V","2.0L Hybrid",2000,"hybrid"],
["Honda","Accord","2.0L Hybrid",2000,"hybrid"],
["Nissan","X-trail","1.5L e-Power Hybrid",1500,"hybrid"],
["Nissan","Altima","2.5L Hybrid",2500,"hybrid"],
["Mazda","CX-5","2.5L Hybrid",2500,"hybrid"],
["Mazda","CX-70","3.3L Turbo Hybrid",3300,"hybrid"],
];

/* ---- Persian brand names, so typing Persian also finds the right brand ---- */
const persianBrandNames = {
  "Mercedes-Benz":"مرسدس بنز","BMW":"بی ام و","Acura":"آکورا","Volkswagen":"فولکس واگن","Audi":"آئودی",
  "Toyota":"تویوتا","Kia":"کیا","Honda":"هوندا","Peugeot":"پژو","Nissan":"نیسان","Hyundai":"هیوندای",
  "Citroen":"سیتروئن","Volvo":"ولوو","Cupra":"کوپرا","Lexus":"لکسوس","Suzuki":"سوزوکی","Mazda":"مزدا",
  "Renault":"رنو","Mitsubishi":"میتسوبیشی","Skoda":"اسکودا","Subaru":"سوبارو","Mini":"مینی","Dacia":"داسیا",
  "BYD":"بی وای دی","Fangchengbao":"فانگ چنگ بائو","MG":"ام جی","Changan":"چانگان","Haval":"هاوال",
  "NIO":"نیو","Tank":"تانک","Voyah":"ووا","Dongfeng":"دانگ فنگ","Xpeng":"اکسپنگ","Alfa Romeo":"آلفارومئو",
  "Infiniti":"اینفینیتی","Avatar":"آواتار","Xiaomi":"شیائومی","Genesis":"جنسیس","Opel":"اوپل","Geely":"جیلی",
  "SsangYong":"سانگ یانگ","LiAuto":"لی اوتو","Yangwang":"یانگ وانگ","Fiat":"فیات","Maextro":"مکسترو",
  "M-Hero":"ام هیرو","ORA":"اورا","Denza":"دنزا","Jaguar":"جگوار","Land Rover":"لندروور",
};

/* ---- Persian model aliases (phonetic), so searches like "لندکروز" or "کمری" find the right model ---- */
const persianModelAliases = {
  "Mercedes-Benz|A Class":"کلاس آ ای کلاس","Mercedes-Benz|B Class":"کلاس ب بی کلاس","Mercedes-Benz|C Class":"سی کلاس کلاس سی",
  "Mercedes-Benz|E Class":"ای کلاس کلاس ای","Mercedes-Benz|S Class":"اس کلاس کلاس اس","Mercedes-Benz|GLE":"جی ال ای",
  "Mercedes-Benz|GLC":"جی ال سی","Mercedes-Benz|GLS":"جی ال اس","Mercedes-Benz|GLA":"جی ال آ","Mercedes-Benz|GLB":"جی ال بی",
  "Mercedes-Benz|CLA":"سی ال آ","Mercedes-Benz|EQE":"ای کیو ای","Mercedes-Benz|EQS":"ای کیو اس","Mercedes-Benz|EQA":"ای کیو آ",
  "BMW|Series 1":"سری یک بی ام و ۱","BMW|Series 2":"سری دو بی ام و ۲","BMW|Series 3":"سری سه بی ام و ۳",
  "BMW|Series 4":"سری چهار بی ام و ۴","BMW|Series 5":"سری پنج بی ام و ۵","BMW|Series 7":"سری هفت بی ام و ۷","BMW|Series 8":"سری هشت بی ام و ۸",
  "BMW|X1":"ایکس یک","BMW|X2":"ایکس دو","BMW|X3":"ایکس سه","BMW|X4":"ایکس چهار","BMW|X5":"ایکس پنج","BMW|X6":"ایکس شش","BMW|X7":"ایکس هفت",
  "BMW|iX1":"آی ایکس یک","BMW|iX2":"آی ایکس دو","BMW|iX3":"آی ایکس سه","BMW|iX":"آی ایکس","BMW|i3":"آی ۳","BMW|i4":"آی ۴","BMW|i5":"آی ۵","BMW|z4":"زد ۴",
  "Acura|Integra":"اینتگرا",
  "Volkswagen|Golf":"گلف","Volkswagen|Jetta":"جتا","Volkswagen|Arteon":"آرتئون","Volkswagen|Passat":"پاسات",
  "Volkswagen|Atlas/Teramont":"اطلس ترامونت","Volkswagen|Tiguan":"تیگوان","Volkswagen|ID.3":"آی دی ۳","Volkswagen|ID.4":"آی دی ۴","Volkswagen|ID.5":"آی دی ۵",
  "Audi|A3":"ای ۳ آ ۳","Audi|A5":"ای ۵ آ ۵","Audi|A7":"ای ۷ آ ۷","Audi|A8":"ای ۸ آ ۸","Audi|Q3":"کیو ۳","Audi|Q5":"کیو ۵","Audi|Q7":"کیو ۷","Audi|Q8":"کیو ۸","Audi|e-tron GT":"ای‌ترون جی‌تی",
  "Toyota|Avalon":"اوالون","Toyota|Camry":"کمری کامری","Toyota|C-HR":"سی اچ آر","Toyota|Corolla":"کرولا",
  "Toyota|Crown":"کراون","Toyota|Highlander":"های لندر هایلندر","Toyota|Mirai":"میرای",
  "Toyota|Prius":"پریوس","Toyota|RAV4":"راو۴ راو چهار","Toyota|Sequoia":"سکویا","Toyota|Sienna":"سیه‌نا سینا","Toyota|Supra":"سوپرا",
  "Toyota|Tundra":"تاندرا","Toyota|Yaris":"یاریس","Toyota|Land Cruiser Prado/250":"لندکروز پرادو لندکروزر پرادو پرادو",
  "Toyota|Land Cruiser 70":"لندکروز هفتاد لندکروزر هفتاد","Toyota|Land Cruiser 300":"لندکروز سیصد لندکروزر سیصد لندکروز ۳۰۰",
  "Toyota|Levin":"لوین","Toyota|Corolla Cross":"کرولا کراس","Toyota|Venza":"ونزا","Toyota|Wildlander":"وایلدلندر","Toyota|GR86":"جی آر ۸۶",
  "Lexus|ES":"ای اس","Lexus|IS":"آی اس","Lexus|LC":"ال سی","Lexus|LS":"ال اس","Lexus|LX":"ال ایکس","Lexus|NX":"ان ایکس",
  "Lexus|RC":"آر سی","Lexus|RZ":"آر زد","Lexus|GX":"جی ایکس","Lexus|UX":"یو ایکس","Lexus|RX":"آر ایکس","Lexus|LM":"ال ام",
  "Hyundai|Accent":"اکسنت","Hyundai|Elantra":"النترا","Hyundai|Ioniq 5":"آیونیک ۵","Hyundai|Kona":"کونا",
  "Hyundai|Palisade":"پالیسید","Hyundai|Santa Fe":"سانتافه سنتافه","Hyundai|Sonata":"سوناتا","Hyundai|Staria":"استاریا",
  "Hyundai|Tucson":"توسان توسکان","Hyundai|Santa Cruz":"سانتاکروز","Hyundai|Casper":"کاسپر","Hyundai|Venue":"وینیو","Hyundai|Bayon":"بایون",
  "Kia|Ceed":"سید","Kia|K3":"کی ۳","Kia|K4":"کی ۴","Kia|K5/Optima":"کی ۵ اپتیما","Kia|K8":"کی ۸","Kia|K9":"کی ۹",
  "Kia|EV6":"ای وی ۶","Kia|EV9":"ای وی ۹","Kia|Seltos":"سلتوس","Kia|Sorento":"سورنتو","Kia|telluride":"تلوراید","Kia|Tasman":"تاسمان","Kia|Carnival":"کارنیوال",
  "Genesis|GV60":"جی وی ۶۰","Genesis|GV70":"جی وی ۷۰","Genesis|GV80":"جی وی ۸۰","Genesis|G70":"جی ۷۰","Genesis|G80":"جی ۸۰","Genesis|G90":"جی ۹۰",
  "Honda|Civic":"سیویک","Honda|CR-V":"سی آر وی","Honda|HR-V":"اچ آر وی","Honda|Passport":"پاسپورت",
  "Honda|Odyssey":"ادیسه","Honda|Accord":"اکورد","Honda|Civic Type R":"سیویک تایپ آر",
  "Nissan|Altima":"آلتیما","Nissan|Sentra":"سنترا","Nissan|X-trail":"ایکس تریل","Nissan|Patrol":"پاترول",
  "Nissan|Pathfinder":"پث فایندر پث‌فایندر","Nissan|Kicks":"کیکس","Nissan|Juke":"جوک","Nissan|Leaf":"لیف",
  "Nissan|Navara":"نوارا","Nissan|Note":"نوت","Nissan|sylphy":"سیلفی","Nissan|Armada":"آرمادا","Nissan|Qashqai":"قشقایی",
  "Nissan|Rogue":"روگ","Nissan|Terra":"ترا","Nissan|Z":"زد نیسان زد","Nissan|Sunny":"سانی","Nissan|Tilda":"تیلدا","Nissan|Ariya":"آریا",
  "Infiniti|QX55":"کیو ایکس ۵۵","Infiniti|QX60":"کیو ایکس ۶۰","Infiniti|Qx80":"کیو ایکس ۸۰",
  "Mazda|CX-30":"سی ایکس ۳۰","Mazda|CX-5":"سی ایکس ۵","Mazda|CX-70":"سی ایکس ۷۰","Mazda|CX-50":"سی ایکس ۵۰","Mazda|MAZDA6":"مزدا ۶","Mazda|3":"مزدا ۳","Mazda|MX-5/Roadster":"ام ایکس ۵ رودستر",
  "Mitsubishi|ASX":"ای اس ایکس","Mitsubishi|Outlander":"اوتلندر","Mitsubishi|Mirage":"میراژ",
  "Mitsubishi|Xforce/Outlander Sport":"ایکس فورس اوتلندر اسپرت","Mitsubishi|Destinator":"دستیناتور",
  "Suzuki|Swift":"سوئیفت","Suzuki|Baleno":"بالنو","Suzuki|Jimny":"جیمنی","Suzuki|Grand Vitara":"گرند ویتارا","Suzuki|Fronx":"فرانکس",
  "Land Rover|Range Rover":"رنج روور","Land Rover|Range Rover Sport":"رنج روور اسپرت",
  "Land Rover|Range Rover Velar":"رنج روور ولار","Land Rover|Range Rover Evoque":"رنج روور ایووک اوک",
  "Land Rover|Discovery":"دیسکاوری","Land Rover|Discovery Sport":"دیسکاوری اسپرت","Land Rover|Defender":"دیفندر",
  "Jaguar|F-Pace":"اف پیس","Jaguar|E-Pace":"ای پیس","Jaguar|F-Type":"اف تایپ","Jaguar|I-Pace":"آی پیس","Jaguar|XF":"ایکس اف",
  "Volvo|XC60":"ایکس سی ۶۰","Volvo|XC90":"ایکس سی ۹۰","Volvo|S60":"اس ۶۰","Volvo|S90":"اس ۹۰",
  "Volvo|EX30":"ای ایکس ۳۰","Volvo|EX40":"ای ایکس ۴۰","Volvo|EX90":"ای ایکس ۹۰",
  "Cupra|Leon":"لئون","Cupra|Tavascan":"تاواسکان",
  "Skoda|Octavia":"اکتاویا","Skoda|Superb":"سوپرب","Skoda|Kodiaq":"کودیاک","Skoda|Enyaq":"انیاک","Skoda|Kamiq":"کامیک","Skoda|Kushaq":"کوشاک",
  "Subaru|BRZ":"بی آر زد","Subaru|WRX":"دبلیو آر ایکس","Subaru|Outback":"آوت بک","Subaru|Crosstrek":"کراس ترک",
  "Mini|Cooper":"کوپر","Mini|Countryman":"کانتری من","Mini|Aceman":"ایس من",
  "Dacia|Duster":"داستر","Dacia|Jogger":"جاگر","Dacia|Logan":"لوگان","Dacia|Lodgy":"لوجی","Dacia|Sunder":"ساندر","Dacia|Spring":"اسپرینگ","Dacia|Bigester":"بیگستر",
  "Renault|Arkana":"آرکانا","Renault|Captur":"کپچر","Renault|Duster":"داستر","Renault|Koleos":"کولئوس",
  "Renault|Clio":"کلیو","Renault|Rafale":"رافال",
  "Citroen|C3":"سی ۳","Citroen|C4":"سی ۴","Citroen|C5x":"سی ۵ ایکس",
  "MG|MG3":"ام جی ۳","MG|MG5 GT":"ام جی ۵ جی تی","MG|MG6":"ام جی ۶","MG|MG7":"ام جی ۷","MG|RX5":"آر ایکس ۵",
  "MG|RX9":"آر ایکس ۹","MG|ZS":"زد اس","MG|HS":"اچ اس","MG|Cyberster":"سایبراستر","MG|MG4 EV":"ام جی ۴",
  "Changan|Eado":"ایدو","Changan|CS75 Plus":"سی اس ۷۵ پلاس","Changan|UNI-V":"یونی وی","Changan|UNI-T":"یونی تی","Changan|UNI-K":"یونی کی","Changan|G318":"جی ۳۱۸",
  "Haval|H5":"اچ ۵","Haval|H6":"اچ ۶","Haval|H9":"اچ ۹","Haval|Jolion":"جولیون","Haval|Raptor":"رپتور هاوال راپتور","Haval|Big dog":"بیگ داگ",
  "NIO|ET5":"ای تی ۵","NIO|ET7":"ای تی ۷","NIO|ET9":"ای تی ۹","NIO|ES6":"ای اس ۶","NIO|ES7":"ای اس ۷","NIO|ES8":"ای اس ۸",
  "Tank|300":"تانک سیصد","Tank|400":"تانک چهارصد","Tank|500":"تانک پانصد","Tank|700":"تانک هفتصد",
  "Xpeng|G6":"جی ۶","Xpeng|G9":"جی ۹","Xpeng|P7":"پی ۷",
  "Alfa Romeo|Giulia":"جولیا","Alfa Romeo|Stelvio":"استلویو","Alfa Romeo|Tonale":"تونال","Alfa Romeo|Junior":"جونیور",
  "BYD|Han":"هان","BYD|Tang":"تانگ","BYD|Seal":"سیل","BYD|Qin L":"کین ال چین ال","BYD|Song Plus":"سانگ پلاس",
  "BYD|Qin Plus":"کین پلاس","BYD|Shark":"شارک","BYD|Destroyer 05":"دیستروی‌یر ۰۵","BYD|Sealion":"سی‌لاین سی‌لایون",
  "Opel|Corsa":"کورسا","Opel|Mokka":"موکا","Opel|Astra":"استرا",
  "SsangYong|Actyon":"اکتیون","SsangYong|Korando":"کوراندو","SsangYong|Musso/Musso Grand Khan":"موسو گرند خان",
  "SsangYong|Rexton":"رکستون","SsangYong|Torres":"تورس","SsangYong|Tivoli":"تیوولی",
  "LiAuto|L6":"ال ۶","LiAuto|L7":"ال ۷","LiAuto|L8":"ال ۸",
  "Yangwang|U8":"یو ۸","Yangwang|U9":"یو ۹",
  "Fiat|Tipo":"تیپو","Fiat|puls":"پالس پولس","Fiat|600":"فیات ششصد","Fiat|500":"فیات پانصد",
  "Maextro|S800":"اس ۸۰۰",
  "M-Hero|M817":"ام ۸۱۷","M-Hero|917":"نهصد و هفده",
  "Denza|Z9":"زد ۹",
  "Peugeot|508":"پژو پانصد و هشت","Peugeot|408":"پژو چهارصد و هشت","Peugeot|308":"پژو سیصد و هشت","Peugeot|2008":"پژو دو هزار و هشت","Peugeot|208":"پژو دویست و هشت",
};

function getModelAlias(brand, model){ return persianModelAliases[brand+'|'+model] || ''; }

const carMap = {};
carDB.forEach(([brand,model,label,cc,fuel])=>{
  carMap[brand] = carMap[brand] || {};
  carMap[brand][model] = carMap[brand][model] || [];
  carMap[brand][model].push({label,cc,fuel});
});
const allowedBrandsLower = Object.keys(carMap).map(b=>b.toLowerCase());

/* ---- Combined brand+model search index (searchable in English or Persian) ---- */
const carSearchIndex = [];
Object.keys(carMap).forEach(brand=>{
  const fa = persianBrandNames[brand] || '';
  Object.keys(carMap[brand]).forEach(model=>{
    const modelFa = getModelAlias(brand, model);
    carSearchIndex.push({
      brand, model,
      display: `${brand} ${model}`,
      search: `${brand} ${model} ${fa} ${modelFa}`.toLowerCase(),
    });
  });
});

let selectedBrand = null;
let selectedModel = null;
let selectedVariant = null; // {label,cc,fuel}

const yearSel = document.getElementById('carYear');
for(let y=2026;y>=2021;y--){
  const opt = document.createElement('option'); opt.value=y; opt.textContent=y+' (میلادی)';
  if(y===2025) opt.selected=true;
  yearSel.appendChild(opt);
}

function makeSearchable(inputEl, listEl, getOptions, onPick){
  function renderList(query){
    const opts = getOptions();
    const q = query.trim().toLowerCase();
    const filtered = q ? opts.filter(o=>o.search.includes(q)) : opts;
    if(!filtered.length){ listEl.innerHTML = '<div class="search-empty">موردی یافت نشد</div>'; listEl.style.display='block'; return; }
    listEl.innerHTML = filtered.slice(0,60).map((o,i)=>{
      const fa = persianBrandNames[o.brand] || '';
      const modelFa = getModelAlias(o.brand, o.model).split(' ')[0] || '';
      const faTag = (fa || modelFa) ? ` <span style="color:#B4ADCB;font-size:.8em;">(${[fa, modelFa].filter(Boolean).join(' ')})</span>` : '';
      return `<div class="search-item" data-i="${i}">${o.display}${faTag}</div>`;
    }).join('');
    listEl.style.display = 'block';
    listEl.querySelectorAll('.search-item').forEach((item,i)=>{
      item.addEventListener('mousedown', (e)=>{
        e.preventDefault();
        const picked = filtered[i];
        inputEl.value = picked.display;
        listEl.style.display = 'none';
        onPick(picked);
      });
    });
  }
  inputEl.addEventListener('focus', ()=>renderList(inputEl.value));
  inputEl.addEventListener('input', ()=>renderList(inputEl.value));
  inputEl.addEventListener('blur', ()=>setTimeout(()=>{ listEl.style.display='none'; }, 150));
}

const carSearchInput = document.getElementById('carSearch');
const carPickedTag = document.getElementById('carPickedTag');
const carVariantWrap = document.getElementById('carVariantWrap');
const carVariantChips = document.getElementById('carVariantChips');
const carVariantAuto = document.getElementById('carVariantAuto');

const carListingsMatchWrap = document.getElementById('carListingsMatch');
const carListingsMatchList = document.getElementById('carListingsMatchList');
function renderCarListingsMatch(brand){
  const norm = s => (s||'').toLowerCase().replace(/[\s-]+/g,'-');
  const brandSlug = norm(brand);
  const matches = CAR_LISTINGS.filter(l => norm(l.make) === brandSlug || norm(l.make).includes(brandSlug) || brandSlug.includes(norm(l.make)));
  if(!matches.length){ carListingsMatchWrap.style.display = 'none'; carListingsMatchList.innerHTML = ''; return; }
  carListingsMatchList.innerHTML = matches.slice(0,8).map(l => `
    <a href="${l.url}" class="car-match-item" target="_blank">
      <div class="thumb">${l.cover ? `<img src="${l.cover}" alt="">` : ''}</div>
      <div class="info">
        <div class="title">${l.title}</div>
        <div class="price">${Number(l.price_aed).toLocaleString('en-US')} درهم</div>
      </div>
    </a>`).join('');
  carListingsMatchWrap.style.display = 'block';
}

makeSearchable(carSearchInput, document.getElementById('carSearchList'), ()=>carSearchIndex, (picked)=>{
  selectedBrand = picked.brand;
  selectedModel = picked.model;
  selectedVariant = null;
  carPickedTag.style.display = 'inline-flex';
  carPickedTag.textContent = `✓ ${picked.display}`;
  renderCarListingsMatch(selectedBrand);

  const variants = carMap[selectedBrand][selectedModel];
  carVariantWrap.style.display = 'block';
  if(variants.length === 1){
    selectedVariant = variants[0];
    carVariantChips.innerHTML = '';
    carVariantAuto.style.display = 'inline-flex';
    carVariantAuto.textContent = `✓ ${variants[0].label}${variants[0].cc ? ' — ' + variants[0].cc + ' سی‌سی' : ''} (به‌صورت خودکار انتخاب شد)`;
    applyEngineSelection(variants[0].cc, variants[0].fuel);
  } else {
    carVariantAuto.style.display = 'none';
    carVariantChips.innerHTML = variants.map((v,i)=>{
      const txt = v.fuel==='electric' ? `${v.label} (برقی)` : v.fuel==='hybrid' ? `${v.label} (هیبرید)` : `${v.label} — ${v.cc} cc`;
      return `<button type="button" class="variant-chip" data-i="${i}">${txt}</button>`;
    }).join('');
    carVariantChips.querySelectorAll('.variant-chip').forEach(chip=>{
      chip.addEventListener('click', ()=>{
        carVariantChips.querySelectorAll('.variant-chip').forEach(c=>c.classList.remove('active'));
        chip.classList.add('active');
        selectedVariant = variants[parseInt(chip.dataset.i)];
        applyEngineSelection(selectedVariant.cc, selectedVariant.fuel);
      });
    });
  }
});

function applyEngineSelection(cc, fuel){
  let catId;
  if(fuel==='electric' || fuel==='hybrid') catId='ev';
  else if(cc<=1500) catId='c1500';
  else if(cc<=2000) catId='c2000';
  else if(cc<=2500) catId='c2500';
  else if(cc<=3000) catId='c3000';
  else catId='c3001';
  selectCategoryById(catId);
}

document.getElementById('manualCCBtn').addEventListener('click', ()=>{
  const cc = parseFloat((document.getElementById('manualCC').value||'').replace(/,/g,''));
  const isHybrid = document.getElementById('manualIsHybrid').checked;
  if(isHybrid){ applyEngineSelection(0, 'hybrid'); }
  else if(!isNaN(cc) && cc>0){ applyEngineSelection(cc, 'petrol'); }
});

function getSelectedCarLabel(){
  if(selectedBrand && selectedModel && selectedVariant){
    return `${selectedBrand} ${selectedModel} — ${selectedVariant.label} (سال ${yearSel.value})`;
  }
  const manual = document.getElementById('manualCC').value;
  if(manual) return `دستی — ${manual} سی‌سی`;
  if(wizardMode === 'cc') return `بر اساس دسته موتور — ${activeCat.label}`;
  if(lastVinDecoded) return lastVinDecoded;
  return 'مشخص نشده';
}

/* ---- Live thousand-separator formatting for money fields ---- */
function formatThousands(el){
  const raw = el.value.replace(/[^\d.]/g,'');
  if(raw === ''){ el.value = ''; return; }
  const parts = raw.split('.');
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  el.value = parts.length>1 ? parts[0]+'.'+parts[1] : parts[0];
}
['realPriceAED','customsPriceAED','freeRate','customsRate','seaFreightAED','permitsAED','storage','manualCC'].forEach(id=>{
  const el = document.getElementById(id);
  el.addEventListener('input', ()=>formatThousands(el));
});

function num(id){ const raw=(document.getElementById(id).value||'').replace(/,/g,''); const v = parseFloat(raw); return isNaN(v)||v<0 ? 0 : v; }
function pct(id){ return num(id)/100; }
function fmt(n){ return Math.round(n).toLocaleString('en-US'); }

function renderDonut(container, slices){
  const total = slices.reduce((s,d)=>s+d.value,0) || 1;
  const r = 66, cx = 90, cy = 90, circumference = 2*Math.PI*r;
  let acc = 0;
  const arcs = slices.map(d=>{
    const frac = d.value/total;
    const dash = frac*circumference;
    const offset = -acc*circumference;
    acc += frac;
    return `<circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${d.color}" stroke-width="24"
      stroke-dasharray="${dash} ${circumference-dash}" stroke-dashoffset="${offset}"
      transform="rotate(-90 ${cx} ${cy})"></circle>`;
  }).join('');
  const legend = slices.map(d=>{
    const p = ((d.value/total)*100).toFixed(1);
    return `<div class="legend-row"><span class="legend-dot" style="background:${d.color}"></span>
      <span class="legend-name">${d.label}</span><span class="legend-val num-font">${fmt(d.value)} (${p}٪)</span></div>`;
  }).join('');
  container.innerHTML = `
    <svg class="donut-svg" viewBox="0 0 180 180">
      <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="#fff" stroke-width="24"></circle>
      ${arcs}
      <text x="${cx}" y="${cy-5}" text-anchor="middle" font-size="10" fill="#5B6478" font-weight="700">جمع کل</text>
      <text x="${cx}" y="${cy+13}" text-anchor="middle" font-size="13" font-weight="900" fill="#122C7A">${fmt(total)}</text>
    </svg>
    <div class="legend">${legend}</div>
  `;
}

function renderBars(container, rows){
  const max = Math.max(...rows.map(r=>r.value), 1);
  container.innerHTML = rows.map(r=>`
    <div class="bar-row">
      <div class="bar-top"><span class="bname">${r.label}</span><span class="bval num-font">${fmt(r.value)}</span></div>
      <div class="bar-track"><div class="bar-fill" style="width:${Math.max((r.value/max)*100,2)}%;background:${r.color}"></div></div>
    </div>
  `).join('');
}

const miniIcons = {
  car:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 13l1.5-4.5A2 2 0 0 1 6.4 7h11.2a2 2 0 0 1 1.9 1.5L21 13"/><rect x="2.5" y="13" width="19" height="5" rx="1.5"/></svg>`,
  customs:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V10l8-6 8 6v11"/><path d="M4 10h16"/></svg>`,
  plate:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="12" rx="2"/><path d="M7 15h4"/></svg>`,
  profit:`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M15 7h6v6"/></svg>`,
};

function renderMiniGauges(container, items){
  container.innerHTML = items.map(it=>`
    <div class="mini-gauge-item">
      <div class="mini-gauge" style="--pct:${it.pct};--ring-color:${it.color}">
        <div class="mg-icon">${it.icon}</div>
      </div>
      <div class="mg-label">${it.label}</div>
      <div class="mg-val num-font">${it.pct.toFixed(0)}٪</div>
    </div>
  `).join('');
}

let lastBreakdownRows = [];
let lastTotals = {};
let lastRawValues = {};

function logCalcNow(){
  return fetch(CALC_LOG_URL, {
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN},
    body: JSON.stringify(lastRawValues)
  }).catch(()=>{});
}

function calc(){
  const realPriceAED = getPriceAED('realPriceAED');
  const customsPriceAED = getPriceAED('customsPriceAED');
  const freeRate = num('freeRate');
  const customsRate = num('customsRate');
  const seaFreightAED = num('seaFreightAED');
  const permitsAED = num('permitsAED');
  const storage = num('storage');

  const CIF = customsPriceAED * customsRate;
  const realPriceToman = realPriceAED * freeRate;
  const dutyProfit = activeCat.coef * CIF;
  const base9 = dutyProfit + CIF;

  const r = {
    customsFixed: pct('r_customsFixed'), gasoline: pct('r_gasoline'), fob: pct('r_fob'),
    vat: pct('r_vat'), advanceTax: pct('r_advanceTax'), redCrescent: pct('r_redCrescent'),
    supervision: pct('r_supervision'), waste: pct('r_waste'), standard: pct('r_standard'),
    scrapCert: pct('r_scrapCert'), plateReg: pct('r_plateReg'), transferTax: pct('r_transferTax'),
    municipal: pct('r_municipal'), individual: pct('r_individual'), serviceProfit: pct('r_serviceProfit'),
  };

  const customsRows = [
    ['سود بازرگانی', `${(activeCat.coef*100).toFixed(0)}٪ از ارزش گمرکی (بر اساس دسته خودرو)`, dutyProfit],
    ['حقوق گمرکی ثابت', `${(r.customsFixed*100).toFixed(2)}٪ از ارزش گمرکی`, r.customsFixed*CIF],
    ['عوارض بنزین‌سوز', `${(r.gasoline*100).toFixed(2)}٪ از ارزش گمرکی`, r.gasoline*CIF],
    ['عوارض ۵٪ فوب', `${(r.fob*100).toFixed(2)}٪ از ارزش فوب`, r.fob*CIF],
    ['مالیات ارزش افزوده (VAT)', `${(r.vat*100).toFixed(2)}٪ از (گمرکی+حقوق ورودی)`, r.vat*base9],
    ['مالیات علی‌الحساب واردات', `${(r.advanceTax*100).toFixed(2)}٪ از (گمرکی+حقوق ورودی)`, r.advanceTax*base9],
    ['عوارض هلال احمر', `${(r.redCrescent*100).toFixed(2)}٪ از حقوق ورودی`, r.redCrescent*dutyProfit],
    ['حق نظارت کارشناسان گمرک', `${(r.supervision*100).toFixed(2)}٪ از حقوق ورودی`, r.supervision*dutyProfit],
    ['عوارض پسماند کالا', `${(r.waste*100).toFixed(3)}٪ از ارزش گمرکی`, r.waste*CIF],
    ['هزینه استاندارد', `${(r.standard*100).toFixed(2)}٪ از ارزش گمرکی`, r.standard*CIF],
  ];
  const sumCustoms10 = customsRows.reduce((s,row)=>s+row[2],0);
  const seaFreight = seaFreightAED * freeRate;
  const permits = permitsAED * freeRate;
  customsRows.push(['حمل دریایی', 'مبلغ دستی وارد شده (درهم × نرخ ارز آزاد)', seaFreight]);
  customsRows.push(['هزینه صدور مجوزهای واردات', 'مبلغ دستی وارد شده (درهم × نرخ ارز آزاد)', permits]);
  customsRows.push(['انبارداری، دموراژ و THC','مبلغ دستی وارد شده', storage]);
  const sumCustomsAll = sumCustoms10 + seaFreight + permits + storage;

  const plateRows = [
    ['خرید گواهی اسقاط', `${(r.scrapCert*100).toFixed(2)}٪ از ارزش گمرکی`, r.scrapCert*CIF],
    ['عوارض شماره‌گذاری راهور', `${(r.plateReg*100).toFixed(2)}٪ از ارزش گمرکی`, r.plateReg*CIF],
    ['مالیات نقل و انتقال', `${(r.transferTax*100).toFixed(2)}٪ از ارزش گمرکی`, r.transferTax*CIF],
    ['عوارض سالانه شهرداری', `${(r.municipal*100).toFixed(2)}٪ از ارزش گمرکی`, r.municipal*CIF],
    ['عوارض شخص حقیقی', `${(r.individual*100).toFixed(2)}٪ از ارزش گمرکی`, r.individual*CIF],
  ];
  const sumPlate = plateRows.reduce((s,row)=>s+row[2],0);

  const totalNoProfit = sumCustomsAll + sumPlate + realPriceToman;
  const serviceProfitAmt = r.serviceProfit * (sumCustoms10 + sumPlate + seaFreight + permits);
  const totalWithProfit = totalNoProfit + serviceProfitAmt;

  const rowHtml = (row,i) => `<tr>
      <td class="idx">${i+1}</td>
      <td data-label="شرح هزینه">${row[0]}</td>
      <td class="rate" data-label="نرخ">${row[1]}</td>
      <td class="amt num-font" data-label="مبلغ (تومان)">${fmt(row[2])}</td>
    </tr>`;

  document.getElementById('tblCustoms').innerHTML = customsRows.map(rowHtml).join('');
  document.getElementById('sumCustomsCell').textContent = fmt(sumCustomsAll);
  document.getElementById('tblPlate').innerHTML = plateRows.map(rowHtml).join('');
  document.getElementById('sumPlateCell').textContent = fmt(sumPlate);

  document.getElementById('s_noProfit').textContent = fmt(totalNoProfit);
  document.getElementById('s_profit').textContent = fmt(serviceProfitAmt);
  document.getElementById('s_total').textContent = fmt(totalWithProfit);
  document.getElementById('stampVal').textContent = fmt(totalWithProfit);

  const palette = ['#2952E0','#FF8A1E','#8B5CF6','#5B6478','#16A34A','#9FB2FF'];
  renderDonut(document.getElementById('donutWrap'), [
    {label:'سود بازرگانی', value:dutyProfit, color:palette[0]},
    {label:'سایر حقوق و عوارض گمرکی (شامل حمل و مجوز)', value:sumCustomsAll-dutyProfit, color:palette[1]},
    {label:'پلاک انتظامی', value:sumPlate, color:palette[2]},
    {label:'قیمت خودرو', value:realPriceToman, color:palette[3]},
    {label:'کارمزد ترخیص‌کار و کارگزار (ناوراکار)', value:serviceProfitAmt, color:palette[5]},
  ]);
  renderBars(document.getElementById('barRows'), [
    {label:'ترخیص گمرکی (شامل حمل و مجوز)', value:sumCustomsAll, color:palette[0]},
    {label:'پلاک انتظامی', value:sumPlate, color:palette[1]},
    {label:'قیمت خودرو', value:realPriceToman, color:palette[3]},
    {label:'کارمزد ترخیص‌کار', value:serviceProfitAmt, color:palette[4]},
  ]);

  renderMiniGauges(document.getElementById('miniGaugeGrid'), [
    {label:'قیمت خودرو', icon:miniIcons.car, color:'#3E6BFF', pct:(realPriceToman/totalWithProfit)*100},
    {label:'ترخیص گمرکی', icon:miniIcons.customs, color:'#FF8A1E', pct:(sumCustomsAll/totalWithProfit)*100},
    {label:'پلاک انتظامی', icon:miniIcons.plate, color:'#8B5CF6', pct:(sumPlate/totalWithProfit)*100},
    {label:'کارمزد ترخیص‌کار', icon:miniIcons.profit, color:'#16A34A', pct:(serviceProfitAmt/totalWithProfit)*100},
  ]);

  lastBreakdownRows = [
    ...customsRows.map(row=>({label:row[0], rate:row[1], amount:fmt(row[2])+' تومان'})),
    ...plateRows.map(row=>({label:row[0], rate:row[1], amount:fmt(row[2])+' تومان'})),
    {label:'قیمت خودرو (اصل کالا)', rate:'-', amount:fmt(realPriceToman)+' تومان'},
  ];
  lastTotals = {
    'جمع کل بدون کارمزد': fmt(totalNoProfit)+' تومان',
    'کارمزد ترخیص‌کار و کارگزار (ناوراکار)': fmt(serviceProfitAmt)+' تومان',
    'جمع کل نهایی': fmt(totalWithProfit)+' تومان',
  };
  lastRawValues = {
    car: getSelectedCarLabel(),
    category: activeCat.label,
    realPriceAED, customsPriceAED, freeRate, customsRate, seaFreightAED, permitsAED, storage,
    sumCustoms: sumCustomsAll, sumPlate,
    totalNoProfit, serviceProfit: serviceProfitAmt, totalWithProfit,
  };
  updateWhatsAppLinks();
}

document.querySelectorAll('input').forEach(inp=>inp.addEventListener('input', calc));

/* ---- VIN / Chassis-number lookup ----
   Primary: public NHTSA vPIC API (free, no key). Its coverage is strongest for
   North-American-pattern VINs, so for many GCC/JDM/EU-spec vehicles it returns
   an empty Make/Model. Fallback: decode the WMI (first 1-3 characters) locally
   using the standard ISO 3780 region-code table plus a small set of well-known
   manufacturer WMI prefixes, so a country (and sometimes brand) is still shown. */
let lastVinDecoded = '';

const wmiRegionMap = {
  '1':'آمریکا','4':'آمریکا','5':'آمریکا','2':'کانادا','3':'مکزیک',
  '6':'استرالیا','7':'نیوزیلند','8':'آرژانتین','9':'برزیل',
  'J':'ژاپن','K':'کره جنوبی','L':'چین','S':'بریتانیا',
  'V':'فرانسه/اسپانیا','W':'آلمان','Z':'ایتالیا','Y':'سوئد/فنلاند',
  'T':'سوئیس/چک','U':'دانمارک/لهستان','R':'تایوان','N':'ترکیه','M':'هند/اندونزی',
};
const wmiBrandMap = {
  'WBA':'BMW','WBS':'BMW','WBY':'BMW','WDB':'Mercedes-Benz','WDD':'Mercedes-Benz','WDC':'Mercedes-Benz',
  'WVW':'Volkswagen','WV1':'Volkswagen','WV2':'Volkswagen','WAU':'Audi','WMW':'Mini',
  'JTD':'Toyota','JTM':'Toyota','JTE':'Toyota','JTN':'Toyota','JHM':'Honda','JH4':'Honda',
  'JN1':'Nissan','JN8':'Nissan','JN6':'Nissan','JM1':'Mazda','JM3':'Mazda','JS2':'Suzuki','JS3':'Suzuki',
  'KMH':'Hyundai','KMF':'Hyundai','KNA':'Kia','KND':'Kia','SAJ':'Jaguar','SAL':'Land Rover',
  'YV1':'Volvo','YV4':'Volvo','VF1':'Renault','VF7':'Citroen','VF3':'Peugeot',
  'ZFA':'Fiat','ZAR':'Alfa Romeo','TMB':'Skoda','VSS':'Cupra',
};

function decodeWmiFallback(vin){
  const region = wmiRegionMap[vin[0]] || 'نامشخص';
  const brand = wmiBrandMap[vin.substring(0,3)] || '';
  return { region, brand };
}

function logVinCheck(payload){
  fetch(VIN_LOG_URL, {
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN},
    body: JSON.stringify(payload)
  }).catch(()=>{});
}

document.getElementById('vinCheckBtn').addEventListener('click', async ()=>{
  const vinRaw = document.getElementById('vinInput').value.trim().toUpperCase();
  const resultEl = document.getElementById('vinResult');
  const vinValid = /^[A-HJ-NPR-Z0-9]{17}$/.test(vinRaw);

  if(!vinValid){
    resultEl.innerHTML = '<div class="vin-msg err">شماره شاسی باید دقیقاً ۱۷ کاراکتر معتبر باشد (بدون حروف I، O، Q).</div>';
    return;
  }
  resultEl.innerHTML = '<div class="vin-msg">در حال استعلام از پایگاه داده خودرویی...</div>';

  let make = '', model = '', year = '', plantCountry = '', usedFallback = false;

  try{
    const res = await fetch(`https://vpic.nhtsa.dot.gov/api/vehicles/decodevinvalues/${vinRaw}?format=json`);
    const data = await res.json();
    const info = data.Results && data.Results[0];
    if(info){
      make = (info.Make || '').trim();
      model = (info.Model || '').trim();
      year = (info.ModelYear || '').trim();
      plantCountry = (info.PlantCountry || '').trim();
    }
  }catch(e){ /* fall through to local fallback below */ }

  if(!make){
    const fb = decodeWmiFallback(vinRaw);
    usedFallback = true;
    make = fb.brand;
    plantCountry = plantCountry || fb.region;
  }

  const isUS = /UNITED STATES|USA|آمریکا/i.test(plantCountry) || /UNITED STATES|USA/i.test(make);
  const brandAllowed = make && allowedBrandsLower.includes(make.toLowerCase());

  let statusHtml, verdict;
  if(!make && !plantCountry){
    statusHtml = `<div class="vin-msg warn">⚠️ این شماره شاسی قابل شناسایی نبود. لطفاً شماره را بررسی و دوباره وارد کنید، یا با کارشناسان تماس بگیرید.</div>`;
    verdict = 'نامشخص';
  } else if(isUS){
    statusHtml = `<div class="vin-msg err">🚫 این خودرو ${plantCountry ? ('ساخت ' + plantCountry) : 'ساخت آمریکا'} است. طبق قوانین این طرح، خودروهای ساخت یا وارداتی آمریکا امکان ثبت ندارند.</div>`;
    verdict = 'غیرمجاز (آمریکا)';
  } else if(brandAllowed){
    statusHtml = `<div class="vin-msg ok">✅ برند «${make}» در فهرست خودروهای مجاز طرح ناوراکار قرار دارد.</div>`;
    verdict = 'مجاز';
  } else if(make){
    statusHtml = `<div class="vin-msg warn">⚠️ برند «${make}» در فهرست خودروهای مجاز این طرح یافت نشد. لطفاً حتماً با کارشناسان ناوراکار تماس بگیرید.</div>`;
    verdict = 'نامشخص - خارج از لیست';
  } else {
    statusHtml = `<div class="vin-msg warn">⚠️ برند خودرو به‌صورت خودکار قابل تشخیص نبود؛ فقط کشور سازنده تخمین زده شد. لطفاً با کارشناسان تماس بگیرید.</div>`;
    verdict = 'نامشخص';
  }

  resultEl.innerHTML = `
    <div class="vin-info">
      <div><b>برند:</b> ${make || 'نامشخص'}</div>
      <div><b>مدل:</b> ${model || '-'}</div>
      <div><b>سال ساخت:</b> ${year || '-'}</div>
      <div><b>کشور سازنده:</b> ${plantCountry || 'نامشخص'}</div>
    </div>
    ${statusHtml}
    <div class="vin-disclaimer">${usedFallback ? 'برند/کشور از روی کد شاسی (WMI) تخمین زده شد چون این VIN در پایگاه NHTSA ثبت کامل نداشت. ' : 'منبع: پایگاه داده NHTSA. '}این نتیجه صرفاً یک برآورد اولیه است؛ برای تعیین قطعی حتماً با کارشناسان ناوراکار تماس بگیرید.</div>
  `;

  if(make){ lastVinDecoded = `${make}${model ? ' ' + model : ''}${year ? ' (' + year + ')' : ''} — از VIN`; }

  logVinCheck({ vin: vinRaw, make, model, year, plantCountry, verdict, source: usedFallback ? 'wmi_fallback' : 'nhtsa' });
});

/* ---- Quote-request form submission ---- */
document.getElementById('qSubmitBtn').addEventListener('click', async ()=>{
  const name = document.getElementById('qName').value.trim();
  const phone = document.getElementById('qPhone').value.trim();
  const email = document.getElementById('qEmail').value.trim();
  const notes = document.getElementById('qNotes').value.trim();
  const btn = document.getElementById('qSubmitBtn');
  const statusEl = document.getElementById('qStatus');

  if(!name || !phone){
    statusEl.textContent = 'لطفاً نام و شماره تماس را وارد کنید.';
    statusEl.className = 'qf-status err';
    return;
  }

  const payload = {
    name, phone, email, notes,
    car: getSelectedCarLabel(),
    category: activeCat.label,
    breakdown: lastBreakdownRows,
    totals: lastTotals,
    website: document.getElementById('qWebsite').value,
    pageLoadedAt: window.__pageLoadedAt || 0,
  };

  btn.disabled = true;
  statusEl.textContent = 'در حال ارسال...';
  statusEl.className = 'qf-status';

  try{
    const res = await fetch(QUOTE_URL, {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF_TOKEN},
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if(data.success){
      statusEl.textContent = 'درخواست شما با موفقیت ارسال شد. کارشناسان ناوراکار به‌زودی با شما تماس می‌گیرند.';
      statusEl.className = 'qf-status ok';
    } else {
      statusEl.textContent = data.message || 'ارسال درخواست ناموفق بود. لطفاً دوباره تلاش کنید.';
      statusEl.className = 'qf-status err';
    }
  } catch(err){
    statusEl.textContent = 'خطا در ارتباط با سرور. لطفاً بعداً دوباره تلاش کنید.';
    statusEl.className = 'qf-status err';
  } finally {
    btn.disabled = false;
  }
});

/* ---- WhatsApp direct-contact message builder ---- */
function buildWhatsAppMessage(){
  const name = document.getElementById('qName')?.value.trim() || '';
  const phone = document.getElementById('qPhone')?.value.trim() || '';
  const email = document.getElementById('qEmail')?.value.trim() || '';
  const notes = document.getElementById('qNotes')?.value.trim() || '';
  let msg = 'سلام، درخواست استعلام قیمت واردات خودرو از سایت ناوراکار دارم.\n';
  if(name) msg += `نام: ${name}\n`;
  if(phone) msg += `تلفن: ${phone}\n`;
  if(email) msg += `ایمیل: ${email}\n`;
  if(lastRawValues && lastRawValues.car && lastRawValues.car !== 'مشخص نشده'){
    msg += `خودرو: ${lastRawValues.car}\n`;
    msg += `دسته: ${lastRawValues.category}\n`;
    msg += `جمع کل تخمینی: ${fmt(lastRawValues.totalWithProfit)} تومان\n`;
  }
  if(notes) msg += `توضیحات: ${notes}\n`;
  return msg;
}
function updateWhatsAppLinks(){
  const msg = encodeURIComponent(buildWhatsAppMessage());
  const uaeHref = 'https://wa.me/' + waDigits(CONTACT_UAE) + '?text=' + msg;
  const iranHref = 'https://wa.me/' + waDigits(CONTACT_IRAN) + '?text=' + msg;
  const a1 = document.getElementById('whatsappBtnFinal');
  const a2 = document.getElementById('whatsappBtnContact');
  const a3 = document.getElementById('waIranFinal');
  const a4 = document.getElementById('waIranContact');
  if(a1) a1.href = uaeHref;
  if(a2) a2.href = uaeHref;
  if(a3) a3.href = iranHref;
  if(a4) a4.href = iranHref;
}
['qName','qPhone','qEmail','qNotes'].forEach(id=>{
  document.getElementById(id).addEventListener('input', updateWhatsAppLinks);
});
updateWhatsAppLinks();

function buildPrintSheet(){
  calc();

  const now = new Date();
  const dateStr = now.getFullYear() + '/' + String(now.getMonth()+1).padStart(2,'0') + '/' + String(now.getDate()).padStart(2,'0');
  document.getElementById('psMeta').innerHTML = `تاریخ گزارش: <b>${dateStr}</b><br>دسته خودرو: <b>${activeCat.label}</b><br>🇮🇷 ${CONTACT_IRAN}<br>🇦🇪 ${CONTACT_UAE}<br>☎️ ${CONTACT_TEHRAN}<br>navracar.com`;

  const carLabel = getSelectedCarLabel();
  const realPriceAED = getPriceAED('realPriceAED');
  const realPriceToman = realPriceAED * num('freeRate');
  const realPriceRial = realPriceToman * 10;

  let carHtml = '<div class="ps-car-title">مشخصات خودرو</div><div class="ps-car-grid">';
  carHtml += `<div><span>خودروی انتخابی</span>${carLabel}</div>`;
  carHtml += `<div><span>دسته خودرو</span>${activeCat.label}</div>`;
  if(selectedVariant){
    carHtml += `<div><span>حجم موتور</span>${selectedVariant.cc ? fmt(selectedVariant.cc) + ' سی‌سی' : 'برقی / هیبرید'}</div>`;
    carHtml += `<div><span>نوع سوخت</span>${selectedVariant.fuel==='electric'?'برقی':selectedVariant.fuel==='hybrid'?'هیبرید':'بنزینی'}</div>`;
    carHtml += `<div><span>سال ساخت</span>${yearSel.value} (میلادی)</div>`;
  }
  carHtml += `<div><span>قیمت خودرو (درهم)</span>${fmt(realPriceAED)} AED</div>`;
  carHtml += `<div><span>قیمت خودرو (معادل ریالی)</span>${fmt(realPriceToman)} تومان — ${fmt(realPriceRial)} ریال</div>`;
  carHtml += '</div>';
  document.getElementById('psCarBox').innerHTML = carHtml;

  function cloneTable(sourceId){
    const rows = Array.from(document.querySelectorAll('#'+sourceId+' tr')).map(tr=>{
      const tds = tr.querySelectorAll('td');
      return `<tr><td>${tds[1].textContent}</td><td style="color:#6B6584;font-size:.78rem;">${tds[2].textContent}</td><td class="amt">${tds[3].textContent}</td></tr>`;
    }).join('');
    return '<thead><tr><th>شرح هزینه</th><th>نرخ</th><th>مبلغ</th></tr></thead><tbody>' + rows + '</tbody>';
  }
  document.getElementById('psCustomsTable').innerHTML = cloneTable('tblCustoms');
  document.getElementById('psPlateTable').innerHTML = cloneTable('tblPlate');

  const totalsEntries = Object.entries(lastTotals);
  document.getElementById('psTotalsTable').innerHTML = totalsEntries.map(([label,val],i)=>
    `<tr class="${i===totalsEntries.length-1?'total':''}"><td>${label}</td><td class="amt">${val}</td></tr>`
  ).join('');
}

function printReport(){
  buildPrintSheet();
  setTimeout(()=>window.print(), 150);
}

/* ==================== Wizard state machine ==================== */
const wizardSteps = ['start','details','pricing','result','final'];
const wizardLabels = {start:'روش', details:'خودرو', pricing:'قیمت', result:'نتیجه', final:'درخواست'};
let wizardMode = null; // 'vin' | 'model' | 'cc'
let currentStep = 'start';

function renderWizProgress(){
  const wrap = document.getElementById('wizProgress');
  if(currentStep === 'contact'){ wrap.innerHTML = ''; return; }
  const curIdx = wizardSteps.indexOf(currentStep);
  wrap.innerHTML = wizardSteps.map((s,i)=>{
    const dotClass = i < curIdx ? 'done' : (i === curIdx ? 'current' : '');
    const dot = `<div class="wiz-dot ${dotClass}" title="${wizardLabels[s]}">${i+1}</div>`;
    const line = i < wizardSteps.length-1 ? `<div class="wiz-dot-line ${i < curIdx ? 'done':''}"></div>` : '';
    return dot + line;
  }).join('');
}

function applyDetailsCardVisibility(){
  document.getElementById('detailsVinCard').style.display = (wizardMode==='vin') ? 'block' : 'none';
  document.getElementById('detailsModelCard').style.display = (wizardMode==='model') ? 'block' : 'none';
  document.getElementById('detailsCcCard').style.display = (wizardMode==='cc') ? 'block' : 'none';

  const sub = document.getElementById('catCardSub');
  const summary = document.getElementById('catEditSummary');
  const details = document.getElementById('catEditDetails');
  if(wizardMode === 'cc'){
    sub.textContent = 'دسته حجم موتور خودروی خود را از گزینه‌های زیر انتخاب کنید.';
    summary.textContent = 'انتخاب دسته موتور';
    details.open = true;
  } else {
    sub.textContent = 'به‌صورت خودکار از روی انتخاب شما تعیین می‌شود.';
    summary.textContent = 'دسته درست نیست؟ دستی انتخاب کنید';
    details.open = false;
  }
}

function switchModeTo(mode){
  wizardMode = mode;
  applyDetailsCardVisibility();
  goToStep('details');
}

function goToStep(step){
  currentStep = step;
  document.querySelectorAll('.wiz-step').forEach(el=>el.classList.remove('active'));
  document.querySelector(`.wiz-step[data-step="${step}"]`).classList.add('active');
  renderWizProgress();

  const isStart = step === 'start';
  document.querySelector('.process-strip').classList.toggle('wiz-hidden', !isStart);
  document.querySelector('.hero-band').classList.toggle('wiz-hidden', !isStart);
  document.querySelector('.hero-disclaimer-wrap').classList.toggle('wiz-hidden', !isStart);

  const nav = document.getElementById('wizNav');
  const prevBtn = document.getElementById('wizPrevBtn');
  const nextBtn = document.getElementById('wizNextBtn');

  const vinOnlyScreen = (step === 'details' && wizardMode === 'vin');

  if(step === 'start' || step === 'contact' || vinOnlyScreen){
    nav.classList.add('hidden');
  } else {
    nav.classList.remove('hidden');
    prevBtn.disabled = false;
    nextBtn.style.display = (step === 'final') ? 'none' : 'flex';
    if(step === 'pricing'){ nextBtn.innerHTML = 'مشاهده نتیجه <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>'; }
    else if(step === 'result'){ nextBtn.innerHTML = 'ادامه به درخواست نهایی <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>'; }
    else { nextBtn.innerHTML = 'بعدی <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>'; }
  }
  const target = isStart ? document.querySelector('header.site') : document.querySelector('.wiz-wrap');
  target?.scrollIntoView({behavior:'smooth', block:'start'});
}

document.querySelectorAll('.start-opt').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const mode = btn.dataset.mode;
    if(mode === 'contact'){
      updateWhatsAppLinks();
      goToStep('contact');
      return;
    }
    switchModeTo(mode);
  });
});

document.getElementById('wizPrevBtn').addEventListener('click', ()=>{
  const idx = wizardSteps.indexOf(currentStep);
  if(idx <= 1){ goToStep('start'); return; }
  goToStep(wizardSteps[idx-1]);
});

document.getElementById('wizNextBtn').addEventListener('click', async ()=>{
  const idx = wizardSteps.indexOf(currentStep);
  if(currentStep === 'pricing'){
    calc();
    await logCalcNow();
  }
  if(idx < wizardSteps.length-1){ goToStep(wizardSteps[idx+1]); }
});

function resetWizard(){
  wizardMode = null;
  document.getElementById('vinInput').value = '';
  document.getElementById('vinResult').innerHTML = '';
  document.getElementById('manualCC').value = '';
  document.getElementById('manualIsHybrid').checked = false;
  carSearchInput.value = '';
  carPickedTag.style.display = 'none';
  selectedBrand = null; selectedModel = null; selectedVariant = null;
  carVariantWrap.style.display = 'none';
  carListingsMatchWrap.style.display = 'none';
  document.getElementById('qName').value = '';
  document.getElementById('qPhone').value = '';
  document.getElementById('qEmail').value = '';
  document.getElementById('qNotes').value = '';
  document.getElementById('qStatus').textContent = '';
  selectCategoryById('c1500');
  goToStep('start');
}

calc();
goToStep('start');
</script>
</body>
</html>
