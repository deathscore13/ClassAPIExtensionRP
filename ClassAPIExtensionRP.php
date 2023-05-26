<?php

/**
 * ClassAPIExtensionRP
 * 
 * Расширение API классов для PHP 8.0.0+
 * https://github.com/deathscore13/ClassAPIExtensionRP
 */

require(__DIR__.'/ReflectionProtect/ReflectionProtect.php');

trait ClassAPIExtensionRPObject
{
    use ReflectionProtectObjectPrivate;

    /**
     * Необходимо вызвать в __construct()
     */
    private function __apiInit(): void
    {
        ReflectionProtect::method();

        $this->__pv('__apiQueue', []);              /**< Очередь для автозагрузки методов */
        $this->__pv('__apiMethods', []);            /**< Добавленные методы */
        $this->__pv('__apiReflectionData', []);     /**< Данные Reflection API для методов */
    }

    /**
     * Необходимо вызвать в __destruct()
     */
    private function __apiDestroy(): void
    {
        ReflectionProtect::method();
        
        $this->__pv('__apiQueue', 0, true);
        $this->__pv('__apiMethods', 0, true);
        $this->__pv('__apiReflectionData', 0, true);
    }

    /**
     * Добавление callback функции для автозагрузки методов
     * 
     * @param callable $callback    Имя функции или анонимная функция | callback(string $name): ?callable
     *                              Если callback функция вернёт не анонимную функцию, то будет выполнен function_exists
     *                              Верните null, если функция не была найдена
     * @param bool $prepend         Если true, то указанная функция будет помещена в начало очереди
     */
    public function __apiAutoload(callable $callback, bool $prepend = false): void
    {
        if (is_string($callback) && !function_exists($callback))
            throw new Exception('Argument #1 ($callback) must be a valid callback, function "'.$callback.
            '" not found or invalid function name');

        $queue = &$this->__pv('__apiQueue');
        if (in_array($callback, $queue))
            return;
        
        if ($prepend)
            array_unshift($queue, $callback);
        else
            $queue[] = $callback;
    }

    /**
     * Добавление метода для вызова
     * 
     * @param string $name          Имя метода
     * @param callable $callback    Callback функция
     */
    public function __apiAddMethod(string $name, callable $callback): void
    {
        $methods = &$this->__pv('__apiMethods');
        if (array_key_exists($name, $methods))
            throw new Exception('Cannot redeclare '.self::class.'::'.$name.'()');
        
        if (is_string($callback) && !function_exists($callback))
            throw new Exception('Argument #1 ($callback) must be a valid callback, function "'.$callback.
            '" not found or invalid function name');
        
        $methods[$name] = $callback;
    }

    /**
     * Проверка наличия добавленного метода
     * 
     * @param string $name          Имя метода
     * 
     * @return bool                 true если метод был добавлен, false если нет
     */
    public function __apiMethodExists(string $name): bool
    {
        return isset(&$this->__pv('__apiMethods')[$name]);
    }

    /**
     * Вызов метода с рабочими ссылками
     * 
     * @param string $name          Имя функции | name(string $self, object $this, ...): mixed
     * @param mixed &...$args       Входящие аргументы, в которых работают ссылки, в отличие от магического метода __call()
     * 
     * @return mixed                Возвращаемое значение функции
     */
    public function &__apiCall(string $name, mixed &...$args): mixed
    {
        $methods = &$this->__pv('__apiMethods');
        if (!isset($methods[$name]))
        {
            foreach (&$this->__pv('__apiQueue') as $callback)
            {
                $ret = $callback($name);

                if ($ret !== null &&
                    (is_string($ret) && function_exists($ret) ||
                    is_callable($ret)))
                {
                    $this->__apiAddMethod($name, $ret);
                    break;
                }
            }

            if (!isset($methods[$name]))
                throw new Exception('Call to undefined method '.self::class.'::'.$name.'()');
        }

        $rData = &$this->__pv('__apiReflectionData');
        if (!isset($rData[$name]))
            $rData[$name] = (new ReflectionFunction($methods[$name]))->returnsReference();
        
        if ($rData[$name])
            return $methods[$name](self::class, $this, ...$args);
        
        $buffer = $methods[$name](self::class, $this, ...$args);
        return $buffer;
    }

    public function __call(string $name, array $args): mixed
    {
        return $this->__apiCall($name, ...$args);
    }
}

trait ClassAPIExtensionRPStatic
{
    use ReflectionProtectStaticPrivate;

    /**
     * Необходимо вызвать перед использованием методов
     */
    public function __apiInitStatic(): void
    {
        static $ready = false;

        if ($ready)
            return;
        $ready = true;

        self::__pvs('__apiQueueStatic', []);            /**< Очередь для автозагрузки статических методов */
        self::__pvs('__apiMethodsStatic', []);          /**< Добавленные статические методы */
        self::__pvs('__apiReflectionDataStatic', []);   /**< Данные Reflection API для статических методов */
    }

    /**
     * Добавление callback функции для автозагрузки статических методов
     * 
     * @param callable $callback    Имя функции или анонимная функция | callback(string $name): ?callable
     *                              Если callback функция вернёт не анонимную функцию, то будет выполнен function_exists
     *                              Верните null, если функция не была найдена
     * @param bool $prepend         Если true, то указанная функция будет помещена в начало очереди
     */
    public static function __apiAutoloadStatic(callable $callback, bool $prepend = false): void
    {
        if (is_string($callback) && !function_exists($callback))
            throw new Exception('Argument #1 ($callback) must be a valid callback, function "'.$callback.
            '" not found or invalid function name');

        $queue = &self::__pvs('__apiQueueStatic');
        if (in_array($callback, $queue))
            return;
        
        if ($prepend)
            array_unshift($queue, $callback);
        else
            $queue[] = $callback;
    }

    /**
     * Добавление статического метода для вызова
     * 
     * @param string $name          Имя метода
     * @param callable $callback    Callback функция
     */
    public static function __apiAddMethodStatic(string $name, callable $callback): void
    {
        $methods = &self::__pvs('__apiMethodsStatic');
        if (array_key_exists($name, $methods))
            throw new Exception('Cannot redeclare '.self::class.'::'.$name.'()');
        
        if (is_string($callback) && !function_exists($callback))
            throw new Exception('Argument #1 ($callback) must be a valid callback, function "'.$callback.
            '" not found or invalid function name');
        
        $methods[$name] = $callback;
    }

    /**
     * Проверка наличия добавленного статического метода
     * 
     * @param string $name          Имя метода
     * 
     * @return bool                 true если метод был добавлен, false если нет
     */
    public static function __apiMethodExistsStatic(string $name): bool
    {
        return isset(&self::__pvs('__apiMethodsStatic')[$name]);
    }

    /**
     * Вызов статического метода с рабочими ссылками
     * 
     * @param string $name          Имя функции | name(string $self, ...): mixed
     * @param mixed &...$args       Входящие аргументы, в которых работают ссылки, в отличие от магического метода __callStatic()
     * 
     * @return mixed                Возвращаемое значение функции
     */
    public static function &__apiCallStatic(string $name, mixed &...$args): mixed
    {
        $methods = &self::__pvs('__apiMethodsStatic');
        if (!isset($methods[$name]))
        {
            foreach (&self::__pvs('__apiQueueStatic'); as $callback)
            {
                $ret = $callback($name);

                if ($ret !== null &&
                    (is_string($ret) && function_exists($ret) ||
                    is_callable($ret)))
                {
                    self::__apiAddMethodStatic($name, $ret);
                    break;
                }
            }

            if (!isset($methods[$name]))
                throw new Exception('Call to undefined method '.self::class.'::'.$name.'()');
        }

        if (!isset(self::$__apiReflectionDataStatic[$name]))
            self::$__apiReflectionDataStatic[$name] = (new ReflectionFunction($methods[$name]))->returnsReference();
        
        if (self::$__apiReflectionDataStatic[$name])
            return $methods[$name](self::class, ...$args);
        
        $buffer = $methods[$name](self::class, ...$args);
        return $buffer;
    }

    public static function __callStatic(string $name, array $args): mixed
    {
        return self::__apiCallStatic($name, ...$args);
    }
}

trait ClassAPIExtensionRPPropertyObject
{
    use ReflectionProtectObjectPrivate;

    /**
     * Необходимо вызвать в __construct()
     */
    private function __apiInitProperty(): void
    {
        ReflectionProtect::method();

        $this->__pv('__apiProperties', []);         /**< Добавленные статические проперти */
    }

    /**
     * Необходимо вызвать в __destruct()
     */
    private function __apiDestroyProperty(): void
    {
        ReflectionProtect::method();

        $this->__pv('__apiProperties', 0, true);
    }
    
    /**
     * Добавление/получение статической проперти
     * 
     * @param string $name          Имя проперти
     * @param mixed $value          Новое значение проперти (если не указано, то не меняет значение)
     * 
     * @return mixed                Значение проперти
     */
    public static function &__apiProperty(string $name, mixed $value = 0): mixed
    {
        $properties = &$this->__pv('__apiProperties');
        if (func_num_args() === 2)
            $properties[$name] = $value;
        else if (!isset($properties[$name]))
            throw new Exception('Access to undeclared static property '.self::class.'::$'.$name);
        
        return $properties[$name];
    }
    
    /**
     * Проверка наличия добавленной статической проперти
     * 
     * @param string $name          Имя проперти
     * 
     * @return bool                 true если проперти была добавлена, false если нет
     */
    public static function __apiIsset(string $name): bool
    {
        return isset(&$this->__pv('__apiProperties')[$name]);
    }
    
    /**
     * Удаление добавленной статической проперти
     * 
     * @param string $name          Имя проперти
     */
    public static function __apiUnset(string $name): void
    {
        unset(&$this->__pv('__apiProperties')[$name]);
    }
}

trait ClassAPIExtensionRPPropertyStatic
{
    use ReflectionProtectStaticPrivate;

    /**
     * Необходимо вызвать перед использованием методов
     */
    public function __apiInitPropertyStatic(): void
    {
        static $ready = false;

        if ($ready)
            return;
        $ready = true;

        self::__pvs('__apiPropertiesStatic', []);       /**< Добавленные статические проперти */
    }
    
    /**
     * Добавление/получение статической проперти
     * 
     * @param string $name          Имя проперти
     * @param mixed $value          Новое значение проперти (если не указано, то не меняет значение)
     * 
     * @return mixed                Значение проперти
     */
    public static function &__apiPropertyStatic(string $name, mixed $value = 0): mixed
    {
        $properties = &self::__pvs('__apiPropertiesStatic');
        if (func_num_args() === 2)
            $properties[$name] = $value;
        else if (!isset($properties[$name]))
            throw new Exception('Access to undeclared static property '.self::class.'::$'.$name);
        
        return $properties[$name];
    }
    
    /**
     * Проверка наличия добавленной статической проперти
     * 
     * @param string $name          Имя проперти
     * 
     * @return bool                 true если проперти была добавлена, false если нет
     */
    public static function __apiIssetStatic(string $name): bool
    {
        return isset(&self::__pvs('__apiPropertiesStatic')[$name]);
    }
    
    /**
     * Удаление добавленной статической проперти
     * 
     * @param string $name          Имя проперти
     */
    public static function __apiUnsetStatic(string $name): void
    {
        unset(&self::__pvs('__apiPropertiesStatic')[$name]);
    }
}

trait ClassAPIExtensionRP
{
    use ClassAPIExtensionRPObject, ClassAPIExtensionRPStatic, ClassAPIExtensionRPPropertyObject, ClassAPIExtensionRPPropertyStatic;
}
