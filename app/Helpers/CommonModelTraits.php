<?php

namespace App\Helpers;

use App\Models\Activity;
use App\Models\Comment;
use App\Models\Workflow;
use App\Models\WorkflowApproval;
use Illuminate\Support\Facades\Auth;

trait CommonModelTraits
{
    /**
     * Summary of findActive
     * @param int $state
     * @return mixed
     */
    public static function findActive($state = 1)
    {
        return self::where('state_id', $state);
    }

    /**
     * Summary of bootMyModelTrait
     * @return void
     */
    public static function bootMyModelTrait()
    {
        static::creating(function ($model) {
            $model->created_by = !empty(Auth::user()) ? Auth::user()->id : 1;
        });
    }

    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->logActivity('created', $model->toArray());
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            $original = $model->getOriginal();

            $changesData = [];

            foreach ($changes as $key => $newValue) {
                if (in_array($key, ['created_at', 'updated_at'])) {
                    continue;
                }
                $oldValue = isset($original[$key]) ? $original[$key] : null;
                // if ($key == 'state_id' && !is_null($oldValue)) {
                //     $validatedData['description'] = "State updated from " . (isset($model->stateOptions()[$oldValue]) ? $model->stateOptions()[$oldValue] : $oldValue) .
                //         " to " . (isset($model->stateOptions()[$newValue]) ? $model->stateOptions()[$newValue] : $newValue);
                //     $validatedData['model_id'] = $model->id;
                //     $validatedData['model_type'] = get_class($model);
                //     Comment::create($validatedData);
                // }
                $changesData[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }

            $model->logActivity('updated', $changesData);
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted', $model->toArray());
        });
    }

    public function logActivity($action, $data)
    {
        // Activity::create([
        //     'model' => get_class($this),
        //     'model_id' => $this->id,
        //     'action' => $action,
        //     'data' => json_encode($data),
        // ]);
    }
}
