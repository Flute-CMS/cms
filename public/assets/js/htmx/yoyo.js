(function (global, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else {
        if (!global.Yoyo) {
            global.Yoyo = factory();
        }
    }
})(typeof self !== 'undefined' ? self : this, function () {
    return (function () {
        'use strict';

        window.YoyoEngine = window.htmx;

        window.addEventListener('popstate', (event) => {
            event?.state?.yoyo?.forEach((state) =>
                restoreComponentStateFromHistory(state),
            );
        });

        var Yoyo = {
            url: null,
            config(options) {
                Object.assign(YoyoEngine.config, options);
            },
            on(name, callback) {
                YoyoEngine.on(window, name, (event) => {
                    delete event.detail.elt;
                    callback(event.detail);
                });
            },
            createNonExistentIdTarget(targetId) {
                if (
                    targetId &&
                    targetId[0] === '#' &&
                    !document.querySelector(targetId)
                ) {
                    let targetDiv = document.createElement('div');
                    targetDiv.setAttribute('id', targetId.slice(1));
                    document.body.appendChild(targetDiv);
                }
            },
            afterProcessNode(evt) {
                if (evt.detail.elt) {
                    this.createNonExistentIdTarget(
                        evt.detail.elt.getAttribute('hx-target'),
                    );
                }

                let component;

                if (!evt.detail.elt || !isComponent(evt.detail.elt)) {
                    component = YoyoEngine.closest(
                        evt.detail.elt,
                        '[hx-swap~=innerHTML]',
                    );
                } else {
                    component = getComponent(evt.detail.elt);
                }

                if (!component) {
                    return;
                }

                initializeComponentSpinners(component);
            },
            bootstrapRequest(evt) {
                const elt = evt.detail.elt;
                let component = getComponent(elt);
                const componentName = getComponentName(component);

                if (
                    elt.hasAttribute('yoyo:ignore') ||
                    elt.closest('[yoyo\\:ignore]')
                ) {
                    return;
                }

                const currentUrl = new URL(window.location.href);
                const currentParams = currentUrl.searchParams;

                const action = getActionAndParseArguments(evt.detail);
                evt.detail.parameters[
                    'component'
                ] = `${componentName}/${action}`;

                const yoyoUrl = new URL(Yoyo.url, window.location.origin);

                currentParams.forEach((value, key) => {
                    if (
                        !evt.detail.parameters.hasOwnProperty(key) &&
                        key !== 'yoyo-id'
                    ) {
                        yoyoUrl.searchParams.append(key, value);
                    }
                });

                evt.detail.path = yoyoUrl.toString();

                componentAddYoyoData(component, { action });

                eventsMiddleware(evt);
            },
            processRedirectHeader(xhr) {
                if (xhr.getAllResponseHeaders().match(/Yoyo-Redirect:/i)) {
                    const url = xhr.getResponseHeader('Yoyo-Redirect');
                    if (url) {
                        const parsedUrl = new URL(url, window.location.origin);
                        parsedUrl.searchParams.delete('yoyo-id');
                        window.location = parsedUrl.toString();
                    }
                }
            },
            processHxTrigger(xhr, elt) {
                const triggerHeader = xhr.getResponseHeader('HX-Trigger');
                if (!triggerHeader) return;
                
                try {
                    const triggers = JSON.parse(triggerHeader);
                    for (const eventName in triggers) {
                        if (triggers.hasOwnProperty(eventName)) {
                            const eventDetail = triggers[eventName];
                            if (typeof eventDetail === 'object') {
                                YoyoEngine.trigger(document.body, eventName, eventDetail);
                            } else {
                                YoyoEngine.trigger(document.body, eventName, {value: eventDetail});
                            }
                        }
                    }
                } catch (e) {
                    console.error('Error processing HX-Trigger:', e, triggerHeader);
                }
            },
            processEmitEvents(elt, events) {
                if (!events || events === '[]') return;

                events =
                    typeof events === 'string' ? JSON.parse(events) : events;

                yoyoEventCache.clear();

                events.forEach((event) => {
                    triggerServerEmittedEvent(elt, event);
                });
            },
            processBrowserEvents(events) {
                if (!events) return;

                events =
                    typeof events === 'string' ? JSON.parse(events) : events;

                events.forEach((event) => {
                    window.dispatchEvent(
                        new CustomEvent(event.event, {
                            detail: event.params,
                        }),
                    );
                });
            },
            beforeRequestActions(elt) {
                let component = getComponent(elt);

                spinningStart(component);
            },
            afterOnLoadActions(evt) {
                let component = getComponentById(evt.detail.target.id);

                if (!component) {
                    if (!evt.detail.elt) {
                        return;
                    }
                    component = getComponent(evt.detail.elt);
                    if (component) {
                        spinningStop(component);
                    }
                    return;
                }

                componentCopyYoyoDataFromTo(evt.detail.target, component);

                setTimeout(() => {
                    removeEventListenerData(component);
                }, 125);
            },
            afterSettleActions(evt) {
                const component = getComponentById(evt.detail.elt.id);

                if (!component) return;

                const xhr = evt.detail.xhr;
                let enableHistory = component.hasAttribute('yoyo:history');
                let pushedUrl = xhr.getResponseHeader('Yoyo-Push');
                let triggerId =
                    evt.detail.requestConfig?.triggerEltInfo?.id ||
                    evt.detail.requestConfig.headers['HX-Trigger'];
                let href = triggerId
                    ? evt.detail.requestConfig?.triggerEltInfo?.href
                    : false;

                if (triggerId && href) {
                    pushedUrl = href;
                    enableHistory = true;
                }

                const url =
                    pushedUrl !== null ? pushedUrl : window.location.href;

                if (
                    !enableHistory ||
                    !pushedUrl ||
                    component?.__yoyo?.replayingHistory
                ) {
                    component.__yoyo.replayingHistory = false;
                    return;
                }

                componentAddYoyoData(component, {
                    effects: {
                        browserEvents:
                            xhr.getResponseHeader('Yoyo-Browser-Event'),
                        emitEvents: xhr.getResponseHeader('Yoyo-Emit'),
                    },
                });

                const componentName = getComponentName(component);

                YoyoEngine.findAll(
                    evt.detail.target,
                    '[yoyo\\:history=remove]',
                ).forEach((node) => node.remove());

                if (!componentAlreadyInCurrentHistoryState(component)) {
                    updateState(
                        'replaceState',
                        document.location.href,
                        component,
                        true,
                        evt.detail.target.outerHTML,
                    );
                }

                if (
                    !history.state?.yoyo ||
                    history.state?.initialState ||
                    url !== window.location.href
                ) {
                    updateState('pushState', url, component);
                } else {
                    updateState('replaceState', url, component);
                }
            },
        };

        let yoyoEventCache = new Set();

        let yoyoSpinners = {};

        function getActionAndParseArguments(detail) {
            let path = detail.path;
            const match = path.match(/(.*)\((.*)\)/);
            if (match) {
                let args = match[2].split(',').map((value) => {
                    const val = value
                        .replace(/'(.*)'/, '$1')
                        .replace(/"(.*)"/, '$1');
                    return isNaN(val) ? val : parseFloat(val);
                });
                path = match[1];
                detail.parameters['actionArgs'] = JSON.stringify(args);
            }

            const action = '' + path;
            return action;
        }

        function isComponent(elt) {
            return elt?.hasAttribute('yoyo:name');
        }

        function getComponent(elt) {
            let component = elt.closest('[yoyo\\:name]');
            if (component) {
                component.__yoyo = component?.__yoyo || {};
            }
            return component;
        }

        function getAllcomponents() {
            return document.querySelectorAll('[yoyo\\:name]');
        }

        function getComponentById(componentId) {
            if (!componentId) return null;

            const component = document.querySelector(`#${componentId}`);

            return isComponent(component) ? component : null;
        }

        function getComponentName(component) {
            return component.getAttribute('yoyo:name');
        }

        function getComponentFingerprint(component) {
            return `${getComponentName(
                component,
            )}:${getComponentIndex(component)}`;
        }

        function getComponentsByName(name) {
            return Array.from(
                document.querySelectorAll(`[yoyo\\:name="${name}"]`),
            );
        }

        function getComponentIndex(component) {
            const name = getComponentName(component);
            const components = getComponentsByName(name);
            return components.indexOf(component);
        }

        function getAncestorcomponents(selector) {
            let ancestor = getComponent(document.querySelector(selector));
            let ancestors = [];

            while (ancestor) {
                ancestors.push(ancestor);
                ancestor = getComponent(ancestor.parentElement);
            }

            ancestors.shift();
            return ancestors;
        }

        function shouldTriggerYoyoEvent(elt, eventName) {
            let key;
            if (isComponent(elt)) {
                key = `${elt.id}${eventName}`;
            } else if (elt.selector !== undefined) {
                return true;
            }

            if (key && !yoyoEventCache.has(key)) {
                yoyoEventCache.add(key);
                return true;
            }

            return false;
        }

        function eventsMiddleware(evt) {
            const component = getComponent(evt.detail.elt);
            const componentName = getComponentName(component);
            const eventData = component.__yoyo.eventListener;

            if (!eventData) return;

            evt.detail.parameters[
                'component'
            ] = `${componentName}/${eventData.name}`;

            if (eventData.params) {
                delete eventData.params.elt;
            }

            evt.detail.parameters = {
                ...evt.detail.parameters,
                eventParams: eventData.params
                    ? JSON.stringify(eventData.params)
                    : [],
            };
        }

        function addEmittedEventParametersToListenerComponent(
            component,
            event,
            params,
        ) {
            let componentListeningFor = component
                .getAttribute('hx-trigger')
                .split(',')
                .map((name) => name.trim())
                .filter(Boolean);

            if (!componentListeningFor.includes(event)) {
                return;
            }

            componentAddYoyoData(component, {
                eventListener: { name: event, params: params },
            });
        }

        function triggerServerEmittedEvent(elt, event) {
            const component = getComponent(elt);
            const eventName = event.event;
            const params = event.params;
            const selector = event.selector || null;
            const componentName = event.component || null;
            const propagation = event.propagation || null;
            let elements;

            if (!selector && !componentName) {
                elements = getAllcomponents();
            } else if (componentName) {
                if (propagation === 'ancestorsOnly') {
                    elements = getAncestorcomponents(selector);
                } else if (propagation === 'self') {
                    elements = [component];
                } else {
                    elements = getComponentsByName(componentName);
                }
            } else if (selector) {
                elements = Array.from(
                    document.querySelectorAll(selector),
                ).filter((element) => !component.contains(element));
                elements.forEach((elt) => (elt.selector = selector));
            }

            if (elements && elements.length) {
                elements.forEach((elt) => {
                    if (shouldTriggerYoyoEvent(elt, eventName)) {
                        addEmittedEventParametersToListenerComponent(
                            getComponent(elt),
                            eventName,
                            params,
                        );
                        YoyoEngine.trigger(elt, eventName, params);
                    }
                });
            }
        }

        function removeEventListenerData(component) {
            delete component.__yoyo.eventListener;
        }

        function spinningStart(component) {
            const componentId = component.id;

            if (!yoyoSpinners[componentId]) {
                return;
            }

            let spinningElts = yoyoSpinners[componentId].generic || [];

            spinningElts = spinningElts.concat(
                yoyoSpinners[componentId]?.actions[component.__yoyo.action] ||
                    [],
            );

            delete yoyoSpinners[component.id];

            spinningElts.forEach((directive) => {
                const spinnerElt = directive.elt;
                if (directive.modifiers.includes('class')) {
                    let classes = directive.value.split(' ').filter(Boolean);

                    doAndSetCallbackOnElToUndo(
                        component,
                        directive,
                        () => spinnerElt.classList.add(...classes),
                        () => spinnerElt.classList.remove(...classes),
                    );
                } else if (directive.modifiers.includes('attr')) {
                    doAndSetCallbackOnElToUndo(
                        component,
                        directive,
                        () => spinnerElt.setAttribute(directive.value, true),
                        () => spinnerElt.removeAttribute(directive.value),
                    );
                } else {
                    doAndSetCallbackOnElToUndo(
                        component,
                        directive,
                        () => (spinnerElt.style.display = 'inline-block'),
                        () => (spinnerElt.style.display = 'none'),
                    );
                }
            });
        }

        function spinningStop(component) {
            if (!component.__yoyo_on_finish_loading) {
                return;
            }

            while (component.__yoyo_on_finish_loading.length > 0) {
                component.__yoyo_on_finish_loading.shift()();
            }
        }

        function initializeComponentSpinners(component) {
            const componentId = component.id;
            component.__yoyo_on_finish_loading = [];

            walk(component, (elt) => {
                const directive = extractModifiersAndValue(elt, 'spinning');
                if (directive) {
                    const yoyoSpinOnAction = elt.getAttribute('yoyo:spin-on');
                    if (yoyoSpinOnAction) {
                        yoyoSpinOnAction
                            .split(',')
                            .map((action) => action.trim())
                            .forEach((action) => {
                                addActionSpinner(
                                    componentId,
                                    action,
                                    directive,
                                );
                            });
                    } else {
                        addGenericSpinner(componentId, directive);
                    }
                }
            });
        }

        function checkSpinnerInitialized(componentId, action) {
            yoyoSpinners[componentId] = yoyoSpinners[componentId] || {
                actions: {},
                generic: [],
            };
            if (
                action &&
                yoyoSpinners?.[componentId]?.actions?.[action] === undefined
            ) {
                yoyoSpinners[componentId].actions[action] = [];
            }
        }

        function addActionSpinner(componentId, action, directive) {
            checkSpinnerInitialized(componentId, action);
            yoyoSpinners[componentId].actions[action].push(directive);
        }

        function addGenericSpinner(componentId, directive) {
            checkSpinnerInitialized(componentId);
            yoyoSpinners[componentId].generic.push(directive);
        }

        function doAndSetCallbackOnElToUndo(
            el,
            directive,
            doCallback,
            undoCallback,
        ) {
            if (directive.modifiers.includes('remove'))
                [doCallback, undoCallback] = [undoCallback, doCallback];

            if (directive.modifiers.includes('delay')) {
                let timeout = setTimeout(() => {
                    doCallback();
                    el.__yoyo_on_finish_loading.push(() => undoCallback());
                }, 200);

                el.__yoyo_on_finish_loading.push(() => clearTimeout(timeout));
            } else {
                doCallback();
                el.__yoyo_on_finish_loading.push(() => undoCallback());
            }
        }

        function componentAlreadyInCurrentHistoryState(component) {
            if (!history?.state?.yoyo) return false;

            return history.state.yoyo.some(
                (state) =>
                    state.fingerprint === getComponentFingerprint(component),
            );
        }

        function updateState(
            method,
            url,
            component,
            initialState,
            originalHTML,
        ) {
            const id = component.id;
            const componentName = getComponentName(component);
            const componentIndex = getComponentIndex(component);
            const fingerprint = getComponentFingerprint(component);
            const html = originalHTML ? originalHTML : component.outerHTML;
            const effects = component.__yoyo.effects || {};

            const newState = {
                url,
                id,
                componentName,
                componentIndex,
                fingerprint,
                html,
                effects,
                initialState,
            };

            const stateArray =
                method === 'pushState'
                    ? [newState]
                    : replaceStateByComponentIndex(newState);

            history[method](
                { yoyo: stateArray, initialState: initialState },
                '',
                url,
            );
        }

        function replaceStateByComponentIndex(newState) {
            let stateArray = history?.state?.yoyo || [];
            let fingerprintFound = false;
            stateArray = stateArray.map((state) => {
                if (state.fingerprint === newState.fingerprint) {
                    fingerprintFound = true;
                    return newState;
                }

                return state;
            });

            if (!fingerprintFound) {
                stateArray.push(newState);
            }

            return stateArray;
        }

        function restoreComponentStateFromHistory(state) {
            const componentName = state.componentName;
            const componentsWithSameName = getComponentsByName(componentName);
            let component = componentsWithSameName[state.componentIndex];

            if (!component) {
                component = getComponentById(state.id);

                if (!component) return;
            }

            var parser = new DOMParser();
            var cached = parser.parseFromString(state.html, 'text/html').body
                .firstElementChild;

            component.replaceWith(cached);

            htmx.process(cached);

            if (state.initialState) {
                componentAddYoyoData(cached, { replayingHistory: true });
                YoyoEngine.trigger(cached, 'refresh');
            } else {
                Yoyo.processBrowserEvents(state?.effects?.browserEvents);
                Yoyo.processEmitEvents(component, state?.effects?.emitEvents);
            }
        }

        function componentCopyYoyoDataFromTo(from, to) {
            to.__yoyo = from?.__yoyo || {};
            to.__yoyo_on_finish_loading = from?.__yoyo_on_finish_loading || [];
        }

        function componentAddYoyoData(component, data) {
            if (!data) return;
            component.__yoyo = Object.assign(component.__yoyo || {}, data);
        }

        function walk(el, callback) {
            if (callback(el) === false) return;

            let node = el.firstElementChild;

            while (node) {
                walk(node, callback);

                node = node.nextElementSibling;
            }
        }

        function extractModifiersAndValue(elt, type) {
            const attr = elt
                .getAttributeNames()
                .filter((name) => name.startsWith(`yoyo:${type}`));

            if (attr.length) {
                const name = attr[0];
                const modifiers = name
                    .replace(`yoyo:${type}`, '')
                    .split('.')
                    .slice(1);

                const value = elt.getAttribute(name);
                return { elt, name, value, modifiers };
            }

            return false;
        }

        return Yoyo;
    })();
});

YoyoEngine.defineExtension('morph', {});

YoyoEngine.defineExtension('yoyo', {
    onEvent: function (name, evt) {
        if (name === 'htmx:afterProcessNode') {
            Yoyo.afterProcessNode(evt);
        }

        if (name === 'htmx:configRequest') {
            if (!evt.detail.elt) return;

            Yoyo.bootstrapRequest(evt);
        }

        if (name === 'htmx:beforeRequest') {
            if (!Yoyo.url) {
                console.error('The yoyo URL needs to be defined');
                evt.preventDefault();
            }

            Yoyo.beforeRequestActions(evt.detail.elt);
        }

        if (name === 'htmx:afterOnLoad') {
            Yoyo.afterOnLoadActions(evt);

            const xhr = evt.detail.xhr;

            Yoyo.processHxTrigger(xhr, evt.detail.elt);

            Yoyo.processEmitEvents(
                evt.detail.elt,
                xhr.getResponseHeader('Yoyo-Emit'),
            );

            Yoyo.processBrowserEvents(
                xhr.getResponseHeader('Yoyo-Browser-Event'),
            );

            Yoyo.processRedirectHeader(xhr);

            let modifier = xhr.getResponseHeader('Yoyo-Swap-Modifier');
            if (!modifier) return;
            let swap = modifier.match(/swap:([0-9.]+)s/);
            let time = swap && swap[1] ? swap[1] * 1000 + 1 : 0;
            setTimeout(() => {
                if (
                    !evt.detail.target.isConnected &&
                    document.querySelector(
                        `[hx-target="#${evt.detail.target.id}"]`,
                    )
                ) {
                    Yoyo.createNonExistentIdTarget(`#${evt.detail.target.id}`);
                }
            }, time);
        }

        if (name === 'htmx:beforeSwap') {
            if (!evt.detail.elt) return;

            let triggerId =
                evt.detail.requestConfig.headers['HX-Trigger'] || null;
            let triggeringElt = htmx.find(`#${triggerId}`);
            if (triggerId && triggeringElt) {
                evt.detail.requestConfig.triggerEltInfo = {
                    id: triggerId,
                    href: triggeringElt.getAttribute('href'),
                };
            }

            const modifier =
                evt.detail.xhr.getResponseHeader('Yoyo-Swap-Modifier');

            if (modifier) {
                const swap =
                    evt.detail.elt.getAttribute('hx-swap') ||
                    YoyoEngine.config.defaultSwapStyle;
                evt.detail.elt.setAttribute('hx-swap', `${swap} ${modifier}`);
            }
        }

        if (name === 'htmx:afterSettle') {
            if (!evt.detail.elt || !evt.detail.elt.isConnected) return;

            Yoyo.afterSettleActions(evt);
        }
    },
    isInlineSwap: function (swapStyle) {
        let config = createMorphConfig(swapStyle);
        return config?.morphStyle === 'outerHTML' || config?.morphStyle == null;
    },
    handleSwap: function (swapStyle, target, fragment) {
        let config = createMorphConfig(swapStyle);
        if (config) {
            return Idiomorph.morph(target, fragment.children, config);
        }
    },
});

function createMorphConfig(swapStyle) {
    if (swapStyle === 'morph' || swapStyle === 'morph:outerHTML') {
        return { morphStyle: 'outerHTML' };
    } else if (swapStyle === 'morph:innerHTML') {
        return { morphStyle: 'innerHTML' };
    } else if (swapStyle.startsWith('morph:')) {
        return Function('return (' + swapStyle.slice(6) + ')')();
    }
}
