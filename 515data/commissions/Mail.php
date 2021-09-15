<?php


namespace Commissions;


class Mail extends Console
{
    const DEBUG = true;

    public static function send($to, $subject, $body)
    {
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-Type: text/html; charset=utf-8' . "\r\n";
        //$headers .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n"; remove comment if the email is plainly text
        $headers .= 'From: ' . config('mail.from.name') . ' <' . config('mail.from.address') . ">\r\n";
        $headers .= 'Bcc: comm@naxum.com';

        if(static::DEBUG) {
            $to = "comm@naxum.com";
        }

        mail($to, $subject, $body , $headers);
    }
}