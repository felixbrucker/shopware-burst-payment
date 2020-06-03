const { Module } = Shopware;

Module.register('burst-payment-config-module', {
    type: 'plugin',
    name: 'burst-payment-config-module',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    routePrefixName: 'burst-payment',
    routePrefixPath: 'burst-payment',

    routes: {
        settings: {
            component: 'burst-payment-config',
            path: 'settings',
            meta: {
                parentPath: 'sw.settings.index',
            },
        },
    },
});
