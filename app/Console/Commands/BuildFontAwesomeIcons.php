<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Yaml\Yaml;

/**
 * Dev-time tool: turns Font Awesome Free's icon metadata into the local JSON file used by the admin
 * icon picker and by the server-side validation. It is never run at request time.
 *
 *   php artisan icons:build-fontawesome
 *   php artisan icons:build-fontawesome --source=path/to/icons.yml --output=path/to/icons.json
 *
 * The version must match the Font Awesome version the storefront renders (Free 5.15.1).
 */
class BuildFontAwesomeIcons extends Command
{
    protected $signature = 'icons:build-fontawesome
                            {--fa-version=5.15.1 : Font Awesome Free version of the metadata}
                            {--source= : Path or URL of icons.yml (defaults to the jsDelivr copy of the free package)}
                            {--output= : Where to write the JSON (defaults to public/admin-assets/data/fontawesome-icons.json)}';

    protected $description = 'Build the local Font Awesome Free icon list used by the admin icon picker';

    /** Free style => CSS class prefix. Pro-only styles (light, duotone, thin) are not available. */
    private const STYLES = ['solid' => 'fas', 'regular' => 'far', 'brands' => 'fab'];

    public function handle(): int
    {
        $version = (string) $this->option('fa-version');
        $source = $this->option('source')
            ?: "https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@{$version}/metadata/icons.yml";
        $output = $this->option('output') ?: public_path('admin-assets/data/fontawesome-icons.json');

        $yaml = $this->read($source);

        if ($yaml === null) {
            $this->error("Could not read {$source}");

            return self::FAILURE;
        }

        $metadata = Yaml::parse($yaml);
        $icons = [];

        foreach ($metadata as $name => $meta) {
            foreach (self::STYLES as $style => $prefix) {
                if (! in_array($style, $meta['styles'] ?? [], true)) {
                    continue;
                }

                $icons[] = [
                    'c' => "{$prefix} fa-{$name}",
                    'n' => (string) $name,
                    'l' => (string) ($meta['label'] ?? $name),
                    't' => $style,
                    's' => strtolower(implode(' ', array_map('strval', $meta['search']['terms'] ?? []))),
                ];
            }
        }

        usort($icons, fn (array $a, array $b) => [$a['n'], array_search($a['t'], array_keys(self::STYLES), true)]
            <=> [$b['n'], array_search($b['t'], array_keys(self::STYLES), true)]);

        if (! is_dir(dirname($output))) {
            mkdir(dirname($output), 0775, true);
        }

        file_put_contents($output, json_encode(['version' => $version, 'icons' => $icons], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $perStyle = collect($icons)->countBy('t');

        $this->info(sprintf(
            'Wrote %d icons (solid %d, regular %d, brands %d) for Font Awesome Free %s to %s',
            count($icons), $perStyle['solid'] ?? 0, $perStyle['regular'] ?? 0, $perStyle['brands'] ?? 0, $version, $output,
        ));

        return self::SUCCESS;
    }

    private function read(string $source): ?string
    {
        if (preg_match('#^https?://#', $source)) {
            $response = Http::timeout(60)->get($source);

            return $response->successful() ? $response->body() : null;
        }

        return is_file($source) ? file_get_contents($source) : null;
    }
}
