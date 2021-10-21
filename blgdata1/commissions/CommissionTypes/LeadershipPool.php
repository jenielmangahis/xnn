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
        return count($this->getSponsoredCustomerOrders());        
    }

    public function generateCommission($start, $length)
    {
      $this->log("Processing");
    }

    
}