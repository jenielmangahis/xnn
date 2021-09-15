<?php


namespace Commissions\Admin;


use App\DailyVolume;
use Illuminate\Support\Facades\DB;
use Commissions\Member\RankHistory as MemberRankHistory;

class RankHistory extends MemberRankHistory
{
    const REPORT_PATH = "csv/admin/rank_history";
    const ENROLLMENT_RANK_FILTER = "paid_as_rank_id";
}