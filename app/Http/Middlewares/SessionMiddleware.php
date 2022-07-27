<?php

declare(strict_types=1);

namespace App\Http\Middlewares;

use App\Helpers\Session;

class SessionMiddleware
{
    public function __invoke(): void
    {
        $GLOBALS[Session::FLASH_KEY] = Session::get(Session::FLASH_KEY);
        
        Session::clearFlash();
    }
}
