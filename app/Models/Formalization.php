<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\ReportStatus;
use App\Enums\DeliberationType;
use App\Traits\HasFiles;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Formalization extends Model implements Auditable
{
    use HasFactory, SoftDeletes, HasFiles, AuditableTrait;

    protected $fillable = [
        'project_id',
        'asjur_finalistic_processing_date',
        'asjur_received_at',
        'process_supervisor_id',
        'report_status',
        'eparcerias_certificate_date',
        'term_number',
        'term_signature_sent_at',
        'sent_to_office_at',
        'term_signed_at',
        'asjur_processing_date',
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

    protected $casts = [
        'asjur_finalistic_processing_date' => 'date',
        'asjur_received_at' => 'date',
        'term_signature_sent_at' => 'date',
        'sent_to_office_at' => 'date',
        'term_signed_at' => 'date',
        'eparcerias_certificate_date' => 'date',
        'asjur_processing_date' => 'date',
        'sacc_registered_at' => 'date',
        'validity_start_at' => 'date',
        'validity_end_at' => 'date',
        'sent_to_chief_of_staff_at' => 'date',
        'official_gazette_published_at' => 'date',
        'legal_opinion_date' => 'date',
        'deleted_at' => 'datetime',
        'report_status' => ReportStatus::class,
        'deliberation' => DeliberationType::class,
    ];

    
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
