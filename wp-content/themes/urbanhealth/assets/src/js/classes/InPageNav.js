import { debounce } from '../helpers/debounce.js';

class InPageNav {
  constructor() {
    this.pageNavigation = document.getElementById('js-page-navigation');
    this.pageNavigationMarker = document.querySelector('.c-page-navigation__page-marker');
    this.activeLinkClass = 'active';
  }

  init() {
    const def = (x) => typeof x !== 'undefined' && x !== null;
    if (def(this.pageNavigation)) {
      this.events();
    }
  }

  events() {


    const pageNavLinks = this.pageNavigation.querySelectorAll('a');

    // close jumpToMenu on link click
    const closeJumpToMenu = (link) => {
      const jumpToMenu = link.parentElement.parentElement.parentElement
      const jumpToButton = jumpToMenu.previousElementSibling
      jumpToMenu.classList.add('hide-on-mob');
      jumpToButton.setAttribute('aria-expanded', false);
      jumpToMenu.setAttribute('aria-hidden', true);
    }

    const { hash } = window.location;
    if (hash) {
      // Add active class if there is a hash link
      pageNavLinks.forEach((link) => {
        const linkHash = link.getAttribute('data-link');
        if (linkHash === hash.substring(1)) {
          link.classList.add(this.activeLinkClass);
        }
      });
    }
    // Add active classes on click
    pageNavLinks.forEach((link) => {
      link.addEventListener('click', () => {
        // Momentarily prevent the intersection handler adding classes
        // this.smoothScrolling = true;
        // setTimeout(() => {
        //   this.smoothScrolling = false;
        // }, 1000);

        closeJumpToMenu(link)

        if (link.classList.contains(this.activeLinkClass)) {
          link.classList.remove(this.activeLinkClass);
        } else {
          this.removeTabActiveStates();
          link.classList.add(this.activeLinkClass);
        }
      });
    });

    // Add class of fixed when in page nav reaches top of page

    window.addEventListener('scroll', debounce(this.makeSticky.bind(this)));

    // Create observer to detect visibility of target text modules
    const textModules = document.querySelectorAll('.js-page-text-module');
    textModules.forEach((textModule) => {
      const activeTabId = textModule.previousElementSibling.id;
      const observer = new IntersectionObserver(
        (entries) => {

          entries.forEach((entry) => {
            // if (this.smoothScrolling) return;
            const visibleTab = document.querySelector(`a[data-link="${activeTabId}"]`);
            if (visibleTab) {
              if (entry.isIntersecting) {

                const anchorLinks = document.querySelectorAll('a.c-page-navigation__link.active');
                anchorLinks.forEach(link => {
                  link.classList.remove('active');
                });

                visibleTab.classList.add(this.activeLinkClass);
              } else {
                visibleTab.classList.remove(this.activeLinkClass);
              }
            }
          });
        },
        {
          threshold: 0.25,
        }
      );
      observer.observe(textModule);
    });
  }

  makeSticky() {
    const topOfPageNavigation = this.pageNavigationMarker.offsetTop;

    if (window.scrollY >= topOfPageNavigation) {
      this.pageNavigation.classList.add('sticky');
    } else {
      this.pageNavigation.classList.remove('sticky');
    }
  }

  removeTabActiveStates() {
    const pageNavLinks = this.pageNavigation.querySelectorAll('a');
    pageNavLinks.forEach((link) => {
      link.classList.remove(this.activeLinkClass);
    });
  }



}

export default InPageNav;
