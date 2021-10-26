<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Commissions\Admin\PoolLiveSetting;

class PoolBonusSettingController extends Controller
{
    protected  $poolLiveSetting;

    public function __construct(PoolLiveSetting $poolLiveSetting)
    {
        $this->poolLiveSetting = $poolLiveSetting;
    }

    public function index()
    {
        return $this->poolLiveSetting->index();
    }
}
