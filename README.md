# Laravel Tiptap

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rjp2525/laravel-tiptap.svg?style=flat-square)](https://packagist.org/packages/rjp2525/laravel-tiptap)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/rjp2525/laravel-tiptap/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/rjp2525/laravel-tiptap/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/rjp2525/laravel-tiptap/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/rjp2525/laravel-tiptap/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/rjp2525/laravel-tiptap.svg?style=flat-square)](https://packagist.org/packages/rjp2525/laravel-tiptap)

A Laravel wrapper for the Tiptap PHP package that provides a fluent, Laravel-friendly interface for processing rich text content. Transforms JSON to HTML, HTML to JSON, validates content structure, extracts statistics etc. with an elegant API that feels like home in a Laravel app.

## Installation

You can install the package via composer:

```bash
composer require rjp2525/laravel-tiptap
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="tiptap-config"
```

This is the contents of the published config file:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Extensions
    |--------------------------------------------------------------------------
    |
    | Default extensions to be loaded when creating a new Tiptap instance.
    | Available extensions: StarterKit, Color, FontFamily, TextAlign
    |
    */
    'extensions' => [
        // Use StarterKit for basic functionality
        \Tiptap\Extensions\StarterKit::class => [],

        // Additional extensions (uncomment as needed)
        // \Tiptap\Extensions\Color::class => [],
        // \Tiptap\Extensions\FontFamily::class => [],
        // \Tiptap\Extensions\TextAlign::class => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for parsed content to improve performance.
    |
    */
    'cache' => [
        'enabled' => env('TIPTAP_CACHE_ENABLED', true),
        'store' => env('TIPTAP_CACHE_STORE', null), // null uses default cache store
        'ttl' => env('TIPTAP_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'tiptap',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Default validation rules for content validation
    |
    */
    'validation' => [
        'max_length' => 50000,
        'max_depth' => 10,
        'allowed_tags' => null, // null means all configured extensions are allowed
    ],
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-tiptap-views"
```

## Basic Usage

### Using the Facade

```php
use RJP\Tiptap\Facades\Tiptap;

// Parse JSON to HTML
$json = [
    'type' => 'doc',
    'content' => [
        [
            'type' => 'paragraph',
            'content' => [
                ['type' => 'text', 'text' => 'Hello '],
                ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'world!'],
            ]
        ]
    ]
];

$html = Tiptap::parseJson($json);
// Output: <p>Hello <strong>world!</strong></p>

// Parse HTML back to JSON
$json = Tiptap::parseHtml('<p>Hello <strong>world!</strong></p>');

// Validate content
$isValid = Tiptap::validate($json, ['max_length' => 1000]);

// Get content statistics
$stats = Tiptap::getStats($json);
// Returns: ['characters' => 12, 'words' => 2, 'paragraphs' => 1, 'reading_time' => 1]
```

### Using the Fluent Builder

```php
use RJP\Tiptap\Facades\Tiptap;

// Content processing with method chaining
$result = Tiptap::make()
    ->content($jsonContent)
    ->starterKit()
    ->color()
    ->textAlign(['types' => ['heading', 'paragraph']])
    ->maxLength(5000)
    ->maxDepth(10)
    ->validateOrFail()
    ->sanitize()
    ->toHtml();

// Content analysis
$stats = Tiptap::make()
    ->content($content)
    ->stats(); // Returns Collection

$wordCount = Tiptap::make()->content($content)->wordCount();
$readingTime = Tiptap::make()->content($content)->readingTime(); // in minutes
$isEmpty = Tiptap::make()->content($content)->isEmpty();
```

## Advanced Usage

### Extension Configuration

Configure extensions with specific options:

```php
// In config/tiptap.php or dynamically
$result = Tiptap::make()
    ->content($content)
    ->starterKit([
        'heading' => ['levels' => [1, 2, 3]], // Only H1-H3
        'bold' => false, // Disable bold
        'bulletList' => [],
        'orderedList' => [],
    ])
    ->color()
    ->textAlign([
        'types' => ['heading', 'paragraph'],
        'alignments' => ['left', 'center', 'right'],
    ])
    ->toHtml();
```

### Content Validation

```php
// Custom validation rules
$isValid = Tiptap::make()
    ->content($content)
    ->rules([
        'max_length' => 10000,
        'max_depth' => 8,
        'allowed_tags' => ['paragraph', 'heading', 'text', 'bold', 'italic']
    ])
    ->validate();

// Fluent validation methods
$builder = Tiptap::make()
    ->content($content)
    ->maxLength(5000)
    ->maxDepth(5)
    ->allowedTags(['paragraph', 'text', 'bold'])
    ->validateOrFail(); // Throws InvalidArgumentException if validation fails
```

### Conditional Processing

```php
$builder = Tiptap::make()
    ->content($content)
    ->starterKit()
    ->when($user->isPremium(), fn($b) => $b->color())
    ->when($user->canUseAdvancedFormatting(), fn($b) => $b->fontFamily())
    ->unless($content->isEmpty(), fn($b) => $b->sanitize())
    ->tap(function ($builder) {
        logger('Processing content', [
            'word_count' => $builder->wordCount(),
            'reading_time' => $builder->readingTime(),
        ]);
    });
```

### Caching

Enable caching for better performance with frequently processed content

```php
// In your .env file
TIPTAP_CACHE_ENABLED=true
TIPTAP_CACHE_TTL=3600
TIPTAP_CACHE_STORE=redis

// Or disable caching for specific operations
$result = Tiptap::make()
    ->content($content)
    ->withoutCache()
    ->toHtml();
```

## Laravel Integration Examples

### Form Request Validation

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use RJP\Tiptap\Facades\Tiptap;

class CreateArticleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'array', function ($attribute, $value, $fail) {
                if (!Tiptap::validate($value, ['max_length' => 50000, 'max_depth' => 10])) {
                    $fail('The content is invalid, too long, or too deeply nested.');
                }
            }],
        ];
    }
}
```

### Eloquent Model Integration

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use RJP\Tiptap\Facades\Tiptap;

class Article extends Model
{
    protected $fillable = ['title', 'content'];
    protected $casts = ['content' => 'array'];

    // Automatically generate HTML from JSON content
    protected function contentHtml(): Attribute
    {
        return Attribute::make(
            get: fn () => Tiptap::parseJson($this->content),
        );
    }

    // Get content statistics
    protected function contentStats(): Attribute
    {
        return Attribute::make(
            get: fn () => collect(Tiptap::getStats($this->content)),
        );
    }

    // Get plain text version
    protected function contentText(): Attribute
    {
        return Attribute::make(
            get: fn () => Tiptap::toText($this->content),
        );
    }

    // Sanitize content before saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($article) {
            if ($article->isDirty('content')) {
                $article->content = Tiptap::make()
                    ->content($article->content)
                    ->validateOrFail()
                    ->sanitize()
                    ->raw();
            }
        });
    }
}
```

### Controller Usage

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateArticleRequest;
use App\Models\Article;
use RJP\Tiptap\Facades\Tiptap;

class ArticleController extends Controller
{
    public function store(CreateArticleRequest $request)
    {
        $processedContent = Tiptap::make()
            ->content($request->validated('content'))
            ->starterKit()
            ->color()
            ->maxLength(50000)
            ->validateOrFail()
            ->sanitize()
            ->raw();

        $stats = Tiptap::make()
            ->content($processedContent)
            ->stats();

        $article = Article::create([
            'title' => $request->validated('title'),
            'content' => $processedContent,
            'word_count' => $stats['words'],
            'reading_time' => $stats['reading_time'],
        ]);

        return response()->json([
            'article' => $article,
            'stats' => $stats,
            'html_preview' => $article->content_html,
        ]);
    }
}
```

### Queued Jobs for Heavy Processing

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RJP\Tiptap\Facades\Tiptap;
use App\Models\Article;

class ProcessArticleContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private int $articleId,
        private array $content
    ) {}

    public function handle(): void
    {
        $processed = Tiptap::make()
            ->content($this->content)
            ->starterKit()
            ->color()
            ->textAlign()
            ->maxLength(100000)
            ->sanitize()
            ->raw();

        $stats = Tiptap::make()
            ->content($processed)
            ->stats();

        Article::where('id', $this->articleId)->update([
            'content' => $processed,
            'content_html' => Tiptap::parseJson($processed),
            'content_text' => Tiptap::toText($processed),
            'word_count' => $stats['words'],
            'character_count' => $stats['characters'],
            'reading_time' => $stats['reading_time'],
            'paragraph_count' => $stats['paragraphs'],
            'processed_at' => now(),
        ]);
    }
}
```

### Blade Components

```php
<?php

namespace App\View\Components;

use Illuminate\View\Component;
use RJP\Tiptap\Facades\Tiptap;

class TiptapContent extends Component
{
    public function __construct(
        public array|string $content,
        public bool $showStats = false,
        public bool $sanitize = true,
    ) {}

    public function render()
    {
        $builder = Tiptap::make()->content($this->content);

        if ($this->sanitize) {
            $builder->sanitize();
        }

        $html = is_array($this->content)
            ? $builder->toHtml()
            : $this->content;

        $stats = $this->showStats
            ? $builder->stats()
            : null;

        return view('components.tiptap-content', [
            'html' => $html,
            'stats' => $stats,
        ]);
    }
}
```

### Custom Extension Registration

While the Tiptap PHP package currently supports StarterKit, Color, FontFamily, and TextAlign extensions, you can register custom extensions by extending the service:

```php
// In a service provider
use RJP\Tiptap\Tiptap;

$this->app->extend(TiptapInterface::class, function (Tiptap $service) {
    // Add your custom extension logic here
    // Note: This requires extending the underlying Tiptap PHP package
    return $service;
});
```

For custom nodes and marks, you'll need to create them following the [Tiptap PHP documentation](https://github.com/ueberdosis/tiptap-php) and then register them through the configuration.

### Package Extensibility

#### Custom Service Implementation

You can create your own service implementation for advanced customization:

```php
<?php

namespace App\Services;

use RJP\Tiptap\Contracts\TiptapInterface;
use RJP\Tiptap\Tiptap;

class CustomTiptap implements TiptapInterface
{
    public function __construct(private Tiptap $tiptap) {}

    // Delegate to original service
    public function parseJson(array|string $json, array $extensions = []): string
    {
        return $this->tiptap->parseJson($json, $extensions);
    }

    // Add custom methods
    public function parseMarkdown(string $markdown): array
    {
        // Your markdown parsing logic
        $json = $this->convertMarkdownToTiptapJson($markdown);
        return $json;
    }

    public function extractImages(array|string $content): array
    {
        // Extract image nodes from content
        return $this->findImageNodes($content);
    }

    // Implement other interface methods...
}

// Register in AppServiceProvider
$this->app->bind(TiptapInterface::class, CustomTiptap::class);
```

#### Extending the Builder

The `TiptapBuilder` class is designed to be extended as well:

```php
<?php

namespace App\Services;

use RJP\Tiptap\Builders\TiptapBuilder;

class CustomTiptapBuilder extends TiptapBuilder
{
    /**
     * Add markdown support to the fluent interface.
     */
    public function markdown(string $markdown): self
    {
        $json = $this->convertMarkdownToJson($markdown);
        return $this->content($json);
    }

    /**
     * Enforce word limit validation.
     */
    public function wordLimit(int $limit): self
    {
        return $this->tap(function ($builder) use ($limit) {
            if ($builder->wordCount() > $limit) {
                throw new InvalidArgumentException("Content exceeds {$limit} word limit");
            }
        });
    }

    /**
     * Add reading level analysis.
     */
    public function readingLevel(): array
    {
        $text = $this->toText();
        return $this->calculateReadingLevel($text);
    }

    private function convertMarkdownToJson(string $markdown): array
    {
        // Your markdown to Tiptap JSON conversion logic
        return [];
    }

    private function calculateReadingLevel(string $text): array
    {
        // Your reading level calculation logic
        return ['grade' => 8, 'difficulty' => 'medium'];
    }
}
```

Then create a custom service that uses your builder:

```php
<?php

namespace App\Services;

use RJP\Tiptap\Tiptap;

class ExtendedTiptap extends Tiptap
{
    public function make(): CustomTiptapBuilder
    {
        return new CustomTiptapBuilder($this);
    }
}
```

### Builder Macros

Add methods to the existing builder without extending:

```php
// In a service provider boot method
use RJP\Tiptap\Builders\TiptapBuilder;

TiptapBuilder::macro('seoAnalysis', function () {
    return [
        'word_count' => $this->wordCount(),
        'reading_time' => $this->readingTime(),
        'headings' => $this->extractHeadings(),
        'images' => $this->extractImages(),
    ];
});

// Usage
$seoData = Tiptap::make()
    ->content($content)
    ->seoAnalysis();
```

### Available Extensions

The package currently supports these extensions from Tiptap PHP:
- **StarterKit:** Includes essential nodes (document, paragraph, text, heading, etc.) and marks (bold, italic, strike, etc.)
- **Color:** Text and background color support
- **FontFamily:** Font family formatting
- **TextAlign:** Text alignment (left, center, right, justify)

### API Reference

#### Facade Methods

```php
// Direct methods
Tiptap::parseJson(array|string $json, array $extensions = []): string
Tiptap::parseHtml(string $html, array $extensions = []): array
Tiptap::validate(array|string $content, array $rules = []): bool
Tiptap::sanitize(array|string $content, array $extensions = []): array|string
Tiptap::toText(array|string $content): string
Tiptap::getStats(array|string $content): array

// Builder factory
Tiptap::make(): TiptapBuilder
```

#### Builder Methods

```php
// Content and extensions
->content(array|string $content)
->extensions(array $extensions)
->starterKit(array $options = [])
->color()
->fontFamily()
->textAlign(array $options = [])

// Validation
->rules(array $rules)
->maxLength(int $length)
->maxDepth(int $depth)
->allowedTags(array $tags)
->validate(): bool
->validateOrFail(): self

// Processing
->sanitize(): self
->withCache() / ->withoutCache()

// Output
->toHtml(): string
->toJson(): array
->toText(): string
->stats(): Collection
->wordCount(): int
->characterCount(bool $includeSpaces = true): int
->readingTime(): int
->isEmpty() / ->isNotEmpty(): bool
->raw(): array|string|null

// Utilities
->when(bool $condition, callable $callback): self
->unless(bool $condition, callable $callback): self
->tap(callable $callback): self
->clone(): self
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [rjp2525](https://github.com/rjp2525)
- Built on top of the [official Tiptap PHP](https://github.com/ueberdosis/tiptap-php) by [ueberdosis](https://github.com/ueberdosis)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
