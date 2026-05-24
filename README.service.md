# Создание нового информационного блока

## Пример этапов создания информационного блока 

### 1. Создание миграции
Создайте файл миграции через административную панель `HC DTO Миграция` или с помощью `make migration-create`:
- Определите тип инфоблока и его настройки
- Реализуйте методы `up()` и `down()`


### 2. Создание DTO
Создайте файл в `local/app/Dto/[Name]Dto.php`:
- Определите структуру данных
- Реализуйте геттеры/сеттеры
- Добавьте метод [toArray()]

### 3. Создание сервиса
Создайте файл в `local/app/Services/[Name]Service.php`:
- Наследуйте класс от `AbstractService`
- Установите в `$iblockCode` код  инфоблока
- Определите `$defaultFields` с требуемыми полями
- Реализуйте CRUD-операции с использованием D7 ORM
- Используйте `$this->apiCache->get()` для кэширования
- Используйте `$this->getCacheTags()` для тегов кэша

### 4. Создание контроллера
Создайте файл в `local/app/Controllers/[Name]Controller.php`:
- Наследуйте класс от `ApiController`
- Реализуйте методы `indexAction()`, `showAction()`, `createAction()`, `updateAction()`, `deleteAction()`

### 5. Добавление маршрута
Обновите файл `local/routes/api.php`:

- Добавьте маршрут для нового ресурса

## Примеры метода show

### Пример в сервисе
```php
public function getItemByCode(string $slug): ?ItemDto
{
    return $this->apiCache->get(
        key: $this->buildCacheKey('detail', $slug),
        callback: function () use ($slug) {
            $query = ElementItemTable::query()
                ->setFilter(['=CODE' => $slug, 'ACTIVE' => 'Y'])
                ->setSelect($this->getSelect());

            $collection = QueryHelper::decompose($query, true);
            $collection->rewind();
            $item = $collection->valid() ? $collection->current() : null;

            return $item ? $this->mapToDto($item) : null;
        },
        tags: $this->getCacheTags()
    );
}
```

### Пример в контроллере
```php
public function show(string $slug): never
{
    $item = $this->service->getItemByCode($slug);

    if ($item) {
        $this->apiResponse($item);
    }

    $this->apiResponseError(404);
}
```

### Пример в маршруте
```php
$routes->get('items/{slug}', fn(string $slug) => (new ItemController())->show($slug))->release();
```
