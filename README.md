# Property Value Manager (`prospektweb.propvalmanager`)

Bitrix-модуль для управления расширенными описаниями значений списочных свойств инфоблоков.

## Возможности
- Проверка зависимостей (`iblock`, `catalog`, `sale`, `highloadblock`) при установке.
- Создание Highload-блока для расширенных описаний значений списочных свойств.
- Административный интерфейс выбора каталога/SKU-инфоблока и управления связями описаний.
- PHP API для пакетного получения описаний с кэшированием.
- Генерация публичного versioned JSON с описаниями значений свойств.
- Безопасная публичная JS-конфигурация `window.ProspektPropValManagerDescriptions` с URL и версией JSON.
- Frontend tooltip/popover для публичной карточки товара Аспро Премьер.
- Настройки модуля через `Option` с typed-обёрткой.
- Удаление с шагом подтверждения и флагом `remove_data`.

## Структура
- `install/*` — установка/удаление модуля.
- `lib/Service/*` — сервисный слой.
- `options.php` — admin UI для настроек.
- `include.php` — автозагрузка классов.
- `assets/js/property-description-tooltips.js` — публичный JS tooltip/popover.
- `assets/css/property-description-tooltips.css` — публичные стили tooltip/popover.
- `samples-aspro/*` — готовые версии файлов Аспро Премьер, которые установщик копирует в рабочий шаблон с backup оригиналов.

## Установка
1. Поместите папку модуля в `/local/modules/prospektweb.propvalmanager` или `/bitrix/modules/prospektweb.propvalmanager`. Сам установщик Bitrix-модуля не копирует папку модуля в `/local/modules`: она должна уже находиться в одном из этих каталогов до установки.
2. В панели Bitrix: **Marketplace → Установленные решения → Установить**.
3. На шаге установки модуль попробует автоматически определить инфоблок товаров и SKU; при необходимости выберите ID вручную.
4. Установщик автоматически найдёт поддерживаемые файлы шаблона Аспро Премьер сначала в `/local/templates/aspro-premier/`, затем в `/bitrix/templates/aspro-premier/`, заменит найденные файлы готовыми версиями из `samples-aspro/` и сохранит оригиналы в backup.
5. Проверьте, что после сохранения описаний модуль сгенерировал JSON вида `/upload/prospektweb.propvalmanager/property-descriptions-<version>.json`.

Администратору не нужно вручную редактировать PHP-файлы Аспро для базовой интеграции tooltip/popover. Ручная проверка нужна только если шаблон находится не по стандартным путям или файлы уже были изменены после установки модуля.

Если модуль уже был установлен до поддержки `/local/templates/aspro-premier/`, переустановите модуль или повторно выполните установочный шаг, чтобы patcher применил готовые файлы к актуальному рабочему шаблону.

## Удаление
1. В панели Bitrix: **Marketplace → Установленные решения → Удалить**.
2. На шаге удаления выберите:
   - `N` — оставить данные.
   - `Y` — удалить только свойства, созданные модулем.
3. Модуль восстановит оригинальные Aspro-файлы из backup только если текущий файл совпадает с хешем версии, установленной модулем.

Если администратор или разработчик изменил файл Аспро после установки модуля, восстановление этого файла пропускается и фиксируется в журнале Bitrix. Это защищает ручные правки от перезаписи старой оригинальной версией.

## Настройки
В `Настройки → Настройки продукта → Настройки модулей → Property Value Manager`:
- `ENABLED`
- `PRODUCTS_IBLOCK_ID`
- `OFFERS_IBLOCK_ID`

## Публичный JSON
Модуль публикует JSON с описаниями значений свойств и хранит путь/версию в настройках:
- `PROPERTY_DESCRIPTIONS_JSON_PATH`
- `PROPERTY_DESCRIPTIONS_JSON_VERSION`

На публичных HTML-страницах модуль добавляет только безопасный конфиг:

```js
window.ProspektPropValManagerDescriptions = {
  jsonUrl: '/upload/prospektweb.propvalmanager/property-descriptions-....json',
  version: '...'
};
```

Frontend tooltip/popover загружает JSON лениво: если на странице нет потенциальных элементов SKU/свойств, `fetch` не выполняется.

## Frontend tooltip/popover в Аспро Премьер

### Поведение

Для значений свойств торговых предложений:
- tooltip открывается при наведении и `focus`;
- tooltip остаётся открытым при переводе мыши с значения SKU в tooltip;
- tooltip закрывается с небольшой задержкой после ухода мыши с значения и tooltip;
- клик по значению SKU не перехватывается, выбор оффера Аспро работает штатно;
- ссылка внутри tooltip кликабельна;
- для `link.target === '_blank'` JS добавляет `target="_blank" rel="noopener noreferrer"`.

Для значений свойств товара:
- popover открывается по клику;
- фокус переводится на кнопку закрытия;
- закрытие работает по кнопке `X`, `Escape` и клику вне popover;
- клик внутри popover и клик по ссылке внутри popover не закрывает его преждевременно.

### Автоматическая замена файлов Аспро при установке

Установщик модуля выполняет бесшовную интеграцию и заменяет два файла Аспро Премьер готовыми версиями из `samples-aspro/`. Для `component_epilog.php` сначала проверяется путь в `/local/templates/aspro-premier/`, затем fallback в `/bitrix/templates/aspro-premier/`. Для блока свойств сначала проверяется корневой `/include/blocks/catalog/props/list.php`.

| Проверяемые рабочие файлы на сайте | Готовый файл в модуле | Что добавлено |
| --- | --- | --- |
| `/local/templates/aspro-premier/components/bitrix/catalog.element/main/component_epilog.php`<br>`/bitrix/templates/aspro-premier/components/bitrix/catalog.element/main/component_epilog.php` | `samples-aspro/bitrix/templates/aspro-premier/components/bitrix/catalog.element/main/component_epilog.php` | Подключение `assets/css/property-description-tooltips.css` и `assets/js/property-description-tooltips.js` через `Bitrix\Main\Page\Asset`, если модуль доступен. Путь assets определяется автоматически: сначала `/local/modules/prospektweb.propvalmanager`, затем `/bitrix/modules/prospektweb.propvalmanager`. |
| `/include/blocks/catalog/props/list.php`<br>`/local/templates/aspro-premier/include/blocks/catalog/props/list.php`<br>`/bitrix/templates/aspro-premier/include/blocks/catalog/props/list.php` | `samples-aspro/include/blocks/catalog/props/list.php` | Вывод `data-pvm-*` атрибутов на `div.properties__value` для сопоставления значений свойств товара с публичным JSON. |

Перед заменой каждого файла установщик сохраняет оригинал в:

```text
/upload/prospektweb.propvalmanager/backup/aspro/<md5-пути-файла>.bak
```

Также сохраняются хеш оригинального файла и хеш версии, установленной модулем. При удалении модуль восстанавливает оригинал только если текущий файл всё ещё совпадает с установленным модулем хешем. Если файл был изменён после установки, модуль не перезатирает его backup-версией.

Если стандартный файл Аспро не найден ни в одном из перечисленных путей, замена этого файла пропускается и записывается в журнал Bitrix. В этом случае проверьте, отличается ли путь шаблона или блока `/include/blocks` от стандартного.

Если на фронте диагностика показывает `property-description-tooltips.js is not connected` и `property-description-tooltips.css is not connected`, значит рабочий `component_epilog.php` не был заменён, страница использует другой шаблон/путь, либо папка модуля отсутствует и в `/local/modules/prospektweb.propvalmanager`, и в `/bitrix/modules/prospektweb.propvalmanager`. После применения patcher-а в HTML должны появиться подключения `.../prospektweb.propvalmanager/assets/js/property-description-tooltips.js` и `.../prospektweb.propvalmanager/assets/css/property-description-tooltips.css` из фактической директории модуля.

#### Какие data-атрибуты ожидает frontend

Для свойств товара готовый `samples-aspro/include/blocks/catalog/props/list.php` выводит `data-pvm-*` на `div.properties__value`, если в `$arProp` есть однозначный `VALUE_ENUM_ID`/`PVM_ENUM_ID` или `VALUE_XML_ID`/`PVM_VALUE_XML_ID`. Целевой HTML для одного значения должен выглядеть так:

```html
<div class="properties__value ..."
     data-pvm-property-description="product"
     data-pvm-enum-id="461">
  Цифровая печать
</div>
```

или так:

```html
<div class="properties__value ..."
     data-pvm-property-description="product"
     data-pvm-iblock-id="14"
     data-pvm-property-id="718"
     data-pvm-property-code="CALC_METHOD"
     data-pvm-value-xml-id="DIGITAL">
  Цифровая печать
</div>
```

Лучший вариант — `data-pvm-enum-id`, потому что JSON содержит прямой индекс `enum`. Если ваша сборка Аспро формирует `$arProp` без нужных идентификаторов, обогатите массив свойств в своём `result_modifier.php` полями:

```php
$arProp['PVM_ENUM_ID'] = '461';
$arProp['PVM_IBLOCK_ID'] = '14';
$arProp['PVM_PROPERTY_ID'] = '718';
$arProp['PVM_PROPERTY_CODE'] = 'CALC_METHOD';
$arProp['PVM_VALUE_XML_ID'] = 'DIGITAL';
```

Для множественных списочных значений не рекомендуется сопоставлять описание по склеенному тексту. Если в одном `properties__value` выводится несколько значений, лучше выводить каждое значение отдельным inline-элементом со своим `data-pvm-enum-id` или набором `data-pvm-*`.

Для SKU-свойств торговых предложений в Аспро Премьер обычно достаточно существующего атрибута:

```html
<button class="sku-props__value" data-onevalue="399">...</button>
```

JS использует `data-onevalue` как enum ID и ищет описание через `json.enum[enumId]`. Если в вашей версии Аспро `data-onevalue` отсутствует или не является enum ID, добавьте на SKU-кнопку `data-pvm-enum-id` или полный набор `data-pvm-iblock-id`, `data-pvm-property-id`, `data-pvm-property-code`, `data-pvm-value-xml-id`.

Клик по SKU-кнопке JS модуля не перехватывает.

## Ручное тестирование на странице товара
1. Откройте детальную страницу товара с торговыми предложениями.
2. В консоли проверьте наличие `window.ProspektPropValManagerDescriptions`.
3. Наведите мышь на значение SKU и убедитесь, что tooltip появился.
4. Переведите мышь в tooltip и убедитесь, что он не закрылся.
5. Кликните по SKU-значению и проверьте, что оффер выбирается штатно.
6. Кликните по значению свойства товара и проверьте открытие popover.
7. Проверьте закрытие popover по `X`, `Escape` и клику вне popover.
8. Проверьте, что ссылка внутри tooltip/popover кликабельна и корректно работает в режимах `_self` и `_blank`.

## Надёжность
- PHP 8+
- Повторная установка безопасна (re-run idempotency).
- Ошибки установки/удаления логируются и показываются администратору.
- Frontend-код не добавляет новых `OnEndBufferContent`-инъекций и не вмешивается в штатный клик выбора SKU.
