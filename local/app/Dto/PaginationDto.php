<?php

namespace Hotcom\Dto;

/**
 * Объект передачи данных для управления пагинацией списков
 */
readonly class PaginationDto
{
  public function __construct(
    public int $page = 1,
    public int $limit = 10
  ) {}

  public static function fromArray(array $data): self
  {
    return new self(
      page: max(1, $data['page'] ?? 1),
      limit: max(1, min($data['limit'] ?? 10, 100))
    );
  }

  public function getOffset(): int
  {
    return (max(1, $this->page) - 1) * max(1, min($this->limit, 100));
  }
}
