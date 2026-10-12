<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductApprovalStatus;
use Illuminate\Validation\Rule;

/**
 * DataTables request for the admin products list, plus its filters:
 * vendor ("inhouse" or a vendor id), category and approval status.
 */
class ProductTableRequest extends TableRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'vendor' => ['nullable', 'string', 'max:20', 'regex:/^(inhouse|\d+)$/'],
            'category_id' => ['nullable', 'integer'],
            'approval_status' => ['nullable', Rule::enum(ProductApprovalStatus::class)],
        ];
    }
}
