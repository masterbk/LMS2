

<?php

$cipher_method = 'AES-128-CTR';
$iv_length = openssl_cipher_iv_length($cipher_method);
$options = 0;
$encryption_iv = '1234567891011121';
$encryption_key = openssl_digest(php_uname(), 'MD5', TRUE);

// Decrypt the password
$username = openssl_decrypt($_GET['username'], $cipher_method, $encryption_key, $options, $encryption_iv);
$password = openssl_decrypt($_GET['password'], $cipher_method, $encryption_key, $options, $encryption_iv);


// Include the Moodle config file (adjust the path as needed)
require('../config.php');
require_once('lib.php');

// Create a new Moodle user object
$user = authenticate_user_login($username, $password);

// Check if the user is authenticated
if ($user) {
    // Complete the user login
    complete_user_login($user);

    // Redirect the user to their dashboard or another page
    redirect(new moodle_url('/my/'));
} else {
    // Handle login failure
    echo 'Login failed';
}