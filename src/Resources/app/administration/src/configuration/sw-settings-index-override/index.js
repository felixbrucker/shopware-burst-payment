import template from './sw-settings-index.html.twig';
import pluginIcon from '../../../../../config/plugin.png';

Shopware.Component.override('sw-settings-index', {
    template,

    data() {
        return {
            pluginIcon,
        };
    },
});






