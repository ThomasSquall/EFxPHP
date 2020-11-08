<?php

namespace EFxPHP\Models\Fields;

use Exception;
use PHPAnnotations\Annotations\Annotation;

/**
 * Class TypeAnnotation.
 * @package EFxPHP\Models\Fields
 */
class TypeAnnotation extends Annotation {
    protected $type = '';
    protected $length;

    private $acceptedTypes = [
        'Numeric' => [
            'tinyint', 'bool',
            'smallint',
            'mediumint',
            'int', 'integer',
            'bigint',
            'decimal',
            'float',
            'double',
            'bit'
        ],

        'String' => [
            'char',
            'varchar',
            'binary',
            'varbinary',
            'tinyblob',
            'blob',
            'mediumblob',
            'longblob',
            'tinytext',
            'text',
            'mediumtext',
            'longtext',
            'enum',
            'set'
        ],

        'DateTime' => [
            'date',
            'time',
            'datetime',
            'timestamp',
            'year'
        ],

        // Not supported 100% at the moment
        'Spatial' => [
            'geometry',
            'point',
            'linestring',
            'polygon',
            'geometrycollection',
            'multilinestring',
            'multipoint',
            'multipolygon'
        ],

        // (MySQL 5.7.8)
        'JSON' => [
            'json'
        ]
    ];

    /**
     * TypeAnnotation constructor.
     * @param string $type
     * @param int $length
     * @throws Exception
     */
    public function __construct(string $type, int $length = -1) {
        if (!(
            in_array($type, $this->acceptedTypes['Numeric']) ||
            in_array($type, $this->acceptedTypes['String']) ||
            in_array($type, $this->acceptedTypes['DateTime']) ||
            in_array($type, $this->acceptedTypes['Spatial']) ||
            in_array($type, $this->acceptedTypes['JSON'])
        ))
            throw new Exception("The type $type does not exist");

        $this->type = $type;

        if ($length > 0)
            $this->length = $length;
    }

    public function getDatatype() {
        return
            in_array($this->type, $this->acceptedTypes['Numeric']) ? 'Numeric' : (
                in_array($this->type, $this->acceptedTypes['String']) ? 'String' : (
                    in_array($this->type, $this->acceptedTypes['DateTime']) ? 'DateTime' : (
                        in_array($this->type, $this->acceptedTypes['Spatial']) ? 'Spatial' : 'JSON'
                    )
                )
            );
    }
}