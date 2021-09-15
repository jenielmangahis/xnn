<?php


namespace Commissions\Repositories;


use Commissions\Contracts\Repositories\CountryRepositoryInterface;

class CountryRepository implements CountryRepositoryInterface
{
    public function all() // TODO: move to database
    {
        return collect(array (
            array (
                'code2' => 'US',
                'code3' => 'USA',
                'name' => 'United States',
                'capital' => 'Washington, D.C.',
                'region' => 'Americas',
                'subregion' => 'Northern America',
                'states' =>
                    array (
                        0 =>
                            array (
                                'code' => 'DC',
                                'name' => 'District of Columbia',
                                'subdivision' => 'district',
                            ),
                        1 =>
                            array (
                                'code' => 'AS',
                                'name' => 'American Samoa',
                                'subdivision' => 'outlying territory',
                            ),
                        2 =>
                            array (
                                'code' => 'GU',
                                'name' => 'Guam',
                                'subdivision' => 'outlying territory',
                            ),
                        3 =>
                            array (
                                'code' => 'MP',
                                'name' => 'Northern Mariana Islands',
                                'subdivision' => 'outlying territory',
                            ),
                        4 =>
                            array (
                                'code' => 'PR',
                                'name' => 'Puerto Rico',
                                'subdivision' => 'outlying territory',
                            ),
                        5 =>
                            array (
                                'code' => 'UM',
                                'name' => 'United States Minor Outlying Islands',
                                'subdivision' => 'outlying territory',
                            ),
                        6 =>
                            array (
                                'code' => 'VI',
                                'name' => 'Virgin Islands, U.S.',
                                'subdivision' => 'outlying territory',
                            ),
                        7 =>
                            array (
                                'code' => 'AL',
                                'name' => 'Alabama',
                                'subdivision' => 'state',
                            ),
                        8 =>
                            array (
                                'code' => 'AK',
                                'name' => 'Alaska',
                                'subdivision' => 'state',
                            ),
                        9 =>
                            array (
                                'code' => 'AZ',
                                'name' => 'Arizona',
                                'subdivision' => 'state',
                            ),
                        10 =>
                            array (
                                'code' => 'AR',
                                'name' => 'Arkansas',
                                'subdivision' => 'state',
                            ),
                        11 =>
                            array (
                                'code' => 'CA',
                                'name' => 'California',
                                'subdivision' => 'state',
                            ),
                        12 =>
                            array (
                                'code' => 'CO',
                                'name' => 'Colorado',
                                'subdivision' => 'state',
                            ),
                        13 =>
                            array (
                                'code' => 'CT',
                                'name' => 'Connecticut',
                                'subdivision' => 'state',
                            ),
                        14 =>
                            array (
                                'code' => 'DE',
                                'name' => 'Delaware',
                                'subdivision' => 'state',
                            ),
                        15 =>
                            array (
                                'code' => 'FL',
                                'name' => 'Florida',
                                'subdivision' => 'state',
                            ),
                        16 =>
                            array (
                                'code' => 'GA',
                                'name' => 'Georgia',
                                'subdivision' => 'state',
                            ),
                        17 =>
                            array (
                                'code' => 'HI',
                                'name' => 'Hawaii',
                                'subdivision' => 'state',
                            ),
                        18 =>
                            array (
                                'code' => 'ID',
                                'name' => 'Idaho',
                                'subdivision' => 'state',
                            ),
                        19 =>
                            array (
                                'code' => 'IL',
                                'name' => 'Illinois',
                                'subdivision' => 'state',
                            ),
                        20 =>
                            array (
                                'code' => 'IN',
                                'name' => 'Indiana',
                                'subdivision' => 'state',
                            ),
                        21 =>
                            array (
                                'code' => 'IA',
                                'name' => 'Iowa',
                                'subdivision' => 'state',
                            ),
                        22 =>
                            array (
                                'code' => 'KS',
                                'name' => 'Kansas',
                                'subdivision' => 'state',
                            ),
                        23 =>
                            array (
                                'code' => 'KY',
                                'name' => 'Kentucky',
                                'subdivision' => 'state',
                            ),
                        24 =>
                            array (
                                'code' => 'LA',
                                'name' => 'Louisiana',
                                'subdivision' => 'state',
                            ),
                        25 =>
                            array (
                                'code' => 'ME',
                                'name' => 'Maine',
                                'subdivision' => 'state',
                            ),
                        26 =>
                            array (
                                'code' => 'MD',
                                'name' => 'Maryland',
                                'subdivision' => 'state',
                            ),
                        27 =>
                            array (
                                'code' => 'MA',
                                'name' => 'Massachusetts',
                                'subdivision' => 'state',
                            ),
                        28 =>
                            array (
                                'code' => 'MI',
                                'name' => 'Michigan',
                                'subdivision' => 'state',
                            ),
                        29 =>
                            array (
                                'code' => 'MN',
                                'name' => 'Minnesota',
                                'subdivision' => 'state',
                            ),
                        30 =>
                            array (
                                'code' => 'MS',
                                'name' => 'Mississippi',
                                'subdivision' => 'state',
                            ),
                        31 =>
                            array (
                                'code' => 'MO',
                                'name' => 'Missouri',
                                'subdivision' => 'state',
                            ),
                        32 =>
                            array (
                                'code' => 'MT',
                                'name' => 'Montana',
                                'subdivision' => 'state',
                            ),
                        33 =>
                            array (
                                'code' => 'NE',
                                'name' => 'Nebraska',
                                'subdivision' => 'state',
                            ),
                        34 =>
                            array (
                                'code' => 'NV',
                                'name' => 'Nevada',
                                'subdivision' => 'state',
                            ),
                        35 =>
                            array (
                                'code' => 'NH',
                                'name' => 'New Hampshire',
                                'subdivision' => 'state',
                            ),
                        36 =>
                            array (
                                'code' => 'NJ',
                                'name' => 'New Jersey',
                                'subdivision' => 'state',
                            ),
                        37 =>
                            array (
                                'code' => 'NM',
                                'name' => 'New Mexico',
                                'subdivision' => 'state',
                            ),
                        38 =>
                            array (
                                'code' => 'NY',
                                'name' => 'New York',
                                'subdivision' => 'state',
                            ),
                        39 =>
                            array (
                                'code' => 'NC',
                                'name' => 'North Carolina',
                                'subdivision' => 'state',
                            ),
                        40 =>
                            array (
                                'code' => 'ND',
                                'name' => 'North Dakota',
                                'subdivision' => 'state',
                            ),
                        41 =>
                            array (
                                'code' => 'OH',
                                'name' => 'Ohio',
                                'subdivision' => 'state',
                            ),
                        42 =>
                            array (
                                'code' => 'OK',
                                'name' => 'Oklahoma',
                                'subdivision' => 'state',
                            ),
                        43 =>
                            array (
                                'code' => 'OR',
                                'name' => 'Oregon',
                                'subdivision' => 'state',
                            ),
                        44 =>
                            array (
                                'code' => 'PA',
                                'name' => 'Pennsylvania',
                                'subdivision' => 'state',
                            ),
                        45 =>
                            array (
                                'code' => 'RI',
                                'name' => 'Rhode Island',
                                'subdivision' => 'state',
                            ),
                        46 =>
                            array (
                                'code' => 'SC',
                                'name' => 'South Carolina',
                                'subdivision' => 'state',
                            ),
                        47 =>
                            array (
                                'code' => 'SD',
                                'name' => 'South Dakota',
                                'subdivision' => 'state',
                            ),
                        48 =>
                            array (
                                'code' => 'TN',
                                'name' => 'Tennessee',
                                'subdivision' => 'state',
                            ),
                        49 =>
                            array (
                                'code' => 'TX',
                                'name' => 'Texas',
                                'subdivision' => 'state',
                            ),
                        50 =>
                            array (
                                'code' => 'UT',
                                'name' => 'Utah',
                                'subdivision' => 'state',
                            ),
                        51 =>
                            array (
                                'code' => 'VT',
                                'name' => 'Vermont',
                                'subdivision' => 'state',
                            ),
                        52 =>
                            array (
                                'code' => 'VA',
                                'name' => 'Virginia',
                                'subdivision' => 'state',
                            ),
                        53 =>
                            array (
                                'code' => 'WA',
                                'name' => 'Washington',
                                'subdivision' => 'state',
                            ),
                        54 =>
                            array (
                                'code' => 'WV',
                                'name' => 'West Virginia',
                                'subdivision' => 'state',
                            ),
                        55 =>
                            array (
                                'code' => 'WI',
                                'name' => 'Wisconsin',
                                'subdivision' => 'state',
                            ),
                        56 =>
                            array (
                                'code' => 'WY',
                                'name' => 'Wyoming',
                                'subdivision' => 'state',
                            ),
                    ),
            ),
            array (
                'code2' => 'CA',
                'code3' => 'CAN',
                'name' => 'Canada',
                'capital' => 'Ottawa',
                'region' => 'Americas',
                'subregion' => 'Northern America',
                'states' =>
                    array (
                        0 =>
                            array (
                                'code' => 'AB',
                                'name' => 'Alberta',
                                'subdivision' => 'province',
                            ),
                        1 =>
                            array (
                                'code' => 'BC',
                                'name' => 'British Columbia',
                                'subdivision' => 'province',
                            ),
                        2 =>
                            array (
                                'code' => 'MB',
                                'name' => 'Manitoba',
                                'subdivision' => 'province',
                            ),
                        3 =>
                            array (
                                'code' => 'NB',
                                'name' => 'New Brunswick',
                                'subdivision' => 'province',
                            ),
                        4 =>
                            array (
                                'code' => 'NL',
                                'name' => 'Newfoundland and Labrador',
                                'subdivision' => 'province',
                            ),
                        5 =>
                            array (
                                'code' => 'NS',
                                'name' => 'Nova Scotia',
                                'subdivision' => 'province',
                            ),
                        6 =>
                            array (
                                'code' => 'ON',
                                'name' => 'Ontario',
                                'subdivision' => 'province',
                            ),
                        7 =>
                            array (
                                'code' => 'PE',
                                'name' => 'Prince Edward Island',
                                'subdivision' => 'province',
                            ),
                        8 =>
                            array (
                                'code' => 'QC',
                                'name' => 'Quebec',
                                'subdivision' => 'province',
                            ),
                        9 =>
                            array (
                                'code' => 'SK',
                                'name' => 'Saskatchewan',
                                'subdivision' => 'province',
                            ),
                        10 =>
                            array (
                                'code' => 'NT',
                                'name' => 'Northwest Territories',
                                'subdivision' => 'territory',
                            ),
                        11 =>
                            array (
                                'code' => 'NU',
                                'name' => 'Nunavut',
                                'subdivision' => 'territory',
                            ),
                        12 =>
                            array (
                                'code' => 'YT',
                                'name' => 'Yukon',
                                'subdivision' => 'territory',
                            ),
                    ),
            ),
        ));
    }
}