<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LogController extends Controller
{
    public function index()
    {
        return view('logs.index');
    }

    public function data()
    {
        // dd(request()->get("loggable_type"), request()->get("loggable_id"));
        $query = Log::with("User:id,name", "loggable:id")
            ->when(request()->has("loggable_id"), function ($query) {
                return $query->where('loggable_id', request()->get("loggable_id"));
            })
            ->when(request()->has("loggable_type"), function ($q) {
                return $q->where('loggable_type', request()->get("loggable_type"));
            })
            ->when(request()->has("except_types"), function ($q) {
                return $q->whereNotIn("action", request()->get("except_types"));
            })
            ->when(request()->has("action"), function ($q) {
                return $q->where("action", request()->get("action"));
            });

        return DataTables::of($query)
            ->addColumn("loggable_type", fn($item) => $item->getLoggableType())
            ->addColumn("text", fn($item) => $item->getTextAttribute())
            ->editColumn('action', fn($item) => $item->getLabelWithSpan())
            ->editColumn('time', fn($item) => $item->created_at->format('Y-m-d H:i:s'))
            ->addColumn('message', function($item) {
                if (!$item->metadata) return '';
                
                $metadata = is_array($item->metadata) ? $item->metadata : json_decode($item->metadata, true);
                
                if (is_array($metadata) && isset($metadata['message'])) {
                    return $metadata['message'];
                }
                
                return '';
            })
            ->addColumn("actions", fn($item) => view('log.data_table.logable', compact("item")))
            ->rawColumns(['action'])
            ->toJson();
    }
}
