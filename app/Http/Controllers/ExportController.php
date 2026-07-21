<?php

namespace App\Http\Controllers;

use App\Models\CrmData;
use App\Models\Invoice;
use App\Models\RawDataExport;
use App\Models\Student;
use App\Services\StudentDetailService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private function csv(string $filename, array $headings, Builder $query, callable $map): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $query, $map) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headings);
            $query->chunkById(300, function ($rows) use ($out, $map) {
                foreach ($rows as $row) fputcsv($out, $map($row));
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function studentQuery(Request $r): Builder
    {
        return Student::query()->with('intakeCommissions')
            ->when($r->boolean('archived'), fn($q) => $q->archived(), fn($q) => $q->active())
            ->when($r->filled('search'), function ($q) use ($r) {
                $t='%'.$r->string('search').'%';
                $q->where(fn($s)=>$s->where('name','like',$t)->orWhere('client_id_crm','like',$t)->orWhere('client_id_invoice','like',$t)->orWhere('contact_id','like',$t)->orWhere('product_name','like',$t));
            })->when($r->filled('partner'),fn($q)=>$q->where('partner_name',$r->partner))
            ->when($r->filled('branch'),fn($q)=>$q->where('branch',$r->branch))
            ->when($r->filled('intake'),fn($q)=>$q->where('applied_intake_date',$r->intake))
            ->when($r->filled('course'),fn($q)=>$q->where('product_name','like','%'.$r->course.'%'));
    }

    public function students(Request $r): StreamedResponse
    {
        $intakes=StudentDetailService::intakeColumns();
        return $this->csv('student-details-'.now()->format('Y-m-d-His').'.csv', array_merge(['Branch','Applied Intake Date','Contact ID','Sub Agent Name','Super Agent Name','Partner Name','Client ID-CRM','Client ID-Invoice','Name','Product Name','Start Date','End Date','Course Duration','Fee Total','Credit Fee','Bonus Due','Paid Fee','Paid Bonus','Remaining Fee','Remaining Bonus','Fee Adjustment','Bonus Adjustment','HO Remarks','Branch Remarks'],$intakes), $this->studentQuery($r)->orderBy('id'), function($s) use($intakes){
            $row=[$s->branch,$s->applied_intake_date,$s->contact_id,$s->sub_agent_name,$s->super_agent_name,$s->partner_name,$s->client_id_crm,$s->client_id_invoice,$s->name,$s->product_name,optional($s->start_date)->format('Y-m-d'),optional($s->end_date)->format('Y-m-d'),$s->course_duration,$s->fee_total,$s->credit_fee,$s->bonus_due,$s->paid_fee,$s->paid_bonus,$s->remaining_fee,$s->remaining_bonus,$s->fee_adjustment,$s->bonus_adjustment,$s->ho_remarks,$s->branch_remarks];
            foreach($intakes as $i)$row[]=$s->receivedFor($i); return $row;
        });
    }

    public function formula(Request $r): StreamedResponse
    {
        return $this->csv('student-details-formula-'.now()->format('Y-m-d-His').'.csv',['Branch','Applied Intake','Contact ID','Partner Name','Client ID','Name','Course','Start Date','End Date','Course Duration','Fee Total','Credit Fee','Paid Fee','Remaining Fee'], $this->studentQuery($r)->orderBy('id'), fn($s)=>[$s->branch,$s->applied_intake_date,$s->contact_id,$s->partner_name,$s->client_id_crm,$s->name,$s->product_name,optional($s->start_date)->format('Y-m-d'),optional($s->end_date)->format('Y-m-d'),$s->course_duration,$s->fee_total,$s->credit_fee,$s->paid_fee,$s->remaining_fee]);
    }

    public function invoices(Request $r): StreamedResponse
    {
        $q=Invoice::query()->when($r->filled('search'),function($q)use($r){$t='%'.$r->string('search').'%';$q->where(fn($s)=>$s->where('name','like',$t)->orWhere('sid','like',$t)->orWhere('application_id','like',$t)->orWhere('po_number','like',$t));})
          ->when($r->filled('partner'),fn($q)=>$q->where('provider_name',$r->partner))->when($r->filled('branch'),fn($q)=>$q->where('branch',$r->branch))->when($r->filled('intake'),fn($q)=>$q->where('commission_intake',$r->intake))->when($r->filled('course'),fn($q)=>$q->where('course',$r->course));
        return $this->csv('invoice-'.now()->format('Y-m-d-His').'.csv',['Application ID','Branch','Subagent','Provider','SID','Name','Course','Start Date','End Date','Commission Intake','Total Fee','Rate','Commission','Discount','Net Commission','Royalty Rate','Bonus','EEVS HO','Branch Commission','Branch Bonus','Remarks','PO Number'],$q->orderBy('id'),fn($x)=>[$x->application_id,$x->branch,$x->subagent,$x->provider_name,$x->sid,$x->name,$x->course,optional($x->start_date)->format('Y-m-d'),optional($x->end_date)->format('Y-m-d'),$x->commission_intake,$x->total_fee,$x->rate,$x->commission,$x->discount,$x->net_commission,$x->royalty_rate,$x->bonus,$x->eevs_ho,$x->branch_commission,$x->branch_bonus,$x->remarks,$x->po_number]);
    }

    public function crm(Request $r): StreamedResponse
    {
        $q=CrmData::query()->when($r->filled('search'),function($q)use($r){$t='%'.$r->string('search').'%';$q->where(fn($s)=>$s->where('name','like',$t)->orWhere('client_id','like',$t)->orWhere('contact_id','like',$t));})->when($r->filled('partner'),fn($q)=>$q->where('partner_name',$r->partner))->when($r->filled('branch'),fn($q)=>$q->where('branch',$r->branch))->when($r->filled('intake'),fn($q)=>$q->where('applied_intake_date',$r->intake))->when($r->filled('course'),fn($q)=>$q->where('product_name',$r->course))->when($r->filled('status'),fn($q)=>$q->where('status',$r->status));
        return $this->csv('crm-data-'.now()->format('Y-m-d-His').'.csv',['Branch','Applied Intake','Contact ID','Sub Agent','Super Agent','Partner','Client ID','Name','Product','Start','End','Fee Total','Application ID','Status','Workflow','Stage'],$q->orderBy('id'),fn($x)=>[$x->branch,$x->applied_intake_date,$x->contact_id,$x->sub_agent_name,$x->super_agent_name,$x->partner_name,$x->client_id,$x->name,$x->product_name,optional($x->start_date)->format('Y-m-d'),optional($x->end_date)->format('Y-m-d'),$x->fee_total,$x->application_id,$x->status,$x->workflow,$x->current_stage]);
    }

    public function raw(Request $r): StreamedResponse
    {
        $q=RawDataExport::query()->when($r->filled('search'),function($q)use($r){$t='%'.$r->string('search').'%';$q->where(fn($s)=>$s->where('client_first_name','like',$t)->orWhere('client_last_name','like',$t)->orWhere('client_id','like',$t)->orWhere('application_id','like',$t));})->when($r->filled('partner'),fn($q)=>$q->where('partner_name',$r->partner))->when($r->filled('branch'),fn($q)=>$q->where('branch',$r->branch))->when($r->filled('intake'),fn($q)=>$q->where('intake_date',$r->intake))->when($r->filled('course'),fn($q)=>$q->where('product_name',$r->course));
        return $this->csv('raw-data-export-'.now()->format('Y-m-d-His').'.csv',['Added Date','Application ID','Deal ID','Deal Name','Contact ID','Client Name','Client ID','Branch','Partner','Product','Intake','Start','End','Stage','Status','Fee Total'],$q->orderBy('id'),fn($x)=>[optional($x->added_date)->format('Y-m-d'),$x->application_id,$x->deal_id,$x->deal_name,$x->contact_id,trim($x->client_first_name.' '.$x->client_last_name),$x->client_id,$x->branch,$x->partner_name,$x->product_name,$x->intake_date,optional($x->start_date)->format('Y-m-d'),optional($x->end_date)->format('Y-m-d'),$x->current_stage,$x->status,$x->fee_total]);
    }
}
