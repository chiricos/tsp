/**
 * tienda_virtual.js
 * Quick-view modal: clicking a product card image opens a Bootstrap-style
 * modal with full title, description, price, stock and a link to the product.
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.tiendaModal = {
    attach: function (context, settings) {

      // Build modal once
      if (!document.getElementById('tv-modal')) {
        const overlay = document.createElement('div');
        overlay.id        = 'tv-modal';
        overlay.className = 'tv-modal-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.innerHTML = `
          <div class="tv-modal">
            <button class="tv-modal-close" aria-label="Cerrar">&#x2715;</button>
            <img class="tv-modal__img" src="" alt="" />
            <div class="tv-modal__body">
              <div class="mb-1"><span class="badge bg-secondary tv-modal__category"></span></div>
              <h2 class="h5 fw-bold tv-modal__title"></h2>
              <p class="text-muted small tv-modal__desc"></p>
              <div class="d-flex align-items-center gap-3 mb-3">
                <span class="fs-5 fw-bold text-primary tv-modal__price"></span>
                <span class="small tv-modal__stock"></span>
              </div>
              <a href="#" class="btn btn-warning fw-bold text-dark w-100" id="tv-modal-link">
                🛒 Comprar ahora
              </a>
            </div>
          </div>`;
        document.body.appendChild(overlay);

        overlay.addEventListener('click', function (e) {
          if (e.target === overlay) closeModal();
        });
        overlay.querySelector('.tv-modal-close').addEventListener('click', closeModal);
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') closeModal();
        });
      }

      // Attach click to every card image wrap
      once('tv-card-click', '.tv-card-image-link', context).forEach(function (imgWrap) {
        imgWrap.addEventListener('click', function (e) {
          e.preventDefault();
          const card = imgWrap.closest('.tv-card');
          if (!card) return;

          const img      = imgWrap.querySelector('img');
          const url      = card.dataset.url      || '#';
          const title    = card.dataset.title    || '';
          const price    = card.dataset.price    || '';
          const stock    = parseInt(card.dataset.stock || '0', 10);
          const category = card.dataset.category || '';
          const body     = card.dataset.body     || '';

          const modal = document.getElementById('tv-modal');
          modal.querySelector('.tv-modal__img').src          = img ? img.src : '';
          modal.querySelector('.tv-modal__img').alt          = title;
          modal.querySelector('.tv-modal__title').textContent   = title;
          modal.querySelector('.tv-modal__category').textContent = category;
          modal.querySelector('.tv-modal__price').textContent   = price;
          modal.querySelector('.tv-modal__desc').textContent    = body;

          const stockEl = modal.querySelector('.tv-modal__stock');
          if (stock > 0) {
            stockEl.textContent = '✅ ' + stock + ' en stock';
            stockEl.className   = 'small tv-stock-ok tv-modal__stock';
          } else {
            stockEl.textContent = '❌ Sin stock';
            stockEl.className   = 'small tv-stock-empty tv-modal__stock';
          }
          modal.querySelector('#tv-modal-link').href = url;

          openModal(modal);
        });
      });

      function openModal(el) {
        el.classList.add('is-open');
        document.body.style.overflow = 'hidden';
      }
      function closeModal() {
        const el = document.getElementById('tv-modal');
        if (el) { el.classList.remove('is-open'); document.body.style.overflow = ''; }
      }
    }
  };

})(jQuery, Drupal);
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.tiendaModal = {
    attach: function (context, settings) {

      // ── Build modal once ─────────────────────────────────────────────
      if (!document.getElementById('tv-modal')) {
        const overlay = document.createElement('div');
        overlay.id        = 'tv-modal';
        overlay.className = 'tv-modal-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.innerHTML = `
          <div class="tv-modal">
            <button class="tv-modal__close" aria-label="Cerrar">&#x2715;</button>
            <img class="tv-modal__img" src="" alt="" />
            <div class="tv-modal__body">
              <div class="tv-modal__category"></div>
              <h2 class="tv-modal__title"></h2>
              <div class="tv-modal__desc"></div>
              <div class="tv-modal__meta">
                <span class="tv-modal__price"></span>
                <span class="tv-stock"></span>
              </div>
              <a href="#" class="tv-btn tv-btn--primary" id="tv-modal-link"
                 style="display:inline-flex;margin-top:.85rem;">
                🛒 Comprar ahora
              </a>
            </div>
          </div>`;
        document.body.appendChild(overlay);

        // Close on overlay click
        overlay.addEventListener('click', function (e) {
          if (e.target === overlay) { closeModal(); }
        });

        // Close button
        overlay.querySelector('.tv-modal__close')
          .addEventListener('click', closeModal);

        // ESC key
        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') { closeModal(); }
        });
      }

      // ── Attach click to every card image ─────────────────────────────
      once('tv-card-click', '.tv-product-card', context).forEach(function (card) {
        const imgLink = card.querySelector('.tv-card-image-link');
        if (!imgLink) { return; }

        imgLink.addEventListener('click', function (e) {
          e.preventDefault();

          const img      = card.querySelector('.tv-card-image');
          const title    = card.querySelector('.tv-card-title a');
          const summary  = card.querySelector('.tv-card-summary');
          const price    = card.querySelector('.tv-price');
          const stock    = card.querySelector('.tv-stock');
          const badge    = card.querySelector('.tv-badge');
          const url      = title ? title.href : '#';

          const modal = document.getElementById('tv-modal');
          modal.querySelector('.tv-modal__img').src         = img  ? img.src   : '';
          modal.querySelector('.tv-modal__img').alt         = img  ? img.alt   : '';
          modal.querySelector('.tv-modal__title').textContent  = title  ? title.textContent  : '';
          modal.querySelector('.tv-modal__category').textContent = badge ? badge.textContent : '';
          modal.querySelector('.tv-modal__price').textContent  = price  ? price.textContent  : '';
          modal.querySelector('.tv-modal__desc').innerHTML   = summary ? '<p>' + summary.textContent + '</p>' : '';

          const stockEl = modal.querySelector('.tv-stock');
          stockEl.textContent = stock ? stock.textContent : '';
          stockEl.className   = stock ? stock.className   : 'tv-stock';

          modal.querySelector('#tv-modal-link').href = url;

          openModal(modal);
        });
      });

      // ── Helpers ───────────────────────────────────────────────────────
      function openModal(el) {
        el.classList.add('is-open');
        document.body.style.overflow = 'hidden';
      }
      function closeModal() {
        const el = document.getElementById('tv-modal');
        if (el) {
          el.classList.remove('is-open');
          document.body.style.overflow = '';
        }
      }
    }
  };

})(jQuery, Drupal);
