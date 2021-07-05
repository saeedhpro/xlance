<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRequest;
use App\Http\Resources\AssetResource;
use App\Models\Upload;
use App\Models\User;
use Carbon\Carbon;

class UploadController extends Controller
{
    public function upload(UploadRequest $request) {
        if ($request->file('file')) {
            $file = $request->file('file');
            $ext = $file->getClientOriginalExtension();
            $name = Carbon::now()->timestamp . uniqid() . Carbon::now()->timestamp . uniqid() . '.' . $ext;
            $folderName = Carbon::now()->format('Y_m_d');
            $path = 'uploads/';
//            if (!file_exists(public_path($path.$folderName))) {
//                mkdir(public_path($path.$folderName), 0775, true);
//            }
            if($p = $file->storeAs('/public/'.$path.$folderName, $name)) {
                /** @var User $user */
                $user = auth()->user();
                $upload = $user->uploads()->create([
                    'name' => $name,
                    'path' => $path.$folderName.'/'.$name,
                    'url' => url('/storage/'.$path.$folderName.'/'.$name)
                ]);
            } else {
                $upload = null;
            }
            return new AssetResource($upload);
        } else {
            return response()->json(['error' => 404, 'message' => 'فایل یافت نشد!'], 404);
        }
    }

    public function destroy(Upload $upload)
    {
        if($upload) {
            $upload->forceDelete();
            return response()->json(['success' => 200, 'message' => 'فایل حذف شد!'], 200);
        } else {
            return response()->json(['error' => 404, 'message' => 'فایل یافت نشد!'], 404);
        }
    }
}
