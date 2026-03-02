<?php

namespace App\Services;

use App\Models\Item;
use Illuminate\Support\Facades\DB;

/**
 * Service for fuzzy matching extracted item names against the tenant's item master.
 *
 * Uses a tiered strategy:
 * 1. Exact name match (case-insensitive)
 * 2. MySQL FULLTEXT search
 * 3. LIKE + Levenshtein distance
 *
 * Returns the best match with a confidence level.
 */
class ItemMatchingService
{
    /**
     * Maximum Levenshtein distance to consider a match.
     */
    private const MAX_LEVENSHTEIN_DISTANCE = 5;

    /**
     * Find the best matching item in the tenant's item master for an extracted name.
     *
     * @param  string  $extractedName  The item name extracted from OCR
     * @param  int     $tenantId       The tenant to search within
     * @return array|null  Returns ['matched_item_id', 'match_confidence', 'matched_name'] or null
     */
    public function findBestMatch(string $extractedName, int $tenantId): ?array
    {
        $extractedName = trim($extractedName);

        if (empty($extractedName)) {
            return null;
        }

        // Strategy 1: Exact name match (case-insensitive)
        $exactMatch = $this->exactMatch($extractedName, $tenantId);
        if ($exactMatch) {
            return $exactMatch;
        }

        // Strategy 2: FULLTEXT search
        $fulltextMatch = $this->fulltextMatch($extractedName, $tenantId);
        if ($fulltextMatch) {
            return $fulltextMatch;
        }

        // Strategy 3: LIKE + Levenshtein distance
        $fuzzyMatch = $this->fuzzyMatch($extractedName, $tenantId);
        if ($fuzzyMatch) {
            return $fuzzyMatch;
        }

        return null;
    }

    /**
     * Strategy 1: Exact case-insensitive name match.
     */
    private function exactMatch(string $name, int $tenantId): ?array
    {
        $item = Item::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();

        if ($item) {
            return [
                'matched_item_id'  => $item->id,
                'match_confidence' => 'high',
                'matched_name'     => $item->name,
            ];
        }

        return null;
    }

    /**
     * Strategy 2: MySQL FULLTEXT search on name and composition.
     */
    private function fulltextMatch(string $name, int $tenantId): ?array
    {
        // Prepare the search term for boolean mode
        $searchTerm = $this->prepareFulltextTerm($name);

        $item = Item::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereRaw(
                'MATCH(name, composition) AGAINST(? IN BOOLEAN MODE)',
                [$searchTerm]
            )
            ->select('items.*')
            ->selectRaw(
                'MATCH(name, composition) AGAINST(? IN BOOLEAN MODE) as relevance_score',
                [$searchTerm]
            )
            ->orderByDesc('relevance_score')
            ->first();

        if ($item && $item->relevance_score > 0) {
            return [
                'matched_item_id'  => $item->id,
                'match_confidence' => 'medium',
                'matched_name'     => $item->name,
            ];
        }

        return null;
    }

    /**
     * Strategy 3: LIKE search followed by Levenshtein distance ranking.
     */
    private function fuzzyMatch(string $name, int $tenantId): ?array
    {
        // Extract significant keywords (remove common words)
        $keywords = $this->extractKeywords($name);

        if (empty($keywords)) {
            return null;
        }

        // Build LIKE query for each keyword
        $query = Item::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNull('deleted_at');

        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $keyword) {
                $q->orWhere('name', 'LIKE', "%{$keyword}%");
            }
        });

        $candidates = $query->limit(20)->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        // Rank candidates by Levenshtein distance
        $bestMatch = null;
        $bestDistance = PHP_INT_MAX;
        $normalizedName = mb_strtolower($name);

        foreach ($candidates as $candidate) {
            $candidateName = mb_strtolower($candidate->name);
            $distance = levenshtein($normalizedName, $candidateName);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $bestMatch = $candidate;
            }
        }

        // Only return if within acceptable distance
        if ($bestMatch && $bestDistance <= self::MAX_LEVENSHTEIN_DISTANCE) {
            return [
                'matched_item_id'  => $bestMatch->id,
                'match_confidence' => 'low',
                'matched_name'     => $bestMatch->name,
            ];
        }

        return null;
    }

    /**
     * Prepare a FULLTEXT search term from a product name.
     * Adds wildcard (*) to each word for boolean mode.
     */
    private function prepareFulltextTerm(string $name): string
    {
        $words = preg_split('/[\s\-\/]+/', $name);
        $terms = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) >= 3) {
                $terms[] = '+' . $word . '*';
            }
        }

        return implode(' ', $terms);
    }

    /**
     * Extract significant keywords from an item name.
     * Removes common words, units, and short words.
     */
    private function extractKeywords(string $name): array
    {
        $stopWords = [
            'tab', 'tabs', 'tablet', 'tablets', 'cap', 'caps', 'capsule', 'capsules',
            'syp', 'syrup', 'inj', 'injection', 'cream', 'oint', 'ointment',
            'drop', 'drops', 'gel', 'lotion', 'powder', 'sachet', 'strip',
            'mg', 'ml', 'gm', 'mcg', 'iu', 'sr', 'er', 'xr', 'cr', 'od',
            'ip', 'bp', 'usp', 'of', 'the', 'and', 'for', 'with',
        ];

        $words = preg_split('/[\s\-\/\(\)]+/', mb_strtolower($name));
        $keywords = [];

        foreach ($words as $word) {
            $word = trim($word);
            if (mb_strlen($word) >= 3 && ! in_array($word, $stopWords) && ! is_numeric($word)) {
                $keywords[] = $word;
            }
        }

        return $keywords;
    }
}
