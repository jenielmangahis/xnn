<?php

namespace App\Http\Controllers\Admin\CommissionRunSettings\Live;

use Commissions\Admin\PoolLiveSetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PoolBonusController extends Controller
{
    protected  $poolLiveSetting;

    public function __construct(PoolLiveSetting $poolLiveSetting)
    {
        $this->poolLiveSetting = $poolLiveSetting;
    }

    public function index()
    {
        dd('this is index');
        return $this->poolLiveSetting->index();
    }

    public function logs()
    {

    }
}
