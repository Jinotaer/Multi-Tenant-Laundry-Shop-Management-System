<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSampleXRequest;
use App\Http\Requests\UpdateSampleXRequest;
use App\Models\SampleX;
use Illuminate\Http\JsonResponse;

class SampleXController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $records = SampleX::query()->latest()->get();

        return response()->json([
            'count' => $records->count(),
            'data' => $records,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Submit number and description to create a SampleX record.',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSampleXRequest $request): JsonResponse
    {
        $sampleX = SampleX::query()->create($request->validated());

        return response()->json($sampleX, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(SampleX $sampleX): JsonResponse
    {
        return response()->json($sampleX);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SampleX $sampleX): JsonResponse
    {
        return response()->json($sampleX);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSampleXRequest $request, SampleX $sampleX): JsonResponse
    {
        $sampleX->update($request->validated());

        return response()->json($sampleX->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SampleX $sampleX): JsonResponse
    {
        $sampleX->delete();

        return response()->json([], 204);
    }
}
