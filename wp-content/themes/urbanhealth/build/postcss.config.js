module.exports = {
  syntax: require("postcss-scss"),
  plugins: [
    require("autoprefixer")({
      overrideBrowserslist: ["defaults", "ie >= 11", "Safari >= 10", "iOS >= 9"]
    })
  ]
};
