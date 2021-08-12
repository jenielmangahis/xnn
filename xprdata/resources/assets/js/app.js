
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

const formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
});

Vue.filter('money', function (value) {
    value = +value;
    return formatter.format(value);
});

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

// Vue.component('example', require('./components/Example.vue'));
Vue.component('select2', require('./components/Select2.vue'));
Vue.component('select2-autocomplete-member', require('./components/Select2AutocompleteMember.vue'));
Vue.component('datepicker', require('./components/Datepicker.vue'));
Vue.component('datepicker-month-year', require('./components/DatepickerMonthYear'));

Vue.filter('capitalize', function (value) {
    if (!value) return ''
    value = value.toString()
    return value.charAt(0).toUpperCase() + value.slice(1)
});

// const app = new Vue({
//     el: '#app'
// });

import * as commissionEngine from './commission';

window.commissionEngine = commissionEngine;
window.COMMISSION_ENGINE_URL = commissionEngine.API_URL;
window.COMMISSION_ENGINE_ACCESS_TOKEN = commissionEngine.ACCESS_TOKEN;
window.cmCreateAccessClient = commissionEngine.createAccessClient;
window.cmSetupAccessTokenJQueryAjax = commissionEngine.setupAccessTokenJQueryAjax;

require('./menu');

window.jQuery.fn.select2 = undefined;

$.fn.dataTable.defaults.column.sClass = "table__cell";

$.extend( $.fn.dataTable.defaults, {
    createdRow: function( row ) {
        $(row).addClass("table__row");
    }
} );
