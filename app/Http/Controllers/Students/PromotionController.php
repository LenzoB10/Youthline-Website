<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\PromoteStudentsRequest;
use App\Services\Students\Promotion\PromotionExecutor;
use App\Services\Students\Promotion\PromotionPlanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:change student status')]
class PromotionController extends Controller
{
    public function index(PromotionPlanner $planner): Response
    {
        $plan = $planner->plan();

        return Inertia::render('Students/Promote', [...$plan, 'year' => $plan['year']?->name]);
    }

    public function store(PromoteStudentsRequest $request, PromotionExecutor $executor): RedirectResponse
    {
        $result = $executor->apply($request->decisions());

        return redirect()->route('students.promote.index')->with('success', $result->summary());
    }
}
