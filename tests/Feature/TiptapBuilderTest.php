<?php

declare(strict_types=1);

use RJP\Tiptap\Builders\TiptapBuilder;
use RJP\Tiptap\Facades\Tiptap;
use Tiptap\Extensions\StarterKit;

it('can chain methods fluently', function (): void {
    $content = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Hello world this is a test',
                    ],
                ],
            ],
        ],
    ];

    $result = Tiptap::make()
        ->content($content)
        ->starterKit()
        ->maxLength(100)
        ->maxDepth(5);

    expect($result->validate())->toBeTrue()
        ->and($result->wordCount())->toBe(6)
        ->and($result->characterCount())->toBeGreaterThan(0)
        ->and($result->isEmpty())->toBeFalse()
        ->and($result->isNotEmpty())->toBeTrue();
});

it('can convert between formats fluently', function (): void {
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

    $html = Tiptap::make()
        ->content($json)
        ->toHtml();

    $backToJson = Tiptap::make()
        ->content($html)
        ->toJson();

    expect($html)->toContain('Hello world')
        ->and($backToJson)->toBeArray();
});

it('can validate and sanitize content', function (): void {
    $content = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'Test content',
                    ],
                ],
            ],
        ],
    ];

    $builder = Tiptap::make()
        ->content($content)
        ->maxLength(50)
        ->validateOrFail()
        ->sanitize();

    expect($builder->validate())->toBeTrue()
        ->and($builder->raw())->toBeArray();
});

it('throws exception when validation fails', function (): void {
    $content = [
        'type' => 'doc',
        'content' => [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'This is a very long text that should exceed the maximum length',
                    ],
                ],
            ],
        ],
    ];

    expect(fn () => Tiptap::make()
        ->content($content)
        ->maxLength(10)
        ->validateOrFail())
        ->toThrow(InvalidArgumentException::class);
});

it('can use conditional methods', function (): void {
    $builder = Tiptap::make();

    $result = $builder
        ->when(true, fn ($b) => $b->maxLength(100))
        ->unless(false, fn ($b) => $b->maxDepth(5));

    // private properties via reflection for testing
    $reflection = new ReflectionClass($result);
    $rulesProperty = $reflection->getProperty('validationRules');
    $rulesProperty->setAccessible(true);
    $rules = $rulesProperty->getValue($result);

    expect($rules)
        ->toHaveKey('max_length', 100)
        ->toHaveKey('max_depth', 5);
});

it('can clone builder with same configuration', function (): void {
    $original = Tiptap::make()
        ->starterKit()
        ->maxLength(100);

    $clone = $original->clone();

    expect($clone)->not->toBe($original);

    // both should have the same configuration
    $reflection = new ReflectionClass($clone);
    $extensionsProperty = $reflection->getProperty('extensions');
    $extensionsProperty->setAccessible(true);

    expect($extensionsProperty->getValue($clone))
        ->toHaveKey(StarterKit::class);
});

it('can tap into builder for debugging', function (): void {
    $tapped = false;

    $result = Tiptap::make()
        ->content(['type' => 'doc'])
        ->tap(function ($builder) use (&$tapped) {
            $tapped = true;
            expect($builder->raw())->toBe(['type' => 'doc']);
        });

    expect($tapped)->toBeTrue()
        ->and($result)->toBeInstanceOf(TiptapBuilder::class);
});
