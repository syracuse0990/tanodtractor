<?php

namespace App\Jobs;

use App\Models\AllocationDetail;
use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\Tractor;
use App\Models\TractorGroup;
use App\Models\User;
use DateTime;
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

class ImportDataWithValue implements ShouldQueue
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
                $header = array_shift($fileData);

                $fileData = array_filter($fileData, function ($row) {
                    $filteredRow = array_filter($row, function ($value) {
                        return !is_null($value) && trim($value) !== '' && trim($value) !== 'N/A';
                    });
                    return !empty($filteredRow);
                });

                $fileData = array_values($fileData);
            } else {
                throw new Exception("Unsupported file format: $extension");
            }

            if (empty($fileData)) {
                throw new Exception("No data found in file.");
            }

            $allocations = [];
            $userIds = [];
            $tractorIds = [];
            $deviceIds = [];

            foreach ($fileData as $key => $data) {
                $data = array_map([$this, 'cleanCsvString'], $data);
                $data = array_map(function ($value) {
                    $trimmed = trim($value);
                    return ($trimmed === 'N/A' || $trimmed === '') ? null : $trimmed;
                }, $data);

                $group = $this->getOrCreateGroup($data);
                $this->assignSubAdminToGroup($data, $group);

                $farmerPhone = str_replace([' ', '-'], '', $data[4] ?? '');
                $farmerEmail = trim($data[5] ?? null);

                $existingUser = User::where(function ($query) use ($farmerEmail, $farmerPhone) {
                    $query->where('email', $farmerEmail)->orWhere('phone', $farmerPhone);
                })->first();

                $tractorImei = trim($data[8] ?? '');
                if ($tractorImei === '' || strtolower($tractorImei) === 'na') {
                    $tractorImei = trim($data[27] ?? '');
                }
                $deviceImei = trim($data[27] ?? '');

                $user = $device = $tractor = null;

                // Ensure all three entities are present or creatable
                if ((empty($farmerPhone) && empty($farmerEmail)) || empty($tractorImei) || empty($deviceImei)) {
                    echo "Incomplete row (missing user, tractor, or device) at: " . ($key + 1) . "\n";
                    continue;
                }

                if (!preg_match('/^[1-9]\d{9}$/', ltrim($farmerPhone, "0"))) {
                    echo "Invalid phone format at row: " . ($key + 1) . "\n";
                    continue;
                }

                if ($existingUser) {
                    $userData = $this->prepareUserData($data, $farmerPhone, $farmerEmail);
                    $existingUser->update($userData);
                    $user = $existingUser;
                } else {
                    $userData = $this->prepareUserData($data, $farmerPhone, $farmerEmail);
                    $user = User::create($userData);
                }

                $existingDevice = Device::where('imei_no', $deviceImei)->first();
                if ($existingDevice) {
                    $deviceData = $this->prepareDeviceData($data, $deviceImei);
                    $existingDevice->update($deviceData);
                    $device = $existingDevice;
                } else {
                    $deviceData = $this->prepareDeviceData($data, $deviceImei);
                    $device = Device::create($deviceData);
                }

                $existingTractor = Tractor::where('imei', $tractorImei)->first();
                if ($existingTractor) {
                    $tractorData = $this->prepareTractorData($data, $tractorImei, $device, $user, $group);
                    $existingTractor->update($tractorData);
                    $tractor = $existingTractor;
                } else {
                    $tractorData = $this->prepareTractorData($data, $tractorImei, $device, $user, $group);
                    $tractor = Tractor::create($tractorData);
                }

                $allocations['group_ids'][] = $group->id;
                $allocations['user_ids'][] = $user->id;
                $allocations['device_ids'][] = $device->id;
                $allocations['tractor_ids'][] = $tractor->id;

                $userIds[$group->id][] = $user->id;
                $deviceIds[$group->id][] = $device->id;
                $tractorIds[$group->id][] = $tractor->id;
            }

            $allGroups = TractorGroup::all()->keyBy('id');

            foreach (array_unique($allocations['group_ids']) as $groupId) {
                $group = $allGroups[$groupId];
                $newUserIds = $userIds[$groupId] ?? [];
                $newTractorIds = $tractorIds[$groupId] ?? [];
                $newDeviceIds = $deviceIds[$groupId] ?? [];

                foreach ($allGroups as $otherGroupId => $otherGroup) {
                    if ($otherGroupId == $groupId) continue;

                    $existing = json_decode($otherGroup->farmer_ids ?? '[]', true);
                    $updated = array_values(array_diff($existing, $newUserIds));
                    if (count($updated) !== count($existing)) {
                        $otherGroup->update(['farmer_ids' => json_encode($updated)]);
                    }

                    $existing = json_decode($otherGroup->tractor_ids ?? '[]', true);
                    $updated = array_values(array_diff($existing, $newTractorIds));
                    if (count($updated) !== count($existing)) {
                        $otherGroup->update(['tractor_ids' => json_encode($updated)]);
                    }

                    $existing = json_decode($otherGroup->device_ids ?? '[]', true);
                    $updated = array_values(array_diff($existing, $newDeviceIds));
                    if (count($updated) !== count($existing)) {
                        $otherGroup->update(['device_ids' => json_encode($updated)]);
                    }
                }

                $existingFarmers = json_decode($group->farmer_ids ?? '[]', true);
                $existingTractors = json_decode($group->tractor_ids ?? '[]', true);
                $existingDevices = json_decode($group->device_ids ?? '[]', true);

                $group->update([
                    'farmer_ids' => json_encode(array_values(array_unique(array_merge($existingFarmers, $newUserIds)))),
                    'tractor_ids' => json_encode(array_values(array_unique(array_merge($existingTractors, $newTractorIds)))),
                    'device_ids' => json_encode(array_values(array_unique(array_merge($existingDevices, $newDeviceIds)))),
                ]);
            }

            // Optional: Flag empty groups
            foreach ($allGroups as $group) {
                $farmers = json_decode($group->farmer_ids ?? '[]', true);
                $tractors = json_decode($group->tractor_ids ?? '[]', true);
                $devices = json_decode($group->device_ids ?? '[]', true);

                if (empty($farmers) && empty($tractors) && empty($devices)) {
                    $group->delete();
                }
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
            echo "An error occurred: " . $e;
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

    /**
     * Get or create a TractorGroup.
     *
     * @param array $data
     * @return \App\Models\TractorGroup
     */
    private function getOrCreateGroup($data)
    {
        // Get group name or default
        $groupName = !empty($data[0]) ? trim($data[0]) : 'Default Group';

        // Check if group exists
        $groupExists = TractorGroup::where('name', $groupName)->first();

        // Use existing or create new
        $group = $groupExists ?: TractorGroup::create([
            'name' => $groupName,
            'state_id' => User::STATE_ACTIVE,
        ]);

        // Return group
        return $group;
    }


    /**
     * Assign a Sub-Admin to a group if they exist.
     *
     * @param array $data
     * @return void
     */
    private function assignSubAdminToGroup($data, $group)
    {
        // Get sub-admin email from data or set to null
        $subadminEmail = !empty($data[35]) ? trim($data[35]) : null;

        // Check if a sub-admin with the given email and role exists
        $subadminExists = $subadminEmail ? User::where(['email' => $subadminEmail, 'role_id' => User::ROLE_SUB_ADMIN])->first() : null;

        // If sub-admin exists, assign them to the group
        if ($subadminEmail && $subadminExists) {
            AssignedGroup::updateOrCreate(['group_id' => $group->id], ['user_id' => $subadminExists->id]);
        }
    }

    // Function to prepare and return the user data
    private function prepareUserData($data, $farmerPhone, $farmerEmail)
    {
        $farmerName = trim($data[1] ?? '');
        $farmerIso = trim($data[2] ?? '');
        $farmerPhoneCode = !empty($data[3]) ? '+' . trim($data[3]) : null;
        $farmerGender = array_search(strtolower(trim($data[6] ?? '')), array_map('strtolower', User::genderOptions()));
        $farmerPassword = !empty($data[7]) ? trim($data[7]) : 'Admin@123';

        return [
            'name' => $farmerName,
            'country_code' => strtolower($farmerIso),
            'phone_country' => $farmerPhoneCode,
            'phone' => $farmerPhone,
            'email' => $farmerEmail ?: null,
            'gender' => $farmerGender !== false ? $farmerGender : null,
            'role_id' => User::ROLE_FARMER,
            'state_id' => User::STATE_ACTIVE,
            'password' => Hash::make($farmerPassword)
        ];
    }

    // Function to prepare and return the device data
    private function prepareDeviceData($data, $deviceImei)
    {
        $subscriptionExpiration = isset($data[33]) && is_numeric(trim($data[33])) ? (int)trim($data[33]) : null;
        // $expirationDate = isset($data[33]) && !empty($data[33]) ? DateTime::createFromFormat('d/m/Y', trim($data[33])) : null;

        $baseDate = DateTime::createFromFormat('Y-m-d', '2025-02-01');

        $expirationDate = $subscriptionExpiration && $subscriptionExpiration > 0
            ? (clone $baseDate)->modify("+$subscriptionExpiration years")
            : null;

        return [
            'imei_no' => $deviceImei,
            'device_modal' => trim($data[28] ?? ''),
            'device_name' => trim($data[29] ?? ''),
            'sim' => trim($data[30] ?? ''),
            'sim_iccid' => trim($data[31] ?? ''),
            'sim_registration_code' => trim($data[32] ?? ''),
            'subscription_expiration' => $subscriptionExpiration,
            'expiration_date' => $expirationDate ? $expirationDate->format('Y-m-d') : null,
            'mobile_data_load' => trim($data[34] ?? ''),
            'state_id' => User::STATE_ACTIVE,
            'created_by' => $this->currentUser,
        ];
    }

    // Function to prepare and return the tractor data
    private function prepareTractorData($data, $tractorImei, $device = null, $user = null, $group = null)
    {
        $manufactureDate = isset($data[18]) && !empty($data[18]) ? DateTime::createFromFormat('d/m/Y', trim($data[18])) : null;
        $installationTime = isset($data[19]) && !empty($data[19]) ? DateTime::createFromFormat('d/m/Y H:i', trim($data[19])) : null;
        $drDate = isset($data[21]) && !empty($data[21]) ? DateTime::createFromFormat('d/m/Y', trim($data[21])) : null;
        $actualDeliveryDate = isset($data[22]) && !empty($data[22]) ? DateTime::createFromFormat('d/m/Y', trim($data[22])) : null;

        return [
            'imei' => $tractorImei,
            'no_plate' => trim($data[9] ?? ''),
            'id_no' => trim($data[10] ?? ''),
            'engine_no' => trim($data[11] ?? ''),
            'fuel_consumption' => trim($data[12] ?? ''),
            'first_maintenance_hr' => trim($data[13] ?? 50),
            'maintenance_kilometer' => trim($data[14] ?? 100),
            'running_km' => trim($data[15] ?? ''),
            'brand' => trim($data[16] ?? ''),
            'model' => trim($data[17] ?? ''),
            'manufacture_date' => $manufactureDate ? $manufactureDate->format('Y-m-d') : null,
            'installation_time' => $installationTime ? $installationTime->format('Y-m-d H:i:s') : null,
            'installation_address' => trim($data[20] ?? ''),
            'dr_date' => $drDate ? $drDate->format('Y-m-d') : null,
            'actual_delivery_date' => $actualDeliveryDate ? $actualDeliveryDate->format('Y-m-d') : null,
            'dr_no' => trim($data[23] ?? ''),
            'front_loader_sn' => trim($data[24] ?? ''),
            'rotary_tiller_sn' => trim($data[25] ?? ''),
            'rotating_disc_plow_sn' => trim($data[26] ?? ''),
            'state_id' => Tractor::STATE_ACTIVE,
            'created_by' => $this->currentUser,
            'device_id' => $device?->id ?? null,
            'driver_id' => $user?->id ?? null,
            'group_id' => $group?->id ?? null,
        ];
    }
}
