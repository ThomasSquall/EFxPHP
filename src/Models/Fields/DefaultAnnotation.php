<?php

namespace EFxPHP\Models\Fields;

use PHPAnnotations\Annotations\Annotation;

/**
 * Class DefaultAnnotation.
 * @package EFxPHP\Models\Fields
 */
class DefaultAnnotation extends Annotation {
    protected $value;

    /**
     * DefaultAnnotation constructor.
     * @param string $value
     */
    public function __construct($value = '') { $this->value = $value; }
}