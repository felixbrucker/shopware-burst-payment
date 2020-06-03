const { join } = require('path');

module.exports = (_env, argv) => ({
    entry: {
        'burst-payment': './src/Resources/app/administration/src/burst-payment-administration.js',
    },
    output: {
        path: join(__dirname, 'src/Resources/public/administration'),
        filename: 'js/[name].js',
        chunkFilename: 'js/[name].js',
    },
    module: {
        rules: [
            {
                test: /\.(html|twig)$/,
                loader: 'html-loader',
            },
            {
                test: /\.(js|tsx?|vue)$/,
                loader: 'babel-loader',
                options: {
                    compact: true,
                    cacheDirectory: true,
                    presets: [[
                        '@babel/preset-env', {
                            modules: false,
                            targets: {
                                browsers: ['last 2 versions', 'edge >= 17'],
                            },
                        },
                    ]],
                },
            },
            {
                test: /\.(png|jpe?g|gif|svg)(\?.*)?$/,
                loader: 'url-loader',
                options: {
                    limit: 10000,
                    name: '[contenthash].[ext]',
                    outputPath: 'static/img',
                    publicPath: 'bundles/burstpayment/administration/static/img',
                },
            },
        ],
    },
    devtool: argv.mode === 'production' ? false : 'eval-source-map',
    stats: {
        modules: false,
    },
});
