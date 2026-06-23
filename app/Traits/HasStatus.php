<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * @method save()
 */
trait HasStatus
{
    protected string $status_attribute = 'status';
    protected string $status_filed = 'active';


    public function ChangeStatus(): void
    {
        $this->{$this->status_attribute} = !$this->{$this->status_attribute};
        $this->save();
        if (method_exists($this, 'logs')) {
            $this->logs()->create([
                'action' => 'status_changed',
                'old_values' => ['status' => !$this->{$this->status_attribute}],
                'new_values' => ['status' => $this->{$this->status_attribute}],
                'user_id' => auth()->id() ?? null,
            ]);

        }

    }

    public function getStatusWithSpan(): string
    {
        if (!$this->{$this->status_attribute}) return "<span class='badge badge-light-warning'> " . __('translation.in' . $this->status_filed) . " </span>";
        else  return "<span class='badge badge-light-success'> " . __('translation.' . $this->status_filed) . "</span>";
    }

    public function scopeActive($query)
    {
        return $query->where($this->status_attribute, 1);
    }
}
