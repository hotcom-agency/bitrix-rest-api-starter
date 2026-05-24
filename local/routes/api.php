<?php

use Bitrix\Main\Routing\RoutingConfigurator;
use Hotcom\Controllers\PageController;
use Hotcom\Controllers\RoomController;
use Hotcom\Controllers\RequestController;

return function (RoutingConfigurator $routes) {
  $routes->prefix('api/v1')->group(function (RoutingConfigurator $routes) {

    // Добавляем ->release() в конец каждого роута комнат
    $routes->get('pages/{slug}', fn(string $slug) => (new PageController())->show($slug));

    $routes->get('rooms', fn() => (new RoomController())->index())->release();
    $routes->get('rooms/facets', fn() => (new RoomController())->facets())->release();
    $routes->get('rooms/{slug}', fn(string $slug) => (new RoomController())->show($slug))->release();

    $routes->post('requests/create', fn() => (new RequestController())->create());
  });
};
