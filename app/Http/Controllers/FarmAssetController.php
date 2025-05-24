<?php

namespace App\Http\Controllers;

use App\Jobs\ImportAssets;
use App\Models\Export;
use App\Models\FarmAsset;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Class FarmAssetController
 * @package App\Http\Controllers
 */
class FarmAssetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $farmAssets = FarmAsset::query();
        if (Auth::user()->role_id == User::ROLE_SUB_ADMIN) {
            $farmAssets = $farmAssets->whereIn('created_by', [Auth::id(), User::ROLE_ADMIN, User::ROLE_GOVERNMENT, User::ROLE_SYSTEM_ADMIN]);
        }
        if ($request->search) {
            $farmAssets->where(function ($query) use ($request) {
                $query->where('number_plate', 'LIKE', '%' . $request->search . '%');
            });
        }

        $farmAssets = $farmAssets->latest('id')->paginate();

        $importInfo = Export::where([
            'created_by' => Auth::id(),
            'type_id' => Export::TYPE_ASSET_IMPORT
        ])->first();

        return view('farm-asset.index', compact('farmAssets', 'importInfo'))
            ->with('i', (request()->input('page', 1) - 1) * $farmAssets->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $farmAsset = new FarmAsset();
        return view('farm-asset.create', compact('farmAsset'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate([
            'number_plate' => 'required|unique:farm_assets,number_plate',
            'mileage' => 'required',
            'type_id' => 'required',
            'condition' => 'required'
        ], [], [
            'type_id' => 'type'
        ]);

        $farmAsset = FarmAsset::create($request->all());

        return redirect()->route('farm-assets.index')
            ->with('success', 'FarmAsset created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $farmAsset = FarmAsset::find($id);

        return view('farm-asset.show', compact('farmAsset'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $farmAsset = FarmAsset::find($id);

        return view('farm-asset.edit', compact('farmAsset'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  FarmAsset $farmAsset
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, FarmAsset $farmAsset)
    {
        request()->validate([
            'number_plate' => 'required|unique:farm_assets,number_plate,' . $farmAsset->id,
            'mileage' => 'required',
            'type_id' => 'required',
            'condition' => 'required'
        ], [], [
            'type_id' => 'type'
        ]);

        $farmAsset->update($request->all());

        return redirect()->route('farm-assets.index')
            ->with('success', 'FarmAsset updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $farmAsset = FarmAsset::find($id)->delete();

        return redirect()->route('farm-assets.index')
            ->with('success', 'FarmAsset deleted successfully');
    }

    public function import(Request $request)
    {
        $rules = [
            'fileInput' => 'required|mimes:csv,txt'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            $file = $request->file('fileInput');
            $extension = $file->getClientOriginalExtension();
            $fileName = uniqid('file_', true) . '.' . $extension;
            $filePath = $file->storeAs('import', $fileName, 'public');

            Export::where(['created_by' => Auth::id(), 'type_id' => Export::TYPE_ASSET_IMPORT])->latest('id')->delete();
            $export = Export::create([
                'file_name' => $file->getClientOriginalName(),
                'type_id' => Export::TYPE_ASSET_IMPORT,
            ]);
            ImportAssets::dispatch($filePath, Auth::id(), $export->id);

            return redirect()->back()->with('success', 'Import request has been added to the queue. Please check back shortly.');
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function closeProgress(Request $request)
    {
        try {
            $response['status'] = 'NOK';
            $export = Export::where(['created_by' => Auth::id(), 'type_id' => $request->type])->latest('id')->delete();
            if ($export) {
                $response['status'] = 'OK';
            }
            return $response;
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function ImportStatus(Request $request)
    {
        try {
            $response['status'] = 'NOK';
            $exportInfo = Export::where(['created_by' => Auth::id(), 'type_id' => $request->type])->latest('id')->first();
            if ($exportInfo) {
                $response['status'] = 'OK';
                $response['progress'] = $exportInfo->progress ?? 0;
            }
            return $response;
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }


    public function getFormat()
    {
        try {
            $filename = 'assets_import_format.csv';
            $filePath = public_path('/assets/format/' . $filename);
            if (!file_exists($filePath)) {
                return redirect()->back()->with('error', 'File not found.');
            }
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($filePath));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            // Delete the file after download

            exit;
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
