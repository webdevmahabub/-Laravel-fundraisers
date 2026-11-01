<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFeeConfigurationRequest;
use App\Models\FeeConfiguration;
use HasinHayder\Tyro\Support\TyroAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FeeConfigurationController extends Controller
{
    /**
     * Show the fee configuration editor.
     */
    public function index(): View
    {
        $configurations = FeeConfiguration::orderBy('key')->get();

        return view('admin.fee-configurations.index', compact('configurations'));
    }

    /**
     * Persist updated fee values (keyed by configuration key).
     */
    public function update(UpdateFeeConfigurationRequest $request): RedirectResponse
    {
        $fees = $request->validated('fees');

        foreach ($fees as $key => $value) {
            FeeConfiguration::where('key', $key)->update(['value' => $value]);
        }

        TyroAudit::log('fee_config.updated', null, null, [
            'keys' => array_keys($fees),
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.fee-config.index')
            ->with('success', 'Fee configuration updated successfully.');
    }
}
