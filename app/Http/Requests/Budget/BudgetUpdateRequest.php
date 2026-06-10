<?php

namespace App\Http\Requests\Budget;

class BudgetUpdateRequest extends BudgetStoreRequest
{
    public function authorize(): bool
    {
        return true;
    }
}
