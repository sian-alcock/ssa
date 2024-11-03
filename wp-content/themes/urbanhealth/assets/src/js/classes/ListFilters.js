import PaginationScrollToTop from './PaginationScrollToTop.js';

class ListFilters {
  constructor() {
    this.filters = document.getElementById('listings-filters');
    this.filters_form = document.querySelectorAll('.js-filters_form');
    // this.search_input = document.getElementById("filters_form");
    this.listingsWrapperEl = document.getElementById('js-listing-wrapper');
    this.clearButtons = document.querySelectorAll('.js-filter-clear');
    this.errorEl = document.getElementById('js-listing-error');
  }

  init() {
    const def = (x) => typeof x !== 'undefined' && x !== null;

    if (def(this.filters_form) && def(this.filters)) {
      // eslint-disable-next-line no-undef
      const currentFilters = globalCurrentFilters;
      // eslint-disable-next-line no-undef
      const currentPostTypes = globalCurrentPostTypes;
      if (currentFilters && currentPostTypes) {
        this.currentFilters = currentFilters;
        this.currentPostTypes = currentPostTypes;
        // eslint-disable-next-line no-undef
        this.currentPreFilters = globalCurrentPreFilters;
        this.events();
      }
    }
  }

  events() {
    this.filters_form.forEach((form) =>
      form.addEventListener('submit', async (evt) => {
        evt.preventDefault();
        const { values, queryString } = this.getValuesQueryString(evt.target);
        const markup = await this.getMarkup(values, queryString);
        this.updateListings(markup);

        // update pagination - needs to research the DOM to get correct links
        const paginationScrollToTop = new PaginationScrollToTop();
        paginationScrollToTop.init();

      })
    );

    // Reload page when backbutton detected
    window.onpopstate = () => {
      window.location.reload();
    };

    if (this.clearButtons) {
      this.clearButtons.forEach((button) => {
        button.addEventListener('click', (e) => {
          e.preventDefault();
          const checkboxesToClear = button.getAttribute('data-checkboxes');
          if (checkboxesToClear === 'all') {
            this.filters.querySelectorAll('input').forEach((checkbox) => {
              checkbox.checked = false;
            });
          } else {
            document.querySelectorAll(`input[name=${checkboxesToClear}]`).forEach((checkbox) => {
              checkbox.checked = false;
            });
          }
        });
      });
    }
  }

  async getMarkup(values, asString) {
    const postID = this.listingsWrapperEl.getAttribute('data-post');

    this.setLoading();
    const form = new FormData();
    form.append('action', 'return_listings_ajax');
    form.append('postId', postID);
    form.append('filters', JSON.stringify(this.currentFilters));
    form.append('postTypes', JSON.stringify(this.currentPostTypes));
    form.append('queryString', asString);
    form.append('preFilters', JSON.stringify(this.currentPreFilters));
    // eslint-disable-next-line no-undef
    form.append('nonce', main_js.nonce);

    Object.keys(values).forEach((key) => {
      form.append(key, values[key]);
    });

    try {
      // eslint-disable-next-line no-undef
      const response = await fetch(main_js.ajaxurl, {
        method: 'POST',
        body: form,
        credentials: 'same-origin',
      });
      if (response.ok) {
        this.removeLoading();
        // if HTTP-status is 200-299
        const markup = await response.text();
        this.constructor.updateUrl(asString);
        this.constructor.closeTabs();
        return markup;
      }
      console.error('Get listing error:', response.status);
      this.showError();
    } catch (e) {
      console.error('Get listing error:', e);
      this.showError();
    }
  }

  getValuesQueryString(form) {
    const formData = new FormData(form);
    const checkedValues = Object.keys(this.currentFilters).reduce((accumulator, checkboxName) => {
      if (formData.getAll(checkboxName).length) {
        accumulator[checkboxName] = formData.getAll(checkboxName);
      }
      return accumulator;
    }, {});
    const values = new URLSearchParams(checkedValues);
    return {
      values: checkedValues,
      queryString: values.toString(),
    };
  }

  setLoading() {
    this.listingsWrapperEl.classList.add('loading');
  }

  removeLoading() {
    this.listingsWrapperEl.classList.remove('loading');
  }

  showError() {
    this.errorEl.style.display = 'block';
    this.listingsWrapperEl.style.display = 'none';
  }

  hideError() {
    this.errorEl.style.display = 'none';
    this.listingsWrapperEl.style.display = 'block';
  }

  static updateUrl(queryString) {
    // Construct url to change to
    const newURL = `${window.location.protocol}//${window.location.host}${window.location.pathname}?${queryString}`;
    window.history.pushState(null, null, newURL);
  }

  static closeTabs() {
    const closeTabsEvent = new Event('close-all-tabs');
    const tabEl = document.getElementById('js-tabs');
    if (tabEl) {
      tabEl.dispatchEvent(closeTabsEvent);
    }
  }

  updateListings(markup) {
    this.listingsWrapperEl.innerHTML = markup;
  }

}

export default ListFilters;
