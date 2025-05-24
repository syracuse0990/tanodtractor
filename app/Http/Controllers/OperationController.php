<?php

namespace App\Http\Controllers;


/**
 * Class OperationController
 * @package App\Http\Controllers
 */
class OperationController extends Controller
{
    public function runMigrations()
    {
        $command = 'Run Migration';
        chdir(base_path());
        // Specify the full path to the artisan file
        $cmd = 'php ' . base_path('artisan') . ' migrate';
        // Execute the command
        $output = shell_exec($cmd);
        // return redirect()->back()->with('success', $output);
        return view('operation.show', compact('output', 'command'));
    }

    public function composerInstall()
    {
        $command = 'Composer Install';

        // Change to the base path and check if it was successful
        if (!chdir(base_path())) {
            return view('operation.show', [
                'output' => 'Failed to change directory to base path.',
                'command' => $command
            ]);
        }

        // Specify the full path to the artisan file
        $cmd = 'composer install 2>&1';  // '2>&1' will include error output in $output

        // Execute the command
        $output = shell_exec($cmd);

        if ($output === null) {
            $output = 'Failed to execute the command.';
        }

        return view('operation.show', compact('output', 'command'));
    }
}
