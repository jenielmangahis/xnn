<?php


namespace Commissions\Admin;

use Illuminate\Support\Facades\DB;
use \Illuminate\Database\Capsule\Manager;
use PDO;
use Commissions\Member\AutoshipReport as MemberAutoshipReport;

class AutoshipReport extends MemberAutoshipReport
{
	const REPORT_PATH = "csv/admin/autoship";
}


