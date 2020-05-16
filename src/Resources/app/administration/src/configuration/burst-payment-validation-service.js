const ApiService = Shopware.Classes.ApiService;

const API_ENDPOINT = 'burst-payment';

class BurstPaymentValidationService extends ApiService {
    constructor(httpClient, loginService) {
        super(httpClient, loginService, API_ENDPOINT);
    }
}

export default BurstPaymentValidationService;
