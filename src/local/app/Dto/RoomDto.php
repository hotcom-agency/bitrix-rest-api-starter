<?php

namespace Hotcom\Dto;
/**
 * Объект передачи данных
 */
readonly class RoomDto
{
  public function __construct(
    public int $id,
    public string $name,
    public ?string $description,
    public ?array $image,
    public array $gallery,
    public ?int $capacity,
    public ?float $area,
    public array $bedTypes,
    public string $slug,
    public int $sort
  ) {}
}
