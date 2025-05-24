<?php

namespace App\Livewire;

use App\Models\AssignedGroup;
use App\Models\Device;
use App\Models\Jimi;
use App\Models\TractorGroup;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ObjectGroupList extends Component
{
    public $groups;
    public $deviceData;
    public $updatedDevices;
    public $deviceList = [];
    public $state;
    public $totalCount;
    public $onlineCount;
    public $offlineCount;
    public $deviceCondition;
    public $movingCount;
    public $idleCount;
    public $staticCount;
    public $inactiveCount;


    protected $listeners = ['apiData'];
    protected $deviceTypeData = ['deviceTypeData'];

    public function updateData($state)
    {
        // $this->totalCount = 0;
        $this->onlineCount = 0;
        $this->offlineCount = 0;
        $this->inactiveCount = 0;
        $this->state = $state;
        $this->movingCount = 0;
        $this->idleCount = 0;
        $this->staticCount = 0;
        $this->deviceCondition = 0;
    }

    public function getFilteredDevices($condition)
    {
        $this->deviceCondition = $condition;
    }

    public function apiData()
    {
        $this->updatedDevices = null;
        $allDevices = $onlineDevices = $offlineDevices = [];

        $userId = Auth::id();
        $roleId = Auth::user()->role_id;

        $this->inactiveCount = Device::whereNull('activation_time')->count();

        $query = Device::select('imei_no')->whereNotNull('activation_time');
        if ($roleId == User::ROLE_SUB_ADMIN) {
            $assignedGroups = AssignedGroup::where('user_id', $userId)->pluck('group_id')->toArray();
            $deviceIds = TractorGroup::whereIn('id', $assignedGroups)
                ->pluck('device_ids')
                ->flatten()
                ->toArray();
            $deviceIds = array_unique($deviceIds);
            $query->whereIn('id', $deviceIds);
        }

        $deviceImeis = $query->pluck('imei_no')->toArray();
        $imeis = array_values($deviceImeis);

        $batchSize = 100;
        $imeisChunks = array_chunk($imeis, $batchSize);

        // Assuming Jimi API supports asynchronous requests

        $apiData = array_merge(
            ...array_map(
                fn($chunk) => (new Jimi())->getDeviceLocation($chunk)['result'] ?? [],
                $imeisChunks
            )
        );

        $dateTime = date('Y-m-d H:i:s');
        $gmt_date = gmdate('Y-m-d H:i:s', strtotime($dateTime));
        foreach ($apiData as $apiDataItem) {
            $deviceDetails = Device::query();
            if (Auth::user()->role_id == User::ROLE_SUB_ADMIN) {
                $assignedGroups = AssignedGroup::where('user_id', Auth::id())->pluck('group_id')->toArray();
                $tractroGroups = TractorGroup::whereIn('id', $assignedGroups)->get();
                $deviceIds = $tractroGroups->pluck('device_ids')->flatten()->toArray();
                $deviceIds = multiDimToSingleDim($deviceIds);
                $deviceDetails = $deviceDetails->whereIn('id', $deviceIds);
            }
            $deviceDetails = $deviceDetails->where('imei_no', $apiDataItem['imei'])->first();
            if ($deviceDetails) {
                // $this->totalCount = $this->totalCount + 1;
                $diffInSeconds = strtotime($gmt_date) - strtotime($apiDataItem['hbTime']);
                $minutes = floor($diffInSeconds / 60);

                if ($minutes <= 8) {
                    $this->onlineCount = $this->onlineCount + 1;
                } else {
                    $this->offlineCount = $this->offlineCount + 1;
                }



                // if ($apiDataItem['status'] == Device::STATE_ACTIVE) {
                //     $this->onlineCount = $this->onlineCount + 1;
                // } elseif ($apiDataItem['status'] == Device::STATE_INACTIVE) {
                //     $this->offlineCount = $this->offlineCount + 1;
                // }
                $deviceDetails['minutes'] = $minutes;
                $deviceDetails['gmt_date'] = $gmt_date;
                $deviceDetails['hbTime'] = $apiDataItem['hbTime'];
                $deviceDetails['status'] = $apiDataItem['status'];
                $deviceDetails['accStatus'] = $apiDataItem['accStatus'];
                $deviceDetails['speed'] = $apiDataItem['speed'];
                if ($this->state == Device::ALL_DEVICES) {
                    $allDevices[] = $deviceDetails;
                } elseif ($this->state == Device::ONLINE_DEVICES) {

                    if ($apiDataItem['status'] == 1 && $apiDataItem['accStatus'] == 1 && $apiDataItem['speed'] > 0) {
                        $this->movingCount = $this->movingCount + 1;
                    } elseif ($apiDataItem['status'] == 0 && $apiDataItem['accStatus'] == 1) {
                        $this->idleCount = $this->idleCount + 1;
                    }

                    if ($this->deviceCondition == 1) {
                        if ($apiDataItem['status'] == 1 && $apiDataItem['accStatus'] == 1 && $apiDataItem['speed'] > 0) {
                            $onlineDevices[] = $deviceDetails;
                        }
                    } elseif ($this->deviceCondition == 2) {
                        if ($apiDataItem['status'] == 0 && $apiDataItem['accStatus'] == 1) {
                            $onlineDevices[] = $deviceDetails;
                        }
                    } elseif ($this->deviceCondition == 3) {
                        if ($apiDataItem['status'] == 0 && $apiDataItem['accStatus'] == 1) {
                            $onlineDevices[] = $deviceDetails;
                        }
                    } else {
                        if ($minutes <= 8) {
                            $onlineDevices[] = $deviceDetails;
                        }
                    }
                } elseif ($this->state == Device::OFFLINE_DEVICES) {
                    if ($minutes > 8) {
                        $offlineDevices[] = $deviceDetails;
                    }
                }
            }
            $this->deviceData[$apiDataItem['imei']] = $apiDataItem;
        }

        foreach ($this->groups as $group) {
            $groupDevices = $group->device_ids ? json_decode($group->device_ids, true) : [];
            $filteredDevices = array_filter($allDevices, fn($data) => in_array($data->id, $groupDevices));
            $this->updatedDevices[$group->id] = $filteredDevices;
        }
        $this->deviceList['2'] = $onlineDevices;
        $this->deviceList['3'] = $offlineDevices;
        $this->deviceList['4'] = $query = Device::whereNull('activation_time')->get();

        $data = [
            'deviceData' => $this->deviceData,
            'deviceList' => $this->deviceList,
            'updatedDevices' => $this->updatedDevices,
            'state' => $this->state,
            // 'totalCount' => $this->totalCount,
            'onlineCount' => $this->onlineCount,
            'offlineCount' => $this->offlineCount,
            'movingCount' => $this->movingCount,
            'idleCount' => $this->idleCount,
            'staticCount' => $this->staticCount,
            'deviceCondition' => $this->deviceCondition,
            'inactiveCount' => $this->inactiveCount
        ];
        $this->dispatch('dataFetched', $data);
    }

    public function render()
    {
        if (is_null($this->state)) {
            $this->state = Device::ALL_DEVICES;
        }
        $this->updateData($this->state);
        return view('livewire.object-group-list');
    }
}
