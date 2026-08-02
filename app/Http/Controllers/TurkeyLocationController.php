<?php

namespace App\Http\Controllers;

use App\Models\TurkeyCity;
use App\Models\TurkeyDistrict;
use App\Services\TurkeyLocationService;
use Illuminate\Http\Request;

class TurkeyLocationController extends Controller
{
    public function __construct(private TurkeyLocationService $locationService) {}

    public function cities()
    {
        $this->locationService->ensureSynced();

        return response()->json(
            TurkeyCity::query()->orderBy('name')->get(['id', 'name'])
        );
    }

    public function districts(Request $request)
    {
        $this->locationService->ensureSynced();

        $validated = $request->validate([
            'cityId' => 'required|integer|exists:turkey_cities,id',
        ]);

        return response()->json(
            TurkeyDistrict::query()
                ->where('cityId', $validated['cityId'])
                ->orderBy('name')
                ->get(['id', 'name', 'cityId'])
        );
    }
}
