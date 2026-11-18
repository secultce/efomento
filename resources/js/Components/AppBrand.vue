<template>
    <span class="app-brand">
        <img src="/images/logos/logo-sipa.svg" alt="SIPA" width="158" height="48" class="app-brand__logo" />
        <img
            src="/images/logos/logo-sipa-description.svg"
            alt="Sistema de integração de processos administrativos"
            width="176"
            height="49"
            class="app-brand__description"
        />
    </span>
</template>

<style scoped>
.app-brand {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    vertical-align: middle;
}

.app-brand__logo {
    display: block;
    width: 126.4px;
    height: auto;
}

.app-brand__description {
    display: block;
    width: 140.8px;
    height: auto;
}

@media (max-width: 599px) {
    .app-brand {
        gap: 4px;
    }

    .app-brand__logo {
        width: 94.8px;
    }

    .app-brand__description {
        width: 105.6px;
    }
}
</style>
