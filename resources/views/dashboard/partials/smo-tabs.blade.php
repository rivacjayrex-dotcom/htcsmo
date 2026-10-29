<script>
    (() => {
        if (window.__smoTabsLoaded) return;
        window.__smoTabsLoaded = true;

        const select = (tab, updateUrl = true) => {
            const workspace = tab.closest('[data-smo-dashboard]');
            workspace.querySelectorAll('[data-smo-tab]').forEach(item => {
                const active = item === tab;
                item.setAttribute('aria-selected', String(active));
                item.tabIndex = active ? 0 : -1;
                document.getElementById(item.getAttribute('aria-controls')).hidden = !active;
            });
            if (updateUrl) window.history.replaceState(window.history.state, '', tab.href);
        };

        document.addEventListener('click', event => {
            const tab = event.target.closest('[data-smo-tab]');
            if (!tab || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            select(tab);
        });

        document.addEventListener('keydown', event => {
            const tab = event.target.closest('[data-smo-tab]');
            if (!tab || !['ArrowLeft', 'ArrowRight', 'Home', 'End', ' '].includes(event.key)) return;
            event.preventDefault();
            const tabs = [...tab.closest('[role="tablist"]').querySelectorAll('[data-smo-tab]')];
            let index = tabs.indexOf(tab);
            if (event.key === 'Home') index = 0;
            else if (event.key === 'End') index = tabs.length - 1;
            else if (event.key === 'ArrowLeft') index = (index + tabs.length - 1) % tabs.length;
            else if (event.key === 'ArrowRight') index = (index + 1) % tabs.length;
            select(tabs[index]);
            tabs[index].focus({preventScroll: true});
        });

        const restore = () => {
            const key = new URL(window.location.href).searchParams.get('tab') || 'recent';
            const tabs = [...document.querySelectorAll('[data-smo-tab]')];
            const tab = tabs.find(item => item.dataset.smoTab === key) || tabs[0];
            if (tab) select(tab, false);
        };
        window.addEventListener('popstate', restore);
        document.addEventListener('htc:workspace-updated', restore);
    })();
</script>
