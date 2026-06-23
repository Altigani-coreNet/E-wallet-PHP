<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceFee;
use Yajra\DataTables\Facades\DataTables;

class MerchantServiceFeeController extends Controller
{
    public function index()
    {
        $merchant = auth()->user()->merchant;
        return view('merchant.service-fees.index', [
            'merchant' => $merchant
        ]);
    }

    public function data(Request $request)
    {
        $query = ServiceFee::query();

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
        }

        // Type filter
        if ($request->has('type') && !empty($request->type)) {
            $query->where('type', $request->type);
        }

        // Date range filter
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $serviceFees = $query->orderBy('id', 'desc')->get();

        return DataTables::of($serviceFees)
            ->addColumn('record_select', function ($serviceFee) {
                return '<div class="form-check form-check-sm form-check-custom form-check-solid">
                            <input class="form-check-input record__select" type="checkbox" value="' . $serviceFee->id . '" />
                        </div>';
            })
            ->addColumn('type', function ($serviceFee) {
                return '<span class="badge badge-light-primary">' . ucfirst($serviceFee->type) . '</span>';
            })
            ->addColumn('fees', function ($serviceFee) {
                return number_format($serviceFee->fees, 2);
            })
            ->addColumn('created_at', function ($serviceFee) {
                return $serviceFee->created_at->format('M d, Y');
            })
            ->addColumn('actions', function ($serviceFee) {
                $actions = '<div class="d-flex justify-content-end flex-shrink-0">';
                
                $actions .= '<a href="' . route('admin.service-fees.show', $serviceFee) . '" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" data-bs-toggle="tooltip" title="View">';
                $actions .= '<i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>';
                $actions .= '</a>';
                
                $actions .= '<a href="' . route('admin.service-fees.edit', $serviceFee) . '" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" data-bs-toggle="tooltip" title="Edit">';
                $actions .= '<i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span class="path2"></span></i>';
                $actions .= '</a>';
                
                $actions .= '<form action="' . route('admin.service-fees.destroy', $serviceFee) . '" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this service fee?\')">';
                $actions .= csrf_field();
                $actions .= method_field('DELETE');
                $actions .= '<button type="submit" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm" data-bs-toggle="tooltip" title="Delete">';
                $actions .= '<i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>';
                $actions .= '</button>';
                $actions .= '</form>';
                
                $actions .= '</div>';
                return $actions;
            })
            ->rawColumns(['record_select', 'type', 'actions'])
            ->make(true);
    }

    public function show($id)
    {
        $merchant = auth()->user()->merchant;
        $serviceFee = ServiceFee::where('merchant_id', $merchant->id)
            ->findOrFail($id);

        return view('merchant.service-fees.show', compact('serviceFee'));
    }
}
