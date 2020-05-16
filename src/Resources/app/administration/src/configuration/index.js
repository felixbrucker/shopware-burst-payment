import './translations';
import './sw-settings-index-override';
import './sw-plugin-list-override';
import './burst-payment-config';
import './burst-payment-config-module';

import BurstPaymentValidationService from './burst-payment-validation-service';

const { Application } = Shopware;

Application.addServiceProvider('burstPaymentValidationService', (container) => {
    const initContainer = Application.getContainer('init');

    return new BurstPaymentValidationService(initContainer.httpClient, container.loginService);
});
