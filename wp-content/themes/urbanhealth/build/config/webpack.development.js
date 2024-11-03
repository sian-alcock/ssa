const path = require("path");
const TimeFixPlugin = require("time-fix-plugin");
const NotifierPlugin = require("friendly-errors-webpack-plugin");
const BrowserSyncPlugin = require("browser-sync-webpack-plugin");
const WatchMissingNodeModulesPlugin = require("react-dev-utils/WatchMissingNodeModulesPlugin");

// Break the present working directory down into an array
// and return the item at requested position
const getPathPosition = position =>
  process.env.PWD.split("/").slice(position)[0];

// We can now get the sitename based on the foldername that FlyWheel creates
const siteName = getPathPosition(-7);
const theme = path.resolve(__dirname, "../..");

module.exports = {
  // Keep webpack running after it completes
  watch: true,
  mode: "development",
  // Descriptive source maps
  devtool: "cheap-module-source-map",
  // This controls webpack output, we're turning most of it off
  // to keep only the file sizes
  stats: {
    assets: true,
    cached: false,
    cachedAssets: false,
    children: false,
    chunks: false,
    chunkModules: false,
    chunkOrigins: false,
    colors: true,
    errors: false,
    errorDetails: true,
    hash: false,
    maxModules: 0,
    modules: false,
    reasons: false,
    source: false,
    timings: true,
    usedExports: false,
    version: false,
    warnings: true,
    entrypoints: false
  },
  // Disable performance hints for unminifed code
  performance: {
    hints: false
  },
  plugins: [
    // BrowserSync in a browser-sync-webpack-plugin
    // wrapper with it's own options/issues
    new BrowserSyncPlugin(
      // BrowserSync uses chokidar to proxy our FlyWheel server
      {
        https: false,
        open: true,
        cors: true,
        notify: false,
        watchTask: true,
        logFileChanges: false,
        reloadOnRestart: true,
        injectChanges: true,
        // browser-sync-webpack-plugin specific option
        injectCss: true,
        proxy: `https://${siteName}.local`,
        reloadDelay: 0,
        files: [
          // Point to exact files to stop sourcemaps refreshing browser
          `${theme}/assets/dist/style.css`,
          `${theme}/assets/dist/scripts.min.js`,
          `${theme}/assets/dist/vendor.min.js`,
          {
            // Trigger a refresh on any php/img/font file update
            match: [
              `${theme}/**/*.php`,
              `${theme}/assets/fonts/**/*`,
              `${theme}/assets/img/**/*`
            ],
            fn(event, file) {
              if (event === "change") {
                this.reload(file);
              }
            }
          }
        ],
        plugins: [
          // Trigger html injection on twig updates
          {
            module: "bs-html-injector",
            options: {
              files: [`${theme}/views/**/*.twig`]
            }
          }
        ]
      },
      {
        // This is supposed to stop refresh on CSS/JS updates
        reload: false
      }
    ),
    // If you require a missing module and then `npm install` it, you still have
    // to restart the development server for Webpack to discover it. This plugin
    // makes the discovery automatic so you don't have to restart.
    new WatchMissingNodeModulesPlugin(path.resolve("node_modules")),
    // Add timewatch plugin to avoid multiple successive builds
    new TimeFixPlugin(),
    // Some clean notifications for terminal
    new NotifierPlugin({
      onErrors: (severity, errors) => {}
    })
  ]
};
