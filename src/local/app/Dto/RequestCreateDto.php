<?php

namespace Hotcom\Dto;

/**
 * Объект передачи данных для создания новой заявки
 * 
 * @package Hotcom\Dto
 */
class RequestCreateDto
{
  public ?string $name = null;
  public ?string $email = null;
  public ?string $phone = null;
  public ?string $venue = null;
  public ?string $people_count = null;
  public ?string $room = null;
  public ?string $dates = null;
  public ?string $message = null;
  public ?string $type = null;
  public ?string $location = null;

  /**
   * Массив для накопления ошибок валидации
   * 
   * @var array<string, string>
   */
  private array $errors = [];

  /**
   * Создание объекта DTO из входящего массива параметров
   * 
   * @param array<string, mixed> $data Массив параметров запроса
   * @return self
   */
  public static function fromArray(array $data): self
  {
    $dto = new self();

    foreach (get_object_vars($dto) as $key => $default) {
      if ($key === 'errors') continue;

      $dto->{$key} = is_string($data[$key] ?? null) ? trim($data[$key]) : null;
    }

    return $dto;
  }

  /**
   * Проверка валидности обязательных полей запроса
   * 
   * @return bool
   */
  public function isValid(): bool
  {
    $this->errors = [];

    if (empty($this->name)) {
      $this->errors['name'] = 'The Name field is required.';
    }

    if (empty($this->phone)) {
      $this->errors['phone'] = 'The Phone field is required.';
    }

    return empty($this->errors);
  }

  /**
   * Получение списка накопленных ошибок валидации
   * 
   * @return array<string, string>
   */
  public function getErrors(): array
  {
    return $this->errors;
  }

  /**
   * Преобразование объекта DTO в плоский массив для совместимости с сервисами
   * 
   * @return array<string, string>
   */
  public function toArray(): array
  {
    $result = [];

    foreach (get_object_vars($this) as $key => $value) {
      if ($key === 'errors') continue;
      $result[$key] = $value ?? '';
    }

    return $result;
  }
}
