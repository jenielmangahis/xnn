let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/assets/js/app.js', 'public/js')
   .sass('resources/assets/sass/app.scss', 'public/css')
   .sass('resources/assets/sass/affiliate_historical_commission.scss', 'public/css')
   .sass('resources/assets/sass/admin_run_commission.scss', 'public/css')
   .sass('resources/assets/sass/admin_pay_commission.scss', 'public/css')
   .sass('resources/assets/sass/admin_withdrawal_request.scss', 'public/css')
   .sass('resources/assets/sass/select2-bootstrap.scss', 'public/css')
   .sass('resources/assets/sass/datepicker.scss', 'public/css')
   .sass('resources/assets/sass/treetable/jquery.treetable.scss', 'public/css')
   .sass('resources/assets/sass/treetable/jquery.treetable.theme.default.scss', 'public/css')
   .sass('resources/assets/sass/datatables.scss', 'public/css')
;
