<footer class="footer">
  <p>SECUREVAULT — SECURE FILE STORAGE SYSTEM WITH BIOMETRIC AUTHENTICATION, ENCRYPTION AND REAL-TIME DETECTION</p>
  <p>CBS/09/24B · GMI</p>
</footer>

<script>
// ── Scroll reveal — shared across every page ──
const revealTargets = document.querySelectorAll('.reveal, .reveal-stagger');
if('IntersectionObserver' in window){
  const observer = new IntersectionObserver((entries)=>{
    entries.forEach(entry=>{
      if(entry.isIntersecting){
        entry.target.classList.add('in-view');
        observer.unobserve(entry.target);
      }
    });
  }, {threshold:0.15, rootMargin:'0px 0px -40px 0px'});
  revealTargets.forEach(el=>observer.observe(el));
} else {
  revealTargets.forEach(el=>el.classList.add('in-view'));
}
</script>
