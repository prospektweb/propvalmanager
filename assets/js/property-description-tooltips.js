(function () {
    'use strict';

    var CONFIG_NAME = 'ProspektPropValManagerDescriptions';
    var ROOT_CLASS = 'pvm-description-tooltip';
    var SKU_SELECTOR = '.sku-props .sku-props__value[data-onevalue], .sku-props [data-pvm-enum-id]';
    var PRODUCT_SELECTOR = '[data-pvm-property-description="product"]';
    var CANDIDATE_SELECTOR = SKU_SELECTOR + ', ' + PRODUCT_SELECTOR;
    var HIDE_DELAY = 180;
    var dataPromise = null;
    var descriptionsData = null;
    var activeTrigger = null;
    var activeItem = null;
    var activeMode = null;
    var hideTimer = null;
    var tooltip = null;
    var closeButton = null;
    var tooltipHovered = false;
    var eventsBound = false;

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
            return;
        }

        callback();
    }

    function getConfig() {
        var config = window[CONFIG_NAME];
        if (!config || typeof config !== 'object') {
            return null;
        }

        if (!config.jsonUrl || typeof config.jsonUrl !== 'string') {
            return null;
        }

        return config;
    }

    function loadDescriptions() {
        if (descriptionsData) {
            return Promise.resolve(descriptionsData);
        }

        if (dataPromise) {
            return dataPromise;
        }

        var config = getConfig();
        if (!config) {
            dataPromise = Promise.resolve(null);
            return dataPromise;
        }

        dataPromise = fetch(config.jsonUrl, {
            credentials: 'same-origin',
            cache: 'force-cache'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Property descriptions JSON request failed: ' + response.status);
                }

                return response.json();
            })
            .then(function (data) {
                descriptionsData = data && typeof data === 'object' ? data : null;
                return descriptionsData;
            })
            .catch(function () {
                descriptionsData = null;
                return null;
            });

        return dataPromise;
    }

    function closestTrigger(target, selector) {
        if (!target || target === document || !target.closest) {
            return null;
        }

        return target.closest(selector);
    }

    function isInsideCurrentTrigger(node) {
        return !!(activeTrigger && node && activeTrigger.contains(node));
    }

    function isInsideTooltip(node) {
        return !!(tooltip && node && tooltip.contains(node));
    }

    function getDatasetValue(element, names) {
        if (!element || !element.dataset) {
            return '';
        }

        for (var index = 0; index < names.length; index += 1) {
            var value = element.dataset[names[index]];
            if (value !== undefined && value !== null && String(value) !== '') {
                return String(value);
            }
        }

        return '';
    }

    function getIblockId(element) {
        return getDatasetValue(element, ['pvmIblockId', 'iblockId', 'iblockid'])
            || getDatasetValue(element.closest && element.closest('[data-pvm-iblock-id], [data-iblockid], [data-iblock-id]'), ['pvmIblockId', 'iblockId', 'iblockid'])
            || getDatasetValue(element.closest && element.closest('[data-offer-iblockid], [data-offer-iblock-id]'), ['offerIblockid', 'offerIblockId']);
    }

    function getItemByKey(data, key) {
        if (!data || !data.items || !key) {
            return null;
        }

        return data.items[key] || null;
    }

    function findDescription(element) {
        if (!descriptionsData || !element) {
            return null;
        }

        var enumId = getDatasetValue(element, ['pvmEnumId', 'onevalue']);
        if (enumId && descriptionsData.enum && descriptionsData.enum[enumId]) {
            var itemByEnum = getItemByKey(descriptionsData, descriptionsData.enum[enumId].key);
            if (hasRenderableContent(itemByEnum)) {
                return itemByEnum;
            }
        }

        var iblockId = getIblockId(element);
        var propertyCode = getDatasetValue(element, ['pvmPropertyCode', 'propertyCode']);
        var valueXmlId = getDatasetValue(element, ['pvmValueXmlId', 'valueXmlId']);
        if (iblockId && propertyCode && valueXmlId && descriptionsData.byCode) {
            var codeKey = descriptionsData.byCode[iblockId]
                && descriptionsData.byCode[iblockId][propertyCode]
                && descriptionsData.byCode[iblockId][propertyCode][valueXmlId];
            var itemByCode = getItemByKey(descriptionsData, codeKey);
            if (hasRenderableContent(itemByCode)) {
                return itemByCode;
            }
        }

        var propertyId = getDatasetValue(element, ['pvmPropertyId', 'propertyId']);
        if (iblockId && propertyId && valueXmlId && descriptionsData.byPropertyId) {
            var propertyKey = descriptionsData.byPropertyId[iblockId]
                && descriptionsData.byPropertyId[iblockId][propertyId]
                && descriptionsData.byPropertyId[iblockId][propertyId][valueXmlId];
            var itemByProperty = getItemByKey(descriptionsData, propertyKey);
            if (hasRenderableContent(itemByProperty)) {
                return itemByProperty;
            }
        }

        return null;
    }

    function hasRenderableContent(item) {
        return !!(item && (
            item.title
            || item.description
            || item.image
            || (item.link && item.link.url)
        ));
    }

    function isSafeUrl(url, allowHash) {
        if (!url || typeof url !== 'string') {
            return false;
        }

        var trimmed = url.trim();
        if (!trimmed) {
            return false;
        }

        if (allowHash && trimmed.charAt(0) === '#') {
            return true;
        }

        try {
            var parsed = new URL(trimmed, window.location.origin);
            return parsed.protocol === 'http:'
                || parsed.protocol === 'https:'
                || parsed.protocol === 'mailto:'
                || parsed.protocol === 'tel:';
        } catch (error) {
            return false;
        }
    }

    function createTooltip() {
        if (tooltip) {
            return tooltip;
        }

        tooltip = document.createElement('div');
        tooltip.className = ROOT_CLASS;
        tooltip.setAttribute('aria-hidden', 'true');
        tooltip.addEventListener('pointerenter', function () {
            tooltipHovered = true;
            clearHideTimer();
        });
        tooltip.addEventListener('pointerleave', function () {
            tooltipHovered = false;
            if (activeMode === 'hover') {
                scheduleHide();
            }
        });
        tooltip.addEventListener('click', function (event) {
            event.stopPropagation();
        });
        document.body.appendChild(tooltip);

        return tooltip;
    }

    function clearHideTimer() {
        if (hideTimer) {
            window.clearTimeout(hideTimer);
            hideTimer = null;
        }
    }

    function scheduleHide() {
        clearHideTimer();
        hideTimer = window.setTimeout(function () {
            if (tooltipHovered) {
                return;
            }

            hideTooltip();
        }, HIDE_DELAY);
    }

    function removeChildren(element) {
        while (element.firstChild) {
            element.removeChild(element.firstChild);
        }
    }

    function renderTooltip(item, mode) {
        var root = createTooltip();
        removeChildren(root);
        closeButton = null;

        root.className = ROOT_CLASS + ' ' + ROOT_CLASS + '--' + mode;
        root.setAttribute('aria-hidden', 'false');
        root.setAttribute('role', mode === 'click' ? 'dialog' : 'tooltip');

        if (mode === 'click') {
            root.setAttribute('aria-modal', 'false');
            closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.className = ROOT_CLASS + '__close';
            closeButton.setAttribute('aria-label', 'Закрыть описание свойства');
            closeButton.textContent = '×';
            closeButton.addEventListener('click', function () {
                hideTooltip(true);
            });
            root.appendChild(closeButton);
        } else {
            root.removeAttribute('aria-modal');
        }

        if (item.image && isSafeUrl(item.image, false)) {
            var image = document.createElement('img');
            image.className = ROOT_CLASS + '__image';
            image.src = item.image;
            image.alt = item.title || item.valueName || '';
            image.loading = 'lazy';
            root.appendChild(image);
        }

        if (item.title) {
            var title = document.createElement('div');
            title.className = ROOT_CLASS + '__title';
            title.textContent = item.title;
            root.appendChild(title);
        }

        if (item.description) {
            var description = document.createElement('div');
            description.className = ROOT_CLASS + '__description';
            description.textContent = item.description;
            root.appendChild(description);
        }

        if (item.link && item.link.url && isSafeUrl(item.link.url, true)) {
            var link = document.createElement('a');
            link.className = ROOT_CLASS + '__link';
            link.href = item.link.url;
            link.textContent = item.link.text || 'Подробнее';
            if (item.link.target === '_blank') {
                link.target = '_blank';
                link.rel = 'noopener noreferrer';
            }
            root.appendChild(link);
        }
    }

    function positionTooltip(trigger) {
        if (!tooltip || !trigger) {
            return;
        }

        var margin = 10;
        var rect = trigger.getBoundingClientRect();
        var tooltipRect = tooltip.getBoundingClientRect();
        var left = rect.left + rect.width / 2 - tooltipRect.width / 2;
        var top = rect.bottom + margin;

        left = Math.max(margin, Math.min(left, window.innerWidth - tooltipRect.width - margin));

        if (top + tooltipRect.height + margin > window.innerHeight && rect.top - tooltipRect.height - margin > margin) {
            top = rect.top - tooltipRect.height - margin;
            tooltip.classList.add(ROOT_CLASS + '--above');
        } else {
            tooltip.classList.remove(ROOT_CLASS + '--above');
        }

        tooltip.style.left = Math.round(left) + 'px';
        tooltip.style.top = Math.round(top) + 'px';
    }

    function showTooltip(trigger, item, mode) {
        if (!trigger || !item) {
            return;
        }

        clearHideTimer();
        activeTrigger = trigger;
        activeItem = item;
        activeMode = mode;
        renderTooltip(item, mode);
        positionTooltip(trigger);
        createTooltip().classList.add('is-open');

        if (mode === 'click' && closeButton) {
            window.setTimeout(function () {
                closeButton.focus({preventScroll: true});
            }, 0);
        }
    }

    function hideTooltip(restoreFocus) {
        clearHideTimer();

        var previousTrigger = activeTrigger;
        activeTrigger = null;
        activeItem = null;
        activeMode = null;
        tooltipHovered = false;

        if (tooltip) {
            tooltip.classList.remove('is-open');
            tooltip.setAttribute('aria-hidden', 'true');
        }

        if (restoreFocus && previousTrigger && typeof previousTrigger.focus === 'function') {
            previousTrigger.focus({preventScroll: true});
        }
    }

    function openForTrigger(trigger, mode) {
        if (!descriptionsData) {
            return;
        }

        var item = findDescription(trigger);
        if (!item) {
            return;
        }

        showTooltip(trigger, item, mode);
    }

    function handleSkuPointerOver(event) {
        var trigger = closestTrigger(event.target, SKU_SELECTOR);
        if (!trigger || (event.relatedTarget && trigger.contains(event.relatedTarget))) {
            return;
        }

        openForTrigger(trigger, 'hover');
    }

    function handleSkuPointerOut(event) {
        var trigger = closestTrigger(event.target, SKU_SELECTOR);
        if (!trigger || activeTrigger !== trigger || (event.relatedTarget && trigger.contains(event.relatedTarget))) {
            return;
        }

        if (event.relatedTarget && isInsideTooltip(event.relatedTarget)) {
            return;
        }

        scheduleHide();
    }

    function handleFocusIn(event) {
        var skuTrigger = closestTrigger(event.target, SKU_SELECTOR);
        if (skuTrigger) {
            openForTrigger(skuTrigger, 'hover');
        }
    }

    function handleFocusOut(event) {
        if (!activeTrigger || activeMode !== 'hover') {
            return;
        }

        if (event.relatedTarget && (isInsideCurrentTrigger(event.relatedTarget) || isInsideTooltip(event.relatedTarget))) {
            return;
        }

        scheduleHide();
    }

    function handleProductClick(event) {
        var trigger = closestTrigger(event.target, PRODUCT_SELECTOR);
        if (!trigger) {
            return;
        }

        var item = findDescription(trigger);
        if (!item) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        showTooltip(trigger, item, 'click');
    }

    function handleDocumentClick(event) {
        if (activeMode !== 'click') {
            return;
        }

        if (isInsideTooltip(event.target) || isInsideCurrentTrigger(event.target)) {
            return;
        }

        hideTooltip(false);
    }

    function handleKeyDown(event) {
        if (event.key === 'Escape' && activeTrigger) {
            hideTooltip(activeMode === 'click');
        }
    }

    function handleReposition() {
        if (activeTrigger && activeItem) {
            positionTooltip(activeTrigger);
        }
    }

    function bindEvents() {
        if (eventsBound) {
            return;
        }

        eventsBound = true;
        document.addEventListener('pointerover', handleSkuPointerOver);
        document.addEventListener('pointerout', handleSkuPointerOut);
        document.addEventListener('focusin', handleFocusIn);
        document.addEventListener('focusout', handleFocusOut);
        document.addEventListener('click', handleProductClick);
        document.addEventListener('click', handleDocumentClick);
        document.addEventListener('keydown', handleKeyDown);
        window.addEventListener('resize', handleReposition);
        window.addEventListener('scroll', handleReposition, true);
    }

    function bootWhenCandidatesExist() {
        loadDescriptions().then(function (data) {
            if (!data || !data.items) {
                return;
            }

            bindEvents();
        });
    }

    function init() {
        if (document.querySelector(CANDIDATE_SELECTOR)) {
            bootWhenCandidatesExist();
            return;
        }

        if (!('MutationObserver' in window) || !document.body) {
            return;
        }

        var observer = new MutationObserver(function () {
            if (!document.querySelector(CANDIDATE_SELECTOR)) {
                return;
            }

            observer.disconnect();
            bootWhenCandidatesExist();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    ready(init);
})();
