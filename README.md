# ClassAPIExtensionRP
### Расширение API классов с использованием ReflectionProtect для PHP 8.0.0+<br><br>

Позволяет добавить в класс объектные/статические методы и статические проперти<br><br>
Для доступа к **`private`** и **`protected`** методам/проперти используйте [Reflection API](https://www.php.net/manual/ru/book.reflection.php), если они не защищены<br><br>
**ОБРАТИТЕ ВНИМАНИЕ, что библиотека использует подмодули и безопаснее будет использовать [require_hash](https://github.com/deathscore13/require_hash) в вашем проекте**<br><br>
Советую открыть **`ClassAPIExtensionRP.php`** и почитать описания методов

<br><br>
### Зависимости и компоненты
1. Основано на [ClassAPIExtension](https://github.com/deathscore13/ClassAPIExtension) (коммит [088d794](https://github.com/deathscore13/ClassAPIExtension/tree/088d79449c8779a27dfabc91be4911d36ef9f972))
2. Содержит [ReflectionProtect](https://github.com/deathscore13/ReflectionProtect)
3. Содержит [require_hash](https://github.com/deathscore13/require_hash)
4. Советую использовать вместе с [ExplicitCallCheck](https://github.com/deathscore13/ExplicitCallCheck)

<br><br>
### Требования
1. PHP 8.0.0+
2. Модуль [Reflection](https://www.php.net/manual/ru/book.reflection.php)

<br><br>
### Ограничения PHP
1. Передача параметров по ссылке работают только через `__apiCall()` и `__apiCallStatic()`
2. Возврат значения по ссылке работает только через `__apiCall()` и `__apiCallStatic()`
3. Нельзя использовать имена `self`/`$this` вне класса, используйте `$self`/`$obj` или другие имена

<br><br>
## Подключение возможностей
`use ClassAPIExtensionRPObject;` - добавление объектных методов (доступ к `self` и `$this`)<br>
`use ClassAPIExtensionRPStatic;` - добавление статических методов (доступ к `self`)<br>
`use ClassAPIExtensionRPProperty;` - добавление объектных проперти<br>
`use ClassAPIExtensionRPPropertyStatic;` - добавление статических проперти<br>
`use ClassAPIExtensionRP;` - добавление всех возможностей

<br><br>
## Пример добавления методов
**`BaseClass.php`**:
```php
// подключение ClassAPIExtensionRP
require('ClassAPIExtensionRP/ClassAPIExtensionRP.php');

class BaseClass
{
    // подключение возможностей вызова через объект класса и статический вызов
    use ClassAPIExtensionRPObject, ClassAPIExtensionRPStatic;

    public function __construct()
    {
        // сброс явного вызова конструктора
        if (ExplicitCallCheck::check())
            return;

        // инициализация ClassAPIExtensionRPObject
        $this->__apiInit();
    }

    public function __destruct()
    {
        // сброс явного вызова деструктора
        if (ExplicitCallCheck::check())
            return;
        
        // очистка ClassAPIExtensionRPObject
        $this->__apiDestroy();
    }
    
    // метод для тестирование вызова через $this из добавленной функции
    public function test(): void
    {
        // вывод выполнения метода
        echo('BaseClass::test()'.PHP_EOL);
    }
    
    // приватный метод для тестирования вызова через self из добавленной функции
    private static function test2(): void
    {
        // вывод выполнения статического метода
        echo('BaseClass::test2()'.PHP_EOL);
    }
}
```
**`BaseClassAPI/method.php`**:
```php
// реализация нового метода
return function (string $self, object $obj): void
{
    // вызов BaseClass::test()
    $obj->test();

    // вызов приватного статического метода (читайте документацию к Reflection API)
    $r = new ReflectionMethod($self, 'test2');
    $r->setAccessible(true);
    $r->invoke(null);
}
```
**`BaseClassAPI/static/methodStatic.php`**:
```php
// реализация нового статического метода
return function &(string $self, int &$value): void
{
    // получение и возврат значения по ссылке
    return $value;
}
```
**`main.php`**:
```php
// подключение класса
require('BaseClass.php');

// создание объекта BaseClass
$b = new BaseClass();

// регистрация функции для автозагрузки методов
$b->__apiAutoload(function (string $name): ?callable
{
    $name = 'BaseClassAPI/'.$name.'.php';
    if (is_file($name))
        return require($name);
    
    return null;
});

// регистрация функции для автозагрузки статических методов
BaseClass::__apiAutoloadStatic(function (string $name): ?callable
{
    $name = 'BaseClassAPI/static/'.$name.'.php';
    if (is_file($name))
        return require($name);
    
    return null;
});

// вызов метода method(). так как __apiAddMethod() не был выполнен, то будет совершена автозагрузка
$b->method();

// вызов статического метода methodStatic() с рабочими ссылками
$value = 123;
$value2 = &BaseClass::__apiCallStatic('methodStatic', $value);
$value2 = 321;
echo($value.PHP_EOL);
```
<br><br>
## Пример добавления проперти
**`BaseClass.php`**:
```php
// подключение ClassAPIExtensionRP
require('ClassAPIExtensionRP/ClassAPIExtensionRP.php');

class BaseClass
{
    // подключение возможности добавления объектных и статических проперти
    use ClassAPIExtensionRPPropertyObject, ClassAPIExtensionRPPropertyStatic;

    public function __construct()
    {
        // сброс явного вызова конструктора
        if (ExplicitCallCheck::check())
            return;

        // инициализация ClassAPIExtensionRPPropertyObject
        $this->__apiInitProperty();
    }

    public function __destruct()
    {
        // сброс явного вызова деструктора
        if (ExplicitCallCheck::check())
            return;
        
        // очистка ClassAPIExtensionRPPropertyObject
        $this->__apiDestroyProperty();
    }
}
```
**`main.php`**:
```php
// подключение класса
require('BaseClass.php');

// создание объекта BaseClass
$b = new BaseClass();

// добавление проперти
$p = &$b->__apiProperty('property', 1);

// проверка наличия проперти
if ($b->__apiIsset('property'))
    echo('BaseClass::$property = '.$p.PHP_EOL); // вывод значения проперти

// удаление проперти
$b->__apiUnset('property');

// добавление статического проперти
$ps = &BaseClass::__apiPropertyStatic('propertyStatic', 2);

// проверка наличия статического проперти
if (BaseClass::__apiIssetStatic('propertyStatic'))
    echo('BaseClass::$propertyStatic = '.$ps.PHP_EOL); // вывод значения статического проперти

// удаление статического проперти
BaseClass::__apiUnsetStatic('propertyStatic');
```
