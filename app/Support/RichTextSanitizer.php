<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class RichTextSanitizer
{
    private const ALLOWED_TAGS = [
        'a', 'b', 'blockquote', 'br', 'code', 'div', 'em', 'h1', 'h2', 'h3',
        'h4', 'h5', 'h6', 'i', 'img', 'li', 'ol', 'p', 'pre', 's', 'span',
        'strike', 'strong', 'u', 'ul',
    ];

    private const DROP_WITH_CONTENT = [
        'base', 'button', 'embed', 'form', 'iframe', 'input', 'link', 'math',
        'meta', 'object', 'option', 'script', 'select', 'style', 'svg', 'textarea',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'target', 'title'],
        'img' => ['alt', 'height', 'src', 'title', 'width'],
        'ol' => ['start', 'type'],
        'li' => ['value'],
    ];

    public static function sanitize(string $html): string
    {
        if (! preg_match('/<[a-z][\s\S]*>/i', $html)) {
            return nl2br(htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="rich-text-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        $root = $document->getElementById('rich-text-root');

        if (! $root) {
            return '';
        }

        self::sanitizeChildren($root);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    private static function sanitizeChildren(DOMNode $parent): void
    {
        for ($node = $parent->firstChild; $node !== null;) {
            $next = $node->nextSibling;

            if (! $node instanceof DOMElement && $node->nodeType !== XML_TEXT_NODE) {
                $parent->removeChild($node);
                $node = $next;

                continue;
            }

            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);

                if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                    $parent->removeChild($node);
                    $node = $next;

                    continue;
                }

                self::sanitizeChildren($node);

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                    $node = $next;

                    continue;
                }

                self::sanitizeAttributes($node, $tag);
            }

            $node = $next;
        }
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = [...(self::ALLOWED_ATTRIBUTES[$tag] ?? []), 'style'];
        $attributeNames = [];

        foreach ($element->attributes as $attribute) {
            $attributeNames[] = $attribute->name;
        }

        foreach ($attributeNames as $name) {
            if (! in_array(strtolower($name), $allowed, true)) {
                $element->removeAttribute($name);
            }
        }

        if ($element->hasAttribute('href') && ! self::isSafeUrl($element->getAttribute('href'), false)) {
            $element->removeAttribute('href');
        }

        if ($element->hasAttribute('src') && ! self::isSafeUrl($element->getAttribute('src'), true)) {
            $element->removeAttribute('src');
        }

        if (
            $element->hasAttribute('target')
            && ! in_array(strtolower($element->getAttribute('target')), ['_blank', '_self'], true)
        ) {
            $element->removeAttribute('target');
        }

        if ($element->hasAttribute('style')) {
            $style = self::sanitizeStyle($element->getAttribute('style'));

            if ($style === '') {
                $element->removeAttribute('style');
            } else {
                $element->setAttribute('style', $style);
            }
        }

        if ($tag === 'a' && strtolower($element->getAttribute('target')) === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function isSafeUrl(string $url, bool $allowImageData): bool
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($url === '') {
            return false;
        }

        if ($allowImageData && preg_match('/^data:image\/(?:gif|jpe?g|png|webp);base64,[a-z0-9+\/=\s]+$/i', $url)) {
            return true;
        }

        if (! $allowImageData && str_starts_with($url, '#')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $allowedSchemes = $allowImageData ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel'];

        return in_array($scheme, $allowedSchemes, true);
    }

    private static function sanitizeStyle(string $style): string
    {
        $allowed = [];

        foreach (explode(';', $style) as $declaration) {
            [$property, $value] = array_pad(explode(':', $declaration, 2), 2, null);
            $property = strtolower(trim((string) $property));
            $value = trim((string) $value);

            $valid = match ($property) {
                'color', 'background-color' => (bool) preg_match(
                    '/^(?:#[0-9a-f]{3,8}|[a-z]+|(?:rgb|rgba|hsl|hsla)\([0-9.,%\s]+\))$/i',
                    $value,
                ),
                'font-style' => (bool) preg_match('/^(?:normal|italic|oblique)$/i', $value),
                'font-weight' => (bool) preg_match('/^(?:normal|bold|[1-9]00)$/i', $value),
                'text-align' => (bool) preg_match('/^(?:left|right|center|justify|start|end)$/i', $value),
                'text-decoration' => (bool) preg_match('/^(?:none|underline|line-through)(?:\s+(?:underline|line-through))*$/i', $value),
                default => false,
            };

            if ($valid) {
                $allowed[] = $property.': '.$value;
            }
        }

        return implode('; ', $allowed);
    }
}
