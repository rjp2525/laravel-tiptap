<?php

declare(strict_types=1);

use RJP\Tiptap\Builders\TiptapBuilder;
use RJP\Tiptap\Contracts\TiptapInterface;

beforeEach(function (): void {
    $this->service = resolve(TiptapInterface::class);
});

it('can create a builder instance', function (): void {
    $builder = $this->service->make();

    expect($builder)->toBeInstanceOf(TiptapBuilder::class);
});

it('can parse JSON to HTML', function (): void {
    $json = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Hello world',
                    ],
                ],
            ],
        ],
    ];

    $html = $this->service->parseJson($json);

    expect($html)->toContain('Hello world');
});

it('can parse HTML to JSON', function (): void {
    $html = '<p>Hello world</p>';

    $json = $this->service->parseHtml($html);

    expect($json)
        ->toBeArray()
        ->and($json['type'])->toBe('doc')
        ->and($json['content'][0]['type'])->toBe('paragraph');
});

it('can validate content with max length', function (): void {
    $content = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Short text',
                    ],
                ],
            ],
        ],
    ];

    expect($this->service->validate($content, ['max_length' => 100]))->toBeTrue()
        ->and($this->service->validate($content, ['max_length' => 5]))->toBeFalse();
});

it('can validate content with max depth', function (): void {
    $content = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Hello',
                    ],
                ],
            ],
        ],
    ];

    expect($this->service->validate($content, ['max_depth' => 3]))->toBeTrue()
        ->and($this->service->validate($content, ['max_depth' => 1]))->toBeFalse();
});

it('can sanitize content', function (): void {
    $content = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Hello world',
                    ],
                ],
            ],
        ],
    ];

    $sanitized = $this->service->sanitize($content);

    expect($sanitized)->toBeArray();
});

it('can convert content to text', function (): void {
    $content = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Hello world',
                    ],
                ],
            ],
        ],
    ];

    $text = $this->service->toText($content);

    expect($text)->toBe('Hello world');
});

it('can get content statistics', function (): void {
    $content = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Hello world test',
                    ],
                ],
            ],
        ],
    ];

    $stats = $this->service->getStats($content);

    expect($stats)
        ->toHaveKey('characters')
        ->toHaveKey('words')
        ->toHaveKey('paragraphs')
        ->toHaveKey('reading_time')
        ->and($stats['words'])->toBe(3)
        ->and($stats['paragraphs'])->toBe(1);
});

it('uses cache when enabled', function (): void {
    config()->set('tiptap.cache.enabled', true);

    $content = ['type' => 'doc', 'content' => []];

    // First call should hit the cache store
    $result1 = $this->service->parseJson($content);
    $result2 = $this->service->parseJson($content);

    expect($result1)->toBe($result2);
});
