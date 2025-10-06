<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$config['gmail_email'] = 'ezhuthupizhaiofficial@gmail.com';
$config['gmail_client_id'] = '1052103106113-qo2c17cjbm6h3hhm36rl4fstf94mepe2.apps.googleusercontent.com';
$config['gmail_client_secret'] = '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';
$config['gmail_refresh_token'] = '1//045vvH8TMHgEvCgYIARAAGAQSNwF-L9Irc2ZRHk_uWEPV86yqXw1_LGcjLuOW-lFem-REoNqjnlMPnDk1DDcYDQvWEyMot-b_4kw';

$config['protocol']    = 'smtp';
$config['smtp_host']   = 'smtp.gmail.com';
$config['smtp_port']   = 587;
$config['smtp_crypto'] = 'tls';
$config['charset']     = 'utf-8';
$config['newline']     = "\r\n";
$config['mailtype']    = 'html'; // Can be 'text' or 'html'
$config['validation']  = TRUE;

$config['from_email'] = 'iloveadyar@gmail.com';
$config['from_name']  = 'Ezhuthupizhai Support';

$config['otp_email_subject_template'] = 'Your {APP_NAME} OTP Code';
$config['otp_email_body_template'] = '
    <p>Dear User,</p>
    <p>Your One-Time Password (OTP) for {APP_NAME} is: <strong>{OTP_CODE}</strong></p>
    <p>This OTP is valid for {OTP_VALIDITY_MINUTES} minutes. Please do not share this code with anyone.</p>
    <p>If you did not request this OTP, please ignore this email.</p>
    <p>Thank you,<br>The {APP_NAME} Team</p>
';