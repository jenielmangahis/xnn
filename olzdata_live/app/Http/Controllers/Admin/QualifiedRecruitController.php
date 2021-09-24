<?php


namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use Commissions\Admin\QualifiedRecruit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QualifiedRecruitController extends Controller
{

    protected $qualified_recruit;

    public function __construct(QualifiedRecruit $qualified_recruit)
    {
        $this->qualified_recruit = $qualified_recruit;
    }

    public function qualified_recruits(Request $request)
    {
        return response()->json(
            $this->qualified_recruit->getQualifiedRecruits($request->all())
        );
    }

    public function downloadQualifiedRecruits(Request $request)
    {
        sleep(2);
        return response()->json([
            'link' => $this->qualified_recruit->getQualifiedRecruitsDownloadLink($request->all())
        ]);
    }

    public function userRepresentativeList($user_id)
    {
        try
        {
            return response()->json($this->qualified_recruit->getUserRepresentativeList($request->all()));
        }
        catch (Exception $ex)
        {
            return response()->json(['error' => ['message' => $ex->getMessage(), 'type' => 'danger']], 400);
        }
    }
}