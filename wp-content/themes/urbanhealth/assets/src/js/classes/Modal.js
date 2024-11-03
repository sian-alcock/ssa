class Modal {
  constructor() {
    this.modalsClass = '.js-modal';
    this.modalsClose = document.querySelectorAll('.js-modal-close');
    this.boundListener = this.handleEscapePress.bind(this);
  }

  init() {
    const def = (x) => typeof x !== 'undefined' && x !== null;
    if (def(document.querySelectorAll(this.modalsClass))) {
      this.events();
    }
  }

  events() {
    this.attachEvents(document.querySelectorAll(this.modalsClass));

    // Escape listener
    document.addEventListener('keydown', this.boundListener);
  }

  attachEvents(arrayOfModals) {
    arrayOfModals.forEach((modal) => {
      modal.addEventListener(
        'hide-modal',
        () => {
          this.hideModal(modal);
        },
        false
      );
      modal.addEventListener(
        'show-modal',
        () => {
          this.showModal(modal);
        },
        false
      );

      // If overlay then assign click handler to overlay
      modal.addEventListener('click', (e) => {
        if (!modal.getAttribute('data-overlay')) return;

        const modalContent = modal.children[0];
        const isClickInside = modalContent.contains(e.target);
        if (!isClickInside) {
          this.hideModal(modal);
        }
      });

      modal.querySelectorAll('.js-modal-close').forEach((closeButton) => {
        closeButton.addEventListener('click', () => {
          const modal = closeButton.parentNode;
          if (modal.classList.contains('js-modal')) {
            this.hideModal(modal);
          } else {
            this.hideModal(modal.parentNode);
          }
        });
      });
    });
  }

  attachDynamicEvents(parentEl) {
    this.attachEvents(parentEl.querySelectorAll(this.modalsClass));
  }

  handleEscapePress(e) {
    const evt = e || window.event;
    let isEscape = false;
    if ('key' in evt) {
      isEscape = evt.key === 'Escape' || evt.key === 'Esc';
    } else {
      isEscape = evt.keyCode === 27;
    }
    if (isEscape) {
      const openModal = document.querySelector('.js-modal.open');
      if (openModal) {
        this.hideModal(openModal);
      }
    }
  }

  showModal(modal) {
    modal.classList.add('open');
    modal.setAttribute('aria-hidden', false);
    const focusable = modal.querySelectorAll(
      'button, [href], input, select, textarea, li, a,[tabindex]:not([tabindex="-1"])'
    );
    // Move focus to first el
    this.previouslyFocusedEl = document.activeElement;
    if (focusable[0]) {
      focusable[0].focus();
    }

    // Escape listener
    document.addEventListener('keydown', this.boundListener);
    document.body.style.overflow = 'hidden';
  }

  hideModal(modal) {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', true);
    if (this.previouslyFocusedEl) {
      this.previouslyFocusedEl.focus();
    }
    // Escape listener
    document.removeEventListener('keydown', this.boundListener);
  }
}

export default Modal;
