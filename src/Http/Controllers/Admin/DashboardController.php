<?php

namespace Karabin\Fabriq\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends AdminController
{
    public function show(Request $request): Response|JsonResponse
    {
        if ($response = $this->jsonGuard($request)) {
            return $response;
        }

        return Inertia::render('Admin/Dashboard/Index', [
            'pageTitle' => 'Dashboard',
            'summary' => [
                [
                    'label' => 'Runtime',
                    'value' => 'Inertia + Vue 3 + TypeScript',
                ],
                [
                    'label' => 'Current path',
                    'value' => '/admin/dashboard',
                ],
                [
                    'label' => 'Logged in as',
                    'value' => (string) $request->user()?->email,
                ],
            ],
        ]);
    }
}
