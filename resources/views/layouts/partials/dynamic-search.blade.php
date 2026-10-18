<style>
    form[data-dynamic-search] input[type="search"] { transition: border-color .18s ease, box-shadow .18s ease, opacity .18s ease; }
    form[data-dynamic-search][aria-busy="true"] input[type="search"] { opacity: .72; }
    form[data-dynamic-search][aria-busy="true"] input[type="search"]:focus { border-color: #2d8a57; box-shadow: 0 0 0 3px rgba(31, 125, 75, .12); }
</style>

<script>
    (() => {
        const formSelector = 'form[data-dynamic-search]';
        const contentSelector = '.main-content-inner';
        const debounceDelay = 350;
        let debounceTimer = null;
        let activeRequest = null;
        let requestSequence = 0;

        const announce = (form, message) => {
            let status = form.querySelector('[data-dynamic-search-status]');
            if (!status) {
                status = document.createElement('span');
                status.className = 'visually-hidden';
                status.dataset.dynamicSearchStatus = '';
                status.setAttribute('role', 'status');
                status.setAttribute('aria-live', 'polite');
                form.appendChild(status);
            }
            status.textContent = message;
        };

        const searchUrl = form => {
            const url = new URL(form.action || window.location.href, window.location.href);
            url.search = '';

            new FormData(form).forEach((value, key) => {
                const normalized = String(value).trim();
                if (normalized !== '') url.searchParams.append(key, normalized);
            });

            // A changed search always starts at the first result page.
            url.searchParams.delete('page');
            return url;
        };

        const restoreSearchFocus = (name, selectionStart, selectionEnd) => {
            const form = document.querySelector(formSelector);
            if (!form) return;

            const input = [...form.querySelectorAll('input[type="search"]')]
                .find(candidate => candidate.name === name);
            if (!input) return;

            input.focus({preventScroll: true});
            if (typeof input.setSelectionRange === 'function') {
                input.setSelectionRange(selectionStart, selectionEnd);
            }
        };

        const refreshResults = async (form, input) => {
            const url = searchUrl(form);
            const currentContent = document.querySelector(contentSelector);
            if (!currentContent) {
                window.location.assign(url);
                return;
            }

            activeRequest?.abort();
            activeRequest = new AbortController();
            const sequence = ++requestSequence;
            const selectionStart = input.selectionStart ?? input.value.length;
            const selectionEnd = input.selectionEnd ?? selectionStart;

            form.setAttribute('aria-busy', 'true');
            announce(form, 'Searching…');

            try {
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'text/html',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    signal: activeRequest.signal,
                });

                if (!response.ok) throw new Error(`Search failed with HTTP ${response.status}`);
                if (sequence !== requestSequence) return;

                const html = await response.text();
                const documentCopy = new DOMParser().parseFromString(html, 'text/html');
                const nextContent = documentCopy.querySelector(contentSelector);
                if (!nextContent) throw new Error('Search response did not contain the page content.');

                currentContent.innerHTML = nextContent.innerHTML;
                window.history.replaceState({}, '', url);

                requestAnimationFrame(() => {
                    window.lucide?.createIcons();
                    restoreSearchFocus(input.name, selectionStart, selectionEnd);
                    const updatedForm = document.querySelector(formSelector);
                    if (updatedForm) announce(updatedForm, 'Search results updated.');
                    document.dispatchEvent(new CustomEvent('htc:dynamic-search-updated', {detail: {url: url.href}}));
                });
            } catch (error) {
                if (error.name === 'AbortError') return;
                // Retain a reliable server-rendered fallback if an asynchronous refresh fails.
                window.location.assign(url);
            } finally {
                if (sequence === requestSequence) {
                    document.querySelector(formSelector)?.removeAttribute('aria-busy');
                }
            }
        };

        document.addEventListener('input', event => {
            const input = event.target.closest(`${formSelector} input[type="search"]`);
            if (!input) return;

            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(() => refreshResults(input.form, input), debounceDelay);
        });
    })();
</script>
