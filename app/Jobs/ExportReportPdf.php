<?php

namespace App\Jobs;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportReportPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 3600;
    protected $data;
    protected $user;
    protected $filename;
    /**
     * Create a new job instance.
     */
    public function __construct($data, $user, $filename)
    {
        $this->data = $data;
        $this->user = $user;
        $this->filename = $filename;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $pdf = Pdf::loadView('report.pdf', $this->data);
        $filePath = 'public/reports/' . $this->filename;
        Storage::put($filePath, $pdf->output());
    }
}
