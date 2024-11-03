class Init {
  constructor(functions) {
    this.core = {};
    this.functions = functions;
    this.events = {};

    if (document.readyState !== 'loading' && document.body) {
      this.init();
    } else if (document.readyState === 'loading') {
      document.addEventListener(
        'readystatechange',
        this.readyHandler.bind(this),
        false
      );
    } else {
      document.addEventListener('DOMContentLoaded', this.init.bind(this));
    }
  }

  static tidy(operation, task) {
    let result = operation.split('__');
    if (task === true) {
      result = result[0];
    } else {
      result.shift();
    }
    return result;
  }

  static presentError(operation, error) {
    if (typeof console === 'undefined') {
      return;
    }
    if (console.groupCollapsed) {
      console.groupCollapsed(
        `%c [${operation} error] - ${error.message}. Expand for details:`,
        'color: #ff5a5a'
      );
      console.log(error.stack);
      console.groupEnd();
    } else {
      console.log(error.stack);
    }
  }

  readyHandler() {
    if (document.readyState !== 'interactive') {
      return;
    }
    this.init();
  }

  init() {
    const operations = Object.keys(this.functions);

    for (let i = 0, l = operations.length; i < l; i++) {
      const operation = operations[i];
      const clean = Init.tidy(operation, true);
      this.events[clean] = Init.tidy(operation);
      this.bindEvents(this.events[clean], operation);
    }

    window.functionCore = this.core;
  }

  bindEvents(events, operation) {
    const clean = Init.tidy(operation, true);

    try {
      this.core[clean] = this.functions[operation]();

      for (let i = 0, l = events.length; i < l; i++) {
        if (events[i] !== 'scroll' && events[i] !== 'resize') {
          continue;
        }

        if (typeof this.core[clean][events[i]] === 'undefined') {
          Init.presentError(
            operation,
            new Error(`Missing this.${events[i]} function`)
          );
          continue;
        }

        window.addEventListener(events[i], e => {
          if (window.requestAnimationFrame) {
            window.requestAnimationFrame(this.core[clean][events[i]]);
          } else {
            try {
              this.core[clean][events[i]](e);
            } catch (error) {
              Init.presentError(operation, error);
            }
          }
        });
      }
    } catch (error) {
      Init.presentError(operation, error);
    }
  }
}

export default Init;
