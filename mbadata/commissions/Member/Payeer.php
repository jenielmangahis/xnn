<?php


namespace Commissions\Member;

use Commissions\Clients\Payeer as PayeerClient;
use Commissions\Exceptions\AlertException;
use Exception;
use App\User;
use App\PayeerUser;
use Illuminate\Support\Facades\DB;


class Payeer
{
    const DEBUG = false; // do not set to true

    protected $payeer_client;

    public function __construct(PayeerClient $payeer_client)
    {
        $this->payeer_client = $payeer_client;

        if(static::DEBUG)
        {
            $this->payeer_client = new PayeerClient('123');
        }
    }

    public function createUser($id, $data)
    {
        
        try {
            $user = User::findOrFail($id);

            DB::transaction(function() use ($user, $id, $data) {
                
                $payeerUser = PayeerUser::find($id);

                $email = $data['email'];
                $account_number = $data['account_number'];
                $user_id = $user->id;

                if($payeerUser != null) throw new Exception("You already have an account");

                if(!$this->payeer_client->isAccountExist($account_number)) throw new Exception("Account Number does not exist!");
                
                $payeerUser = new PayeerUser();
                $payeerUser->user_id = $user->id;
                $payeerUser->account_number = $account_number;
                $payeerUser->email = $email;
                // $payeerUser->response = json_encode($result);
                $payeerUser->save();

            });

            return $this->getUser($id);
        } catch(Exception $ex) {

            $message = $ex->getMessage();

            if(strpos($message, ' Integrity constraint violation: 1062 Duplicate entry ') !== false)
            {
                $message = "The account you provided is already registered with another user.";
            }

            throw new AlertException($message);
        }
    }

    public function getUser($id)
    {
        // PayeerUser::find(20)->delete();
        $user = DB::table('users AS u')
            ->leftJoin('cm_payeer_users AS pu', 'pu.user_id', '=', 'u.id')
            ->selectRaw("
                u.id AS user_id,
                u.fname AS first_name,
                u.lname AS last_name,
                IFNULL(pu.email, u.email) AS email,
                pu.account_number AS account_number,
                IF(IFNULL(pu.user_id, 0), 1, NULL) AS is_registered
            ")
            ->where('u.id', $id)
            ->first();

        return $user;
    }

    public function updateUser($id, $data)
    {

        DB::transaction(function() use ($id, $data) {
            
            $payeerUser = PayeerUser::find($id);
            $new_account_number = $data['account_number'];

            if($payeerUser == null) throw new Exception("You don't have an account");

            if($payeerUser->account_number != $new_account_number) {
                if(!$this->payeer_client->isAccountExist($new_account_number)) throw new Exception("Account Number does not exist!");

                $doneby = $id.' update payeer account number';
                $value = "Old account number:".$payeerUser->account_number.", New:".$new_account_number;

                DB::table('users_mods')->insert([
                    'userid' => $id,
                    'doneby' => $doneby,
                    'vals' => $value,
                ]);
                
                $payeerUser->account_number = $new_account_number;
                $payeerUser->save();
            }
            else {
                throw new Exception("New Account Number is the same with the Old Account Number!");
            }
        });

        return $this->getUser($id);
    }
}