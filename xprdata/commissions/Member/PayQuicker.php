<?php


namespace Commissions\Member;

use Commissions\Clients\PayQuicker as PayQuickerClient;
use Commissions\Exceptions\AlertException;
use Exception;
use App\User;
use App\PayQuickerUser;
use Illuminate\Support\Facades\DB;


class PayQuicker
{
    const DEBUG = false; // do not set to true

    protected $pay_quicker_client;

    public function __construct(PayQuickerClient $pay_quicker_client)
    {
        $this->pay_quicker_client = $pay_quicker_client;

        if(static::DEBUG)
        {
            $this->pay_quicker_client = new PayQuickerClient(
                "65c8dcab384a43fa9452c2dbcb51aa91072faeca677946c680bb8629ca9b3f65",
                '59eb60a875324002b79c9c5033c3de1aa85ee0e404c842dea6afc43fb771fa08',
                '8d8e841e9d2741c89db9a1a9f29e24c7',
                'https://naxum-demo.mypayquicker.com'
            );
        }
    }

    public function createUser($id, $data)
    {
        try {
            $user = User::findOrFail($id);

            DB::transaction(function() use ($user, $id, $data){
                $payQuickerUser = PayQuickerUser::find($id);

                if($payQuickerUser != null) throw new Exception("You already have an account");

                $email = $data['email'];
                $user_id = $user->id;

                if(static::DEBUG) {
                    $e = explode("@", $email);
                    $email = $e[0] . "+" . time() . "@" . $e[1];

                    $user_id .= "_" . time();
                }

                $result = $this->pay_quicker_client->sendInvitation([
                    'userCompanyAssignedUniqueKey' => $user_id,
                    'userNotificationEmailAddress' => $email,
                    'firstName' => $user->fname,
                    'lastName' => $user->lname,
                    // 'issuePlasticCard' => +$data['has_plastic_card'] ? true : false,
                    'issuePlasticCard' => false,
                    // 'notifyUser' => true
                ]);

                if(!isset($result[0]) || !isset($result[0]['invitationKey']))
                {
                    logger()->error(print_r($result, true));
                    throw new Exception("Unable to sign up. Invitation Key is missing.");
                }
                // https://naxum-demo.mypayquicker.com/Welcome?invitationId=
                $payQuickerUser = new PayQuickerUser();
                $payQuickerUser->user_id = $user->id;
                $payQuickerUser->company_assigned_key = $user_id;
                $payQuickerUser->email = $email;
                $payQuickerUser->invitation_key = $result[0]['invitationKey'];
                $payQuickerUser->has_plastic_card = +$data['has_plastic_card'];
                $payQuickerUser->response = json_encode($result);
                $payQuickerUser->save();

            });

            return $this->getUser($id);
        } catch(Exception $ex) {
            throw new AlertException($ex->getMessage());
        }
    }

    public function getUser($id)
    {
        // PayQuickerUser::find(20)->delete();
        $user = DB::table('users AS u')
            ->leftJoin('cm_payquicker_users AS pu', 'pu.user_id', '=', 'u.id')
            ->selectRaw("
                u.id AS user_id,
                IFNULL(pu.company_assigned_key, u.id) AS company_assigned_key,
                u.fname AS first_name,
                u.lname AS last_name,
                IFNULL(pu.email, u.email) AS email,
                IFNULL(pu.has_plastic_card, 0) AS has_plastic_card,
                pu.invitation_key
            ")
            ->where('u.id', $id)
            ->first();

        if($user != null)
        {
            $user->invitation_link = $user->invitation_key ? $this->pay_quicker_client->getInvitationLink($user->invitation_key) : null;
        }

        return $user;
    }
}