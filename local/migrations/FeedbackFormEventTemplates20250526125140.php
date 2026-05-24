<?php

namespace Sprint\Migration;

use Hotcom\Helpers\Migration;

class FeedbackFormEventTemplates20250526125140 extends Migration
{
  protected $author = "hc_admin";

  protected $description = "Создание шаблона email уведомлений";

  protected $moduleVersion = "5.3.3";

  /**
   * @throws Exceptions\HelperException
   * @return bool|void
   */
  public function up()
  {
    $helper = $this->getHelperManager();

    $defaultEventMessages = $helper->Event()->getEventMessages('FEEDBACK_FORM');

    foreach ($defaultEventMessages as $key => $value) {
      $helper->Event()->deleteEventMessage(array(
        'LID' => array(
          0 => 's1',
        ),
        'SUBJECT' => $value['SUBJECT'],
        'EVENT_NAME' => $value['EVENT_NAME']
      ));
    }

    $helper->Event()->saveEventType('FEEDBACK_FORM', array(
      'LID' => 'ru',
      'EVENT_TYPE' => 'email',
      'NAME' => 'Отправка сообщения через форму обратной связи',
      'DESCRIPTION' => '#NAME# - Email автора запроса
#PHONE# - Телефон автора запроса
#EMAIL# - Email автора запроса
#MESSAGE# - Сообщение
#MESSAGE# - Тема запроса
#LOCATION# - Url раздела
#MS_EMAIL_FROM# - Email отправителя
#MS_EMAIL_TO# - Email получателя
#MS_EMAIL_COPY# - Email получателя (копия)
#MS_WEBSITE_NAME# - Название (хост) сайта',
      'SORT' => '7',
    ));

    $helper->Event()->saveEventMessage('FEEDBACK_FORM', array(
      'LID' =>
      array(
        0 => 's1',
      ),
      'ACTIVE' => 'Y',
      'EMAIL_FROM' => '#MS_EMAIL_FROM#',
      'EMAIL_TO' => '#MS_EMAIL_TO#',
      'SUBJECT' => '[#MS_WEBSITE_NAME#] запрос обратной связи',
      'MESSAGE' => '
      <h2>Новый запрос&nbsp;[#MS_WEBSITE_NAME#]</h2>
      <h3><b>#THEME#</b></h3>
      #MESSAGE#
      <p>
      ----
      </p>
      <p><b>Отправлено:</b> <a href="#LOCATION#" target="_blank">#LOCATION#</a></p>',
      'BODY_TYPE' => 'html',
      'BCC' => '',
      'REPLY_TO' => '',
      'CC' => '#MS_EMAIL_COPY#',
      'IN_REPLY_TO' => '',
      'PRIORITY' => '',
      'FIELD1_NAME' => '',
      'FIELD1_VALUE' => '',
      'FIELD2_NAME' => '',
      'FIELD2_VALUE' => '',
      'SITE_TEMPLATE_ID' => '',
      'ADDITIONAL_FIELD' =>
      array(),
      'LANGUAGE_ID' => 'ru',
      'EVENT_TYPE' => '[FEEDBACK_FORM] Отправка сообщения через форму обратной связи',
    ));
  }

  /**
   * @throws Exceptions\HelperException
   * @return bool|void
   */
  public function down()
  {
    $helper = $this->getHelperManager();

    $defaultEventMessages = $helper->Event()->getEventMessages('FEEDBACK_FORM');

    foreach ($defaultEventMessages as $key => $value) {
      $helper->Event()->deleteEventMessage(array(
        'LID' => array(
          0 => 's1',
        ),
        'SUBJECT' => $value['SUBJECT'],
        'EVENT_NAME' => $value['EVENT_NAME']
      ));
    }
  }
}
