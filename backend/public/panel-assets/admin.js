// StudioAI Admin — küçük etkileşimler (çerçevesiz)
document.addEventListener('DOMContentLoaded', () => {
  // Mobil menü
  document.querySelectorAll('[data-menu-toggle]').forEach((btn) =>
    btn.addEventListener('click', () => document.body.classList.toggle('nav-open'))
  );

  // Silme vb. onay gerektiren formlar
  document.querySelectorAll('form[data-confirm]').forEach((form) =>
    form.addEventListener('submit', (e) => {
      if (!window.confirm(form.dataset.confirm)) e.preventDefault();
    })
  );

  // Sekmeler
  document.querySelectorAll('[data-tabs]').forEach((root) => {
    const tabs = root.querySelectorAll('[data-tab]');
    const panels = document.querySelectorAll('[data-panel]');
    tabs.forEach((tab) =>
      tab.addEventListener('click', () => {
        tabs.forEach((t) => t.classList.toggle('active', t === tab));
        panels.forEach((p) => p.classList.toggle('active', p.dataset.panel === tab.dataset.tab));
      })
    );
  });

  // Görsel yükleme önizlemesi
  document.querySelectorAll('input[type=file][data-preview]').forEach((input) =>
    input.addEventListener('change', () => {
      const img = document.getElementById(input.dataset.preview);
      const file = input.files && input.files[0];
      if (img && file) {
        img.src = URL.createObjectURL(file);
        img.classList.remove('hidden');
      }
    })
  );

  // İkon alanı canlı önizleme
  document.querySelectorAll('input[data-icon-preview]').forEach((input) => {
    const target = document.getElementById(input.dataset.iconPreview);
    input.addEventListener('input', () => { if (target) target.textContent = input.value || 'help'; });
  });

  // Bildirimleri otomatik kapat
  document.querySelectorAll('.flash[data-autohide]').forEach((el) =>
    setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 4500)
  );

  // Panoya kopyala
  document.querySelectorAll('[data-copy]').forEach((btn) =>
    btn.addEventListener('click', () => {
      const el = document.getElementById(btn.dataset.copy);
      if (el) navigator.clipboard.writeText(el.innerText).then(() => { btn.querySelector('span:last-child').textContent = 'Kopyalandı'; });
    })
  );
});
