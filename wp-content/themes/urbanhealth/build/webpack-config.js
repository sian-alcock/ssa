const webpackMerge = require("webpack-merge");
const commonConfig = require("./config/webpack.common.js");

// The target comes from the cli tasks in package.json
const target = process.env.NODE_ENV;

module.exports = () => {
  /* eslint-disable */
  // We can now choose which config to load
  const envConfig = require(`./config/webpack.${target}.js`);
  /* eslint-enable */

  return webpackMerge(commonConfig, envConfig);
};
