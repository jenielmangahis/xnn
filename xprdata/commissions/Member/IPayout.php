<?php


namespace Commissions\Member;

use App\IPayoutUser;
use Commissions\Exceptions\IPayoutException;
use Illuminate\Support\Facades\DB;

class IPayout
{
    const DEBUG = true;

    protected $ipayout;

    public function __construct(\Commissions\Clients\IPayout $payout)
    {
        $this->ipayout = $payout;
    }

    public function createUser($id, $data)
    {
        DB::transaction(function() use ($id, $data){
            $data['user_id'] = $id;
            $data['status'] = 'success';

            $user = IPayoutUser::create($data);
            $response = $this->ipayout->registerUser($user);

            $data = [
                'response' => json_encode($response)
            ];

            if(array_key_exists('TransactionRefID', $response)) {
                $data['reference_id'] = $response['TransactionRefID'];
            }

            IPayoutUser::find($id)->update($data);
        });

        return $this->getUser($id);
    }

    public function getUser($id)
    {
        // IPayoutUser::where('user_id', 20)->delete();
        $user = DB::table('users AS u')
            ->leftJoin('cm_ipayout_users AS iu', 'iu.user_id', '=', 'u.id')
            ->selectRaw("
                u.id AS user_id,
                IFNULL(iu.first_name, u.fname) first_name,
                IFNULL(iu.last_name, u.lname) last_name,
                IFNULL(iu.username, u.site) username,
                IFNULL(iu.email, u.email) email,
                iu.date_of_birth,
                iu.company_name,
                iu.address_1,
                iu.address_2,
                iu.city,
                IFNULL(iu.state, '') state,
                iu.zip_code,
                iu.`status`,
                IFNULL(iu.country_code, '') country_code
            ")
            ->where('u.id', $id)
            ->first();

        if($user != null)
        {
            $user->invitation_link = $user->status ? config('services.ipayout.member_portal_url') : null;
        }

        return $user;
    }
}