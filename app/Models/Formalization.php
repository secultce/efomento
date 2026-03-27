<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasFiles;

class Formalization extends Model
{
    use HasFactory, SoftDeletes, HasFiles;

    protected $fillable = [
        'asjur_finalistic_processing_date',
        'asjur_received_at',
        'process_supervisor_id',
        'report_status',
        'eparcerias_certificate_date',
        'term_status',
        'term_number',
        'term_signature_sent_at',
        'sent_to_office_at',
        'term_signed_at',
        'asjur_processing_date',
        'office_signature_status',
        'sacc_number',
        'cge_atende_ticket',
        'sacc_registered_at',
        'deliberation',
        'validity_start_at',
        'validity_end_at',
        'sent_to_chief_of_staff_at',
        'official_gazette_published_at',
        'legal_opinion_date',
    ];

    protected $dates = [
        'asjur_finalistic_processing_date',
        'asjur_received_at',
        'term_signature_sent_at',
        'sent_to_office_at',
        'term_signed_at',
        'eparcerias_certificate_date',
        'asjur_processing_date',
        'sacc_registered_at',
        'validity_start_at',
        'validity_end_at',
        'sent_to_chief_of_staff_at',
        'official_gazette_published_at',
        'legal_opinion_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function commitmentTermFiles()
    {
        return $this->filesByGroup('commitment_term');
    }

    public function termSummaryFiles()
    {
        return $this->filesByGroup('term_summary');
    }

    public function officialGazetteFiles()
    {
        return $this->filesByGroup('official_gazette');
    }

    public function legalOpinionFiles()
    {
        return $this->filesByGroup('legal_opinion');
    }
    
}