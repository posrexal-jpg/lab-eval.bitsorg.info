(function () {
  // Print buttons (CSP forbids inline handlers)
  document.addEventListener('click', function (e) {
    var p = e.target.closest('[data-print]');
    if (p) { e.preventDefault(); window.print(); }
    var c = e.target.closest('[data-copy]');
    if (c && navigator.clipboard) {
      navigator.clipboard.writeText(c.getAttribute('data-copy')).then(function () {
        var t = c.textContent; c.textContent = 'Copied'; setTimeout(function () { c.textContent = t; }, 1200);
      });
    }
  });

  // Confirm dialogs on destructive forms
  document.addEventListener('submit', function (e) {
    var msg = e.target.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) { e.preventDefault(); return; }
    var btn = e.target.querySelector('[data-once]');
    if (btn) { btn.disabled = true; btn.textContent = 'Sending…'; }
  });

  // Auto refresh for the wall monitor
  var refresh = parseInt(document.body.getAttribute('data-refresh'), 10);
  if (refresh > 0) { setTimeout(function () { window.location.reload(); }, refresh * 1000); }

  // Public form: prefill room/date, and restore answers after a validation error
  var form = document.getElementById('evalform');
  if (form) {
    var room = form.getAttribute('data-room');
    var roomInput = form.querySelector('[name="room_no"]');
    if (roomInput && !roomInput.value) { roomInput.value = room; }
    var dateInput = form.querySelector('input[type="date"][name="date"]');
    if (dateInput && !dateInput.value) {
      var d = new Date(); var m = ('0' + (d.getMonth() + 1)).slice(-2); var day = ('0' + d.getDate()).slice(-2);
      dateInput.value = d.getFullYear() + '-' + m + '-' + day;
    }
    // Pre-pick the matching "Computer Laboratory N" radio when the room name equals an option.
    var lab = form.querySelectorAll('input[type="radio"][name="laboratory"]');
    lab.forEach(function (r) { if (r.value === room && !form.querySelector('input[name="laboratory"]:checked')) { r.checked = true; } });

    var old = {};
    try { old = JSON.parse(form.getAttribute('data-old') || '{}'); } catch (err) {}
    Object.keys(old).forEach(function (k) {
      if (k.charAt(0) === '_' || k === 'contact_url_hp') { return; }
      var v = old[k]; var vals = Array.isArray(v) ? v : [v];
      var els = form.querySelectorAll('[name="' + k + '"], [name="' + k + '[]"]');
      els.forEach(function (el) {
        if (el.type === 'radio' || el.type === 'checkbox') { el.checked = vals.indexOf(el.value) !== -1; }
        else { el.value = vals[0]; }
      });
    });
  }
})();
