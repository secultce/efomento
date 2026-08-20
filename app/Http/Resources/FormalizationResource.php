<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormalizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,

            'asjur_finalistic_processing_date' => $this->asjur_finalistic_processing_date,
            'asjur_received_at' => $this->asjur_received_at,
            'process_assigned_to' => $this->process_assigned_to,
            'report_status' => $this->report_status?->value,
            'eparcerias_certificate_date' => $this->eparcerias_certificate_date,

            'asjur_processing_date' => $this->asjur_processing_date,
            'term_number' => $this->term_number,

            'term_signed_at' => $this->term_signed_at,
            'sent_to_office_at' => $this->sent_to_office_at,
            'signed_by_office_at' => $this->signed_by_office_at,

            'sacc_number' => $this->sacc_number,
            'cge_atende_ticket' => $this->cge_atende_ticket?->value,
            'deliberation' => $this->deliberation?->value,

            'sent_to_chief_of_staff_at' => $this->sent_to_chief_of_staff_at,
            'official_gazette_published_at' => $this->official_gazette_published_at,

            'validity_start_at' => $this->validity_start_at,
            'validity_end_at' => $this->validity_end_at,

            'files' => $this->whenLoaded('files', function () {
                return $this->files
                    ->groupBy('grp')
                    ->map(fn ($group) => FileResource::collection($group));
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
