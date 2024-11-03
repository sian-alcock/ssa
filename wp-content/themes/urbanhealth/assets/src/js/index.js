import AOS from 'aos';
import Accordion from './classes/Accordion.js';
import Dropdown from './classes/Dropdown.js';
import InPageNav from './classes/InPageNav.js';
import Init from './classes/Init';
import ListFilters from './classes/ListFilters.js';
import Modal from './classes/Modal.js';
import NavMenu from './classes/NavMenu.js';
import PaginationScrollToTop from './classes/PaginationScrollToTop.js';
import Questions from './classes/Questions.js';
import SearchBar from './classes/SearchBar.js';
import Tabs from './classes/Tabs.js';
import VideoHero from './classes/VideoHero.js';


let globalModal = null;

const siteFunctions = {
  AOS__ready() {
    AOS.init();
  },

  Accordion__ready() {
    const accordion = new Accordion();
    accordion.init();
  },

  Dropdown__ready() {
    const dropdown = new Dropdown();
    dropdown.init();
  },

  InPageNav__ready() {
    const inPageNav = new InPageNav();
    inPageNav.init();
  },

  ListFilters__ready() {
    const listFilters = new ListFilters();
    listFilters.init();
  },

  Modal__ready() {
    const modal = new Modal();
    globalModal = modal;
    globalModal.init();

  },

  NavMenu__ready() {
    const navMenu = new NavMenu();
    navMenu.init();
  },

  PaginationScrollToTop__ready() {
    const paginationScrollToTop = new PaginationScrollToTop();
    paginationScrollToTop.init();
  },

  Questions__ready() {
    const questions = new Questions(globalModal);
    questions.init();
  },

  SearchBar__ready() {
    const searchBar = new SearchBar();
    searchBar.init();
  },

  Tabs__ready() {
    const tabs = new Tabs();
    tabs.init();
  },

  VideoHero__ready() {
    VideoHero();
  },

  addRolesToWYSIWYGLists__ready() {
    const listParents = document.querySelectorAll(".o-wysiwyg > ul, .o-wysiwyg > ol");
    for (let i = 0; i < listParents.length; i++) {
      listParents[i].setAttribute("role", "list");
    }

    const listChildren = document.querySelectorAll(".o-wysiwyg > ul > li, .o-wysiwyg > ol > li");
    for (let i = 0; i < listChildren.length; i++) {
      listChildren[i].setAttribute("role", "listitem");
    }
  },

  setAriaRoleOnHamburger() {
    const hamburger = document.getElementById("nav-hamburger");
    hamburger.addEventListener('click', () => {
      hamburger.ariaExpanded = !JSON.parse(hamburger.ariaExpanded);
    })
  },


};

window.functionCore = new Init(siteFunctions);
