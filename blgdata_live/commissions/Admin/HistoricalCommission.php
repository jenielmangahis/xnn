<?php


namespace Commissions\Admin;

use Commissions\Member\HistoricalCommission as MemberHistoricalCommission;


class HistoricalCommission extends MemberHistoricalCommission
{
    const REPORT_PATH = "csv/admin/historical_commission";
}