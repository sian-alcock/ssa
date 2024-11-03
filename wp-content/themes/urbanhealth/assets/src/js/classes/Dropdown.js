class Dropdown {
  constructor() {
    this.dropdown = document.querySelector('.js-search-options');
    this.dropdownTab = document.querySelector('.js-search-tab');
  }

  init() {
    const def = (x) => typeof x !== 'undefined' && x !== null;
    if (def(this.dropdown)) {
      this.events();
    }
  }

  events() {
    this.dropdownTab.addEventListener('click', () => {
      this.toggleDropdown();
    });
  }

  toggleDropdown() {
    this.dropdown.classList.toggle('active');
  }
}

export default Dropdown;
