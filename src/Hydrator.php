<?php
/*
 * (c) Alkis Stamos <stamosalkis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AlkisStamos\Hydrator;
use AlkisStamos\Hydrator\Cast\DateTimeCastStrategy;
use AlkisStamos\Hydrator\Cast\FlatTypeCastStrategy;
use AlkisStamos\Hydrator\Cast\TypeCastStrategyInterface;
use AlkisStamos\Metadata\Driver\MetadataDriverInterface;
use AlkisStamos\Metadata\Metadata\ClassMetadata;
use AlkisStamos\Metadata\Metadata\PropertyMetadata;
use AlkisStamos\Metadata\Metadata\TypeMetadata;
use AlkisStamos\Hydrator\Naming\NamingStrategyInterface;
use AlkisStamos\Hydrator\Naming\UnderscoreNamingStrategy;
use AlkisStamos\Hydrator\Resolver\PathNameValueResolver;
use AlkisStamos\Hydrator\Resolver\PropertyValueResolverInterface;
use AlkisStamos\Metadata\MetadataDriver;
use Psr\SimpleCache\CacheInterface;

/**
 * @package Metadata
 * @author Alkis Stamos <stamosalkis@gmail.com>
 * @license MIT
 * @copyright Alkis Stamos
 *
 * Hydrates and extracts data from PHP classes to arrays and back
 */
class Hydrator implements HydratorInterface
{
    /**
     * Instance to extract metadata for a class
     *
     * @var MetadataDriver
     */
    private $driver;
    /**
     * Instantiator to generate instances and reflections
     *
     * @var InstantiatorInterface
     */
    private $instantiator;
    /**
     * Naming strategy to apply on property names
     *
     * @var NamingStrategyInterface
     */
    private $namingStrategy;
    /**
     * Collection of type cast strategies indexed by their supported types and strategy names
     *
     * @var TypeCastStrategyInterface[][]
     */
    private $castStrategies = [];
    /**
     * The current hydration strategy, would be set on each hydrate/extract method
     *
     * @var string
     */
    private $strategy = '_default';
    /**
     * List of property name resolvers indexed by their applied strategies
     *
     * @var PropertyValueResolverInterface[]
     */
    private $propertyResolvers = [];
    /**
     * List of hydration hooks indexed by their strategies
     *
     * @var HydratorHookInterface[]
     */
    private $hooks = [];

    /**
     * Hydrator constructor.
     * Instantiates the hydrator with the prefered configured dependencies
     * @param MetadataDriverInterface|null $driver
     * @param InstantiatorInterface|null $instantiator
     * @param NamingStrategyInterface|null $namingStrategy
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function __construct(MetadataDriverInterface $driver=null, InstantiatorInterface $instantiator=null, NamingStrategyInterface $namingStrategy=null)
    {
        if($driver === null)
        {
            $this->driver = new MetadataDriver();
            $this->driver->enableAnnotations();
        }
        else if(get_class($driver) !== MetadataDriver::class)
        {
            $this->driver = new MetadataDriver([
                $driver
            ]);
        }
        else
        {
            $this->driver = $driver;
        }
        $this->instantiator = $instantiator === null ? new Instantiator() : $instantiator;
        $this->namingStrategy = $namingStrategy === null ? new UnderscoreNamingStrategy() : $namingStrategy;
        $this->addTypeCastStrategy(new FlatTypeCastStrategy())
            ->addTypeCastStrategy(new DateTimeCastStrategy())
            ->addPropertyResolver(new PathNameValueResolver());
    }

    /**
     * Hydrates the data into an object of type $class
     *
     * @param array $data
     * @param string $class
     * @param null|string $strategy
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function hydrate(array $data, string $class, ?string $strategy=null)
    {
        $this->strategy = $strategy === null ? '_default' : $strategy;
        $metadata = $this->driver->getClassMetadata($this->instantiator->getReflectionClass($class));
        $hook = $this->getHookListener($this->strategy);
        if($hook !== null)
        {
            $hook->onBeforeHydrate($metadata,$data);
        }
        $instance = $this->instantiator->instantiate($class);
        foreach($metadata->properties as $property)
        {
            $preferHook = false;
            $targetData = null;
            if($hook !== null)
            {
                $targetData = $hook->onPropertyHydrate($metadata,$property,$data,$preferHook);
            }
            if(!$preferHook)
            {
                $targetData = $this->resolveDataInPayload($property,$data);
            }
            if($targetData === null)
            {
                if($property->type->isNullable)
                {
                    $this->setProperty($metadata,$property,$instance,null);
                }
            }
            else
            {
//                $value = $preferHook === true ? $targetData : $this->mapProperty($property,$targetData); //TODO: this may be better allowing the hook to decide the payload
                $value = $this->mapProperty($property,$targetData);
                $this->setProperty($metadata,$property,$instance,$value);
            }
        }
        if($hook !== null)
        {
            $hook->onAfterHydrate($metadata,$instance);
        }
        return $instance;
    }

    /**
     * Returns the property data from the payload or null if no correct index is set in the payload. If the metadata
     * name contains the dot '.' sign the method will search in nested arrays to find the related data
     *
     * @param PropertyMetadata $propertyMetadata
     * @param array $payload
     * @return mixed|null
     */
    protected function resolveDataInPayload(PropertyMetadata $propertyMetadata, array $payload)
    {
        $resolver = $this->getPropertyResolver();
        if($resolver === null || !$resolver->supports($propertyMetadata))
        {
            $res = isset($payload[$propertyMetadata->name]) ? $payload[$propertyMetadata->name] : null;
            if($res === null)
            {
                $extractedName = $this->serializedPropertyName($propertyMetadata);
                $res = isset($payload[$extractedName]) ? $payload[$extractedName] : null;
            }
            return $res;
        }
        $profile = $resolver->resolveProperty($propertyMetadata,$payload);
        if(strpos($profile,'.') !== false)
        {
            $nested = $payload;
            $profileFragments = explode('.',$profile);
            foreach($profileFragments as $profileIndex=>$profileItem)
            {
                if(!array_key_exists($profileItem,$nested))
                {
                    break;
                }
                if(!isset($profileFragments[$profileIndex + 1]))
                {
                    return $nested[$profileItem];
                }
                $nested = $nested[$profileItem];
            }
        }
        return isset($payload[$profile]) ? $payload[$profile] : null;
    }

    /**
     * Maps and type casts the value according to the property metadata. The method will no map the value to the
     * property
     *
     * @param PropertyMetadata $metadata
     * @param $value
     * @return array|mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    protected function mapProperty(PropertyMetadata $metadata, $value)
    {
        $customTypecast = $this->getSupportedTypecastStrategy($metadata->type);
        if($customTypecast !== null)
        {
            return $customTypecast->hydrate($metadata->type,$value);
        }
        if(!$metadata->type->isFlat && is_array($value))
        {
            if($metadata->type->isArray)
            {
                $res = [];
                foreach($value as $key=>$item)
                {
                    $res[$key] = $this->hydrate($item,$metadata->type->name,$this->strategy);
                }
                return $res;
            }
            else
            {
                return $this->hydrate($value,$metadata->type->name,$this->strategy);
            }
        }
        return $value;
    }

    /**
     * Generates the extracted property name according to the naming strategy configuration in the service
     *
     * @param PropertyMetadata $propertyMetadata
     * @return string
     */
    protected function serializedPropertyName(PropertyMetadata $propertyMetadata)
    {
        return $this->namingStrategy->translatePropertyName($propertyMetadata);
    }

    /**
     * Does map the value to the class property. The method will check the access of the property and if the access is
     * not public it will prefer to use a setter instead to map the value directly. The setter name would be generated
     * according to the naming strategy
     *
     * @param ClassMetadata $classMetadata
     * @param PropertyMetadata $metadata
     * @param $classInstance
     * @param $value
     */
    protected function setProperty(ClassMetadata $classMetadata, PropertyMetadata $metadata, $classInstance, $value)
    {
        if($metadata->access === 'public')
        {
            $classInstance->{$metadata->name} = $value;
            return;
        }
        else
        {
            $setterName = $this->namingStrategy->setterName($metadata);
            if(isset($classMetadata->methods[$setterName]))
            {
                $classInstance->{$setterName}($value);
                return;
            }
        }
        throw new \RuntimeException('Cannot find a way to map data to the property '.$classMetadata->name.'::'.$metadata->name);
    }

    /**
     * Extracts an object back to an array
     *
     * @param mixed $object
     * @param null|string $strategy
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function extract($object, ?string $strategy=null): array
    {
        $this->strategy = $strategy === null ? '_default' : $strategy;
        $classMetadata = $this->driver->getClassMetadata($this->instantiator->getReflectionClass(get_class($object)));
        $hook = $this->getHookListener($this->strategy);
        if($hook !== null)
        {
            $hook->onBeforeExtract($classMetadata,$object);
        }
        $res = [];
        foreach($classMetadata->properties as $property)
        {
            $customTypeCaster = $this->getSupportedTypecastStrategy($property->type);
            if($customTypeCaster !== null)
            {
                $this->extractContent($res,$customTypeCaster->extract($property->type,$this->getPropertyValue($classMetadata,$property,$object)),$classMetadata,$property,$object,$hook);
                continue;
            }
            if($property->type->isFlat)
            {
                $this->extractContent($res,$this->getPropertyValue($classMetadata,$property,$object),$classMetadata,$property,$object,$hook);
            }
            else
            {
                if($property->type->isArray)
                {
                    $embbed = [];
                    $values = $this->getPropertyValue($classMetadata,$property,$object);
                    foreach($values as $key=>$value)
                    {
                        $embbed[$key] = $this->extract($value,$this->strategy);
                    }
                    $this->extractContent($res,$embbed,$classMetadata,$property,$object,$hook);
                }
                else
                {
                    $propValue = $this->getPropertyValue($classMetadata,$property,$object);
                    $this->extractContent(
                        $res,
                        $propValue === null ? null : $this->extract($propValue,$this->strategy),
                        $classMetadata,
                        $property,
                        $object,
                        $hook
                    );
                }
            }
        }
        if($hook !== null)
        {
            $hook->onAfterExtract($classMetadata,$res);
        }
        return $res;
    }

    /**
     * Generates the final extracted content of the property
     *
     * @param array $extractedContent
     * @param $value
     * @param PropertyMetadata $propertyMetadata
     * @param ClassMetadata $classMetadata
     * @param $targetObject
     * @param HydratorHookInterface|null $hook
     * @return array
     */
    protected function extractContent(array &$extractedContent, $value, ClassMetadata $classMetadata, PropertyMetadata $propertyMetadata, $targetObject, ?HydratorHookInterface $hook)
    {
        $key = $this->extractedPropertyName($propertyMetadata,$targetObject);
        if(strpos($key,'.') !== false)
        {
            $profile = explode('.',$key);
            $ref = &$extractedContent;
            foreach($profile as $profileIndex=>$profileItem)
            {
                if(!isset($profile[$profileIndex + 1]))
                {
                    $ref[$profileItem] = $value;
                }
                else
                {
                    if(isset($extractedContent[$profileItem]))
                    {
                        $ref[$profileItem] = $extractedContent[$profileItem];
                    }
                    else
                    {
                        $ref[$profileItem] = [];
                    }
                }
                $ref = &$ref[$profileItem];
            }
            if($hook !== null)
            {
                $hookContent = $hook->onPropertyExtract($classMetadata,$propertyMetadata,$extractedContent,$preferHook);
                if($preferHook === true)
                {
                    $extractedContent = $hookContent;
                    return $hookContent;
                }
            }
            return $extractedContent;
        }
        $extractedContent[$key] = $value;
        return $extractedContent;
    }

    /**
     * Resolves the extracted property name
     *
     * @param PropertyMetadata $propertyMetadata
     * @param $targetObject
     * @return string
     */
    protected function extractedPropertyName(PropertyMetadata $propertyMetadata, $targetObject)
    {
        $resolver = $this->getPropertyResolver();
        if($resolver === null || !$resolver->supportsExtraction($propertyMetadata))
        {
            return $this->serializedPropertyName($propertyMetadata);
        }
        return $resolver->extractProperty($propertyMetadata,$targetObject);
    }

    /**
     * Extracts the property value from an object using direct access or getters in case of non public access
     *
     * @param ClassMetadata $classMetadata
     * @param PropertyMetadata $propertyMetadata
     * @param $object
     * @return mixed
     */
    protected function getPropertyValue(ClassMetadata $classMetadata, PropertyMetadata $propertyMetadata, $object)
    {
        if($propertyMetadata->access === 'public')
        {
            return $object->{$propertyMetadata->name};
        }
        $getterName = $this->namingStrategy->getterName($propertyMetadata);
        if(isset($classMetadata->methods[$getterName]))
        {
            return $object->{$getterName}();
        }
        throw new \RuntimeException('Cannot find a way to extract the property '.$classMetadata->name.'::'.$propertyMetadata->name);
    }

    /**
     * Adds a type cast strategy to the hydrator. Note the strategies are indexed by their name/sypported types so in
     * the same strategy name the last supported type string only would work.
     *
     * @param TypeCastStrategyInterface $castStrategy
     * @return $this
     */
    public function addTypeCastStrategy(TypeCastStrategyInterface $castStrategy)
    {
        $strategy = $castStrategy->strategy();
        $strategy = $strategy === null ? '_default' : $strategy;
        if(!isset($this->castStrategies[$strategy]))
        {
            $this->castStrategies[$strategy] = [];
        }
        $this->castStrategies[$strategy][] = $castStrategy;
        return $this;
    }

    /**
     * Retuns the supported typecast strategy service for the given type
     *
     * @param TypeMetadata $metadata
     * @return TypeCastStrategyInterface|null
     */
    public function getSupportedTypecastStrategy(TypeMetadata $metadata)
    {
        foreach($this->castStrategies[$this->strategy] as $castStrategy)
        {
            if($castStrategy->isSupported($metadata))
            {
                return $castStrategy;
            }
        }
        foreach($this->castStrategies['_default'] as $castStrategy)
        {
            if($castStrategy->isSupported($metadata))
            {
                return $castStrategy;
            }
        }
        return null;
    }

    /**
     * Extracts the metadata from a class
     *
     * @param string $class
     * @return ClassMetadata
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \ReflectionException
     */
    public function getClassMetadata(string $class): ClassMetadata
    {
        return $this->driver->getClassMetadata($this->instantiator->getReflectionClass($class));
    }

    /**
     * Adds a property name resolver in the list
     *
     * @param PropertyValueResolverInterface $propertyTargetResolver
     * @return $this
     */
    public function addPropertyResolver(PropertyValueResolverInterface $propertyTargetResolver)
    {
        $strategy = $propertyTargetResolver->strategy();
        $strategy = $strategy === null ? '_default' : $strategy;
        $this->propertyResolvers[$strategy] = $propertyTargetResolver;
        return $this;
    }

    /**
     * Returns the property resolver for the current or default strategy
     *
     * @return PropertyValueResolverInterface|null
     */
    protected function getPropertyResolver()
    {
        if(isset($this->propertyResolvers[$this->strategy]))
        {
            return $this->propertyResolvers[$this->strategy];
        }
        if(isset($this->propertyResolvers['_default']))
        {
            return $this->propertyResolvers['_default'];
        }
        return null;
    }

    /**
     * Enables the built-in annotation metadata driver in the hydrator
     *
     * @param string $cacheDir
     * @return $this
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    public function enableAnnotations(?string $cacheDir=null)
    {
        $this->driver->enableAnnotations($cacheDir);
        return $this;
    }

    /**
     * Registers a cache pool in the metadata driver
     *
     * @param CacheInterface $cache
     * @return $this
     */
    public function addCache(CacheInterface $cache)
    {
        $this->driver->registerCache($cache);
        return $this;
    }

    /**
     * Attaches a hook listener to the hydrator
     *
     * @param HydratorHookInterface $hook
     * @return mixed
     */
    public function attachHook(HydratorHookInterface $hook)
    {
        $this->hooks[$hook->strategy() === null ? '_default' : $hook->strategy()] = $hook;
        return $this;
    }

    /**
     * Returns the hydrator hook for the registered strategy, if any
     *
     * @param string $strategy
     * @return HydratorHookInterface|null
     */
    public function getHookListener(string $strategy): ?HydratorHookInterface
    {
        return isset($this->hooks[$strategy]) ? $this->hooks[$strategy] : null;
    }

    /**
     * Overrides the current instantiator
     *
     * @param InstantiatorInterface $instantiator
     */
    public function setInstantiator(InstantiatorInterface $instantiator)
    {
        $this->instantiator = $instantiator;
    }

    /**
     * Returns the hydrator's metadata driver for testing custom instantiation scenarios
     *
     * @return MetadataDriverInterface
     */
    public function getMetadataDriver(): MetadataDriverInterface
    {
        return $this->driver;
    }
}