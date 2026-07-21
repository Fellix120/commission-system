<?php

namespace App\Http\Controllers;

use App\Models\CrmData;
use App\Models\RawDataExport;
use App\Services\StudentDetailService;
use Illuminate\Http\Request;

class CrmDataController extends Controller
{
    /** The "CRM Data" tab: completed-only, sorted old -> new. */
    public function index(Request $request)
    {
        $rows = CrmData::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('partner'), fn ($q) => $q->where('partner_name', $request->partner))
            ->when($request->filled('branch'), fn ($q) => $q->where('branch', $request->branch))
            ->when($request->filled('intake'), fn ($q) => $q->where('applied_intake_date', $request->intake))
            ->when($request->filled('course'), fn ($q) => $q->where('product_name', $request->course))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($sub) => $sub
                    ->where('name', 'like', $term)
                    ->orWhere('client_id', 'like', $term)
                    ->orWhere('product_name', 'like', $term));
            })
            ->orderBy('start_date')
            ->paginate(50)
            ->withQueryString();

        $statuses = CrmData::distinct()->orderBy('status')->pluck('status')->filter();
        $partners = CrmData::distinct()->orderBy('partner_name')->pluck('partner_name')->filter();
        $branches = CrmData::distinct()->orderBy('branch')->pluck('branch')->filter();
        $intakes = CrmData::distinct()->orderBy('applied_intake_date')->pluck('applied_intake_date')->filter();
        $courses = CrmData::distinct()->orderBy('product_name')->pluck('product_name')->filter();

        return view('crm.index', compact('rows', 'statuses', 'partners', 'branches', 'intakes', 'courses'));
    }

    /** The untouched "Raw Data Export" tab. */
    public function raw(Request $request)
    {
        $rows = RawDataExport::query()
            ->when($request->filled('partner'), fn ($q) => $q->where('partner_name', $request->partner))
            ->when($request->filled('branch'), fn ($q) => $q->where('branch', $request->branch))
            ->when($request->filled('intake'), fn ($q) => $q->where('intake_date', $request->intake))
            ->when($request->filled('course'), fn ($q) => $q->where('product_name', $request->course))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(fn ($sub) => $sub
                    ->where('client_first_name', 'like', $term)
                    ->orWhere('client_last_name', 'like', $term)
                    ->orWhere('application_id', 'like', $term));
            })
            ->orderByDesc('added_date')
            ->paginate(50)
            ->withQueryString();

        $partners = RawDataExport::distinct()->orderBy('partner_name')->pluck('partner_name')->filter();
        $branches = RawDataExport::distinct()->orderBy('branch')->pluck('branch')->filter();
        $intakes = RawDataExport::distinct()->orderBy('intake_date')->pluck('intake_date')->filter();
        $courses = RawDataExport::distinct()->orderBy('product_name')->pluck('product_name')->filter();

        return view('crm.raw', compact('rows', 'partners', 'branches', 'intakes', 'courses'));
    }

    /**
     * Delete one CRM row.
     *
     * CRM Data is the source Student Details is built from, so the affected
     * student is refreshed immediately. Removing the only row for a client
     * leaves a student with nothing behind it -- the formula screen will show
     * the row count as zero.
     */
    public function destroy(CrmData $crmDatum, StudentDetailService $service)
    {
        $clientId = $crmDatum->client_id;
        $crmDatum->delete();

        if ($clientId) {
            $service->refreshOne($clientId);
        }

        return back()->with('status', 'CRM row deleted and the student figures refreshed.');
    }

    /**
     * Empty the CRM Data tab.
     */
    public function truncate(StudentDetailService $service)
    {
        $n = CrmData::count();
        CrmData::query()->delete();
        $service->refreshAll(true);

        return back()->with('status', "Deleted {$n} CRM row".($n === 1 ? '' : 's').'.');
    }

    /**
     * Delete one Raw Data Export row. Nothing is derived from this tab, so
     * no refresh is needed.
     */
    public function destroyRaw(RawDataExport $rawDatum)
    {
        $rawDatum->delete();

        return back()->with('status', 'Raw data row deleted.');
    }

    /**
     * Empty the Raw Data Export tab.
     */
    public function truncateRaw()
    {
        $n = RawDataExport::count();
        RawDataExport::query()->delete();

        return back()->with('status', "Deleted {$n} raw row".($n === 1 ? '' : 's').'.');
    }
}
