<?php

use Hotcom\Controllers\ApiController;

if (class_exists(ApiController::class)) {
  $controller = new ApiController();
  $controller->apiResponseError(500);
}
