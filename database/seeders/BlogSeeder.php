<?php

namespace Database\Seeders;

use App\Enums\BlogStatus;
use App\Enums\CommentStatus;
use App\Models\Admin;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Services\HtmlSanitizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Local sample data: 10 published blog posts in 4 categories, with tags and a few approved comments.
 * The template's blog images are copied to the public disk so they behave exactly like uploaded ones.
 * Safe to run again (posts are matched by slug). Run with: php artisan db:seed --class=BlogSeeder
 */
class BlogSeeder extends Seeder
{
    public function run(HtmlSanitizer $sanitizer): void
    {
        $author = Admin::query()->oldest('id')->first();

        $categories = collect(['Fashion', 'Lifestyle', "Women's", 'Technology'])
            ->mapWithKeys(fn (string $name, int $i) => [$name => BlogCategory::firstOrCreate(['name' => $name], ['sort_order' => $i + 1, 'is_active' => true])]);

        $posts = [
            ['New found the women’s shirt for summer season', "Women's", ['Women', 'Summer', 'Fashion']],
            ['Fusce lacinia arcuet nulla menasious', 'Lifestyle', ['Lifestyle', 'Hobbies']],
            ['Found the men’s shirt for summer season', 'Fashion', ['Men', 'Summer', 'Style']],
            ['Winter collection for women’s', "Women's", ['Women', 'Winter', 'Shopping']],
            ['Comes a cool blog post with images', 'Lifestyle', ['Photography', 'Lifestyle']],
            ['Fashion tells about who you are from external', 'Fashion', ['Fashion', 'Style']],
            ['One thing separates creators from consumers', 'Lifestyle', ['Hobbies', 'Travel']],
            ['Smart gadgets that make your mornings easier', 'Technology', ['Gadgets', 'Shopping']],
            ['How to choose the perfect sneakers', 'Fashion', ['Shoes', 'Men', 'Style']],
            ['Ten travel essentials for your next trip', 'Lifestyle', ['Travel', 'Shopping']],
        ];

        foreach ($posts as $index => [$title, $category, $tags]) {
            $number = $index + 1;
            // blog_1 ... blog_6 cycle; every third post uses the wide details picture.
            $source = public_path('frontend-assets/images/'.($number % 3 === 0 ? 'blog_details.jpg' : 'blog_'.(($index % 6) + 1).'.jpg'));

            if (! is_file($source)) {
                $this->command?->warn("Missing template image for “{$title}”; skipped.");

                continue;
            }

            $image = "blogs/sample-blog-{$number}.jpg";
            Storage::disk('public')->put($image, file_get_contents($source));

            $content = (string) $sanitizer->clean($this->content($title));

            $blog = Blog::withTrashed()->updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'blog_category_id' => $categories[$category]->id,
                    'admin_id' => $author?->id,
                    'title' => $title,
                    'image' => $image,
                    'excerpt' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Dicta perferendis cumque nam dolorum dignissimos nisi totam labore minus.',
                    'content' => $content,
                    'status' => BlogStatus::Published,
                    'published_at' => now()->subDays(11 - $number)->setTime(10, 0),
                    'show_on_home' => true,
                    'deleted_at' => null,
                ],
            );

            // Views are a counter, not a fillable field.
            Blog::query()->whereKey($blog->id)->update(['views' => 25 * (11 - $number) + $number]);

            $blog->tags()->sync(collect($tags)->map(fn (string $tag) => BlogTag::findOrCreateByName($tag)->id)->all());

            if ($number <= 3 && ! $blog->comments()->exists()) {
                $first = $blog->comments()->create([
                    'name' => 'Shopnil Mahadi',
                    'email' => 'shopnil@example.com',
                    'comment' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit. Cupiditate sint molestiae eos? Officia, fuga eaque.',
                    'status' => CommentStatus::Approved,
                ]);
                $blog->comments()->create([
                    'parent_id' => $first->id,
                    'name' => 'Smith Jhon',
                    'email' => 'smith@example.com',
                    'comment' => 'Totally agree. Thanks for sharing these tips!',
                    'status' => CommentStatus::Approved,
                ]);
            }
        }
    }

    /**
     * Template-style article body: paragraphs, a list, the template quote box and a sub heading.
     */
    private function content(string $title): string
    {
        $paragraph = '<p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Dicta perferendis cumque nam dolorum '
            .'dignissimos nisi totam labore minus deleniti fuga molestias ab quo necessitatibus provident, corrupti ipsam hic. '
            .'Dolorum tenetur nam fugiat molestiae veritatis sequi ad quia, id eos officiis aliquid numquam iusto sint iure eaque?</p>';

        return $paragraph
            .'<ul>'
            .'<li>Consectetur adipisicing elit. Voluptatum sapiente aliquam ut neque voluptatibus inventore odit nesciunt.</li>'
            .'<li>Delectus dignissimos maiores fuga doloremque magni, ratione provident exercitationem aliquam tempore velit facere.</li>'
            .'<li>Repudiandae qui sint? Delectus dignissimos maiores fuga doloremque magni, ratione provident exercitationem.</li>'
            .'</ul>'
            .$paragraph
            .'<div class="wsus__blog_quot_area"><i class="far fa-quote-left"></i><p>'.e($title).'</p><span>JELLY CRISTIANA</span></div>'
            .'<h5>Defaulting to Mindfulness: The Third Person Effect</h5>'
            .$paragraph
            .$paragraph;
    }
}
