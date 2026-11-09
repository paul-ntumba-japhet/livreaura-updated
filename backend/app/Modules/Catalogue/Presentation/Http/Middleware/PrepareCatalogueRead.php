<?php

namespace App\Modules\Catalogue\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PrepareCatalogueRead
{
    public function handle(Request $request, Closure $next): Response
    {
        DB::statement("SET SESSION time_zone = '+00:00'");
        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');

        return $next($request);
    }
}
