<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class RecipeImportService
{
    /**
     * @return array{title: string, description: string, ingredients: array<string>, instructions: array<string>}
     */
    public function fromUrl(string $url): array
    {
        $html = Http::get($url)->body();
        // Find contents of <script type="ld+json">
        preg_match_all(
            '/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si',
            $html,
            $matches
        );

        foreach ($matches[1] as $json) {
            $data = json_decode($json, true);

            if (! is_array($data)) {
                continue;
            }

            if (array_is_list($data)) {
                // JSON-LD root is an array of schema objects e.g. [{...}, {...}]
                $candidates = $data;
            } elseif (isset($data['@graph'])) {
                $candidates = $data['@graph'];
            } else {
                $candidates = [$data];
            }

            foreach ($candidates as $item) {
                if ($this->isRecipeSchema($item)) {
                    logger()->debug('RecipeImportService Recipe Mapping', ['url' => $url, 'recipe' => $this->mapToRecipe($item)]);

                    return $this->mapToRecipe($item);
                }
            }
        }

        throw new RuntimeException('No recipe data found at the provided URL.');
    }

    private function isRecipeSchema(mixed $data): bool
    {
        if (! is_array($data)) {
            return false;
        }

        $type = $data['@type'] ?? '';

        return is_array($type) ? in_array('Recipe', $type) : $type === 'Recipe';
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array{title: string, description: string, ingredients: array<string>, instructions: array<string>}
     */
    private function mapToRecipe(array $schema): array
    {
        return [
            'title' => $schema['name'] ?? '',
            'description' => strip_tags($schema['description'] ?? ''),
            'ingredients' => array_map(
                fn (string $ingredient) => $this->normalizeDecimals($ingredient),
                $schema['recipeIngredient'] ?? []
            ),
            'instructions' => $this->parseInstructions($schema['recipeInstructions'] ?? []),
        ];
    }

    /**
     * @param  array<mixed>  $instructions
     * @return array<string>
     */
    private function parseInstructions(array $instructions): array
    {
        $steps = [];

        foreach ($instructions as $item) {
            if (is_string($item)) {
                $steps[] = $this->normalizeDecimals(strip_tags($item));
            } elseif (is_array($item)) {
                if (($item['@type'] ?? '') === 'HowToSection' && isset($item['itemListElement'])) {
                    foreach ($item['itemListElement'] as $step) {
                        if (isset($step['text'])) {
                            $steps[] = $this->normalizeDecimals(strip_tags($step['text']));
                        }
                    }
                } elseif (isset($item['text'])) {
                    $steps[] = $this->normalizeDecimals(strip_tags($item['text']));
                }
            }
        }

        return array_values(array_filter($steps));
    }

    private function normalizeDecimals(string $text): string
    {
        return preg_replace_callback(
            '/\d+\.\d+/',
            fn (array $matches) => $this->decimalToFraction((float) $matches[0]),
            $text
        );
    }

    private function decimalToFraction(float $decimal): string
    {
        $whole = (int) $decimal;
        $fractional = $decimal - $whole;

        if (abs($fractional) < 0.005) {
            return (string) $whole;
        }

        $bestNumerator = 1;
        $bestDenominator = 1;
        $bestError = PHP_FLOAT_MAX;

        for ($denominator = 1; $denominator <= 16; $denominator++) {
            $numerator = (int) round($fractional * $denominator);
            $error = abs($fractional - $numerator / $denominator);

            if ($error < $bestError) {
                $bestError = $error;
                $bestNumerator = $numerator;
                $bestDenominator = $denominator;
            }
        }

        $gcd = $this->gcd($bestNumerator, $bestDenominator);
        $bestNumerator = (int) ($bestNumerator / $gcd);
        $bestDenominator = (int) ($bestDenominator / $gcd);

        $fraction = "{$bestNumerator}/{$bestDenominator}";

        return $whole > 0 ? "{$whole} {$fraction}" : $fraction;
    }

    private function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return abs($a);
    }
}
