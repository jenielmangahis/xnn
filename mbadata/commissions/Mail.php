<?php


namespace Commissions;


class Mail extends Console
{
    const DEBUG = false;

    public static function send($to, $subject, $body)
    {
        $headers = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-Type: text/html; charset=utf-8' . "\r\n";
        $headers .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n";
        $headers .= 'From: ' . config('mail.from.name') . ' <' . config('mail.from.address') . ">\r\n";
        $headers .= 'Bcc: comm@mymbatrading.com';

        if(static::DEBUG) {
            $to = "comm@mymbatrading.com";
        }

        mail($to, $subject, $body , $headers);
    }
}