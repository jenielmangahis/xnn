<?php

namespace App\Console\Commands\Commission;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use \PDO;

class SetEnergyAccountStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:set-energy-account-status {date? : The date to run (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set Energy Account Status';

    protected $db;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->db = DB::connection()->getPdo();
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {	
        try {
			
			$this->info('Time started - ' . Carbon::now());
			$executionDate = $this->argument('date');

			if (!isset($executionDate) || empty($executionDate)) {
				$executionDate = date('Y-m-d h:m:s');
			}
			$this->info('Date param: ' . $executionDate );

            DB::transaction(function() use ($executionDate) {
                
				//For flowing
                DB::statement("
					UPDATE cm_energy_accounts cea
					JOIN cm_energy_account_flowing ceac ON cea.plank_energy_account_id = ceac.plank_energy_account_id
					SET cea.status = 5
					WHERE ceac.flowing_date = '$executionDate'
					AND EXISTS (SELECT 1 FROM cm_energy_account_logs l WHERE l.energy_account_id = cea.id AND l.current_status = 4)
                ");

				DB::statement("
					INSERT INTO cm_energy_account_logs (notes, energy_account_id, current_status, customer_id, reference_id, flowing_id, created_at)
					SELECT
						'CRON execution',
						cea.id,
						5,
						cea.customer_id,
						cea.reference_id,
						ceac.id,
						'$executionDate'
					FROM cm_energy_account_flowing ceac
					JOIN cm_energy_accounts cea ON cea.plank_energy_account_id = ceac.plank_energy_account_id
					WHERE ceac.flowing_date = '$executionDate'
					AND NOT EXISTS (SELECT 1 FROM cm_energy_account_logs l WHERE l.energy_account_id = cea.id AND l.current_status = 5)
                    AND EXISTS (SELECT 1 FROM cm_energy_account_logs l WHERE l.energy_account_id = cea.id AND l.current_status = 4)
					GROUP BY ceac.plank_energy_account_id
                ");

                //For cancellation
                DB::statement("
					UPDATE cm_energy_accounts cea
					JOIN cm_energy_account_cancellation ceac ON cea.plank_energy_account_id = ceac.plank_energy_account_id
					SET cea.status = 7
					WHERE ceac.cancellation_date = DATE_SUB('$executionDate', INTERVAL 1 DAY)
                ");

				DB::statement("
					INSERT INTO cm_energy_account_logs (notes, energy_account_id, current_status, customer_id, reference_id, cancellation_id, created_at)
					SELECT
						'CRON execution',
						cea.id,
						7,
						cea.customer_id,
						cea.reference_id,
						ceac.id,
						'$executionDate'
					FROM cm_energy_account_cancellation ceac
					JOIN cm_energy_accounts cea ON cea.plank_energy_account_id = ceac.plank_energy_account_id
					WHERE ceac.cancellation_date = DATE_SUB('$executionDate', INTERVAL 1 DAY)
					AND NOT EXISTS (SELECT 1 FROM cm_energy_account_logs l WHERE l.energy_account_id = cea.id AND l.current_status = 7)
					GROUP BY ceac.plank_energy_account_id
                ");

                // $this->updateEnergyStatus();

            }, 2);
        }
        catch (\Illuminate\Database\QueryException $ex) {
            if(strpos($ex->getMessage(), 'Lock wait timeout exceeded') === false && strpos($ex->getMessage(), 'Deadlock found') === false) {
                throw $ex;
            }
        }
		finally {
			$this->info("Done setting energy account status");
			$this->info('Time ended - ' . Carbon::now());
		}
    }

    public function updateEnergyStatus() {
        $sql = "
            SELECT
                cea.id, cea.created_at, c.memberid customer_id, c.fname, c.lname, app.reference_id, cea.plank_energy_account_id, cea.remarks,
                app.created_date approvedDate,
                flow.created_date flowingDate,
                pendApp.created_date pendingApproval,
                pendCon.created_date pendingConfirmation,
                flowPend.created_date flowingPending,
                cancel.created_date cancel, cea.status, 
                IF(cancel.created_date IS NOT NULL, 7, 6) correct_status,
                CONCAT('UPDATE cm_energy_accounts SET status = ', IF(cancel.created_date IS NOT NULL, 7, 6), ' WHERE status = ', cea.status, ' AND id = ', cea.id, ';') AS update_query
            FROM cm_energy_accounts cea
            LEFT JOIN cm_energy_account_logs app ON app.energy_account_id = cea.id AND app.current_status = 4
            LEFT JOIN cm_energy_account_logs flow ON flow.energy_account_id = cea.id AND flow.current_status = 5
            LEFT JOIN cm_energy_account_logs pendApp ON pendApp.energy_account_id = cea.id AND pendApp.current_status = 2
            LEFT JOIN cm_energy_account_logs pendCon ON pendCon.energy_account_id = cea.id AND pendCon.current_status = 1
            LEFT JOIN cm_energy_account_logs flowPend ON flowPend.energy_account_id = cea.id AND flowPend.current_status = 6
            LEFT JOIN cm_energy_account_logs cancel ON cancel.energy_account_id = cea.id AND cancel.current_status = 7
            LEFT JOIN customers c ON c.id = cea.customer_id
            WHERE cea.status = 5 AND (SELECT ceasl.status_type FROM cm_energy_account_status_logs ceasl WHERE ceasl.plank_energy_account_id = cea.plank_energy_account_id ORDER BY ceasl.created_at DESC LIMIT 1) = 7
            HAVING flowingPending IS NOT NULL OR cancel IS NOT NULL;
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->info("Number of accounts to be updated:".count($results));
        if(count($results) > 0) {

            foreach($results as $account) {
                $sql1 = $account['update_query'];
                $stmt1 = $this->db->prepare($sql1);
                $stmt1->execute();
            }
        }
    }
}
