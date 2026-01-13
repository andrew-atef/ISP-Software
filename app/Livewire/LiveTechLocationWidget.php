<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;

class LiveTechLocationWidget extends Component
{
    public User $record;

    public function getCoordinates()
    {
        // Force refresh from database to get latest coordinates
        $this->record->refresh();

        return [
            'lat' => (float) $this->record->current_lat,
            'lng' => (float) $this->record->current_lng,
            'last_seen' => $this->record->last_seen_at?->diffForHumans() ?? 'Never',
        ];
    }

    public function render()
    {
        return view('livewire.live-tech-location-widget');
    }
}
