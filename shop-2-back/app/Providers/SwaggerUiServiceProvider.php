<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;

class SwaggerUiServiceProvider
{
    public function boot(): void
    {
        Gate::define('viewSwaggerUI', function ($user = null) {
            return in_array(optional($user)->email, [
                //
            ]);
        });

        $this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);
    }
}
