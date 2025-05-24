<?php

namespace App\Livewire;

use App\Models\TractorBooking;
use Livewire\Component;

class TrackRecord extends Component
{
    public $device_id;
    public $groups;

    public function render()
    {
        $bookingData = TractorBooking::where('device_id', $this->device_id)->get();
        return view('livewire.track-record', compact('bookingData'));
    }
}
