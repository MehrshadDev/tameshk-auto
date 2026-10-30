<script data-navigate-once>
    (() => {
        const apply = () => {
            const theme = localStorage.getItem('theme');
            const dark = theme === 'dark' || (theme === null && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        };
        apply();
        document.addEventListener('livewire:navigated', apply);
    })();
</script>
