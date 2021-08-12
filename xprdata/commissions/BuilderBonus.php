<?php


namespace Commissions;

use App\Rank;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\DailyVolume;
use \PDO;
use DateTime;


final class BuilderBonus extends Console
{
    protected $db;
    protected $end_date;
    protected $start_date;
    protected $qualified_members;
    protected $members_builder_bonus;
    protected $root_user_id;

    public function __construct($end_date = null)
    {
        $this->db = DB::connection()->getPdo();
        $this->root_user_id = 3;

        $this->setDates($end_date);
    }

    private function process()
    {
        DB::transaction(function () {
            $this->setMainParameters();

            $this->log("Start Date: " . $this->getStartDate());
            $this->log("End Date: " . $this->getEndDate());

            $this->log("Fetching qualified members");
            $this->getQualifiedMembers();
            
            $this->log("Processing builder bonus");
            $this->computeBuilderBonus();
            $this->processBuilderBonus();
        }, 3);
    }

    private function getQualifiedMembers()
    {

        $sql = "
            SELECT dv.user_id, ca.cat_id, MAX(dv.cv) AS total_cv
            FROM cm_daily_volumes AS dv
                LEFT JOIN cm_affiliates AS ca ON dv.user_id = ca.user_id 
                LEFT JOIN users AS u ON dv.user_id = u.id 
            WHERE ca.cat_id IN(13,16,14) AND dv.cv >= 50      
            GROUP BY ca.user_id 
        ";

        $db = DB::connection()->getPdo();
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach($result as $r){
            $this->qualified_members[$r->user_id] = $r->user_id;
        }
    }

    private function computeBuilderBonus()
    {
        foreach($this->qualified_members as $key => $value){
            //Get member qualified consultants
            $sql = "
                SELECT dv.user_id, ca.cat_id, MAX(dv.cv) AS total_cv
                FROM cm_daily_volumes AS dv
                    LEFT JOIN cm_affiliates AS ca ON dv.user_id = ca.user_id 
                    LEFT JOIN users AS u ON dv.user_id = u.id 
                WHERE dv.cv >= 50 AND dv.user_id = (
                        SELECT uu.id 
                        FROM users uu 
                        WHERE uu.leaderid = :user_id
                    )
                GROUP BY ca.user_id 
            ";

            $db = DB::connection()->getPdo();
            $stmt = $db->prepare($sql);
            $stmt->bindParam(":user_id", $key);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $total_qualified     = count($result);
            $total_builder_bonus = 100 * $total_qualified;

            $this->members_builder_bonus[] = ['member_id' => $key, 'total_builder_bonus' => $total_builder_bonus];
        }
    }

    private function processBuilderBonus()
    {
        //Inser builder bonus data
    }

    protected function setDates($end_date = null)
    {
        $end_date = $this->getRealCarbonDateParameter($end_date);

        $this->end_date = $end_date->format("Y-m-d");
        $this->start_date = $end_date->copy()->firstOfMonth()->format("Y-m-d");
    }

    public function run($comissions_period_id)
    {
        $this->setDates($end_date);

        $this->process();
    }

    public function getEndDate()
    {
        if (!isset($this->end_date)) {
            throw new Exception("End date is not set.");
        }

        return $this->end_date;
    }

    public function setEndDate($end_date)
    {
        $this->throwIfInvalidDateFormat($end_date);
        $this->end_date = $end_date;
    }

    public function getStartDate()
    {
        if (!isset($this->start_date)) {
            throw new Exception("Start date is not set.");
        }
        return $this->start_date;
    }

    public function setStartDate($start_date)
    {
        $this->throwIfInvalidDateFormat($start_date);
        $this->start_date = $start_date;
    }
}