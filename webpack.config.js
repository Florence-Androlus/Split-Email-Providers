const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
  entry: './src/index.js', // Fichier d'entrée de votre application
  output: {
    filename: 'tableauFournisseurs.js', // Nom du fichier de sortie
    path: path.resolve(__dirname, 'dist'), // Dossier de sortie
    publicPath: '/wp-content/plugins/split-email-providers/dist/', // URL publique pour accéder au fichier
  },
  module: {
    rules: [
      {
        test: /\.vue$/,
        loader: 'vue-loader',
      },
      {
        test: /\.js$/,
        loader: 'babel-loader',
        exclude: /node_modules/,
      },
      {
        test: /\.css$/, // Ajout du traitement des fichiers CSS
        use: ['style-loader', 'css-loader'],
      },
    ],
  },
  plugins: [
    new VueLoaderPlugin(),
  ],
  externals: {
    ...defaultConfig.externals,
    '@wordpress/data': ['wp', 'data'], // Ajoutez ceci
  },
};