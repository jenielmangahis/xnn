<?php
/**
 * Created by 
 * User: Jeniel Mangahis
 * Date: 10/21/2021
 * Time: 10:00 PM
 */

namespace Commissions\CommissionTypes;

use Illuminate\Support\Facades\DB as DB;


class LeadershipPool extends CommissionType
{
  

    public function count()
    {
        return 1;
    }

    public function generateCommission($start, $length)
    {
      $this->log("Processing");
    }

    
}