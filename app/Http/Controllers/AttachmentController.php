<?php

namespace App\Http\Controllers;

use App\Models\Attachments;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class AttachmentController extends Controller
{
    public function data()
    {
        $query = Attachments::when(\request()->has("attachable_id"), function ($query) {
            return $query->where('attachable_id', request()->get("attachable_id"));
        })
            ->when(\request()->has("attachable_type"), function ($q) {
                return $q->where('attachable_type', request()->get("attachable_type"));
            })
            ->when(\request()->has("except_types"), function ($q) {
                return $q->whereNotIn("url_type", request()->get("except_types"));
            })
            ->when(\request()->has("url_type"), function ($q) {
                $url_type = request()->get("url_type");
                // dd($url_type);
                if (is_string($url_type)) {
                    $url_type = [$url_type];
                }

                return $q->whereIn("url_type", $url_type);
            })
            ->when(\request()->has("merchant_id"), function ($q) {
                return $q->where('attachable_id', request()->get("merchant_id"))
                         ->where('attachable_type', 'App\\Models\\Merchant');
            });

        if (request()->get('attachable_type') === Profile::class && request()->has('attachable_id') && request()->get('url_type') !== 'subscription_contract') {
            $profile = Profile::With("User:id,profile_image")->find(request()->get('attachable_id'));
            if ($profile && $profile->User->profile_image) {
                $logoData = [
                    'id' => 'logo_' . $profile->id,
                    'attachment' => $profile->User->getProfileImageApi(),
                    'file_name' => 'Profile Logo',
                    'url_type' => 'logo',
                    'type' => 'image',
                    "url" => $profile->User->getProfileImageApi(),
                    'actions' => '',
                    'created_at' => now(),
                    "attachable_type" => Profile::class,
                    "attachable_id" => $profile->user_id,
                ];
                $query = $query->get()->push((object)$logoData);
            }
        }

        $dataTable = DataTables::of($query)
            ->addColumn('record_select', function($attachment) {
                return view('attachments.data_table.record_select', ['id' => $attachment->id])->render();
            })
            ->addColumn('attachment', fn($attachment) => view('attachments.data_table.attachment', compact('attachment'))->render())
            ->addColumn('file_name', fn($attachment) => $attachment->url)
            ->addColumn('url_type', fn($attachment) => $attachment->url_type ? __('translation.' . $attachment->url_type) : "")
            ->addColumn('type', fn($attachment) => $attachment->type ? __('translation.' . $attachment->type) : "")
            ->addColumn('actions', function ($attachment) {
                return view('attachments.data_table.actions', [
                    'id' => $attachment->id,
                    'url' => $attachment->url
                ]);
            })
            ->editColumn('created_at', fn($attachment) => $attachment->created_at?->format('Y-m-d H:i') ?? '')
            ->rawColumns(['record_select', "attachment", 'attachable', 'actions', 'file_path']);

        return $dataTable->toJson();
    }

    public function show($id)
    {
        $file_name = Attachments::find($id)->url;
        if (Str::startsWith($file_name, 'mec\\'))
            $file_name = Str::replaceFirst('mec\\', 'mec/', $file_name);
        return response()->file(public_path($file_name));
    }

    public function download($id)
    {
        $file_name = Attachments::find($id)->url;
        if (Str::startsWith($file_name, 'mec\\'))
            $file_name = Str::replaceFirst('mec\\', 'mec/', $file_name);
        $pathToFile = public_path($file_name);

        return response()->download($pathToFile);
    }

    public function deleteImageWithFilePath(Request $request)
    {
        $request->validate([
            "filePath" => "required",
        ]);
        try {
            DB::beginTransaction();
            $parsedUrl = parse_url($request->filePath);

            $pathWithoutSlash = ltrim($parsedUrl['path'], '/');
            $attach = Attachments::where("url", $pathWithoutSlash)->firstOrFail();

            $pathToFile = public_path($attach->url);

            // Check if the file exists, then delete it
            if (File::exists($pathToFile)) {
                File::delete($pathToFile);
            }

            $attach->delete();

            DB::commit();

            return response()->json(["message" => "deleted successfully"]);

        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json(["error" => $exception->getMessage()], 500);
        }
    }
}