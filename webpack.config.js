const path = require('path');

module.exports = {
  entry: './app/resources/js/app.js',
  output: {
    filename: 'app.js',
    path: path.resolve(__dirname, 'public/js'),
  },
  module: {
    rules: [
      {
        test: /\.s[ac]ss$/i,
        use: [
          // Creates `style` nodes from JS strings
          'style-loader',
          // Translates CSS into CommonJS
          'css-loader',
          // Compiles Sass to CSS. Explicitly use Dart Sass's modern JS API
          // (the legacy render() API sass-loader used by default is
          // deprecated and prints a warning on every build) - see
          // https://sass-lang.com/d/legacy-js-api
          {
            loader: 'sass-loader',
            options: {
              api: 'modern',
            },
          },
        ],
      },
    ],
  },
};
