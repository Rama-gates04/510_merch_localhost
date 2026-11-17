(() => {
  'use strict';
  const rupiah = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0
  }).format(value).replace('IDR', 'Rp').trim();

  // Reveal content with a gentle entrance motion.
  const revealTargets = document.querySelectorAll('.hero-banner, .stat-card, .panel');
  revealTargets.forEach((item, index) => {
    item.style.setProperty('--reveal-delay', `${Math.min(index * 55, 360)}ms`);
    item.classList.add('reveal-ready');
  });
  requestAnimationFrame(() => document.body.classList.add('page-ready'));

  // Animate real summary values; reduced-motion users see the final values immediately.
  document.querySelectorAll('.count-up').forEach((node) => {
    const target = Number(node.dataset.value || 0);
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      node.textContent = rupiah(target);
      return;
    }
    const start = performance.now();
    const duration = 850;
    const tick = (now) => {
      const progress = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      node.textContent = rupiah(Math.round(target * eased));
      if (progress < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  });

  // Product quick preview on the sales form.
  const productSelect = document.querySelector('#produk_id');
  const productPreview = document.querySelector('#productPreview');
  const quantityInput = document.querySelector('#jumlah_jual');
  const renderProductPreview = () => {
    if (!productSelect || !productPreview) return;
    const option = productSelect.selectedOptions[0];
    if (!option || !option.value) {
      productPreview.innerHTML = '<span>Pilih merchandise untuk melihat detail.</span>';
      productPreview.classList.remove('is-active');
      return;
    }
    const price = Number(option.dataset.price || 0);
    const stock = Number(option.dataset.stock || 0);
    const quantity = Math.max(1, Number(quantityInput?.value || 1));
    productPreview.innerHTML = `<div><small>Harga satuan</small><strong>${rupiah(price)}</strong></div><div><small>Stok tersedia</small><strong>${stock} unit</strong></div><div><small>Estimasi total</small><strong>${rupiah(price * quantity)}</strong></div>`;
    productPreview.classList.add('is-active');
    if (quantityInput) quantityInput.max = String(Math.max(stock, 1));
  };
  productSelect?.addEventListener('change', renderProductPreview);
  quantityInput?.addEventListener('input', renderProductPreview);

  // Live search for inventory and transaction history.
  const bindTableSearch = (inputId, tableId, rowSelector, countId) => {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    const rows = [...table.querySelectorAll(rowSelector)];
    const update = () => {
      const query = input.value.trim().toLocaleLowerCase('id');
      let visible = 0;
      rows.forEach((row) => {
        const match = row.textContent.toLocaleLowerCase('id').includes(query);
        row.hidden = !match;
        if (match) visible++;
      });
      const count = countId ? document.getElementById(countId) : null;
      if (count) count.textContent = `${visible} dari ${rows.length} transaksi`;
    };
    input.addEventListener('input', update);
  };
  bindTableSearch('stockSearch', 'stockTable', '[data-search-row]');
  bindTableSearch('historySearch', 'historyTable', '[data-history-row]', 'historyCount');

  // Dismissible server-rendered success/error messages.
  document.querySelectorAll('[data-toast]').forEach((toast) => {
    toast.querySelector('.toast-close')?.addEventListener('click', () => {
      toast.classList.add('toast-dismiss');
      window.setTimeout(() => toast.remove(), 220);
    });
  });

  // Prevent accidental repeated submissions and show an in-button progress state.
  document.querySelectorAll('form').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
        return;
      }
      const button = form.querySelector('button[type="submit"]');
      if (!button || button.disabled) return;
      button.disabled = true;
      button.classList.add('is-loading');
      button.dataset.originalText = button.textContent.trim();
      button.innerHTML = '<span class="button-spinner" aria-hidden="true"></span> Memproses...';
    });
  });
})();
