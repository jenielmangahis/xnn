<?php
/**
     * This class helps you to config your Yii application
     * environment.
     * Any comments please post a message in the forum
     * Enjoy it!
     *
     * @name Environment
     * @author Fernando Torres | Marciano Studio
     * @version 1.0
     */

    class Environment {

        const DEVELOPMENT = 100;
        const TEST        = 200;
        const STAGE       = 300;
        const PRODUCTION  = 400;

        private $_mode = 0;
        private $_debug;
        private $_trace_level;
        private $_config;


        /**
         * Returns the debug mode
         * @return Bool
         */
        public function getDebug() {
            return $this->_debug;
        }

        /**
         * Returns the trace level for YII_TRACE_LEVEL
         * @return int
         */
        public function getTraceLevel() {
            return $this->_trace_level;
        }

        /**
         * Returns the configuration array depending on the mode
         * you choose
         * @return array
         */
        public function getConfig() {
            return $this->_config;
        }


        /**
         * Initilizes the Environment class with the given mode
         * @param constant $mode
         */
        function __construct($mode) {
            $this->_mode = $mode;
            $this->setConfig();
        }

        /**
         * Sets the configuration for the choosen environment
         * @param constant $mode
         */
        private function setConfig() {
            switch($this->_mode) {
                case self::DEVELOPMENT:
                    $this->_config      = array_merge_recursive ($this->_main(), $this->_development());
                    $this->_debug       = true;
                    $this->_trace_level = 3;
                    break;
                case self::TEST:
                    $this->_config      = array_merge_recursive ($this->_main(), $this->_test());
                    $this->_debug       = FALSE;
                    $this->_trace_level = 0;
                    break;
                case self::STAGE:
                    $this->_config      = array_merge_recursive ($this->_main(), $this->_stage());
                    $this->_debug       = TRUE;
                    $this->_trace_level = 0;
                    break;
                case self::PRODUCTION:
                    $this->_config      = array_merge_recursive ($this->_main(), $this->_production());
                    $this->_debug       = TRUE;
                    $this->_trace_level = 3;
                    break;
                default:
                    $this->_config      = $this->_main();
                    $this->_debug       = TRUE;
                    $this->_trace_level = 0;
                    break;
            }
        }


        /**
         * Main configuration
         * This is the general configuration that uses all environments
         */
        private function _main() {
            return array(

                    'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
                    'name'=>'My Web Application',
                    'layout'=>'newlayout',
                    // preloading 'log' component
                    'preload'=>array('log'),

                    // autoloading model and component classes
                    'import'=>array(
                            'application.models.*',
                            'application.components.*',
                            'application.extensions.pdf.*',
                            'application.extensions.phpmailer.*',
							'application.extensions.dropbox.*',
							'application.extensions.gantt.*',
                                                        'application.extensions.tmxreader.*',
														'application.extensions.braintreeapi.*',
														'application.extensions.campaignmonitor.*',
														'application.extensions.bing.*',
														'application.extensions.phpword.*',
                    ),

                    'modules'=>array(
                            // uncomment the following to enable the Gii tool

                            'invoice',
                            'files',
                            'faq',
                            'settings',
                            'projects',
                            'terms',
                            'people',
                            'theme2',
                            'myprojects',
                            'fillup',
                            'tool'

                    ),

                    // Application components
                    'components' => array(
                            'user'=>array(
                                    // enable cookie-based authentication
                                    'allowAutoLogin'=>true,
                                    'loginUrl'=>array('login/login'),
                            ),
                            // uncomment the following to enable URLs in path-format

                            'urlManager'=>array(
                                    'urlFormat'=>'path',
                                    'showScriptName'=>false,
                                    'rules'=>array(
											//'(.*)'=>'site/maintenance',
                                            '<controller:\w+>/<id:\d+>'=>'<controller>/view',
                                            '<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
                                            '<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
                                    ),
                            ),

                            'file'=>array(
                                'class'=>'application.extensions.file.CFile',
                            ),
                            'errorHandler'=>array(
                                // use 'site/error' action to display errors
                                'errorAction'=>'site/error',
                            ),
							
                    ),

                    // Application-level parameters
                    'params'=>array(
                            'adminEmail'=>'admin@example.com',
                            'environment'=> $this->_mode
                    )
            );
        }


        /**
         * Development configuration
         * Usage:
         * - Local website
         * - Local DB
         * - Show all details on each error.
         * - Gii module enabled
         */
        private function _development () {

            return array(

                    // Modules
                    'modules'=>array(
                            'gii'=>array(
                                    'class'=>'system.gii.GiiModule',
                                    'password'=>'password',
                            ),
                    ),

                    // Application components
                    'components' => array(

                            // Database
                            'db'=>array(
                                    'connectionString' => 'mysql:host=localhost;dbname=anecsys_translate',
                                    'emulatePrepare' => true,
                                    'username' => 'root',
                                    'password' => 'anecsysserver1111',
                                    'charset' => 'utf8',
                                    'tablePrefix'=>'tbl_'
                            ),

                            // Application Log
                            'log'=>array(
                                    'class'=>'CLogRouter',
                                    'routes'=>array(
                                    // Save log messages on file
                                            array(
                                                    'class'=>'CFileLogRoute',
                                                    //'levels'=>'error, warning,trace, info',
													'levels'=>'error, warning',
                                            ),
                                            // Show log messages on web pages
                                            array(
                                                    'class'=>'CWebLogRoute',
                                                    //'levels'=>'error, warning,trace, info',
													'levels'=>'error, warning',
                                            ),

                                    ),
                            ),
                    ),
            );
        }


        /**
         * Test configuration
         * Usage:
         * - Local website
         * - Local DB
         * - Standard production error pages (404,500, etc.)
         * @var array
         */
        private function _test() {
            return array(

                    // Application components
                    'components' => array(

                            // Database
                            'db'=>array(
                                   'connectionString' => 'mysql:host=localhost;dbname=anecsys_beta_db',
                                    'emulatePrepare' => true,
                                    'username' => 'anecsys_beta',
                                    'password' => 'v1nc3nt',
									//'username' => 'root',
                                    //'password' => 'scholes12',
                                    'charset' => 'utf8',
                                    'tablePrefix'=>'tbl_'
                            ),


                            // Fixture Manager for testing
                            'fixture'=>array(
                                    'class'=>'system.test.CDbFixtureManager',
                            ),

                            // Application Log
                            'log'=>array(
                                    'class'=>'CLogRouter',
                                    'routes'=>array(
                                            array(
                                                    'class'=>'CFileLogRoute',
                                                    'levels'=>'error, warning,trace, info',
                                            ),

                                            // Show log messages on web pages
                                            array(
                                                    'class'=>'CWebLogRoute',
                                                    'levels'=>'error, warning',
                                            ),
                                    ),
                            ),
                    ),
            );
        }

        /**
         * Stage configuration
         * Usage:
         * - Online website
         * - Production DB
         * - All details on error
         */
        private function _stage() {
            return array(

                    // Application components
                    'components' => array(
                    // Database
                            'db'=>array(
                                    'connectionString' => 'mysql:host=localhost;dbname=anecsys_development',
                                    'emulatePrepare' => true,
                                    'username' => 'anecsys_dev',
                                    'password' => 'v1nc3nt',
                                    'charset' => 'utf8',
                                    'tablePrefix'=>'tbl_'

                            ),

                            // Application Log
                            'log'=>array(
                                    'class'=>'CLogRouter',
                                    'routes'=>array(
                                            array(
                                                    'class'=>'CFileLogRoute',
                                                    'levels'=>'error, warning, trace, info',
                                            ),

                                    ),
                            ),
                    ),
            );
        }

        /**
         * Production configuration
         * Usage:
         * - online website
         * - Production DB
         * - Standard production error pages (404,500, etc.)
         */
        private function _production() {
            return array(

                    // Application components
                    'components' => array(

                            // Database


                            'db'=>array(
                                    'connectionString' => 'mysql:host=localhost;dbname=anecsys_dev_db',
									//'connectionString' => 'mysql:host=anecsysdbinstance.ckgbgyldlpi0.ap-southeast-2.rds.amazonaws.com;dbname=anecsys_dev_db',
                                    'emulatePrepare' => true,
                                    'username' => 'root',
                                    'password' => 'anecsysserver1111',
                                    'charset' => 'utf8',
                                    'tablePrefix'=>'tbl_'

                            ),
							'dbtm' => array(
								'connectionString' => 'mysql:host=anecsystm.ckgbgyldlpi0.ap-southeast-2.rds.amazonaws.com;dbname=translationmemory',
								'emulatePrepare' => true,
								'username'=> 'root',
								'password'=> 'anecsysserver1111',
								'charset' => 'utf8',
                                'tablePrefix'=>'tbl_',
								'class'=> 'CDbConnection'
							),


                            // Application Log
                            'log'=>array(
                                    'class'=>'CLogRouter',
                                    'routes'=>array(
                                            array(
                                                    'class'=>'CFileLogRoute',
                                                    'levels'=>'error, warning',
                                            ),

                                            // Send errors via email to the system admin
                                            array(
                                                    'class'=>'CEmailLogRoute',
                                                    'levels'=>'error, warning,trace, info',
                                                    'emails'=>'admin@example.com',
                                            ),
											
                                    ),
                            ),
							
							'cache'=>array(
								'class'=>'system.caching.CMemCache',
								//'servers'=>array(
								//	array('host'=>'127.0.0.1', 'port'=>11211, 'weight'=>60),
								//),
							),
                    ),
            );
        }
    }// END Environment Class

?>
