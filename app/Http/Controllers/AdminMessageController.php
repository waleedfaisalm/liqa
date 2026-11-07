<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMessageController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'unread' ? 'unread' : 'all';

        $messages = ContactMessage::query()
            ->when($tab === 'unread', fn ($q) => $q->whereNull('read_at'))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.messages', [
            'messages' => $messages,
            'tab' => $tab,
            'unread' => ContactMessage::whereNull('read_at')->count(),
            'total' => ContactMessage::count(),
        ]);
    }

    public function toggle(ContactMessage $message): RedirectResponse
    {
        $message->update(['read_at' => $message->isUnread() ? now() : null]);

        return back();
    }

    public function destroy(Request $request, ContactMessage $message): RedirectResponse
    {
        AdminLog::record($request->user(), 'message.deleted', null, null, $message->name, 'الموضوع: '.$message->subject);
        $message->delete();

        return back()->with('success', 'تم حذف الرسالة.');
    }
}
