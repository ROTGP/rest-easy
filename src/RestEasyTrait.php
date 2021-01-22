<?php

namespace ROTGP\RestEasy;

use Illuminate\Http\Request;

use ROTGP\RestEasy\Traits\CoreTrait;

trait RestEasyTrait
{  
    use CoreTrait;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return $this->_index($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  int|string  $resource
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $resource)
    {
        return $this->_show($request, $resource);
    }

     /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int|string  $resource
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $resource)
    {
        return $this->_update($request, $resource);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        return $this->_store($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int|string  $resource
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $resource)
    {
        return $this->_destroy($request, $resource);
    }
}
