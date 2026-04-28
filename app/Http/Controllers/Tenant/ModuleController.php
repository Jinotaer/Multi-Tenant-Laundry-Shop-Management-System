<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ModuleRequest;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ModuleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): string
    {
        return 'THIS IS MODULE INDEX';
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): JsonResponse
    {
        return response()->json([
            'message' => 'Module create form is not implemented.',
        ], Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ModuleRequest $request): JsonResponse
    {
        $module = Module::create($request->validated());

        return response()->json($module, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Module $module): JsonResponse
    {
        return response()->json($module);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Module $module): JsonResponse
    {
        return response()->json([
            'message' => 'Module edit form is not implemented.',
            'data' => $module,
        ], Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ModuleRequest $request, Module $module): JsonResponse
    {
        $module->update($request->validated());

        return response()->json($module);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Module $module): Response
    {
        $module->delete();

        return response()->noContent();
    }
}
