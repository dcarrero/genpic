<?php

namespace App\Http\Controllers;

class LegalController extends Controller
{
    public function legal()
    {
        return view('legal.notice');
    }

    public function privacy()
    {
        return view('legal.privacy');
    }

    public function terms()
    {
        return view('legal.terms');
    }

    public function cookies()
    {
        return view('legal.cookies');
    }
}
