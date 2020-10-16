<?php

namespace Flat3\Lodata;

use Flat3\Lodata\Controller\Response;
use Flat3\Lodata\Controller\Transaction;
use Flat3\Lodata\Exception\Internal\LexerException;
use Flat3\Lodata\Exception\Internal\PathNotHandledException;
use Flat3\Lodata\Exception\Protocol\BadRequestException;
use Flat3\Lodata\Exception\Protocol\InternalServerErrorException;
use Flat3\Lodata\Exception\Protocol\NoContentException;
use Flat3\Lodata\Exception\Protocol\NotFoundException;
use Flat3\Lodata\Expression\Lexer;
use Flat3\Lodata\Helper\Laravel;
use Flat3\Lodata\Interfaces\ArgumentInterface;
use Flat3\Lodata\Interfaces\ContextInterface;
use Flat3\Lodata\Interfaces\EmitInterface;
use Flat3\Lodata\Interfaces\IdentifierInterface;
use Flat3\Lodata\Interfaces\PipeInterface;
use Flat3\Lodata\Interfaces\ResourceInterface;
use Flat3\Lodata\Interfaces\TypeInterface;
use Flat3\Lodata\Type\Binary;
use Flat3\Lodata\Type\Boolean;
use Flat3\Lodata\Type\Byte;
use Flat3\Lodata\Type\Date;
use Flat3\Lodata\Type\DateTimeOffset;
use Flat3\Lodata\Type\Decimal;
use Flat3\Lodata\Type\Double;
use Flat3\Lodata\Type\Duration;
use Flat3\Lodata\Type\Enum;
use Flat3\Lodata\Type\Guid;
use Flat3\Lodata\Type\Int16;
use Flat3\Lodata\Type\Int32;
use Flat3\Lodata\Type\Int64;
use Flat3\Lodata\Type\Property;
use Flat3\Lodata\Type\SByte;
use Flat3\Lodata\Type\Single;
use Flat3\Lodata\Type\Stream;
use Flat3\Lodata\Type\String_;
use Flat3\Lodata\Type\TimeOfDay;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Class PrimitiveType
 * @method static Binary binary()
 * @method static Boolean boolean()
 * @method static Byte byte()
 * @method static Date date()
 * @method static DateTimeOffset datetimeoffset()
 * @method static Decimal decimal()
 * @method static Double double()
 * @method static Duration duration()
 * @method static Enum enum()
 * @method static Guid guid()
 * @method static Int16 int16()
 * @method static Int32 int32()
 * @method static Int64 int64()
 * @method static SByte sbyte()
 * @method static Single single()
 * @method static Stream stream()
 * @method static String_ string()
 * @method static TimeOfDay timeofday()
 * @package Flat3\OData
 */
abstract class PrimitiveType implements TypeInterface, IdentifierInterface, ContextInterface, ResourceInterface, EmitInterface, PipeInterface, ArgumentInterface
{
    protected $identifier = 'Edm.None';

    /** @var bool $nullable Whether the value can be made null */
    protected $nullable = true;

    protected $immutable = false;

    /** @var Entity $entity */
    private $entity;

    /** @var Property $property */
    private $property;

    /** @var ?mixed $value Internal representation of the value */
    protected $value;

    public function __construct($value = null, bool $nullable = true)
    {
        $this->nullable = $nullable;
        $this->set($value);
    }

    public function seal(): self
    {
        $this->immutable = true;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return Str::afterLast($this->identifier, '.');
    }

    public function getNamespace(): string
    {
        return Laravel::beforeLast($this->identifier, '.');
    }

    public function getResolvedName(string $namespace): string
    {
        if ($this->getNamespace() === $namespace) {
            return $this->getName();
        }

        return $this->getIdentifier();
    }

    /**
     * Set the internal value from a standard typed value
     *
     * @param $value
     * @noinspection PhpUnusedParameterInspection
     * @return PrimitiveType
     */
    public function set($value)
    {
        if ($this->immutable) {
            throw new RuntimeException('Primitive value is immutable');
        }

        return $this;
    }

    public static function factory($value = null, ?bool $nullable = true): self
    {
        if ($value instanceof PrimitiveType) {
            return $value;
        }

        return new static($value, $nullable);
    }

    /**
     * Get the internal representation of the value
     *
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    /**
     * Get the value as OData URL encoded
     *
     * @return string
     */
    abstract public function toUrl(): string;

    /**
     * Get the value as suitable for IEEE754 JSON encoding
     *
     * @return string
     */
    public function toJsonIeee754(): ?string
    {
        $value = $this->toJson();

        return null === $value ? null : (string) $value;
    }

    /**
     * Get the value as suitable for JSON encoding
     *
     * @return mixed
     */
    abstract public function toJson();

    /**
     * Return null or an empty value if this type cannot be made null
     *
     * @param $value
     *
     * @return mixed
     */
    public function maybeNull($value)
    {
        if (null === $value) {
            return $this->nullable ? null : $this->getEmpty();
        }

        return $value;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function setNullable(bool $nullable): self
    {
        if ($this->immutable) {
            throw new RuntimeException('Primitive type is immutable');
        }

        $this->nullable = $nullable;
        return $this;
    }

    protected function getEmpty()
    {
        return '';
    }

    public function setProperty(Property $property): self
    {
        $this->property = $property;
        return $this;
    }

    public function setEntity(Entity $entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    public function getProperty()
    {
        return $this->property;
    }

    public static function pipe(
        Transaction $transaction,
        string $currentComponent,
        ?string $nextComponent,
        ?PipeInterface $argument
    ): ?PipeInterface {
        $lexer = new Lexer($currentComponent);

        try {
            $property = $lexer->identifier();
        } catch (LexerException $e) {
            throw new PathNotHandledException();
        }

        if (null === $argument) {
            throw new PathNotHandledException();
        }

        if (!$argument instanceof Entity) {
            throw new BadRequestException('bad_entity', 'Primitive must be passed an entity');
        }

        $property = $argument->getType()->getProperty($property);

        if (null === $property) {
            throw new NotFoundException('unknown_property',
                sprintf('The requested property (%s) was not known', $property));
        }

        return $argument->getPrimitive($property);
    }

    public function getContextUrl(): string
    {
        if ($this->entity) {
            return sprintf(
                '%s(%s)/%s',
                $this->entity->getEntitySet()->getContextUrl(),
                $this->entity->getEntityId()->toUrl(),
                $this->property
            );
        }

        return Transaction::getContextUrl().'#'.$this->getIdentifier();
    }

    public function getResourceUrl(): string
    {
        return Transaction::getResourceUrl().$this->getIdentifier().'()';
    }

    public function emit(Transaction $transaction): void
    {
        $transaction->outputRaw($this);
    }

    public function response(Transaction $transaction): Response
    {
        if (null === $this->get()) {
            throw new NoContentException('null_value');
        }

        $transaction->configureJsonResponse();

        $metadata = [
            'context' => $this->getContextUrl(),
        ];

        $metadata = $transaction->getMetadata()->filter($metadata);

        return $transaction->getResponse()->setCallback(function () use ($transaction, $metadata) {
            $transaction->outputJsonObjectStart();

            if ($metadata) {
                $transaction->outputJsonKV($metadata);
                $transaction->outputJsonSeparator();
            }

            $transaction->outputJsonKey('value');
            $transaction->outputJsonValue($this);

            $transaction->outputJsonObjectEnd();
        });
    }

    public static function __callStatic($name, $arguments)
    {
        $resolver = [
            'binary' => Binary::class,
            'boolean' => Boolean::class,
            'byte' => Byte::class,
            'date' => Date::class,
            'datetimeoffset' => DateTimeOffset::class,
            'decimal' => Decimal::class,
            'double' => Double::class,
            'duration' => Duration::class,
            'enum' => Enum::class,
            'guid' => Guid::class,
            'int16' => Int16::class,
            'int32' => Int32::class,
            'int64' => Int64::class,
            'sbyte' => SByte::class,
            'single' => Single::class,
            'stream' => Stream::class,
            'string' => String_::class,
            'timeofday' => TimeOfDay::class,
        ];

        if (!array_key_exists($name, $resolver)) {
            throw new InternalServerErrorException('invalid_type', 'An invalid type was requested: '.$name);
        }

        $clazz = $resolver[$name];
        return (new $clazz())->seal();
    }

    public function clone(): self
    {
        return clone $this;
    }

    public function __clone()
    {
        $this->immutable = false;
    }
}
