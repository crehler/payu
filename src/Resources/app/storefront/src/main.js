const PluginManager = window.PluginManager;

PluginManager.register(
    'CardTokenization',
    () => import('./cr-payu-card-tokenization/card-tokenization'),
    '[data-card-tokenization]'
);
