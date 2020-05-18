import template from './sw-plugin-list.html.twig';

Shopware.Component.override('sw-plugin-list', {
    template,

    methods: {
        openBurstPaymentPluginConfig() {
            this.$router.push({ name: 'burst-payment.settings' });
        },
    }
});
