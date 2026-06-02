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
- `samples-aspro/*` — примеры минимальных правок шаблона Аспро Премьер.

## Установка
1. Поместите папку модуля в `/local/modules/prospektweb.propvalmanager`.
2. В панели Bitrix: **Marketplace → Установленные решения → Установить**.
3. На шаге установки модуль попробует автоматически определить инфоблок товаров и SKU; при необходимости выберите ID вручную.
4. Проверьте, что после сохранения описаний модуль сгенерировал JSON вида `/upload/prospektweb.propvalmanager/property-descriptions-<version>.json`.
5. Внесите минимальные правки в шаблон Аспро из раздела ниже, если они ещё не применены.

## Удаление
1. В панели Bitrix: **Marketplace → Установленные решения → Удалить**.
2. На шаге удаления выберите:
   - `N` — оставить данные.
   - `Y` — удалить только свойства, созданные модулем.
3. Модуль восстановит оригинальные Aspro-файлы из backup. Директория модуля и установочные файлы при удалении не удаляются.

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

### Что изменить в шаблоне Аспро вручную

Примеры готовых изменений лежат в `samples-aspro/`. Администратор может перенести их в рабочий шаблон вручную.

#### 1. Подключить JS/CSS на странице детальной карточки

Рабочий файл Аспро обычно находится здесь:

```text
/bitrix/templates/aspro-premier/components/bitrix/catalog.element/main/component_epilog.php
```

Добавьте импорты рядом с существующим `use Bitrix\Main\Localization\Loc;`:

```php
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
```

После вызова:

```php
TSolution\Extensions::init($arExtensions);
```

добавьте:

```php
if (Loader::includeModule('prospektweb.propvalmanager')) {
    Asset::getInstance()->addCss('/local/modules/prospektweb.propvalmanager/assets/css/property-description-tooltips.css');
    Asset::getInstance()->addJs('/local/modules/prospektweb.propvalmanager/assets/js/property-description-tooltips.js');
}
```

Если модуль установлен не в `/local/modules`, скорректируйте пути к `assets/css/property-description-tooltips.css` и `assets/js/property-description-tooltips.js`.

#### 2. Добавить data-атрибуты к значениям свойств товара

Рабочий файл Аспро обычно находится здесь:

```text
/bitrix/templates/aspro-premier/include/blocks/catalog/props/list.php
```

В примере `samples-aspro/include/blocks/catalog/props/list.php` добавлен блок подготовки `$pvmAttrs` и вывод этих атрибутов на `div.properties__value`.

Целевой результат в HTML для однозначно сопоставимого значения должен быть одним из вариантов:

```html
<div class="properties__value ..."
     data-pvm-property-description="product"
     data-pvm-enum-id="461">
  Цифровая печать
</div>
```

или:

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

Лучший вариант — `data-pvm-enum-id`, потому что JSON содержит прямой индекс `enum`.

Если `$arProp` в вашем шаблоне не содержит `VALUE_ENUM_ID`, `VALUE_XML_ID`, `IBLOCK_ID`, `ID` или `CODE`, обогатите массив свойств в `result_modifier.php` и передайте технические поля:

```php
$arProp['PVM_ENUM_ID'] = '461';
$arProp['PVM_IBLOCK_ID'] = '14';
$arProp['PVM_PROPERTY_ID'] = '718';
$arProp['PVM_PROPERTY_CODE'] = 'CALC_METHOD';
$arProp['PVM_VALUE_XML_ID'] = 'DIGITAL';
```

Для множественных списочных значений не рекомендуется сопоставлять описание по склеенному тексту. Если в одном `properties__value` выводится несколько значений, лучше выводить каждое значение отдельным inline-элементом со своим `data-pvm-enum-id` или набором `data-pvm-*`.

#### 3. SKU-свойства торговых предложений

Для SKU в Аспро Премьер обычно достаточно существующего атрибута:

```html
<button class="sku-props__value" data-onevalue="399">...</button>
```

JS использует `data-onevalue` как enum ID и ищет описание через `json.enum[enumId]`. Если в вашей версии Аспро `data-onevalue` отсутствует или не является enum ID, добавьте на SKU-кнопку:

```html
data-pvm-enum-id="399"
```

или полный набор:

```html
data-pvm-iblock-id="15"
data-pvm-property-id="706"
data-pvm-property-code="FORMAT"
data-pvm-value-xml-id="A7"
```

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
