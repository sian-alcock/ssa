const TerserPlugin = require('terser-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const OptimizeCssAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

module.exports = {
  mode: 'production',
  devtool: 'source-map',
  performance: {
    hints: 'warning',
  },
  optimization: {
    minimizer: [
      // Minify all code with support for ES6, faster and produces smaller
      // files than UglifyJS
      new TerserPlugin({
        cache: true,
        parallel: true,
        sourceMap: false,
        extractComments: true,
        terserOptions: {
          safari10: true,
        },
      }),
    ],
    // Pull out external imported node module files and
    // put them into their own file
    // splitChunks: {
    //   cacheGroups: {
    //     vendor: {
    //       test: /[\\/]node_modules[\\/]/,
    //       name: "vendors",
    //       chunks: "all"
    //     }
    //   }
    // }
  },
  plugins: [
    // Wipe the dist folder
    new CleanWebpackPlugin(),
    // Minify CSS and remove comments, also minify SVGs
    new OptimizeCssAssetsPlugin({
      cssProcessorOptions: {
        map: { inline: false, annotations: true },
        discardComments: {
          removeAll: true,
        },
        svgo: true,
        rawCache: true,
        reduceIdents: true,
      },
    }),
    // Create bundle analyzer files
    new BundleAnalyzerPlugin({
      // Disable the localhost browser so we dont hang on server
      // You can now run npm run bundle to see this manually
      analyzerMode: 'disabled',
      openAnalyzer: false,
      generateStatsFile: true,
    }),
  ],
};
