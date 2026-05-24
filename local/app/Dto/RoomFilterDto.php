<?php

namespace Hotcom\Dto;

/**
 * Объект передачи данных для фильтрации списка номеров
 */
readonly class RoomFilterDto
{
  public function __construct(
    public ?int $capacityMin = null,
    public ?int $capacityMax = null,
    public ?float $areaMin = null,
    public ?float $areaMax = null,
    public array $bedTypes = [],
    public ?string $search = null
  ) {}

  public static function fromArray(array $data): self
  {
    return new self(
      capacityMin: $data['capacity_min'] ?? null,
      capacityMax: $data['capacity_max'] ?? null,
      areaMin: $data['area_min'] ?? null,
      areaMax: $data['area_max'] ?? null,
      bedTypes: $data['bed_types'] ?? [],
      search: $data['search'] ?? null
    );
  }
}
