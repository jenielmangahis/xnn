<?php

namespace Commissions;

use App\Affiliate;
use Illuminate\Support\Facades\DB;
use \PDO;

class ProFreePlan extends Console
{
    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
    }

    public function process()
    {
        DB::transaction(function () {
            $this->log("Get All Ambassadors");

            $ambasadors = $this->getMembers();

            if(count($ambasadors) > 0) {
                foreach($ambasadors as $ambasador) {
                    
                    $this->upgradeToProFreePlan($ambasador['user_id']);
                }
            } else {
                $this->log("No qualified users");
            }
        });
    }

    public function upgradeToProFreePlan($user_id)
    {
        $pro_free_plan = config('commission.member-types.pro-free-plan');

        $this->log("user_id: $user_id; catid: $pro_free_plan");
        $this->log("Update cm_affiliates catid");

        $affiliate = new Affiliate();
        
        $user = $affiliate::findOrFail($user_id);
        $user->cat_id = $pro_free_plan;
        $user->save();

        $this->log("Insert new catid in categorymap");

        $sql = "INSERT INTO categorymap (catid,userid) VALUES($pro_free_plan,$user_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
    }

    public function getMembers()
    {
        $ambasador = config('commission.member-types.ambasador');

        $sql = "
            SELECT u.id AS user_id, SUM(t.computed_cv) AS cv FROM users u 
            LEFT JOIN cm_affiliates a ON u.id = a.user_id
            JOIN v_cm_transactions t ON t.user_id = u.id
            
            WHERE a.cat_id = $ambasador AND 
            u.levelid = 3 
            GROUP BY u.id
            HAVING cv >= 25000 OR (
                    SELECT 1 FROM cm_minimum_ranks mr 
                    WHERE mr.user_id = u.id AND mr.influencer_level >= 2 
                    AND CURRENT_DATE() BETWEEN mr.start_date AND mr.end_date );
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}