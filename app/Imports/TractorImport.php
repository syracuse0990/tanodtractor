<?php

namespace App\Imports;

use App\Models\Tractor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
// use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;


class TractorImport implements ToModel, WithHeadingRow, WithCalculatedFormulas
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
     public function model(array $row)
        {
            //  \Log::info($row);
        // $groupName = $this->cleanFormulaValue($row['group_name'] ?? '');
        // $deviceName = $this->cleanFormulaValue($row['device_name'] ?? '');
        // $tractorImei = $this->cleanFormulaValue($row['tractor_imei'] ?? '');
               \Log::info($row);
        // return new Tractor([
        //     'group_name' => $groupName,
        //     'name' => $row['name'] ?? '',
        //     'iso_code' => $row['iso_code'] ?? null,
        //     'phone_code' => $this->cleanNA($row['phone_code'] ?? ''),
        //     'phone' => $this->cleanNA($row['phone'] ?? ''),
        //     'email' => $this->cleanNA($row['email'] ?? ''),
        //     'gender' => $this->cleanNA($row['gender'] ?? ''),
        //     'password' => $this->cleanNA($row['password'] ?? ''),
        //     'tractor_imei' => $tractorImei,
        //     'number_plate' => $this->cleanNA($row['number_plate'] ?? ''),
        //     'id_number' => $this->cleanNA($row['id_number'] ?? ''),
        //     'engine_number' => $this->cleanNA($row['engine_number'] ?? ''),
        //     'fuel100km' => $this->cleanNA($row['fuel100km'] ?? ''),
        //     'first_maintenance_hours' => $row['first_maintenance_hours'] ?? null,
        //     'subsequest_maintenace_hours' => $row['subsequest_maintenace_hours'] ?? null,
        //     'running_hours' => $this->cleanNA($row['running_hours'] ?? ''),
        //     'tractor_brand' => $this->cleanNA($row['tractor_brand'] ?? ''),
        //     'tractor_model' => $this->cleanNA($row['tractor_model'] ?? ''),
        //     'manufacture_date_yyyy_mm_dd' => $this->cleanNA($row['manufacture_date_yyyy_mm_dd'] ?? ''),
        //     'installation_time_yyyy_mm_dd_hhmmss' => $this->cleanNA($row['installation_time_yyyy_mm_dd_hhmmss'] ?? ''),
        //     'installation_address' => $this->cleanNA($row['installation_address'] ?? ''),
        //     'dr_date' => $this->cleanNA($row['dr_date'] ?? ''),
        //     'actual_delivery_date' => $this->cleanNA($row['actual_delivery_date'] ?? ''),
        //     'dr_no' => $this->cleanNA($row['dr_no'] ?? ''),
        //     'front_loader_sn' => $this->cleanNA($row['front_loader_sn'] ?? ''),
        //     'rotary_tiller_sn' => $this->cleanNA($row['rotary_tiller_sn'] ?? ''),
        //     'rotating_disc_plow_sn' => $this->cleanNA($row['rotating_disc_plow_sn'] ?? ''),
        //     'device_imei' => $row['device_imei'] ?? null,
        //     'device_model' => $row['device_model'] ?? '',
        //     'device_name' => $deviceName,
        //     'sim_card_number' => $this->cleanNA($row['sim_card_number'] ?? ''),
        //     'sim_card_iccid' => $this->cleanNA($row['sim_card_iccid'] ?? ''),
        //     'sim_registration_code' => $this->cleanNA($row['sim_registration_code'] ?? ''),
        //     'activated_date_as_of_feb_2025' => $row['activated_date_as_of_feb_2025'] ?? null,
        //     'mobile_data_load' => $this->cleanNA($row['mobile_data_load'] ?? ''),
        // ]);
    }

    //  public function rules(): array
    // {
    //     return [
    //         'name' => 'required|string',
    //         'iso_code' => 'required|numeric',
    //         'device_imei' => 'required|numeric',
    //     ];
    // }

    private function cleanFormulaValue($value)
    {
        if (is_string($value) && str_starts_with($value, '=')) {
            return '';
        }
        return $value;
    }

    private function cleanNA($value)
    {
        return trim($value) === 'N/A' ? '' : $value;
    }
}
