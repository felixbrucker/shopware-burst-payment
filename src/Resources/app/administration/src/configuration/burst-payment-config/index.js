import template from './burst-payment-config.html.twig';

const { Mixin, Component } = Shopware;

const BURST_PAYMENT_CONFIG_DOMAIN = 'BurstPayment.config';

Component.register('burst-payment-config', {
    template,

    name: 'burst-payment-config',

    mixins: [
        Mixin.getByName('notification'),
    ],

    inject: ['burstPaymentValidationService'],

    data() {
        return {
            isLoading: false,
            isSaveSuccessful: false,
            config: null,
            configDomain: BURST_PAYMENT_CONFIG_DOMAIN,
        };
    },
    methods: {
        async saveConfig() {
            const burstAddress = this.config[`${BURST_PAYMENT_CONFIG_DOMAIN}.burstAddress`];
            const isValidBurstAddress = this.burstPaymentValidationService.isBurstAddress(burstAddress);
            if (!isValidBurstAddress) {
                this.createNotificationError({
                    title: this.$t('burst-payment.titles.error'),
                    message: this.$t('burst-payment.messages.invalid-burst-address', { burstAddress }),
                });

                return;
            }

            this.isLoading = true;
            try {
                await this.$refs.systemConfig.saveAll();
                this.isSaveSuccessful = true;
                this.createNotificationSuccess({
                    title: this.$t('burst-payment.titles.success'),
                    message: this.$t('burst-payment.messages.save.success'),
                });
            } catch (error) {
                this.isSaveSuccessful = false;
                let errorMessage = error.response && error.response.data;
                if (!errorMessage) {
                    errorMessage = this.$t('burst-payment.messages.unknown-error');
                }
                this.createNotificationError({
                    title: this.$t('burst-payment.titles.error'),
                    message: this.$t('burst-payment.messages.save.error', { errorMessage }),
                });
                this.isLoading = false;
                return;
            }

            await this.validateWalletConnection();

            this.isLoading = false;
        },

        async validateWalletConnection() {
            const burstWalletUrl = this.config[`${BURST_PAYMENT_CONFIG_DOMAIN}.burstWalletUrl`];
            const isReachable = await this.burstPaymentValidationService.validateWalletConnection(
                burstWalletUrl
            );
            if (isReachable) {
                this.createNotificationSuccess({
                    title: this.$t('burst-payment.titles.success'),
                    message: this.$t('burst-payment.messages.wallet-connection.reachable'),
                });
            } else {
                this.createNotificationError({
                    title: this.$t('burst-payment.titles.error'),
                    message: this.$t('burst-payment.messages.wallet-connection.unreachable', { burstWalletUrl }),
                });
            }
        },

        updateConfig(config) {
            this.config = config;
            this.isSaveSuccessful = false;
        },
    },
});
