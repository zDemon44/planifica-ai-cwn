<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('components.layouts.aplicacion', function (View $view): void {
            $usuario = auth()->user();
            $view->with('rolCuenta', User::ROLES[$usuario?->role] ?? $usuario?->role);
        });
        Gate::define('gestionar-docencia', fn (User $user): bool => $user->esAdministrador());
        Gate::define('gestionar-usuarios', fn (User $user): bool => $user->activo && $user->role === 'SUPERADMIN');
        Gate::define('portal-docente', fn (User $user): bool => $user->esDocente());
        Gate::define('ver-seguimiento-curricular', fn (User $user): bool => $user->esAdministrador() || $user->esDocente());
        Gate::define('ver-cronograma', fn (User $user): bool => $user->esAdministrador() || $user->esDocente());
    }
}
