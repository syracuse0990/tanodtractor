<?php

namespace App\Http\Controllers;

use App\Jobs\ExportFarmers;
use App\Jobs\ImportAllData;
use App\Jobs\ImportData;
use App\Jobs\ImportDataWithoutValue;
use App\Jobs\ImportDataWithValue;
use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\Export;
use App\Models\Tractor;
use App\Models\TractorBooking;
use App\Models\TractorGroup;
use App\Models\User;
use DateTime;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Class UserController
 * @package App\Http\Controllers
 */
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = null;
        $users = User::whereIn('role_id', [User::ROLE_FARMER]);
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
            $groups = TractorGroup::whereIn('id', $assignedGroups)->get();
            $userIds = $groups->pluck('farmer_ids')->flatten()->toArray();
            $userIds = multiDimToSingleDim($userIds);
            $users = $users->whereIn('id', $userIds);
        }
        if ($request->search) {
            $search = $request->search;
            $users =  $users->where(function (Builder $query) use ($search) {
                return $query->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('email', 'LIKE', '%' . $search . '%')->orWhere('phone', 'LIKE', '%' . $search . '%');
            });
        }
        $users = $users->orderBy('id', 'DESC')->paginate();
        return view('user.index', compact('users', 'search'))
            ->with('i', (request()->input('page', 1) - 1) * $users->perPage());
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (in_array(Auth::user()->role_id, [User::ROLE_SUB_ADMIN])) {
            abort(403, 'You are not allowed to perform this action!!');
        }
        $user = new User();
        return view('user.create', compact('user'));
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
            'name' => 'required',
            'email' => 'required|email:rfc,dns|unique:users,email',
            'phone' => 'required|numeric|unique:users,phone',
            'gender' => 'required'
        ]);

        $userData = $request->all();
        $userData['role_id'] = User::ROLE_SUB_ADMIN;
        $userData['password'] = Hash::make('subadmin@123');
        $userData['phone_country'] = '+' . $request->phone_country;
        $userData['country_code'] = $request->country_code;
        $userData['state_id'] = User::STATE_ACTIVE;
        $userData['email_verified_at'] = now();
        $userData['phone_verified_at'] = now();
        $user = User::create($userData);

        return redirect()->route('users.subAdmin')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::findorFail($id);
        $tractorIds = TractorBooking::where('created_by', $user->id)->pluck('tractor_id')->toArray();
        $tractors = Tractor::whereIn('id', $tractorIds)->paginate();
        return view('user.show', compact('user', 'tractors'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::findorFail($id);

        if ($user->id != Auth::user()->id && Auth::user()->role_id != User::ROLE_ADMIN) {
            return abort(403, 'You are not allowed to perform this action!!!');
        }

        return view('user.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        request()->validate([
            'name' => 'required',
            // 'email' => 'required|email:rfc,dns|unique:users,email,' . $user->id,
            'phone' => 'required|numeric|unique:users,phone,' . $user->id,
            'gender' => 'required'
        ]);
        $userData = $request->all();
        $userData['phone_country'] = '+' . $request->phone_country;
        $userData['country_code'] = $request->country_code;
        $user->update($userData);
        if ($request->file('profile_photo_path')) {
            $path = $request->file('profile_photo_path')->store('image', 'public');
            $user->profile_photo_path = $path;
            $user->save();
        }
        return redirect()->route('users.show', [$user->id])
            ->with('success', 'User updated successfully');
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $user = User::findorFail($id);
        if ($user) {
            $bookings = TractorBooking::where('created_by', $user->id)->update(['state_id' => TractorBooking::STATE_DELETED]);
            $groups = TractorGroup::get();
            foreach ($groups as $group) {
                $farmer_ids = $group->farmer_ids ? json_decode($group->farmer_ids, true) : [];
                if (!empty($farmer_ids) && in_array($user->id, $farmer_ids)) {
                    $key = array_search($user->id, $farmer_ids);
                    unset($farmer_ids[$key]);
                    $farmer_ids = array_values($farmer_ids);
                    $group->farmer_ids = json_encode($farmer_ids);
                    if (!$group->save()) {
                        return redirect()->back()->with('error', 'User not deleted, please try again later.');
                    }
                    $user->delete();
                } else {
                    $user->delete();
                }
            }
            AssignedGroup::where('user_id', $user->id)->delete();
        }
        return redirect()->back()->with('success', 'User deleted successfully');
    }

    public function updatePassword(Request $request)
    {
        # Validation
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        #Match The Old Password

        $validator = Validator::make($request->all(), User::$rules);
        $validator->after(function ($validator) use ($request) {

            if (!Hash::check($request->old_password, auth()->user()->password)) {
                $validator->errors()->add(
                    'old_password',
                    "Old Password Doesn't match!"
                );
            }
        });
        if ($validator->fails()) {

            return redirect()->back()->withErrors($validator)->withInput();
        }

        #Update the new Password
        User::whereId(auth()->user()->id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        return redirect()->route('login')->with("status", "Password changed successfully!");
    }

    public function updateImage(Request $request)
    {
        $user = User::findorFail($request->id);
        if ($user) {
            if ($request->file('profile_photo_path')) {
                $path = $request->file('profile_photo_path')->store('image', 'public');
                $user->profile_photo_path = $path;
                if ($user->save()) {
                    return redirect()->back()->with('success', 'Profile Image updated successfully');
                }
            }
        }
        return redirect()->back();
    }

    public function subAdmin(Request $request)
    {
        $users = User::whereIn('role_id', [User::ROLE_SUB_ADMIN])->orderBy('id', 'DESC');
        $search = null;
        if ($request->search) {
            $search = $request->search;
            $users =  $users->where(function (Builder $query) use ($search) {
                return $query->where('name', 'LIKE', '%' . $search . '%')
                    ->orWhere('email', 'LIKE', '%' . $search . '%')->orWhere('phone', 'LIKE', '%' . $search . '%');
            })->paginate();
        } else {
            $users = $users->paginate();
        }
        return view('user.index', compact('users', 'search'))
            ->with('i', (request()->input('page', 1) - 1) * $users->perPage());
    }

    public function assignIndex(Request $request)
    {
        $group_id = $request->id;
        $assignedIds = AssignedGroup::pluck('user_id')->toArray();
        $users = User::where('role_id', User::ROLE_SUB_ADMIN)->latest('id')->paginate();
        return view('user.assignIndex', compact('users', 'group_id'))
            ->with('i', (request()->input('page', 1) - 1) * $users->perPage());
    }

    public function assignUser(Request $request)
    {
        if ($request->state == 0) {
            $assignedGroup = AssignedGroup::where(['group_id' => $request->id, 'user_id' => $request->user_id])->delete();
            $status = 'unassigned';
        } else {
            $assignedGroup = AssignedGroup::create([
                'group_id' => $request->id,
                'user_id' => $request->user_id,
            ]);
            $status = 'assigned';
        }
        return redirect()->route('tractor-groups.show', $request->id)
            ->with('success', 'Sub Admin ' . $status . ' successfully');
    }

    /**
     * Function to create csv file of farmer users.
     */
    public function exportFarmer(Request $request)
    {
        if ($request->id) {
            $group = TractorGroup::find($request->id);
            $fileName = 'farmers_' . date('Ymdhis') . '.csv';
            Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_FARMER])->latest('id')->delete();
            $export = Export::create([
                'file_name' => $fileName,
                'type_id' => Export::TYPE_FARMER
            ]);

            ExportFarmers::dispatch($fileName, $group->getUsers());
            return redirect()->back()->with('success', 'Export added to queue. Please wait!');
        } else {
            if (!User::where('role_id', User::ROLE_FARMER)->count()) {
                return redirect()->route('users.index')->with('error', 'No data found.');
            }
            $fileName = 'farmers_' . date('Ymdhis') . '.csv';
            Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_FARMER])->latest('id')->delete();
            $export = Export::create([
                'file_name' => $fileName,
                'type_id' => Export::TYPE_FARMER
            ]);

            $farmers = User::where('role_id', User::ROLE_FARMER)->get();
            ExportFarmers::dispatch($fileName, $farmers);
            return redirect()->back()->with('success', 'Export added to queue. Please wait!');
        }
    }

    /**
     * Function to get the file name and dowload that file if found.
     */
    public function download(Request $request)
    {
        try {
            if ($request->filename) {
                $originalFileName = $request->filename;
            } else {
                $export = Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_FARMER])->latest('id')->first();
                $originalFileName = $export->file_name;
            }
            $filePath = storage_path('app/public/csv/' . $originalFileName);

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
            unlink($filePath);

            exit;
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function checkFile()
    {
        $response['status'] = 'NOK';
        $export = Export::where(['created_by' => Auth::user()->id, 'type_id' => Export::TYPE_FARMER])->latest('id')->first();
        if ($export) {
            $fileName = $export->file_name;
            $filePath = storage_path('app/public/csv/' . $fileName);
            if (file_exists($filePath)) {
                $response['status'] = 'OK';
            }
        }
        return $response;
    }



    public function import(Request $request)
    {
        $rules = [
            'fileInput' => 'required|mimes:csv,txt,xlsx,xls'
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

            ImportDataWithValue::dispatch($filePath, Auth::id());
            // ImportDataWithoutValue::dispatch($filePath, Auth::id());

            return redirect()->back()->with('success', 'Import request has been added to the queue. Please check back shortly.');
        } catch (Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function getFormat()
    {
        try {

            $filename = 'data_import_format.csv';
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

    public function removeDuplicateUsers()
    {
        $duplicateEmails = User::select('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('email');

        // Get phones with duplicates
        $duplicatePhones = User::select('phone')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone');

        // Find the IDs of the first created records for duplicate emails
        $keepEmailIds = User::whereIn('email', $duplicateEmails)
            ->select(DB::raw('MIN(id) as id'))
            ->groupBy('email')
            ->pluck('id');

        // Find the IDs of the first created records for duplicate phones
        $keepPhoneIds = User::whereIn('phone', $duplicatePhones)
            ->select(DB::raw('MIN(id) as id'))
            ->groupBy('phone')
            ->pluck('id');

        // Combine the IDs to keep (to avoid deletion)
        $keepIds = $keepEmailIds->merge($keepPhoneIds)->unique();

        // Delete duplicates, keeping the first created record
        User::where(function ($query) use ($duplicateEmails, $duplicatePhones) {
            $query->whereIn('email', $duplicateEmails)
                ->orWhereIn('phone', $duplicatePhones);
        })->whereNotIn('id', $keepIds)->delete();

        return redirect()->back()->with('success', 'Duplicacy removed.');
    }
}
