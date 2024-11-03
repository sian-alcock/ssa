class Tabs {
  constructor() {
    this.tabs = document.querySelectorAll('[role="tab"]');
    this.tabList = document.querySelector('[role="tablist"]');
    this.tabEl = document.getElementById('js-tabs');
  }

  init() {
    const def = (x) => typeof x !== 'undefined' && x !== null;

    if (def(this.tabs) && def(this.tabList) && def(this.tabEl)) {
      this.events();
    }
  }

  events() {
    this.tabs.forEach((tab) => {
      tab.addEventListener('click', this.constructor.changeTabs);
    });
    document.addEventListener('click', (event) => {
      const isClickInside = this.tabEl.contains(event.target);

      if (!isClickInside) {
        this.constructor.hideAllTabs();
        // the click was outside the specifiedElement, do something
      }
    });
    this.tabEl.addEventListener(
      'close-all-tabs',
      () => {
        this.constructor.hideAllTabs();
      },
      false
    );

    // Enable arrow navigation between tabs in the tab list
    let tabFocus = 0;

    this.tabList.addEventListener('keydown', (e) => {
      // Move right
      if (e.keyCode === 39 || e.keyCode === 37) {
        this.tabs[tabFocus].setAttribute('tabindex', -1);
        if (e.keyCode === 39) {
          tabFocus++;
          // If we're at the end, go to the start
          if (tabFocus >= this.tabs.length) {
            tabFocus = 0;
          }
          // Move left
        } else if (e.keyCode === 37) {
          tabFocus--;
          // If we're at the start, move tso the end
          if (tabFocus < 0) {
            tabFocus = this.tabs.length - 1;
          }
        }

        this.tabs[tabFocus].setAttribute('tabindex', 0);
        this.tabs[tabFocus].focus();
      }
    });
  }

  static hideAllTabs() {
    document
      .querySelectorAll('[aria-selected="true"]')
      .forEach((t) => t.setAttribute('aria-selected', false));
    document.querySelectorAll('[role="tabpanel"]').forEach((p) => p.setAttribute('hidden', true));
    document.querySelector('[role="tablist"]').classList.remove('active');
  }

  static changeTabs(e) {
    const { target } = e;
    const parent = target.parentNode;
    const grandparent = parent.parentNode;
    const targetIsActive = target.getAttribute('aria-selected') !== 'false';
    // Remove all current selected tabs
    parent
      .querySelectorAll('[aria-selected="true"]')
      .forEach((t) => t.setAttribute('aria-selected', false));

    // Set this tab as selected
    if (!targetIsActive) {
      parent.classList.add('active');
      target.setAttribute('aria-selected', true);
    } else {
      parent.classList.remove('active');
    }

    // Hide all tab panels
    grandparent
      .querySelectorAll('[role="tabpanel"]')
      .forEach((p) => p.setAttribute('hidden', true));

    // Show the selected panel
    if (!targetIsActive) {
      grandparent.parentNode
        .querySelector(`#${target.getAttribute('aria-controls')}`)
        .removeAttribute('hidden');
    }
  }
}

export default Tabs;
