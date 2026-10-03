<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $logName = $request->string('log_name')->trim()->toString();
        $userId = $request->input('user_id');

        $query = Activity::query()
            ->with(['causer'])
            ->latest('id');

        if ($logName !== '') {
            $query->where('log_name', $logName);
        }

        if ($userId !== null && $userId !== '') {
            $query->where('causer_id', (int) $userId)
                ->where('causer_type', User::class);
        }

        $activities = $query->paginate(25)->withQueryString();

        $logNames = Activity::query()
            ->select('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        return view('audit.index', [
            'activities' => $activities,
            'logNames' => $logNames,
            'users' => $users,
            'filters' => [
                'log_name' => $logName,
                'user_id' => $userId,
            ],
        ]);
    }
}
