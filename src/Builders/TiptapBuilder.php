<?php

declare(strict_types=1);

namespace RJP\Tiptap\Builders;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use RJP\Tiptap\Tiptap;
use Tiptap\Extensions\{
    Color,
    FontFamily,
    StarterKit,
    TextAlign
};

class TiptapBuilder
{
    private array|string|null $content = null;

    private array $extensions = [];

    private array $validationRules = [];

    private bool $shouldCache = true;

    public function __construct(
        private readonly Tiptap $service
    ) {}

    /**
     * Set the content to be processed.
     */
    public function content(array|string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Set the extensions to use.
     */
    public function extensions(array $extensions): self
    {
        $this->extensions = $extensions;

        return $this;
    }

    /**
     * Add a single extension.
     */
    public function extension(string $extensionClass, array $options = []): self
    {
        $this->extensions[$extensionClass] = $options;

        return $this;
    }

    /**
     * Add StarterKit extension with options.
     */
    public function starterKit(array $options = []): self
    {
        return $this->extension(StarterKit::class, $options);
    }

    /**
     * Add Color extension.
     */
    public function color(): self
    {
        return $this->extension(Color::class);
    }

    /**
     * Add FontFamily extension.
     */
    public function fontFamily(): self
    {
        return $this->extension(FontFamily::class);
    }

    /**
     * Add TextAlign extension with options.
     */
    public function textAlign(array $options = []): self
    {
        return $this->extension(TextAlign::class, $options);
    }

    /**
     * Set validation rules.
     */
    public function rules(array $rules): self
    {
        $this->validationRules = $rules;

        return $this;
    }

    /**
     * Add a validation rule.
     */
    public function rule(string $key, mixed $value): self
    {
        $this->validationRules[$key] = $value;

        return $this;
    }

    /**
     * Set max length validation rule.
     */
    public function maxLength(int $length): self
    {
        return $this->rule('max_length', $length);
    }

    /**
     * Set max depth validation rule.
     */
    public function maxDepth(int $depth): self
    {
        return $this->rule('max_depth', $depth);
    }

    /**
     * Set allowed tags validation rule.
     */
    public function allowedTags(array $tags): self
    {
        return $this->rule('allowed_tags', $tags);
    }

    /**
     * Disable caching for this operation.
     */
    public function withoutCache(): self
    {
        $this->shouldCache = false;

        return $this;
    }

    /**
     * Enable caching for this operation.
     */
    public function withCache(): self
    {
        $this->shouldCache = true;

        return $this;
    }

    /**
     * Parse JSON content to HTML.
     */
    public function toHtml(): string
    {
        $this->ensureContentExists();

        return $this->service->parseJson($this->content, $this->extensions);
    }

    /**
     * Parse HTML content to JSON.
     */
    public function toJson(): array
    {
        $this->ensureContentExists();

        if (is_array($this->content)) {
            return $this->content;
        }

        return $this->service->parseHtml($this->content, $this->extensions);
    }

    /**
     * Convert content to plain text.
     */
    public function toText(): string
    {
        $this->ensureContentExists();

        return $this->service->toText($this->content);
    }

    /**
     * Get content statistics.
     */
    public function stats(): Collection
    {
        $this->ensureContentExists();

        return collect($this->service->getStats($this->content));
    }

    /**
     * Get word count.
     */
    public function wordCount(): int
    {
        return $this->stats()->get('words', 0);
    }

    /**
     * Get character count.
     */
    public function characterCount(bool $includeSpaces = true): int
    {
        $key = $includeSpaces ? 'characters' : 'characters_no_spaces';

        return $this->stats()->get($key, 0);
    }

    /**
     * Get estimated reading time in minutes.
     */
    public function readingTime(): int
    {
        return $this->stats()->get('reading_time', 0);
    }

    /**
     * Validate the content.
     */
    public function validate(): bool
    {
        $this->ensureContentExists();

        return $this->service->validate($this->content, $this->validationRules);
    }

    /**
     * Validate content and throw exception if invalid.
     */
    public function validateOrFail(): self
    {
        if (! $this->validate()) {
            throw new InvalidArgumentException('Content validation failed');
        }

        return $this;
    }

    /**
     * Sanitize the content.
     */
    public function sanitize(): self
    {
        $this->ensureContentExists();

        $this->content = $this->service->sanitize($this->content, $this->extensions);

        return $this;
    }

    /**
     * Check if content is empty.
     */
    public function isEmpty(): bool
    {
        if (is_null($this->content)) {
            return true;
        }

        $text = $this->toText();

        return empty(trim($text));
    }

    /**
     * Check if content is not empty.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Apply a callback to the content.
     */
    public function tap(callable $callback): self
    {
        $callback($this);

        return $this;
    }

    /**
     * Apply a callback if condition is true.
     */
    public function when(bool $condition, callable $callback): self
    {
        if ($condition) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Apply a callback unless condition is true.
     */
    public function unless(bool $condition, callable $callback): self
    {
        return $this->when(! $condition, $callback);
    }

    /**
     * Create a new builder instance with the same configuration.
     */
    public function clone(): self
    {
        $clone = new self($this->service);
        $clone->extensions = $this->extensions;
        $clone->validationRules = $this->validationRules;
        $clone->shouldCache = $this->shouldCache;

        return $clone;
    }

    /**
     * Get the raw content.
     */
    public function raw(): array|string|null
    {
        return $this->content;
    }

    private function ensureContentExists(): void
    {
        if (is_null($this->content)) {
            throw new InvalidArgumentException('Content must be set before processing');
        }
    }
}
