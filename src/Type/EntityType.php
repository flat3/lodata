<?php

namespace Flat3\OData\Type;

use Flat3\OData\Interfaces\IdentifierInterface;
use Flat3\OData\Internal\ObjectArray;
use Flat3\OData\Property;
use Flat3\OData\Property\Declared;
use Flat3\OData\Property\Navigation;
use Flat3\OData\Resource\Operation;
use Flat3\OData\Traits\HasFactory;
use Flat3\OData\Traits\HasIdentifier;
use Flat3\OData\Type;
use Illuminate\Support\Str;
use ReflectionClass;

class EntityType extends Type implements IdentifierInterface
{
    use HasIdentifier;
    use HasFactory;

    /** @var Property $key Primary key property */
    protected $key;

    /** @var ObjectArray[Property] $properties Properties */
    protected $properties;

    /** @var ObjectArray[Operation] $bound_operations Operations bound to this entity type */
    protected $boundOperations;

    public function __construct($identifier = null)
    {
        if ($identifier) {
            $this->setIdentifier($identifier);
        } else {
            $reflect = new ReflectionClass($this);
            $this->setIdentifier(Str::slug(Str::replaceLast('Type', '', $reflect->getShortName()), ''));
        }

        $this->properties = new ObjectArray();
        $this->boundOperations = new ObjectArray();
    }

    public function add_bound_operation(Operation $operation): self
    {
        $this->boundOperations[] = $operation;

        return $this;
    }

    public function getBoundOperations(): ObjectArray
    {
        return $this->boundOperations;
    }

    /**
     * Return the defined key
     *
     * @return Property|null
     */
    public function getKey(): ?Property
    {
        return $this->key;
    }

    /**
     * Set the key property by name
     *
     * @param  Property  $key
     *
     * @return $this
     */
    public function setKey(Property $key): self
    {
        $this->addProperty($key);

        // Key property is not nullable
        $key->setNullable(false);

        // Key property should be marked keyable
        $key->setAlternativeKey(true);

        $this->key = $key;

        return $this;
    }

    /**
     * Add a property to the list
     *
     * @param  Property  $property
     *
     * @return $this
     */
    public function addProperty(Property $property): self
    {
        $this->properties[] = $property;

        return $this;
    }

    public function addDeclaredProperty($identifier, Type $type): self
    {
        return $this->addProperty(new Declared($identifier, $type));
    }

    public function getDeclaredProperties(): ObjectArray
    {
        return $this->properties->sliceByClass(Declared::class);
    }

    public function getProperty(string $property): ?Property
    {
        return $this->getProperties()->get($property);
    }

    public function getProperties(): ObjectArray
    {
        return $this->properties;
    }

    public function getNavigationProperties(): ObjectArray
    {
        return $this->properties->sliceByClass(Navigation::class);
    }

    public function getName(): string
    {
        return $this->getIdentifier();
    }
}
