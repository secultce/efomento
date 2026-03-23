<?php

namespace Tests\Feature\Category;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_nao_pode_ter_duas_categorias_com_mesmo_nome(): void
    {
        Category::factory()->create(['name' => 'PROJETOS R$ 30.000,00']);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::
        class);

        Category::factory()->create(['name' => 'PROJETOS R$ 30.000,00']);
    }

}
