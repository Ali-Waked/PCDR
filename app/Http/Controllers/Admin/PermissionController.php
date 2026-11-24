<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

/**
 * Backward-compatible bookmark target only.
 *
 * Permission definitions are code-owned and read-only in the dashboard. All
 * create, update, and delete endpoints have been removed.
 */
final class PermissionController extends Controller
{
    public function index(string $locale): RedirectResponse
    {
        return redirect()->route('admin.access.index', ['locale' => $locale]);
    }
}
