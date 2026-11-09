<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;
use InvalidArgumentException;
use Illuminate\Support\Str;

/**
 * Turns an uploaded .html form into (a) a sanitized HTML fragment and
 * (b) a schema describing every input, so submissions can be validated and reported on.
 *
 * Conventions that make reports nicer (all optional):
 *   <tr class="section"><td>1. Facilities</td></tr>     -> groups rating questions
 *   <tr data-label="Question text"> ... radios 5..1/NA  -> question label
 *   radio groups whose values are 5,4,3,2,1 (and NA)     -> detected as rating questions
 */
class TemplateParser
{
    private const ALLOWED_TAGS = [
        'div', 'span', 'p', 'br', 'hr', 'b', 'strong', 'i', 'em', 'u', 'small', 'sup', 'sub',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'center', 'section', 'header', 'footer',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'colgroup', 'col', 'caption',
        'label', 'input', 'select', 'option', 'optgroup', 'textarea', 'fieldset', 'legend', 'img', 'a',
    ];

    // Removed together with their content.
    private const DROP_TAGS = [
        'script', 'iframe', 'object', 'embed', 'link', 'meta', 'base', 'svg', 'math', 'button',
        'style', 'noscript', 'template', 'frame', 'frameset', 'applet', 'audio', 'video', 'canvas', 'title',
    ];

    private const ALLOWED_ATTRS = [
        'class', 'id', 'colspan', 'rowspan', 'align', 'valign', 'width', 'height', 'title', 'for',
        'name', 'value', 'type', 'placeholder', 'rows', 'cols', 'maxlength', 'required', 'min', 'max',
        'step', 'lang', 'dir', 'style', 'alt', 'src', 'href', 'role', 'aria-label',
    ];

    private const INPUT_TYPES = ['text', 'radio', 'checkbox', 'date', 'number', 'email', 'tel', 'time'];

    private const RESERVED_NAMES = ['contact_url_hp', 'website'];

    /**
     * @return array{html: string, schema: array}
     */
    public function process(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            throw new InvalidArgumentException('The file is empty.');
        }

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">'.$raw, LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$loaded || !$body) {
            throw new InvalidArgumentException('Could not read the HTML file.');
        }

        // Collect <style> blocks (anywhere), then sanitize the body.
        $css = '';
        foreach ($dom->getElementsByTagName('style') as $style) {
            $css .= $style->textContent."\n";
        }
        $css = $this->cleanStylesheet($css);

        $this->clean($body);

        $schema = $this->buildSchema($dom);
        if (count($schema['fields']) === 0) {
            throw new InvalidArgumentException(
                'No usable input fields were found. Use <input type="radio|checkbox|text|date|number|email|tel|time">, <select> or <textarea> elements that each have a name attribute.'
            );
        }

        $html = '';
        foreach ($body->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }
        if ($css !== '') {
            $html = '<style>'.$css.'</style>'.$html;
        }

        return ['html' => $html, 'schema' => $schema];
    }

    /* ---------------------------------------------------------------- sanitizing */

    private function clean(DOMNode $parent): void
    {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, self::DROP_TAGS, true)) {
                    $parent->removeChild($child);
                    continue;
                }

                // <form> and unknown tags are unwrapped: children are kept, the tag is dropped.
                if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                    $this->clean($child);
                    while ($child->firstChild) {
                        $parent->insertBefore($child->firstChild, $child);
                    }
                    $parent->removeChild($child);
                    continue;
                }

                if (!$this->cleanElement($child, $tag)) {
                    $parent->removeChild($child);
                    continue;
                }

                $this->clean($child);
            } elseif (!($child instanceof DOMText)) {
                $parent->removeChild($child); // comments, processing instructions
            }
        }
    }

    /** Returns false when the element itself must be removed. */
    private function cleanElement(DOMElement $el, string $tag): bool
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            $value = trim($attr->value);

            $allowed = in_array($name, self::ALLOWED_ATTRS, true) || str_starts_with($name, 'data-');
            if (!$allowed || str_starts_with($name, 'on')) {
                $el->removeAttribute($attr->name);
                continue;
            }

            switch ($name) {
                case 'href':
                    if (!preg_match('#^(https?://|mailto:|\#)#i', $value)) {
                        $el->removeAttribute($attr->name);
                    } elseif ($tag === 'a') {
                        $el->setAttribute('rel', 'noopener noreferrer');
                        $el->setAttribute('target', '_blank');
                    }
                    break;

                case 'src':
                    if ($tag !== 'img' || !preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[A-Za-z0-9+/=\s]+$#i', $value)) {
                        $el->removeAttribute($attr->name);
                    }
                    break;

                case 'style':
                    if (preg_match('/(expression\s*\(|javascript:|behaviou?r\s*:|-moz-binding|@import|url\s*\()/i', $value)) {
                        $el->removeAttribute($attr->name);
                    }
                    break;

                case 'name':
                    if (!preg_match('/^[A-Za-z][A-Za-z0-9_\-]{0,63}(\[\])?$/', $value)
                        || in_array(strtolower(rtrim($value, '[]')), self::RESERVED_NAMES, true)) {
                        $el->removeAttribute($attr->name);
                    }
                    break;

                case 'id':
                    if (!preg_match('/^[A-Za-z][A-Za-z0-9_\-:.]{0,63}$/', $value)) {
                        $el->removeAttribute($attr->name);
                    }
                    break;
            }
        }

        if ($tag === 'input') {
            $type = strtolower($el->getAttribute('type') ?: 'text');
            if (!in_array($type, self::INPUT_TYPES, true)) {
                return false; // password, file, hidden, submit, image ...
            }
            $el->setAttribute('type', $type);
            if (in_array($type, ['text', 'email', 'tel'], true) && !$el->hasAttribute('maxlength')) {
                $el->setAttribute('maxlength', '255');
            }
        }

        if ($tag === 'img' && !$el->hasAttribute('src')) {
            return false;
        }

        return true;
    }

    private function cleanStylesheet(string $css): string
    {
        $css = preg_replace('/@import[^;]*;?/i', '', $css);
        $css = preg_replace('/url\s*\([^)]*\)/i', 'none', $css);
        $css = preg_replace('/(expression\s*\([^)]*\)|javascript:|behaviou?r\s*:[^;}]*|-moz-binding[^;}]*)/i', '', $css);

        return trim(str_replace('<', '', $css));
    }

    /* ---------------------------------------------------------------- schema */

    private function buildSchema(DOMDocument $dom): array
    {
        $xp = new DOMXPath($dom);
        $fields = [];

        foreach ($xp->query('//body//input | //body//select | //body//textarea') as $el) {
            /** @var DOMElement $el */
            $raw = $el->getAttribute('name');
            if ($raw === '') {
                continue;
            }

            $multi = str_ends_with($raw, '[]');
            $name = $multi ? substr($raw, 0, -2) : $raw;
            $tag = strtolower($el->tagName);
            $type = $tag === 'input' ? strtolower($el->getAttribute('type') ?: 'text') : $tag;

            if (!isset($fields[$name])) {
                $fields[$name] = [
                    'name'     => $name,
                    'type'     => $type,
                    'multi'    => $multi,
                    'options'  => [],
                    'required' => false,
                    'rating'   => false,
                    'label'    => $this->labelFor($el, $xp, $name, $type),
                    'section'  => $this->sectionFor($el, $xp),
                ];
            }

            if ($fields[$name]['type'] !== $type) {
                continue; // same name reused with a different kind of control: first one wins
            }

            if (in_array($type, ['radio', 'checkbox'], true)) {
                $value = $el->hasAttribute('value') ? $el->getAttribute('value') : 'on';
                if ($value !== '' && !in_array($value, $fields[$name]['options'], true)) {
                    $fields[$name]['options'][] = $value;
                }
            } elseif ($type === 'select') {
                foreach ($xp->query('.//option', $el) as $opt) {
                    $value = $opt->hasAttribute('value') ? $opt->getAttribute('value') : trim($opt->textContent);
                    if ($value !== '' && !in_array($value, $fields[$name]['options'], true)) {
                        $fields[$name]['options'][] = $value;
                    }
                }
            }

            if ($el->hasAttribute('required')) {
                $fields[$name]['required'] = true;
            }
        }

        foreach ($fields as &$f) {
            if ($f['type'] === 'radio') {
                $numeric = array_filter($f['options'], fn ($o) => preg_match('/^[1-5]$/', $o));
                $others = array_filter($f['options'], fn ($o) => !preg_match('/^([1-5]|n\/?a)$/i', $o));
                $f['rating'] = count($numeric) >= 3 && count($others) === 0;
            }
        }
        unset($f);

        return ['fields' => array_values($fields)];
    }

    private function labelFor(DOMElement $el, DOMXPath $xp, string $name, string $type): string
    {
        // 1. data-label on the element or a close ancestor
        $node = $el;
        for ($i = 0; $i < 4 && $node instanceof DOMElement; $i++, $node = $node->parentNode) {
            if ($node->hasAttribute('data-label')) {
                return $this->tidy($node->getAttribute('data-label'));
            }
        }

        $isChoice = in_array($type, ['radio', 'checkbox'], true);

        // 2. <label for="id"> (text-like controls only)
        if (!$isChoice && $el->hasAttribute('id')) {
            $id = $el->getAttribute('id');
            $label = $xp->query('//label[@for="'.$id.'"]')->item(0);
            if ($label && $this->tidy($label->textContent) !== '') {
                return $this->tidy($label->textContent);
            }
        }

        // 3. Rating rows: first cell of the row holds the question text
        $row = $xp->query('ancestor::tr[1]', $el)->item(0);
        $cell = $xp->query('ancestor::td[1] | ancestor::th[1]', $el)->item(0);

        if ($row && $type === 'radio' && $this->looksLikeRating($el, $xp)) {
            $first = $xp->query('./td[1] | ./th[1]', $row)->item(0);
            if ($first && $this->tidy($first->textContent) !== '') {
                return $this->tidy($first->textContent);
            }
        }

        // 4. The cell just before the one containing the control ("Date | [input]")
        if ($cell) {
            $prev = $xp->query('preceding-sibling::*[1]', $cell)->item(0);
            if ($prev && $this->tidy($prev->textContent) !== '') {
                return $this->tidy($prev->textContent);
            }
        }

        // 5. <fieldset><legend>
        $legend = $xp->query('ancestor::fieldset[1]/legend', $el)->item(0);
        if ($legend && $this->tidy($legend->textContent) !== '') {
            return $this->tidy($legend->textContent);
        }

        if ($el->hasAttribute('placeholder')) {
            return $this->tidy($el->getAttribute('placeholder'));
        }

        return Str::headline($name);
    }

    /** A radio sits in a rating row if its row has several radios sharing one name. */
    private function looksLikeRating(DOMElement $el, DOMXPath $xp): bool
    {
        $name = $el->getAttribute('name');
        $row = $xp->query('ancestor::tr[1]', $el)->item(0);
        if (!$row) {
            return false;
        }
        $count = 0;
        foreach ($xp->query('.//input[@type="radio"]', $row) as $r) {
            if ($r->getAttribute('name') === $name && preg_match('/^([1-5]|n\/?a)$/i', $r->getAttribute('value'))) {
                $count++;
            }
        }

        return $count >= 4;
    }

    private function sectionFor(DOMElement $el, DOMXPath $xp): ?string
    {
        $node = $el;
        for ($i = 0; $i < 8 && $node instanceof DOMElement; $i++, $node = $node->parentNode) {
            if ($node->hasAttribute('data-section')) {
                return $this->tidy($node->getAttribute('data-section'));
            }
        }

        $sec = $xp->query(
            'ancestor::tr[1]/preceding-sibling::tr[contains(concat(" ", normalize-space(@class), " "), " section ")][1]',
            $el
        )->item(0);

        return $sec ? ($this->tidy($sec->textContent) ?: null) : null;
    }

    private function tidy(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return Str::limit(rtrim($text, ": \t"), 200, '');
    }
}
