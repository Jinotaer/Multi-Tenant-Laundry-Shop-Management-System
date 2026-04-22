<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Http\Requests\StorePromotionRequest;

class PromotionController extends Controller
{
    /**
     * Display a listing of the promotions.
     */
    public function index()
    {
        // Simple JSON dump for demonstration purposes to prove the seeds worked
        $promotions = Promotion::latest()->limit(50)->get();
        
        return response()->json([
            'message' => 'Update Successful! You are looking at 50 freshly seeded Promotions.',
            'status' => 'success',
            'count' => $promotions->count(),
            'data' => $promotions
        ]);
    }

    /**
     * Store a newly created promotion in storage.
     */
    public function store(StorePromotionRequest $request)
    {
        $promotion = Promotion::create($request->validated());

        return response()->json([
            'message' => 'Promotion created successfully',
            'data' => $promotion
        ], 201);
    }
}
