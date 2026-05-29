<?php

namespace App\Models;

use App\Enums\DeliberationType;
use App\Enums\ReportStatus;
use App\Enums\TermStatus;
use App\Traits\HasCreatedBy;
use App\Traits\HasFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Formalization extends Model implements Auditable
{
    use AuditableTrait, HasCreatedBy, HasFactory, HasFiles, SoftDeletes;

    protected $fillable = [
        'project_id',
        'asjur_finalistic_processing_date',
        'asjur_received_at',
        'process_assigned_to',
        'report_status',
        'eparcerias_certificate_date',
        'asjur_processing_date',
        'responsible_at_asjur',
        'term_number',
        'term_signature_sent_at',
        'term_status',
        'term_signed_at',
        'sent_to_office_at',
        'signature_status_office',
        'signed_by_office_at',
        'sacc_number',
        'cge_atende_ticket',
        'deliberation',
        'sent_to_chief_of_staff_at',
        'official_gazette_published_at',
        'validity_start_at',
        'validity_end_at',
        'legal_opinion_date',
        'created_by',
    ];

    protected $casts = [
        'asjur_finalistic_processing_date' => 'date',
        'asjur_received_at' => 'date',
        'report_status' => ReportStatus::class,
        'eparcerias_certificate_date' => 'date',
        'asjur_processing_date' => 'date',
        'term_signature_sent_at' => 'date',
        'term_status' => TermStatus::class,
        'term_signed_at' => 'date',
        'sent_to_office_at' => 'date',
        'signature_status_office' => TermStatus::class,
        'signed_by_office_at' => 'date',
        'deliberation' => DeliberationType::class,
        'sent_to_chief_of_staff_at' => 'date',
        'official_gazette_published_at' => 'date',
        'validity_start_at' => 'date',
        'validity_end_at' => 'date',
        'legal_opinion_date' => 'date',
        'deleted_at' => 'datetime',
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
