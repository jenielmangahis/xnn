<?php


namespace Commissions\Member;


use App\DailyVolume;
use Commissions\CsvReport;
use Illuminate\Support\Facades\DB;
use PDO;

class QualifiedRecruit
{
    const REPORT_PATH = "csv/member/qualified_recruit";

    protected $db;

    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function getQualifiedRecruits($filters, $user_id = null)
    {
        $data = [];

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search  = $filters['search'];
        $order   = $filters['order'];
        $columns = $filters['columns'];

        $period = isset($filters['period']) ? $filters['period'] : null;
        $memberId   = isset($filters['memberId']) ? $filters['memberId'] : null;

        if (!$period) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'period');
        }

        $query = $this->getQualifiedRecruitsQuery($user_id, $period, $memberId);

        $recordsTotal = count($query->get());//TODO: Not sure why di mo gana ni -> $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('s.id', $search)
                    ->orWhere('s.sponsorid', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('s.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s.lname', 'LIKE', "%{$search}%")
                    ->orWhere('s1.fname', 'LIKE', "%{$search}%")
                    ->orWhere('s1.lname', 'LIKE', "%{$search}%");
            });
        }

        $recordsFiltered = count($query->get());//TODO: Not sure why di mo gana ni -> $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

		if ($take) {
			$query = $query->take($take);
		}

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'member_id', 'start_date');
    }

    protected function getQualifiedRecruitsQuery($user_id, $period, $memberId)
    {
        $affiliates = config('commission.member-types.affiliates');
        $customers  = config('commission.member-types.customers');
		
		$transaction_start_date = date('Y-m-1', strtotime($period));
		$transaction_end_date = date('Y-m-t', strtotime($period));

		$query =DB::table('users as u')
				->join('cm_affiliates AS ca', 'ca.user_id', '=', 'u.sponsorid')
				->leftJoin(DB::raw("
					(	
						SELECT 
							sponsorid,
							user_id
						FROM (
							SELECT 
								u.id AS user_id,
								u.sponsorid,
								SUM(COALESCE(ps.sales, 0) + COALESCE(cs.sales, 0)) AS total_prs
							FROM users u
							LEFT JOIN
							(
								SELECT
									t.user_id,
									SUM(COALESCE(t.computed_cv, 0)) AS sales
								FROM v_cm_transactions t
								WHERE transaction_date BETWEEN '$transaction_start_date' AND '$transaction_end_date'
									AND t.`type` = 'product'
									AND FIND_IN_SET(t.purchaser_catid, '$affiliates')
								GROUP BY t.user_id
							) AS ps ON ps.user_id = u.id
							LEFT JOIN (
								SELECT
									ti.upline_id AS user_id,
									SUM(COALESCE(t.computed_cv, 0)) AS sales
								FROM v_cm_transactions t
								JOIN cm_transaction_info ti ON ti.transaction_id = t.transaction_id
								WHERE t.transaction_date BETWEEN '$transaction_start_date' AND '$transaction_end_date'
									AND t.`type` = 'product' 
									AND FIND_IN_SET(t.purchaser_catid, '$customers')
								GROUP BY ti.upline_id
							) AS cs ON cs.user_id = u.id
							GROUP BY u.id
							HAVING total_prs >= 500
						) AS prs_grouped
					) AS p "), 'u.id', '=', 'p.user_id')
			->selectRaw("
				s.id AS user_id,
				CONCAT(s.fname, ' ', s.lname) AS member,
				s.created AS enrolled_date,
                IFNULL(ud.datedone, '') as affiliated_date,
				s.email,
				s.country,
				s1.id AS sponsor_id,
				CONCAT(s1.fname, ' ', s1.lname) AS sponsor,
				COUNT(p.user_id) AS sponsored_qualified_representatives_count,
				(SELECT
					COUNT(*) AS total_reps
				FROM users u
				JOIN cm_affiliates c ON u.id = c.user_id
				WHERE c.affiliated_date BETWEEN '$transaction_start_date' AND '$transaction_end_date'
				AND u.sponsorid = ca.user_id) AS total_reps
			")
			->leftJoin('users AS s', 's.id', '=', 'u.sponsorid')
			->leftJoin('users AS s1', 's1.id', '=', 's.sponsorid')
			->leftJoin(
                DB::raw(
                    "(SELECT userid, datedone FROM updowngrade where status = 'done' AND FIND_IN_SET(newcatid, '$affiliates')) as ud"
                ), 's.id', '=', 'ud.userid');

		$query->groupBy('u.sponsorid');

		if (!!$memberId) {
			$query = $query->where('u.sponsorid', $memberId);
		}

        return $query;
    }

    public function getQualifiedRecruitsDownloadLink($filters, $user_id = null)
    {
        $period    = isset($filters['period']) ? $filters['period'] : null;
        $memberId      = isset($filters['memberId']) ? $filters['memberId'] : null;

        $csv   = new CsvReport(static::REPORT_PATH);

        $data = $this->getQualifiedRecruitsQuery($user_id, $period, $memberId)->get();

        $filename = "qualified-recruits-$period";

        if ($memberId !== null) {
            $filename .= "$memberId-";
        }

        $filename .= time();

        return $csv->generateLink($filename, $data);
    }

    public function getUserRepresentativeList($filters, $user_id = null)
    {
        $data = [];

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search  = $filters['search'];
        $order   = $filters['order'];
        $columns = $filters['columns'];

        $period  = isset($filters['period']) ? $filters['period'] : null;
        $userId  = isset($filters['userId']) ? $filters['userId'] : null;

        if (!$period) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'period');
        }

        $query = $this->getUserRepresentativeQuery($userId, $period);
        $recordsTotal = count($query->get());//TODO: Not sure why di mo gana ni -> $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('s.user_id', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")                    
            });
        }

        $recordsFiltered = count($query->get());//TODO: Not sure why di mo gana ni -> $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

		if ($take) {
			$query = $query->take($take);
		}

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'member_id', 'start_date');
    }

    protected function getUserRepresentativeQuery($user_id, $period)
    {
        $affiliates = config('commission.member-types.affiliates');
        $customers  = config('commission.member-types.customers');
		
		$transaction_start_date = date('Y-m-1', strtotime($period));
		$transaction_end_date = date('Y-m-t', strtotime($period));

		$query =DB::table('users u')
			->join('cm_affiliates AS ca', 'u.id', '=', 'ca.user_id')
			->selectRaw("
				u.id AS user_id,
				u.sponsorid,
				CONCAT(u.fname, ' ', u.lname) AS member_name
			")
		;

		$query->whereBetween('c.affiliated_date', [$transaction_start_date, $transaction_end_date]);
		$query->where('u.sponsorid', $user_id);

        return $query;
    }

    public function getQualifiedUserRepresentativeList($filters, $user_id = null)
    {
    	$data = [];

        $draw = intval($filters['draw']);

        $skip = $filters['start'];
        $take = $filters['length'];

        $search  = $filters['search'];
        $order   = $filters['order'];
        $columns = $filters['columns'];

        $period  = isset($filters['period']) ? $filters['period'] : null;
        $userId  = isset($filters['userId']) ? $filters['userId'] : null;

        if (!$period) {
            return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'period');
        }

        $query = $this->getQualifiedUserRepresentativeQuery($userId, $period);
        $recordsTotal = count($query->get());//TODO: Not sure why di mo gana ni -> $query->count(DB::raw("1"));

        // apply search
        $search = isset($search['value']) ? $search['value'] : "";

        if (is_numeric($search) && is_int(+$search)) {

            $query->where(function ($query) use ($search) {
                $query->where('s.user_id', $search);
            });

        } elseif (!!$search) {
            $query->where(function ($query) use ($search) {
                $query->where('u.fname', 'LIKE', "%{$search}%")
                    ->orWhere('u.lname', 'LIKE', "%{$search}%")                    
            });
        }

        $recordsFiltered = count($query->get());//TODO: Not sure why di mo gana ni -> $query->count(DB::raw("1"));

        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

		if ($take) {
			$query = $query->take($take);
		}

        if ($skip) {
            $query = $query->skip($skip);
        }

        $data = $query->get();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'member_id', 'start_date');
    }

    protected function getQualifiedUserRepresentativeQuery($user_id, $period)
    {
        $affiliates = config('commission.member-types.affiliates');
        $customers  = config('commission.member-types.customers');
		
		$transaction_start_date = date('Y-m-1', strtotime($period));
		$transaction_end_date = date('Y-m-t', strtotime($period));

		$query =DB::table('users u')
			->join('cm_affiliates AS ca', 'u.id', '=', 'ca.user_id')
			->leftJoin(DB::raw("
				(
					SELECT
						t.user_id,
						SUM(COALESCE(t.computed_cv, 0)) AS sales
					FROM v_cm_transactions t
					WHERE transaction_date BETWEEN '$transaction_start_date' AND '$transaction_end_date'
						AND t.`type` = 'product'
						AND FIND_IN_SET(t.purchaser_catid, '$affiliates')
					GROUP BY t.user_id
				)AS ps
				"), 'ps.user_id', '=', 'u.id'
			)
			->leftJoin(DB::raw("
				(
					SELECT
						ti.upline_id AS user_id,
						SUM(COALESCE(t.computed_cv, 0)) AS sales
					FROM v_cm_transactions t
					JOIN cm_transaction_info ti ON ti.transaction_id = t.transaction_id
					WHERE t.transaction_date BETWEEN '$transaction_start_date' AND '$transaction_end_date'
						AND t.`type` = 'product' 
						AND FIND_IN_SET(t.purchaser_catid, '$customers')
					GROUP BY ti.upline_id
				) AS cs
				"), 'cs.user_id', '=', 'u.id'
			)
		;

		$query->whereBetween('c.affiliated_date', [$transaction_start_date, $transaction_end_date]);
		$query->where('u.sponsorid', $user_id);

        return $query;
    }
}