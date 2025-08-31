<?php

declare(strict_types=1);

namespace RJP\Tiptap\Facades;

use Illuminate\Support\Facades\Facade;
use RJP\Tiptap\Builders\TiptapBuilder;

/**
 * @method static TiptapBuilder make()
 * @method static string parseJson(array|string $json, array $extensions = [])
 * @method static array parseHtml(string $html, array $extensions = [])
 * @method static bool validate(array|string $content, array $rules = [])
 * @method static array|string sanitize(array|string $content, array $extensions = [])
 * @method static string toText(array|string $content)
 * @method static array getStats(array|string $content)
 *
 * @see \RJP\Tiptap\Contracts\TiptapInterface
 */
final class Tiptap extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'tiptap';
    }
}
