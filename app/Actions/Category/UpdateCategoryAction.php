<?php

namespace App\Actions\Category;

use App\Models\Category;
use App\Services\ImageUploadService;
use Illuminate\Http\UploadedFile;

class UpdateCategoryAction
{
    public function __construct(private readonly ImageUploadService $images) {}

    /**
     * @param  array<string, mixed>  $data  validated fields without image / remove_image
     */
    public function execute(Category $category, array $data, ?UploadedFile $image = null, bool $removeImage = false): Category
    {
        $oldImage = $category->image;

        $data['sort_order'] = $data['sort_order'] ?? 0;

        if ($image) {
            $data['image'] = $this->images->upload($image, StoreCategoryAction::FOLDER);
        } elseif ($removeImage) {
            $data['image'] = null;
        }

        $category->update($data);

        // Delete the previous file only after the new state is saved.
        if (array_key_exists('image', $data)) {
            $this->images->delete($oldImage);
        }

        return $category;
    }
}
