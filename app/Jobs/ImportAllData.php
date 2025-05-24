<?php

namespace App\Jobs;

use App\Models\AllocationDetail;
use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\Tractor;
use App\Models\TractorGroup;
use App\Models\User;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportAllData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    protected $filePath;
    protected $currentUser;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath, $currentUser)
    {
        $this->filePath = $filePath;
        $this->currentUser = $currentUser;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            DB::beginTransaction();
            $filePath = storage_path('app/public/' . $this->filePath);
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);

            $fileData = [];
            $header = [];

            if ($extension === 'csv') {
                $handle = fopen($filePath, 'r');
                if (!$handle) {
                    throw new Exception("Failed to open CSV file.");
                }
                $header = fgetcsv($handle);
                while (($data = fgetcsv($handle)) !== false) {
                    $fileData[] = $data;
                }
                fclose($handle);
            } elseif (in_array($extension, ['xls', 'xlsx'])) {
                $spreadsheet = IOFactory::load($filePath);
                $sheet = $spreadsheet->getActiveSheet();
                $fileData = $sheet->toArray();

                // Skip the first row if it is a header
                $header = array_shift($fileData);

                // Remove empty rows or rows that contain only "N/A"
                $fileData = array_filter($fileData, function ($row) {
                    $filteredRow = array_filter($row, function ($value) {
                        return !is_null($value) && trim($value) !== '' && trim($value) !== 'N/A';
                    });
                    return !empty($filteredRow);
                });

                // Reset array keys
                $fileData = array_values($fileData);
            } else {
                throw new Exception("Unsupported file format: $extension");
            }

            if (empty($fileData)) {
                throw new Exception("No data found in file.");
            }

            $allocations = [];
            $groupIds = [];
            $userIds = [];
            $tractorIds = [];
            $deviceIds = [];

            foreach ($fileData as $key => $data) {
                $data = array_map([$this, 'cleanCsvString'], $data);
                $data = array_map(function ($value) {
                    $trimmed = trim($value);
                    return ($trimmed === 'N/A' || $trimmed === '') ? null : $trimmed;
                }, $data);

                $groupName = !empty($data[0]) ? trim($data[0]) : 'Default Group';
                $skipRow = false;

                // Check duplicates
                $groupExists = TractorGroup::where('name', $groupName)->first();
                $subadminEmail = !empty($data[26]) ? trim($data[26]) : null;
                $subadminExists = $subadminEmail ? User::where(['email' => $subadminEmail, 'role_id' => User::ROLE_SUB_ADMIN])->first() : null;

                $farmerPhone = str_replace([' ', '-'], '', $data[4] ?? '');
                $farmerEmail = trim($data[5] ?? '');
                // $existingUser = User::where(function ($query) use ($farmerEmail, $farmerPhone) {
                //     $query->where('email', $farmerEmail)->orWhere('phone', $farmerPhone);
                // })->first();

                // if ($existingUser) {
                //     $skipRow = true;
                // }

                $tractorImei = trim($data[8] ?? '');
                // if (!empty($tractorImei) && Tractor::where('imei', $tractorImei)->exists()) {
                //     $skipRow = true;
                // }

                $deviceImei = trim($data[21] ?? '');
                if (!empty($deviceImei) && Device::where('imei_no', $deviceImei)->exists()) {
                    $skipRow = true;
                }

                // if ($skipRow) {
                //     echo "Duplicate entry found, skipping row: " . ($key + 1);
                //     continue;
                // }

                $group = $groupExists ?: TractorGroup::create(['name' => $groupName, 'state_id' => User::STATE_ACTIVE]);
                $groupIds[] = $group->id;
                $allocations['group_ids'][] = $group->id;

                if ($subadminEmail && $subadminExists) {
                    AssignedGroup::updateOrCreate(['group_id' => $group->id], ['user_id' => $subadminExists->id]);
                }

                if (!empty($farmerPhone) || !empty($farmerEmail)) {
                    if (!preg_match('/^[1-9]\d{9}$/', $farmerPhone)) {
                        echo "Invalid phone format at row: " . ($key + 1);
                        continue;
                    }

                    $phone = User::where('phone', $farmerPhone)->first();
                    $email = User::where('email', $farmerEmail)->first();

                    if (!$phone && !$email) {
                        $farmerName = trim($data[1] ?? '');
                        $farmerIso = trim($data[2] ?? '');
                        $farmerPhoneCode = !empty($data[3]) ? '+' . trim($data[3]) : null;
                        $farmerGender = array_search(strtolower(trim($data[6] ?? '')), array_map('strtolower', User::genderOptions()));
                        $farmerPassword = !empty($data[7]) ? trim($data[7]) : 'Admin@123';

                        $user = User::create([
                            'name' => $farmerName,
                            'country_code' => strtolower($farmerIso),
                            'phone_country' => $farmerPhoneCode,
                            'phone' => $farmerPhone,
                            'email' => $farmerEmail,
                            'gender' => $farmerGender !== false ? $farmerGender : null,
                            'role_id' => User::ROLE_FARMER,
                            'state_id' => User::STATE_ACTIVE,
                            'password' => Hash::make($farmerPassword)
                        ]);
                        $userIds[$group->id][] = $user->id;
                        $allocations['user_ids'][] = $user->id;
                    }
                }

                if (!empty($tractorImei)) {
                    $tractor = Tractor::create([
                        'imei' => $tractorImei,
                        'no_plate' => trim($data[9] ?? ''),
                        'id_no' => trim($data[10] ?? ''),
                        'state_id' => Tractor::STATE_ACTIVE,
                        'created_by' => $this->currentUser
                    ]);
                    $tractorIds[$group->id][] = $tractor->id;
                    $allocations['tractor_ids'][] = $tractor->id;
                }

                if (!empty($deviceImei)) {
                    $device = Device::create([
                        'imei_no' => $deviceImei,
                        'device_modal' => trim($data[22] ?? ''),
                        'device_name' => trim($data[23] ?? ''),
                        'state_id' => User::STATE_ACTIVE,
                        'created_by' => $this->currentUser
                    ]);
                    $deviceIds[$group->id][] = $device->id;
                    $allocations['device_ids'][] = $device->id;
                }
            }

            foreach (array_unique($allocations['group_ids']) as $id) {
                $group = TractorGroup::find($id);
                $groupFarmers = $group->farmer_ids ? json_decode($group->farmer_ids, true) : [];
                $groupFarmers = array_merge($groupFarmers, array_map('strval', $userIds[$id] ?? []));

                $groupTractors = $group->tractor_ids ? json_decode($group->tractor_ids, true) : [];
                $groupTractors = array_merge($groupTractors, array_map('strval', $tractorIds[$id] ?? []));

                $groupDevices = $group->device_ids ? json_decode($group->device_ids, true) : [];
                $groupDevices = array_merge($groupDevices, array_map('strval', $deviceIds[$id] ?? []));

                $group->update([
                    'farmer_ids' => json_encode($groupFarmers),
                    'tractor_ids' => json_encode($groupTractors),
                    'device_ids' => json_encode($groupDevices)
                ]);
            }

            foreach ($allocations['group_ids'] as $key => $value) {
                AllocationDetail::create([
                    'group_id' => $value,
                    'user_id' => $allocations['user_ids'][$key] ?? null,
                    'tractor_id' => $allocations['tractor_ids'][$key] ?? null,
                    'device_id' => $allocations['device_ids'][$key] ?? null,
                ]);
            }

            Storage::disk('public')->delete($filePath);

            StoreDeviceDetail::dispatch();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            echo "An error occurred: " . $e->getMessage();
        }
    }


    private function cleanCsvString($data)
    {
        $data = str_replace('�', '’', $data);
        $encodings = ['UTF-8', 'ISO-8859-1', 'windows-1252'];
        $detectedEncoding = mb_detect_encoding($data, $encodings, true);
        if ($detectedEncoding && $detectedEncoding !== 'UTF-8') {
            $data = mb_convert_encoding($data, 'UTF-8', $detectedEncoding);
        }
        return $data;
    }
}
