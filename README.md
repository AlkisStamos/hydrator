# hydrator
hydrates or extracts data from arrays to PHP classes and back

## Simple example
By default, each property will be mapped to the same key in the target array. If the property access is not public a
setter method will be used if exists (setter's name should be: set{PropName}).
```php
<?php
class MyClass
{
    public $prop1;
    public $prop2;
}
$data = ['prop1' => 'value1', 'prop2' => 'value2'];
$hydrator = new \Alks\Hydrator\Hydrator();
var_dump($hydrator->hydrate($data,MyClass::class));
```

## Using annotations
The library comes with the Property annotation which allows further customization to each property.<br>
For example in order to map custom array keys to properties:
```php
<?php
use Alks\Hydrator\Annotation\Property;
class MyClass
{
    /**
     * @Property(from="targetKey")
     */
    public $prop1;
}
$data = ['prop1' => 'value1', 'prop2' => 'value2', 'targetKey' => 'the actual value'];
$hydrator = new \Alks\Hydrator\Hydrator();
/** @var MyClass $res */
$res = $hydrator->hydrate($data,MyClass::class);
echo $res->prop1; //"the actual value"
```
Also, we can target embedded values:
```php
<?php
use Alks\Hydrator\Annotation\Property;
class MyClass
{
    /**
     * @Property(from="targetKey.child.actualKey")
     */
    public $prop1;
}
$data = ['prop1' => 'value1', 'prop2' => 'value2', 'targetKey' => ['child'=>['actualKey' => 'the actual value']]];
$hydrator = new \Alks\Hydrator\Hydrator();
/** @var MyClass $res */
$res = $hydrator->hydrate($data,MyClass::class);
echo $res->prop1; //"the actual value"
```

## Typecasting
When hydrating or extracting typecasting of values can be customized by registering type cast strategies to the hydrator.
This can be achieved by implementing the TypeCastStrategyInterface, and the addTypeCastStrategy method.<br>
By default the library comes with two typecast strategies:
 * **FlatTypeCastStrategy**: Which will typecast flat typed values (string, bool, arrays etc)
 * **DateTimeCastStrategy**: Which will typecast DateTime values (the default date format is Y-m-d\TH:i:sO)
 
## Naming Strategies
By default, the library uses the underscore naming strategy. This will map camel cased properties either to same named
array keys or snake cased keys.
```php
<?php
class MyClass
{
    public $firstProperty;
    public $secondProperty;
}
$data = ['firstProperty' => 'firstValue','second_property' => 'secondValue'];
$hydrator = new \Alks\Hydrator\Hydrator();
/** @var MyClass $res */
$res = $hydrator->hydrate($data,MyClass::class);
echo $res->secondProperty; //"secondValue"
```
Naming strategies can be customized by implementing the NamingStrategyInterface