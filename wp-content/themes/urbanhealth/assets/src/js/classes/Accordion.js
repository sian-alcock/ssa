class Accordion {
  constructor() {
    this.accordionButtons = document.querySelectorAll('.js-accordion-button');
    this.jumptoButton = document.querySelector('.js-jump-to-button');
  }

  init() {
    const def = (x) => typeof x !== 'undefined' && x !== null;

    if (def(this.accordionButtons)) {
      this.events();
    }
  }

  events() {

    if (this.accordionButtons) {
      this.accordionButtons.forEach((button) => {
        button.addEventListener('click', (e) => {
          e.preventDefault();
          this.constructor.handleAccordionClick(button);
        });
      });
    }



    // this accordiion needs to show on desktop so added new class .hide-on-mob

    if (this.jumptoButton) {
      this.jumptoButton.addEventListener('click', (e) => {
        e.preventDefault();
        this.constructor.handleJumpToClick(this.jumptoButton);
      })
    }

  }

  static handleAccordionClick(button) {
    const hiddenContent = button.nextElementSibling;
    const expanded = button.getAttribute('aria-expanded');
    if (expanded && expanded !== 'false') {
      // Close accordion
      hiddenContent.classList.add('hide');
      button.setAttribute('aria-expanded', false);
      hiddenContent.setAttribute('aria-hidden', true);
    } else {
      // Open accordion
      hiddenContent.classList.remove('hide');
      button.setAttribute('aria-expanded', true);
      hiddenContent.setAttribute('aria-hidden', false);
    }
  }

  static handleJumpToClick(button) {
    // this accordiion needs to show on desktop so added new class .hide-on-mob
    const hiddenContent = button.nextElementSibling;
    const expanded = button.getAttribute('aria-expanded');
    if (expanded && expanded !== 'false') {
      // Close accordion
      hiddenContent.classList.add('hide-on-mob');
      button.setAttribute('aria-expanded', false);
      hiddenContent.setAttribute('aria-hidden', true);
    } else {
      // Open accordion
      hiddenContent.classList.remove('hide-on-mob');
      button.setAttribute('aria-expanded', true);
      hiddenContent.setAttribute('aria-hidden', false);
    }
  }
}

export default Accordion;
