import { isBurstAddress } from '@burstjs/util';

const ApiService = Shopware.Classes.ApiService;

const API_ENDPOINT = 'burst-payment';

class BurstPaymentValidationService extends ApiService {
    constructor(httpClient, loginService) {
        super(httpClient, loginService, API_ENDPOINT);
    }

    async validateWalletConnection(burstWalletUrl) {
        const headers = this.getBasicHeaders();

        const response = await this.httpClient.post(
            `_action/${this.getApiBasePath()}/validate-wallet-connection`,
            { burstWalletUrl },
            { headers },
        );

        return ApiService.handleResponse(response).isReachable;
    }

    isBurstAddress(address) {
        return isBurstAddress(address);
    }
}

export default BurstPaymentValidationService;
