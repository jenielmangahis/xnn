<?php

namespace App\nxm\models;

use Illuminate\Database\Eloquent\Model as Eloquent;

class Matrix extends Eloquent
{
    protected $table = 'cm_matrix';

    /**
     * @param $str
     * @param $key
     * @return array
     * Accept hierarchy string separated by "-" and convert it to array.
     */
    public static function hierarchyStrToArray($str, $key) {

        $len = strlen((string)$key);
        $leftSide = strpos((string)$str, (string)$key); //
        $lmost = substr((string)$str, 0, $len + $leftSide);

        return array_reverse(explode("-", $lmost));
    }
} // Matrix