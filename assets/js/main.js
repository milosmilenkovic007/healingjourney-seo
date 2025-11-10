(() => {
  // Tabs
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-tab]');
    if (!btn) return;
    const id = btn.getAttribute('data-tab');
    const group = btn.closest('.tabs');
    group.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    const panels = document.querySelectorAll('.tabpanel');
    panels.forEach(p => p.classList.remove('active'));
    const active = document.getElementById(id);
    if (active) active.classList.add('active');
  });
})();