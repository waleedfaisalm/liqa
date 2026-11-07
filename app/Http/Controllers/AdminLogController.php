<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminLogController extends Controller
{
    public function index(Request $request): View
    {
        $action = array_key_exists((string) $request->query('action'), AdminLog::ACTIONS) ? $request->query('action') : null;
        $adminId = (int) $request->query('admin') ?: null;

        $logs = AdminLog::query()
            ->when($action, fn ($q) => $q->where('action', $action))
            ->when($adminId, fn ($q) => $q->where('admin_id', $adminId))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $admins = User::where('is_admin', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.logs', compact('logs', 'action', 'adminId', 'admins'));
    }
}
