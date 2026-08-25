(function () {
    'use strict';

    var activePopover = null;
    var activeButton = null;

    function post(action, values) {
        var form = new FormData();
        form.append('action', action);
        form.append('nonce', THGAdmin.nonce);
        Object.keys(values).forEach(function (key) {
            form.append(key, values[key]);
        });
        return window.fetch(THGAdmin.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: form
        }).then(function (response) {
            return response.json();
        });
    }

    function closePopover() {
        if (activePopover) {
            activePopover.remove();
        }
        if (activeButton) {
            activeButton.setAttribute('aria-expanded', 'false');
            activeButton.focus();
        }
        activePopover = null;
        activeButton = null;
    }

    function positionPopover(button, popover) {
        var rect = button.getBoundingClientRect();
        var left = rect.left + window.scrollX;
        var maxLeft = window.scrollX + document.documentElement.clientWidth - popover.offsetWidth - 12;
        popover.style.top = (rect.bottom + window.scrollY + 6) + 'px';
        popover.style.left = Math.max(window.scrollX + 12, Math.min(left, maxLeft)) + 'px';
    }

    function createPopover(button) {
        var popover = document.createElement('section');
        popover.className = 'thg-popover';
        popover.setAttribute('role', 'dialog');
        popover.setAttribute('aria-modal', 'false');
        popover.innerHTML = '<div class="thg-popover-head"><h2></h2><button type="button" class="thg-popover-close"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span><span class="screen-reader-text"></span></button></div><div class="thg-popover-body" aria-live="polite"></div>';
        popover.querySelector('h2').textContent = button.getAttribute('data-title') || THGAdmin.labels.help;
        popover.querySelector('.screen-reader-text').textContent = THGAdmin.labels.close;
        popover.querySelector('.thg-popover-body').textContent = THGAdmin.labels.loading;
        popover.querySelector('.thg-popover-close').addEventListener('click', closePopover);
        document.body.appendChild(popover);
        positionPopover(button, popover);
        return popover;
    }

    function openGuide(button) {
        closePopover();
        activeButton = button;
        activeButton.setAttribute('aria-expanded', 'true');
        activePopover = createPopover(button);
        post('thg_inline_get_post', { id: button.getAttribute('data-wiki-id') })
            .then(function (response) {
                if (!activePopover) {
                    return;
                }
                if (!response || !response.success) {
                    throw new Error('Request failed');
                }
                activePopover.querySelector('h2').textContent = response.data.title || THGAdmin.labels.help;
                activePopover.querySelector('.thg-popover-body').innerHTML = response.data.html || '';
                positionPopover(button, activePopover);
                activePopover.querySelector('.thg-popover-close').focus();
            })
            .catch(function () {
                if (activePopover) {
                    activePopover.querySelector('.thg-popover-body').textContent = THGAdmin.labels.loadFailure;
                }
            });
    }

    function addInlineTargets(matches) {
        matches.forEach(function (guide) {
            guide.targets.forEach(function (target) {
                var nodes;
                try {
                    nodes = document.querySelectorAll(target.selector);
                } catch (error) {
                    return;
                }
                var node = nodes[target.position - 1];
                if (!node || (node.nextElementSibling && node.nextElementSibling.classList.contains('thg-inline-button'))) {
                    return;
                }
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'thg-inline-button';
                button.setAttribute('data-wiki-id', guide.id);
                button.setAttribute('data-title', guide.title || THGAdmin.labels.help);
                button.setAttribute('aria-label', guide.title || THGAdmin.labels.help);
                button.setAttribute('aria-expanded', 'false');
                node.insertAdjacentElement('afterend', button);
            });
        });
    }

    function initialiseInlineHelp() {
        if (!THGAdmin.acfAvailable) {
            return;
        }
        post('thg_inline_targets', { url: window.location.href })
            .then(function (response) {
                if (response && response.success) {
                    addInlineTargets(response.data.matches || []);
                }
            })
            .catch(function () {});
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.thg-inline-button');
        if (button) {
            event.preventDefault();
            openGuide(button);
        } else if (activePopover && !event.target.closest('.thg-popover')) {
            closePopover();
        }
    });
    document.addEventListener('keydown', function (event) {
        if ('Escape' === event.key && activePopover) {
            closePopover();
        }
    });
    window.addEventListener('resize', function () {
        if (activePopover) {
            closePopover();
        }
    });
    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('.thg-tree-toggle');
        if (!toggle) {
            return;
        }
        var childList = toggle.parentElement.querySelector(':scope > .thg-tree');
        if (!childList) {
            return;
        }
        var expanded = 'true' === toggle.getAttribute('aria-expanded');
        toggle.setAttribute('aria-expanded', String(!expanded));
        toggle.querySelector('[aria-hidden="true"]').textContent = expanded ? '+' : '−';
        childList.hidden = expanded;
    });

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', initialiseInlineHelp);
    } else {
        initialiseInlineHelp();
    }
}());
