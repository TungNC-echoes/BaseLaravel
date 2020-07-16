<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::morphMap([
            'users' => User::class
        ]);

        Validator::extend('compare_num_len', function($attribute, $value, $parameters, $validator) {
            return eval('return '. strlen((string) $value) . (string) $parameters[0] . (int) $parameters[1] .';');
        });

        Validator::replacer('compare_num_len', function($message, $attribute, $rule, $parameters) {
            return str_replace(':compare_num_len', $parameters[1], $message);
        });
    }
}
