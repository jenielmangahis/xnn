<?php
require_once($_SERVER["DOCUMENT_ROOT"] . '/commissions/commissions.class.php');
class ELM_Commission extends Commission
{
    private $debug;
    private $autoqualified;

    public function __construct($test = false)
    {
        parent::__construct($test);
    }

    public function setDebug()
    {
        $this->debug = true;
    }

    public function getDatePeriodsByType($type)
    {
        $sql = "Select DATE_FORMAT( start_date,  '%b %d %Y') AS start_date, DATE_FORMAT( end_date,  '%b %d %Y') AS end_date 
					from cm_commission_periods p join cm_commission_period_types cpt using(commission_period_type_id)
					where cpt.frequency=:type and p.locked=1 group by start_date";

        $smt = $this->db->prepare($sql);
        $smt->bindParam('type', $type);

        $smt->execute();
        $result = $smt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public function getHistoricalCommissions($data)
    {
        //TODO: Update query for getting historical commission
        $start_date = date('Y-m-d', strtotime($data['from']));
        $end_date = date('Y-m-d', strtotime($data['to']));

        //temp data only
        $entry1 = ['buyer'=>'Dale Payne-Sizer','commission_type'=>'Direct Commission','commission'=>'100.00','percent'=>'30%','level'=>'1','product'=>'E-Boot Camp'];
        $entry2 = ['buyer'=>'Dale Payne-Sizer','commission_type'=>'Coded Commission','commission'=>'50.00','percent'=>'10%','level'=>'1','product'=>'Ignite Coaching'];
        $result = [$entry1, $entry2];
        return $result;
    }

    public function getChildren($user_id){

        $sql = "SELECT 

                DISTINCT
                u.id as id,
                u.fname as fname,
                u.lname as lname,
                IFNULL(u.country, '') as country,
                CONCAT(s.fname,' ',s.lname) as sponsor_name,
                IFNULL(r.name, '') as rank,
                (
                SELECT IFNULL(DATE_FORMAT(max(t.transactiondate), '%M %d, %Y'),'') FROM transactions t 
                INNER JOIN categorymap cm2 ON cm2.userid = t.userid
                WHERE t.sponsorid = cmn.member_id AND cm2.catid = ".CUSTOMER." and t.type = 'product'
                ) as last_retail_sale
                
                FROM cm_nodes cmn

                INNER JOIN users s ON cmn.parent_id = s.id
                INNER JOIN users u ON cmn.member_id = u.id
                INNER JOIN categorymap cm ON cm.userid = u.id
                INNER JOIN categories c ON c.id = cm.catid
                LEFT JOIN ranks r ON r.id = u.rank_id

                WHERE cmn.parent_id=:user_id and u.levelid=3 and cm.catid = ".IBP."";

        $stmt =$this->db->prepare($sql);
        $stmt->bindParam("user_id", $user_id);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $details = array();
        foreach($results as $r){
            $child_count = $this->getChildCount($r['id']);
            $branch ="false";

            if($child_count > 0) $branch="true";

            $detail = array('id' => $r['id'],
                'fname' => $r['fname'],
                'lname' =>$r['lname'],
                'country' => $r['country'],
                'rank' => $r['rank'],
                'sponsor_name' => $r['sponsor_name'],
                'last_retail_sale' => $r['last_retail_sale'],
                'branch' => $branch);

            $details[] = $detail;
        }

        return $details;
    }

    private function getChildCount($user_id) {
        $sql = "SELECT 

                COUNT(DISTINCT u.member_id) as m_count
                
                FROM cm_nodes u
                
                INNER JOIN categorymap cm ON cm.userid = u.parent_id
                INNER JOIN categories c ON c.id = cm.catid
                
                WHERE u.parent_id= :user_id and cm.catid = ".IBP."";
        $stmt =$this->db->prepare($sql);
        $stmt->bindParam("user_id", $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['m_count'];
    }
}