const fs = require("fs");
const path = require("path");
const webpack = require("webpack");
const globImporter = require("node-sass-glob-importer");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const stylelintFormatter = require("stylelint-formatter-pretty");
const EasyStylelintPlugin = require("easy-stylelint-plugin");
const formatterPretty = require("eslint-formatter-pretty");

module.exports = {
  // Entry point for the app JS and Styles
  entry: [path.resolve(__dirname, "../../assets/src/app.js")],
  // Output
  output: {
    // Destination folder
    path: path.resolve(__dirname, "../../assets/dist"),
    // We don't know the URL for our site, so we copy the path src
    // This prefixes all of our assets with the correct
    // destination which is why it ends with a slash
    publicPath: path.resolve(__dirname, "../../assets/dist/"),
    // Compiled JS
    filename: "scripts.min.js",
    // Compiled external libraries
    // chunkFilename: "vendor.min.js"
  },
  // Type of application, could be node etc
  target: "web",
  resolve: {
    // Specify what type of files we support with webpack
    extensions: ["*", ".js", ".jsx"],
    // Specify possible sources
    modules: [path.resolve(__dirname, "../node_modules"), "node_modules"]
  },
  module: {
    rules: [
      // Tell webpack how to load an image file
      {
        test: /\.(jpe?g|png|gif)$/,
        exclude: /(node_modules)/,
        loader: "file-loader",
        options: {
          name: "./img/[name].[ext]"
        }
      },
      // Tell webpack how to load font files
      {
        test: /\.(ttf|otf|eot|woff(2)?)(\?[a-z0-9]+)?$/,
        exclude: /(node_modules)/,
        loader: "url-loader?limit=100000",
        options: {
          name: "./fonts/[name].[ext]"
        }
      },
      // Allow you to import SVGs in JS
      {
        test: /\.svg$/,
        use: ["@svgr/webpack", "url-loader"]
      },
      // ESlint handler for your code with auto fix and
      // formatterPretty for nicer error messages
      {
        enforce: "pre",
        test: /\.(js|jsx)$/,
        exclude: /(node_modules)/,
        loader: "eslint-loader",
        options: {
          fix: true,
          formatter: formatterPretty
        }
      },
      // Load babel config to determine what code
      // to convert to ES5 and how
      {
        test: /\.(js|jsx)$/,
        exclude: /(node_modules)/,
        use: [
          {
            loader: "babel-loader",
            // Point to .babelrc file in parent folder
            // It can't resolved without this line
            options: {
              ...JSON.parse(
                fs.readFileSync(path.resolve(__dirname, "../.babelrc"))
              )
            }
          }
        ]
      },
      {
        test: /\.(sa|sc|c)ss$/,
        use: [
          {
            // Extract CSS from JS
            loader: MiniCssExtractPlugin.loader
          },

          // Handle standard CSS files
          {
            // Handle sass files
            loader: "css-loader",
            options: {
              // import them all for sourceMaps
              sourceMap: true
            }
          },
          {
            // Handle CSS with postcss support, autoprefixr and sourceMaps
            loader: "postcss-loader",
            options: {
              ident: "postcss",
              sourceMap: "inline",
              config: {
                path: path.resolve(__dirname, "../../build")
              }
            }
          },
          {
            // Handle sass files
            loader: "sass-loader",
            options: {
              // import them all for sourceMaps
              sourceMap: true,
              importer: globImporter()
            }
          }
        ]
      }
    ]
  },
  plugins: [
    // Take the CSS extracted from CSS and write to a file
    new MiniCssExtractPlugin({
      filename: "style.css"
    }),
    // Lint our stylesheets and auto fix them based on .stylelintrc file
    new EasyStylelintPlugin({
      formatter: stylelintFormatter,
      context: path.resolve(__dirname, "../../assets"),
      fix: true
    }),
    // Ignore locale and moment packages imported from momentJS library
    // We can then require the exact files required and greatly
    // reduce bundle size
    new webpack.IgnorePlugin(/^\.\/locale$/, /moment$/)
  ]
};
