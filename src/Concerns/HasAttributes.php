<?php

namespace Yource\ExactOnlineClient\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Yource\ExactOnlineClient\Resources\Resource;

/**
 * @todo add functionality that removes attributes to clean up the returned
 */
trait HasAttributes
{
    /**
     * The field with the resource's primary key.
     */
    protected string $primaryKeyFieldName = 'ID';

    /**
     * The resource's attributes.
     */
    protected array $attributes = [];

    /**
     * The attributes that should be mutated to dates.
     */
    protected array $dates = [];

    /**
     * The resource's relationships.
     *
     * @var array[] field => resource class
     */
    protected array $relationships = [];

    /**
     * Fill the resource with an array of attributes.
     *
     * @return $this
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Set a given attribute on the resource.
     */
    public function setAttribute(string $key, $value)
    {
        // First we will check for the presence of a mutator for the set operation
        // which simply lets the developers tweak the attribute as it is set on
        // the model, such as "json_encoding" an listing of data for storage.
        if ($this->hasSetMutator($key)) {
            return $this->setMutatedAttributeValue($key, $value);
        }

        // If an attribute is listed as a "date", we'll convert it from a DateTime
        // instance into a form proper for storage on the database tables using
        // the connection grammar's date format. We will auto set the values.
        elseif ($value && $this->isDateAttribute($key)) {
            $value = $this->fromDateTime($value);
        }

        $this->attributes[$key] = $value;

        return $this;
    }

    /**
     * Get an attribute from the resource.
     */
    public function getAttribute(string $key)
    {
        if (!$key) {
            return;
        }

        // If an attribute is listed as a "relationship", we'll do another request to grab it/them
        // When it's not yet an resource (or collection of resources)
        // Be aware this could result in multiple requests
        if (
            $this->isRelationshipAttribute($key)
            && !$this->getAttributeValue($key) instanceof Collection
            && !$this->getAttributeValue($key) instanceof Resource
        ) {
            return $this->requestRelationship($key);
        }

        // If the attribute exists in the attribute array or has a "get" mutator we will
        // get the attribute's value. Otherwise, we will proceed as if the developers
        // are asking for a relationship's value. This covers both types of values.
        if (
            array_key_exists($key, $this->attributes) ||
            $this->hasGetMutator($key)
        ) {
            return $this->getAttributeValue($key);
        }

        // Here we will determine if the model base class itself contains this given key
        // since we don't want to treat any of those methods as relationships because
        // they are all intended as helper methods and none of these are relations.
        if (method_exists(self::class, $key)) {
            return;
        }

        return $this->$key;
    }

    /**
     * Determine if a set mutator exists for an attribute.
     */
    public function hasSetMutator(string $key): bool
    {
        return method_exists($this, 'set' . Str::studly($key) . 'Attribute');
    }

    /**
     * Determine if a get mutator exists for an attribute.
     */
    public function hasGetMutator(string $key): bool
    {
        return method_exists($this, 'get' . Str::studly($key) . 'Attribute');
    }

    /**
     * Get a plain attribute (not a relationship).
     *
     * @param  string  $key
     */
    public function getAttributeValue($key)
    {
        return $this->transformModelValue($key, $this->getAttributeFromArray($key));
    }

    /**
     * Transform a raw model value using mutators, casts, etc.
     *
     * @param  string  $key
     */
    protected function transformModelValue($key, $value)
    {
        // If the attribute has a get mutator, we will call that then return what
        // it returns as the value, which is useful for transforming values on
        // retrieval from the model to a form that is more useful for usage.
        if ($this->hasGetMutator($key)) {
            return $this->mutateAttribute($key, $value);
        }

        return $value;
    }

    public function getDates(): array
    {
        return $this->dates;
    }

    /**
     * Convert a DateTime to a storable string.
     *
     * @return string|null
     */
    public function fromDateTime($value)
    {
        return empty($value) ? $value : $this->asDateTime($value);
    }

    /**
     * Determine if the given attribute is a date or date castable.
     */
    protected function isDateAttribute($key): bool
    {
        return !empty($this->getDates()) && in_array($key, $this->getDates(), true);
    }

    /**
     * Return a timestamp as DateTime object.
     * @param $value
     */
    protected function asDateTime($value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        preg_match('/(\d{13})/', $value, $matches);
        return Carbon::createFromTimestampMs($matches[0]);
    }

    /**
     * Determine if an attributes is a relationship.
     */
    public function isRelationshipAttribute(string $key): bool
    {
        return !empty($this->relationships) && !empty($this->relationships[$key]);
    }

    /**
     * Determine if an attributes is a relationship.
     */
    public function requestRelationship($relationName): Collection
    {
        // @todo what to do when a new parent is created and it doesnt have a primary key yet
        $parentEndpoint = $this->getEndpoint();
        $parentKey = $this->getPrimaryKey();
        $relationClass = $this->relationships[$relationName];

        return (new $relationClass)->newClient()
            ->setEndpoint("{$parentEndpoint}(guid'{$parentKey}')/{$relationName}")
            ->all();
    }

    /**
     * Get the value of an attribute using its mutator.
     *
     * @param  string  $key
     */
    protected function mutateAttribute($key, $value)
    {
        return $this->{'get' . Str::studly($key) . 'Attribute'}($value);
    }

    /**
     * Get the value of an attribute using its mutator for array conversion.
     */
    protected function mutateAttributeForArray(string $key, $value)
    {
        $value = $this->mutateAttribute($key, $value);

        return $value instanceof Arrayable ? $value->toArray() : $value;
    }

    /**
     * Get an attribute from the $attributes array.
     */
    protected function getAttributeFromArray(string $key)
    {
        return $this->getAttributes()[$key] ?? null;
    }

    /**
     * Get all of the current attributes on the resource.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Check if the resource has any attributes.
     */
    public function hasAttributes(): bool
    {
        return !empty($this->attributes);
    }

    /**
     * Convert the resource's attributes to an array.
     *
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = $this->getAttributes();
        $attributes = $this->formatDates($attributes);

        return $attributes;
    }

    /**
     * Convert the resource's dates to formatted strings.
     *
     * @return array
     */
    private function formatDates($attributes)
    {
        foreach ($attributes as $key => $value) {
            if ($this->isDateAttribute($key) && $value instanceof Carbon) {
                $attributes[$key] = $value->toDateString();
            }
        }

        return $attributes;
    }

    /**
     * Determine if the given attribute exists.
     */
    public function offsetExists($offset): bool
    {
        return ! is_null($this->getAttribute($offset));
    }

    /**
     * Get the value for a given offset.
     */
    public function offsetGet($offset)
    {
        return $this->getAttribute($offset);
    }

    /**
     * Set the value for a given offset.
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->setAttribute($offset, $value);
    }

    /**
     * Unset the value for a given offset.
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset], $this->relations[$offset]);
    }

    /**
     * Return the field in the resource that holds the primary key
     */
    public function getPrimaryKeyFieldName(): string
    {
        return $this->primaryKeyFieldName;
    }

    /**
     * Return the primary key of the resource
     */
    public function getPrimaryKey()
    {
        $fieldName = $this->getPrimaryKeyFieldName();
        return $this->$fieldName;
    }
}
