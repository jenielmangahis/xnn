<?php
    // Country values on our database as of February 15, 2019 is a full name  example "United States" for "US"
    // it is not in ISO format. While other third party like hyperwallet strickly use ISO country thus we need
    // to convert country to ISO before passing it to them.
    // This class will convert a string country name into iso country name.
    namespace App\nxm\models;
    
    class ConvertCountry {
        
        public static function valueOf($country) {
            
            if ($country === 'United States') {
    
                return 'US';
            } else if ($country === 'Canada') {
    
                return 'CA';
            } else {
                
                return '';
            }
        }
    }