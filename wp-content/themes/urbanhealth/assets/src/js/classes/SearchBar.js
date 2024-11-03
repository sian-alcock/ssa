class SearchBar {
  constructor() {
    this.searchModalTrigger = document.getElementById("js-search-trigger");
    this.searchModal = document.querySelector(".js-modal");
  }

  init() {
    const def = (x) => typeof x !== "undefined" && x !== null;
    if (def(this.searchModalTrigger) && def(this.searchModal)) {
      this.events();
    }
  }

  events() {
    this.searchModalTrigger.addEventListener("click", () => {
      const grandparent = this.searchModalTrigger.parentNode.parentNode;
      const modalToOpen = grandparent.querySelector(".js-modal");
      const showModalEvent = new Event("show-modal");

      modalToOpen.dispatchEvent(showModalEvent);
    });
  }
}

export default SearchBar;
