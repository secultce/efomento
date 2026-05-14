<?php

use App\Enums\DeliberationType;
use App\Enums\ReportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formalizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->unique()
                ->constrained()
                ->nullOnDelete();
            $table->date('asjur_finalistic_processing_date')->nullable();
            $table->date('asjur_received_at')->nullable();
            $table->foreignId('process_supervisor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->enum('report_status', ReportStatus::cases())->default(ReportStatus::SEM_CADASTRO->value);
            $table->date('eparcerias_certificate_date')->nullable();

            $table->string('term_number');
            $table->date('term_signature_sent_at')->nullable();
            $table->date('sent_to_office_at')->nullable();
            $table->date('term_signed_at')->nullable();

            $table->date('asjur_processing_date')->nullable();

            $table->string('sacc_number')->nullable();
            $table->string('cge_atende_ticket')->nullable();
            $table->date('sacc_registered_at')->nullable();

            $table->enum('deliberation', DeliberationType::cases())->default(DeliberationType::MANUAL->value);

            $table->date('validity_start_at')->nullable();
            $table->date('validity_end_at')->nullable();

            $table->date('sent_to_chief_of_staff_at')->nullable();
            $table->date('official_gazette_published_at')->nullable();
            $table->date('legal_opinion_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index('created_by');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formalizations');
    }
};
