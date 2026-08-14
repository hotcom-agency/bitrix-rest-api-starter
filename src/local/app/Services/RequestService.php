<?php

namespace Hotcom\Services;

use Bitrix\Iblock\PropertyEnumerationTable;
use Bitrix\Main\Context;
use Bitrix\Main\Mail\Event;
use CIBlockElement;
use Hotcom\Constants\MailSettings;
use Hotcom\Dto\RequestCreateDto;

class RequestService extends AbstractService
{
  protected string $requestContent = '';

  protected array $fields = [];

  protected string $iblockCode = 'requests';

  /**
   * Карта соответствия полей формы и их читаемых названий (идут в верстку сообщения)
   */
  private array $formFields = [
    'name'         => 'Имя',
    'email'        => 'Email',
    'phone'        => 'Телефон',
    'venue'        => 'Площадка',
    'people_count' => 'Количество человек',
    'room'         => 'Категория номера',
    'dates'        => 'Период проживания',
    'message'      => 'Комментарий'
  ];

  /**
   * Список полей, которые должны записаться напрямую в свойства инфоблока
   */
  private array $allowedProperties = [
    'email',
    'phone',
    'location',
    'type',
    'message'
  ];

  /**
   * Создание нового запроса на основе DTO
   * 
   * @param RequestCreateDto $dto
   * @return array|null
   */
  public function create(RequestCreateDto $dto): ?array
  {
    // Превращаем DTO в массив для внутренней логики маппинга
    $this->fields = $dto->toArray();

    $id = $this->createElement();

    if ($id) {
      $this->sendMail();
    }

    return $id ? ['ID' => $id] : null;
  }

  /**
   * Создание элемента инфоблока
   * 
   * @return int|null
   */
  private function createElement(): ?int
  {
    if (!$this->iblockId) {
      return null;
    }

    // Инициализация базовых параметров нового элемента инфоблока
    $arParams = [
      'IBLOCK_ID'       => $this->iblockId,
      'NAME'            => ($this->fields['name'] !== '') ? $this->fields['name'] : 'Запрос от ' . date('d.m.Y H:i:s'),
      'PROPERTY_VALUES' => []
    ];

    if (empty($this->fields['type'])) {
      $this->fields['type'] = 'other';
    }

    // Тема обращения
    $enumResult = PropertyEnumerationTable::getList([
      "select" => ["XML_ID", "ID", "VALUE"],
      "filter" => [
        "PROPERTY.CODE" => "TYPE",
        "PROPERTY.IBLOCK_ID" => $this->iblockId
      ],
      "cache" => ["ttl" => 86400]
    ])->fetchAll();

    $typeIds   = array_column($enumResult, "ID", "XML_ID");
    $typeNames = array_column($enumResult, "VALUE", "XML_ID");
    $arParams['PROPERTY_VALUES']['TYPE'] = (int)($typeIds[$this->fields['type']] ?? 0);
    $typeName = $typeNames[$this->fields['type']] ?? 'Общее обращение';
    
    $this->requestContent .= "<h3>Тема обращения: " . htmlspecialchars($typeName) . "</h3>" . PHP_EOL;

    // Генерация HTML-контента для остальных полей письма
    foreach ($this->formFields as $key => $label) {
      if ($this->fields[$key] !== '') {
        $this->requestContent .= "<p><b>{$label}</b>: " . htmlspecialchars($this->fields[$key]) . "</p>" . PHP_EOL;

        if (in_array($key, $this->allowedProperties, true)) {
          $arParams['PROPERTY_VALUES'][strtoupper($key)] = $this->fields[$key];
        }
      }
    }

    // Обработка и запись свойства LOCATION (очистка от GET-параметров)
    if ($this->fields['location'] !== '') {
      $this->fields['location'] = strtok($this->fields['location'], "?");
      $arParams['PROPERTY_VALUES']['LOCATION'] = $this->fields['location'];
    }

    // @phpstan-ignore class.notFound
    $element = new CIBlockElement;
    $elementId = $element->Add($arParams);

    if (!$elementId) {
      error_log('Ошибка CIBlockElement::Add: ' . $element->LAST_ERROR);
    }

    return $elementId ? (int)$elementId : null;
  }

  /**
   * Отправка почтового уведомления
   */
  private function sendMail(): void
  {
    $emailFrom = MailSettings::getEmailFromValue();
    $emailTo   = MailSettings::getEmailToValue();
    $emailCopy = MailSettings::getEmailCopyValue();

    if (($this->fields['type'] ?? '') === 'event') {
      $salesTo = MailSettings::getEmailSalesToValue();
      if ($salesTo !== '') {
        $emailTo = $salesTo;
        $emailCopy = MailSettings::getEmailSalesCopyValue();
      }
    }

    $host = parse_url((string)($this->fields['location'] ?? ''), PHP_URL_HOST)
      ?: Context::getCurrent()->getServer()->getHttpHost()
      ?: \Bitrix\Main\SiteTable::getByPrimary('s1')->fetch()['SERVER_NAME']
      ?: 'bx-request-service.local';

    $cFields = [
      'MS_EMAIL_FROM'   => $emailFrom,
      'MS_EMAIL_TO'     => $emailTo,
      'MS_EMAIL_COPY'   => $emailCopy,
      'MS_WEBSITE_NAME' => strtok($host, ':')
    ];

    foreach ($this->formFields as $key => $label) {
      if ($this->fields[$key] !== '') {
        $cFields[strtoupper($key)] = $this->fields[$key];
      }
    }

    foreach ($this->allowedProperties as $key) {
      if (($this->fields[$key] ?? '') !== '') {
        $cFields[strtoupper($key)] = $this->fields[$key];
      }
    }

    if ($this->requestContent !== '') {
      $cFields['MESSAGE'] = $this->requestContent;
    }

    try {
      $result = Event::send([
        "EVENT_NAME" => "FEEDBACK_FORM",
        "LID"        => "s1",
        "C_FIELDS"   => $cFields,
      ]);

      if (!$result->isSuccess()) {
        error_log('Event::send (RequestService): ' . implode(', ', $result->getErrorMessages()));
      }
    } catch (\Throwable $th) {
      error_log('Event::send (RequestService): ' . $th->getMessage());
    }
  }
}
