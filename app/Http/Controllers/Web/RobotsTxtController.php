<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use Illuminate\Http\Response;

class RobotsTxtController extends Controller
{
    public function show(): Response
    {
        $allow = config('app.robots_txt') === 'allow';

        return response("User-agent: *\n".($allow ? 'Disallow:' : 'Disallow: /')."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
