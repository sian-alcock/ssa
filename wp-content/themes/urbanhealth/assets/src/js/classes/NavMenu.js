class NavMenu {
  constructor() {
    this.hamburger = document.getElementById('nav-hamburger');
    this.primaryMenu = document.getElementById('primary-menu');
    this.headerOverlay = document.getElementById('js-header-overlay');
  }

  init() {
    const def = (x) => typeof x !== 'undefined' && x !== null;
    if (def(this.hamburger) && def(this.primaryMenu)) {
      this.events();
    }
  }

  events() {
    document.querySelectorAll('.js-nav-item-has-children').forEach((menuItem) => {
      menuItem.addEventListener('click', () => {
        this.menuItemClicked(menuItem);
      });
    });
    // Detect clicks within header that isn't inside the open navigation to close
    document.querySelector('header').addEventListener('click', (e) => {
      const navList = document.querySelector('.js-nav-list');
      if (!navList) return;

      if (!navList.contains(e.target) && !this.hamburger.contains(e.target)) {
        this.closeOpenMenus();
      }
    });

    window.addEventListener("keyup", (e) => {
      if (e.code === "Tab" && document.activeElement.closest('header') === null) {
        this.closeOpenMenus();
      }
    });

    this.hamburger.addEventListener('click', () => {
      this.hamburgerClick();
    });

    this.headerOverlay.addEventListener('click', () => {
      this.closeMainMenu();
      this.closeOpenMenus();
    });
  }

  hamburgerClick() {
    const { primaryMenu } = this;
    if (primaryMenu.classList.contains('open')) {
      this.closeMainMenu();
    } else {
      this.expandCurrentPageMenu();
      this.openMainMenu();
    }
  }

  closeMainMenu() {
    this.hamburger.classList.remove('is-active');

    this.headerOverlay.classList.remove('open');
    this.primaryMenu.classList.remove('open');
  }

  openMainMenu() {
    this.hamburger.classList.add('is-active');
    this.headerOverlay.classList.add('open');
    this.primaryMenu.classList.add('open');
  }

  menuItemClicked(item) {
    const parentItem = item.parentNode;
    const childMenu = parentItem.querySelector('.js-nav-child-menu');

    if (!this.isMobileMenu()) {
      this.closeOpenMenus();
    }

    if (parentItem.classList.contains('open')) {
      this.closeChildMenu(childMenu, parentItem);
    } else {
      this.openChildMenu(childMenu, parentItem);
    }
  }

  closeOpenMenus() {
    document.querySelectorAll('.js-nav-child-menu').forEach((childMenu) => {
      const parentItem = childMenu.parentNode;
      if (parentItem.classList.contains('open')) {
        this.closeChildMenu(childMenu, parentItem);
      }
    });
  }

  closeChildMenu(childMenu, parentItem) {
    if (!this.isMobileMenu()) {
      this.headerOverlay.classList.remove('open');
    }

    // Give the element a height to change from
    childMenu.style.height = `${childMenu.scrollHeight}px`;
    // debugger;
    // Set the height back to 0
    window.setTimeout(function () {
      childMenu.style.height = '0';
    }, 1);

    // When the transition is complete, hide it
    window.setTimeout(function () {
      parentItem.classList.remove('open');
    }, 200);
  }

  openChildMenu(childMenu, parentItem) {
    // const childMenu = menu.querySelector('.js-nav-child-menu');
    this.headerOverlay.classList.add('open');
    const childMenuHeight = this.getExpandedHeight(childMenu);

    // console.log('openChildMenu', childMenu, parentItem, childMenuHeight);
    parentItem.classList.add('open'); // Make the element visible
    childMenu.style.height = childMenuHeight; // Update the max-height

    // Once the transition is complete, remove the inline max-height so the content can scale responsively
    window.setTimeout(function () {
      childMenu.style.height = '';
    }, 200);
  }

  expandCurrentPageMenu() {
    document.querySelectorAll('.js-nav-child-menu').forEach((childMenu) => {
      const currentItem = childMenu.querySelector('.js-current-menu-item');
      if (!currentItem) return;
      this.openChildMenu(childMenu, childMenu.parentNode);
    });
  }

  // eslint-disable-next-line class-methods-use-this
  getExpandedHeight(el) {
    el.style.display = 'block'; // Make it visible
    const height = `${el.scrollHeight}px`; // Get it's height
    el.style.display = ''; //  Hide it again
    return height;
  }

  isMobileMenu() {
    const hamburgerStyle = window.getComputedStyle(this.hamburger);
    return hamburgerStyle.display !== 'none';
  }
}

export default NavMenu;
