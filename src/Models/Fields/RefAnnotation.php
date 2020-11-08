<?php

namespace EFxPHP\Models\Fields;

use PHPAnnotations\Annotations\Annotation;

/**
 * Class RefAnnotation.
 * @package EFxPHP\Models\Fields
 */
class RefAnnotation extends Annotation {
    protected $model = '';
    protected $field = '';

    /**
     * RefAnnotation constructor.
     * @param string $model
     * @param string $field
     */
    public function __construct(string $model, string $field) {
        $this->model = $model;
        $this->field = $field;
    }
}