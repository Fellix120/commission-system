<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\StudentDetailService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        // One builder, reused for the page and the totals, so a filtered view
        // does not show unfiltered sums underneath it.
        $filtered = fn () => Invoice::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', $term)
                    ->orWhere('sid', 'like', $term)
                    ->orWhere('application_id', 'like', $term)
                    ->orWhere('po_number', 'like', $term));
            })
            ->when($request->filled('partner'), fn ($q) => $q->where('provider_name', $request->partner))
            ->when($request->filled('branch'), fn ($q) => $q->where('branch', $request->branch))
            ->when($request->filled('intake'), fn ($q) => $q->where('commission_intake', $request->intake))
            ->when($request->filled('course'), fn ($q) => $q->where('course', $request->course));

        $invoices = $filtered()
            ->orderByDesc('start_date')
            ->paginate(50)
            ->withQueryString();

        $partners = Invoice::distinct()->orderBy('provider_name')->pluck('provider_name')->filter();
        $branches = Invoice::distinct()->orderBy('branch')->pluck('branch')->filter();
        $intakes = Invoice::distinct()->orderBy('commission_intake')->pluck('commission_intake')->filter();
        $courses = Invoice::distinct()->orderBy('course')->pluck('course')->filter();

        $totals = [
            'total_fee'         => (float) $filtered()->sum('total_fee'),
            'net_commission'    => (float) $filtered()->sum('net_commission'),
            'branch_commission' => (float) $filtered()->sum('branch_commission'),
            'bonus'             => (float) $filtered()->sum('bonus'),
        ];

        return view('invoices.index', compact('invoices', 'partners', 'branches', 'intakes', 'courses', 'totals'));
    }

    /**
     * Delete one invoice row.
     *
     * Paid Fee and the intake columns are summed from Invoice, so the
     * affected student is refreshed straight away -- otherwise the sheet
     * would keep showing money against a row that no longer exists.
     */
    public function destroy(Invoice $invoice, StudentDetailService $service)
    {
        $sid = $invoice->sid;
        $invoice->delete();

        if ($sid) {
            $service->refreshOne($sid);
        }

        return back()->with('status', 'Invoice deleted and the student figures refreshed.');
    }

    /**
     * Empty the Invoice tab.
     */
    public function truncate(StudentDetailService $service)
    {
        $n = Invoice::count();
        Invoice::query()->delete();

        // Every student's paid/intake figures came from Invoice, so rebuild.
        $service->refreshAll(true);

        return back()->with('status', "Deleted {$n} invoice row".($n === 1 ? '' : 's').'.');
    }
}
