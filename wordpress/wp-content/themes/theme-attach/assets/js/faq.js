document.addEventListener('DOMContentLoaded', () => {
  const root = document.querySelector('.js-faq');
  if (!root) return;

  const items = root.querySelectorAll('.faq-item');
  if (!items.length) return;

  // ==========================
  // ABRIR PRIMER ITEM POR DEFECTO
  // ==========================
  const firstItem = items[0];
  const firstBtn = firstItem.querySelector('.faq-item__question');
  const firstPanel = firstItem.querySelector('.faq-item__answer');

  if (firstBtn && firstPanel) {
    firstItem.classList.add('is-open');
    firstBtn.setAttribute('aria-expanded', 'true');
    firstPanel.removeAttribute('hidden');
  }

  // ==========================
  // ACCORDION CLICK
  // ==========================
  items.forEach(item => {
    const btn = item.querySelector('.faq-item__question');
    const panel = item.querySelector('.faq-item__answer');
    if (!btn || !panel) return;

    btn.addEventListener('click', () => {
      const isOpen = item.classList.contains('is-open');

      // cerrar todos
      items.forEach(i => {
        i.classList.remove('is-open');
        const b = i.querySelector('.faq-item__question');
        const p = i.querySelector('.faq-item__answer');
        if (b) b.setAttribute('aria-expanded', 'false');
        if (p) p.setAttribute('hidden', '');
      });

      // abrir el actual si estaba cerrado
      if (!isOpen) {
        item.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        panel.removeAttribute('hidden');

        // scroll suave opcional
        const rect = btn.getBoundingClientRect();
        if (rect.top < 0 || rect.top > window.innerHeight) {
          btn.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  });
});
