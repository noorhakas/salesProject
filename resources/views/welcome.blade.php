<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Horizon — Sales Management Platform</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,700;9..144,800;9..144,900&family=Inter:wght@400;500;600;700&family=Space+Mono&display=swap" rel="stylesheet">

<style>
  :root{
    --navy: #0E1A2B;
    --navy-2: #142338;
    --gold: #C9A24B;
    --emerald: #1E7A5F;
    --paper: #F3EEE2;
    --slate: #93A0B4;
    --line: rgba(243,238,226,0.10);
  }

  *{box-sizing:border-box; margin:0; padding:0;}

  html{scroll-behavior:smooth;}

  body{
    background: var(--navy);
    color: var(--paper);
    font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
    line-height: 1.7;
    overflow-x: hidden;
  }

  h1,h2,h3, .display{
    font-family: 'Fraunces', 'Segoe UI', Georgia, serif;
  }

  .mono{
    font-family: 'Space Mono', 'Segoe UI', monospace;
  }

  @media (prefers-reduced-motion: reduce){
    *{animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important;}
  }

  a{ color: inherit; }
  a:focus-visible, button:focus-visible{
    outline: 2px solid var(--gold);
    outline-offset: 3px;
  }

  /* ---------- background texture ---------- */
  .bg-grid{
    position: fixed;
    inset: 0;
    background-image:
      linear-gradient(var(--line) 1px, transparent 1px),
      linear-gradient(90deg, var(--line) 1px, transparent 1px);
    background-size: 64px 64px;
    mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, black 40%, transparent 90%);
    pointer-events: none;
    z-index: 0;
  }

  .wrap{
    max-width: 1120px;
    margin: 0 auto;
    padding: 0 28px;
    position: relative;
    z-index: 1;
  }

  /* ---------- top bar ---------- */
  .topbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding: 26px 0;
  }

  .brand{
    display:flex;
    align-items:center;
    gap:12px;
    font-weight:800;
    font-size: 20px;
    letter-spacing: 0.3px;
    font-family: 'Fraunces', serif;
  }

  .brand-mark{
    width: 34px; height: 34px;
    border-radius: 8px;
    background: linear-gradient(155deg, var(--gold), var(--emerald));
    display:flex; align-items:center; justify-content:center;
    font-family:'Space Mono', monospace;
    color: var(--navy);
    font-weight:700;
    font-size: 16px;
  }

  .topbar nav{
    display:flex;
    gap: 28px;
    font-size: 14px;
    color: var(--slate);
  }

  .topbar nav a{
    transition: color .2s ease;
    border-bottom: 1px solid transparent;
    padding-bottom: 2px;
    text-decoration: none;
  }
  .topbar nav a:hover{ color: var(--paper); border-color: var(--gold); }

  /* ---------- hero ---------- */
  .hero{
    padding: 72px 0 60px;
    display:grid;
    grid-template-columns: 1.15fr 1fr;
    gap: 48px;
    align-items:center;
  }

  .eyebrow{
    display:inline-flex;
    align-items:center;
    gap:8px;
    font-size: 13px;
    color: var(--gold);
    letter-spacing: 1px;
    margin-bottom: 22px;
    text-transform: uppercase;
  }
  .eyebrow::before{
    content:"";
    width: 22px; height:1px;
    background: var(--gold);
  }

  .hero h1{
    font-size: clamp(34px, 4.6vw, 56px);
    font-weight: 900;
    line-height: 1.2;
    margin-bottom: 22px;
  }
  .hero h1 em{
    font-style: italic;
    color: var(--gold);
  }

  .hero p{
    color: var(--slate);
    font-size: 17px;
    max-width: 46ch;
    margin-bottom: 32px;
  }

  .cta-row{
    display:flex;
    gap: 14px;
    flex-wrap: wrap;
  }

  .btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding: 13px 26px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 15px;
    text-decoration:none;
    transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
    border: 1px solid transparent;
    cursor:pointer;
  }
  .btn-primary{
    background: var(--gold);
    color: var(--navy);
  }
  .btn-primary:hover{
    transform: translateY(-2px);
    box-shadow: 0 10px 24px -8px rgba(201,162,75,0.55);
  }
  .btn-ghost{
    border-color: var(--line);
    color: var(--paper);
  }
  .btn-ghost:hover{
    border-color: var(--gold);
    color: var(--gold);
  }

  /* ---------- signature: ascending sales line ---------- */
  .signature{
    position:relative;
    background: var(--navy-2);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 24px 22px 18px;
  }

  .signature-head{
    display:flex;
    justify-content:space-between;
    align-items:baseline;
    margin-bottom: 6px;
  }

  .signature-head .label{
    font-size: 12px;
    color: var(--slate);
    letter-spacing: 0.5px;
  }

  .signature-head .value{
    font-family:'Space Mono', monospace;
    font-size: 26px;
    font-weight:700;
    color: var(--emerald);
  }

  .chart-svg{ width: 100%; height: 200px; display:block; }

  .chart-path{
    stroke-dasharray: 900;
    stroke-dashoffset: 900;
    animation: draw 1.8s ease forwards 0.3s;
  }
  .chart-fill{
    opacity: 0;
    animation: fadein 1.2s ease forwards 1.3s;
  }
  .chart-dot{
    opacity:0;
    animation: fadein .5s ease forwards 2s;
  }

  @keyframes draw{ to{ stroke-dashoffset: 0; } }
  @keyframes fadein{ to{ opacity:1; } }

  .signature-foot{
    display:flex;
    justify-content:space-between;
    font-size: 12px;
    color: var(--slate);
    margin-top: 6px;
    font-family: 'Space Mono', monospace;
  }

  /* ---------- stat strip ---------- */
  .stats{
    border-top: 1px solid var(--line);
    border-bottom: 1px solid var(--line);
    padding: 34px 0;
    display:grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
  }
  .stat b{
    display:block;
    font-family:'Space Mono', monospace;
    font-size: 28px;
    color: var(--gold);
  }
  .stat span{
    font-size: 13px;
    color: var(--slate);
  }

  /* ---------- features ---------- */
  .section-head{
    padding: 64px 0 8px;
  }
  .section-head .eyebrow{ margin-bottom: 12px; }
  .section-head h2{
    font-size: 30px;
    font-weight: 800;
    max-width: 30ch;
  }

  .features{
    display:grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1px;
    background: var(--line);
    border: 1px solid var(--line);
    border-radius: 16px;
    overflow: hidden;
    margin: 32px 0 20px;
  }

  .feature{
    background: var(--navy);
    padding: 30px 28px;
    transition: background .2s ease;
  }
  .feature:hover{ background: var(--navy-2); }

  .feature .icon{
    width: 40px; height:40px;
    border-radius: 10px;
    background: rgba(201,162,75,0.12);
    color: var(--gold);
    display:flex; align-items:center; justify-content:center;
    margin-bottom: 18px;
  }
  .feature h3{
    font-size: 17px;
    font-weight: 700;
    margin-bottom: 10px;
    font-family: 'Inter', sans-serif;
  }
  .feature p{
    color: var(--slate);
    font-size: 14px;
  }

  /* ---------- footer ---------- */
  footer{
    border-top: 1px solid var(--line);
    padding: 26px 0 40px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    color: var(--slate);
    font-size: 13px;
    flex-wrap: wrap;
    gap: 12px;
  }
  footer a{ text-decoration: underline; text-underline-offset: 3px; }
  footer a:hover{ color: var(--gold); }
  .footer-links{ display:flex; gap: 22px; }

  /* ---------- responsive ---------- */
  @media (max-width: 860px){
    .hero{ grid-template-columns: 1fr; padding-top: 40px; }
    .stats{ grid-template-columns: repeat(2,1fr); }
    .features{ grid-template-columns: 1fr; }
    .topbar nav{ display:none; }
  }
</style>
</head>
<body>

<div class="bg-grid"></div>

<div class="wrap">

  <div class="topbar">
    <div class="brand">
      <div class="brand-mark">H</div>
       Sales
    </div>
    <nav>
      <a href="#features">Features</a>
      <a href="#stats">Numbers</a>
      <a href="#">Docs</a>
      <a href="#">Contact</a>
    </nav>
  </div>

  <section class="hero">
    <div>
      <div class="eyebrow">A complete sales management platform</div>
      <h1>Your sales, in full view — <em>from first order to final report</em></h1>
      <p>One platform to manage products, orders, customers, and invoices, with live reports that help you decide with the numbers right in front of you.</p>
      <div class="cta-row">
        <a href="#" class="btn btn-primary">Start free</a>
        <a href="#features" class="btn btn-ghost">Explore features</a>
      </div>
    </div>

    <div class="signature">
      <div class="signature-head">
        <span class="label">Total sales this month</span>
        <span class="value mono">+38%</span>
      </div>
      <svg class="chart-svg" viewBox="0 0 400 200" preserveAspectRatio="none">
        <defs>
          <linearGradient id="fillGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#1E7A5F" stop-opacity="0.45"/>
            <stop offset="100%" stop-color="#1E7A5F" stop-opacity="0"/>
          </linearGradient>
        </defs>
        <path class="chart-fill" fill="url(#fillGrad)" d="M0,170 L40,150 L80,155 L120,120 L160,130 L200,95 L240,100 L280,65 L320,72 L360,35 L400,40 L400,200 L0,200 Z"/>
        <path class="chart-path" fill="none" stroke="#C9A24B" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"
          d="M0,170 L40,150 L80,155 L120,120 L160,130 L200,95 L240,100 L280,65 L320,72 L360,35 L400,40"/>
        <circle class="chart-dot" cx="400" cy="40" r="5" fill="#C9A24B"/>
      </svg>
      <div class="signature-foot">
        <span>Jan</span><span>Mar</span><span>May</span><span>Jul</span>
      </div>
    </div>
  </section>

  <section class="stats" id="stats">
    <div class="stat"><b class="mono">1,240+</b><span>Sales this month</span></div>
    <div class="stat"><b class="mono">312</b><span>Active customers</span></div>
    <div class="stat"><b class="mono">98.4%</b><span>Inventory accuracy</span></div>
    <div class="stat"><b class="mono">4.2 min</b><span>Avg. invoice time</span></div>
  </section>

  <section id="features">
    <div class="section-head">
      <div class="eyebrow">Everything in one place</div>
      <h2>Four pillars the whole sales process runs on</h2>
    </div>

    <div class="features">
      <div class="feature">
        <div class="icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16V8z"></path><path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"></path></svg>
        </div>
        <h3>Products &amp; inventory</h3>
        <p>Track quantities, pricing, and low-stock alerts in real time, with every product tied to its actual sales activity.</p>
      </div>

      <div class="feature">
        <div class="icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"></path><rect x="9" y="3" width="6" height="4" rx="1"></rect><path d="M9 12h6M9 16h4"></path></svg>
        </div>
        <h3>Orders &amp; invoices</h3>
        <p>From order to invoice in minutes, with a full archive of every past transaction.</p>
      </div>

      <div class="feature">
        <div class="icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 20V10M12 20V4M6 20v-6"></path></svg>
        </div>
        <h3>Reports &amp; analytics</h3>
        <p>Dashboards that show how products, teams, and branches are performing — so you spot opportunities early.</p>
      </div>

      <div class="feature">
        <div class="icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"></path></svg>
        </div>
        <h3>Customer management</h3>
        <p>A full record for every customer: purchases, preferences, and communication history with your sales team.</p>
      </div>
    </div>
  </section>

  <footer>
    <div class="footer-links">
      <a href="#">Docs</a>
      <a href="#">Support</a>
      <a href="#">Contact</a>
    </div>
    <div class="mono">Horizon v1.0</div>
  </footer>

</div>

</body>
</html>