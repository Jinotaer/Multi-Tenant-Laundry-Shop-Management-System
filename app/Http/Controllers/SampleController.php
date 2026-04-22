<?php

namespace App\Http\Controllers;

use App\Models\sample;
use App\Http\Requests\StoresampleRequest;
use App\Http\Requests\UpdatesampleRequest;

class SampleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoresampleRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(sample $sample)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(sample $sample)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatesampleRequest $request, sample $sample)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(sample $sample)
    {
        //
    }
}
