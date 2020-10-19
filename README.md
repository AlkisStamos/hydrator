# hydrator
hydrates or extracts data from arrays to PHP classes and back

## Usage
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
