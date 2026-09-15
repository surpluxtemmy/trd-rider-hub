(function () {
  var root = document.documentElement;
  var BASE = root.getAttribute('data-base') || '';

  if ('serviceWorker' in navigator) {
    var swUrl = BASE + '/sw.js';
    navigator.serviceWorker.register(swUrl, { scope: BASE + '/' || '/' }).catch(function () {});
  }

  var deferredPrompt = null;
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    document.querySelectorAll('[data-install]').forEach(function (btn) {
      btn.hidden = false;
      btn.addEventListener('click', function () {
        if (!deferredPrompt) return;
        deferredPrompt.prompt();
        deferredPrompt.userChoice.finally(function () {
          deferredPrompt = null;
        });
      });
    });
  });

  document.querySelectorAll('[data-menu]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.body.classList.toggle('nav-open');
    });
  });

  document.querySelectorAll('.flash').forEach(function (el) {
    setTimeout(function () {
      el.classList.add('is-out');
    }, 5200);
  });

  document.querySelectorAll('input[type=file][data-preview]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var id = inp.getAttribute('data-preview');
      var slot = id ? document.getElementById(id) : null;
      if (!slot) return;
      var file = inp.files && inp.files[0];
      if (!file) {
        slot.textContent = 'Choose file';
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
        slot.textContent = 'File is larger than 2 MB';
        slot.classList.add('is-err');
        inp.value = '';
        return;
      }
      slot.classList.remove('is-err');
      var ok = /^(image\/(jpeg|png|webp)|application\/pdf)$/.test(file.type) || /\.(jpe?g|png|webp|pdf)$/i.test(file.name);
      if (!ok) {
        slot.textContent = 'Use JPG, PNG, WebP or PDF';
        slot.classList.add('is-err');
        inp.value = '';
        return;
      }
      if (file.type.indexOf('image/') === 0) {
        var url = URL.createObjectURL(file);
        slot.innerHTML = '';
        var img = document.createElement('img');
        img.src = url;
        img.alt = '';
        slot.appendChild(img);
        var cap = document.createElement('em');
        cap.textContent = file.name;
        slot.appendChild(cap);
      } else {
        slot.textContent = file.name;
      }
    });
  });

  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button[type=submit], .btn-primary');
      if (btn && !btn.classList.contains('is-busy')) {
        btn.classList.add('is-busy');
        btn.setAttribute('aria-busy', 'true');
      }
    });
  });

  var sos = document.getElementById('sos-form');
  if (sos && navigator.geolocation) {
    var status = document.getElementById('geo-status');
    navigator.geolocation.getCurrentPosition(function (pos) {
      var lat = sos.querySelector('[name=lat]');
      var lng = sos.querySelector('[name=lng]');
      if (lat) lat.value = String(pos.coords.latitude);
      if (lng) lng.value = String(pos.coords.longitude);
      if (status) status.textContent = 'Location attached to this SOS.';
    }, function () {
      if (status) status.textContent = 'Location was not attached. You can still send SOS.';
    }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 30000 });
  }

  document.querySelectorAll('.choose').forEach(function (el) {
    el.addEventListener('click', function () {
      document.querySelectorAll('.choose').forEach(function (o) { o.classList.remove('is-on'); });
      el.classList.add('is-on');
    });
  });
})();
