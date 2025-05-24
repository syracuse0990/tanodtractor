<?php

namespace App\Jobs;

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

class ImportData implements ShouldQueue
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

            if ($extension === 'csv') {
                $handle = fopen($filePath, 'r');
                $header = fgetcsv($handle);
                $fileData = [];
                while (($data = fgetcsv($handle)) !== false) {
                    $fileData[] = $data;
                }
            } elseif (in_array($extension, ['xls', 'xlsx'])) {
                // Load the XLSX file
                $spreadsheet = IOFactory::load($filePath);
                $sheet = $spreadsheet->getActiveSheet();
                $fileData = $sheet->toArray();
                $header = array_shift($fileData); // Assumes first row is header
            } else {
                throw new Exception("Unsupported file format: $extension");
            }

            $userIds = $tractorIds = $deviceIds = $groupIds = [];
            $lastGroup = null;
            // while (($data = fgetcsv($handle)) !== false) {

            foreach ($fileData as $data) {
                foreach ($data as $index => $value) {
                    $data[$index] = $this->cleanCsvString($value);
                }

                //Get group name and check if it is not empty.
                $groupName = isset($data[0]) && !empty($data[0]) ? trim($data[0]) : null;
                if ($groupName) {
                    //Group Functionality
                    $groupExists = TractorGroup::where('name', $groupName)->first();
                    if ($groupExists) {
                        $group = $groupExists;
                        $groupIds[] = $group->id;
                    } else {
                        $groupData['name'] = $groupName;
                        $groupData['state_id'] = User::STATE_ACTIVE;
                        $group = TractorGroup::create($groupData);
                        $groupIds[] = $group->id;
                    }
                    $lastGroup = $group;

                    //Subadmin functionality
                    $subadminEmail = isset($data[26]) && !empty($data[26]) ? trim($data[26]) : null;
                    if (!empty($subadminEmail)) {
                        $subadminExists = User::where([
                            'email' => $subadminEmail,
                            'role_id' => User::ROLE_SUB_ADMIN
                        ])->first();
                        if ($subadminExists) {
                            AssignedGroup::updateOrCreate([
                                'group_id' => $group->id
                            ], [
                                'user_id' => $subadminExists->id,
                                'group_id' => $group->id
                            ]);
                        }
                    }

                    //User Functionality
                    $farmerPhone = isset($data[4]) && !empty($data[4]) ? trim($data[4]) : null;
                    $farmerPhone = str_replace([' ', '-'], '', $farmerPhone);
                    $farmerEmail = isset($data[5]) && !empty($data[5]) ? trim($data[5]) : null;

                    if ($farmerPhone || $farmerEmail) {
                        $userExists = User::whereNotNull('email')
                            ->whereNotNull('phone')
                            ->where(function ($query) use ($farmerEmail, $farmerPhone) {
                                $query->where('email', $farmerEmail)
                                    ->orWhere('phone', $farmerPhone);
                            })
                            ->first();
                        if ($userExists) {
                            $groups = TractorGroup::get();
                            $userGroup = false;
                            foreach ($groups as $group) {
                                $groupFarmerIds = $group->farmer_ids ? json_decode($group->farmer_ids, true) : [];
                                if (in_array($userExists->id, $groupFarmerIds)) {
                                    $userGroup = true;
                                }
                            }
                            // if ($userGroup) {
                            //     continue;
                            // } else {
                            //     $userIds[$group->id][] = $userExists->id;
                            // }
                            if (!$userGroup) {
                                $userIds[$group->id][] = $userExists->id;
                            }
                        } else {
                            $phone = $email = null;
                            if ($farmerEmail) {
                                $email = User::where('email', $farmerEmail)->first();
                                // if ($email) {
                                //     continue;
                                // }
                            }
                            if ($farmerPhone) {
                                $phone = User::where('phone', $farmerPhone)->first();
                                // if ($phone) {
                                //     continue;
                                // }
                            }
                            if (empty($phone) && empty($email)) {
                                $farmerName = isset($data[1]) && !empty($data[1]) ? trim($data[1]) : null;
                                $farmerIso = isset($data[2]) && !empty($data[2]) ? trim($data[2]) : null;
                                $farmerPhoneCode = isset($data[3]) && !empty($data[3]) ? '+' . trim($data[3]) : null;
                                $farmerGender = array_search(strtolower(trim($data[6])), array_map('strtolower', User::genderOptions()));
                                $farmerPassword = (isset($data[7]) && !empty($data[7])) ? trim($data[7]) : 'Admin@123';

                                $userData = [
                                    'name' => $farmerName,
                                    'country_code' => strtolower($farmerIso),
                                    'phone_country' => $farmerPhoneCode,
                                    'phone' => $farmerPhone,
                                    'email' => $farmerEmail,
                                    'gender' => isset($farmerGender) && $farmerGender !== false ? $farmerGender : null,
                                    'role_id' => User::ROLE_FARMER,
                                    'state_id' => User::STATE_ACTIVE,
                                    'password' => Hash::make($farmerPassword)
                                ];
                                $user = User::create($userData);
                                $userIds[$group->id][] = $user->id;
                            }
                        }
                    }

                    //TractorFunctionality
                    $tractorNumberPlate = isset($data[8]) && !empty($data[8]) ? trim($data[8]) : null;
                    if (!empty($tractorNumberPlate)) {
                        if (Tractor::where('no_plate', $tractorNumberPlate)->exists()) {
                            $tractorExists = Tractor::where('no_plate', $tractorNumberPlate)->first();
                            $groups = TractorGroup::get();
                            $tractorGroup = false;
                            foreach ($groups as $group) {
                                $groupTractorIds = $group->tractor_ids ? json_decode($group->tractor_ids, true) : [];
                                if (in_array($tractorExists->id, $groupTractorIds)) {
                                    $tractorGroup = true;
                                }
                            }
                            // if ($tractorGroup) {
                            //     continue;
                            // } else {
                            //     $tractorIds[$group->id][] = $tractorExists->id;
                            // }
                            if (!$tractorGroup) {
                                $tractorIds[$group->id][] = $tractorExists->id;
                            }
                        } else {
                            $idNo = isset($data[9]) && !empty($data[9]) ? trim($data[9]) : null;
                            $engineNo = isset($data[10]) && !empty($data[10]) ? trim($data[10]) : null;
                            $fuelConsumption = isset($data[11]) && !empty($data[11]) ? trim($data[11]) : null;
                            $first_maintenance_hr = isset($data[12]) && !empty($data[12]) ? trim($data[12]) : null;
                            $maintenaceHours = isset($data[13]) && !empty($data[13]) ? trim($data[13]) : null;
                            $runningHours = isset($data[14]) && !empty($data[14]) ? trim($data[14]) : null;
                            $brand = isset($data[15]) && !empty($data[15]) ? trim($data[15]) : null;
                            $model = isset($data[16]) && !empty($data[16]) ? trim($data[16]) : null;
                            $manufactureDate = isset($data[17]) && !empty($data[17]) ? DateTime::createFromFormat('d/m/Y', trim($data[17])) : null;
                            $installationTime = isset($data[18]) && !empty($data[18]) ? DateTime::createFromFormat('d/m/Y H:i', trim($data[18])) : null;
                            $installationAddress = isset($data[19]) && !empty($data[19]) ? trim($data[19]) : null;
                            $tractorData = [
                                'no_plate' => $tractorNumberPlate,
                                'id_no' => $idNo,
                                'engine_no' => $engineNo,
                                'fuel_consumption' => $fuelConsumption,
                                'first_maintenance_hr' => $first_maintenance_hr,
                                'maintenance_kilometer' => $maintenaceHours,
                                'running_km' => $runningHours,
                                'brand' => $brand,
                                'model' => $model,
                                'manufacture_date' => $manufactureDate ? $manufactureDate->format('Y-m-d') : null,
                                'installation_time' => $installationTime ? $installationTime->format('Y-m-d H:i:s') : null,
                                'installation_address' => $installationAddress,
                                'state_id' => User::STATE_ACTIVE
                            ];
                            $tractor = Tractor::create($tractorData);
                            $tractorIds[$group->id][] = $tractor->id;
                        }
                    }

                    //Device Functionality
                    $imei = isset($data[20]) && !empty($data[20]) ? trim($data[20]) : null;
                    if (!empty($imei)) {
                        if (preg_match('/^\d+\.\d+E\+\d+$/', $imei)) {
                            $imei = number_format((float)$imei, 0, '', '');
                        }
                        if (Device::where('imei_no', $imei)->exists()) {
                            $deviceExists = Device::where('imei_no', $imei)->first();
                            $groups = TractorGroup::get();
                            $deviceGroup = false;
                            foreach ($groups as $group) {
                                $groupDeviceIds = $group->device_ids ? json_decode($group->device_ids, true) : [];
                                if (in_array($deviceExists->id, $groupDeviceIds)) {
                                    $deviceGroup = true;
                                }
                            }
                            // if ($deviceGroup) {
                            //     continue;
                            // } else {
                            //     $deviceIds[$group->id][] = $deviceExists->id;
                            // }
                            if (!$deviceGroup) {
                                $deviceIds[$group->id][] = $deviceExists->id;
                            }
                        } else {
                            $deviceModel = isset($data[21]) && !empty($data[21]) ? trim($data[21]) : null;
                            $deviceName = isset($data[22]) && !empty($data[22]) ? trim($data[22]) : null;
                            $sim = isset($data[23]) && !empty($data[23]) ? trim($data[23]) : null;
                            $subscriptionExpiration = isset($data[24]) && !empty($data[24]) ? trim($data[24]) : null;
                            $expirationDate = isset($data[25]) && !empty($data[25]) ? DateTime::createFromFormat('d/m/Y', trim($data[25])) : null;
                            $deviceData = [
                                'imei_no' => $imei,
                                'device_modal' => $deviceModel,
                                'device_name' => $deviceName,
                                'sim' => $sim,
                                'subscription_expiration' => $subscriptionExpiration,
                                'expiration_date' => $expirationDate ? $expirationDate->format('Y-m-d') : null,
                                'state_id' => User::STATE_ACTIVE
                            ];
                            $device = Device::create($deviceData);
                            $deviceIds[$group->id][] = $device->id;
                        }
                    }
                } else {
                    //Group Functionality
                    if ($lastGroup) {
                        $group = $lastGroup;
                        $groupIds[] = $group->id;
                    } else {
                        $groupData['name'] = $groupName;
                        $groupData['state_id'] = User::STATE_ACTIVE;
                        $group = TractorGroup::create($groupData);
                        $groupIds[] = $group->id;
                    }
                    //Subadmin functionality
                    $subadminEmail = isset($data[26]) && !empty($data[26]) ? trim($data[26]) : null;
                    if (!empty($subadminEmail)) {
                        $subadminExists = User::where([
                            'email' => $subadminEmail,
                            'role_id' => User::ROLE_SUB_ADMIN
                        ])->first();
                        if ($subadminExists) {
                            AssignedGroup::updateOrCreate([
                                'group_id' => $group->id
                            ], [
                                'user_id' => $subadminExists->id,
                                'group_id' => $group->id
                            ]);
                        }
                    }

                    //User Functionality
                    $farmerPhone = isset($data[4]) && !empty($data[4]) ? trim($data[4]) : null;
                    $farmerPhone = str_replace([' ', '-'], '', $farmerPhone);
                    $farmerEmail = isset($data[5]) && !empty($data[5]) ? trim($data[5]) : null;

                    if ($farmerPhone || $farmerEmail) {
                        $userExists = User::whereNotNull('email')
                            ->whereNotNull('phone')
                            ->where(function ($query) use ($farmerEmail, $farmerPhone) {
                                $query->where('email', $farmerEmail)
                                    ->orWhere('phone', $farmerPhone);
                            })
                            ->first();
                        if ($userExists) {
                            $groups = TractorGroup::get();
                            $userGroup = false;
                            foreach ($groups as $group) {
                                $groupFarmerIds = $group->farmer_ids ? json_decode($group->farmer_ids, true) : [];
                                if (in_array($userExists->id, $groupFarmerIds)) {
                                    $userGroup = true;
                                }
                            }
                            // if ($userGroup) {
                            //     continue;
                            // } else {
                            //     $userIds[$group->id][] = $userExists->id;
                            // }
                            if (!$userGroup) {
                                $userIds[$group->id][] = $userExists->id;
                            }
                        } else {
                            $farmerName = isset($data[1]) && !empty($data[1]) ? trim($data[1]) : null;
                            $farmerIso = isset($data[2]) && !empty($data[2]) ? trim($data[2]) : null;
                            $farmerPhoneCode = isset($data[3]) && !empty($data[3]) ? '+' . trim($data[3]) : null;
                            $farmerGender = array_search(strtolower(trim($data[6])), array_map('strtolower', User::genderOptions()));
                            $farmerPassword = (isset($data[7]) && !empty($data[7])) ? trim($data[7]) : 'Admin@123';

                            $userData = [
                                'name' => $farmerName,
                                'country_code' => strtolower($farmerIso),
                                'phone_country' => $farmerPhoneCode,
                                'phone' => $farmerPhone,
                                'email' => $farmerEmail,
                                'gender' => isset($farmerGender) && $farmerGender !== false ? $farmerGender : null,
                                'role_id' => User::ROLE_FARMER,
                                'state_id' => User::STATE_ACTIVE,
                                'password' => Hash::make($farmerPassword)
                            ];
                            $user = User::create($userData);
                            $userIds[$group->id][] = $user->id;
                        }
                    }

                    //TractorFunctionality
                    $tractorNumberPlate = isset($data[8]) && !empty($data[8]) ? trim($data[8]) : null;
                    if (!empty($tractorNumberPlate)) {
                        if (Tractor::where('no_plate', $tractorNumberPlate)->exists()) {
                            $tractorExists = Tractor::where('no_plate', $tractorNumberPlate)->first();
                            $groups = TractorGroup::get();
                            $tractorGroup = false;
                            foreach ($groups as $group) {
                                $groupTractorIds = $group->tractor_ids ? json_decode($group->tractor_ids, true) : [];
                                if (in_array($tractorExists->id, $groupTractorIds)) {
                                    $tractorGroup = true;
                                }
                            }
                            // if ($tractorGroup) {
                            //     continue;
                            // } else {
                            //     $tractorIds[$group->id][] = $tractorExists->id;
                            // }
                            if (!$tractorGroup) {
                                $tractorIds[$group->id][] = $tractorExists->id;
                            }
                        } else {
                            $idNo = isset($data[9]) && !empty($data[9]) ? trim($data[9]) : null;
                            $engineNo = isset($data[10]) && !empty($data[10]) ? trim($data[10]) : null;
                            $fuelConsumption = isset($data[11]) && !empty($data[11]) ? trim($data[11]) : null;
                            $first_maintenance_hr = isset($data[12]) && !empty($data[12]) ? trim($data[12]) : null;
                            $maintenaceHours = isset($data[13]) && !empty($data[13]) ? trim($data[13]) : null;
                            $runningHours = isset($data[14]) && !empty($data[14]) ? trim($data[14]) : null;
                            $brand = isset($data[15]) && !empty($data[15]) ? trim($data[15]) : null;
                            $model = isset($data[16]) && !empty($data[16]) ? trim($data[16]) : null;
                            $manufactureDate = isset($data[17]) && !empty($data[17]) ? DateTime::createFromFormat('d/m/Y', trim($data[17])) : null;
                            $installationTime = isset($data[18]) && !empty($data[18]) ? DateTime::createFromFormat('d/m/Y H:i:s', trim($data[18])) : null;
                            $installationAddress = isset($data[19]) && !empty($data[19]) ? trim($data[19]) : null;
                            $tractorData = [
                                'no_plate' => $tractorNumberPlate,
                                'id_no' => $idNo,
                                'engine_no' => $engineNo,
                                'fuel_consumption' => $fuelConsumption,
                                'first_maintenance_hr' => $first_maintenance_hr,
                                'maintenance_kilometer' => $maintenaceHours,
                                'running_km' => $runningHours,
                                'brand' => $brand,
                                'model' => $model,
                                'manufacture_date' => $manufactureDate ? $manufactureDate->format('Y-m-d') : null,
                                'installation_time' => $installationTime ? $installationTime->format('Y-m-d H:i:s') : null,
                                'installation_address' => $installationAddress,
                                'state_id' => User::STATE_ACTIVE
                            ];
                            $tractor = Tractor::create($tractorData);
                            $tractorIds[$group->id][] = $tractor->id;
                        }
                    }

                    //Device Functionality
                    $imei = isset($data[20]) && !empty($data[20]) ? trim($data[20]) : null;
                    if (!empty($imei)) {
                        if (preg_match('/^\d+\.\d+E\+\d+$/', $imei)) {
                            $imei = number_format((float)$imei, 0, '', '');
                        }
                        if (Device::where('imei_no', $imei)->exists()) {
                            $deviceExists = Device::where('imei_no', $imei)->first();
                            $groups = TractorGroup::get();
                            $deviceGroup = false;
                            foreach ($groups as $group) {
                                $groupDeviceIds = $group->device_ids ? json_decode($group->device_ids, true) : [];
                                if (in_array($deviceExists->id, $groupDeviceIds)) {
                                    $deviceGroup = true;
                                }
                            }
                            // if ($deviceGroup) {
                            //     continue;
                            // } else {
                            //     $deviceIds[$group->id][] = $deviceExists->id;
                            // }
                            if ($deviceGroup) {
                                $deviceIds[$group->id][] = $deviceExists->id;
                            }
                        } else {
                            $deviceModel = isset($data[21]) && !empty($data[21]) ? trim($data[21]) : null;
                            $deviceName = isset($data[22]) && !empty($data[22]) ? trim($data[22]) : null;
                            $sim = isset($data[23]) && !empty($data[23]) ? trim($data[23]) : null;
                            $subscriptionExpiration = isset($data[24]) && !empty($data[24]) ? trim($data[24]) : null;
                            $expirationDate = isset($data[25]) && !empty($data[25]) ? DateTime::createFromFormat('d/m/Y', trim($data[25])) : null;
                            $deviceData = [
                                'imei_no' => $imei,
                                'device_modal' => $deviceModel,
                                'device_name' => $deviceName,
                                'sim' => $sim,
                                'subscription_expiration' => $subscriptionExpiration,
                                'expiration_date' => $expirationDate ? $expirationDate->format('Y-m-d') : null,
                                'state_id' => User::STATE_ACTIVE
                            ];
                            $device = Device::create($deviceData);
                            $deviceIds[$group->id][] = $device->id;
                        }
                    }
                }
            }
            foreach (array_unique($groupIds) as $id) {
                $group = TractorGroup::find($id);
                // Farmers
                $groupFarmers = $group->farmer_ids ? json_decode($group->farmer_ids, true) : [];
                $groupFarmers = array_merge($groupFarmers, array_map('strval', isset($userIds[$id]) ? $userIds[$id] : []));

                // Tractors
                $groupTractors = $group->tractor_ids ? json_decode($group->tractor_ids, true) : [];
                $groupTractors = array_merge($groupTractors, array_map('strval', isset($tractorIds[$id]) ? $tractorIds[$id] : []));
                // Devices
                $groupDevices = $group->device_ids ? json_decode($group->device_ids, true) : [];
                $groupDevices = array_merge($groupDevices, array_map('strval', isset($deviceIds[$id]) ? $deviceIds[$id] : []));

                $group['farmer_ids'] = json_encode($groupFarmers);
                $group['tractor_ids'] = json_encode($groupTractors);
                $group['device_ids'] = json_encode($groupDevices);
                $group->save();
            }
            fclose($handle);
            Storage::disk('public')->delete($filePath);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            echo "An error occured :" . $e->getMessage();
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
