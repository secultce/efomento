<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\FileResource;

class FormalizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'asjur_finalistic_processing_date' => $this->asjur_finalistic_processing_date,
            'asjur_received_at' => $this->asjur_received_at,
            'process_supervisor_id' => $this->process_supervisor_id,

            'report_status' => $this->report_status,
            'eparcerias_certificate_date' => $this->eparcerias_certificate_date,

            'term_number' => $this->term_number,
            'term_signature_sent_at' => $this->term_signature_sent_at,
            'sent_to_office_at' => $this->sent_to_office_at,
            'term_signed_at' => $this->term_signed_at,

            'asjur_processing_date' => $this->asjur_processing_date,

            'sacc_number' => $this->sacc_number,
            'cge_atende_ticket' => $this->cge_atende_ticket,
            'sacc_registered_at' => $this->sacc_registered_at,

            'deliberation' => $this->deliberation,

            'validity_start_at' => $this->validity_start_at,
            'validity_end_at' => $this->validity_end_at,

            'sent_to_chief_of_staff_at' => $this->sent_to_chief_of_staff_at,
            'official_gazette_published_at' => $this->official_gazette_published_at,
            'legal_opinion_date' => $this->legal_opinion_date,

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