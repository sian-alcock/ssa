class PaginationScrollToTop {

  constructor() {
    this.listingsTopAnchor = document.getElementById('listings-top');
    this.isSearch = document.getElementById('search_results');
  }


  init() {
    const def = (x) => typeof x !== 'undefined' && x !== null;
    if (def(this.listingsTopAnchor)) {
      this.events();
    }
  }

  events() {
    const anchorPosition = this.listingsTopAnchor.offsetTop;
    const paginationLinks = document.querySelectorAll(".page-link");

    if (paginationLinks.length > 0) {
      paginationLinks.forEach(function (paginationLink) {
        paginationLink.addEventListener('click', function (event) {
          event.preventDefault();
          const href = paginationLink.getAttribute('data-href');
          window.location.href = href;
        });
      });
    }

    if (this.isSearch == null) {
      if (/[?&]/.test(location.search)) { //eslint-disable-line
        window.scroll(0, anchorPosition);
      }
    } else {
      if (/(page\/[0-9]+\?)/.test(location.href)) { //eslint-disable-line
        window.scroll(0, anchorPosition);
      }
    }
  }
}

export default PaginationScrollToTop;

