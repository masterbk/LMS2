<?php

defined('MOODLE_INTERNAL') || die();
$functions = array(
    'local_custom_service_get_course_general_info' => array(
        'classname' => 'local_custom_service_external',
        'methodname' => 'get_course_general_info',
        'classpath' => 'local/custom_service/externallib.php',
        'description' => 'Get course info for mobile app',
        'type' => 'write',
        'ajax' => true,
    ), 
    'local_custom_service_get_courses_general_info' => array(
        'classname' => 'local_custom_service_external',
        'methodname' => 'get_courses_general_info',
        'classpath' => 'local/custom_service/externallib.php',
        'description' => 'Get course info for mobile app',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_custom_service_get_user_profile' => array(
        'classname' => 'local_custom_service_external',
        'methodname' => 'get_user_profile',
        'classpath' => 'local/custom_service/externallib.php',
        'description' => 'Get user profile for mobile app',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_custom_service_get_user_certificates' => array(
        'classname' => 'local_custom_service_external',
        'methodname' => 'get_user_certificates',
        'classpath' => 'local/custom_service/externallib.php',
        'description' => 'Get user certificates for mobile app',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_custom_service_update_user_profile' => array(
        'classname' => 'local_custom_service_external',
        'methodname' => 'update_user_profile',
        'classpath' => 'local/custom_service/externallib.php',
        'description' => 'Update user profile for mobile app',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_custom_service_change_user_lang' => array(
        'classname' => 'local_custom_service_external',
        'methodname' => 'change_user_lang',
        'classpath' => 'local/custom_service/externallib.php',
        'description' => 'Change language for mobile app',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_custom_service_change_user_password' => array(
        'classname' => 'local_custom_service_external',
        'methodname' => 'change_user_password',
        'classpath' => 'local/custom_service/externallib.php',
        'description' => 'Change password for mobile app',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_custom_service_remove_user_account' => array(
        'classname' => 'local_custom_service_external',
        'methodname' => 'remove_user_account',
        'classpath' => 'local/custom_service/externallib.php',
        'description' => 'Remove user account',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_custom_service_update_courses_lti' => array(
        'classname' => 'local_custom_service_external',
        'methodname' => 'update_courses_lti',
        'classpath' => 'local/custom_service/externallib.php',
        'description' => 'Update courses LTI to show in Gradebook',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_custom_service_update_courses_sections' => array(
        'classname' => 'local_custom_service_external',
        'methodname' => 'update_courses_sections',
        'classpath' => 'local/custom_service/externallib.php',
        'description' => 'Update courses sections title in DB',
        'type' => 'write',
        'ajax' => true,
    )
);

$services = array(
    'VTC Custom Services' => array(
        'functions' => array(            
            'local_custom_service_get_course_general_info',
            'local_custom_service_get_courses_general_info',
            'local_custom_service_get_user_profile',
            'local_custom_service_get_user_certificates',
            'local_custom_service_update_user_profile',
            'local_custom_service_change_user_lang',
            'local_custom_service_change_user_password',
            'local_custom_service_remove_user_account',
            'local_custom_service_update_courses_lti',
            'local_custom_service_update_courses_sections'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'vtc_web_services'
    )
);