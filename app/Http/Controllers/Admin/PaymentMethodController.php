<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use HasinHayder\Tyro\Support\TyroAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(): View
    {
        $paymentMethods = PaymentMethod::ordered()->get();

        return view('admin.payment-methods.index', compact('paymentMethods'));
    }

    public function create(): View
    {
        return view('admin.payment-methods.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency_type' => 'required|in:BDT,USD',
            'icon' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:500',
            'payment_instructions' => 'nullable|string|max:2000',
            'account_holder_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'routing_number' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:50',
            'qr_code_image_path' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        PaymentMethod::create($validated);

        TyroAudit::log('payment_method.created', null, null, [
            'name' => $validated['name'],
            'currency_type' => $validated['currency_type'],
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'Payment method "'.$validated['name'].'" created successfully.');
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('admin.payment-methods.edit', ['paymentMethod' => $paymentMethod]);
    }

    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'currency_type' => 'required|in:BDT,USD',
            'icon' => 'nullable|string|max:10',
            'description' => 'nullable|string|max:500',
            'payment_instructions' => 'nullable|string|max:2000',
            'account_holder_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'routing_number' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:50',
            'qr_code_image_path' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $paymentMethod->update($validated);

        TyroAudit::log('payment_method.updated', null, null, [
            'name' => $validated['name'],
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'Payment method "'.$validated['name'].'" updated successfully.');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $name = $paymentMethod->name;
        $paymentMethod->delete();

        TyroAudit::log('payment_method.deleted', null, null, [
            'name' => $name,
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'Payment method "'.$name.'" deleted successfully.');
    }

    public function toggle(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->update(['is_active' => ! $paymentMethod->is_active]);

        $status = $paymentMethod->is_active ? 'enabled' : 'disabled';

        return redirect()
            ->route('admin.payment-methods.index')
            ->with('success', 'Payment method "'.$paymentMethod->name.'" '.$status.' successfully.');
    }
}
