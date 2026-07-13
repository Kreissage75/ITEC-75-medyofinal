<?php

namespace App\Http\Controllers;

class SupportController extends Controller
{
    /**
     * Show the self-service support portal.
     */
    public function index()
    {
        return view('support.index');
    }
}
