const folderPath = process.env.PWD.split("/");
const path = require("path");

const theme = path.resolve(`${__dirname}/..`);

const BrowserSync = function(options) {
  this.sync = require("browser-sync").create();
  this.outputPath = null;
  this.prevTimestamps = {};
  this.startTime = Date.now();
  this.defaults = {
    target: `${folderPath[folderPath.length - 7]}.local`,
    stylesheet: "style.css",
    js: "scripts.min.js"
  };
  this.options = Object.assign(this.defaults, options);

  this.sync.init(null, {
    logFileChanges: false,
    reloadOnRestart: true,
    logPrefix: "Manifesto Browser Sync",
    logLevel: "info",
    files: [`${theme}/assets/style.css`, `${theme}/assets/scripts.min.js`],
    proxy: {
      target: `${this.options.target}`
    },
    watchTask: true,
    ui: false,
    open: false,
    plugins: [
      {
        module: "bs-html-injector",
        options: {
          files: [`${theme}/views/**/*.twig`]
        }
      }
    ]
  });

  this.sync.watch(`${theme}/**/*.php`, (e, file) => {
    if (e === "change") {
      this.sync.reload(file);
    }
  });
};

BrowserSync.prototype.notify = (text, time) => {
  this.sync.notify(data, time);
};

BrowserSync.prototype.deriveReload = function(files) {
  let reloadCSS = false;
  let reloadJS = false;

  for (let i = 0; i < files.length; i++) {
    if (files[i].match(/\.js$/gi)) {
      reloadJS = true;
      break;
    }
    if (files[i].match(/\.scss$/gi)) {
      reloadCSS = true;
      break;
    }
  }

  if (reloadCSS === true) {
    this.sync.reload(`${this.outputPath}/${this.options.stylesheet}`);
  }

  if (reloadJS === true) {
    this.sync.reload(`${this.outputPath}/${this.options.js}`);
  }
};

BrowserSync.prototype.apply = function(compiler) {
  this.outputPath = compiler.options.output.path;
  compiler.plugin("emit", (compilation, callback) => {
    const changedFiles = Object.keys(compilation.fileTimestamps).filter(
      watchfile =>
        (this.prevTimestamps[watchfile] || this.startTime) <
        (compilation.fileTimestamps[watchfile] || Infinity)
    );

    this.prevTimestamps = compilation.fileTimestamps;
    this.deriveReload(changedFiles);

    callback();
  });
};

exports.default = BrowserSync;
