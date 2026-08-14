<?php
namespace Hotcom\Dto;

/**
 * Объект передачи данных для фасетных (агрегированных) фильтров номеров
 */
readonly class RoomFacetsDto
{
  public function __construct(
    public int $totalCount,
    public array $bedTypes,
    public ?int $minCapacity,
    public ?int $maxCapacity,
    public ?float $minArea,
    public ?float $maxArea
  ) {}
}
