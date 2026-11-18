<?php

namespace Tests\Unit;

use App\Enums\MonitoringReportRequestDeadline;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MonitoringReportRequestDeadlineTest extends TestCase
{
    #[DataProvider('deadlines')]
    public function test_it_maps_each_notice_rule_to_its_deadline(
        MonitoringReportRequestDeadline $deadline,
        int $expectedDays,
        string $expectedLabel,
    ): void {
        $this->assertSame($expectedDays, $deadline->days());
        $this->assertSame($expectedLabel, $deadline->label());
    }

    public static function deadlines(): array
    {
        return [
            'PNAB' => [MonitoringReportRequestDeadline::PNAB, 120, 'PNAB'],
            'Lei Aldir Blanc' => [
                MonitoringReportRequestDeadline::LEI_ALDIR_BLANC,
                120,
                'Lei Aldir Blanc',
            ],
            'Lei Paulo Gustavo' => [
                MonitoringReportRequestDeadline::LEI_PAULO_GUSTAVO,
                120,
                'Lei Paulo Gustavo',
            ],
            'Ciclos calendarizados' => [
                MonitoringReportRequestDeadline::CICLOS_CALENDARIZADOS,
                90,
                'Ciclos calendarizados',
            ],
            'Mecenas' => [MonitoringReportRequestDeadline::MECENAS, 240, 'Mecenas'],
        ];
    }
}
