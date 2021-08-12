<?php


namespace App\Http\Controllers\Common;


use App\Http\Controllers\Controller;
use Commissions\Contracts\Repositories\CountryRepositoryInterface;

class CountriesController extends Controller
{
    protected $repository;

    public function __construct(CountryRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        return response()->json(
            $this->repository->all()
        );
    }
}