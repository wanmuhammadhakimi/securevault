<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SecureVault — Vault Access</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg%20xmlns%3D%27http%3A//www.w3.org/2000/svg%27%20viewBox%3D%270%200%2034%2034%27%3E%3Ccircle%20cx%3D%2717%27%20cy%3D%2717%27%20r%3D%2716%27%20fill%3D%27%23080a0d%27/%3E%3Ccircle%20cx%3D%2717%27%20cy%3D%2717%27%20r%3D%2715%27%20fill%3D%27none%27%20stroke%3D%27%2317b8d6%27%20stroke-width%3D%271.4%27/%3E%3Cg%20fill%3D%27none%27%20stroke%3D%27%235ef0ff%27%20stroke-linecap%3D%27round%27%3E%3Cpath%20d%3D%27M9.04%2026.36%20A15.13%2018.91%20-4.0%201%201%2025.04%2025.27%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M9.87%2024.36%20A13.24%2016.54%20-2.7%201%201%2023.18%2023.82%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M10.71%2022.36%20A11.35%2014.18%20-1.4%201%201%2021.55%2022.22%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M11.55%2020.29%20A9.45%2011.82%20-0.1%201%201%2020.05%2020.44%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M12.42%2018.25%20A7.56%209.45%201.2%201%201%2018.82%2018.51%27%20stroke-width%3D%271.0%27/%3E%3Cpath%20d%3D%27M13.29%2016.15%20A5.67%207.09%202.5%201%201%2017.73%2016.47%27%20stroke-width%3D%270.9%27/%3E%3Cpath%20d%3D%27M14.16%2014.07%20A3.78%204.73%203.8%201%201%2016.89%2014.33%27%20stroke-width%3D%270.9%27/%3E%3Cpath%20d%3D%27M15.18%2013.02%20A3.64%202.55%202%201%201%2015.36%208.00%27%20stroke-width%3D%270.75%27%20opacity%3D%270.8%27/%3E%3Cpath%20d%3D%27M14.89%2014.91%20A2.18%201.45%20-3%201%201%2014.02%2012.15%27%20stroke-width%3D%270.75%27%20opacity%3D%270.8%27/%3E%3C/g%3E%3C/svg%3E">
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --graphite: #080a0d;
  --graphite2: #10141b;
  --graphite3: #1a2029;
  --brass: #17b8d6;
  --brass-bright: #5ef0ff;
  --brass-dim: rgba(23,184,214,0.12);
  --steel: #3d5a80;
  --steel-light: #7b93b5;
  --ink: #eef1f5;
  --ink-dim: #8891a3;
  --line: #2a3140;
  --red: #c9432f;
  --red-dim: rgba(201,67,47,0.12);
  --green: #4a9d6f;
  --green-dim: rgba(74,157,111,0.12);
  --display: 'Oswald', sans-serif;
  --body: 'Inter', sans-serif;
  --mono: 'IBM Plex Mono', monospace;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--body);color:var(--ink);background:var(--graphite);-webkit-font-smoothing:antialiased}
button,input,select{font-family:inherit}
button{cursor:pointer}
:focus-visible{outline:2px solid var(--brass);outline-offset:2px}

.screen{display:none;min-height:100vh}
.screen.active{display:block;animation:screenIn .45s cubic-bezier(.16,1,.3,1)}

@keyframes screenIn{
  from{opacity:0;transform:translateY(10px) scale(.99)}
  to{opacity:1;transform:translateY(0) scale(1)}
}
@keyframes pageIn{
  from{opacity:0;transform:translateY(6px)}
  to{opacity:1;transform:translateY(0)}
}
@keyframes pulseRing{
  0%{box-shadow:0 0 0 0 rgba(23,184,214,.35)}
  70%{box-shadow:0 0 0 10px rgba(23,184,214,0)}
  100%{box-shadow:0 0 0 0 rgba(23,184,214,0)}
}
@keyframes checkPop{
  0%{transform:scale(.4);opacity:0}
  60%{transform:scale(1.15);opacity:1}
  100%{transform:scale(1)}
}
@keyframes shakeX{
  0%,100%{transform:translateX(0)}
  20%{transform:translateX(-6px)}
  40%{transform:translateX(5px)}
  60%{transform:translateX(-3px)}
  80%{transform:translateX(2px)}
}
@keyframes rowIn{
  from{opacity:0;transform:translateY(4px)}
  to{opacity:1;transform:translateY(0)}
}
@keyframes countUp{
  from{opacity:0;transform:translateY(3px)}
  to{opacity:1;transform:translateY(0)}
}

/* ── AUTH ── */
.noise{
  position:fixed;inset:0;pointer-events:none;z-index:1;opacity:.035;mix-blend-mode:overlay;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='120' height='120'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
}

/* ── CYBER BACKGROUND (same as landing pages) ── */
.cyber-bg{
  position:absolute;inset:0;z-index:0;pointer-events:none;overflow:hidden;
  background-color:var(--graphite);
  background-image:
    linear-gradient(rgba(123,147,181,.10) 1px, transparent 1px),
    linear-gradient(90deg, rgba(123,147,181,.10) 1px, transparent 1px),
    radial-gradient(ellipse 70% 55% at 15% -5%, rgba(23,184,214,.14), transparent 60%),
    radial-gradient(ellipse 60% 45% at 100% 100%, rgba(61,90,128,.18), transparent 60%);
  background-size: 46px 46px, 46px 46px, 100% 100%, 100% 100%;
  background-position: -1px -1px, -1px -1px, 0 0, 0 0;
}
.cyber-bg::before{
  content:'';position:absolute;left:0;right:0;top:-220px;height:220px;
  background:linear-gradient(180deg, transparent 0%, rgba(23,184,214,.12) 45%, rgba(23,184,214,.04) 55%, transparent 100%);
  animation:cyber-scan 9s linear infinite;
}
.cyber-bg::after{
  content:'';position:absolute;inset:0;
  background-image:repeating-linear-gradient(90deg, rgba(123,147,181,.22) 0 1px, transparent 1px 160px);
  opacity:.5;
  mask-image:linear-gradient(180deg, rgba(0,0,0,.6), transparent 70%);
}
@keyframes cyber-scan{
  0%{transform:translateY(0)}
  100%{transform:translateY(calc(100% + 220px))}
}
.network-bg-svg{
  position:absolute;inset:0;z-index:0;pointer-events:none;width:100%;height:100%;
  opacity:.55;mix-blend-mode:screen;
}
.net-lines line{stroke:var(--steel-light);stroke-width:1;opacity:.22}
.net-bokeh circle{fill:var(--brass-bright);opacity:.3}
.net-bokeh circle:nth-child(3n){fill:var(--steel-light);opacity:.18}
.net-bokeh circle:nth-child(5n){fill:var(--brass);opacity:.4}
.net-ring{fill:none;stroke:var(--brass-bright);stroke-width:1.4;opacity:.55;filter:url(#netGlow)}
.net-ring-pulse{animation:net-pulse 3.4s ease-out infinite;opacity:.5}
.net-node:nth-of-type(2) .net-ring-pulse{animation-delay:.6s}
.net-node:nth-of-type(4) .net-ring-pulse{animation-delay:1.2s}
.net-node:nth-of-type(6) .net-ring-pulse{animation-delay:1.8s}
@keyframes net-pulse{
  0%{r:16;opacity:.55}
  75%{opacity:0}
  100%{r:48;opacity:0}
}
.net-lock{color:var(--brass-bright);filter:url(#netGlow)}
.net-warn{color:var(--brass)}
.net-node-main .net-ring{stroke-width:1.8}

.auth-wrap{
  min-height:100vh;position:relative;overflow:hidden;
  display:flex;align-items:center;justify-content:center;padding:40px 20px;
  background:var(--graphite);
}
.visual-canvas{position:absolute;inset:0;width:100%;height:100%;display:block;z-index:0}
.auth-wrap::after{
  content:'';position:absolute;inset:0;z-index:1;pointer-events:none;
  background:radial-gradient(ellipse 48% 60% at 50% 50%, transparent 0%, var(--graphite) 72%);
}
.auth-card{
  position:relative;z-index:2;border:1px solid rgba(23,184,214,.28);
  border-radius:4px;padding:48px 44px;width:100%;max-width:400px;
  background-color:rgba(16,20,27,.68);
  background-image:
    linear-gradient(rgba(23,184,214,.07) 1px, transparent 1px),
    linear-gradient(90deg, rgba(23,184,214,.07) 1px, transparent 1px);
  background-size:28px 28px;
  backdrop-filter:blur(16px) saturate(150%);
  -webkit-backdrop-filter:blur(16px) saturate(150%);
  box-shadow:0 30px 80px -20px rgba(0,0,0,.6), 0 0 0 1px rgba(23,184,214,.10), 0 0 60px rgba(23,184,214,.12);
  animation:cardIn .5s cubic-bezier(.16,1,.3,1) .1s backwards, holo-float 6s ease-in-out infinite 1s;
}
.auth-card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:2px;
  background:linear-gradient(90deg,transparent,var(--brass),transparent);
  animation:holo-sheen 3.2s ease-in-out infinite;
}
@keyframes holo-float{
  0%,100%{transform:translateY(0)}
  50%{transform:translateY(-6px)}
}
@keyframes holo-sheen{
  0%,100%{opacity:.5}
  50%{opacity:1}
}
.holo-bracket{
  position:absolute;width:32px;height:32px;border:2px solid var(--brass-bright);
  opacity:.8;filter:drop-shadow(0 0 7px rgba(94,240,255,.75));pointer-events:none;
  animation:holo-bracket-pulse 2.4s ease-in-out infinite;
}
.holo-bracket.tl{top:-14px;left:-14px;border-right:none;border-bottom:none}
.holo-bracket.tr{top:-14px;right:-14px;border-left:none;border-bottom:none;animation-delay:.3s}
.holo-bracket.bl{bottom:-14px;left:-14px;border-right:none;border-top:none;animation-delay:.6s}
.holo-bracket.br{bottom:-14px;right:-14px;border-left:none;border-top:none;animation-delay:.9s}
@keyframes holo-bracket-pulse{
  0%,100%{opacity:.6;filter:drop-shadow(0 0 4px rgba(94,240,255,.5))}
  50%{opacity:1;filter:drop-shadow(0 0 10px rgba(94,240,255,.9))}
}

/* overlapping top badge */
.auth-badge{
  position:absolute;top:-26px;left:50%;transform:translateX(-50%);
  width:52px;height:52px;border-radius:50%;background:var(--brass);
  display:flex;align-items:center;justify-content:center;z-index:4;
  box-shadow:0 0 0 4px var(--graphite2), 0 0 26px rgba(23,184,214,.55);
}
.auth-badge svg{width:24px;height:24px}

/* icon-prefixed inputs */
.input-icon-wrap{position:relative}
.input-icon-wrap svg{
  position:absolute;left:14px;top:50%;transform:translateY(-50%);
  width:16px;height:16px;fill:none;stroke:var(--brass);stroke-width:1.8;pointer-events:none;
}
.input-icon-wrap .form-input{padding-left:40px}
@keyframes cardIn{
  from{opacity:0;transform:translateY(18px) scale(.98)}
  to{opacity:1;transform:translateY(0) scale(1)}
}
.visual-tag{
  position:relative;z-index:2;font-family:var(--mono);font-size:11px;color:var(--brass-bright);letter-spacing:1.5px;
  text-transform:uppercase;display:flex;align-items:center;gap:10px;justify-content:center;
  margin-bottom:22px;
}
.visual-tag::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--green);box-shadow:0 0 8px var(--green);animation:blinkDot 2s ease-in-out infinite}
@keyframes blinkDot{0%,100%{opacity:1}50%{opacity:.35}}
.visual-stats{position:relative;z-index:2;display:flex;gap:36px;justify-content:center;margin-top:26px;flex-wrap:wrap}
.visual-stat{text-align:center}
.visual-stat b{font-family:var(--mono);font-size:18px;color:var(--ink);display:block;margin-bottom:3px}
.visual-stat span{font-family:var(--mono);font-size:10.5px;color:var(--ink-dim);text-transform:uppercase;letter-spacing:.5px}
.auth-brand{display:flex;align-items:center;gap:12px;justify-content:center;margin-bottom:30px;animation:fieldIn .5s cubic-bezier(.16,1,.3,1) .2s backwards;text-decoration:none;color:var(--ink);transition:color .2s ease}
.auth-brand:hover{color:var(--brass-bright)}
.back-home{
  position:absolute;top:24px;left:24px;z-index:5;display:inline-flex;align-items:center;gap:7px;
  font-family:var(--mono);font-size:12px;letter-spacing:.5px;color:var(--ink-dim);text-decoration:none;
  transition:color .2s ease;
}
.back-home:hover{color:var(--brass-bright)}
.back-home svg{width:14px;height:14px}
.auth-brand svg{width:32px;height:32px}
.auth-brand span{font-family:var(--display);font-weight:600;font-size:19px;text-transform:uppercase;letter-spacing:.5px}
.auth-card h1{font-family:var(--display);font-size:24px;font-weight:600;text-align:center;text-transform:uppercase;margin-bottom:8px;letter-spacing:.3px;min-height:30px}
.auth-sub{text-align:center;color:var(--ink-dim);font-size:13px;margin-bottom:30px;font-family:var(--mono);animation:fieldIn .5s cubic-bezier(.16,1,.3,1) .3s backwards}

@keyframes fieldIn{
  from{opacity:0;transform:translateY(10px)}
  to{opacity:1;transform:translateY(0)}
}
.form-group{margin-bottom:16px;animation:fieldIn .5s cubic-bezier(.16,1,.3,1) backwards}
#s-login .form-group:nth-of-type(1){animation-delay:.32s}
#s-login .form-group:nth-of-type(2){animation-delay:.38s}
#s-login .btn-brass{animation:fieldIn .5s cubic-bezier(.16,1,.3,1) .44s backwards}
#s-login .divider{animation:fieldIn .5s cubic-bezier(.16,1,.3,1) .5s backwards}
#s-login .bio-box{animation:fieldIn .5s cubic-bezier(.16,1,.3,1) .56s backwards}
#s-login .auth-foot{animation:fieldIn .5s cubic-bezier(.16,1,.3,1) .62s backwards}

.form-label{display:block;font-family:var(--mono);font-size:10.5px;font-weight:500;color:var(--brass);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px}
.form-input{
  width:100%;padding:12px 14px;border:1px solid var(--line);border-radius:2px;
  font-size:14px;background:var(--graphite);color:var(--ink);
  transition:border-color .2s ease, box-shadow .2s ease;
}
.form-input:focus{border-color:var(--brass);box-shadow:0 0 0 3px rgba(23,184,214,.12)}
.form-select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%238891a3'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center}

.btn{
  display:inline-flex;align-items:center;justify-content:center;gap:8px;
  padding:13px 20px;font-family:var(--display);font-weight:500;font-size:13px;
  text-transform:uppercase;letter-spacing:1px;border-radius:2px;
  border:1px solid transparent;transition:background-color .15s ease, box-shadow .15s ease, transform .1s ease;text-decoration:none;width:100%;
}
.btn:active{transform:scale(.97)}
.btn-brass{background:var(--brass);color:var(--graphite)}
.btn-brass:hover{background:var(--brass-bright);box-shadow:0 0 20px rgba(23,184,214,.3)}
.btn-brass:disabled{opacity:.35;cursor:not-allowed;box-shadow:none;transform:none}
.btn-outline{background:transparent;color:var(--ink);border-color:var(--line);transition:border-color .15s ease, transform .1s ease}
.btn-outline:hover{border-color:var(--brass)}
.btn-outline:active{transform:scale(.97)}

.divider{display:flex;align-items:center;gap:12px;margin:24px 0;color:var(--ink-dim);font-family:var(--mono);font-size:10.5px;text-transform:uppercase;letter-spacing:1px}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--line)}

.bio-box{border:1px dashed var(--line);border-radius:2px;padding:24px;text-align:center;transition:border-color .2s ease, transform .1s ease}
.bio-box:hover{border-color:var(--brass)}
.bio-box:active{transform:scale(.98)}
.bio-box.scanning{border-style:solid;border-color:var(--brass);animation:pulseRing 1.6s ease-out infinite}
.bio-box.success{border-style:solid;border-color:var(--green)}
.bio-box.fail{animation:shakeX .4s ease}
.bio-icon{font-size:26px;margin-bottom:8px;display:inline-block}
.bio-icon.pop{animation:checkPop .4s cubic-bezier(.34,1.56,.64,1)}
.bio-status{font-family:var(--mono);font-size:12px;color:var(--ink-dim);transition:color .2s ease}
.bio-status.scanning{color:var(--brass-bright)}
.bio-status.success{color:var(--green)}
.bio-status.fail{color:var(--red)}
.bio-video{width:100%;max-width:280px;border-radius:2px;border:1px solid var(--line);margin:0 auto 12px;display:block;transform:scaleX(-1)}

.auth-foot{text-align:center;margin-top:22px;font-size:13px;color:var(--ink-dim)}
.auth-foot a{color:var(--brass-bright);font-weight:600;text-decoration:none;cursor:pointer}

.step-dots{display:flex;gap:6px;justify-content:center;margin-bottom:26px}
.step-dot{width:34px;height:3px;background:var(--line)}
.step-dot.active{background:var(--brass)}
.step-dot.done{background:var(--green)}

/* ── APP SHELL ── */
.app-shell{display:flex;min-height:100vh}
.sidebar{width:240px;flex-shrink:0;background:var(--graphite2);border-right:1px solid var(--line);display:flex;flex-direction:column;padding:22px 14px}
.sb-brand{display:flex;align-items:center;gap:11px;padding:6px 10px 26px}
.sb-brand svg{width:26px;height:26px}
.sb-brand span{font-family:var(--display);font-weight:600;font-size:16px;text-transform:uppercase;letter-spacing:.4px}
.sb-nav{display:flex;flex-direction:column;gap:2px;flex:1}
.sb-item{
  display:flex;align-items:center;gap:12px;padding:11px 12px;border-radius:2px;
  color:var(--ink-dim);font-size:13px;font-weight:500;border:none;background:transparent;
  text-align:left;width:100%;transition:background-color .15s ease, color .15s ease, transform .1s ease, border-left-color .15s ease;
  text-transform:uppercase;letter-spacing:.3px;border-left:2px solid transparent;
}
.sb-item:hover{background:var(--graphite3);color:var(--ink)}
.sb-item:active{transform:scale(.98)}
.sb-item.active{background:var(--brass-dim);color:var(--brass-bright);border-left-color:var(--brass)}
.sb-icon{width:16px;text-align:center;flex-shrink:0;font-size:14px}
.sb-foot{border-top:1px solid var(--line);padding-top:16px;margin-top:12px}
.sb-user{display:flex;align-items:center;gap:10px;padding:8px 10px;margin-bottom:8px}
.sb-avatar{width:30px;height:30px;border-radius:2px;background:var(--brass);color:var(--graphite);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;font-family:var(--mono);overflow:hidden}
.sb-avatar img{width:100%;height:100%;object-fit:cover;display:block}

/* ── Settings: avatar upload ── */
.avatar-row{display:flex;align-items:center;gap:18px;margin-bottom:22px}
.avatar-preview{
  width:72px;height:72px;border-radius:50%;background:var(--brass);color:var(--graphite);
  display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;
  font-family:var(--mono);overflow:hidden;border:2px solid var(--line);flex-shrink:0;
}
.avatar-preview img{width:100%;height:100%;object-fit:cover;display:block}
.avatar-meta{display:flex;flex-direction:column;gap:8px}
.avatar-meta span{font-size:11.5px;color:var(--ink-dim);font-family:var(--mono)}

/* ── Avatar crop/zoom modal ── */
.crop-viewport{
  width:260px;height:260px;border-radius:50%;overflow:hidden;position:relative;
  margin:0 auto 20px;background:#000;border:2px solid var(--brass);cursor:grab;touch-action:none;
}
.crop-viewport.dragging{cursor:grabbing}
.crop-viewport img{position:absolute;left:0;top:0;max-width:none;user-select:none;-webkit-user-drag:none;pointer-events:none}
.crop-zoom-row{display:flex;align-items:center;gap:12px;margin-bottom:22px}
.crop-zoom-row span{font-size:16px;color:var(--ink-dim)}
.crop-zoom-row input[type="range"]{flex:1;accent-color:var(--brass)}
.crop-actions{display:flex;gap:10px}
.crop-actions .btn{width:auto;flex:1}
.sb-user-name{font-size:12.5px;font-weight:600}
.sb-user-role{font-size:10.5px;color:var(--ink-dim);font-family:var(--mono);text-transform:uppercase}

.main{flex:1;min-width:0}
.topbar{background:var(--graphite2);border-bottom:1px solid var(--line);padding:20px 32px;display:flex;align-items:center;justify-content:space-between}
.topbar h1{font-family:var(--display);font-size:20px;font-weight:600;text-transform:uppercase;letter-spacing:.3px}
.topbar-sub{font-size:12px;color:var(--ink-dim);margin-top:3px;font-family:var(--mono)}
.content{padding:30px 32px}
.page{display:none}
.page.active{display:block;animation:pageIn .35s cubic-bezier(.16,1,.3,1)}

.panel{background:var(--graphite2);border:1px solid var(--line);border-radius:2px;overflow:hidden;margin-bottom:20px}
.panel-head{padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between}
.filter-bar{
  padding:14px 20px;border-bottom:1px solid var(--line);background:var(--graphite);
  display:flex;gap:10px;flex-wrap:wrap;align-items:center;
}
.filter-bar input,.filter-bar select{
  background:var(--graphite2);border:1px solid var(--line);color:var(--ink);
  padding:8px 12px;border-radius:2px;font-size:12.5px;font-family:var(--body);
  transition:border-color .2s ease;
}
.filter-bar input:focus,.filter-bar select:focus{border-color:var(--brass);outline:none}
.filter-bar input[type="text"]{flex:1;min-width:160px}
.filter-bar select{min-width:140px}
.filter-bar input[type="date"]{color-scheme:dark}
.filter-clear{
  background:transparent;border:1px solid var(--line);color:var(--ink-dim);
  padding:8px 14px;border-radius:2px;font-size:11.5px;font-family:var(--mono);
  text-transform:uppercase;letter-spacing:.5px;cursor:pointer;transition:border-color .2s ease, color .2s ease;
}
.filter-clear:hover{border-color:var(--brass);color:var(--brass-bright)}
.filter-count{font-family:var(--mono);font-size:11px;color:var(--ink-dim);margin-left:auto;white-space:nowrap}
.panel-head h3{font-family:var(--display);font-size:14px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}

table{width:100%;border-collapse:collapse}
th{text-align:left;padding:11px 20px;font-family:var(--mono);font-size:10.5px;font-weight:500;color:var(--brass);text-transform:uppercase;letter-spacing:.8px;background:var(--graphite);border-bottom:1px solid var(--line)}
td{padding:13px 20px;font-size:13.5px;border-bottom:1px solid var(--line);color:var(--ink)}
tr:last-child td{border-bottom:none}
tbody tr{animation:rowIn .3s ease backwards}
tbody tr:nth-child(1){animation-delay:.02s}
tbody tr:nth-child(2){animation-delay:.05s}
tbody tr:nth-child(3){animation-delay:.08s}
tbody tr:nth-child(4){animation-delay:.11s}
tbody tr:nth-child(5){animation-delay:.14s}
tbody tr:nth-child(n+6){animation-delay:.16s}
tr:hover td{background:var(--graphite3);transition:background-color .15s ease}
.mono{font-family:var(--mono);font-size:12px;color:var(--ink-dim)}

.badge{padding:3px 9px;border-radius:2px;font-family:var(--mono);font-size:10.5px;font-weight:500;text-transform:uppercase;letter-spacing:.4px;display:inline-block}
.badge-ok{background:var(--green-dim);color:var(--green)}
.badge-warn{background:var(--brass-dim);color:var(--brass-bright)}
.badge-alert{background:var(--red-dim);color:var(--red)}
.badge-info{background:rgba(123,147,181,0.12);color:var(--steel-light)}

.stat-row{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:var(--line);border:1px solid var(--line);margin-bottom:24px}
.stat-card{background:var(--graphite2);padding:20px;animation:countUp .4s ease backwards}
.stat-card:nth-child(1){animation-delay:.02s}
.stat-card:nth-child(2){animation-delay:.07s}
.stat-card:nth-child(3){animation-delay:.12s}
.stat-card:nth-child(4){animation-delay:.17s}
.stat-card b{font-family:var(--mono);font-size:22px;color:var(--brass-bright);display:block;margin-bottom:4px}
.stat-card span{font-size:11.5px;color:var(--ink-dim);text-transform:uppercase;letter-spacing:.3px}

.btn-sm{width:auto;padding:9px 16px;font-size:11.5px}
.btn-danger{background:var(--red);color:#fff}
.btn-danger:hover{background:#e04f38}

.toast{
  position:fixed;bottom:24px;right:24px;background:var(--graphite2);color:var(--ink);
  border:1px solid var(--brass);padding:14px 18px;border-radius:2px;display:flex;align-items:center;gap:10px;
  font-size:13px;box-shadow:0 12px 30px -10px rgba(0,0,0,.5);z-index:100;
  opacity:0;transform:translateY(14px) scale(.96);transition:opacity .3s cubic-bezier(.34,1.56,.64,1), transform .3s cubic-bezier(.34,1.56,.64,1);pointer-events:none;
}
.toast.show{opacity:1;transform:translateY(0) scale(1)}

/* ── Temp password reveal modal ── */
.modal-overlay{
  position:fixed;inset:0;z-index:200;display:none;align-items:center;justify-content:center;
  background:rgba(10,12,16,.72);backdrop-filter:blur(3px);padding:20px;
}
.modal-overlay.show{display:flex;animation:fieldIn .25s ease}
.modal-card{
  background:var(--graphite2);border:1px solid var(--line);border-radius:4px;
  padding:34px 32px;max-width:420px;width:100%;text-align:center;position:relative;
  box-shadow:0 30px 80px -20px rgba(0,0,0,.6);animation:cardIn .3s cubic-bezier(.16,1,.3,1);
}
.modal-card::before{
  content:'';position:absolute;top:0;left:0;right:0;height:2px;
  background:linear-gradient(90deg,transparent,var(--brass),transparent);
}
.modal-icon{font-size:30px;margin-bottom:12px}
.modal-title{font-family:var(--display);font-size:18px;font-weight:600;text-transform:uppercase;letter-spacing:.3px;margin-bottom:10px}
.modal-sub{font-size:13px;color:var(--ink-dim);line-height:1.5;margin-bottom:20px}
.modal-pw-box{
  display:flex;align-items:center;justify-content:space-between;gap:10px;
  background:var(--graphite);border:1px solid var(--brass);border-radius:2px;
  padding:14px 16px;margin-bottom:16px;
}
.modal-pw-box .mono{font-size:17px;color:var(--brass-bright);letter-spacing:1px}
.modal-copy-btn{
  background:transparent;border:1px solid var(--line);color:var(--ink-dim);
  padding:6px 12px;border-radius:2px;font-family:var(--mono);font-size:11px;
  text-transform:uppercase;letter-spacing:.5px;cursor:pointer;flex-shrink:0;
  transition:border-color .2s ease, color .2s ease, background-color .2s ease;
}
.modal-copy-btn:hover{border-color:var(--brass);color:var(--brass-bright)}
.modal-copy-btn.copied{border-color:var(--green);color:var(--green);background:var(--green-dim)}
.modal-warn{font-size:12px;color:var(--red);margin-bottom:22px;font-family:var(--mono)}

@media(max-width:860px){
  .sidebar{width:70px}
  .sb-item span:not(.sb-icon){display:none}
  .sb-brand span,.sb-user-name,.sb-user-role{display:none}
  .stat-row{grid-template-columns:1fr 1fr}
}
</style>
</head>
<body>

<div class="noise"></div>

<!-- Intrusion alarm banner — shown app-wide whenever alert_state.json is
     active, so an admin can silence the physical buzzer/OLED from here
     instead of waiting for it to time out on its own. -->
<div id="alarm-banner" style="display:none;position:fixed;top:0;left:0;right:0;z-index:150;background:var(--red);color:#fff;padding:12px 20px;align-items:center;justify-content:center;gap:16px;font-family:var(--mono);font-size:13px;letter-spacing:.4px;box-shadow:0 4px 20px rgba(0,0,0,.4)">
  <span>🚨 INTRUSION ALERT — <span id="alarm-banner-reason"></span></span>
  <button class="btn btn-sm" style="width:auto;background:#fff;color:var(--red);border:none" onclick="silenceAlarm()">🔇 Silence Alarm</button>
</div>

<!-- shared brand mark used in auth + sidebar -->
<svg style="display:none">
  <symbol id="vault-icon" viewBox="0 0 34 34">
    <circle cx="17" cy="17" r="16" fill="#080a0d"/>
    <circle cx="17" cy="17" r="15" fill="none" stroke="#17b8d6" stroke-width="1.4"/>
    <g fill="none" stroke="#5ef0ff" stroke-linecap="round">
      <path d="M9.04 26.36 A15.13 18.91 -4.0 1 1 25.04 25.27" stroke-width="1.0"/>
      <path d="M9.87 24.36 A13.24 16.54 -2.7 1 1 23.18 23.82" stroke-width="1.0"/>
      <path d="M10.71 22.36 A11.35 14.18 -1.4 1 1 21.55 22.22" stroke-width="1.0"/>
      <path d="M11.55 20.29 A9.45 11.82 -0.1 1 1 20.05 20.44" stroke-width="1.0"/>
      <path d="M12.42 18.25 A7.56 9.45 1.2 1 1 18.82 18.51" stroke-width="1.0"/>
      <path d="M13.29 16.15 A5.67 7.09 2.5 1 1 17.73 16.47" stroke-width="0.9"/>
      <path d="M14.16 14.07 A3.78 4.73 3.8 1 1 16.89 14.33" stroke-width="0.9"/>
      <path d="M15.18 13.02 A3.64 2.55 2 1 1 15.36 8.00" stroke-width="0.75" opacity="0.8"/>
      <path d="M14.89 14.91 A2.18 1.45 -3 1 1 14.02 12.15" stroke-width="0.75" opacity="0.8"/>
    </g>
  </symbol>
</svg>

<!-- ══════════ LOGIN ══════════ -->
<div class="screen active" id="s-login">
  <div class="auth-wrap">
    <div class="cyber-bg"></div>
    <?php include 'partials/network-bg.php'; ?>
    <a href="index.php" class="back-home"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>Back to Home</a>
    <div class="auth-card">
      <span class="holo-bracket tl"></span>
      <span class="holo-bracket tr"></span>
      <span class="holo-bracket bl"></span>
      <span class="holo-bracket br"></span>
      <div class="auth-badge">
        <svg viewBox="0 0 24 24"><path d="M7 10V7a5 5 0 0 1 10 0v3" fill="none" stroke="var(--graphite)" stroke-width="2" stroke-linecap="round"/><rect x="5" y="10" width="14" height="10" rx="2" fill="var(--graphite)"/></svg>
      </div>
      <a href="index.php" class="auth-brand"><svg><use href="#vault-icon"/></svg><span>SecureVault</span></a>
      <h1 id="loginHeadline">Vault Access</h1>
      <p class="auth-sub">// AUTHENTICATE TO PROCEED</p>

      <div class="form-group">
        <label class="form-label">Username</label>
        <div class="input-icon-wrap">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
          <input class="form-input" id="li-user">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-icon-wrap">
          <svg viewBox="0 0 24 24"><path d="M6 10V7a6 6 0 0 1 12 0v3"/><rect x="4.5" y="10" width="15" height="10" rx="2"/></svg>
          <input class="form-input" id="li-pass" type="password" placeholder="••••••••">
        </div>
      </div>
      <button class="btn btn-brass" onclick="doLogin()">Sign In</button>

      <p class="auth-foot" style="margin-top:10px"><a onclick="requestPasswordReset()">Forgot password?</a></p>
      <p class="auth-foot">No vault access yet? <a onclick="goTo('s-register')">Request access</a></p>

      <div class="visual-stats">
        <div class="visual-stat"><b>AES-256</b><span>Cipher active</span></div>
        <div class="visual-stat"><b id="visLoginNodes">—</b><span>Verified nodes</span></div>
        <div class="visual-stat"><b>0</b><span>Breaches</span></div>
      </div>
    </div>
  </div>
</div>

<!-- ══════════ REGISTER ══════════ -->
<div class="screen" id="s-register">
  <div class="auth-wrap">
    <div class="cyber-bg"></div>
    <?php include 'partials/network-bg.php'; ?>
    <a href="index.php" class="back-home"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>Back to Home</a>
    <div class="auth-card">
      <span class="holo-bracket tl"></span>
      <span class="holo-bracket tr"></span>
      <span class="holo-bracket bl"></span>
      <span class="holo-bracket br"></span>
      <a href="index.php" class="auth-brand"><svg><use href="#vault-icon"/></svg><span>SecureVault</span></a>
      <h1>Request Access</h1>
      <p class="auth-sub">// ACCOUNTS ARE ISSUED BY YOUR ADMINISTRATOR</p>

      <div id="ra-form">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input class="form-input" id="ra-fullname">
        </div>
        <div class="form-group">
          <label class="form-label">Preferred Username</label>
          <input class="form-input" id="ra-username">
        </div>
        <div class="form-group">
          <label class="form-label">Work Email</label>
          <input class="form-input" id="ra-email" type="email" placeholder="name@organization.com">
        </div>
        <div class="form-group">
          <label class="form-label">Department / Reason <span style="opacity:.6">(optional)</span></label>
          <input class="form-input" id="ra-note" maxlength="300" placeholder="e.g. Radiology — needs access to patient scans">
        </div>
        <button class="btn btn-brass" id="ra-submit" onclick="submitAccessRequest()">Submit Request →</button>
        <p class="auth-foot">Already have access? <a onclick="goTo('s-login')">Sign in</a></p>
      </div>

      <div id="ra-done" style="display:none;text-align:center">
        <div style="font-size:34px;margin:6px 0 10px">📨</div>
        <p class="auth-sub" style="margin-bottom:18px;line-height:1.6">Request sent. An administrator will verify your identity, create your account and enroll your fingerprint at the kiosk. They will give you a temporary password.</p>
        <button class="btn btn-outline" onclick="resetAccessRequestForm(); goTo('s-login')">Back to Sign In</button>
      </div>

      <div class="visual-stats">
        <div class="visual-stat"><b>AES-256</b><span>Cipher standard</span></div>
        <div class="visual-stat"><b>AS608</b><span>Fingerprint sensor</span></div>
        <div class="visual-stat"><b>RBAC</b><span>Role-scoped</span></div>
      </div>
    </div>
  </div>
</div>

<!-- ══════════ APP DASHBOARD ══════════ -->
<div class="screen" id="s-app">
  <div class="app-shell">
    <aside class="sidebar">
      <div class="sb-brand"><svg><use href="#vault-icon"/></svg><span>SecureVault</span></div>
      <nav class="sb-nav">
        <button class="sb-item active" onclick="showPage('p-files', this)"><span class="sb-icon">📁</span><span>Files</span></button>
        <button class="sb-item" onclick="showPage('p-intrusion', this)"><span class="sb-icon">🚨</span><span>Intrusion Logs</span></button>
        <button class="sb-item" onclick="showPage('p-activity', this)"><span class="sb-icon">📋</span><span>Activity Log</span></button>
        <button class="sb-item" onclick="showPage('p-access', this)"><span class="sb-icon">🧩</span><span>Access Control</span></button>
        <button class="sb-item" onclick="showPage('p-settings', this)"><span class="sb-icon">⚙️</span><span>Settings</span></button>
      </nav>
      <div class="sb-foot">
        <div class="sb-user">
          <div class="sb-avatar" id="sb-avatar">?</div>
          <div>
            <div class="sb-user-name" id="sb-username">—</div>
            <div class="sb-user-role">Vault Session Active</div>
          </div>
        </div>
        <button class="sb-item" onclick="doLogout()"><span class="sb-icon">↩</span><span>Log Out</span></button>
      </div>
    </aside>

    <main class="main">
      <div class="topbar">
        <div>
          <h1 id="topbar-title">Files</h1>
          <div class="topbar-sub" id="topbar-sub">// YOUR ENCRYPTED FILE STORAGE</div>
        </div>
        <input type="file" id="file-input" style="display:none" onchange="handleFileUpload(this.files[0])">
        <button class="btn btn-brass btn-sm" id="topbar-action" onclick="document.getElementById('file-input').click()">+ Upload File</button>
      </div>

      <div class="content">

        <!-- FILES PAGE -->
        <div class="page active" id="p-files">
          <div class="stat-row">
            <div class="stat-card"><b id="stat-count">—</b><span>Files Stored</span></div>
            <div class="stat-card"><b id="stat-size">—</b><span>Total Encrypted Size</span></div>
            <div class="stat-card"><b>AES-256</b><span>Cipher Standard</span></div>
            <div class="stat-card"><b id="stat-week">—</b><span>Uploaded This Week</span></div>
          </div>
          <div class="panel">
            <div class="panel-head"><h3>Vault Contents</h3></div>
            <table>
              <tr><th>Filename</th><th>Size</th><th>Uploaded</th><th>Status</th><th></th></tr>
              <tbody id="files-tbody">
                <tr><td colspan="5" class="mono" style="text-align:center;padding:24px">Loading vault contents…</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- INTRUSION LOGS PAGE -->
        <div class="page" id="p-intrusion">
          <div class="panel">
            <div class="panel-head"><h3>Threat Watch</h3></div>
            <div class="filter-bar">
              <select id="intrusion-filter-severity" onchange="loadIntrusions()">
                <option value="">All Severities</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
              </select>
              <select id="intrusion-filter-type" onchange="loadIntrusions()">
                <option value="">All Threat Types</option>
              </select>
              <input type="date" id="intrusion-filter-from" onchange="loadIntrusions()">
              <input type="date" id="intrusion-filter-to" onchange="loadIntrusions()">
              <input type="text" id="intrusion-filter-search" placeholder="Search user or response…" oninput="onIntrusionSearchInput()">
              <button class="filter-clear" onclick="clearIntrusionFilters()">Clear</button>
              <button class="btn btn-outline btn-sm" style="width:auto" onclick="exportIntrusions()">⬇ Export CSV</button>
              <span class="filter-count" id="intrusion-filter-count"></span>
            </div>
            <table>
              <tr><th>Time</th><th>Threat Type</th><th>Severity</th><th>Reason</th><th>Response</th></tr>
              <tbody id="intrusion-tbody">
                <tr><td colspan="5" class="mono" style="text-align:center;padding:24px">Loading threat log…</td></tr>
              </tbody>
            </table>
            <div style="padding:14px 20px;text-align:center">
              <button class="btn btn-outline btn-sm" id="intrusion-load-more" style="display:none;width:auto" onclick="loadIntrusions(true)">Load More</button>
            </div>
          </div>
        </div>

        <!-- ACTIVITY LOG PAGE -->
        <div class="page" id="p-activity">
          <div class="panel">
            <div class="panel-head"><h3>Access Ledger</h3></div>
            <div class="filter-bar">
              <select id="activity-filter-action" onchange="loadActivity()">
                <option value="">All Actions</option>
              </select>
              <input type="date" id="activity-filter-from" onchange="loadActivity()">
              <input type="date" id="activity-filter-to" onchange="loadActivity()">
              <input type="text" id="activity-filter-search" placeholder="Search user or details…" oninput="onActivitySearchInput()">
              <button class="filter-clear" onclick="clearActivityFilters()">Clear</button>
              <span class="filter-count" id="activity-filter-count"></span>
            </div>
            <table>
              <tr><th>Time</th><th>User</th><th>Action</th><th>Details</th></tr>
              <tbody id="activity-tbody">
                <tr><td colspan="4" class="mono" style="text-align:center;padding:24px">Loading activity log…</td></tr>
              </tbody>
            </table>
            <div style="padding:14px 20px;text-align:center">
              <button class="btn btn-outline btn-sm" id="activity-load-more" style="display:none;width:auto" onclick="loadActivity(true)">Load More</button>
            </div>
          </div>
        </div>

        <!-- ACCESS CONTROL PAGE -->
        <div class="page" id="p-access">
          <div class="panel" id="access-requests-panel" style="display:none">
            <div class="panel-head"><h3>Access Requests</h3><span class="mono" id="access-requests-count" style="color:var(--brass-bright);font-size:12px"></span></div>
            <table>
              <tr><th>Name</th><th>Email</th><th>Department / Reason</th><th>Requested</th><th></th></tr>
              <tbody id="access-requests-tbody"></tbody>
            </table>
          </div>
          <div class="panel">
            <div class="panel-head"><h3>Role Registry</h3><button class="btn btn-brass btn-sm" onclick="openAddUserModal()">+ Add User</button></div>
            <table>
              <tr><th>User</th><th>Email</th><th>Role</th><th>Biometric</th><th>Account</th><th>Status</th><th></th></tr>
              <tbody id="users-tbody">
                <tr><td colspan="7" class="mono" style="text-align:center;padding:24px">Loading role registry…</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- SETTINGS PAGE -->
        <div class="page" id="p-settings">
          <div class="panel">
            <div class="panel-head"><h3>Account Settings</h3></div>
            <div style="padding:20px">
              <div class="avatar-row">
                <div class="avatar-preview" id="settings-avatar-preview">?</div>
                <div class="avatar-meta">
                  <input type="file" id="avatar-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none" onchange="openCropModal(this.files[0]); this.value='';">
                  <button class="btn btn-outline btn-sm" style="width:auto" onclick="document.getElementById('avatar-input').click()">📷 Change Photo</button>
                  <span>JPG, PNG, GIF or WEBP — max 2MB</span>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Username</label>
                <input class="form-input" id="settings-username" value="" disabled style="opacity:.6">
              </div>
              <div class="form-group">
                <label class="form-label">Role</label>
                <input class="form-input" id="settings-role" value="" disabled style="opacity:.6">
              </div>
              <div class="form-group">
                <label class="form-label">Full Name</label>
                <input class="form-input" id="settings-fullname" value="">
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input class="form-input" id="settings-email" type="email" value="" placeholder="you@example.com">
              </div>
              <div class="form-group">
                <label class="form-label">Biometric Status</label>
                <div id="settings-bio-status" class="mono" style="padding:10px 0;color:var(--ink-dim)">—</div>
              </div>
              <div class="form-group">
                <label class="form-label">Fingerprint Enrollment</label>
                <button class="btn btn-outline" style="width:auto" id="settings-rescan-btn" onclick="reScanFingerprint()"><svg viewBox="0 0 34 34" width="14" height="14" style="vertical-align:-2px;margin-right:4px" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M9.04 26.36 A15.13 18.91 -4.0 1 1 25.04 25.27" stroke-width="2.2"/><path d="M9.87 24.36 A13.24 16.54 -2.7 1 1 23.18 23.82" stroke-width="2.2"/><path d="M10.71 22.36 A11.35 14.18 -1.4 1 1 21.55 22.22" stroke-width="2.2"/><path d="M11.55 20.29 A9.45 11.82 -0.1 1 1 20.05 20.44" stroke-width="2.2"/><path d="M12.42 18.25 A7.56 9.45 1.2 1 1 18.82 18.51" stroke-width="2.0"/><path d="M13.29 16.15 A5.67 7.09 2.5 1 1 17.73 16.47" stroke-width="1.9"/><path d="M14.16 14.07 A3.78 4.73 3.8 1 1 16.89 14.33" stroke-width="1.9"/><path d="M15.18 13.02 A3.64 2.55 2 1 1 15.36 8.00" stroke-width="1.6" opacity="0.85"/><path d="M14.89 14.91 A2.18 1.45 -3 1 1 14.02 12.15" stroke-width="1.6" opacity="0.85"/></svg>Enroll Fingerprint</button>
              </div>
              <button class="btn btn-brass" onclick="saveSettings()">Save Changes</button>
            </div>
          </div>

          <div class="panel" style="margin-top:20px">
            <div class="panel-head"><h3>Change Password</h3></div>
            <div style="padding:20px">
              <div class="form-group">
                <label class="form-label">Current Password</label>
                <input class="form-input" type="password" id="pw-current" placeholder="Enter your current password">
              </div>
              <div class="form-group">
                <label class="form-label">New Password</label>
                <input class="form-input" type="password" id="pw-new" placeholder="Min. 8 characters">
              </div>
              <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input class="form-input" type="password" id="pw-confirm" placeholder="Repeat new password">
              </div>
              <button class="btn btn-brass" style="width:auto" onclick="changePassword()">Update Password</button>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>

<div class="toast" id="toast"><span id="toast-icon">✅</span><div><b id="toast-title"></b><div id="toast-detail" style="font-size:11.5px;color:var(--ink-dim);font-family:var(--mono)"></div></div></div>

<!-- Temp password reveal modal — replaces the plain browser alert() -->
<div class="modal-overlay" id="temp-pw-overlay">
  <div class="modal-card">
    <div class="modal-icon">🔑</div>
    <h3 class="modal-title">Temporary Password Generated</h3>
    <p class="modal-sub">For <b id="temp-pw-username" style="color:var(--ink)"></b> — relay this through a channel where you've already verified their identity.</p>
    <div class="modal-pw-box">
      <span class="mono" id="temp-pw-value"></span>
      <button class="modal-copy-btn" id="temp-pw-copy-btn" onclick="copyTempPassword()">Copy</button>
    </div>
    <p class="modal-warn">⚠️ This will not be shown again. Make sure it's copied before closing.</p>
    <button class="btn btn-brass" onclick="closeTempPasswordModal()">Done — I've relayed it</button>
  </div>
</div>

<!-- Admin "Add user" modal — creates an approved account with a temporary password -->
<div class="modal-overlay" id="add-user-overlay">
  <div class="modal-card" style="text-align:left">
    <div class="modal-icon" style="text-align:center">👤</div>
    <h3 class="modal-title" style="text-align:center">Add User</h3>
    <p class="modal-sub" style="text-align:center">The account is approved immediately. A temporary password is generated and the user must change it on first login.</p>
    <div class="form-group"><label class="form-label">Full name</label><input class="form-input" id="au-fullname" autocomplete="off"></div>
    <div class="form-group"><label class="form-label">Username</label><input class="form-input" id="au-username" autocomplete="off"></div>
    <div class="form-group"><label class="form-label">Email</label><input class="form-input" id="au-email" type="email" placeholder="name@organization.com" autocomplete="off"></div>
    <div class="form-group"><label class="form-label">Role</label>
      <select class="form-input form-select" id="au-role">
        <option value="user">User</option>
        <option value="admin">Admin</option>
      </select>
    </div>
    <div style="display:flex;gap:10px;justify-content:center;margin-top:8px">
      <button class="btn btn-outline" onclick="closeAddUserModal()">Cancel</button>
      <button class="btn btn-brass" id="au-submit" onclick="submitAddUser()">Create</button>
    </div>
  </div>
</div>

<!-- Generic confirm modal — replaces window.confirm()'s "localhost says..." -->
<div class="modal-overlay" id="confirm-overlay">
  <div class="modal-card" style="max-width:360px">
    <div class="modal-icon" id="confirm-icon">⚠️</div>
    <h3 class="modal-title" id="confirm-title">Are You Sure?</h3>
    <p class="modal-sub" id="confirm-message"></p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button class="btn btn-outline" id="confirm-cancel-btn">Cancel</button>
      <button class="btn btn-brass" id="confirm-ok-btn">Confirm</button>
    </div>
  </div>
</div>

<!-- Fingerprint step-up verification modal (before file download) -->
<div class="modal-overlay" id="fp-verify-overlay">
  <div class="modal-card" style="max-width:340px">
    <div class="modal-icon" id="fp-verify-icon"><svg viewBox="0 0 34 34" width="38" height="38" fill="none" stroke="#5ef0ff" stroke-linecap="round"><path d="M9.04 26.36 A15.13 18.91 -4.0 1 1 25.04 25.27" stroke-width="1.8"/><path d="M9.87 24.36 A13.24 16.54 -2.7 1 1 23.18 23.82" stroke-width="1.8"/><path d="M10.71 22.36 A11.35 14.18 -1.4 1 1 21.55 22.22" stroke-width="1.8"/><path d="M11.55 20.29 A9.45 11.82 -0.1 1 1 20.05 20.44" stroke-width="1.8"/><path d="M12.42 18.25 A7.56 9.45 1.2 1 1 18.82 18.51" stroke-width="1.6"/><path d="M13.29 16.15 A5.67 7.09 2.5 1 1 17.73 16.47" stroke-width="1.5"/><path d="M14.16 14.07 A3.78 4.73 3.8 1 1 16.89 14.33" stroke-width="1.5"/><path d="M15.18 13.02 A3.64 2.55 2 1 1 15.36 8.00" stroke-width="1.3" opacity="0.85"/><path d="M14.89 14.91 A2.18 1.45 -3 1 1 14.02 12.15" stroke-width="1.3" opacity="0.85"/></svg></div>
    <h3 class="modal-title">Confirm It's You</h3>
    <p class="modal-sub">Scan your fingerprint at the sensor to decrypt and download this file.</p>
    <p class="bio-status scanning" id="fp-verify-status-txt" style="margin-bottom:20px">Starting…</p>
    <div style="display:flex;gap:10px;justify-content:center">
      <button class="btn btn-outline" onclick="closeFpVerifyModal()">Cancel</button>
      <button class="btn btn-brass" onclick="retryFpVerify()">Retry</button>
    </div>
  </div>
</div>

<!-- Avatar crop/zoom modal -->
<div class="modal-overlay" id="crop-overlay">
  <div class="modal-card" style="max-width:340px">
    <h3 class="modal-title">Adjust Photo</h3>
    <p class="modal-sub">Drag to reposition, use the slider to zoom.</p>
    <div class="crop-viewport" id="crop-viewport">
      <img id="crop-img" alt="">
    </div>
    <div class="crop-zoom-row">
      <span>−</span>
      <input type="range" id="crop-zoom-slider" min="100" max="300" value="100" oninput="onCropZoomChange()">
      <span>+</span>
    </div>
    <div class="crop-actions">
      <button class="btn btn-outline" onclick="closeCropModal()">Cancel</button>
      <button class="btn btn-brass" onclick="saveCroppedAvatar()">Save</button>
    </div>
  </div>
</div>

<script>
// ── Fingerprint glyph (vector, not an emoji) — renders identically on
// every device/font instead of risking a tofu box for newer emoji like 🫆. ──
const FP_ICON_SM = '<svg viewBox="0 0 34 34" width="14" height="14" style="vertical-align:-2px;margin-right:4px" fill="none" stroke="currentColor" stroke-linecap="round"><path d="M9.04 26.36 A15.13 18.91 -4.0 1 1 25.04 25.27" stroke-width="2.2"/><path d="M9.87 24.36 A13.24 16.54 -2.7 1 1 23.18 23.82" stroke-width="2.2"/><path d="M10.71 22.36 A11.35 14.18 -1.4 1 1 21.55 22.22" stroke-width="2.2"/><path d="M11.55 20.29 A9.45 11.82 -0.1 1 1 20.05 20.44" stroke-width="2.2"/><path d="M12.42 18.25 A7.56 9.45 1.2 1 1 18.82 18.51" stroke-width="2.0"/><path d="M13.29 16.15 A5.67 7.09 2.5 1 1 17.73 16.47" stroke-width="1.9"/><path d="M14.16 14.07 A3.78 4.73 3.8 1 1 16.89 14.33" stroke-width="1.9"/><path d="M15.18 13.02 A3.64 2.55 2 1 1 15.36 8.00" stroke-width="1.6" opacity="0.85"/><path d="M14.89 14.91 A2.18 1.45 -3 1 1 14.02 12.15" stroke-width="1.6" opacity="0.85"/></svg>';
const FP_ICON_LG = '<svg viewBox="0 0 34 34" width="38" height="38" fill="none" stroke="#5ef0ff" stroke-linecap="round"><path d="M9.04 26.36 A15.13 18.91 -4.0 1 1 25.04 25.27" stroke-width="1.8"/><path d="M9.87 24.36 A13.24 16.54 -2.7 1 1 23.18 23.82" stroke-width="1.8"/><path d="M10.71 22.36 A11.35 14.18 -1.4 1 1 21.55 22.22" stroke-width="1.8"/><path d="M11.55 20.29 A9.45 11.82 -0.1 1 1 20.05 20.44" stroke-width="1.8"/><path d="M12.42 18.25 A7.56 9.45 1.2 1 1 18.82 18.51" stroke-width="1.6"/><path d="M13.29 16.15 A5.67 7.09 2.5 1 1 17.73 16.47" stroke-width="1.5"/><path d="M14.16 14.07 A3.78 4.73 3.8 1 1 16.89 14.33" stroke-width="1.5"/><path d="M15.18 13.02 A3.64 2.55 2 1 1 15.36 8.00" stroke-width="1.3" opacity="0.85"/><path d="M14.89 14.91 A2.18 1.45 -3 1 1 14.02 12.15" stroke-width="1.3" opacity="0.85"/></svg>';

// ── Secure network canvas — glowing depth-layered particle network,
// a rotating radar sweep, and periodic "verification ping" pulses. ──
const secureCanvasEngines = {};

function startSecureCanvas(canvasId){
  const canvas = document.getElementById(canvasId);
  if(!canvas) return;
  if(secureCanvasEngines[canvasId] && secureCanvasEngines[canvasId].running) return;

  const ctx = canvas.getContext('2d');
  let width, height, cx, cy, particles, pings, sweepAngle, rafId, lastPingTime;
  const LINK_DIST = 150;

  function resize(){
    const rect = canvas.parentElement.getBoundingClientRect();
    width = canvas.width = rect.width;
    height = canvas.height = rect.height;
    cx = width / 2;
    cy = height / 2;
  }

  function initParticles(){
    // Two depth layers for a subtle parallax/richness effect
    const far = Array.from({length: 45}, () => ({
      x: Math.random()*width, y: Math.random()*height,
      vx:(Math.random()-0.5)*0.12, vy:(Math.random()-0.5)*0.12,
      r: Math.random()*1 + 0.6, layer:'far'
    }));
    const near = Array.from({length: 32}, () => ({
      x: Math.random()*width, y: Math.random()*height,
      vx:(Math.random()-0.5)*0.28, vy:(Math.random()-0.5)*0.28,
      r: Math.random()*1.8 + 1.4, layer:'near'
    }));
    particles = far.concat(near);
    pings = [];
    sweepAngle = Math.random() * Math.PI * 2;
    lastPingTime = 0;
  }

  function spawnPing(){
    const candidates = particles.filter(p => p.layer === 'near');
    const p = candidates[Math.floor(Math.random()*candidates.length)];
    if(p) pings.push({x:p.x, y:p.y, r:0, maxR: 46 + Math.random()*24, alpha:0.55});
  }

  function step(timestamp){
    ctx.clearRect(0, 0, width, height);

    // radial vignette glow centered on the card so the effect feels anchored
    const glow = ctx.createRadialGradient(cx, cy, 0, cx, cy, Math.max(width,height)*0.6);
    glow.addColorStop(0, 'rgba(23,184,214,0.05)');
    glow.addColorStop(1, 'rgba(23,184,214,0)');
    ctx.fillStyle = glow;
    ctx.fillRect(0,0,width,height);

    // ── Radar sweep ──
    sweepAngle += 0.006;
    const sweepLen = Math.max(width,height) * 0.6;
    const sweepGrad = ctx.createLinearGradient(
      cx, cy,
      cx + Math.cos(sweepAngle)*sweepLen, cy + Math.sin(sweepAngle)*sweepLen
    );
    sweepGrad.addColorStop(0, 'rgba(94,240,255,0.09)');
    sweepGrad.addColorStop(0.55, 'rgba(94,240,255,0.03)');
    sweepGrad.addColorStop(1, 'rgba(94,240,255,0)');
    ctx.save();
    ctx.beginPath();
    ctx.moveTo(cx, cy);
    ctx.arc(cx, cy, sweepLen, sweepAngle - 0.32, sweepAngle);
    ctx.closePath();
    ctx.fillStyle = sweepGrad;
    ctx.fill();
    ctx.restore();

    // ── Particle motion ──
    for(const p of particles){
      p.x += p.vx; p.y += p.vy;
      if(p.x < 0 || p.x > width) p.vx *= -1;
      if(p.y < 0 || p.y > height) p.vy *= -1;
    }

    // ── Connecting lines (near layer only, for clarity) ──
    const near = particles.filter(p=>p.layer==='near');
    for(let i=0;i<near.length;i++){
      for(let j=i+1;j<near.length;j++){
        const dx = near[i].x - near[j].x, dy = near[i].y - near[j].y;
        const dist = Math.sqrt(dx*dx+dy*dy);
        if(dist < LINK_DIST){
          ctx.strokeStyle = `rgba(123,147,181,${0.22*(1-dist/LINK_DIST)})`;
          ctx.lineWidth = 1;
          ctx.beginPath();
          ctx.moveTo(near[i].x, near[i].y);
          ctx.lineTo(near[j].x, near[j].y);
          ctx.stroke();
        }
      }
    }

    // ── Nodes (glow via shadowBlur) ──
    for(const p of particles){
      const isNear = p.layer === 'near';
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.r, 0, Math.PI*2);
      ctx.fillStyle = isNear ? 'rgba(94,240,255,0.9)' : 'rgba(123,147,181,0.5)';
      ctx.shadowColor = isNear ? 'rgba(94,240,255,0.8)' : 'transparent';
      ctx.shadowBlur = isNear ? 8 : 0;
      ctx.fill();
    }
    ctx.shadowBlur = 0;

    // ── Verification ping rings ──
    if(timestamp - lastPingTime > 1400){
      spawnPing();
      lastPingTime = timestamp;
    }
    for(let i = pings.length-1; i>=0; i--){
      const ping = pings[i];
      ping.r += 0.9;
      ping.alpha -= 0.011;
      if(ping.alpha <= 0){ pings.splice(i,1); continue; }
      ctx.beginPath();
      ctx.arc(ping.x, ping.y, ping.r, 0, Math.PI*2);
      ctx.strokeStyle = `rgba(74,157,111,${ping.alpha})`;
      ctx.lineWidth = 1.5;
      ctx.stroke();
    }

    rafId = requestAnimationFrame(step);
  }

  resize();
  initParticles();
  rafId = requestAnimationFrame(step);

  const onResize = () => { resize(); };
  window.addEventListener('resize', onResize);

  secureCanvasEngines[canvasId] = {
    running: true,
    stop(){
      cancelAnimationFrame(rafId);
      window.removeEventListener('resize', onResize);
      this.running = false;
    }
  };

  // Small live-feeling counter for the login panel's "Verified nodes" stat
  const nodesEl = document.getElementById('visLoginNodes');
  if(nodesEl && canvasId === 'secureCanvas-login'){
    nodesEl.textContent = (1200 + Math.floor(Math.random()*40)).toLocaleString();
  }
}

function stopSecureCanvas(canvasId){
  if(secureCanvasEngines[canvasId] && secureCanvasEngines[canvasId].running){
    secureCanvasEngines[canvasId].stop();
  }
}

// Start the login canvas immediately since it's the default active screen
document.addEventListener('DOMContentLoaded', () => startSecureCanvas('secureCanvas-login'));

// ── Decryption scramble effect on login headline (matches landing page) ──
(function(){
  const el = document.getElementById('loginHeadline');
  if(!el) return;
  const finalText = "VAULT ACCESS";
  const chars = "!<>-_\\/[]{}—=+*^?#________";
  let frame = 0;
  const totalFrames = 22;

  function render(){
    let out = '';
    for(let i=0;i<finalText.length;i++){
      const revealFrame = (i / finalText.length) * totalFrames;
      if(finalText[i] === ' '){ out += ' '; continue; }
      if(frame >= revealFrame + 6){
        out += finalText[i];
      } else if(frame >= revealFrame){
        out += chars[Math.floor(Math.random()*chars.length)];
      } else {
        out += ' ';
      }
    }
    el.textContent = out;
    frame++;
    if(frame < totalFrames + 8){
      requestAnimationFrame(render);
    } else {
      el.textContent = finalText;
    }
  }
  render();
})();

// ── Intrusion alarm banner — admin-only. Polls the alert flag and lets an
//    admin silence the physical buzzer/OLED from the dashboard. Regular
//    users/viewers never see this or get polled for it. ──
function pollAlarmStatus(){
  if(currentRole !== 'admin'){
    document.getElementById('alarm-banner').style.display = 'none';
    return;
  }
  fetch('api/intrusion_alert.php?action=status')
    .then(r=>r.json())
    .then(data=>{
      const banner = document.getElementById('alarm-banner');
      if(data.active){
        document.getElementById('alarm-banner-reason').textContent = data.reason || 'unspecified';
        banner.style.display = 'flex';
      } else {
        banner.style.display = 'none';
      }
    })
    .catch(()=>{}); // stay silent on transient network hiccups
}

function silenceAlarm(){
  if(currentRole !== 'admin') return;
  fetch('api/intrusion_alert.php?action=ack')
    .then(r=>r.json())
    .then(data=>{
      if(!data.success){
        showToast('⛔',data.message||'Could not silence alarm','');
        return;
      }
      document.getElementById('alarm-banner').style.display = 'none';
      showToast('🔇','Alarm silenced','The physical buzzer/OLED will stop on its next check-in');
    })
    .catch(()=>showToast('⛔','Could not silence alarm','Check your connection and try again'));
}

setInterval(pollAlarmStatus, 3000);
document.addEventListener('DOMContentLoaded', pollAlarmStatus);

// ── Screen switching ──
function goTo(id){
  document.querySelectorAll('.screen').forEach(s=>s.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  if(id === 's-login') startSecureCanvas('secureCanvas-login');
  else stopSecureCanvas('secureCanvas-login');
  if(id === 's-register') startSecureCanvas('secureCanvas-register');
  else stopSecureCanvas('secureCanvas-register');
}

// ── Dashboard page switching ──
function showPage(id, btn){
  if(mustChangePassword && id !== 'p-settings'){
    showToast('⚠️','Change your password first','You must set a new password before accessing other pages');
    return;
  }

  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  document.querySelectorAll('.sb-item').forEach(i=>i.classList.remove('active'));
  if(btn) btn.classList.add('active');

  const titles = {
    'p-files':['Files','// YOUR ENCRYPTED FILE STORAGE'],
    'p-intrusion':['Intrusion Logs','// SIGNATURE-BASED THREAT DETECTION'],
    'p-activity':['Activity Log','// FULL AUDIT TRAIL'],
    'p-access':['Access Control','// ROLE-BASED PERMISSIONS'],
    'p-settings':['Settings','// MANAGE YOUR ACCOUNT']
  };
  document.getElementById('topbar-title').textContent = titles[id][0];
  document.getElementById('topbar-sub').textContent = titles[id][1];
  document.getElementById('topbar-action').style.display = (id==='p-files') ? 'inline-flex' : 'none';

  if(id==='p-files') loadFiles();
  if(id==='p-access'){ loadUsers(); loadAccessRequests(); }
  if(id==='p-intrusion') loadIntrusions();
  if(id==='p-activity') loadActivity();
  if(id==='p-settings') loadSettings();
}

// ── Access Control: list + edit roles ──
function loadUsers(){
  fetch('api/list_users.php')
    .then(r=>r.json())
    .then(data=>{
      const tbody = document.getElementById('users-tbody');
      if(!data.success){
        tbody.innerHTML = `<tr><td colspan="7" class="mono" style="text-align:center;padding:24px">${escapeHtml(data.message)}</td></tr>`;
        return;
      }
      const now = new Date();
      usersById = {}; // reset lookup map used by deleteUser() — avoids embedding raw usernames inside onclick attributes
      tbody.innerHTML = data.users.map(u=>{
        usersById[u.user_id] = u.username;
        // MySQL DATETIME ("YYYY-MM-DD HH:MM:SS") isn't reliably parsed by
        // new Date() in every browser — normalize to ISO-ish format first.
        // locked_until no longer auto-expires — its presence means the
        // account is locked until an admin explicitly unlocks it.
        const isLocked = !!u.locked_until;
        const isPendingApproval = (u.account_status === 'pending');
        const statusCell = isLocked
          ? `<span class="badge badge-alert" title="Locked since ${u.locked_until} — requires admin unlock">🔒 Locked</span>`
          : (u.failed_attempts > 0
              ? `<span class="badge badge-warn">${u.failed_attempts} failed attempt(s)</span>`
              : `<span class="badge badge-ok">Active</span>`);
        const accountCell = isPendingApproval
          ? `<span class="badge badge-warn" title="Self-registered — cannot log in until approved">⏳ Pending Approval</span>`
          : `<span class="badge badge-ok">Approved</span>`;
        const unlockBtn = isLocked
          ? `<button class="btn btn-outline btn-sm" onclick="unlockAccount(${u.user_id})">Unlock</button>`
          : '';
        const resetBtn = u.reset_requested
          ? `<button class="btn btn-outline btn-sm" style="color:var(--brass-bright);border-color:var(--brass)" onclick="adminResetPassword(${u.user_id})" title="Reset requested ${escapeHtml(u.reset_requested)}">🔑 Approve Reset</button>`
          : '';
        const approveBtn = isPendingApproval
          ? `<button class="btn btn-brass btn-sm" onclick="approveUser(${u.user_id})">✅ Approve</button>`
          : '';
        const enrollFpBtn = `<button class="btn btn-outline btn-sm" onclick="adminEnrollFingerprint(${u.user_id}, '${escapeHtml(u.username)}')">${FP_ICON_SM}${u.fingerprint_slot_id !== null ? 'Re-enroll' : 'Enroll'} FP</button>`;
        return `<tr>
        <td>${escapeHtml(u.full_name || u.username)}<div class="mono" style="color:var(--ink-dim);font-size:11px">@${escapeHtml(u.username)}</div></td>
        <td class="mono" style="color:${u.email ? 'var(--ink-dim)' : 'var(--red)'};font-size:12.5px">${u.email ? escapeHtml(u.email) : 'Not set'}</td>
        <td>
          <select class="role-select" onchange="updateUserRole(this, ${u.user_id})" style="background:var(--panel-2,#1a1f27);color:inherit;border:1px solid var(--border,#333);border-radius:6px;padding:4px 8px;font-family:inherit;font-size:12.5px">
            <option value="admin" ${u.role==='admin' ? 'selected' : ''}>ADMIN</option>
            <option value="user" ${u.role==='user' ? 'selected' : ''}>USER</option>
          </select>
        </td>
        <td>${u.fingerprint_slot_id !== null ? '<span class="badge badge-ok">Enrolled</span>' : '<span class="badge badge-warn">Pending</span>'}</td>
        <td>${accountCell}</td>
        <td>${statusCell}</td>
        <td style="display:flex;gap:8px;flex-wrap:wrap">
          ${approveBtn}
          ${enrollFpBtn}
          ${unlockBtn}
          ${resetBtn}
          <button class="btn btn-outline btn-sm" style="color:var(--red);border-color:var(--red)" onclick="deleteUser(${u.user_id})">Delete</button>
        </td>
      </tr>`;
      }).join('');
    })
    .catch(()=>showToast('⛔','Could not load users',''));
}

// Promise-based replacement for window.confirm() — shows the app's own
// styled modal instead of the browser's native "localhost says..." dialog.
function showConfirm(title, message, confirmLabel){
  return new Promise((resolve)=>{
    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-message').textContent = message;
    document.getElementById('confirm-ok-btn').textContent = confirmLabel || 'Confirm';
    const overlay = document.getElementById('confirm-overlay');
    const okBtn = document.getElementById('confirm-ok-btn');
    const cancelBtn = document.getElementById('confirm-cancel-btn');

    function cleanup(result){
      overlay.classList.remove('show');
      okBtn.removeEventListener('click', onOk);
      cancelBtn.removeEventListener('click', onCancel);
      resolve(result);
    }
    function onOk(){ cleanup(true); }
    function onCancel(){ cleanup(false); }

    okBtn.addEventListener('click', onOk);
    cancelBtn.addEventListener('click', onCancel);
    overlay.classList.add('show');
  });
}

async function approveUser(userId){
  const ok = await showConfirm('Approve Account', 'They will be able to log in once approved.', 'Approve');
  if(!ok) return;

  fetch('api/approve_user.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({user_id:userId})
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      showToast('✅','Account approved','They can now log in');
      loadUsers();
    } else {
      showToast('⛔',data.message||'Could not approve account','');
    }
  })
  .catch(()=>showToast('⛔','Connection error',''));
}

async function adminEnrollFingerprint(userId, username, skipConfirm){ // FP_POLICY_V8
  const ok = skipConfirm ? true : await showConfirm('Enroll Fingerprint', `Enroll fingerprint for "${username}"? They (or you, on their behalf) need to be physically at the sensor now — it will ask for two scans.`, 'Start Enrollment');
  if(!ok) return;

  showToast('ℹ️','Starting enrollment','Place the finger on the sensor when prompted');

  fetch('api/fingerprint_enroll_start.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({target_user_id:userId})
  })
  .then(r=>r.json())
  .then(data=>{
    if(!data.success){
      if(data.need_verify){
        showToast('🔐','Authorisation needed',data.message||'Scan your fingerprint first');
        requireFingerprintVerification(data.purpose, ()=>adminEnrollFingerprint(userId, username, true));
        return;
      }
      showToast('⛔',data.message||'Could not start enrollment','');
      return;
    }
    if(data.cooling_off_after){
      showToast('⏳','Cooling-off will apply',`Downloads for ${username} pause for ${data.cooling_off_after} min after this change`);
    }

    let attempts = 0;
    const maxAttempts = 27; // ~40 seconds at 1500ms intervals
    let lastStep = null;

    const pollInterval = setInterval(()=>{
      attempts++;

      fetch('api/fingerprint_enroll_poll.php')
      .then(r=>r.json())
      .then(poll=>{
        if(poll.status === 'waiting'){
          if(poll.step && poll.step !== lastStep){
            lastStep = poll.step;
            showToast('ℹ️', fpEnrollStepText(poll.step), '');
          }
          if(attempts >= maxAttempts){
            clearInterval(pollInterval);
            showToast('⛔','Enrollment timed out','');
          }
          return;
        }

        clearInterval(pollInterval);

        if(poll.status === 'success' && poll.success){
          showToast('✅','Fingerprint enrolled',`${username} can now log in with fingerprint`);
          loadUsers();
        } else {
          showToast('⛔',poll.message||'Enrollment failed','');
        }
      })
      .catch(()=>{
        clearInterval(pollInterval);
        showToast('⛔','Connection error','');
      });
    }, 1500);
  })
  .catch(()=>showToast('⛔','Connection error',''));
}

async function unlockAccount(userId){
  const ok = await showConfirm('Unlock Account', 'Clear failed login attempts and unlock this account?', 'Unlock');
  if(!ok) return;

  fetch('api/unlock_account.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({user_id:userId})
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      showToast('✅','Account unlocked','');
      loadUsers();
    } else {
      showToast('⛔',data.message||'Could not unlock','');
    }
  })
  .catch(()=>showToast('⛔','Connection error',''));
}

async function adminResetPassword(userId){
  const ok = await showConfirm('Generate Temporary Password', 'They will be required to change it on next login. Only relay it through a channel where you have already verified their identity.', 'Generate');
  if(!ok) return;

  fetch('api/admin_reset_password.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({user_id:userId})
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      // Shown once via a blocking modal rather than a toast — this value
      // must not linger in the DOM or get missed, and it will not be
      // shown again after this.
      showTempPasswordModal(data.username, data.temp_password);
      loadUsers();
    } else {
      showToast('⛔',data.message||'Could not generate reset','');
    }
  })
  .catch(()=>showToast('⛔','Connection error',''));
}

function openAddUserModal(prefill){
  prefill = prefill || {};
  addUserRequestId = prefill.request_id || 0;
  document.getElementById('au-fullname').value = prefill.full_name || '';
  document.getElementById('au-username').value = prefill.username || '';
  document.getElementById('au-email').value = prefill.email || '';
  document.getElementById('au-role').value = 'user';
  document.getElementById('add-user-overlay').classList.add('show');
  setTimeout(()=>document.getElementById('au-fullname').focus(),50);
}

function closeAddUserModal(){
  document.getElementById('add-user-overlay').classList.remove('show');
  addUserRequestId = 0;
}

function submitAddUser(){
  const body = {
    full_name: document.getElementById('au-fullname').value.trim(),
    username:  document.getElementById('au-username').value.trim(),
    email:     document.getElementById('au-email').value.trim(),
    role:      document.getElementById('au-role').value,
    request_id: addUserRequestId || undefined
  };
  if(!body.username || !body.email){
    showToast('⚠️','Missing details','Username and email are required');
    return;
  }
  const btn = document.getElementById('au-submit');
  btn.disabled = true; btn.textContent = 'Creating…';
  fetch('api/admin_create_user.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(body)
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      closeAddUserModal();
      // Same one-time reveal modal as "reset password" — shown once, never stored.
      showTempPasswordModal(data.username, data.temp_password);
      loadUsers();
      loadAccessRequests();
    } else {
      showToast('⛔',data.message||'Could not create account','');
    }
  })
  .catch(()=>showToast('⛔','Connection error',''))
  .finally(()=>{ btn.disabled = false; btn.textContent = 'Create'; });
}

// ── Access requests (replace open self-registration) ──
let addUserRequestId = 0;
let accessRequestsById = {};

function loadAccessRequests(){
  const panel = document.getElementById('access-requests-panel');
  if(!panel) return;
  if(currentRole !== 'admin'){ panel.style.display = 'none'; return; }
  fetch('api/list_access_requests.php')
    .then(r=>r.json())
    .then(data=>{
      if(!data.success || !data.requests.length){ panel.style.display = 'none'; return; }
      accessRequestsById = {};
      document.getElementById('access-requests-count').textContent = data.requests.length + ' pending';
      document.getElementById('access-requests-tbody').innerHTML = data.requests.map(r=>{
        accessRequestsById[r.request_id] = r;
        return `<tr>
          <td>${escapeHtml(r.full_name)}<div class="mono" style="color:var(--ink-dim);font-size:11px">@${escapeHtml(r.username)}</div></td>
          <td class="mono" style="color:var(--ink-dim);font-size:12.5px">${escapeHtml(r.email)}</td>
          <td style="font-size:12.5px;color:var(--ink-dim)">${r.note ? escapeHtml(r.note) : '—'}</td>
          <td class="mono" style="font-size:11.5px;color:var(--ink-dim)">${escapeHtml(r.created_at)}</td>
          <td style="display:flex;gap:8px;flex-wrap:wrap">
            <button class="btn btn-brass btn-sm" onclick="createFromRequest(${r.request_id})">Create account</button>
            <button class="btn btn-outline btn-sm" style="color:var(--red);border-color:var(--red)" onclick="dismissAccessRequest(${r.request_id})">Dismiss</button>
          </td>
        </tr>`;
      }).join('');
      panel.style.display = '';
    })
    .catch(()=>{ panel.style.display = 'none'; });
}

function createFromRequest(requestId){
  const r = accessRequestsById[requestId];
  if(!r) return;
  openAddUserModal({request_id: r.request_id, full_name: r.full_name, username: r.username, email: r.email});
}

async function dismissAccessRequest(requestId){
  const r = accessRequestsById[requestId];
  const ok = await showConfirm('Dismiss Request', `Dismiss the access request from "${r ? r.username : ''}"? No account will be created.`, 'Dismiss');
  if(!ok) return;
  fetch('api/dismiss_access_request.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({request_id:requestId})
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){ showToast('🗑️','Request dismissed',''); loadAccessRequests(); }
    else showToast('⛔',data.message||'Could not dismiss request','');
  })
  .catch(()=>showToast('⛔','Connection error',''));
}

function showTempPasswordModal(username, tempPassword){
  document.getElementById('temp-pw-username').textContent = username;
  document.getElementById('temp-pw-value').textContent = tempPassword;
  const copyBtn = document.getElementById('temp-pw-copy-btn');
  copyBtn.textContent = 'Copy';
  copyBtn.classList.remove('copied');
  document.getElementById('temp-pw-overlay').classList.add('show');
}

function closeTempPasswordModal(){
  document.getElementById('temp-pw-overlay').classList.remove('show');
  // Clear the value from the DOM once dismissed — it was shown once, on purpose.
  document.getElementById('temp-pw-value').textContent = '';
}

function copyTempPassword(){
  const value = document.getElementById('temp-pw-value').textContent;
  const btn = document.getElementById('temp-pw-copy-btn');
  navigator.clipboard.writeText(value).then(()=>{
    btn.textContent = '✓ Copied';
    btn.classList.add('copied');
  }).catch(()=>{
    showToast('⛔','Could not copy','Select and copy the password manually');
  });
}

let usersById = {}; // populated by loadUsers() — avoids putting raw usernames inside onclick attributes

async function deleteUser(userId){
  const username = usersById[userId] || 'this account';
  const ok = await showConfirm('Delete Account', `Permanently delete account "${username}"? This cannot be undone.`, 'Delete');
  if(!ok) return;

  fetch('api/delete_users.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({user_id:userId})
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      showToast('✅','User deleted','');
      loadUsers();
    } else {
      showToast('⛔',data.message||'Could not delete user','');
    }
  })
  .catch(()=>showToast('⛔','Connection error',''));
}

function updateUserRole(selectEl, userId){
  const clean = selectEl.value;
  const previousValue = selectEl.dataset.prev || clean;

  fetch('api/update_role.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({user_id:userId, role:clean})
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      showToast('✅','Role updated',`Now ${clean.toUpperCase()}`);
      selectEl.dataset.prev = clean;
    } else {
      showToast('⛔',data.message,'');
      selectEl.value = previousValue; // revert the dropdown on failure
    }
  })
  .catch(()=>{
    showToast('⛔','Connection error','');
    selectEl.value = previousValue;
  });
}

// ── Settings / Profile ──
function loadSettings(){
  fetch('api/whoami.php')
    .then(r=>r.json())
    .then(data=>{
      if(!data.success){
        showToast('⛔',data.message||'Could not load profile','');
        return;
      }
      document.getElementById('settings-username').value = data.username || '';
      document.getElementById('settings-role').value = (data.role || 'user').toUpperCase();
      document.getElementById('settings-fullname').value = data.full_name || '';
      document.getElementById('settings-email').value = data.email || '';
      const bioEl = document.getElementById('settings-bio-status');
      bioEl.textContent = data.biometric_enrolled ? '✓ Fingerprint enrolled' : '✗ Not enrolled — required before you can download or delete files';
      bioEl.style.color = data.biometric_enrolled ? 'var(--brass-bright)' : 'var(--red)';
      const rescanBtn = document.getElementById('settings-rescan-btn');
      if(!settingsFpEnrollBusy){
        rescanBtn.innerHTML = FP_ICON_SM + (data.biometric_enrolled ? 'Re-enroll Fingerprint' : 'Enroll Fingerprint');
      }
      // Enrollment is admin-only (done at the kiosk) — regular users get a note instead.
      const canSelfEnroll = (data.role === 'admin') || data.biometric_enrolled === true;
      rescanBtn.style.display = canSelfEnroll ? '' : 'none';
      let fpNote = document.getElementById('settings-fp-note');
      if(!fpNote){
        fpNote = document.createElement('div');
        fpNote.id = 'settings-fp-note';
        fpNote.className = 'mono';
        fpNote.style.cssText = 'padding:6px 0;color:var(--ink-dim);font-size:12px';
        rescanBtn.insertAdjacentElement('afterend', fpNote);
      }
      fpNote.textContent = 'Fingerprint enrollment is done by an administrator at the SecureVault kiosk.';
      fpNote.style.display = canSelfEnroll ? 'none' : '';

      const preview = document.getElementById('settings-avatar-preview');
      if(data.profile_picture){
        preview.innerHTML = `<img src="${data.profile_picture}?t=${Date.now()}" alt="">`;
      } else {
        preview.textContent = (data.username || '?').charAt(0).toUpperCase();
      }
      updateSidebarAvatar(data);
    })
    .catch(()=>showToast('⛔','Could not load profile',''));
}

// ── Avatar upload — same scan pipeline as vault file uploads (api/upload_avatar.php) ──
// `file` here is either the original File from the input, or a cropped Blob
// produced by saveCroppedAvatar() below — both work the same with FormData.
function uploadAvatar(file){
  if(!file) return;

  if(file.size > 2 * 1024 * 1024){
    showToast('⚠️','Image too large','Max 2MB');
    return;
  }

  const formData = new FormData();
  formData.append('avatar', file, 'avatar.jpg');

  showToast('🔒','Scanning image…','Running security scan before saving');

  fetch('api/upload_avatar.php', { method:'POST', body: formData })
    .then(r=>r.json())
    .then(data=>{
      if(data.success){
        showToast('✅','Photo updated','');
        loadSettings();
      } else {
        showToast('⛔',data.message||'Could not update photo','');
      }
    })
    .catch(()=>showToast('⛔','Connection error',''));
}

// ── Avatar crop/zoom modal ──
// A lightweight drag-to-reposition + slider-to-zoom cropper, built with
// plain canvas math (no external library) so it works offline during a
// demo. The user adjusts how the image sits inside a circular viewport;
// "Save" renders exactly what's visible onto a canvas and uploads that.
const CROP_VP = 260;     // viewport size shown on screen (css px)
const CROP_OUTPUT = 480; // exported image resolution (px) — higher than the
                          // viewport so the saved avatar stays sharp even
                          // though the on-screen preview is small.

let cropState = null; // { naturalW, naturalH, baseScale, scale, offsetX, offsetY }
let cropDragging = false;
let cropDragStart = null;

function openCropModal(file){
  if(!file) return;

  if(!file.type.startsWith('image/')){
    showToast('⚠️','Not an image','Choose a JPG, PNG, GIF or WEBP file');
    return;
  }
  if(file.size > 2 * 1024 * 1024){
    showToast('⚠️','Image too large','Max 2MB');
    return;
  }

  const img = document.getElementById('crop-img');
  const reader = new FileReader();
  reader.onload = (e) => {
    img.onload = () => {
      const naturalW = img.naturalWidth, naturalH = img.naturalHeight;
      const baseScale = Math.max(CROP_VP / naturalW, CROP_VP / naturalH); // "cover" fit
      cropState = { naturalW, naturalH, baseScale, scale: 1, offsetX: 0, offsetY: 0 };
      document.getElementById('crop-zoom-slider').value = 100;
      applyCropTransform();
      document.getElementById('crop-overlay').classList.add('show');
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
}

function closeCropModal(){
  document.getElementById('crop-overlay').classList.remove('show');
  cropState = null;
}

function applyCropTransform(){
  if(!cropState) return;
  const { naturalW, naturalH, baseScale, scale } = cropState;
  const s = baseScale * scale;
  const dispW = naturalW * s, dispH = naturalH * s;

  // Clamp offsets so the image always fully covers the circular viewport
  const maxOffX = Math.max(0, (dispW - CROP_VP) / 2);
  const maxOffY = Math.max(0, (dispH - CROP_VP) / 2);
  cropState.offsetX = Math.max(-maxOffX, Math.min(maxOffX, cropState.offsetX));
  cropState.offsetY = Math.max(-maxOffY, Math.min(maxOffY, cropState.offsetY));

  const left = CROP_VP / 2 - dispW / 2 + cropState.offsetX;
  const top  = CROP_VP / 2 - dispH / 2 + cropState.offsetY;

  const img = document.getElementById('crop-img');
  img.style.width = dispW + 'px';
  img.style.height = dispH + 'px';
  img.style.transform = `translate(${left}px, ${top}px)`;
}

function onCropZoomChange(){
  if(!cropState) return;
  cropState.scale = document.getElementById('crop-zoom-slider').value / 100;
  applyCropTransform();
}

(function setupCropDrag(){
  const vp = document.getElementById('crop-viewport');
  if(!vp) return;

  function start(x, y){
    if(!cropState) return;
    cropDragging = true;
    vp.classList.add('dragging');
    cropDragStart = { x, y, offsetX: cropState.offsetX, offsetY: cropState.offsetY };
  }
  function move(x, y){
    if(!cropDragging || !cropState) return;
    cropState.offsetX = cropDragStart.offsetX + (x - cropDragStart.x);
    cropState.offsetY = cropDragStart.offsetY + (y - cropDragStart.y);
    applyCropTransform();
  }
  function end(){
    cropDragging = false;
    vp.classList.remove('dragging');
  }

  vp.addEventListener('pointerdown', (e)=>{ vp.setPointerCapture(e.pointerId); start(e.clientX, e.clientY); });
  vp.addEventListener('pointermove', (e)=>{ move(e.clientX, e.clientY); });
  vp.addEventListener('pointerup', end);
  vp.addEventListener('pointercancel', end);
})();

function saveCroppedAvatar(){
  if(!cropState) return;
  const { naturalW, naturalH, baseScale, scale, offsetX, offsetY } = cropState;
  const s = baseScale * scale;
  const dispW = naturalW * s, dispH = naturalH * s;
  const left = CROP_VP / 2 - dispW / 2 + offsetX;
  const top  = CROP_VP / 2 - dispH / 2 + offsetY;

  // Map the visible viewport window back to natural image pixel coordinates
  const srcX = -left / s;
  const srcY = -top / s;
  const srcW = CROP_VP / s;
  const srcH = CROP_VP / s;

  const canvas = document.createElement('canvas');
  canvas.width = CROP_OUTPUT;
  canvas.height = CROP_OUTPUT;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(document.getElementById('crop-img'), srcX, srcY, srcW, srcH, 0, 0, CROP_OUTPUT, CROP_OUTPUT);

  canvas.toBlob((blob)=>{
    closeCropModal();
    uploadAvatar(blob);
  }, 'image/jpeg', 0.92);
}

// ── Keeps sidebar avatar in sync with profile picture (falls back to initial) ──
function updateSidebarAvatar(data){
  const avatarEl = document.getElementById('sb-avatar');
  if(!avatarEl) return;
  if(data && data.profile_picture){
    avatarEl.innerHTML = `<img src="${data.profile_picture}?t=${Date.now()}" alt="">`;
  } else {
    avatarEl.textContent = ((data && data.username) || '?').charAt(0).toUpperCase();
  }
}

function saveSettings(){
  const fullName = document.getElementById('settings-fullname').value.trim();
  const email = document.getElementById('settings-email').value.trim();
  if(!fullName){
    showToast('⚠️','Full name required','');
    return;
  }
  if(!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){
    showToast('⚠️','Valid email required','');
    return;
  }
  fetch('api/update_profile.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({full_name:fullName, email:email})
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      showToast('✅','Saved','Profile updated');
    } else {
      showToast('⛔',data.message||'Could not save','');
    }  })
  .catch(()=>showToast('⛔','Connection error',''));
}

function changePassword(){
  const current = document.getElementById('pw-current').value;
  const newPw = document.getElementById('pw-new').value;
  const confirm = document.getElementById('pw-confirm').value;

  if(!current || !newPw || !confirm){
    showToast('⚠️','Fill in all fields','');
    return;
  }
  if(newPw.length < 8){
    showToast('⚠️','Password too short','Min. 8 characters');
    return;
  }
  if(newPw !== confirm){
    showToast('⚠️','Passwords do not match','');
    return;
  }

  fetch('api/change_password.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({
      current_password: current,
      new_password: newPw,
      confirm_password: confirm
    })
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      showToast('✅','Password updated','');
      document.getElementById('pw-current').value='';
      document.getElementById('pw-new').value='';
      document.getElementById('pw-confirm').value='';
      if(mustChangePassword){
        mustChangePassword = false;
        showToast('✅','You\'re all set','Full access unlocked');
        showPage('p-files', document.querySelector('.sb-item'));
        loadFiles();
      }
    } else {
      showToast('⛔',data.message||'Could not change password','');
    }
  })
  .catch(()=>showToast('⛔','Connection error',''));
}

let settingsFpEnrollBusy = false;

function reScanFingerprint(){
  if(settingsFpEnrollBusy){
    showToast('⏳','Please wait','Enrollment already in progress');
    return;
  }

  const btn = document.getElementById('settings-rescan-btn');
  btn.disabled = true;
  btn.textContent = 'Starting…';

  fetch('api/fingerprint_enroll_start.php',{method:'POST'})
    .then(r=>r.json())
    .then(data=>{
      if(!data.success){
        btn.disabled = false;
        btn.innerHTML = FP_ICON_SM + 'Re-scan Fingerprint';
        if(data.need_verify){
          showToast('🔐','Confirm it is you',data.message||'Scan your current fingerprint first');
          requireFingerprintVerification(data.purpose, ()=>reScanFingerprint());
          return;
        }
        showToast('⛔',data.message||'Could not start','');
        return;
      }

      settingsFpEnrollBusy = true;
      btn.innerHTML = FP_ICON_SM + 'Place finger (1st scan)…';

      let attempts = 0;
      const maxAttempts = 27; // ~40 seconds at 1500ms intervals

      const pollInterval = setInterval(()=>{
        attempts++;

        fetch('api/fingerprint_enroll_poll.php')
        .then(r=>r.json())
        .then(poll=>{
          if(poll.status === 'waiting'){
            btn.innerHTML = FP_ICON_SM + fpEnrollStepText(poll.step);
            if(attempts >= maxAttempts){
              clearInterval(pollInterval);
              settingsFpEnrollBusy = false;
              btn.disabled = false;
              btn.innerHTML = FP_ICON_SM + 'Re-scan Fingerprint';
              showToast('⛔','Enrollment timed out','');
            }
            return;
          }

          clearInterval(pollInterval);
          settingsFpEnrollBusy = false;
          btn.disabled = false;
          btn.innerHTML = FP_ICON_SM + 'Re-scan Fingerprint';

          if(poll.status === 'success' && poll.success){
            showToast('✅','Re-enrolled','Fingerprint updated');
            loadSettings();
            return;
          }

          showToast('⛔',poll.message||'Enrollment failed','');
        })
        .catch(()=>{
          clearInterval(pollInterval);
          settingsFpEnrollBusy = false;
          btn.disabled = false;
          btn.innerHTML = FP_ICON_SM + 'Re-scan Fingerprint';
          showToast('⛔','Connection error','');
        });
      }, 1500);
    })
    .catch(()=>{
      btn.disabled = false;
      btn.innerHTML = FP_ICON_SM + 'Re-scan Fingerprint';
      showToast('⛔','Connection error','');
    });
}
let activityOffset = 0;
const ACTIVITY_PAGE_SIZE = 50;
let activitySearchDebounce = null;

function loadActivity(append = false){
  if(!append) activityOffset = 0;

  const params = new URLSearchParams({
    limit: ACTIVITY_PAGE_SIZE,
    offset: activityOffset,
    action: document.getElementById('activity-filter-action').value,
    from: document.getElementById('activity-filter-from').value,
    to: document.getElementById('activity-filter-to').value,
    search: document.getElementById('activity-filter-search').value.trim()
  });

  fetch('api/list_activity.php?' + params.toString())
    .then(r=>r.json())
    .then(data=>{
      const tbody = document.getElementById('activity-tbody');
      if(!data.success){
        tbody.innerHTML = '<tr><td colspan="4" class="mono" style="text-align:center;padding:24px;color:var(--red)">'+escapeHtml(data.message||'Failed to load')+'</td></tr>';
        console.error('list_activity.php error:', data.message);
        return;
      }
      populateActivityActionFilter(data.actions || []);
      renderActivityTable(data.logs || [], data.total || 0, append);
      activityOffset += (data.logs || []).length;

      const loadMoreBtn = document.getElementById('activity-load-more');
      if(loadMoreBtn) loadMoreBtn.style.display = data.has_more ? 'inline-flex' : 'none';
    })
    .catch((err)=>{
      document.getElementById('activity-tbody').innerHTML =
        '<tr><td colspan="4" class="mono" style="text-align:center;padding:24px;color:var(--red)">Failed to load — check console (F12) for details</td></tr>';
      console.error('loadActivity error:', err);
    });
}

function populateActivityActionFilter(actions){
  const sel = document.getElementById('activity-filter-action');
  const current = sel.value;
  sel.innerHTML = '<option value="">All Actions</option>' +
    actions.map(a=>`<option value="${escapeHtml(a)}">${escapeHtml(a.replace(/_/g,' '))}</option>`).join('');
  if(actions.includes(current)) sel.value = current;
}

const activityActionBadge = {LOGIN_SUCCESS:'badge-ok', FILE_UPLOAD:'badge-info', FILE_DOWNLOAD:'badge-info',
                      LOGIN_FAIL:'badge-warn', ACCOUNT_LOCKED:'badge-alert', ACCESS_DENIED:'badge-alert'};

function renderActivityTable(logs, total, append){
  const tbody = document.getElementById('activity-tbody');

  if(!append){
    document.getElementById('activity-filter-count').textContent = `${total} matching entries`;
  }

  if(logs.length===0 && !append){
    tbody.innerHTML = '<tr><td colspan="4" class="mono" style="text-align:center;padding:24px">No matching activity.</td></tr>';
    return;
  }

  const rowsHtml = logs.map(l=>{
    const time = l.created_at || l.timestamp || l.log_time || '';
    return `<tr>
    <td class="mono">${escapeHtml(time)}</td>
    <td>${escapeHtml(l.username || 'Unknown')}</td>
    <td><span class="badge ${activityActionBadge[l.action] || 'badge-info'}">${escapeHtml(l.action)}</span></td>
    <td class="mono">${escapeHtml(l.details || '')}</td>
  </tr>`;
  }).join('');

  tbody.innerHTML = append ? (tbody.innerHTML + rowsHtml) : rowsHtml;
}

function clearActivityFilters(){
  document.getElementById('activity-filter-action').value = '';
  document.getElementById('activity-filter-from').value = '';
  document.getElementById('activity-filter-to').value = '';
  document.getElementById('activity-filter-search').value = '';
  loadActivity();
}

function onActivitySearchInput(){
  clearTimeout(activitySearchDebounce);
  activitySearchDebounce = setTimeout(()=>loadActivity(), 350);
}

let intrusionOffset = 0;
const INTRUSION_PAGE_SIZE = 50;
let intrusionSearchDebounce = null;

function loadIntrusions(append = false){
  if(!append) intrusionOffset = 0;

  const params = new URLSearchParams({
    limit: INTRUSION_PAGE_SIZE,
    offset: intrusionOffset,
    severity: document.getElementById('intrusion-filter-severity').value,
    threat_type: document.getElementById('intrusion-filter-type').value,
    from: document.getElementById('intrusion-filter-from').value,
    to: document.getElementById('intrusion-filter-to').value,
    search: document.getElementById('intrusion-filter-search').value.trim()
  });

  fetch('api/list_intrusions.php?' + params.toString())
    .then(r=>r.json())
    .then(data=>{
      const tbody = document.getElementById('intrusion-tbody');
      if(!data.success){
        tbody.innerHTML = '<tr><td colspan="5" class="mono" style="text-align:center;padding:24px;color:var(--red)">'+escapeHtml(data.message||'Failed to load')+'</td></tr>';
        console.error('list_intrusions.php error:', data.message);
        return;
      }
      populateIntrusionTypeFilter(data.threat_types || []);
      renderIntrusionTable(data.alerts || [], data.total || 0, append);
      intrusionOffset += (data.alerts || []).length;

      const loadMoreBtn = document.getElementById('intrusion-load-more');
      if(loadMoreBtn) loadMoreBtn.style.display = data.has_more ? 'inline-flex' : 'none';
    })
    .catch((err)=>{
      document.getElementById('intrusion-tbody').innerHTML =
        '<tr><td colspan="5" class="mono" style="text-align:center;padding:24px;color:var(--red)">Failed to load — check console (F12) for details</td></tr>';
      console.error('loadIntrusions error:', err);
    });
}

function exportIntrusions(){
  const params = new URLSearchParams({
    severity: document.getElementById('intrusion-filter-severity').value,
    threat_type: document.getElementById('intrusion-filter-type').value,
    from: document.getElementById('intrusion-filter-from').value,
    to: document.getElementById('intrusion-filter-to').value,
    search: document.getElementById('intrusion-filter-search').value.trim()
  });
  window.location.href = 'api/export_intrusions.php?' + params.toString();
}

function populateIntrusionTypeFilter(types){
  const sel = document.getElementById('intrusion-filter-type');
  const current = sel.value;
  sel.innerHTML = '<option value="">All Threat Types</option>' +
    types.map(t=>`<option value="${escapeHtml(t)}">${escapeHtml(t.replace(/_/g,' '))}</option>`).join('');
  if(types.includes(current)) sel.value = current;
}

const intrusionSevBadge = {high:'badge-alert', medium:'badge-warn', low:'badge-info'};

function renderIntrusionTable(alerts, total, append){
  const tbody = document.getElementById('intrusion-tbody');

  if(!append){
    document.getElementById('intrusion-filter-count').textContent = `${total} matching entries`;
  }

  if(alerts.length===0 && !append){
    tbody.innerHTML = '<tr><td colspan="5" class="mono" style="text-align:center;padding:24px">No matching threats.</td></tr>';
    return;
  }

  const rowsHtml = alerts.map(a=>{
    const time = a.created_at || a.timestamp || a.alert_time || a.detected_at || '';
    const sev = (a.severity || 'low').toLowerCase();
    return `<tr>
    <td class="mono">${escapeHtml(time)}</td>
    <td>${escapeHtml((a.threat_type||'').replace(/_/g,' '))}${a.username ? ' <span class="mono" style="color:var(--ink-dim)">('+escapeHtml(a.username)+')</span>' : ''}</td>
    <td><span class="badge ${intrusionSevBadge[sev] || 'badge-info'}">${escapeHtml(sev.toUpperCase())}</span></td>
    <td class="mono" style="max-width:320px;white-space:normal">${escapeHtml(a.description || '')}</td>
    <td class="mono">${escapeHtml(a.response_action || '')}</td>
  </tr>`;
  }).join('');

  tbody.innerHTML = append ? (tbody.innerHTML + rowsHtml) : rowsHtml;
}

function clearIntrusionFilters(){
  document.getElementById('intrusion-filter-severity').value = '';
  document.getElementById('intrusion-filter-type').value = '';
  document.getElementById('intrusion-filter-from').value = '';
  document.getElementById('intrusion-filter-to').value = '';
  document.getElementById('intrusion-filter-search').value = '';
  loadIntrusions();
}

function onIntrusionSearchInput(){
  clearTimeout(intrusionSearchDebounce);
  intrusionSearchDebounce = setTimeout(()=>loadIntrusions(), 350);
}


// ── Files: list, upload, download ──
function formatBytes(bytes){
  if(bytes < 1024) return bytes + ' B';
  if(bytes < 1024*1024) return (bytes/1024).toFixed(1) + ' KB';
  return (bytes/(1024*1024)).toFixed(1) + ' MB';
}

function loadFiles(){
  fetch('api/list_files.php')
    .then(r=>r.json())
    .then(data=>{
      const tbody = document.getElementById('files-tbody');
      if(!data.success || data.files.length===0){
        tbody.innerHTML = '<tr><td colspan="5" class="mono" style="text-align:center;padding:24px">No files in your vault yet.</td></tr>';
        document.getElementById('stat-count').textContent = '0';
        document.getElementById('stat-size').textContent = '0 KB';
        document.getElementById('stat-week').textContent = '0';
        return;
      }

      let totalSize = 0;
      let thisWeek = 0;
      const weekAgo = Date.now() - 7*24*60*60*1000;
      filesById = {}; // reset lookup map used by deleteFile() — avoids embedding raw filenames inside onclick attributes

      tbody.innerHTML = data.files.map(f=>{
        totalSize += parseInt(f.file_size);
        if(new Date(f.uploaded_at).getTime() > weekAgo) thisWeek++;
        filesById[f.file_id] = f.original_filename;
        const deleteBtn = `<button class="btn btn-outline btn-sm" style="color:var(--red);border-color:var(--red)" onclick="deleteFile(${f.file_id})">Delete</button>`;
        // Only the owner can download (server enforces this too). Admins see
        // other users' files for management, with the owner shown, but no Download.
        const isOwnFile = Number(f.is_owner) === 1;
        const downloadBtn = isOwnFile
          ? `<button class="btn btn-outline btn-sm" onclick="downloadFile(${f.file_id})">Download</button>`
          : '';
        const lockedTag = `<button class="btn btn-outline btn-sm" disabled style="opacity:.45;cursor:not-allowed" title="Only the owner can download or delete this file">Owner only</button>`;
        const ownerTag = (!isOwnFile && f.owner)
          ? `<div class="mono" style="color:var(--ink-dim);font-size:11px">@${escapeHtml(f.owner)}</div>`
          : '';
        return `<tr>
          <td>${escapeHtml(f.original_filename)}${ownerTag}</td>
          <td class="mono">${formatBytes(f.file_size)}</td>
          <td class="mono">${escapeHtml(f.uploaded_at)}</td>
          <td><span class="badge badge-ok">Encrypted</span></td>
          <td style="display:flex;gap:8px">
            ${isOwnFile ? downloadBtn + ' ' + deleteBtn : lockedTag}
          </td>
        </tr>`;
      }).join('');

      document.getElementById('stat-count').textContent = data.files.length;
      document.getElementById('stat-size').textContent = formatBytes(totalSize);
      document.getElementById('stat-week').textContent = thisWeek;
    })
    .catch(()=>showToast('⛔','Could not load files',''));
}

function handleFileUpload(file){
  if(!file) return;
  showToast('🔐','Encrypting…','AES-256, please wait');

  const formData = new FormData();
  formData.append('file', file);

  fetch('api/upload.php',{method:'POST', body:formData})
    .then(r=>r.json())
    .then(data=>{
      if(data.success){
        showToast('✅','File encrypted','Stored: ' + data.filename);
        loadFiles();
      } else {
        showToast('⛔',data.message,'');
      }
    })
    .catch(()=>showToast('⛔','Upload failed',''));

  document.getElementById('file-input').value = '';
}

// ── Fingerprint step-up verification before a sensitive file action ──
// Generic: pass a `purpose` string (for server-side logging only) and an
// `onVerified` callback — runs AFTER a fresh fingerprint match. Reused by
// both downloadFile() and deleteFile() so every sensitive file action
// requires a fresh scan, not just a one-time session flag.
let fpVerifyBusy = false;
let fpVerifyPollInterval = null;
let fpVerifyRetryFn = null; // re-invoked by the modal's Retry button

function requireFingerprintVerification(purpose, onVerified){
  fpVerifyRetryFn = () => requireFingerprintVerification(purpose, onVerified);

  if(fpVerifyBusy){
    showToast('⏳','Please wait','A fingerprint verification is already in progress');
    return;
  }
  openFpVerifyModal();

  fetch('api/fingerprint_verify_start.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({purpose:purpose})
  })
  .then(r=>r.json())
  .then(data=>{
    if(!data.success){
      failFpVerifyModal(data.message || 'Could not start verification');
      if(data.account_locked){
        setTimeout(()=>{ closeFpVerifyModal(); goTo('s-login'); }, 3500);
      }
      return;
    }
    fpVerifyBusy = true;
    setFpVerifyStatus('scanning', 'Place your finger on the sensor…');

    let attempts = 0;
    const maxAttempts = 14; // ~21 seconds at 1500ms intervals

    fpVerifyPollInterval = setInterval(()=>{
      attempts++;

      fetch('api/fingerprint_verify_poll.php')
      .then(r=>r.json())
      .then(poll=>{
        if(poll.status === 'waiting'){
          if(attempts >= maxAttempts){
            clearInterval(fpVerifyPollInterval);
            fpVerifyBusy = false;
            failFpVerifyModal('Timed out — no scan detected');
          }
          return;
        }

        clearInterval(fpVerifyPollInterval);
        fpVerifyBusy = false;

        if(poll.status === 'matched' && poll.success){
          setFpVerifyStatus('success', '✓ Verified!');
          setTimeout(()=>{
            closeFpVerifyModal();
            onVerified();
          }, 600);
          return;
        }

        if(poll.account_locked){
          failFpVerifyModal(poll.message);
          setTimeout(()=>{ closeFpVerifyModal(); goTo('s-login'); }, 3500);
          return;
        }
        failFpVerifyModal(poll.message || 'Fingerprint did not match');
      })
      .catch(()=>{
        clearInterval(fpVerifyPollInterval);
        fpVerifyBusy = false;
        failFpVerifyModal('Connection error while waiting for scan');
      });
    }, 1500);
  })
  .catch(()=>{
    failFpVerifyModal('Connection error');
  });
}

function openFpVerifyModal(){
  setFpVerifyStatus('scanning', 'Starting…');
  document.getElementById('fp-verify-overlay').classList.add('show');
}

function closeFpVerifyModal(){
  if(fpVerifyPollInterval){ clearInterval(fpVerifyPollInterval); fpVerifyPollInterval = null; }
  fpVerifyBusy = false;
  document.getElementById('fp-verify-overlay').classList.remove('show');
}

function setFpVerifyStatus(state, text){
  const icon = document.getElementById('fp-verify-icon');
  const st = document.getElementById('fp-verify-status-txt');
  const stateIcons = {scanning:FP_ICON_LG, success:'✅', fail:'⛔'};
  icon.innerHTML = stateIcons[state] || FP_ICON_LG;
  st.className = 'bio-status ' + state;
  st.textContent = text;
}

function failFpVerifyModal(message){
  fpVerifyBusy = false;
  setFpVerifyStatus('fail', '✗ ' + message);
  showToast('⛔','Verification failed', message || '');
}

function retryFpVerify(){
  if(fpVerifyRetryFn) fpVerifyRetryFn();
}

function downloadFile(fileId){
  requireFingerprintVerification('download:' + fileId, ()=>{
    showToast('🔓','Decrypting…','Preparing your file');
    window.location.href = 'api/download.php?file_id=' + fileId;
  });
}

let filesById = {}; // populated by loadFiles() — avoids putting raw filenames inside onclick attributes

async function deleteFile(fileId){
  const filename = filesById[fileId] || 'this file';
  const ok = await showConfirm('Delete File', `Permanently delete "${filename}"? This cannot be undone.`, 'Delete');
  if(!ok) return;

  requireFingerprintVerification('delete:' + fileId, ()=>{
    fetch('api/delete_file.php',{
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({file_id:fileId})
    })
    .then(r=>r.json())
    .then(data=>{
      if(data.success){
        showToast('✅','File deleted','');
        loadFiles();
      } else {
        showToast('⛔',data.message||'Could not delete file','');
      }
    })
    .catch(()=>showToast('⛔','Connection error',''));
  });
}

// ── Toast ──
// ── Security: escape user-controlled text before inserting into innerHTML.
// Without this, a malicious filename, full name, or username could contain
// HTML/JS that executes in the browser of anyone who views it (stored XSS).
function escapeHtml(str){
  if(str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function showToast(icon,title,detail){
  document.getElementById('toast-icon').textContent=icon;
  document.getElementById('toast-title').textContent=title;
  document.getElementById('toast-detail').textContent=detail||'';
  const t=document.getElementById('toast');
  t.classList.add('show');
  clearTimeout(window._toastTimer);
  window._toastTimer=setTimeout(()=>t.classList.remove('show'),3200);
}

// ── Shared webcam capture helpers ──
function getSavedCameraId(){
  return localStorage.getItem('securevault_camera_id') || null;
}

function populateCameraSelect(selectEl){
  return navigator.mediaDevices.enumerateDevices().then(devices=>{
    const cams = devices.filter(d=>d.kind === 'videoinput');
    selectEl.innerHTML = '';
    cams.forEach(cam=>{
      const opt = document.createElement('option');
      opt.value = cam.deviceId;
      opt.textContent = cam.label || `Camera ${selectEl.length + 1}`;
      selectEl.appendChild(opt);
    });
    const saved = getSavedCameraId();
    if(saved && cams.some(c=>c.deviceId === saved)){
      selectEl.value = saved;
    }
    selectEl.onchange = () => localStorage.setItem('securevault_camera_id', selectEl.value);
    return cams;
  });
}

function startCamera(videoEl, selectEl){
  const deviceId = (selectEl && selectEl.value) ? selectEl.value : getSavedCameraId();
  const constraints = {
    video: deviceId ? {deviceId:{exact:deviceId}, width:320, height:240} : {width:320, height:240},
    audio: false
  };
  return navigator.mediaDevices.getUserMedia(constraints)
    .then(stream=>{
      videoEl.srcObject = stream;
      videoEl.style.display = 'block';
      return stream;
    });
}

function stopCamera(stream, videoEl){
  if(stream){
    stream.getTracks().forEach(track=>track.stop());
  }
  if(videoEl){
    videoEl.style.display = 'none';
    videoEl.srcObject = null;
  }
}

function captureFrameBase64(videoEl, canvasEl){
  canvasEl.width = videoEl.videoWidth;
  canvasEl.height = videoEl.videoHeight;
  const ctx = canvasEl.getContext('2d');
  ctx.drawImage(videoEl, 0, 0, canvasEl.width, canvasEl.height);
  return canvasEl.toDataURL('image/jpeg', 0.8);
}

function resetEnrollment(){
  // Clears any old saved face images before starting a fresh enrollment
  // or re-scan — otherwise leftover images from a previous session make
  // the server instantly report "done" without checking the new frames.
  return fetch('api/enroll_reset.php', {method:'POST'}).then(r=>r.json());
}

// ── Login ──
let mustChangePassword = false; // set on login; gates navigation until password is changed

function doLogin(){
  const u=document.getElementById('li-user').value;
  const p=document.getElementById('li-pass').value;
  if(!u||!p){showToast('⚠️','Enter credentials','Username and password required');return;}
  showToast('🔒','Verifying credentials…','Checking password hash');

  fetch('api/login.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({username:u,password:p})
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      document.getElementById('sb-username').textContent=data.username;
      document.getElementById('sb-avatar').textContent=data.username.charAt(0).toUpperCase();
      mustChangePassword = !!data.must_change_password;
      showToast('✅','Login successful','Welcome back');
      setTimeout(()=>{
        goTo('s-app');
        fetch('api/whoami.php').then(r=>r.json()).then(who=>{
          if(who.success){
            currentRole = who.role;
            applyRolePermissions();
            updateSidebarAvatar(who);
          }
          if(mustChangePassword){
            showToast('⚠️','Set a new password','A temporary password was issued — you must change it before continuing');
            showPage('p-settings', document.querySelectorAll('.sb-item')[4]);
          } else if(who.success && !who.biometric_enrolled){
            showToast('🔏','Enroll your fingerprint','Needed before you can download or delete files — ask an administrator to enroll it at the kiosk');
            showPage('p-files',document.querySelector('.sb-item'));
            loadFiles();
          } else {
            showPage('p-files',document.querySelector('.sb-item'));
            loadFiles();
          }
        }).catch(()=>{
          showPage('p-files',document.querySelector('.sb-item'));
          loadFiles();
        });
      },500);
    } else {
      showToast('⛔',data.message,'');
    }
  })
  .catch(()=>showToast('⛔','Connection error','Could not reach server'));
}

function requestPasswordReset(){
  const suggested = document.getElementById('li-user').value.trim();
  const username = prompt('Enter your username to request a password reset. An administrator will review and issue a temporary password.', suggested);
  if(!username) return;

  fetch('api/request_reset.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({username:username.trim()})
  })
  .then(r=>r.json())
  .then(data=>{
    showToast(data.success ? '✅' : '⛔', data.success ? 'Request sent' : 'Error', data.message||'');
  })
  .catch(()=>showToast('⛔','Connection error',''));
}

let currentRole = 'user';

// Maps the Arduino's enrollment step names to friendly, step-by-step text —
// so the UI shows exactly what to do instead of one generic "waiting"
// message for the whole ~10-15 second 2-scan process.
function fpEnrollStepText(step){
  const steps = {
    'waiting_first_scan':  'Place your finger on the sensor (scan 1 of 2)…',
    'first_scan_ok':       '✓ First scan captured — lift your finger',
    'remove_finger':       'Lift your finger off the sensor…',
    'waiting_second_scan': 'Place the SAME finger again (scan 2 of 2)…',
    'second_scan_ok':      '✓ Second scan captured — saving…',
  };
  return steps[step] || 'Scanning…';
}

function applyRolePermissions(){
  // Access Control (user list, roles, Add User, Enroll FP, access requests) is
  // admin-only. The server enforces this too (403 for non-admins) — this just
  // hides the menu so regular users don't see buttons they can't use.
  const isAdmin = (currentRole === 'admin');
  document.querySelectorAll('.sb-item[onclick*="p-access"]').forEach(b => {
    b.style.display = isAdmin ? '' : 'none';
  });
  if(!isAdmin && document.getElementById('p-access').classList.contains('active')){
    showPage('p-files', document.querySelector('.sb-item'));
  }
}

// ── Request access (replaces open self-registration) ──
// No account, password or session is created here — an administrator reviews
// the request, creates the account and enrolls the fingerprint at the kiosk.
function submitAccessRequest(){
  const body = {
    full_name: document.getElementById('ra-fullname').value.trim(),
    username:  document.getElementById('ra-username').value.trim(),
    email:     document.getElementById('ra-email').value.trim(),
    note:      document.getElementById('ra-note').value.trim()
  };
  if(!body.full_name || !body.username || !body.email){
    showToast('⚠️','Fill in all fields','Full name, username and email required');
    return;
  }
  if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(body.email)){
    showToast('⚠️','Invalid email','Enter a valid email address');
    return;
  }
  const btn = document.getElementById('ra-submit');
  btn.disabled = true; btn.textContent = 'Sending…';
  fetch('api/request_access.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(body)
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      document.getElementById('ra-form').style.display = 'none';
      document.getElementById('ra-done').style.display = 'block';
    } else {
      showToast('⛔',data.message||'Could not send request','');
    }
  })
  .catch(()=>showToast('⛔','Connection error',''))
  .finally(()=>{ btn.disabled = false; btn.textContent = 'Submit Request →'; });
}

function resetAccessRequestForm(){
  ['ra-fullname','ra-username','ra-email','ra-note'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('ra-form').style.display = 'block';
  document.getElementById('ra-done').style.display = 'none';
}

// ── Logout ──
function doLogout(){
  fetch('api/logout.php',{method:'POST'})
    .then(()=>{
      showToast('🔒','Session ended','');
      setTimeout(()=>goTo('s-login'),800);
    });
}
</script>

</body>
</html>