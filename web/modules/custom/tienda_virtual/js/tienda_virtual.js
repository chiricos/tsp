/**
 * tienda_virtual.js
 * Quick-view modal: clicking a product image opens a modal with
 * full title, description, price, stock and a link to the product page.
 */
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
