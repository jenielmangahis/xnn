<?php


namespace App;

use Illuminate\Database\Eloquent\Model;

class OfficeGiftCard extends  Model
{
    protected $primaryKey = 'code';
    protected $table = "gift_cards";
    public $timestamps = false;

    protected $guarded = ['code'];

    public function validationCode(){
        $lower_limit = 100000;
        $upper_limit = 999999;

        $vc = (int)(rand($upper_limit,$lower_limit) + $lower_limit);

        return $vc;
    }

    public static function generateRandomString($length = 6) {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}