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
    const MAX_POINTS = 200;
    const MIN_ACTIVE_POINTS = 40;

    protected $db;
    protected $end_date;
    protected $start_date;
    protected $affiliates;
    protected $customers;
    protected $root_user_id;
    protected $rank_requirements;

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

        return +$stmt->fetchColumn() > 0;
    }

    private function processBuilderBonus()
    {
        
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