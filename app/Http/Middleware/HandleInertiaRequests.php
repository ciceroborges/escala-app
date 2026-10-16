<?php

namespace App\Http\Middleware;

use App\Support\CurrentMinistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        // closures: o ministério só é resolvido depois, no middleware da rota
        $current = fn () => app(CurrentMinistry::class);

        return [
            ...parent::share($request),
            'auth' => fn () => [
                'user' => $request->user()?->only('id', 'name', 'email', 'avatar_url'),
            ],
            'ministry' => fn () => $current()->ministry()?->only('id', 'name', 'church_name'),
            'member' => fn () => $current()->member()
                ? ['id' => $current()->member()->id, 'name' => $current()->member()->name, 'role' => $current()->member()->role->value]
                : null,
            'can' => fn () => $current()->member() ? [
                'admin' => Gate::allows('admin'),
                'manageSchedule' => Gate::allows('manage-schedule'),
                'manageRepertoire' => Gate::allows('manage-repertoire'),
                'viewFullSchedule' => Gate::allows('view-full-schedule'),
            ] : (object) [],
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
