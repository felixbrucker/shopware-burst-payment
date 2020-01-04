import template from './sw-order-user-card.html.twig';

Shopware.Component.override('sw-order-user-card', {
    template,

    props: {
        currentOrder: {
            type: Object,
            required: true
        },
    },
    computed: {
        burstPaymentContext() {
            if (this.currentOrder.transactions.length === 0) {
                return null;
            }
            const orderTransaction = this.currentOrder.transactions[0];
            if (!orderTransaction.customFields || !orderTransaction.customFields.burst_payment_context) {
                return null;
            }

            return orderTransaction.customFields.burst_payment_context;
        },

        hasBurstPaymentContext() {
            return !!this.burstPaymentContext;
        },

        transactionStateString() {
            const transactionState = this.burstPaymentContext.transactionState;
            if (transactionState !== 'pending') {
                return this.$t(`burst_payment.transaction_states.${transactionState}`);
            }

            const confirmations = this.burstPaymentContext.confirmations;

            return this.$t(`burst_payment.transaction_states.${transactionState}`, {
                confirmations,
            });
        },
    },
});
