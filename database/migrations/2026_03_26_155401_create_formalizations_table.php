<?php

use App\Enums\CgeAtendeStatus;
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
            $table->foreignId('process_assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('report_status', ReportStatus::cases())->nullable()->default(ReportStatus::SEM_CADASTRO->value);
            $table->date('eparcerias_certificate_date')->nullable();

            $table->date('asjur_processing_date')->nullable();
            $table->string('term_number')->nullable();

            $table->date('term_signed_at')->nullable();
            $table->date('sent_to_office_at')->nullable();
            $table->date('signed_by_office_at')->nullable();

            $table->string('sacc_number')->nullable();
            $table->enum('cge_atende_ticket', CgeAtendeStatus::cases())->nullable();
            $table->enum('deliberation', DeliberationType::cases())->nullable()->default(DeliberationType::MANUAL->value);

            $table->date('sent_to_chief_of_staff_at')->nullable();
            $table->date('official_gazette_published_at')->nullable();

            $table->date('validity_start_at')->nullable();
            $table->date('validity_end_at')->nullable();

            $table->date('data_sign_gabinete')->nullable();
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
