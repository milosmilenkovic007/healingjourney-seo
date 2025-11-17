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

// Populate Task List selector in modal
window.addEventListener('DOMContentLoaded', function() {
  const listSelect = document.getElementById('newtask-list-select');
  if (!listSelect) return;
  let lists = [];
  const raw = document.body.getAttribute('data-tasklists');
  if (raw) {
    try {
      lists = JSON.parse(raw);
    } catch (e) { lists = []; }
  }
  listSelect.innerHTML = '';
  if (Array.isArray(lists) && lists.length > 0) {
    listSelect.innerHTML = '<option value="">Select list</option>' +
      lists.map(l => `<option value="${l.term_id}">${l.name}</option>`).join('');
  } else {
    listSelect.innerHTML = '<option value="">No lists found</option>';
  }
});