<?php

namespace App\Livewire;

use Livewire\Component;

class ChangeStatus extends Component
{
    public $model;

    public function render()
    {
        return view('livewire.change-status')->with(['model' => $this->model]);
    }
}
