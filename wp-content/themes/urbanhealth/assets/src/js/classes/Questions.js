class Questions {
  constructor(modal) {
    this.modal = modal;
    this.questionsWrapper = document.getElementById('js-question-wrapper');
    this.questions = document.querySelectorAll('.js-question');
    this.options = document.querySelectorAll('.js-question-option');
    this.optionChoice = document.querySelectorAll('.js-question-option-choice');
    this.backButton = document.getElementById('js-questions-back');
    this.optionChoiceClass = '.js-question-option-choice';
    this.optionClass = '.js-question-option';
    this.questionsClass = '.js-question';
    this.popUpButtonsClass = '.js-question-popup';
    this.noneButtonClass = '.js-question-none-option';
  }

  init() {
    const def = (x) => typeof x !== 'undefined' && x !== null;
    if (
      def(this.options) &&
      def(this.optionChoice) &&
      def(this.questions) &&
      def(this.backButton)
    ) {
      this.events();
    }
  }

  events() {
    // As html is added dynamically attach listener to static parent
    this.questionsWrapper.addEventListener('click', (e) => {
      // Choice made
      if (e.target.closest(this.optionChoiceClass)) {
        const option = e.target.closest(this.optionClass);
        this.optionChosen(option);
      }
      // Popup opened manually
      if (e.target.closest(this.popUpButtonsClass)) {
        const option = e.target.closest(this.optionClass);
        this.handlePopUpButtonClick(e.target, option);
      }

      // Next clicked inside popup
      if (e.target.closest('.js-popup-option-chosen')) {
        const option = e.target.closest(this.optionClass);
        const modal = e.target.closest('.js-modal');
        this.constructor.hideModal(modal);
        this.optionChosen(option, true);
      }

      // None of the above clicked
      if (e.target.closest(this.noneButtonClass)) {
        const question = e.target.closest(this.questionsClass);
        this.noneOptionHandler(question);
      }
    });

    // Back button clicked
    this.backButton.addEventListener('click', () => {
      this.backToPreviousQuestion();
    });
  }

  optionChosen(option, popupOptionChosen = false) {
    /**
     * @typedef modalType
     * @type {string}  "no" | "after_selection" | "popup_button"
     */
    /**
     * @typedef chosenType
     * @type {string}  "next-question" | "success-ending" | "incorrect"
     */

    /** @type {modalType} */
    const modalType = option.getAttribute('data-modal-type');

    /** @type {chosenType} */

    const chosenType = option.getAttribute('data-chosen-type');

    // Update hidden input
    if (option.parentNode.querySelector('input[type="hidden"]')) {
      const optionValue = option.getAttribute('data-value');
      option.parentNode.querySelector('input[type="hidden"]').value = optionValue;
    }

    if (modalType === 'after_selection' && !popupOptionChosen) {
      const modal = option.querySelector('.js-modal');
      return this.constructor.openModal(modal);
    }

    if (chosenType === 'next-question') {
      return this.moveToNextQuestion(option);
    }

    if (chosenType === 'success-ending') {
      return this.showSuccess(option);
    }
    if (chosenType === 'incorrect') {
      return this.showUnsuccessful(option);
    }
  }

  async moveToNextQuestion(option) {
    const nextQuestionId = option.getAttribute('data-next-question-id');
    const nextQuestionMarkup = await this.getNextQuestion(nextQuestionId);
    if (!nextQuestionMarkup) return;
    this.insertNewQuestion(nextQuestionMarkup);
    // Attach modal events to newly added content
    this.modal.attachDynamicEvents(this.latestQuestion());
    return this.handleBackButtonVisibility();
  }

  handlePopUpButtonClick(button, option) {
    const modal = option.querySelector('.js-modal');
    this.constructor.openModal(modal);
  }

  static openModal(modal) {
    const showModalEvent = new Event('show-modal');
    modal.dispatchEvent(showModalEvent);
  }

  static hideModal(modal) {
    const showModalEvent = new Event('hide-modal');
    modal.dispatchEvent(showModalEvent);
  }

  async getNextQuestion(questionId) {
    const data = new FormData();
    data.append('action', 'return_next_question');
    data.append('questionId', questionId);
    // eslint-disable-next-line no-undef
    data.append('nonce', main_js.nonce);

    try {
      // eslint-disable-next-line no-undef
      const response = await fetch(main_js.ajaxurl, {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
      });
      if (response.ok) {
        // if HTTP-status is 200-299
        const markup = await response.text();
        return markup;
      }

      this.showError();
    } catch (e) {
      this.showError();
    }
  }

  hideQuestions() {
    document.querySelectorAll(this.questionsClass).forEach((question) => {
      question.classList.add('hide');
    });
  }

  hideOptions() {
    document.querySelectorAll(this.noneButtonClass).forEach((noneButton) => {
      noneButton.classList.add('hide');
    });
    document.querySelectorAll(this.optionClass).forEach((option) => {
      option.classList.add('hide');
    });
    this.latestQuestion().querySelector('.js-question-detail').classList.add('hide');
    this.hideBackButton();
  }

  insertNewQuestion(markup) {
    // Hide previous question
    this.hideQuestions();
    this.questionsWrapper.insertAdjacentHTML('beforeEnd', markup);
    this.scrollToQuestions();
  }

  showSuccess(option) {
    this.hideOptions();
    this.setStepperValid();
    const successMessage = option.parentNode.querySelector('.js-option-success');
    successMessage.classList.remove('hide');
    this.scrollToQuestions();
  }

  showUnsuccessful(option) {
    this.hideOptions();
    this.setStepperInvalid();
    this.scrollToQuestions();
    const unsuccessfulMessage = option.nextElementSibling;
    unsuccessfulMessage.classList.remove('hide');
  }

  stepperFirstStep() {
    return this.latestQuestion().querySelector('.c-stepper--first');
  }

  setStepperInvalid() {
    if (this.stepperFirstStep()) {
      this.stepperFirstStep().classList.add('c-stepper--invalid');
    }
  }

  setStepperValid() {
    if (this.stepperFirstStep()) {
      this.stepperFirstStep().classList.add('c-stepper--completed');
    }
  }

  handleBackButtonVisibility() {
    const questionsRendered = this.questionsWrapper.childElementCount;

    if (questionsRendered > 1) {
      this.showBackButton();
    } else {
      this.hideBackButton();
    }
  }

  showBackButton() {
    this.backButton.classList.remove('hide');
  }

  hideBackButton() {
    this.backButton.classList.add('hide');
  }

  latestQuestion() {
    return this.questionsWrapper.lastElementChild;
  }

  backToPreviousQuestion() {
    // Remove the current question
    const wrapper = this.questionsWrapper;
    wrapper.removeChild(wrapper.lastElementChild);
    // Show the previous
    const questionToShow = this.latestQuestion();
    questionToShow.classList.remove('hide');
    this.scrollToQuestions();
    this.handleBackButtonVisibility();
  }

  noneOptionHandler(question) {
    this.hideOptions();
    this.setStepperInvalid();
    question.querySelector('.js-none-of-above-message').classList.remove('hide');
    this.scrollToQuestions();
  }

  showError() {
    document.getElementById('js-questions-error').classList.remove('hide');
    this.questionsWrapper.classList.add('hide');
    this.scrollToQuestions();
  }

  scrollToQuestions() {
    const headerOffset = 60;
    const elementPosition = this.questionsWrapper.getBoundingClientRect().top;
    const offsetPosition = elementPosition - headerOffset;

    window.scrollTo({
      top: offsetPosition + window.pageYOffset,
      behavior: 'smooth',
    });
  }
}

export default Questions;
