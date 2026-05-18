<?php

namespace App\Modules\Consultation\Events;

use App\Modules\Consultation\Models\ConsultationMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ConsultationMessageSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * ConsultationMessageSubmitted constructor.
     *
     * @param ConsultationMessage $consultationMessage
     */
    public function __construct(
        public readonly ConsultationMessage $consultationMessage
    ) {
    }
}
