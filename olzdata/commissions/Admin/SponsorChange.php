<?php

namespace Commissions\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Capsule\Manager;
use Exception;
use App\User;
use App\UserPlacement;
use App\UserMatrix;
use PDO;

class SponsorChange
{
    public function changeSponsor($tree_id, $member_id, $sponsor_id, $moved_by_id, $update_past_orders = false)
    {
        $this->checkForError($tree_id, $member_id, $sponsor_id);


        DB::transaction(function() use ($tree_id, $member_id, $sponsor_id, $moved_by_id, $update_past_orders){

            $db = DB::connection()->getPdo();

            if(+$tree_id === 1) {
                // tree id 1 Enrollment

                $member = User::findOrFail($member_id);

                $old_parent_id = $member->sponsorid;

                DB::table('users')->where('id', $member_id)->update(['sponsorid' => $sponsor_id]);

                $category_ids =  config('commission.member-types.customers');
                $category_ids .= ',';
                $category_ids .= config('commission.member-types.affiliates');

                // UPDATE ORDERS HERE
				if($update_past_orders) {
					$qry = "
                        UPDATE transactions t
                            SET t.sponsorid = :sponsor_id,
                        t.sponsor_catid = (
                            SELECT 
                                cm.catid 
                            FROM categorymap cm 
                            WHERE cm.userid = $sponsor_id
                            ORDER BY FIND_IN_SET(cm.catid, '$category_ids') DESC
                            LIMIT 1
                        )
                        WHERE userid = :user_id
                    ";

                    $smt = $db->prepare($qry);
                    $smt->bindParam(':user_id',$member_id);
                    $smt->bindParam(':sponsor_id',$sponsor_id);
                    $smt->execute();

                    $update_past_orders = 1;
                    
				} else {
					$update_past_orders = 0;
				}

                #################################################

                $qry = "INSERT INTO cm_genealogy_history(user_id, old_parent_id, new_parent_id, tree_id, module_used, moved_by_id, orders_updated) VALUES(:user_id, :old_parent_id, :new_parent_id, :tree_id, 'sponsorchange', :moved_by_id, :update_past_orders)";

                $smt = $db->prepare($qry);

                $smt->bindParam(':user_id',$member_id);
                $smt->bindParam(':old_parent_id',$old_parent_id);
                $smt->bindParam(':new_parent_id',$sponsor_id);
                $smt->bindParam(':tree_id',$tree_id);
                $smt->bindParam(':moved_by_id',$moved_by_id);
                $smt->bindParam(':update_past_orders',$update_past_orders);
                $smt->execute();

                // #################################################

                $value = 'UNI LEVEL Tree Change Sponsor: Old sponsor('.$old_parent_id.') to New Sponsor('.$sponsor_id.')';

                $doneby = $moved_by_id.' SPONSOR CHANGE TOOL';

                $qry = "INSERT INTO users_mods(userid, doneby, vals) VALUES(:user_id, :doneby, :value)";

                $smt = $db->prepare($qry);

                $smt->bindParam(':user_id',$member_id);
                $smt->bindParam(':value',$value);
                $smt->bindParam(':doneby',$doneby);
                $smt->execute();
            }
        });

        // return ['message' => 'ok'];
    }

    public function getRelationship($tree_id, $member_id, $sponsor_id)
    {
        $this->checkForError($tree_id, $member_id, $sponsor_id);

        if (+$tree_id == 1) {
            return $this->getEnrollmentRelationship($tree_id, $member_id, $sponsor_id);
        }
        elseif (+$tree_id == 2) {
            return $this->getPlacementRelationship($tree_id, $member_id, $sponsor_id);
        }
        else {
            return $this->getMatrixRelationship($tree_id, $member_id, $sponsor_id);
        }

    }

    public function getEnrollmentRelationship($tree_id, $member_id, $sponsor_id)
    {
        $member = User::findOrFail($member_id);
        $sponsor = User::findOrFail($sponsor_id);

        $member_sponsor = $member->sponsor;
        $sponsor_sponsor = $sponsor->sponsor;

        if($sponsor_id == 3 || !($sponsor->sponsor))
        $sponsor_sponsor = $sponsor;



        $before = [];
        $after = [];

        if($member->sponsorid == $sponsor_id)
        {
            $before[] = [
                'member_id' => $sponsor_id,
                'member_name' => $sponsor->fname . ' ' . $sponsor->lname,
                'level' => 0,
                'sponsor_id' => $sponsor->sponsorid,
                'sponsor_name' => $sponsor_sponsor->fname . ' ' . $sponsor_sponsor->lname,
            ];

            $before[] = [
                'member_id' => $member_id,
                'member_name' => $member->fname . ' ' . $member->lname,
                'level' => 1,
                'sponsor_id' => $sponsor->id,
                'sponsor_name' => $sponsor->fname . ' ' . $sponsor->lname,
            ];

            $after[] = [
                'message' => 'No changes'
            ];

            return compact('before', 'after');
        }

        $on_leg = $this->on_leg($tree_id, $member_id, $sponsor_id);

        if(!empty($on_leg))
        {
            $before[] = [
                'member_id' => $member_sponsor->id,
                'member_name' => $member_sponsor->fname . ' ' . $member_sponsor->lname,
                'level' => 0,
                'sponsor_id' => $member_sponsor->sponsorid,
                'sponsor_name' => $member_sponsor->sponsor->fname . ' ' . $member_sponsor->sponsor->lname,
            ];

            $before[] = [
                'member_id' => $sponsor->id,
                'member_name' => $sponsor->fname . ' ' . $sponsor->lname,
                'level' => $on_leg['level'],
                'sponsor_id' => $sponsor_sponsor->id,
                'sponsor_name' => $sponsor_sponsor->fname . ' ' . $sponsor_sponsor->lname,
            ];

            $before[] = [
                'member_id' => $member->id,
                'member_name' => $member->fname . ' ' . $member->lname,
                'level' => $on_leg['level'] + 1,
                'sponsor_id' => $sponsor->id,
                'sponsor_name' => $sponsor->fname . ' ' . $sponsor->lname,
            ];

            $after[] = [
                'member_id' => $sponsor_id,
                'member_name' => $sponsor->fname . ' ' . $sponsor->lname,
                'level' => 0,
                'sponsor_id' => $sponsor->sponsorid,
                'sponsor_name' => $sponsor_sponsor->fname . ' ' . $sponsor_sponsor->lname,
            ];

            $after[] = [
                'member_id' => $member_id,
                'member_name' => $member->fname . ' ' . $member->lname,
                'level' => 1,
                'sponsor_id' => $sponsor->id,
                'sponsor_name' => $sponsor->fname . ' ' . $sponsor->lname,
            ];

            return compact('before', 'after');
        }


        $before[] = [
            'message' => 'No Relationship. New sponsor is on different leg.'
        ];

        $after[] = [
            'member_id' => $sponsor_id,
            'member_name' => $sponsor->fname . ' ' . $sponsor->lname,
            'level' => 0,
            'sponsor_id' => $sponsor->sponsorid,
            'sponsor_name' => $sponsor_sponsor->fname . ' ' . $sponsor_sponsor->lname,
        ];

        $after[] = [
            'member_id' => $member_id,
            'member_name' => $member->fname . ' ' . $member->lname,
            'level' => 1,
            'sponsor_id' => $sponsor->id,
            'sponsor_name' => $sponsor->fname . ' ' . $sponsor->lname,
        ];

        return compact('before', 'after');
    }

    public function checkForError($tree_id, $member_id, $sponsor_id)
    {

        if(!$tree_id)
        {
            throw new \Exception(" Tree is required");
        }

        if(!$member_id)
        {
            throw new \Exception(" Member is required");
        }

        if(!$sponsor_id)
        {
            throw new \Exception(" Sponsor is required");
        }

        if($member_id == $sponsor_id)
        {
            throw new \Exception(" You cannot assign member itself as its sponsor.");
        }

        if(+$tree_id == 1)
        {
            $member = User::find($member_id);

            if($member == null)
            {
                throw new \Exception(" Member is required");
            }

            if($member->sponsorid == $sponsor_id)
            {
                throw new \Exception(" The new sponsor is the same with the current sponsor of the member.");
            }
        }

        if($this->isCircularSponsorship($tree_id, $member_id, $sponsor_id))
        {
            throw new \Exception(" The new sponsor is a downline of the selected member.");
        }
    }

    private function isCircularSponsorship($tree_id, $member_id, $sponsor_id)
    {
        $db = DB::connection()->getPdo();

        if(+$tree_id == 1)
        {
            $sql = "
                    WITH RECURSIVE cte (user_id, sponsor_id, dt_enrolled, `level`) AS (
                        SELECT 
                            id as userid,
                            sponsorid,
                            created,                    
                            1 AS `level`
                        FROM users
                        WHERE sponsorid = :member_id 
                        
                        UNION ALL
                        
                        SELECT
                            id as userid,
                            sponsorid,
                            created,
                            `level` + 1 `level`
                        FROM users u
                        INNER JOIN cte ON u.sponsorid = cte.user_id
                    )
                    SELECT * FROM cte WHERE cte.user_id = :sponsor_id
                    ";
            $stmt = $db->prepare($sql);
            $stmt->bindParam('member_id', $member_id);
            $stmt->bindParam('sponsor_id', $sponsor_id);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $db = null;
        return count($result) > 0;
    }

    private function on_leg($tree_id, $user_id, $sponsor_id)
    {
        $db = DB::connection()->getPdo();

        if(+$tree_id == 1){
            $qry = "WITH RECURSIVE cte (user_id, sponsor_id, dt_enrolled, `level`) AS (
                        SELECT 
                            id as userid,
                            sponsorid,
                            created,                    
                            1 AS `level`
                        FROM users
                        WHERE sponsorid = :sponsor_id 
                        
                        UNION ALL
                        
                        SELECT
                            id as userid,
                            sponsorid,
                            created,
                            `level` + 1 `level`
                        FROM users u
                        INNER JOIN cte ON u.sponsorid = cte.user_id
                    )
                    SELECT sponsor_id, level FROM cte WHERE cte.user_id = :user_id";

            $smt = $db->prepare($qry);
            $smt->bindParam(':user_id',$user_id);
            $smt->bindParam(':sponsor_id',$sponsor_id);
            $smt->execute();

            $res = $smt->fetch(PDO::FETCH_ASSOC);
        }

        return $res;
    }

    public function logs($request)
    {

        $draw = intval($request['draw']);
        $skip = $request['start'];
        $take = $request['length'];
        $search = $request['search'];
        $order = $request['order'];
        $columns = $request['columns'];

        // custom filters

        // build the query
        $query = DB::table('cm_genealogy_history AS h')
            ->selectRaw("
                h.id,
                CONCAT(u.id, ':', u.fname, ' ', u.lname) member,
                CONCAT(n.id, ':', n.fname, ' ', n.lname) new_sponsor,
                CONCAT(o.id, ':', o.fname, ' ', o.lname) old_sponsor,
                CONCAT(m.id, ':', m.fname, ' ', m.lname) moved_by,
                CASE
                    WHEN tree_id = 1 THEN 'Enroller Tree'
                    ELSE ''
                END AS tree,
                h.orders_updated,
                h.created_at
            ")
            ->join('users AS u', 'u.id', '=', 'h.user_id')
            ->join('users AS n', 'n.id', '=', 'h.new_parent_id')
            ->join('users AS o', 'o.id', '=', 'h.old_parent_id')
            ->join('users AS m', 'm.id', '=', 'h.moved_by_id')
            ->where('h.module_used', 'sponsorchange');

        // count total records
        $recordsTotal = $query->count(DB::raw("1"));

        // apply where
        if (isset($search) && $search['value'] != '') {
            $value = trim($search['value']);

            if(is_numeric($value) && is_int(+$value))
            {
                $query =
                    $query->where(function ($query) use ($value) {
                        $query->where("u.id", $value)
                            ->orWhere("n.id", $value)
                            ->orWhere("o.id", $value)
                            ->orWhere("m.id", $value);
                    });
            }
            else
            {
                $query =
                    $query->where(function ($query) use ($value) {
                        $query->whereRaw("CONCAT(u.fname, ' ', u.lname) LIKE ?", ["%{$value}%"])
                            ->orWhereRaw("CONCAT(n.fname, ' ', n.lname) LIKE ?", ["%{$value}%"])
                            ->orWhereRaw("CONCAT(o.fname, ' ', o.lname) LIKE ?", ["%{$value}%"])
                            ->orWhereRaw("CONCAT(m.fname, ' ', m.lname) LIKE ?", ["%{$value}%"]);
                    });
            }
        }

        // count total filtered records
        $recordsFiltered = $query->count(DB::raw("1"));

        // apply order by (only 1 column for now)
        if (isset($order) && count($order)) {
            $column = $order[0];
            $query = $query->orderBy($columns[+$column['column']]['data'], $column['dir']);
        }

        // apply limit
        $query = $query->take($take);

        // apply offset
        if ($skip) $query = $query->skip($skip);

        // apply group by
        // $query = $query->groupBy('order_id')
        //         ->having('commission_value', '>', 0);

        $data = $query->get();
        $dump = $query->toSql();

        return compact('recordsTotal', 'draw', 'recordsFiltered', 'data', 'dump');
    }
}