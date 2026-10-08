<?php

namespace App\Actions\Category;

use App\Models\Category;
use App\Services\ImageUploadService;
use Illuminate\Http\UploadedFile;

class StoreCategoryAction
{
    public const FOLDER = 'categories';

    public function __construct(private readonly ImageUploadService $images) {}

    /**
     * @param  array<string, mixed>  $data  validated fields without image / remove_image
     */
    public function execute(array $data, ?UploadedFile $image = null): Category
    {
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['image'] = $image ? $this->images->upload($image, self::FOLDER) : null;

        return Category::create($data);
    }
}
