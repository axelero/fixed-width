<?php

namespace Axelero\FixedWidth;

use RuntimeException;

class FixedWidth
{
    protected $fields;

    /**
     * Bases other than 1 are untested, unsupported, won't likely work.
     *
     * @var int
     */
    protected $base = 1;

    /**
     * The character we use to build the output line before filling it.
     *
     * @var string
     */
    protected $filler = ' ';

    protected $trimBeforeConvertingToText = true;

    public function __construct($fields = [])
    {
        $this->fields($fields);
    }

    public function readField($line, $name)
    {
        // discard newlines
        $line = rtrim($line);

        $field = $this->fields[$name];
        $value = substr(
            $line,
            $field['start'] - $this->base,
            $field['length']
        );

        return $this->textToValue($name, $value);
    }

    public function readLine($line)
    {
        $record = [];
        foreach ($this->fields as $field) {
            if (is_numeric($field['name'])) {
                continue;
            }
            $record[$field['name']] = $this->readField($line, $field['name']);
        }

        return $record;
    }

    public function validate($data)
    {
        foreach ($data as $key => $value) {
            $field    = $this->fields[$key];
            $dataType = gettype($value);
            if ($dataType !== $field['type']) {
                throw new InvalidDataException(
                    "Field {$field['name']} must be of type {$field['type']}"
                    . " ($dataType given)"
                );
            }
        }

        return true;
    }

    protected function string($field)
    {
        $defaults = [
            'alignment' => 'left',
            'padding'   => ' ',
            'required'  => false,
            'default'   => '',
        ];

        return $field + $defaults;
    }

    protected function integer($field)
    {
        $defaults = [
            'alignment' => 'right',
            'padding'   => '0',
            'required'  => false,
            'default'   => 0,
        ];

        return $field + $defaults;
    }

    protected function normalizeLength($field)
    {
        if ($field['start'] < $this->base) {
            throw new RuntimeException(
                "Field {$field['name']} start ({$field['start']}) cannot be less than the base ({$this->base})"
            );
        }

        $field = $field + ['length' => null, 'end' => null];
        if ($field['end'] !== null && $field['length'] !== null) {
            if (($field['start'] + $field['length'] - 1) !== $field['end']) {
                throw new RuntimeException("Start/End/  Length mismatch on field {$field['name']}");
            }
        } elseif ($field['length'] !== null) {
            $field['end'] = $field['start'] + $field['length'] - 1;
        } elseif ($field['end'] !== null) {
            $field['length'] = ($field['end'] - $field['start']) + 1;
        }
        if ($field['end'] < $field['start']) {
            throw new RuntimeException("Field {$field['name']} ends before it starts");
        }

        return $field;
    }

    protected function checkField($field)
    {
        if (!in_array($field['alignment'], ['left', 'right'])) {
            throw new RuntimeException("Field {$field['name']} has an invalid alignment value ({$field['alignment']})");
        }
    }

    public function getAvailableTypes()
    {
        return ['string', 'integer'];
    }

    public function fields($fieldArray = null)
    {
        if (!func_num_args()) {
            return $this->fields;
        }

        $result = [];

        foreach ($fieldArray as $key => $field) {
            if (empty($field['type'])) {
                throw new RuntimeException("Field $key must have a type, none given");
            }
            if (in_array($field['type'], $this->getAvailableTypes())) {
                $field['name'] = $key;
                $type          = $field['type'];
                $field         = $this->normalizeLength($field);
                $field         = $this->$type($field);
                $this->checkField($field);
            } else {
                throw new RuntimeException("Field $key type is invalid ({$field['type']}");
            }
            if (!strlen($field['padding'])) {
                throw new RuntimeException("Field $key must have a non empty padding");
            }
            $result[$key] = $field;
        }

        $this->fields = $result;

        foreach ($this->fields as $field) {
            $this->checkOverlap($field);
        }

        return $this;
    }

    protected function checkOverlap($field)
    {
        foreach ($this->fields as $other) {
            if ($field['name'] === $other['name']) {
                continue;
            }

            // http://stackoverflow.com/a/13387860/358813
            // e2 >= b1 and e1 >= b2
            if ($other['end'] >= $field['start'] && $field['end'] >= $other['start']) {
                throw new RuntimeException(
                    "Field {$field['name']} ({$field['start']}/{$field['end']}) overlaps with"
                    . "field {$other['name']} ({$other['start']}/{$other['end']})"
                );
            }
        }
    }

    protected function textToValue($name, $value)
    {
        $field        = $this->fields[$name];
        $trimFunction = $this->matchAlignment($field, 'rtrim', 'ltrim');

        $value = $trimFunction($value, $field['padding']);

        switch ($field['type']) {
            case 'string':
                return (string) $value;
            case 'integer':
                if (!filter_var($value, FILTER_VALIDATE_INT)) {
                    throw new RuntimeException("Field {$field['name']} cannot be cast as integer (value: '$value')");
                }

                return (int) $value;
        }

        return $value;
    }

    protected function matchAlignment($field, $left, $right)
    {
        $alignment = $field['alignment'];

        switch ($alignment) {
            case 'left':
                return $left;
            case 'right':
                return $right;
        }

        throw new \LogicException("Field {$field['name']} has an invalid alignment ($alignment)");
    }

    public function valueToText($name, $value)
    {
        $field = $this->fields[$name];
        $value = (string) $value;
        if ($this->trimBeforeConvertingToText) {
            $value = trim($value);
        }

        $alignment = $this->matchAlignment($field, STR_PAD_RIGHT, STR_PAD_LEFT);
        $string    = str_pad($value, $field['length'], $field['padding'], $alignment);

        if (strlen($string) > $field['length'] && in_array($field['type'], ['integer'])) {
            throw new RuntimeException("Field $name overflows (max length: {$field['length']} {$field['type']}");
        }
        if (strpos($value, "\n") !== false) {
            throw new RuntimeException("Field $name contains newlines");
        }
        $string = substr($string, 0, $field['length']);

        return $string;
    }

    /**
     * Calculates the length of a row.
     */
    public function getLength()
    {
        $length = 0;
        foreach ($this->fields as $field) {
            $length = max($length, $field['end']);
        }

        return $length;
    }

    public function writeLine($data)
    {
        $line = str_repeat($this->filler, $this->getLength());

        foreach ($this->fields as $field) {
            $name  = $field['name'];
            $value = array_key_exists($name, $data) ? $data[$name] : $field['default'];
            $line  = substr_replace(
                $line,
                $this->valueToText($name, $value),
                $field['start'] - $this->base,
                $field['length']
            );
        }

        return $line;
    }
}
