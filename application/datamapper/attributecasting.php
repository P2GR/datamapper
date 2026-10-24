<?php

/**
 * Compatibility trait for models written before casting moved into DataMapper.
 * Keep $casts untyped, as in the parent model; magic access uses the core implementation.
 */
trait DMZ_AttributeCasting
{
    protected function has_cast(string $key): bool { return isset($this->casts[$key]); }
    protected function get_cast_type(string $key): ?string { return $this->casts[$key] ?? NULL; }
    protected function has_get_accessor(string $key): bool { return $this->_has_get_accessor($key); }
    protected function has_set_mutator(string $key): bool { return $this->_has_set_mutator($key); }
    protected function get_attribute_value(string $key): mixed { return $this->_get_attribute_value($key); }
    protected function set_attribute_value(string $key, mixed $value): void { $this->_set_attribute_value($key, $value); }
    protected function cast_attribute(string $key, mixed $value): mixed { return $this->_cast_attribute($key, $value); }
    protected function reverse_cast_attribute(string $key, mixed $value): mixed { return $this->_reverse_cast_attribute($key, $value); }
    protected function from_json(mixed $value): array { return $this->_from_json($value); }
    protected function as_json(mixed $value): string { return $this->_as_json($value); }
    protected function as_date_time(mixed $value): ?DateTime { return $this->_as_datetime($value); }
    protected function as_date(mixed $value): ?DateTime { return $this->_as_date($value); }
    protected function from_date_time(mixed $value): ?string { return $this->_from_datetime($value); }
    protected function studly_case(string $value): string { return str_replace(' ', '', ucwords(str_replace('_', ' ', $value))); }
    protected function snake_case(string $value): string { return strtolower(ltrim(preg_replace('/([A-Z])/', '_$1', $value), '_')); }

    public function to_array($fields = ''): array
    {
        require_once APPPATH . 'datamapper/array.php';
        if (empty($fields)) {
            $fields = $this->fields;
            foreach (get_class_methods($this) as $method) {
                if (preg_match('/^get(.+)Attribute$/', $method, $matches)) {
                    $fields[] = $this->snake_case($matches[1]);
                }
            }
        }
        return (new DMZ_Array())->to_array($this, array_unique((array) $fields));
    }

    public function toArray(): array { return $this->to_array(); }
}
