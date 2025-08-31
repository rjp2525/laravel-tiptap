<?php

declare(strict_types=1);

namespace RJP\Tiptap;

use Illuminate\Support\Facades\Cache;
use RJP\Tiptap\Builders\TiptapBuilder;
use RJP\Tiptap\Contracts\TiptapInterface;
use Tiptap\Editor;
use Tiptap\Extensions\{
    Color,
    FontFamily,
    StarterKit,
    TextAlign
};

final class Tiptap implements TiptapInterface
{
    private array $defaultExtensions;

    public function __construct()
    {
        $this->defaultExtensions = config('tiptap.extensions', [
            StarterKit::class => [],
        ]);
    }

    public function make(): TiptapBuilder
    {
        return new TiptapBuilder($this);
    }

    public function parseJson(array|string $json, array $extensions = []): string
    {
        $extensionInstances = $this->resolveExtensions($extensions);
        $content = is_string($json) ? json_decode($json, true) : $json;

        if ($this->shouldCache()) {
            $cacheKey = $this->getCacheKey('json', $content, $extensions);

            return Cache::store($this->getCacheStore())
                ->remember($cacheKey, $this->getCacheTtl(), function () use ($content, $extensionInstances) {
                    return $this->createEditor($extensionInstances)->setContent($content)->getHTML();
                });
        }

        return $this->createEditor($extensionInstances)->setContent($content)->getHTML();
    }

    public function parseHtml(string $html, array $extensions = []): array
    {
        $extensionInstances = $this->resolveExtensions($extensions);

        if ($this->shouldCache()) {
            $cacheKey = $this->getCacheKey('html', $html, $extensions);

            return Cache::store($this->getCacheStore())
                ->remember($cacheKey, $this->getCacheTtl(), function () use ($html, $extensionInstances) {
                    $json = $this->createEditor($extensionInstances)->setContent($html)->getJSON();

                    return is_string($json) ? json_decode($json, true) : $json;
                });
        }

        $json = $this->createEditor($extensionInstances)->setContent($html)->getJSON();

        return is_string($json) ? json_decode($json, true) : $json;
    }

    public function validate(array|string $content, array $rules = []): bool
    {
        $rules = array_merge(config('tiptap.validation', []), $rules);
        $contentArray = is_string($content) ? json_decode($content, true) : $content;

        // Validate max length
        if (isset($rules['max_length']) && $this->getTextLength($contentArray) > $rules['max_length']) {
            return false;
        }

        // Validate depth
        if (isset($rules['max_depth']) && $this->getContentDepth($contentArray) > $rules['max_depth']) {
            return false;
        }

        // Validate allowed tags
        if (isset($rules['allowed_tags']) && ! $this->validateAllowedTags($contentArray, $rules['allowed_tags'])) {
            return false;
        }

        return true;
    }

    public function sanitize(array|string $content, array $extensions = []): array|string
    {
        $extensionInstances = $this->resolveExtensions($extensions);
        $isString = is_string($content);
        $contentArray = $isString ? json_decode($content, true) : $content;

        $json = $this->createEditor($extensionInstances)->setContent($contentArray)->getJSON();
        $sanitized = is_string($json) ? json_decode($json, true) : $json;

        return $isString ? json_encode($sanitized) : $sanitized;
    }

    public function toText(array|string $content): string
    {
        $contentArray = is_string($content) ? json_decode($content, true) : $content;

        return $this->createEditor()->setContent($contentArray)->getText();
    }

    public function getStats(array|string $content): array
    {
        $text = $this->toText($content);
        $contentArray = is_string($content) ? json_decode($content, true) : $content;

        return [
            'characters' => mb_strlen($text),
            'characters_no_spaces' => mb_strlen(str_replace(' ', '', $text)),
            'words' => str_word_count($text),
            'paragraphs' => $this->countParagraphs($contentArray),
            'reading_time' => ceil(str_word_count($text) / 200), // Average reading speed
        ];
    }

    private function createEditor(array $extensionInstances = []): Editor
    {
        if (empty($extensionInstances)) {
            $extensionInstances = $this->resolveExtensions([]);
        }

        return new Editor([
            'extensions' => $extensionInstances,
        ]);
    }

    private function resolveExtensions(array $extensions): array
    {
        if (empty($extensions)) {
            return $this->instantiateExtensions($this->defaultExtensions);
        }

        // If extensions are already instances, return them
        if (isset($extensions[0]) && is_object($extensions[0])) {
            return $extensions;
        }

        return $this->instantiateExtensions($extensions);
    }

    private function instantiateExtensions(array $extensionConfig): array
    {
        $instances = [];

        foreach ($extensionConfig as $extensionClass => $options) {
            if (is_numeric($extensionClass) && is_string($options)) {
                // Handle legacy string format: ['StarterKit'] -> [StarterKit::class => []]
                $extensionClass = $this->resolveExtensionClass($options);
                $options = [];
            }

            if (class_exists($extensionClass)) {
                $instances[] = new $extensionClass($options);
            }
        }

        return $instances;
    }

    private function resolveExtensionClass(string $name): string
    {
        $classMap = [
            'StarterKit' => StarterKit::class,
            'Color' => Color::class,
            'FontFamily' => FontFamily::class,
            'TextAlign' => TextAlign::class,
        ];

        return $classMap[$name] ?? $name;
    }

    private function shouldCache(): bool
    {
        return config('tiptap.cache.enabled', false);
    }

    private function getCacheStore(): ?string
    {
        return config('tiptap.cache.store');
    }

    private function getCacheTtl(): int
    {
        return config('tiptap.cache.ttl', 3600);
    }

    private function getCacheKey(string $type, mixed $content, array $extensions): string
    {
        $contentHash = is_array($content) ? md5(json_encode($content)) : md5($content);
        $extensionsHash = md5(serialize($extensions));

        return sprintf(
            '%s:%s:%s:%s',
            config('tiptap.cache.prefix', 'tiptap'),
            $type,
            $contentHash,
            $extensionsHash
        );
    }

    private function getTextLength(array $content): int
    {
        return mb_strlen($this->extractTextFromContent($content));
    }

    private function getContentDepth(array $content, int $currentDepth = 0): int
    {
        $maxDepth = $currentDepth;

        if (isset($content['content']) && is_array($content['content'])) {
            foreach ($content['content'] as $child) {
                $depth = $this->getContentDepth($child, $currentDepth + 1);
                $maxDepth = max($maxDepth, $depth);
            }
        }

        return $maxDepth;
    }

    private function validateAllowedTags(array $content, array $allowedTags): bool
    {
        if (isset($content['type']) && ! in_array($content['type'], $allowedTags)) {
            return false;
        }

        if (isset($content['content']) && is_array($content['content'])) {
            foreach ($content['content'] as $child) {
                if (! $this->validateAllowedTags($child, $allowedTags)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function extractTextFromContent(array $content): string
    {
        $text = '';

        if (isset($content['text'])) {
            $text .= $content['text'];
        }

        if (isset($content['content']) && is_array($content['content'])) {
            foreach ($content['content'] as $child) {
                $text .= $this->extractTextFromContent($child);
            }
        }

        return $text;
    }

    private function countParagraphs(array $content): int
    {
        $count = 0;

        if (isset($content['type']) && $content['type'] === 'paragraph') {
            $count++;
        }

        if (isset($content['content']) && is_array($content['content'])) {
            foreach ($content['content'] as $child) {
                $count += $this->countParagraphs($child);
            }
        }

        return $count;
    }
}
