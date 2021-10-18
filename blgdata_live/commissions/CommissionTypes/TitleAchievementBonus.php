<?php


namespace Commissions\CommissionTypes;


use Commissions\Contracts\CommissionTypeInterface;

class TitleAchievementBonus extends CommissionType implements CommissionTypeInterface
{

    public static function getBonus($rank_id)
    {
        $bonuses = [
            // Influencer
            5 => 100,
            6 => 200,
            7 => 500,
            8 => 500,
            9 => 500,
            10 => 600,
            11 => 700,
            12 => 800,
            13 => 1000,
            // Silver
            14 => 2000,
            15 => 2250,
            16 => 2500,
            17 => 2750,
            18 => 3000,
            19 => 3250,
            20 => 3500,
            21 => 4000,
            22 => 4500,
            // Gold
            23 => 5000,
            24 => 6000,
            25 => 7000,
            26 => 8000,
            27 => 9000,
            28 => 10000,
            29 => 11000,
            30 => 12000,
            31 => 13000,
            // Platinum
            32 => 15000,
            33 => 17000,
            34 => 19000,
            35 => 21000,
            36 => 23000,
            37 => 25000,
            38 => 27000,
            39 => 29000,
            40 => 31000,
            // Diamond
            41 => 250000,
        ];

        if(array_key_exists($rank_id, $bonuses)) {
            return $bonuses[$rank_id];
        }

        return 0;
    }

    public function count()
    {
        // TODO: Implement count() method.
    }

    public function generateCommission($start, $length)
    {
        // TODO: Implement generateCommission() method.
    }
}