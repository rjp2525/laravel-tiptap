<?php

declare(strict_types=1);

namespace RJP\Tiptap\Contracts;

use RJP\Tiptap\Builders\TiptapBuilder;

interface TiptapInterface
{
    /**
     * Create a new Tiptap builder instance.
     */
    public function make(): TiptapBuilder;

    /**
     * Parse JSON content to HTML.
     */
    public function parseJson(array|string $json, array $extensions = []): string;

    /**
     * Parse HTML content to JSON.
     */
    public function parseHtml(string $html, array $extensions = []): array;

    /**
     * Validate content structure.
     */
    public function validate(array|string $content, array $rules = []): bool;

    /**
     * Clean and sanitize content.
     */
    public function sanitize(array|string $content, array $extensions = []): array|string;

    /**
     * Extract plain text from content.
     */
    public function toText(array|string $content): string;

    /**
     * Get content statistics (word count, character count, etc.).
     */
    public function getStats(array|string $content): array;
}
