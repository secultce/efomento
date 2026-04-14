<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Installment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InstallmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_an_installment()
    {
        $installment = Installment::factory()->create([
            'installment_number' => 1
        ]);

        $this->assertDatabaseHas('installments', [
            'id' => $installment->id,
        ]);
    }

    public function test_it_belongs_to_a_budget()
    {
        $installment = Installment::factory()->create([
            'installment_number' => 1
        ]);

        $this->assertInstanceOf(Budget::class, $installment->budget);
    }

    public function test_amount_is_casted_correctly()
    {
        $installment = Installment::factory()->create([
            'amount' => 1234.56,
            'installment_number' => 1
        ]);

        $this->assertEquals('1234.56', $installment->amount);
    }

    public function test_request_date_is_casted_to_date()
    {
        $installment = Installment::factory()->create([
            'installment_number' => 1
        ]);

        $this->assertInstanceOf(Carbon::class, $installment->request_date);
    }
}
