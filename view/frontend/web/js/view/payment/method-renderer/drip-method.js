/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'Magento_Checkout/js/view/payment/default',
    'Magento_Checkout/js/model/quote',
], function (Component, quote) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Drip_Payments/payment/drip'
        },
        totals: quote.getTotals(),
        getValue: function() {
            var formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
              });
            return formatter.format(this.totals().grand_total).replace('$','').replace(',', '');
        },
        getFormattedToday: function() {
            return new Date().toISOString().slice(0, 10);
        },
        /**
         * Returns payment method instructions.
         *
         * @return {*}
         */
        getInstructions: function() {
            return window.checkoutConfig.payment.instructions[this.item.method];
        },
        getIframeUrl: function() {
            var url = window.checkoutConfig.payment.dripPaymentsIframeUrl;
            url = url.replace(/totalOrderamount/, this.getValue());
            url = url.replace(/actualDate/, this.getFormattedToday());
            var cnpj = window.checkoutConfig.payment.dripPaymentsActualCnpj;
            var cnpjUrl = '';
            if (cnpj != undefined && cnpj.length > 5) {
                cnpjUrl = `&merchant=${cnpj}`
            }
            return url + cnpjUrl;
        },
        getTitleWithCashback: function() {
            var cashbackRate = window.checkoutConfig.payment.dripPaymentsActualCashbackRate;
            return `Drip Pix Parcelado +${cashbackRate}% de Cashback`;
        },
        getDescriptionWithCashback: function() {
            var cashbackRate = window.checkoutConfig.payment.dripPaymentsActualCashbackRate;
            return `Compre em 3x no Pix. Com ${cashbackRate}% de cashback e zero juros. Compre e receba seu produto agora e faça o primeiro pagamento só daqui 1 mês.`;
        },
        checkDripIsEnabled: function() {
            var isDisabled = window.checkoutConfig.payment.dripPaymentsIsDisabled;
            return !isDisabled;
        },
    });
});