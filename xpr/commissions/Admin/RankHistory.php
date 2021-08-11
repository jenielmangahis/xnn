<?php


namespace Commissions\Admin;


use App\DailyVolume;
use Illuminate\Support\Facades\DB;
use Commissions\Member\RankHistory as MemberRankHistory;

class RankHistory extends MemberRankHistory
{
    const REPORT_PATH = "csv/admin/rank_history";
}