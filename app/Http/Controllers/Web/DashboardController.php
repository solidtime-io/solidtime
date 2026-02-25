<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('Dashboard');
    }
}
