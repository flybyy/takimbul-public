(() => {
  const button = document.querySelector('.menu-toggle');
  const nav = document.querySelector('.main-nav');
  const actions = document.querySelector('.nav-actions');
  if (!button || !nav) return;
  button.addEventListener('click', () => {
    const open = document.body.classList.toggle('menu-open');
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
  document.addEventListener('click', (event) => {
    if (!document.body.classList.contains('menu-open')) return;
    if (button.contains(event.target) || nav.contains(event.target) || actions?.contains(event.target)) return;
    document.body.classList.remove('menu-open');
    button.setAttribute('aria-expanded', 'false');
  });
})();

(() => {
  const form = document.querySelector('[data-lineup-selector]');
  if (!form) return;
  const limit = Number(form.dataset.starterLimit || 7);
  const starterCount = form.querySelector('[data-starter-count]');
  const subCount = form.querySelector('[data-sub-count]');
  const update = () => {
    const starters = [...form.querySelectorAll('input[data-role="starter"]:checked')];
    const subs = [...form.querySelectorAll('input[data-role="substitute"]:checked')];
    if (starterCount) starterCount.textContent = String(starters.length);
    if (subCount) subCount.textContent = String(subs.length);
    form.querySelectorAll('[data-player-id]').forEach((row) => {
      const starter = row.querySelector('input[data-role="starter"]');
      const sub = row.querySelector('input[data-role="substitute"]');
      if (starter?.checked && sub?.checked) sub.checked = false;
    });
  };
  form.addEventListener('change', (event) => {
    const input = event.target.closest('input[data-role]');
    if (!input) return;
    const row = input.closest('[data-player-id]');
    if (input.checked && row) {
      const other = row.querySelector(`input[data-role="${input.dataset.role === 'starter' ? 'substitute' : 'starter'}"]`);
      if (other) other.checked = false;
    }
    const starters = form.querySelectorAll('input[data-role="starter"]:checked');
    if (starters.length > limit && input.dataset.role === 'starter') input.checked = false;
    const subs = form.querySelectorAll('input[data-role="substitute"]:checked');
    if (subs.length > 7 && input.dataset.role === 'substitute') input.checked = false;
    update();
  });
  update();
})();

(() => {
  const banner = document.querySelector('[data-cookie-consent]');
  if (!banner) return;
  const ga4 = banner.dataset.ga4 || '';
  const key = 'takimbul_analytics_consent';
  const loadAnalytics = () => {
    if (!ga4 || window.__takimbulGaLoaded) return;
    window.__takimbulGaLoaded = true;
    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(ga4)}`;
    document.head.appendChild(script);
    window.dataLayer = window.dataLayer || [];
    window.gtag = function(){ window.dataLayer.push(arguments); };
    window.gtag('js', new Date());
    window.gtag('config', ga4, { anonymize_ip: true });
  };
  const choice = localStorage.getItem(key);
  if (choice === 'accepted') loadAnalytics();
  if (!choice) banner.hidden = false;
  banner.querySelector('[data-cookie-accept]')?.addEventListener('click', () => { localStorage.setItem(key,'accepted'); banner.hidden = true; loadAnalytics(); });
  banner.querySelector('[data-cookie-reject]')?.addEventListener('click', () => { localStorage.setItem(key,'rejected'); banner.hidden = true; });
})();

// TakımBul v9 mobile command sheet
(() => {
  const sheet = document.querySelector('[data-v9-sheet]');
  const open = document.querySelector('[data-v9-sheet-open]');
  if (!sheet || !open) return;
  const closeButtons = sheet.querySelectorAll('[data-v9-sheet-close]');
  const setOpen = (value) => {
    sheet.hidden = !value;
    open.setAttribute('aria-expanded', value ? 'true' : 'false');
    document.documentElement.style.overflow = value ? 'hidden' : '';
  };
  open.addEventListener('click', () => setOpen(true));
  closeButtons.forEach((button) => button.addEventListener('click', () => setOpen(false)));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !sheet.hidden) setOpen(false);
  });
})();

// TakımBul v10 first-visit sponsored takeover
(() => {
  const takeover = document.querySelector('[data-welcome-ad]');
  if (!takeover) return;
  const secure = location.protocol === 'https:' ? '; Secure' : '';
  document.cookie = `takimbul_welcome_ad_seen=1; Path=/; SameSite=Lax${secure}`;
  const close = () => {
    takeover.remove();
    document.documentElement.style.overflow = '';
  };
  document.documentElement.style.overflow = 'hidden';
  takeover.querySelector('[data-welcome-ad-close]')?.addEventListener('click', close);
  takeover.addEventListener('click', (event) => {
    if (event.target === takeover) close();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.body.contains(takeover)) close();
  });
})();

// TakımBul v10.1 amateur player career editor
(() => {
  const form = document.querySelector('[data-amateur-form]');
  const list = document.querySelector('[data-career-list]');
  const add = document.querySelector('[data-career-add]');
  const template = document.querySelector('#career-row-template');
  if (!form || !list || !add || !template) return;

  const reindex = () => {
    [...list.querySelectorAll('[data-career-row]')].forEach((row, index) => {
      row.querySelectorAll('[name^="career["]').forEach((field) => {
        field.name = field.name.replace(/^career\[\d+\]/, `career[${index}]`);
      });
      row.querySelectorAll('[data-career-name]').forEach((field) => {
        field.name = `career[${index}][${field.dataset.careerName}]`;
      });
    });
    add.disabled = list.querySelectorAll('[data-career-row]').length >= 12;
  };

  add.addEventListener('click', () => {
    if (list.querySelectorAll('[data-career-row]').length >= 12) return;
    const node = template.content.cloneNode(true);
    list.appendChild(node);
    reindex();
    list.lastElementChild?.querySelector('input')?.focus();
  });

  list.addEventListener('click', (event) => {
    const remove = event.target.closest('[data-career-remove]');
    if (!remove) return;
    const row = remove.closest('[data-career-row]');
    if (!row) return;
    if (list.querySelectorAll('[data-career-row]').length === 1) {
      row.querySelectorAll('input,textarea').forEach((input) => { input.value = ''; });
      row.querySelectorAll('select').forEach((select) => { select.selectedIndex = 0; });
      return;
    }
    row.remove();
    reindex();
  });

  reindex();

  const intentInputs = form.querySelectorAll('input[name="profile_intent"]');
  const transferFields = form.querySelector('[data-transfer-fields]');
  const syncIntent = () => {
    const selected = form.querySelector('input[name="profile_intent"]:checked');
    const showcaseOnly = selected?.value === 'showcase';
    transferFields?.classList.toggle('is-showcase-optional', showcaseOnly);
    transferFields?.setAttribute('aria-label', showcaseOnly ? 'Transfer tercihleri - vitrin profilinde isteğe bağlı' : 'Transfer tercihleri');
  };
  intentInputs.forEach((input) => input.addEventListener('change', syncIntent));
  syncIntent();
})();

// TakımBul v10.1 share helpers
(() => {
  const copy = document.querySelector('[data-copy-url]');
  if (!copy) return;
  copy.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(location.href);
      const previous = copy.textContent;
      copy.textContent = 'Kopyalandı';
      setTimeout(() => { copy.textContent = previous; }, 1600);
    } catch (_) {
      window.prompt('İlan bağlantısını kopyala:', location.href);
    }
  });
})();

// TakımBul v10.3 authentication UX hardening
(() => {
  document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', () => {
      const targetId = toggle.getAttribute('data-target');
      const input = targetId ? document.getElementById(targetId) : null;
      if (!(input instanceof HTMLInputElement)) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      toggle.textContent = show ? 'Gizle' : 'Göster';
      toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
      toggle.setAttribute('aria-label', show ? 'Şifreyi gizle' : 'Şifreyi göster');
      input.focus({ preventScroll: true });
    });
  });

  document.querySelectorAll('form[data-submit-guard]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (event.defaultPrevented) return;
      if (form.classList.contains('is-submitting')) {
        event.preventDefault();
        return;
      }
      form.classList.add('is-submitting');
      form.setAttribute('aria-busy', 'true');
      form.querySelectorAll('button[type="submit"],input[type="submit"]').forEach((button) => {
        const busy = button.getAttribute('data-busy-text');
        if (busy && button instanceof HTMLButtonElement) {
          button.dataset.originalText = button.textContent || '';
          button.textContent = busy;
        }
        button.setAttribute('disabled', 'disabled');
      });
    });
  });
})();
