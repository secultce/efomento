<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nup' => $this->nup,
            'name' => $this->name,
            'opportunity_url' => $this->opportunity_url,
            'external_id' => $this->external_id,
            'total_opportunity_amount' => [
                'amount' => $this->total_opportunity_amount,
                'currency' => 'BRL',
            ],
            'total_commitment_amount' => [
                'amount' => $this->total_commitment_amount,
                'currency' => 'BRL',
            ],
            'installments' => $this->installments,
            'instrument_type' => $this->instrument_type,
            'process_manager' => $this->process_manager,
            'process_manager_email' => $this->process_manager_email,
            'creditor_registration_nup' => $this->creditor_registration_nup,
            'creditor_registration_request_date' => optional($this->creditor_registration_request_date)->format('Y-m-d'),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}