<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TrackingController extends Controller
{
    public function open(string $trackingId): Response
    {
        //
    }

    public function click(string $trackingId): RedirectResponse
    {
        //
    }
}
